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

class Ebizmarts_MailChimp_Model_Api_Customers extends Ebizmarts_MailChimp_Model_Api_SyncItem
{
    const BATCH_LIMIT = 100;

    protected $_optInConfiguration;
    protected $_optInStatusForStore;
    protected $_locale;
    protected $_directoryRegionModel;
    protected $_magentoStoreId;
    protected $_mailchimpStoreId;
    protected $_batchId;

    public function __construct()
    {
        parent::__construct();

        $this->_optInConfiguration = array();
        $this->_locale = Mage::app()->getLocale();
        $this->_directoryRegionModel = Mage::getModel('directory/region');
    }

    /**
     * Get an array of customer entity IDs of the next batch of customers
     * to sync.
     *
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
        $collection->getSelect()->group("e.entity_id");

        return $collection;
    }

    /**
     * @param $mailchimpStoreId
     * @param $magentoStoreId
     * @return array
     * @throws Mage_Core_Exception
     */
    public function createBatchJson($mailchimpStoreId, $magentoStoreId)
    {
        $this->setMailchimpStoreId($mailchimpStoreId);
        $this->setMagentoStoreId($magentoStoreId);
        $helper = $this->getHelper();

        $customersCollection = array();
        $customerIds = $this->getCustomersToSync();
        if (!empty($customerIds)) {
            $customersCollection = $this->makeCustomersNotSentCollection($customerIds);
        }

        $customerArray = array();
        $this->makeBatchId();
        $this->setOptInStatusForStore($this->getOptIn($this->getBatchMagentoStoreId()));
        $subscriber = $this->getSubscriberModel();
        $listId = $helper->getGeneralList($magentoStoreId);

        $counter = 0;
        foreach ($customersCollection as $customer) {
            $data = $this->_buildCustomerData($customer);
            $customerJson = json_encode($data);

            if (false !== $customerJson) {
                if (!empty($customerJson)) {
                    $isSubscribed = $this->isSubscribed($subscriber, $customer);
                    $dataCustomer = $helper->getEcommerceSyncDataItem(
                        $customer->getId(),
                        Ebizmarts_MailChimp_Model_Config::IS_CUSTOMER,
                        $mailchimpStoreId
                    );
                    $this->incrementCounterSentPerBatch(
                        $dataCustomer,
                        $helper
                    );

                    $customerArray[$counter] = $this->makePutBatchStructure($customerJson, $customer);
                    $this->_updateSyncData(
                        Ebizmarts_MailChimp_Model_Config::IS_CUSTOMER,
                        $customer->getId(),
                        $mailchimpStoreId,
                        null,
                        null,
                        0,
                        null,
                        null
                    );
                    $counter++;

                    if (!$isSubscribed) {
                        /**
                         * subscribe all customers to the newsletter
                         */
                        if ($this->getOptInStatusForStore()) {
                            $subscriber->subscribe($customer->getEmail());
                        } else {
                            /**
                             * send merge fields for customers currently not subscribed (transactional)
                             */
                            list($customerArray, $counter) = $this->sendMailchimpTags(
                                $magentoStoreId, $dataCustomer,
                                $subscriber, $customer, $listId, $counter, $customerArray
                            );
                        }
                    }
                } else {
                    $this->_updateSyncData(
                        Ebizmarts_MailChimp_Model_Config::IS_CUSTOMER,
                        $customer->getId(),
                        $mailchimpStoreId,
                        $this->getDateHelper()->getCurrentDateTime(),
                        'Customer with no data',
                        0
                    );
                }
            } else {
                $jsonErrorMessage = $this->logCouldNotEncodeCustomerError($customer);
                $this->_updateSyncData(
                    Ebizmarts_MailChimp_Model_Config::IS_CUSTOMER,
                    $customer->getId(),
                    $mailchimpStoreId,
                    $this->getDateHelper()->getCurrentDateTime(),
                    $jsonErrorMessage,
                    0,
                    null,
                    null,
                    null,
                    false,
                    -1
                );
            }
        }

        return $customerArray;
    }

