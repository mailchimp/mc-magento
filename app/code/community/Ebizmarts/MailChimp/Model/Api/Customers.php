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

    public function createBatchJson($mailchimpStoreId, $magentoStoreId)
    {
        //get customers
        $mailchimpTableName = Mage::getSingleton('core/resource')->getTableName('mailchimp/ecommercesyncdata');
        $collection = Mage::getModel('customer/customer')->getCollection();
        $collection->addFieldToFilter('store_id', array('eq' => $magentoStoreId));
        $collection->getSelect()->joinLeft(
            array('m4m' => $mailchimpTableName),
            "m4m.related_id = e.entity_id and m4m.type = '".Ebizmarts_MailChimp_Model_Config::IS_CUSTOMER."'
            AND m4m.mailchimp_store_id = '" . $mailchimpStoreId . "'",
            array('m4m.*')
        );
        $collection->getSelect()->where(
            "m4m.mailchimp_sync_delta IS null ".
            "OR m4m.mailchimp_sync_modified = 1"
        );
        $collection->getSelect()->limit(self::BATCH_LIMIT);
        $customerArray = array();
        
        $batchId = 'storeid-' . $magentoStoreId . '_' . Ebizmarts_MailChimp_Model_Config::IS_CUSTOMER . '_' . Mage::helper('mailchimp')->getDateMicrotime();

        $counter = 0;
        foreach ($collection as $item) {
            $customerId = $item->getId();
            $customer = Mage::getModel('customer/customer')->load($customerId);
            $data = $this->_buildCustomerData($customer, $magentoStoreId);
            $customerJson = "";

            //enconde to JSON
            try {
                $customerJson = json_encode($data);
            } catch (Exception $e) {
                //json encode failed
                Mage::helper('mailchimp')->logError("Customer ".$customer->getId()." json encode failed on store ".$magentoStoreId, $magentoStoreId);
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
        $orderCollection = Mage::getModel('sales/order')->getCollection()
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
        $address = $customer->getDefaultBillingAddresses();
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

//        $mergeFields = $this->getMergeVars($customer);
//        if (is_array($mergeFields)) {
//            $data = array_merge($mergeFields, $data);
//        }

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
        if (Mage::helper('mailchimp')->isEcomSyncDataEnabled($storeId)) {
            $mailchimpStoreId = Mage::helper('mailchimp')->getMCStoreId($storeId);
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
        if (Mage::helper('mailchimp')->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_CUSTOMERS_OPTIN, $magentoStoreId)) {
            $optin = true;
        } else {
            $optin = false;
        }

        return $optin;
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
        Mage::helper('mailchimp')->saveEcommerceSyncData($customerId, Ebizmarts_MailChimp_Model_Config::IS_CUSTOMER, $mailchimpStoreId, $syncDelta, $syncError, $syncModified, null, null, $saveOnlyIfexists);
    }
}