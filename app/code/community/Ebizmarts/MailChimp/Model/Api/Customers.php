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
    private $mailchimpHelper;
    private $optInConfiguration;
    private $optInStatusForStore;
    private $locale;
    private $directoryRegionModel;
    private $magentoStoreId;
    private $mailchimpStoreId;
    private $batchId;

    public function __construct()
    {
        $this->mailchimpHelper      = Mage::helper('mailchimp');
        $this->optInConfiguration   = array();
        $this->locale               = Mage::app()->getLocale();
        $this->directoryRegionModel = Mage::getModel('directory/region');
    }

    public function createBatchJson($mailchimpStoreId, $magentoStoreId)
    {
        $this->mailchimpStoreId = $mailchimpStoreId;
        $this->magentoStoreId   = $magentoStoreId;

        $collection = $this->makeCustomersNotSentCollection();
        $this->joinMailchimpSyncData($collection);

        $customerArray = array();
        
        $this->makeBatchId();

        $this->optInStatusForStore = $this->getOptin($this->getBatchMagentoStoreId());

        $counter = 0;
        foreach ($collection as $customer) {
            $data = $this->_buildCustomerData($customer);

            $customerJson = json_encode($data);
            if (false !== $customerJson) {
                $customerArray[$counter] = $this->makePutBatchStructure($customerJson);
                $this->_updateSyncData($customer->getId(), $mailchimpStoreId, Varien_Date::now());
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
    protected function makePutBatchStructure(
        $customerJson
    ) {
        $customerId = json_decode($customerJson)->id;

        $batchData = array();
        $batchData['method'] = "PUT";
        $batchData['path'] = "/ecommerce/stores/{$this->mailchimpStoreId}/customers/{$customerId}";
        $batchData['operation_id'] = "{$this->batchId}_{$customerId}";
        $batchData['body'] = $customerJson;
        return $batchData;
    }

    protected function _buildCustomerData($customer)
    {
        $data = array();
        $data["id"] = $customer->getId();
        $data["email_address"] = $this->getCustomerEmail($customer);
        $data["first_name"] = $this->getCustomerFirstname($customer);
        $data["last_name"] = $this->getCustomerLastname($customer);
        $data["opt_in_status"] = $this->optInStatusForStore;

        $data["orders_count"] = (int)$customer->getOrdersCount();
        $data["total_spent"] = (float)$customer->getTotalSpent();

        $data += $this->getCustomerAddressData($customer);

        if ($customer->getCompany()) {
            $data["company"] = $customer->getCompany();
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
            $customerAddress["address1"] = $street[0];
            $customerAddress["address2"] = $street[1];
        } else {
            if (!empty($street[0])) {
                $customerAddress["address1"] = $street[0];
            }
        }

        if ($customer->getCity()) {
            $customerAddress["city"] = $customer->getCity();
        }

        if ($customer->getRegion()) {
            $customerAddress["province"] = $customer->getRegion();
        }

        if ($customer->getRegionId()) {
            $customerAddress["province_code"] = $this->directoryRegionModel->load($customer->getRegionId())->getCode();
            if (!$customerAddress["province_code"]) {
                unset($customerAddress["province_code"]);
            }
        }

        if ($customer->getPostcode()) {
            $customerAddress["postal_code"] = $customer->getPostcode();
        }

        if ($customer->getCountryId()) {
            $customerAddress["country"]      = $this->getCountryNameByCode($customer->getCountryId());
            $customerAddress["country_code"] = $customer->getCountryId();
        }

        if (!empty($customerAddress)) {
            $data["address"] = $customerAddress;
        }

        return $data;
    }

    protected function getCountryNameByCode($countryCode)
    {
        return $this->locale->getCountryTranslation($countryCode);
    }

    /**
     * Update customer sync data after modification.
     *
     * @param $customerId
     * @param $storeId
     */
    public function update($customerId, $storeId)
    {
        if ($this->mailchimpHelper->isEcomSyncDataEnabled($storeId)) {
            $mailchimpStoreId = $this->mailchimpHelper->getMCStoreId($storeId);
            $this->_updateSyncData($customerId, $mailchimpStoreId, null, null, 1, true);
        }
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

    public function getOptin($magentoStoreId)
    {
        return $this->getOptinConfiguration($magentoStoreId);
    }

    protected function getOptinConfiguration($magentoStoreId)
    {
        if (array_key_exists($magentoStoreId, $this->optInConfiguration)) {
            return $this->optInConfiguration[$magentoStoreId];
        }

        $this->checkEcommerceOptInConfigAndUpdateStorage($magentoStoreId);

        return $this->optInConfiguration[$magentoStoreId];
    }

    /**
     * update customer sync data
     * 
     * @param $customerId
     * @param $mailchimpStoreId
     * @param null             $syncDelta
     * @param null             $syncError
     * @param int              $syncModified
     * @param bool             $saveOnlyIfexists
     */
    protected function _updateSyncData($customerId, $mailchimpStoreId, $syncDelta = null, $syncError = null, $syncModified = 0, $saveOnlyIfexists = false)
    {
        $this->mailchimpHelper->saveEcommerceSyncData($customerId, Ebizmarts_MailChimp_Model_Config::IS_CUSTOMER, $mailchimpStoreId, $syncDelta, $syncError, $syncModified, null, null, $saveOnlyIfexists);
    }

    /**
     * @return void
     */
    protected function makeBatchId()
    {
        $this->batchId = "storeid-{$this->getBatchMagentoStoreId()}_";
        $this->batchId .= Ebizmarts_MailChimp_Model_Config::IS_CUSTOMER . '_';
        $this->batchId .= $this->mailchimpHelper->getDateMicrotime();
    }

    /**
     * @return Mage_Customer_Model_Resource_Customer_Collection
     */
    public function makeCustomersNotSentCollection()
    {
        $helper = $this->mailchimpHelper;
        $magentoStoreId = $this->getBatchMagentoStoreId();
        /**
         * @var Mage_Customer_Model_Resource_Customer_Collection $collection
         */
        $collection = $this->getCustomerResourceCollection();
        $collection->addFieldToFilter('store_id', array('eq' => $magentoStoreId));

        $helper->addResendFilter($collection, $magentoStoreId);

        $collection->addNameToSelect();

        $this->joinDefaultBillingAddress($collection);

        $this->joinSalesData($collection);

        $collection->getSelect()->group("e.entity_id");

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
                new Zend_Db_Expr("SUM(s.grand_total) AS total_spent"),
                new Zend_Db_Expr("COUNT(s.entity_id) AS orders_count"),
            )
        );
    }

    /**
     * @param $collection
     */
    protected function joinMailchimpSyncData($collection)
    {
        $this->joinMailchimpSyncDataWithoutWhere($collection);

        $collection->getSelect()->where("m4m.mailchimp_sync_delta IS null OR m4m.mailchimp_sync_modified = 1");
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
     * @param $magentoStoreId
     * @return mixed
     */
    protected function isEcommerceCustomerOptInConfigEnabled($magentoStoreId)
    {
        $configValue = $this->mailchimpHelper->getConfigValueForScope(
            Ebizmarts_MailChimp_Model_Config::ECOMMERCE_CUSTOMERS_OPTIN,
            $magentoStoreId
        );
        return (1 === (int)$configValue);
    }

    /**
     * @param $magentoStoreId
     */
    protected function checkEcommerceOptInConfigAndUpdateStorage($magentoStoreId)
    {
        if ($this->isEcommerceCustomerOptInConfigEnabled($magentoStoreId)) {
            $this->optInConfiguration[$magentoStoreId] = true;
        } else {
            $this->optInConfiguration[$magentoStoreId] = false;
        }
    }

    /**
     * @return mixed
     */
    protected function getBatchLimitFromConfig()
    {
        $helper = $this->mailchimpHelper;
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
        $this->mailchimpHelper->logError(
            "Customer " . $customer->getId() . " json encode failed on store " . $this->getBatchMagentoStoreId(), $this->getBatchMagentoStoreId()
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
        return $this->magentoStoreId;
    }

    /**
     * @param $collection
     * @param null $mailchimpStoreId
     */
    public function joinMailchimpSyncDataWithoutWhere($collection, $mailchimpStoreId = null)
    {
        if (!$mailchimpStoreId) {
            $mailchimpStoreId = $this->mailchimpStoreId;
        }
        $joinCondition = "m4m.related_id = e.entity_id and m4m.type = '%s' AND m4m.mailchimp_store_id = '%s'";
        $mailchimpTableName = $this->getSyncDataTableName();

        $collection->getSelect()->joinLeft(
            array("m4m" => $mailchimpTableName),
            sprintf($joinCondition, Ebizmarts_MailChimp_Model_Config::IS_CUSTOMER, $mailchimpStoreId), array(
                "m4m.related_id",
                "m4m.type",
                "m4m.mailchimp_store_id",
                "m4m.mailchimp_sync_delta",
                "m4m.mailchimp_sync_modified"
            )
        );
    }
}