    /**
     * @param $subscriber
     * @param $storeId
     * @return false|Ebizmarts_MailChimp_Model_Api_Subscribers_MailchimpTags
     */
    protected function _buildMailchimpTags($subscriber, $storeId)
    {
        $mailChimpTags = Mage::getModel('mailchimp/api_subscribers_MailchimpTags');
        $mailChimpTags->setStoreId($storeId);
        $mailChimpTags->setSubscriber($subscriber);
        $mailChimpTags->setCustomer(
            $this->getCustomerByWebsiteAndId()
                ->setWebsiteId($this->getWebsiteByStoreId($storeId))->load($subscriber->getCustomerId())
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
     * @param $customer
     * @return array
     */
    protected function makePutBatchStructure($customerJson, $customer)
    {
        $customerHash = json_decode($customerJson)->id;
        $customerId = $customer->getId();

        $batchData = array();
        $batchData['method'] = "PUT";
        $batchData['path'] = "/ecommerce/stores/{$this->_mailchimpStoreId}/customers/{$customerHash}";
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
        $mailchimpStoreId = $this->getHelper()->getMCStoreId($storeId);
        $this->_updateSyncData(
            Ebizmarts_MailChimp_Model_Config::IS_CUSTOMER,
            $customerId,
            $mailchimpStoreId,
            null,
            null,
            1,
            null,
            null,
            null,
            true,
            false
        );
    }

    /**
     * @param $magentoStoreId
     * @return array
     */
    public function getOptIn($magentoStoreId)
    {
        return $this->getOptInConfiguration($magentoStoreId);
    }

    /**
     * @param $magentoStoreId
     * @return array
     */
    protected function getOptInConfiguration($magentoStoreId)
    {
        if (array_key_exists($magentoStoreId, $this->_optInConfiguration)) {
            return $this->_optInConfiguration[$magentoStoreId];
        }

        $this->checkEcommerceOptInConfigAndUpdateStorage($magentoStoreId);

        return $this->_optInConfiguration[$magentoStoreId];
    }

    /**
     * @return void
     */
    protected function makeBatchId()
    {
        $this->_batchId = "storeid-{$this->getBatchMagentoStoreId()}_";
        $this->_batchId .= Ebizmarts_MailChimp_Model_Config::IS_CUSTOMER . '_';
        $this->_batchId .= $this->getDateHelper()->getDateMicrotime();
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
     * @param $magentoStoreId
     * @return bool
     * @throws Mage_Core_Exception
     */
    protected function isEcommerceCustomerOptInConfigEnabled($magentoStoreId)
    {
        $configValue = $this->getHelper()->getConfigValueForScope(
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
        $helper = $this->getHelper();
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
     * @return string
     */
    protected function logCouldNotEncodeCustomerError($customer)
    {
        $jsonErrorMessage = json_last_error_msg();
        $this->getHelper()->logError(
            "Customer " . $customer->getId() . " json encode failed (".$jsonErrorMessage.") on store "
            . $this->getBatchMagentoStoreId()
        );
        return $jsonErrorMessage;
    }

    /**
     * @param $customer
     * @param $mailchimpTags
     */
    protected function logCouldNotEncodeMailchimpTags($customer, $mailchimpTags)
    {
        $this->getHelper()->logError(
            "MailChimp tags encode failed, Customer " . $customer->getId() . " on store " .
            $this->getBatchMagentoStoreId()." mergeFields:"
        );
        $this->getHelper()->logError($mailchimpTags);
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
     * @param null       $mailchimpStoreId
     */
    public function joinMailchimpSyncDataWithoutWhere($collection, $mailchimpStoreId = null)
    {
        if (!$mailchimpStoreId) {
            $mailchimpStoreId = $this->_mailchimpStoreId;
        }

        $joinCondition = "m4m.related_id = e.entity_id AND m4m.type = '%s' AND m4m.mailchimp_store_id = '%s'";
        $mailchimpTableName = $this->getMailchimpEcommerceDataTableName();

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
     * @param $customer
     * @param $listId
     * @param $mergeFields
     * @return array|null
     */
    protected function makePatchBatchStructure($customer, $listId, $mergeFields)
    {
        $batchData = null;
        $mergeFieldJSON = json_encode($mergeFields);
        $customerId = $customer->getId();

        if ($mergeFieldJSON === false) {
            $this->logCouldNotEncodeMailchimpTags($customer, $mergeFields);
        } else {
            $md5HashEmail = md5(strtolower($customer->getEmail()));
            $batchData = array();
            $batchData['method'] = "PATCH";
            $batchData['path'] = "/lists/" . $listId . "/members/" . $md5HashEmail;
            $batchData['operation_id'] = "{$this->_batchId}_{$customerId}_SUB";
            $batchData['body'] = $mergeFieldJSON;
        }

        return $batchData;
    }

    /**
     * @param $subscriber
     * @param $customer
     * @return mixed
     */
    protected function isSubscribed($subscriber, $customer)
    {
        if ($subscriber->loadByEmail($customer->getEmail())->getSubscriberId()
            && $subscriber->getStatus() == Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $optInStatus
     */
    protected function setOptInStatusForStore($optInStatus)
    {
        $this->_optInStatusForStore = $optInStatus;
    }

    /**
     * @return mixed
     */
    protected function getOptInStatusForStore()
    {
        return $this->_optInStatusForStore;
    }

    /**
     * @param $mergeFields
     * @param $customer
     * @param $listId
     * @return array|null
     */
    protected function getCustomerPatchBatch($mergeFields, $customer, $listId)
    {
        $batchData = null;

        if (!empty($mergeFields["merge_fields"])) {
            $batchData = $this->makePatchBatchStructure($customer, $listId, $mergeFields);
        }

        return $batchData;
    }

    /**
     * @param $magentoStoreId
     * @param $subscriber
     * @param $customer
     * @param $listId
     * @param $counter
     * @return array|null
     */
    protected function makeMailchimpTagsBatchStructure($magentoStoreId, $subscriber, $customer, $listId)
    {
        $subscriber->setSubscriberEmail($customer->getEmail());
        $subscriber->setCustomerId($customer->getId());
        $mailChimpTags = $this->_buildMailchimpTags($subscriber, $magentoStoreId);
        $mergeFields["merge_fields"] = $mailChimpTags->getMailchimpTags();
        $batchData = $this->getCustomerPatchBatch($mergeFields, $customer, $listId);
        return $batchData;
    }

    /**
     * @param Varien_Object $dataCustomer
     * @param Ebizmarts_MailChimp_Helper_Data $helper
     */
    protected function incrementCounterSentPerBatch(
        Varien_Object $dataCustomer,
        Ebizmarts_MailChimp_Helper_Data $helper
    ) {
        if ($dataCustomer->getId()) {
            $helper->modifyCounterSentPerBatch(Ebizmarts_MailChimp_Helper_Data::CUS_MOD);
        } else {
            $helper->modifyCounterSentPerBatch(Ebizmarts_MailChimp_Helper_Data::CUS_NEW);
        }
    }

    /**
     * Send merge fields for transactional members
     *
     * @param $magentoStoreId
     * @param Varien_Object $dataCustomer
     * @param $subscriber
     * @param $customer
     * @param $listId
     * @param $counter
     * @param array $customerArray
     * @return array
     */
    protected function sendMailchimpTags(
        $magentoStoreId,
        Varien_Object $dataCustomer,
        $subscriber,
        $customer,
        $listId,
        $counter,
        array $customerArray
    ) {
        if ($dataCustomer->getMailchimpSyncedFlag()) {
            $batchData = $this->makeMailchimpTagsBatchStructure(
                $magentoStoreId,
                $subscriber,
                $customer,
                $listId,
                $counter
            );

            if ($batchData !== null) {
                $customerArray[$counter] = $batchData;
                $counter++;
            }
        }

        return array($customerArray, $counter);
    }

}
