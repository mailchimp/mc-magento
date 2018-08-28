<?php

/**
 * mailchimp-lib Magento Component
 *
 * @category  Ebizmarts
 * @package   mailchimp-lib
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Ebizmarts_MailChimp_Model_Api_Customers
{
    const BATCH_LIMIT = 100;

    /**
     * @var Ebizmarts_MailChimp_Helper_Data
     */
    private $_mailchimpHelper;
    private $_optInConfiguration;
    private $_optInStatusForStore;
    private $_locale;
    private $_directoryRegionModel;
    private $_magentoStoreId;
    private $_mailchimpStoreId;
    private $_batchId;

    public function __construct()
    {
        $this->_mailchimpHelper = Mage::helper('mailchimp');
        $this->_optInConfiguration = array();
        $this->_locale = Mage::app()->getLocale();
        $this->_directoryRegionModel = Mage::getModel('directory/region');
    }

    public function createBatchJson($_mailchimpStoreId, $_magentoStoreId)
    {
        $this->setMailchimpStoreId($_mailchimpStoreId);
        $this->setMagentoStoreId($_magentoStoreId);

        $frontEndCollection = $this->makeCustomersNotSentCollection();
        $adminCollection = $this->makeAdminCustomersNotSentCollection();

        $collectionIds = array_unique(array_merge($frontEndCollection->getAllIds(), $adminCollection->getAllIds()));

        $collection = $this->getCustomerResourceCollection()
            ->addFieldToFilter('entity_id', array('in' => $collectionIds))
            ->addAttributeToSelect('*');

        $this->joinMailchimpSyncData($collection);

        $customerArray = array();

        $this->makeBatchId();

        $this->_optInStatusForStore = $this->getOptin($this->getBatchMagentoStoreId());

        $counter = 0;
        foreach ($collection as $customer) {
            $data = $this->_buildCustomerData($customer);
            $customerJson = json_encode($data);
            if (false !== $customerJson) {
                $customerArray[$counter] = $this->makePutBatchStructure($customerJson);
                $this->_updateSyncData($customer->getId(), $_mailchimpStoreId);
            } else {
                $this->logCouldNotEncodeCustomerError($customer);
            }

            $counter++;
        }
        return $customerArray;
    }

    /**
     * @param $customerJson
     * @return array
     */
    protected function makePutBatchStructure($customerJson)
    {
        $customerId = json_decode($customerJson)->id;

        $batchData = array();
        $batchData['method'] = 'PUT';
        $batchData['path'] = "/ecommerce/stores/{$this->_mailchimpStoreId}/customers/{$customerId}";
        $batchData['operation_id'] = "{$this->_batchId}_{$customerId}";
        $batchData['body'] = $customerJson;
        return $batchData;
    }

    protected function _buildCustomerData($customer)
    {
        $data = array();
        $data['id'] = $customer->getId();
        $data['email_address'] = $this->getCustomerEmail($customer);
        $data['first_name'] = $this->getCustomerFirstname($customer);
        $data['last_name'] = $this->getCustomerLastname($customer);
        $data['opt_in_status'] = $this->_optInStatusForStore;

        $data['orders_count'] = (int)$customer->getOrdersCount();
        $data['total_spent'] = (float)$customer->getTotalSpent();

        $data += $this->getCustomerAddressData($customer);

        if ($customer->getCompany()) {
            $data['company'] = $customer->getCompany();
        }

        return $data;
    }

    /**
     * @param $customer
     * @return array
     */
    protected function getCustomerAddressData($customer)
    {
        $data = array();
        $customerAddress = array();

        $street = explode("\n", $customer->getStreet());
        if (count($street) > 1) {
            $customerAddress['address1'] = $street[0];
            $customerAddress['address2'] = $street[1];
        } else {
            if (!empty($street[0])) {
                $customerAddress['address1'] = $street[0];
            }
        }

        if ($customer->getCity()) {
            $customerAddress['city'] = $customer->getCity();
        }

        if ($customer->getRegion()) {
            $customerAddress['province'] = $customer->getRegion();
        }

        if ($customer->getRegionId()) {
            $customerAddress['province_code'] = $this->_directoryRegionModel->load($customer->getRegionId())->getCode();
            if (!$customerAddress['province_code']) {
                unset($customerAddress['province_code']);
            }
        }

        if ($customer->getPostcode()) {
            $customerAddress['postal_code'] = $customer->getPostcode();
        }

        if ($customer->getCountryId()) {
            $customerAddress['country'] = $this->getCountryNameByCode($customer->getCountryId());
            $customerAddress['country_code'] = $customer->getCountryId();
        }

        if (!empty($customerAddress)) {
            $data['address'] = $customerAddress;
        }

        return $data;
    }

    protected function getCountryNameByCode($countryCode)
    {
        return $this->_locale->getCountryTranslation($countryCode);
    }

    /**
     * Update customer sync data after modification.
     *
     * @param $customerId
     * @param $storeId
     */
    public function update($customerId, $storeId)
    {
        $_mailchimpStoreId = $this->_mailchimpHelper->getMCStoreId($storeId);
        $this->_updateSyncData($customerId, $_mailchimpStoreId, null, null, 1, null, true, false);
    }

    public function createGuestCustomer($guestId, $order)
    {
        $guestCustomer = Mage::getModel('customer/customer')->setId($guestId);
        foreach ($order->getData() as $key => $value) {
            $keyArray = explode('_', $key);
            if ($value && isset($keyArray[0]) && $keyArray[0] == 'customer') {
                $guestCustomer->{'set' . ucfirst($keyArray[1])}($value);
            }
        }

        return $guestCustomer;
    }

    public function getOptin($_magentoStoreId)
    {
        return $this->getOptinConfiguration($_magentoStoreId);
    }

    protected function getOptinConfiguration($_magentoStoreId)
    {
        if (array_key_exists($_magentoStoreId, $this->_optInConfiguration)) {
            return $this->_optInConfiguration[$_magentoStoreId];
        }

        $this->checkEcommerceOptInConfigAndUpdateStorage($_magentoStoreId);

        return $this->_optInConfiguration[$_magentoStoreId];
    }

    /**
     * update customer sync data
     *
     * @param int $customerId
     * @param string $_mailchimpStoreId
     * @param int|null $syncDelta
     * @param int|null $syncError
     * @param int|null $syncModified
     * @param int|null $syncedFlag
     * @param bool $saveOnlyIfexists
     * @param bool $allowBatchRemoval
     */
    protected function _updateSyncData(
        $customerId,
        $_mailchimpStoreId,
        $syncDelta = null,
        $syncError = null,
        $syncModified = 0,
        $syncedFlag = null,
        $saveOnlyIfexists = false,
        $allowBatchRemoval = true
    )
    {
        $this->_mailchimpHelper->saveEcommerceSyncData(
            $customerId,
            $this->isCustomer(),
            $_mailchimpStoreId,
            $syncDelta,
            $syncError,
            $syncModified,
            null,
            null,
            $syncedFlag,
            $saveOnlyIfexists,
            null,
            $allowBatchRemoval
        );
    }

    /**
     * @return void
     */
    protected function makeBatchId()
    {
        $this->_batchId = "storeid-{$this->getBatchMagentoStoreId()}_";
        $this->_batchId .= $this->isCustomer() . '_';
        $this->_batchId .= $this->_mailchimpHelper->getDateMicrotime();
    }

    /**
     * @return Mage_Customer_Model_Resource_Customer_Collection
     */
    public function makeCustomersNotSentCollection()
    {
        $helper = $this->_mailchimpHelper;
        $_magentoStoreId = $this->getBatchMagentoStoreId();
        /**
         * @var Mage_Customer_Model_Resource_Customer_Collection $collection
         */
        $collection = $this->getCustomerResourceCollection();
        $collection->addFieldToFilter('store_id', array('eq' => $_magentoStoreId));

        $helper->addResendFilter($collection, $_magentoStoreId, $this->isCustomer());

        $collection->addNameToSelect();

        $this->joinDefaultBillingAddress($collection);

        $this->joinSalesData($collection);

        $collection->getSelect()->group('e.entity_id');

        $collection->getSelect()->limit($this->getBatchLimitFromConfig());

        return $collection;
    }

    /**
     * @param Mage_Customer_Model_Resource_Customer_Collection $collection
     */
    protected function joinDefaultBillingAddress($collection)
    {
        $collection->joinAttribute('postcode', 'customer_address/postcode', 'default_billing', null, 'left');
        $collection->joinAttribute('city', 'customer_address/city', 'default_billing', null, 'left');
        $collection->joinAttribute('region', 'customer_address/region', 'default_billing', null, 'left');
        $collection->joinAttribute('region_id', 'customer_address/region_id', 'default_billing', null, 'left');
        $collection->joinAttribute('country_id', 'customer_address/country_id', 'default_billing', null, 'left');
        $collection->joinAttribute('street', 'customer_address/street', 'default_billing', null, 'left');
        $collection->joinAttribute('company', 'customer_address/company', 'default_billing', null, 'left');
    }

    protected function joinSalesData($collection)
    {
        $collection->getSelect()->joinLeft(
            array('s' => $collection->getTable('sales/order')),
            'e.entity_id = s.customer_id',
            array(
                new Zend_Db_Expr('SUM(s.grand_total) AS total_spent'),
                new Zend_Db_Expr('COUNT(s.entity_id) AS orders_count'),
            )
        );
    }

    /**
     * @param $collection
     */
    protected function joinMailchimpSyncData($collection)
    {
        $this->joinMailchimpSyncDataWithoutWhere($collection);

        $collection->getSelect()->where('m4m.mailchimp_sync_delta IS null OR m4m.mailchimp_sync_modified = 1');
    }

    /**
     * @return string
     */
    public function getSyncDataTableName()
    {
        $mailchimpTableName = Mage::getSingleton('core/resource')->getTableName('mailchimp/ecommercesyncdata');

        return $mailchimpTableName;
    }

    /**
     * @param $_magentoStoreId
     * @return mixed
     */
    protected function isEcommerceCustomerOptInConfigEnabled($_magentoStoreId)
    {
        $configValue = $this->_mailchimpHelper->getConfigValueForScope(
            Ebizmarts_MailChimp_Model_Config::ECOMMERCE_CUSTOMERS_OPTIN,
            $_magentoStoreId
        );
        return (1 === (int)$configValue);
    }

    /**
     * @param $_magentoStoreId
     */
    protected function checkEcommerceOptInConfigAndUpdateStorage($_magentoStoreId)
    {
        if ($this->isEcommerceCustomerOptInConfigEnabled($_magentoStoreId)) {
            $this->_optInConfiguration[$_magentoStoreId] = true;
        } else {
            $this->_optInConfiguration[$_magentoStoreId] = false;
        }
    }

    /**
     * @return mixed
     */
    protected function getBatchLimitFromConfig()
    {
        $helper = $this->_mailchimpHelper;
        return $helper->getCustomerAmountLimit();
    }

    /**
     * @param $customer
     * @return string
     */
    protected function getCustomerEmail($customer)
    {
        return $customer->getEmail() ? $customer->getEmail() : '';
    }

    /**
     * @param $customer
     * @return string
     */
    protected function getCustomerFirstname($customer)
    {
        return $customer->getFirstname() ? $customer->getFirstname() : '';
    }

    /**
     * @param $customer
     * @return string
     */
    protected function getCustomerLastname($customer)
    {
        return $customer->getLastname() ? $customer->getLastname() : '';
    }

    /**
     * @param $customer
     */
    protected function logCouldNotEncodeCustomerError($customer)
    {
        $this->_mailchimpHelper->logError(
            'Customer ' . $customer->getId() . ' json encode failed on store ' . $this->getBatchMagentoStoreId()
        );
    }

    /**
     * @return Object
     */
    protected function getCustomerResourceCollection()
    {
        return Mage::getResourceModel('customer/customer_collection');
    }

    /**
     * @return mixed
     */
    protected function getBatchMagentoStoreId()
    {
        return $this->_magentoStoreId;
    }

    /**
     * @param $collection
     * @param null $_mailchimpStoreId
     */
    public function joinMailchimpSyncDataWithoutWhere($collection, $_mailchimpStoreId = null)
    {
        if (!$_mailchimpStoreId) {
            $_mailchimpStoreId = $this->_mailchimpStoreId;
        }
        $joinCondition = "m4m.related_id = e.entity_id and m4m.type = '%s' AND m4m.mailchimp_store_id = '%s'";
        $mailchimpTableName = $this->getSyncDataTableName();

        $collection->getSelect()->joinLeft(
            array("m4m" => $mailchimpTableName),
            sprintf($joinCondition, $this->isCustomer(), $_mailchimpStoreId), array(
                'm4m.related_id',
                'm4m.type',
                'm4m.mailchimp_store_id',
                'm4m.mailchimp_sync_delta',
                'm4m.mailchimp_sync_modified'
            )
        );
    }

    /**
     * @param $_mailchimpStoreId
     */
    protected function setMailchimpStoreId($_mailchimpStoreId)
    {
        $this->_mailchimpStoreId = $_mailchimpStoreId;
    }

    /**
     * @param $_magentoStoreId
     */
    protected function setMagentoStoreId($_magentoStoreId)
    {
        $this->magentoStoreId = $_magentoStoreId;
    }

    public function makeAdminCustomersNotSentCollection()
    {
        $helper = $this->_mailchimpHelper;
        $_magentoStoreId = $this->getBatchMagentoStoreId();
        /**
         * @var Mage_Customer_Model_Resource_Customer_Collection $collection
         */
        $collection = $this->getCustomerResourceCollection();
        $collection->addAttributeToFilter('mailchimp_store_view', array('eq' => $_magentoStoreId));

        $helper->addResendFilter($collection, $_magentoStoreId, $this->isCustomer());

        $collection->addNameToSelect();

        $this->joinDefaultBillingAddress($collection);

        $this->joinSalesData($collection);

        $collection->getSelect()->group('e.entity_id');

        $collection->getSelect()->limit($this->getBatchLimitFromConfig());

        return $collection;
    }

    /**
     * @return string
     */
    protected function isCustomer()
    {
        return Ebizmarts_MailChimp_Model_Config::IS_CUSTOMER;
    }
}
