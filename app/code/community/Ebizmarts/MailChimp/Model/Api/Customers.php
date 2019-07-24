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
    protected $_mailchimpHelper;
    protected $_mailchimpDateHelper;
    protected $_optInConfiguration;
    protected $_optInStatusForStore;
    protected $_locale;
    protected $_directoryRegionModel;
    protected $_magentoStoreId;
    protected $_mailchimpStoreId;
    protected $_batchId;

    public function __construct()
    {
        $this->_mailchimpHelper = $this->makeHelper();
        $this->_mailchimpDateHelper = $this->makeDateHelper();
        $this->_optInConfiguration = array();
        $this->_locale = Mage::app()->getLocale();
        $this->_directoryRegionModel = Mage::getModel('directory/region');
    }

    /**
     * Get an array of customer entity IDs of the next batch of customers
     * to sync.
     * @return int[] Customer IDs to sync
     * @throws Mage_Core_Exception
     */
    protected function getCustomersToSync()
    {
        $collection = $this->getCustomerResourceCollection();
        $collection->addAttributeToFilter(
            array(
                array('attribute' => 'store_id', 'eq' => $this->getBatchMagentoStoreId()),
                array('attribute' => 'mailchimp_store_view', 'eq' => $this->getBatchMagentoStoreId()),
            ),
            null,
            'left'
        );
        $this->joinMailchimpSyncData($collection);

        return $collection->getAllIds($this->getBatchLimitFromConfig());
    }

    /**
     * @param int[] $customerIdsToSync Customer IDs to synchronise.
     * @return Mage_Customer_Model_Resource_Customer_Collection
     */
    public function makeCustomersNotSentCollection($customerIdsToSync)
    {
        /**
         * @var Mage_Customer_Model_Resource_Customer_Collection $collection
         */

        $collection = $this->getCustomerResourceCollection();
        $collection->addFieldToFilter('entity_id', array('in' => $customerIdsToSync));
        $collection->addNameToSelect();
        $this->joinDefaultBillingAddress($collection);
        $this->joinSalesData($collection);
        $collection->getSelect()->group("e.entity_id");

        return $collection;
    }

    /**
     * @param $mailchimpStoreId
     * @param $magentoStoreId
     * @return array
     */
    public function createBatchJson($mailchimpStoreId, $magentoStoreId)
    {
        $this->setMailchimpStoreId($mailchimpStoreId);
        $this->setMagentoStoreId($magentoStoreId);
        $helper = $this->getMailChimpHelper();

        $customersCollection = array();
        $customerIds = $this->getCustomersToSync();
        if (!empty($customerIds)) {
            $customersCollection = $this->makeCustomersNotSentCollection($customerIds);
        }

        $customerArray = array();
        $this->makeBatchId();
        $this->_optInStatusForStore = $this->getOptin($this->getBatchMagentoStoreId());
        $subscriber = $this->getSubscriberModel();
        $listId = $helper->getGeneralList($magentoStoreId);

        $counter = 0;
        foreach ($customersCollection as $customer) {
            $data = $this->_buildCustomerData($customer);
            $customerJson = json_encode($data);

            if (false !== $customerJson) {
                $isSubscribed = $subscriber->loadByEmail($customer->getEmail())->getSubscriberId();/* method: && status != subscribed(const)*/
                $helper = $this->getMailChimpHelper();
                $dataCustomer = $helper->getEcommerceSyncDataItem(
                    $customer->getId(),
                    Ebizmarts_MailChimp_Model_Config::IS_CUSTOMER,
                    $mailchimpStoreId
                );
                if ($dataCustomer->getId()) {
                    $helper->modifyCounterSentPerBatch(Ebizmarts_MailChimp_Helper_Data::CUS_MOD);
                } else {
                    $helper->modifyCounterSentPerBatch(Ebizmarts_MailChimp_Helper_Data::CUS_NEW);
                }

                $customerArray[$counter] = $this->makePutBatchStructure($customerJson);
                $this->_updateSyncData($customer->getId(), $mailchimpStoreId);
                $counter++;
                /**
                 *subscribe all customers to the newsletter
                 */
                if ($this->_optInStatusForStore) {
                    if (!$isSubscribed) {
                        $subscriber->subscribe($customer->getEmail());
                    }
                }
                else {
                    /**
                     * send merge fields for customers currently not subscribed (transactional)
                     */
                    if (!$isSubscribed) {
                        $subscriber->setSubscriberEmail($customer->getEmail());
                        $subscriber->setCustomerId($customer->getId());
                        $mailChimpTags = $this->_buildMailchimpTags($subscriber, $magentoStoreId);
                        $mergeFields["merge_fields"] = $mailChimpTags->getMailchimpTags();
                        if (!empty($mergeFields["merge_fields"])) {
                            $batchData = $this->makePatchBatchStructure($customer, $listId, $mergeFields);
                            if ($batchData !== null) {
                                $customerArray[$counter] = $batchData;
                                $counter++;
                            }
                        }
                    }
                }
            } else {
                $this->logCouldNotEncodeCustomerError($customer);
            }
        }

        return $customerArray;
    }

    protected function _buildMailchimpTags($subscriber, $storeId)
    {
        $mailChimpTags = Mage::getModel('mailchimp/api_subscribers_MailchimpTags');
        $mailChimpTags->setStoreId($storeId);
        $mailChimpTags->setSubscriber($subscriber);
        $mailChimpTags->setCustomer(
            $this->getCustomerByWebsiteAndId()->
            setWebsiteId($this->getWebsiteByStoreId($storeId))->load($subscriber->getCustomerId())
        );
        $mailChimpTags->buildMailChimpTags();

        return $mailChimpTags;
    }

    /**
     * @param $storeId
     * @return Mage_Core_Model_Abstract
     */
    protected function getWebsiteByStoreId($storeId)
    {
        return Mage::getModel('core/store')->load($storeId)->getWebsiteId();
    }

    /**
     * @return false|Mage_Customer_Model_Customer
     */
    protected function getCustomerByWebsiteAndId()
    {
        return Mage::getModel('customer/customer');
    }

    /**
     * @param $customerJson
     * @return array
     */
    protected function makePutBatchStructure($customerJson)
    {
        $customerId = json_decode($customerJson)->id;

        $batchData = array();
        $batchData['method'] = "PUT";
        $batchData['path'] = "/ecommerce/stores/{$this->_mailchimpStoreId}/customers/{$customerId}";
        $batchData['operation_id'] = "{$this->_batchId}_{$customerId}";
        $batchData['body'] = $customerJson;
        return $batchData;
    }

    /**
     * @param $customer
     * @return array
     */
    protected function _buildCustomerData($customer)
    {
        $data = array();
        $data["id"] = md5(strtolower($this->getCustomerEmail($customer)));
        $data["email_address"] = $this->getCustomerEmail($customer);
        $data["first_name"] = $this->getCustomerFirstname($customer);
        $data["last_name"] = $this->getCustomerLastname($customer);

        $data["orders_count"] = (int)$customer->getOrdersCount();
        $data["total_spent"] = (float)$customer->getTotalSpent();
        $data["opt_in_status"] = false;

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
            $customerAddress["province_code"] = $this->_directoryRegionModel->load($customer->getRegionId())->getCode();
            if (!$customerAddress["province_code"]) {
                unset($customerAddress["province_code"]);
            }
        }

        if ($customer->getPostcode()) {
            $customerAddress["postal_code"] = $customer->getPostcode();
        }

        if ($customer->getCountryId()) {
            $customerAddress["country"] = $this->getCountryNameByCode($customer->getCountryId());
            $customerAddress["country_code"] = $customer->getCountryId();
        }

        if (!empty($customerAddress)) {
            $data["address"] = $customerAddress;
        }

        return $data;
    }

    /**
     * @param $countryCode
     * @return array
     */
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
        $mailchimpStoreId = $this->_mailchimpHelper->getMCStoreId($storeId);
        $this->_updateSyncData($customerId, $mailchimpStoreId, null, null, 1, null, true, false);
    }

    /**
     * @param $magentoStoreId
     * @return array
     */
    public function getOptin($magentoStoreId)
    {
        return $this->getOptionConfiguration($magentoStoreId);
    }

    /**
     * @param $magentoStoreId
     * @return array
     */
    protected function getOptionConfiguration($magentoStoreId)
    {
        if (array_key_exists($magentoStoreId, $this->_optInConfiguration)) {
            return $this->_optInConfiguration[$magentoStoreId];
        }

        $this->checkEcommerceOptInConfigAndUpdateStorage($magentoStoreId);

        return $this->_optInConfiguration[$magentoStoreId];
    }

    /**
     * update customer sync data
     *
     * @param int $customerId
     * @param string $mailchimpStoreId
     * @param int|null $syncDelta
     * @param int|null $syncError
     * @param int|null $syncModified
     * @param int|null $syncedFlag
     * @param bool $saveOnlyIfexists
     * @param bool $allowBatchRemoval
     */
    protected function _updateSyncData(
        $customerId,
        $mailchimpStoreId,
        $syncDelta = null,
        $syncError = null,
        $syncModified = 0,
        $syncedFlag = null,
        $saveOnlyIfexists = false,
        $allowBatchRemoval = true
    ) {
        $this->_mailchimpHelper->saveEcommerceSyncData(
            $customerId,
            Ebizmarts_MailChimp_Model_Config::IS_CUSTOMER,
            $mailchimpStoreId,
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
        $this->_batchId .= Ebizmarts_MailChimp_Model_Config::IS_CUSTOMER . '_';
        $this->_batchId .= $this->_mailchimpDateHelper->getDateMicrotime();
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

    /**
     * @param $collection
     */
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
        $joinCondition      = "m4m.related_id = e.entity_id AND m4m.type = '%s' AND m4m.mailchimp_store_id = '%s'";
        $mailchimpTableName = $this->getSyncdataTableName();

        $collection->getSelect()->joinLeft(
            array("m4m" => $mailchimpTableName),
            sprintf($joinCondition, Ebizmarts_MailChimp_Model_Config::IS_CUSTOMER, $this->_mailchimpStoreId),
            array()
        );

        $collection->getSelect()->where("m4m.mailchimp_sync_delta IS null OR m4m.mailchimp_sync_modified = 1");
    }

    /**
     * @return string
     */
    public function getSyncDataTableName()
    {
        $mailchimpTableName = Mage::getSingleton('core/resource')
            ->getTableName('mailchimp/ecommercesyncdata');

        return $mailchimpTableName;
    }

    /**
     * @param $magentoStoreId
     * @return mixed
     */
    protected function isEcommerceCustomerOptInConfigEnabled($magentoStoreId)
    {
        $configValue = $this->_mailchimpHelper->getConfigValueForScope(
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
            $this->_optInConfiguration[$magentoStoreId] = true;
        } else {
            $this->_optInConfiguration[$magentoStoreId] = false;
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
            "Customer " . $customer->getId() . " json encode failed on store " . $this->getBatchMagentoStoreId()
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
     * @param null $mailchimpStoreId
     */
    public function joinMailchimpSyncDataWithoutWhere($collection, $mailchimpStoreId = null)
    {
        if (!$mailchimpStoreId) {
            $mailchimpStoreId = $this->_mailchimpStoreId;
        }

        $joinCondition = "m4m.related_id = e.entity_id AND m4m.type = '%s' AND m4m.mailchimp_store_id = '%s'";
        $mailchimpTableName = $this->getSyncDataTableName();

        $collection->getSelect()->joinLeft(
            array("m4m" => $mailchimpTableName),
            sprintf($joinCondition, Ebizmarts_MailChimp_Model_Config::IS_CUSTOMER, $mailchimpStoreId),
            array(
                "m4m.related_id",
                "m4m.type",
                "m4m.mailchimp_store_id",
                "m4m.mailchimp_sync_delta",
                "m4m.mailchimp_sync_modified"
            )
        );
    }

    /**
     * @param $mailchimpStoreId
     */
    protected function setMailchimpStoreId($mailchimpStoreId)
    {
        $this->_mailchimpStoreId = $mailchimpStoreId;
    }

    /**
     * @param $magentoStoreId
     */
    protected function setMagentoStoreId($magentoStoreId)
    {
        $this->_magentoStoreId = $magentoStoreId;
    }

    /**
     * @return false|Mage_Core_Model_Abstract
     */
    protected function getSubscriberModel()
    {
        $subscriber = Mage::getModel('newsletter/subscriber');
        return $subscriber;
    }

    /**
     * @return Ebizmarts_MailChimp_Helper_Data
     */
    protected function makeHelper()
    {
        return Mage::helper('mailchimp');
    }

    /**
     * @return Ebizmarts_MailChimp_Helper_Data
     */
    protected function getMailChimpHelper()
    {
        return $this->_mailchimpHelper;
    }

    /**
     * @return Ebizmarts_MailChimp_Helper_Date
     */
    protected function makeDateHelper()
    {
        return Mage::helper('mailchimp/date');
    }

    /**
     * @return Ebizmarts_MailChimp_Helper_Date
     */
    protected function getMailChimpDateHelper()
    {
        return $this->_mailchimpDateHelper;
    }

    /**
     * @param $customer
     * @param $listId
     * @param $mergeFields
     * @return array|null
     */
    protected function makePatchBatchStructure($customer, $listId, $mergeFields)
    {
        $batchData = null;
        $mergeFieldJSON = json_encode($mergeFields);

        if($mergeFieldJSON === false) {
            //loguear error
        }
        else {
            $md5HashEmail = md5(strtolower($customer->getEmail()));
            $batchData = array();
            $batchData['method'] = "PATCH";
            $batchData['path'] = "/lists/" . $listId . "/members/" . $md5HashEmail;
            $batchData['operation_id'] = "{$this->batchId}_SUB_" . $md5HashEmail;
            $batchData['body'] = $mergeFieldJSON;
        }

        return $batchData;
    }

}
