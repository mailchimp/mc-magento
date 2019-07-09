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

class Ebizmarts_MailChimp_Model_Api_Subscribers_MailchimpTags
{
    const GENDER_VALUE_MALE = 1;
    const GENDER_VALUE_FEMALE = 2;

    /**
     * @var int
     */
    private $_storeId;
    /**
     * @var array
     */
    private $_mailChimpTags;
    /**
     * @var Mage_Newsletter_Model_Subscriber
     */
    private $_subscriber;
    /**
     * @var Mage_Customer_Model_Customer
     */
    private $_customer;
    /**
     * @var Ebizmarts_MailChimp_Helper_Data
     */
    private $_mcHelper;
    /**
     * @var Mage_Sales_Model_Order
     */
    private $_lastOrder;

    public function __construct()
    {
        $this->setMailChimpHelper();
    }

    /**
     * @param $storeId
     */
    public function setStoreId($storeId)
    {
        $this->_storeId = $storeId;
    }

    /**
     * @return int
     */
    public function getStoreId()
    {
        return $this->_storeId;
    }

    /**
     * @param Mage_Newsletter_Model_Subscriber $subscriber
     */
    public function setSubscriber($subscriber)
    {
        $this->_subscriber = $subscriber;
    }

    /**
     * @return Mage_Newsletter_Model_Subscriber
     */
    public function getSubscriber()
    {
        return $this->_subscriber;
    }

    /**
     * @param Mage_Customer_Model_Customer $customer
     */
    public function setCustomer($customer)
    {
        $this->_customer = $customer;
    }

    /**
     * @return Mage_Customer_Model_Customer
     */
    public function getCustomer()
    {
        return $this->_customer;
    }

    /**
     * @return array
     */
    public function getMailChimpTags()
    {
        return $this->_mailChimpTags;
    }

    /**
     * @param $key
     * @param $value
     */
    public function addMailChimpTag($key, $value)
    {
        $this->_mailChimpTags[$key] = $value;
    }

    /**
     * @param $key
     * @return mixed|null
     */
    public function getMailChimpTagValue($key)
    {
        $mailchimpTagValue = null;

        if (isset($this->_mailChimpTags[$key])) {
            $mailchimpTagValue = $this->_mailChimpTags[$key];
        }

        return $mailchimpTagValue;
    }

    /**
     * @return Mage_Sales_Model_Order
     */
    public function getLastOrder()
    {
        return $this->_lastOrder;
    }

    /**
     * @param Mage_Sales_Model_Order $lastOrder
     */
    public function setLastOrder($lastOrder)
    {
        $this->_lastOrder = $lastOrder;
    }

    /**
     * @throws Mage_Core_Exception
     */
    public function buildMailChimpTags()
    {
        $helper = $this->getMailchimpHelper();
        $storeId = $this->getStoreId();
        $mapFields = $helper->getMapFields($storeId);
        $maps = $this->unserializeMapFields($mapFields);

        $attrSetId = $this->getEntityAttributeCollection()
            ->setEntityTypeFilter(1)
            ->addSetInfo()
            ->getData();

        foreach ($maps as $map) {
            $customAtt = $map['magento'];
            $chimpTag = $map['mailchimp'];
            if ($chimpTag && $customAtt) {
                $key = strtoupper($chimpTag);

                if (is_numeric($customAtt)) {
                    $this->buildCustomerAttributes($attrSetId, $customAtt, $key);
                } else {
                    $this->buildCustomizedAttributes($customAtt, $key);
                }
            }
        }

        $newVars = $this->getNewVarienObject();
        $this->dispatchEventMergeVarAfter($newVars);

        if ($newVars->hasData()) {
            $this->mergeMailchimpTags($newVars->getData());
        }
    }

    /**
     * @return Varien_Object
     */
    protected function getNewVarienObject()
    {
        return new Varien_Object;
    }

