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
        $collection->addFieldToFilter('store_id',array('eq' => $magentoStoreId));
        $collection->getSelect()->joinLeft(
            array('m4m' => $mailchimpTableName),
            "m4m.related_id = e.entity_id and m4m.type = '".Ebizmarts_MailChimp_Model_Config::IS_CUSTOMER."'
            AND m4m.mailchimp_store_id = '" . $mailchimpStoreId . "'",
            array('m4m.*')
        );
        $collection->getSelect()->where("m4m.mailchimp_sync_delta IS null ".
            "OR m4m.mailchimp_sync_modified = 1");
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
            ->addFieldToFilter('state',
                array(
                    array('neq' => Mage_Sales_Model_Order::STATE_CANCELED),
                    array('neq' => Mage_Sales_Model_Order::STATE_CLOSED)
                ))
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

        $mergeFields = $this->getMergeVars($customer);
        if (is_array($mergeFields)) {
            $data = array_merge($mergeFields, $data);
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
        if (Mage::helper('mailchimp')->isEcomSyncDataEnabled($storeId)) {
            $mailchimpStoreId = Mage::helper('mailchimp')->getMCStoreId($storeId);
            $this->_updateSyncData($customerId, $mailchimpStoreId, null, null, 1, true);
        }
    }

    public function getMergeVars($object)
    {
        $storeId = $object->getStoreId();
        $value = Mage::helper('mailchimp')->getMapFields($storeId);
        $maps = unserialize($value);
        $websiteId = Mage::getModel('core/store')->load($storeId)->getWebsiteId();
        $attrSetId = Mage::getResourceModel('eav/entity_attribute_collection')
            ->setEntityTypeFilter(1)
            ->addSetInfo()
            ->getData();
        $mergeVars = array();
        if($object instanceof Mage_Newsletter_Model_Subscriber) {
            $customer = Mage::getModel('customer/customer')->setWebsiteId($websiteId)->loadByEmail($object->getSubscriberEmail());
        } else {
            $customer = $object;
        }

        foreach ($maps as $map) {
            $customAtt = $map['magento'];
            $chimpTag = $map['mailchimp'];
            if ($chimpTag && $customAtt) {
                $key = strtoupper($chimpTag);
                $attributeCode = $customAtt;
                foreach ($attrSetId as $attribute) {
                    if ($attribute['attribute_id'] == $customAtt) {
                        if ($customer->getId()) {
//                            if ($customer->getData($attributeCode)) {
                                switch ($attributeCode) {
                                    case 'email':
                                        break;
                                    case 'default_billing':
                                    case 'default_shipping':
                                        $address = $customer->getPrimaryAddress($attributeCode);
//                                        if (!$address) {
//                                            if ($customer->{'getDefault' . ucfirst($addr[1])}()) {
//                                                $address = Mage::getModel('customer/address')->load($customer->{'getDefault' . ucfirst($addr[1])}());
//                                            }
//                                        }

                                        if ($address) {
                                            $street = $address->getStreet();
                                            $mergeVars[$key] = array(
                                                "addr1" => $street[0] ? $street[0] : "",
                                                "addr2" => count($street)>1 ? $street[1] : "",
                                                "city" => $address->getCity() ? $address->getCity() : "",
                                                "state" => $address->getRegion() ? $address->getRegion() : "",
                                                "zip" => $address->getPostcode() ? $address->getPostcode() : "",
                                                "country" => $address->getCountry() ? Mage::getModel('directory/country')->loadByCode($address->getCountry())->getName() : ""
                                            );
                                        }
                                        break;
                                    case 'gender':
                                        if ($customer->getData($attributeCode)) {
                                            $genderValue = $customer->getData($attributeCode);
                                            if ($genderValue == 1) {
                                                $mergeVars[$key] = 'Male';
                                            } elseif ($genderValue == 2) {
                                                $mergeVars[$key] = 'Female';
                                            }
                                        }
                                        break;
                                    case 'group_id':
                                        if ($customer->getData($attributeCode)) {
                                            $group_id = (int)$customer->getData($attributeCode);
                                            $customerGroup = Mage::helper('customer')->getGroups()->toOptionHash();
                                            $mergeVars[$key] = $customerGroup[$group_id];
                                        }
                                        break;
                                    default:
                                        if($customer->getData($attributeCode)) {
                                            $mergeVars[$key] = $customer->getData($attributeCode);
                                        }
                                        break;
                                }

//                            }
                        } else {
                            switch ($attributeCode) {
                                case 'group_id':
                                    $mergeVars[$key] = 'NOT LOGGED IN';
                                    break;
                                case 'store_id':
                                    $mergeVars[$key] = $storeId;
                                    break;
                                case 'website_id':
                                    $websiteId = Mage::getModel('core/store')->load($storeId)->getWebsiteId();
                                    $mergeVars[$key] = $websiteId;
                                    break;
                                case 'created_in':
                                    $storeCode = Mage::getModel('core/store')->load($storeId)->getCode();
                                    $mergeVars[$key] = $storeCode;
                                    break;
                                case 'firstname':
                                    if($object instanceof Mage_Newsletter_Model_Subscriber) {
                                        $firstName = $object->getSubscriberFirstname();
                                    } else {
                                        $firstName = $customer->getFirstname();
                                    }

                                    if ($firstName) {
                                        $mergeVars[$key] = $firstName;
                                    }
                                    break;
                                case 'lastname':
                                    if($object instanceof Mage_Newsletter_Model_Subscriber) {
                                        $lastName = $object->getSubscriberLastname();
                                    } else {
                                        $lastName = $customer->getLastname();
                                    }

                                    if ($lastName) {
                                        $mergeVars[$key] = $lastName;
                                    }
                            }
                        }
                    }
                }

                switch ($customAtt) {
                    case 'billing_company':
                    case 'shipping_company':
                        $addr = explode('_', $attributeCode);
                        $address = $customer->getPrimaryAddress('default_'.ucfirst($addr[0]));

                        if ($address) {
                            $company = $address->getCompany();
                            if ($company) {
                                $mergeVars[$key] = $company;
                            }
                        }
                        break;
                    case 'billing_telephone':
                    case 'shipping_telephone':
                        $addr = explode('_', $attributeCode);
                        $address = $customer->getPrimaryAddress('default_'.ucfirst($addr[0]));

                        if ($address) {
                            $telephone = $address->getTelephone();
                            if ($telephone) {
                                $mergeVars[$key] = $telephone;
                            }
                        }
                        break;
                    case 'billing_country':
                    case 'shipping_country':
                        $addr = explode('_', $attributeCode);
                        $address = $customer->getPrimaryAddress('default_'.ucfirst($addr[0]));

                        if ($address) {
                            $countryCode = $address->getCountry();
                            if ($countryCode) {
                                $countryName = Mage::getModel('directory/country')->loadByCode($countryCode)->getName();
                                $mergeVars[$key] = $countryName;
                            }
                        }
                        break;
                    case 'billing_zipcode':
                    case 'shipping_zipcode':
                        $addr = explode('_', $attributeCode);
                        $address = $customer->getPrimaryAddress('default_'.ucfirst($addr[0]));

                        if ($address) {
                            $zipCode = $address->getPostcode();
                            if ($zipCode) {
                                $mergeVars[$key] = $zipCode;
                            }
                        }
                        break;
                }
            }
        }

        return (!empty($mergeVars)) ? $mergeVars : null;
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