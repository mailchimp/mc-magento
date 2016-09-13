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
    const DEFAULT_OPT_IN = true;

    public function createBatchJson($mailchimpStoreId)
    {
        //get customers
        $collection = Mage::getModel('customer/customer')->getCollection()
            ->addAttributeToSelect('id')
            ->addAttributeToFilter(
                array(
                array('attribute' => 'mailchimp_sync_delta', 'null' => true),
                array('attribute' => 'mailchimp_sync_delta', 'eq' => ''),
                array('attribute' => 'mailchimp_sync_delta', 'lt' => Mage::helper('mailchimp')->getMCMinSyncDateFlag()),
                array('attribute' => 'mailchimp_sync_modified', 'eq'=> 1)
                ), '', 'left'
            );
        $collection->getSelect()->limit(self::BATCH_LIMIT);

        $customerArray = array();
        $batchId = Ebizmarts_MailChimp_Model_Config::IS_CUSTOMER . '_' . date('Y-m-d-H-i-s');

        $counter = 0;
        foreach ($collection as $item) {
            $customer = Mage::getModel('customer/customer')->load($item->getId());
            $data = $this->_buildCustomerData($customer);
            $customerJson = "";

            //enconde to JSON
            try {
                $customerJson = json_encode($data);
            } catch (Exception $e) {
                //json encode failed
                Mage::helper('mailchimp')->logError("Customer ".$customer->getId()." json encode failed");
            }

            if (!empty($customerJson)) {
                $patchPath = "/ecommerce/stores/" . $mailchimpStoreId . "/customers/" . $customer->getId();
                if ($customer->getMailchimpSyncModified()) {
                    $customerArray[$counter]['method'] = "PATCH";
                    $customerArray[$counter]['path'] = $patchPath;
                } else {
                    $customerArray[$counter]['method'] = "POST";
                    $customerArray[$counter]['path'] = "/ecommerce/stores/" . $mailchimpStoreId . "/customers";
                }
                $customerArray[$counter]['operation_id'] = $batchId . '_' . $customer->getId();
                $customerArray[$counter]['body'] = $customerJson;

                //update customers delta
                $customer->setData("mailchimp_sync_delta", Varien_Date::now());
                $customer->setData("mailchimp_sync_error", "");
                $customer->setData("mailchimp_sync_modified", 0);
                $customer->setMailchimpUpdateObserverRan(true);
                $customer->save();
            }
            $counter++;
        }
        return $customerArray;
    }

    protected function _buildCustomerData($customer)
    {
        $data = array();
        $data["id"] = $customer->getId();
        $data["email_address"] = $customer->getEmail();
        $data["first_name"] = $customer->getFirstname();
        $data["last_name"] = $customer->getLastname();
        $data["opt_in_status"] = self::DEFAULT_OPT_IN;

        //customer orders data
        $orderCollection = Mage::getModel('sales/order')->getCollection()
            ->addFieldToFilter('state', 'complete')
            ->addAttributeToFilter('customer_id', array('eq' => $customer->getId()));
        $totalOrders = 0;
        $totalAmountSpent = 0;
        foreach ($orderCollection as $order) {
            $totalOrders++;
            $totalAmountSpent += (int)$order->getGrandTotal();
        }
        $data["orders_count"] = $totalOrders;
        $data["total_spent"] = $totalAmountSpent;

        //addresses data
        foreach ($customer->getAddresses() as $address) {
            //send only first address
            if (!array_key_exists("address", $data)) {
                $street = $address->getStreet();
                $data["address"] = array(
                    "address1" => $street[0],
                    "address2" => count($street)>1 ? $street[1] : "",
                    "city" => $address->getCity(),
                    "province" => $address->getRegion() ? $address->getRegion() : "",
                    "province_code" => $address->getRegionCode() ? $address->getRegionCode() : "",
                    "postal_code" => $address->getPostcode(),
                    "country" => Mage::getModel('directory/country')->loadByCode($address->getCountry())->getName(),
                    "country_code" => $address->getCountry()
                );

                //company
                if ($address->getCompany()) {
                    $data["company"] = $address->getCompany();
                }
                break;
            }
        }
        $mergeFields = $this->getMergeVars($customer);
        if (is_array($mergeFields)) {
            $data = array_merge($mergeFields, $data);
        }

        return $data;
    }
    public function update($customer)
    {
        if (Mage::helper('mailchimp')->isEcomSyncDataEnabled()) {
//        $customer->setData("mailchimp_sync_delta", Varien_Date::now());
            $customer->setData("mailchimp_sync_error", "");
            $customer->setData("mailchimp_sync_modified", 1);
        }
    }

    public function getMergeVars($object)
    {
        $storeId = $object->getStoreId();
        $maps = unserialize(Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_MAP_FIELDS, $storeId));
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
                foreach ($attrSetId as $attribute) {
                    if ($attribute['attribute_id'] == $customAtt) {
                        $attributeCode = $attribute['attribute_code'];
                        if ($customer->getId()) {
//                            if ($customer->getData($attributeCode)) {
                                switch ($attributeCode) {
                                    case 'default_billing':
                                    case 'default_shipping':
                                        $addr = explode('_', $attributeCode);
                                        $address = $customer->{'getPrimary' . ucfirst($addr[1]) . 'Address'}();
                                        if (!$address) {
                                            if ($customer->{'getDefault' . ucfirst($addr[1])}()) {
                                                $address = Mage::getModel('customer/address')->load($customer->{'getDefault' . ucfirst($addr[1])}());
                                            }
                                        }
                                        if ($address) {
                                            $mergeVars[$key] = array(
                                                'addr1' => $address->getStreet(1),
                                                'addr2' => $address->getStreet(2),
                                                'city' => $address->getCity(),
                                                'state' => (!$address->getRegion() ? $address->getCity() : $address->getRegion()),
                                                'zip' => $address->getPostcode(),
                                                'country' => $address->getCountryId()
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
                        $address = $customer->{'getPrimary' . ucfirst($addr[0]) . 'Address'}();
                        if (!$address) {
                            if ($customer->{'getDefault' . ucfirst($addr[0])}()) {
                                $address = Mage::getModel('customer/address')->load($customer->{'getDefault' . ucfirst($addr[0])}());
                            }
                        }
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
                        $address = $customer->{'getPrimary' . ucfirst($addr[0]) . 'Address'}();
                        if (!$address) {
                            if ($customer->{'getDefault' . ucfirst($addr[0])}()) {
                                $address = Mage::getModel('customer/address')->load($customer->{'getDefault' . ucfirst($addr[0])}());
                            }
                        }
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
                        $address = $customer->{'getPrimary' . ucfirst($addr[0]) . 'Address'}();
                        if (!$address) {
                            if ($customer->{'getDefault' . ucfirst($addr[0])}()) {
                                $address = Mage::getModel('customer/address')->load($customer->{'getDefault' . ucfirst($addr[0])}());
                            }
                        }
                        if ($address) {
                            $country = $address->getCountryId();
                            if ($country) {
                                $countryName = Mage::getModel('directory/country')->load($country)->getName();
                                $mergeVars[$key] = $countryName;
                            }
                        }
                        break;
                    case 'billing_zipcode':
                    case 'shipping_zipcode':
                        $addr = explode('_', $attributeCode);
                        $address = $customer->{'getPrimary' . ucfirst($addr[0]) . 'Address'}();
                        if (!$address) {
                            if ($customer->{'getDefault' . ucfirst($addr[0])}()) {
                                $address = Mage::getModel('customer/address')->load($customer->{'getDefault' . ucfirst($addr[0])}());
                            }
                        }
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

    public function createGuestCustomer($guestId, $order) {
        $guestCustomer = Mage::getModel('customer/customer')->setId($guestId);
        foreach ($order->getData() as $key => $value) {
            $keyArray = explode('_', $key);
            if ($value && isset($keyArray[0]) && $keyArray[0] == 'customer') {
                $guestCustomer->{'set' . ucfirst($keyArray[1])}($value);
            }
        }
        return $guestCustomer;
    }
}