    /**
     * @param $attributeCode
     * @param $key
     * @param $attribute
     * @return |null
     */
    protected function customerAttributes($attributeCode, $key, $attribute)
    {
        $subscriber = $this->getSubscriber();
        $customer   = $this->getCustomer();

        $eventValue = null;

        if ($attributeCode == 'email') {
            return $eventValue;
        } elseif ($attributeCode == 'default_billing' || $attributeCode == 'default_shipping') {
            $this->addDefaultShipping($attributeCode, $key, $customer);
        } elseif ($attributeCode == 'gender') {
            $this->addGender($attributeCode, $key, $customer);
        } elseif ($attributeCode == 'group_id') {
            $this->addGroupId($attributeCode, $key, $customer);
        } elseif ($attributeCode == 'firstname') {
            $this->addFirstName($key, $subscriber, $customer);
        } elseif ($attributeCode == 'lastname') {
            $this->addLastName($key, $subscriber, $customer);
        } elseif ($attributeCode == 'store_id') {
            $this->addMailChimpTag($key, $this->getStoreId());
        } elseif ($attributeCode == 'website_id') {
            $this->addWebsiteId($key);
        } elseif ($attributeCode == 'created_in') {
            $this->addCreatedIn($key);
        } elseif ($attributeCode == 'dob') {
            $this->addDob($attributeCode, $key, $customer);
        } else {
            $this->addUnknownMergeField($attributeCode, $key, $attribute, $customer);
        }

        if ($this->getMailChimpTagValue($key) !== null) {
            $eventValue = $this->getMailChimpTagValue($key);
        }

        return $eventValue;
    }

    /**
     * @param $mapFields
     * @return mixed
     */
    protected function unserializeMapFields($mapFields)
    {
        return unserialize($mapFields);
    }

    protected function getEntityAttributeCollection()
    {
        return Mage::getResourceModel('eav/entity_attribute_collection');
    }

    /**
     * Add possibility to change value on certain merge tag
     *
     * @param $attributeCode
     * @param $eventValue
     */
    protected function dispatchMergeVarBefore($attributeCode, &$eventValue)
    {
        Mage::dispatchEvent(
            'mailchimp_merge_field_send_before',
            array(
                'customer_id' => $this->getCustomer()->getId(),
                'subscriber_email' => $this->getSubscriber()->getSubscriberEmail(),
                'merge_field_tag' => $attributeCode,
                'merge_field_value' => &$eventValue
            )
        );
    }

    /**
     * Allow possibility to add new vars in 'new_vars' array
     *
     * @param $newVars
     */
    protected function dispatchEventMergeVarAfter( &$newVars)
    {
        Mage::dispatchEvent(
            'mailchimp_merge_field_send_after',
            array(
                'subscriber' => $this->getSubscriber(),
                'vars' => $this->getMailChimpTags(),
                'new_vars' => &$newVars
            )
        );
    }

    /**
     * @return mixed
     */
    protected function toArray()
    {
        return $this->_mailChimpTags;
    }

