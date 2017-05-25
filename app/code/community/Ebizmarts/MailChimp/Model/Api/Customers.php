<?php

/**
 * mailchimp-lib Magento Component
 *
 * @category Ebizmarts
 * @package mailchimp-lib
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Ebizmarts_MailChimp_Model_Api_Customers
{
    const BATCH_LIMIT = 100;

    private $mailchimpHelper;
    private $optInConfiguration;

    public function __construct()
    {
        $this->mailchimpHelper = Mage::helper('mailchimp');
        $this->optInConfiguration = array();
    }

    public function createBatchJson($mailchimpStoreId, $magentoStoreId)
    {
        $collection = $this->makeCustomersNotSentCollection($magentoStoreId);
        $this->joinMailchimpSyncData($mailchimpStoreId, $collection);

        Mage::log((string)$collection->getSelect(), null, 'customers.log', true);

        $customerArray = array();
        
        $batchId = $this->makeBatchId($magentoStoreId);

        $counter = 0;
        foreach ($collection as $item) {
            Mage::log($item->toArray(), null, 'customers.log', true);
            die;
            $customerId = $item->getId();
            $customer = Mage::getModel('customer/customer')->load($customerId);
            $data = $this->_buildCustomerData($customer, $magentoStoreId);
            $customerJson = "";

            //enconde to JSON
            try {
                $customerJson = json_encode($data);
            } catch (Exception $e) {
                //json encode failed
                $this->mailchimpHelper->logError("Customer ".$customer->getId()." json encode failed on store ".$magentoStoreId, $magentoStoreId);
            }

            if (!empty($customerJson)) {
                $customerArray[$counter]['method'] = "PUT";
                $customerArray[$counter]['path'] = "/ecommerce/stores/" . $mailchimpStoreId . "/customers/" . $customerId;
                $customerArray[$counter]['operation_id'] = $batchId . '_' . $customerId;
                $customerArray[$counter]['body'] = $customerJson;

                //update customers delta
                $this->_updateSyncData($customerId, $mailchimpStoreId, Varien_Date::now());
            }

            $counter++;
        }
        Mage::log($customerArray, null, 'customers.log', true);
        return $customerArray;
    }

    protected function _buildCustomerData($customer, $magentoStoreId)
    {
        $data = array();
        $data["id"] = $customer->getId();
        $data["email_address"] = $customer->getEmail() ? $customer->getEmail() : '';
        $data["first_name"] = $customer->getFirstname() ? $customer->getFirstname() : '';
        $data["last_name"] = $customer->getLastname() ? $customer->getLastname() : '';
        $data["opt_in_status"] = $this->getOptin($magentoStoreId);

        //customer orders data
        $orderCollection = Mage::getResourceModel('sales/order_collection')
            ->addFieldToFilter(
                'state',
                array(
                    array('neq' => Mage_Sales_Model_Order::STATE_CANCELED),
                    array('neq' => Mage_Sales_Model_Order::STATE_CLOSED)
                )
            )
            ->addAttributeToFilter('customer_id', array('eq' => $customer->getId()));

        $totalOrders = 0;
        $totalAmountSpent = 0;
        foreach ($orderCollection as $customerOrder) {
            $totalOrders++;
            $totalAmountSpent += ($customerOrder->getGrandTotal() - $customerOrder->getTotalRefunded() - $customerOrder->getTotalCanceled());
        }

        $data["orders_count"] = $totalOrders;
        $data["total_spent"] = $totalAmountSpent;

        //addresses data
        $address = $customer->getDefaultBillingAddress();
        if ($address) {
            $customerAddress = array();
            $street = $address->getStreet();
            if ($street[0]) {
                $customerAddress["address1"] = $street[0];
            }

            if (count($street) > 1 && $street[1]) {
                $customerAddress["address2"] = $street[1];
            }

            if ($address->getCity()) {
                $customerAddress["city"] = $address->getCity();
            }

            if ($address->getRegion()) {
                $customerAddress["province"] = $address->getRegion();
            }

            if ($address->getRegionCode()) {
                $customerAddress["province_code"] = $address->getRegionCode();
            }

            if ($address->getPostcode()) {
                $customerAddress["postal_code"] = $address->getPostcode();
            }

            if ($address->getCountry()) {
                $customerAddress["country"] = Mage::getModel('directory/country')->loadByCode($address->getCountry())->getName();
                $customerAddress["country_code"] = $address->getCountry();
            }

            if (count($customerAddress)) {
                $data["address"] = $customerAddress;
            }

            //company
            if ($address->getCompany()) {
                $data["company"] = $address->getCompany();
            }
        }

        return $data;
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
     * @param null $syncDelta
     * @param null $syncError
     * @param int $syncModified
     * @param bool $saveOnlyIfexists
     */
    protected function _updateSyncData($customerId, $mailchimpStoreId, $syncDelta = null, $syncError = null, $syncModified = 0, $saveOnlyIfexists = false)
    {
        $this->mailchimpHelper->saveEcommerceSyncData($customerId, Ebizmarts_MailChimp_Model_Config::IS_CUSTOMER, $mailchimpStoreId, $syncDelta, $syncError, $syncModified, null, null, $saveOnlyIfexists);
    }

    /**
     * @param $magentoStoreId
     * @return string
     */
    protected function makeBatchId($magentoStoreId)
    {
        return 'storeid-' . $magentoStoreId . '_' . Ebizmarts_MailChimp_Model_Config::IS_CUSTOMER . '_' . $this->mailchimpHelper->getDateMicrotime();
    }

    /**
     * @param $mailchimpStoreId
     * @param $magentoStoreId
     * @return Mage_Customer_Model_Resource_Customer_Collection
     */
    public function makeCustomersNotSentCollection($magentoStoreId) {

        /** @var Mage_Customer_Model_Resource_Customer_Collection $collection */
        $collection = Mage::getResourceModel('customer/customer_collection');
        $collection->addFieldToFilter('website_id', array('eq' => $this->getWebsiteIdForStoreId($magentoStoreId)));

        $collection->addNameToSelect();

        $this->joinDefaultBillingAddress($collection);

        $this->joinSalesData($collection);

        $collection->getSelect()->group("e.entity_id");

        print_r((string)$collection->getSelect());die;
        $collection->getSelect()->limit($this->getBatchLimitFromConfig());


        return $collection;
    }

    /**
     * @param Mage_Customer_Model_Resource_Customer_Collection $collection
     */
    protected function joinDefaultBillingAddress($collection)
    {
            /*$customerAddress["province_code"] = $address->getRegionCode();
            $customerAddress["country"] = Mage::getModel('directory/country')->loadByCode($address->getCountry())->getName();*/

        $collection->joinAttribute('postcode', 'customer_address/postcode', 'default_billing', null, 'left');
        $collection->joinAttribute('city', 'customer_address/city', 'default_billing', null, 'left');
        $collection->joinAttribute('region', 'customer_address/region', 'default_billing', null, 'left');
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
     * @param $mailchimpStoreId
     * @param $collection
     */
    protected function joinMailchimpSyncData($mailchimpStoreId, $collection)
    {
        $joinCondition      = "m4m.related_id = e.entity_id and m4m.type = '%s' AND m4m.mailchimp_store_id = '%s'";
        $mailchimpTableName = $this->getSyncdataTableName();

        $collection->getSelect()->joinLeft(array("m4m" => $mailchimpTableName),
            sprintf($joinCondition, Ebizmarts_MailChimp_Model_Config::IS_CUSTOMER, $mailchimpStoreId), array(
                "m4m.related_id",
                "m4m.type",
                "m4m.mailchimp_store_id",
                "m4m.mailchimp_sync_delta",
                "m4m.mailchimp_sync_modified"
            ));

        $collection->getSelect()->where("m4m.mailchimp_sync_delta IS null OR m4m.mailchimp_sync_modified = 1");
    }

    /**
     * @param $magentoStoreId
     * @return int|null|string
     */
    protected function getWebsiteIdForStoreId($magentoStoreId)
    {
        return Mage::app()->getStore($magentoStoreId)->getWebsiteId();
    }

    /**
     * @return string
     */
    public function getSyncdataTableName()
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
        $configValue = $this->mailchimpHelper->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_CUSTOMERS_OPTIN,
            $magentoStoreId);
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
     * @return int
     */
    protected function getBatchLimitFromConfig()
    {
        return self::BATCH_LIMIT;
    }
}