    /**
     * @param $mailchimpTags
     * @return bool
     */
    protected function mergeMailchimpTags($mailchimpTags)
    {
        if (is_array($mailchimpTags)) {
            $this->_mailChimpTags = array_merge($this->_mailChimpTags, $mailchimpTags);
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $storeId
     * @return mixed
     */
    protected function getWebSiteByStoreId($storeId)
    {
        return Mage::getModel('core/store')->load($storeId)->getWebsiteId();
    }

    /**
     * @param $address
     * @return array | returns an array with the address data of the customer.
     */
    protected function getAddressData($address)
    {
        $lastOrder = $this->getLastOrderByEmail();
        $addressData = $this->getAddressFromLastOrder($lastOrder);
        if (!empty($addressData)) {
            if ($address) {
                $street = $address->getStreet();
                if (count($street) > 1) {
                    $addressData["addr1"] = $street[0];
                    $addressData["addr2"] = $street[1];
                } else {
                    if (!empty($street[0])) {
                        $addressData["addr1"] = $street[0];
                    }
                }

                if ($address->getCity()) {
                    $addressData["city"] = $address->getCity();
                }

                if ($address->getRegion()) {
                    $addressData["state"] = $address->getRegion();
                }

                if ($address->getPostcode()) {
                    $addressData["zip"] = $address->getPostcode();
                }

                if ($address->getCountry()) {
                    $addressData["country"] = Mage::getModel('directory/country')
                        ->loadByCode($address->getCountry())
                        ->getName();
                }
            }
        }

        return $addressData;
    }

    /**
     * @param $attributeCode
     * @param $customer
     * @return string | returns the data of the attribute code.
     */
    protected function getCustomerGroupLabel($attributeCode, $customer)
    {
        return $customer->getData($attributeCode);
    }

    /**
     * @param $mergeVars
     * @param $key
     * @param $genderValue
     * @return string | return a string with the gender of the customer.
     */
    protected function getGenderValue($mergeVars, $key, $genderValue)
    {
        if ($genderValue == self::GENDER_VALUE_MALE) {
            $mergeVars[$key] = 'Male';
        } elseif ($genderValue == self::GENDER_VALUE_FEMALE) {
            $mergeVars[$key] = 'Female';
        }

        return $mergeVars[$key];
    }

    /**
     * @param $subscriber
     * @param $customer
     * @return string | returns the first name of the customer.
     */
    protected function getFirstName($subscriber, $customer)
    {
        $lastOrder = $this->getLastOrderByEmail();
        $firstName = $customer->getFirstname();

        if (!$firstName) {
            if ($subscriber->getSubscriberFirstname()) {
                $firstName = $subscriber->getSubscriberFirstname();
            } elseif ($lastOrder && $lastOrder->getCustomerFirstname()) {
                $firstName = $lastOrder->getCustomerFirstname();
            }
        }

        return $firstName;
    }

    /**
     * @param $subscriber
     * @param $customer
     * @return string | return the last name of the customer.
     */
    protected function getLastName($subscriber, $customer)
    {
        $lastOrder = $this->getLastOrderByEmail();
        $lastName = $customer->getLastname();

        if (!$lastName) {
            if ($subscriber->getSubscriberLastname()) {
                $lastName = $subscriber->getSubscriberLastname();
            } elseif ($lastOrder && $lastOrder->getCustomerLastname()) {
                $lastName = $lastOrder->getCustomerLastname();
            }
        }

        return $lastName;
    }

    /**
     * @param $lastOrder
     * @return array
     */
    protected function getAddressFromLastOrder($lastOrder)
    {
        $addressData = array();
        if ($lastOrder && $lastOrder->getShippingAddress()) {
            $addressData = $lastOrder->getShippingAddress();
        }

        return $addressData;
    }

    /**
     * @param $customAtt
     * @param $customer
     * @return array | returns an array with the address if it exists
     */
    protected function getAddressForCustomizedAttributes($customAtt, $customer)
    {
        $lastOrder = $this->getLastOrderByEmail();
        $address = $this->getAddressFromLastOrder($lastOrder);
        if (!empty($address)) {
            $addr = explode('_', $customAtt);
            $address = $customer->getPrimaryAddress('default_' . $addr[0]);
        }

        return $address;
    }

    /**
     * @param $customAtt
     * @param $key
     * @return mixed | null
     */
    protected function customizedAttributes($customAtt, $key)
    {
        $eventValue = null;
        $customer = $this->getCustomer();

        if ($customAtt == 'billing_company' || $customAtt == 'shipping_company') {
            $this->addCompany($customAtt, $customer, $key);
        } elseif ($customAtt == 'billing_telephone' || $customAtt == 'shipping_telephone') {
            $this->addTelephoneFromCustomizedAttribute($customAtt, $key, $customer);
        } elseif ($customAtt == 'billing_country' || $customAtt == 'shipping_country') {
            $this->addCountryFromCustomizedAttribute($customAtt, $key, $customer);
        } elseif ($customAtt == 'billing_zipcode' || $customAtt == 'shipping_zipcode') {
            $this->addZipCodeFromCustomizedAttribute($customAtt, $key, $customer);
        } elseif ($customAtt == 'billing_state' || $customAtt == 'shipping_state') {
            $this->addStateFromCustomizedAttribute($customAtt, $key, $customer);
        } elseif ($customAtt == 'dop') {
            $this->addDopFromCustomizedAttribute($key);
        } elseif ($customAtt == 'store_code') {
            $this->addStoreCodeFromCustomizedAttribute($key);
        }

        if ((string)$this->getMailChimpTagValue($key) != '') {
            $eventValue = $this->getMailChimpTagValue($key);
        }

        return $eventValue;
    }

    /**
     * @param $attributeCode
     * @param $customer
     * @param $attribute
     * @return mixed
     */
    protected function getUnknownMergeField($attributeCode, $customer, $attribute)
    {
        $optionValue = null;

        $attrValue = $this->getCustomerGroupLabel($attributeCode, $customer);
        if ($attrValue !== null) {
            if ($attribute['frontend_input'] == 'select' && $attrValue) {
                $attr = $customer->getResource()->getAttribute($attributeCode);
                $optionValue = $attr->getSource()->getOptionText($attrValue);
            } elseif ($attrValue) {
                $optionValue = $attrValue;
            }
        }

        return $optionValue;
    }


    /**
     * @param $attributeCode
     * @param $customer
     * @return mixed
     * @throws Mage_Core_Model_Store_Exception
     */
    protected function getDateOfBirth($attributeCode, $customer)
    {
        return $this->getMailchimpHelper()->formatDate(
            $this->getCustomerGroupLabel($attributeCode, $customer),
            'm/d', 1
        );
    }

    /**
     * If orders with the given email exists, returns the date of the last order made.
     *
     * @param  $subscriberEmail
     * @return null
     */
    protected function getLastDateOfPurchase()
    {
        $lastDateOfPurchase = null;
        $lastOrder = $this->getLastOrderByEmail();
        if ($lastOrder !== null) {
            $lastDateOfPurchase = $lastOrder->getCreatedAt();
        }

        return $lastDateOfPurchase;
    }

    /**
     * @param $customAtt
     * @param $customer
     * @param $mergeVars
     * @param $key
     * @return mixed
     */
    protected function addCompany($customAtt, $customer, $key)
    {
        $address = $this->getAddressForCustomizedAttributes($customAtt, $customer);
        if ($address) {
            $company = $address->getCompany();
            if ($company) {
                $this->addMailChimpTag($key, $company);
            }
        }

    }

    /**
     * return the latest order for this subscriber
     * @return Mage_Sales_Model_Order
     */
    protected function getLastOrderByEmail()
    {
        $lastOrder = $this->getLastOrder();
        if ($lastOrder === null) {
            $helper = $this->getMailchimpHelper();
            $orderCollection = $helper->getOrderCollectionByCustomerEmail($this->getSubscriber()->getSubscriberEmail());
            if ($this->isNotEmptyOrderCollection($orderCollection)) {
                $lastOrder = $orderCollection->setOrder('created_at', 'DESC')->getFirstItem();
                $this->setLastOrder($lastOrder);
            }
        }

        return $lastOrder;
    }

    /**
     * @param $orderCollection
     * @return bool | returns true if the size of the orderCollection have at least one element.
     */
    protected function isNotEmptyOrderCollection($orderCollection)
    {
        return $orderCollection->getSize() > 0;
    }

    /**
     * @return Ebizmarts_MailChimp_Helper_Data
     */
    public function getMailchimpHelper()
    {
        return $this->_mcHelper;
    }

    /**
     * @param $mageMCHelper
     */
    protected function setMailChimpHelper()
    {
        $this->_mcHelper = Mage::helper('mailchimp');
    }

    /**
     * @param $key
     * @return bool
     */
    protected function mailChimpTagIsSet($key)
    {
        return isset($this->_mailChimpTags[$key]);
    }

    /**
     * @param $attrSetId
     * @param $customAtt
     * @param $key
     */
    protected function buildCustomerAttributes($attrSetId, $customAtt, $key)
    {
        $eventValue = null;
        foreach ($attrSetId as $attribute) {
            if ($attribute['attribute_id'] == $customAtt) {
                $attributeCode = $attribute['attribute_code'];
                $eventValue = $this->customerAttributes(
                    $attributeCode, $key, $attribute
                );

                $this->dispatchMergeVarBefore($attributeCode, $eventValue);
                if ($eventValue !== null) {
                    $this->addMailChimpTag($key, $eventValue);
                }
            }
        }
    }

    /**
     * @param $customAtt
     * @param $key
     */
    protected function buildCustomizedAttributes($customAtt, $key)
    {
        $eventValue = null;
        $eventValue = $this->customizedAttributes(
            $customAtt, $key
        );

        $this->dispatchMergeVarBefore($customAtt, $eventValue);
        if ($eventValue !== null) {
            $this->addMailChimpTag($key, $eventValue);
        }
    }

    /**
     * @param $attributeCode
     * @param $key
     * @param $customer
     */
    protected function addDefaultShipping($attributeCode, $key, $customer)
    {
        $address = $customer->getPrimaryAddress($attributeCode);
        $addressData = $this->getAddressData($address);

        if (count($addressData)) {
            $this->addMailChimpTag($key, $addressData);
        }
    }

    /**
     * @param $attributeCode
     * @param $key
     * @param $customer
     */
    protected function addGender($attributeCode, $key, $customer)
    {
        if ($this->getCustomerGroupLabel($attributeCode, $customer)) {
            $genderValue = $this->getCustomerGroupLabel($attributeCode, $customer);
            $this->addMailChimpTag($key, $this->getGenderValue($this->_mailChimpTags, $key, $genderValue));
        }
    }

    /**
     * @param $attributeCode
     * @param $key
     * @param $customer
     */
    protected function addGroupId($attributeCode, $key, $customer)
    {
        if ($this->getCustomerGroupLabel($attributeCode, $customer)) {
            $groupId = (int)$this->getCustomerGroupLabel($attributeCode, $customer);
            $customerGroup = Mage::helper('customer')->getGroups()->toOptionHash();
            $this->addMailChimpTag($key, $customerGroup[$groupId]);
        } else {
            $this->addMailChimpTag($key, 'NOT LOGGED IN');
        }
    }

    /**
     * @param $key
     * @param $subscriber
     * @param $customer
     */
    protected function addFirstName($key, $subscriber, $customer)
    {
        $firstName = $this->getFirstName($subscriber, $customer);

        if ($firstName) {
            $this->addMailChimpTag($key, $firstName);
        }
    }

    /**
     * @param $key
     * @param $subscriber
     * @param $customer
     */
    protected function addLastName($key, $subscriber, $customer)
    {
        $lastName = $this->getLastName($subscriber, $customer);

        if ($lastName) {
            $this->addMailChimpTag($key, $lastName);
        }
    }

    /**
     * @param $key
     */
    protected function addWebsiteId($key)
    {
        $websiteId = $this->getWebSiteByStoreId($this->getStoreId());
        $this->addMailChimpTag($key, $websiteId);
    }

    /**
     * @param $key
     */
    protected function addCreatedIn($key)
    {
        $storeName = Mage::getModel('core/store')->load($this->getStoreId())->getName();
        $this->addMailChimpTag($key, $storeName);
    }

    /**
     * @param $attributeCode
     * @param $key
     * @param $customer
     */
    protected function addDob($attributeCode, $key, $customer)
    {
        if ($this->getCustomerGroupLabel($attributeCode, $customer)) {
            $this->addMailChimpTag($key, $this->getDateOfBirth($attributeCode, $customer));
        }
    }

    /**
     * @param $attributeCode
     * @param $key
     * @param $attribute
     * @param $customer
     */
    protected function addUnknownMergeField($attributeCode, $key, $attribute, $customer)
    {
        $mergeValue = $this->getUnknownMergeField($attributeCode, $customer, $attribute);
        if ($mergeValue !== null) {
            $this->addMailChimpTag($key, $mergeValue);
        }
    }

    /**
     * @param $customAtt
     * @param $key
     * @param $customer
     */
    protected function addTelephoneFromCustomizedAttribute($customAtt, $key, $customer)
    {
        $address = $this->getAddressForCustomizedAttributes($customAtt, $customer);
        if ($address) {
            $telephone = $address->getTelephone();
            if ($telephone) {
                $this->addMailChimpTag($key, $telephone);
            }
        }
    }

    /**
     * @param $customAtt
     * @param $key
     * @param $customer
     */
    protected function addCountryFromCustomizedAttribute($customAtt, $key, $customer)
    {
        $address = $this->getAddressForCustomizedAttributes($customAtt, $customer);
        if ($address) {
            $countryCode = $address->getCountry();
            if ($countryCode) {
                $countryName = Mage::getModel('directory/country')->loadByCode($countryCode)->getName();
                $this->addMailChimpTag($key, $countryName);
            }
        }
    }

    /**
     * @param $customAtt
     * @param $key
     * @param $customer
     */
    protected function addZipCodeFromCustomizedAttribute($customAtt, $key, $customer)
    {
        $address = $this->getAddressForCustomizedAttributes($customAtt, $customer);
        if ($address) {
            $zipCode = $address->getPostcode();
            if ($zipCode) {
                $this->addMailChimpTag($key, $zipCode);
            }
        }
    }

    /**
     * @param $customAtt
     * @param $key
     * @param $customer
     */
    protected function addStateFromCustomizedAttribute($customAtt, $key, $customer)
    {
        $address = $this->getAddressForCustomizedAttributes($customAtt, $customer);
        if ($address) {
            $state = $address->getRegion();
            if ($state) {
                $this->addMailChimpTag($key, $state);
            }
        }
    }

    /**
     * @param $key
     * @param $subscriberEmail
     */
    protected function addDopFromCustomizedAttribute($key)
    {
        $dop = $this->getLastDateOfPurchase();
        if ($dop) {
            $this->addMailChimpTag($key, $dop);
        }
    }

    /**
     * @param $key
     */
    protected function addStoreCodeFromCustomizedAttribute($key)
    {
        $storeCode = Mage::getModel('core/store')->load($this->getStoreId())->getCode();
        $this->addMailChimpTag($key, $storeCode);
    }

}

