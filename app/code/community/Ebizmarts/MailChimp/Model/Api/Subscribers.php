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
class Ebizmarts_MailChimp_Model_Api_Subscribers
{
    const BATCH_LIMIT = 100;
    const GENDER_VALUE_MALE = 1;
    const GENDER_VALUE_FEMALE = 2;

    /**
     * Ebizmarts_MailChimp_Helper_Data
     */
    private $mcHelper;

    public function __construct()
    {
        $mageMCHelper = Mage::helper('mailchimp');
        $this->setMailchimpHelper($mageMCHelper);
    }

    public function createBatchJson($listId, $storeId, $limit)
    {
        $helper = $this->getMailchimpHelper();
        $thisScopeHasSubMinSyncDateFlag = $helper->getIfConfigExistsForScope(Ebizmarts_MailChimp_Model_Config::GENERAL_SUBMINSYNCDATEFLAG, $storeId);
        $thisScopeHasList = $helper->getIfConfigExistsForScope(Ebizmarts_MailChimp_Model_Config::GENERAL_LIST, $storeId);

        $subscriberArray = array();
        if ($thisScopeHasList && !$thisScopeHasSubMinSyncDateFlag || !$helper->getSubMinSyncDateFlag($storeId)) {
            $realScope = $helper->getRealScopeForConfig(Ebizmarts_MailChimp_Model_Config::GENERAL_LIST, $storeId);
            $configValues = array(array(Ebizmarts_MailChimp_Model_Config::GENERAL_SUBMINSYNCDATEFLAG, Varien_Date::now()));
            $helper->saveMailchimpConfig($configValues, $realScope['scope_id'], $realScope['scope']);
        }

        //get subscribers
        $collection = Mage::getResourceModel('newsletter/subscriber_collection')
            ->addFieldToFilter('subscriber_status', array('eq' => 1))
            ->addFieldToFilter('store_id', array('eq' => $storeId))
            ->addFieldToFilter(
                array(
                    'mailchimp_sync_delta',
                    'mailchimp_sync_delta',
                    'mailchimp_sync_delta',
                    'mailchimp_sync_modified'
                ),
                array(
                    array('null' => true),
                    array('eq' => ''),
                    array('lt' => $helper->getSubMinSyncDateFlag($storeId)),
                    array('eq' => 1)
                )
            );
        $collection->addFieldToFilter('mailchimp_sync_error', array('eq' => ''));
        $collection->getSelect()->limit($limit);
        $date = $helper->getDateMicrotime();
        $batchId = 'storeid-' . $storeId . '_' . Ebizmarts_MailChimp_Model_Config::IS_SUBSCRIBER . '_' . $date;

        $counter = 0;
        foreach ($collection as $subscriber) {
            $data = $this->_buildSubscriberData($subscriber);
            $md5HashEmail = md5(strtolower($subscriber->getSubscriberEmail()));
            $subscriberJson = "";

            //enconde to JSON
            try {
                $subscriberJson = json_encode($data);
            } catch (Exception $e) {
                //json encode failed
                $errorMessage = "Subscriber " . $subscriber->getSubscriberId() . " json encode failed";
                $helper->logError($errorMessage);
            }

            if (!empty($subscriberJson)) {
                if ($subscriber->getMailchimpSyncModified()) {
                    $helper->modifyCounterSubscribers(Ebizmarts_MailChimp_Helper_Data::SUB_MOD);
                } else {
                    $helper->modifyCounterSubscribers(Ebizmarts_MailChimp_Helper_Data::SUB_NEW);
                }

                $subscriberArray[$counter]['method'] = "PUT";
                $subscriberArray[$counter]['path'] = "/lists/" . $listId . "/members/" . $md5HashEmail;
                $subscriberArray[$counter]['operation_id'] = $batchId . '_' . $subscriber->getSubscriberId();
                $subscriberArray[$counter]['body'] = $subscriberJson;

                //update subscribers delta
                $subscriber->setData("mailchimp_sync_delta", Varien_Date::now());
                $subscriber->setData("mailchimp_sync_error", "");
                $subscriber->setData("mailchimp_sync_modified", 0);
                $subscriber->setSubscriberSource(Ebizmarts_MailChimp_Model_Subscriber::SUBSCRIBE_SOURCE);
                $subscriber->save();
            }

            $counter++;
        }

        return $subscriberArray;
    }

    protected function _buildSubscriberData($subscriber)
    {
        $helper = $this->getMailchimpHelper();
        $storeId = $subscriber->getStoreId();
        $data = array();
        $data["email_address"] = $subscriber->getSubscriberEmail();
        $mergeVars = $this->getMergeVars($subscriber);
        if ($mergeVars) {
            $data["merge_fields"] = $mergeVars;
        }

        $status = $this->translateMagentoStatusToMailchimpStatus($subscriber->getStatus());
        $data["status_if_new"] = $status;
        if ($subscriber->getMailchimpSyncModified()) {
            $data["status"] = $status;
        }

        $data["language"] = $helper->getStoreLanguageCode($storeId);
        $interest = $this->_getInterest($subscriber);
        if (count($interest)) {
            $data['interests'] = $interest;
        }

        return $data;
    }

    /**
     * @param $subscriber
     * @return array
     */
    protected function _getInterest($subscriber)
    {
        $storeId = $subscriber->getStoreId();
        $rc = array();
        $helper = $this->getMailchimpHelper();
        $interestsAvailable = $helper->getInterest($storeId);
        $interest = $helper->getInterestGroups(null, $subscriber->getSubscriberId(), $storeId, $interestsAvailable);
        foreach ($interest as $i) {
            foreach ($i['category'] as $key => $value) {
                $rc[$value['id']] = $value['checked'];
            }
        }

        return $rc;
    }

    public function getMergeVars($subscriber)
    {
        $helper = $this->getMailchimpHelper();
        $storeId = $subscriber->getStoreId();
        $mapFields = $helper->getMapFields($storeId);
        $maps = $this->unserilizeMapFields($mapFields);
        $websiteId = $this->getWebSiteByStoreId($storeId);
        $attrSetId = $this->getEntityAttributeCollection()
            ->setEntityTypeFilter(1)
            ->addSetInfo()
            ->getData();
        $mergeVars = array();
        $subscriberEmail = $subscriber->getSubscriberEmail();
        $customer = $this->getCustomerByWebsiteAndId()->setWebsiteId($websiteId)->load($subscriber->getCustomerId());

        $this->saveLastOrderInSession($subscriberEmail);

        foreach ($maps as $map) {
            $customAtt = $map['magento'];
            $chimpTag = $map['mailchimp'];
            if ($chimpTag && $customAtt) {
                $eventValue = null;
                $key = strtoupper($chimpTag);
                if (is_numeric($customAtt)) {
                    foreach ($attrSetId as $attribute) {
                        if ($attribute['attribute_id'] == $customAtt) {
                            $attributeCode = $attribute['attribute_code'];

                            $mergeVars = $this->customerAttributes($subscriber, $attributeCode, $customer, $mergeVars, $key, $storeId, $attribute);
                            $eventValue = $mergeVars[$key];
                            $this->dispatchMergeVarBefore($customer, $subscriberEmail, $attributeCode, $eventValue);
                        }
                    }
                } else {
                    $mergeVars = $this->customizedAttributes($customAtt, $customer, $mergeVars, $key, $helper, $subscriberEmail, $storeId);
                    if (isset($mergeVars[$key])) {
                        $eventValue = $mergeVars[$key];
                    }

                    $this->dispatchMergeVarBefore($customer, $subscriberEmail, $customAtt, $eventValue);
                }

                if ($eventValue) {
                    $mergeVars[$key] = $eventValue;
                }
            }
        }

        $newVars = new Varien_Object;
        $this->dispatchEventMergeVarAfter($subscriber, $mergeVars, $newVars);

        if ($newVars->hasData()) {
            $mergeVars = array_merge($mergeVars, $newVars->getData());
        }

        return (!empty($mergeVars)) ? $mergeVars : null;
    }

    /**
     * @param $subscriber
     * @param bool $updateStatus If set to true, it will force the status update even for those already subscribed.
     */
    public function updateSubscriber($subscriber, $updateStatus = false)
    {
        $saveSubscriber = false;
        $isAdmin = Mage::app()->getStore()->isAdmin();
        $helper = $this->getMailchimpHelper();
        $storeId = $subscriber->getStoreId();
        $subscriptionEnabled = $helper->isSubscriptionEnabled($storeId);
        if ($subscriptionEnabled) {
            $listId = $helper->getGeneralList($storeId);
            $newStatus = $this->translateMagentoStatusToMailchimpStatus($subscriber->getStatus());
            $forceStatus = ($updateStatus) ? $newStatus : null;
            try {
                $api = $helper->getApi($storeId);
            } catch (Ebizmarts_MailChimp_Helper_Data_ApiKeyException $e) {
                $helper->logError($e->getMessage());
                return;
            }

            $mergeVars = $this->getMergeVars($subscriber);
            $language = $helper->getStoreLanguageCode($storeId);
            $interest = $this->_getInterest($subscriber);

            $md5HashEmail = md5(strtolower($subscriber->getSubscriberEmail()));
            try {
                $api->lists->members->addOrUpdate(
                    $listId,
                    $md5HashEmail,
                    $subscriber->getSubscriberEmail(),
                    $newStatus,
                    null,
                    $forceStatus,
                    $mergeVars,
                    $interest,
                    $language,
                    null,
                    null
                );
                $subscriber->setData("mailchimp_sync_delta", Varien_Date::now());
                $subscriber->setData("mailchimp_sync_error", "");
                $subscriber->setData("mailchimp_sync_modified", 0);
                $saveSubscriber = true;
            } catch (MailChimp_Error $e) {
                if ($newStatus === 'subscribed' && $subscriber->getIsStatusChanged() && !$helper->isSubscriptionConfirmationEnabled($storeId)) {
                    if (strstr($e->getMailchimpDetails(), 'is in a compliance state')) {
                        try {
                            $api->lists->members->update($listId, $md5HashEmail, null, 'pending', $mergeVars, $interest);
                            $subscriber->setSubscriberStatus(Mage_Newsletter_Model_Subscriber::STATUS_NOT_ACTIVE);
                            $saveSubscriber = true;
                            $message = $helper->__('To begin receiving the newsletter, you must first confirm your subscription');
                            Mage::getSingleton('core/session')->addWarning($message);
                        } catch (MailChimp_Error $e) {
                            $errorMessage = $e->getFriendlyMessage();
                            $helper->logError($errorMessage);
                            if ($isAdmin) {
                                $this->addError($errorMessage);
                            } else {
                                $errorMessage = $helper->__("The subscription could not be applied.");
                                $this->addError($errorMessage);
                            }

                            $subscriber->setSubscriberStatus(Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED);
                            $saveSubscriber = true;
                        } catch (Exception $e) {
                            $helper->logError($e->getMessage());
                        }
                    } else {
                        $errorMessage = $e->getFriendlyMessage();
                        $helper->logError($errorMessage);
                        if ($isAdmin) {
                            $this->addError($errorMessage);
                        } else {
                            $errorMessage = $helper->__("The subscription could not be applied.");
                            $this->addError($errorMessage);
                        }

                        $subscriber->setSubscriberStatus(Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED);
                        $saveSubscriber = true;
                    }
                } else {
                    $errorMessage = $e->getFriendlyMessage();
                    $helper->logError($errorMessage);
                    if ($isAdmin) {
                        $this->addError($errorMessage);
                    } else {
                        $errorMessage = $helper->__("The subscription could not be applied.");
                        $this->addError($errorMessage);
                    }

                    $subscriber->setSubscriberStatus(Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED);
                }
            } catch (Exception $e) {
                $helper->logError($e->getMessage());
            }

            if ($saveSubscriber) {
                $subscriber->setSubscriberSource(Ebizmarts_MailChimp_Model_Subscriber::SUBSCRIBE_SOURCE);
                $subscriber->save();
            }
        }
    }

    /**
     * @param $status
     * @return string
     */
    public function translateMagentoStatusToMailchimpStatus($status)
    {
        if ($this->statusEqualsUnsubscribed($status)) {
            $status = 'unsubscribed';
        } elseif ($this->statusEqualsNotActive($status) || $this->statusEqualsUnconfirmed($status)) {
            $status = 'pending';
        } elseif ($this->statusEqualsSubscribed($status)) {
            $status = 'subscribed';
        }

        return $status;
    }

    /**
     * @param $status
     * @return bool
     */
    protected function statusEqualsUnsubscribed($status)
    {
        return $status == Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED;
    }

    /**
     * @param $status
     * @return bool
     */
    protected function statusEqualsSubscribed($status)
    {
        return $status == Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED;
    }

    /**
     * @param $status
     * @return bool
     */
    protected function statusEqualsNotActive($status)
    {
        return $status == Mage_Newsletter_Model_Subscriber::STATUS_NOT_ACTIVE;
    }

    /**
     * @param $status
     * @return bool
     */
    protected function statusEqualsUnconfirmed($status)
    {
        return $status == Mage_Newsletter_Model_Subscriber::STATUS_UNCONFIRMED;
    }

    /**
     * @param $subscriber
     */
    public function deleteSubscriber($subscriber)
    {
        $helper = $this->getMailchimpHelper();
        $storeId = $subscriber->getStoreId();
        $listId = $helper->getGeneralList($storeId);
        try {
            $api = $helper->getApi($storeId);
            $md5HashEmail = md5(strtolower($subscriber->getSubscriberEmail()));
            $api->lists->members->update($listId, $md5HashEmail, null, 'unsubscribed');
        } catch (Ebizmarts_MailChimp_Helper_Data_ApiKeyException $e) {
            $helper->logError($e->getMessage());
        } catch (MailChimp_Error $e) {
            $helper->logError($e->getFriendlyMessage());
            Mage::getSingleton('adminhtml/session')->addError($e->getFriendlyMessage());
        } catch (Exception $e) {
            $helper->logError($e->getMessage());
        }
    }

    public function update($emailAddress, $storeId)
    {
        $subscriber = Mage::getSingleton('newsletter/subscriber')->loadByEmail($emailAddress);
        if ($subscriber->getId()) {
            $subscriber->setMailchimpSyncModified(1)
                ->save();
        }
    }

    /**
     * @param $address
     * @return array | returns an array with the address data of the customer.
     */
    protected function getAddressData($address)
    {
        $lastOrder = $this->getSubscriberLastOrder();
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
                    $addressData["country"] = Mage::getModel('directory/country')->loadByCode($address->getCountry())->getName();
                }
            }
        }

        return $addressData;
    }

    /**
     * @param $errorMessage
     */
    protected function addError($errorMessage)
    {
        Mage::getSingleton('core/session')->addError($errorMessage);
    }

    /**
     * @param $customAtt
     * @param $customer
     * @param $mergeVars
     * @param $key
     * @param $helper
     * @param $subscriberEmail
     * @param $storeId
     * @return array
     */
    protected function customizedAttributes($customAtt, $customer, $mergeVars, $key, $helper, $subscriberEmail, $storeId)
    {
        switch ($customAtt) {
            case 'billing_company':
            case 'shipping_company':
                $address = $this->getAddressForCustomizedAttributes($customAtt, $customer);

                if ($address) {
                    $company = $address->getCompany();
                    if ($company) {
                        $mergeVars[$key] = $company;
                    }
                }
                break;
            case 'billing_telephone':
            case 'shipping_telephone':
                $address = $this->getAddressForCustomizedAttributes($customAtt, $customer);

                if ($address) {
                    $telephone = $address->getTelephone();
                    if ($telephone) {
                        $mergeVars[$key] = $telephone;
                    }
                }
                break;
            case 'billing_country':
            case 'shipping_country':
                $address = $this->getAddressForCustomizedAttributes($customAtt, $customer);

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
                $address = $this->getAddressForCustomizedAttributes($customAtt, $customer);

                if ($address) {
                    $zipCode = $address->getPostcode();
                    if ($zipCode) {
                        $mergeVars[$key] = $zipCode;
                    }
                }
                break;
            case 'billing_state':
            case 'shipping_state':
                $address = $this->getAddressForCustomizedAttributes($customAtt, $customer);

                if ($address) {
                    $state = $address->getRegion();
                    if ($state) {
                        $mergeVars[$key] = $state;
                    }
                }
                break;
            case 'dop':
                $dop = $this->getLastDateOfPurchase($subscriberEmail);
                if ($dop) {
                    $mergeVars[$key] = $dop;
                }
                break;
            case 'store_code':
                $storeCode = Mage::getModel('core/store')->load($storeId)->getCode();
                $mergeVars[$key] = $storeCode;
        }

        return $mergeVars;
    }

    /**
     * @param $subscriber
     * @param $attributeCode
     * @param $customer
     * @param $mergeVars
     * @param $key
     * @param $storeId
     * @param $attribute
     * @return array | returns an array of mergeFields.
     */
    protected function customerAttributes($subscriber, $attributeCode, $customer, $mergeVars, $key, $storeId, $attribute)
    {
        switch ($attributeCode) {
            case 'email':
                break;
            case 'default_billing':
            case 'default_shipping':
                $address = $customer->getPrimaryAddress($attributeCode);
                $addressData = $this->getAddressData($address);

                if (count($addressData)) {
                    $mergeVars[$key] = $addressData;
                }
                break;
            case 'gender':
                if ($this->getCustomerGroupLabel($attributeCode, $customer)) {
                    $genderValue = $this->getCustomerGroupLabel($attributeCode, $customer);
                    $mergeVars[$key] = $this->getGenderValue($mergeVars, $key, $genderValue);
                }
                break;
            case 'group_id':
                if ($this->getCustomerGroupLabel($attributeCode, $customer)) {
                    $group_id = (int)$this->getCustomerGroupLabel($attributeCode, $customer);
                    $customerGroup = Mage::helper('customer')->getGroups()->toOptionHash();
                    $mergeVars[$key] = $customerGroup[$group_id];
                } else {
                    $mergeVars[$key] = 'NOT LOGGED IN';
                }
                break;
            case 'firstname':
                $firstName = $this->getFirstName($subscriber, $customer);

                if ($firstName) {
                    $mergeVars[$key] = $firstName;
                }
                break;
            case 'lastname':
                $lastName = $this->getLastName($subscriber, $customer);

                if ($lastName) {
                    $mergeVars[$key] = $lastName;
                }
                break;
            case 'store_id':
                $mergeVars[$key] = $storeId;
                break;
            case 'website_id':
                $websiteId = $this->getWebSiteByStoreId($storeId);
                $mergeVars[$key] = $websiteId;
                break;
            case 'created_in':
                $storeName = Mage::getModel('core/store')->load($storeId)->getName();
                $mergeVars[$key] = $storeName;
                break;
            case 'dob':
                if ($this->getCustomerGroupLabel($attributeCode, $customer)) {
                    $mergeVars[$key] = $this->getDateOfBirth($attributeCode, $customer);
                }
                break;
            default:
                $mergeValue = $this->getUnknownMergeField($attributeCode, $customer, $attribute);
                if ($mergeValue !== null) {
                    $mergeVars[$key] = $mergeValue;
                }

                //$mergeVars[$key] = $this->getUnknownMergeField($attributeCode, $customer, $mergeVars, $key, $attribute);
                break;
        }

        return $mergeVars;
    }

    /**
     * Add possibility to change value on certain merge tag
     *
     * @param $customer
     * @param $subscriberEmail
     * @param $attributeCode
     * @param $eventValue
     */
    protected function dispatchMergeVarBefore($customer, $subscriberEmail, $attributeCode, &$eventValue)
    {
        Mage::dispatchEvent(
            'mailchimp_merge_field_send_before',
            array(
                'customer_id' => $customer->getId(),
                'subscriber_email' => $subscriberEmail,
                'merge_field_tag' => $attributeCode,
                'merge_field_value' => &$eventValue
            )
        );
    }

    /**
     * Allow possibility to add new vars in 'new_vars' array
     *
     * @param $subscriber
     * @param $mergeVars
     * @param $newVars
     */
    protected function dispatchEventMergeVarAfter($subscriber, $mergeVars, &$newVars)
    {
        Mage::dispatchEvent(
            'mailchimp_merge_field_send_after',
            array(
                'subscriber' => $subscriber,
                'vars' => $mergeVars,
                'new_vars' => &$newVars
            )
        );
    }

    /**
     * @param $storeId
     * @return Mage_Core_Model_Abstract
     */
    protected function getWebSiteByStoreId($storeId)
    {
        return Mage::getModel('core/store')->load($storeId)->getWebsiteId();
    }

    /**
     * @return Mage_Eav_Model_Resource_Attribute_Collection
     */
    protected function getEntityAttributeCollection()
    {
        return Mage::getResourceModel('eav/entity_attribute_collection');
    }

    /**
     * @return false|Mage_Customer_Model_Customer
     */
    protected function getCustomerByWebsiteAndId()
    {
        return Mage::getModel('customer/customer');
    }

    /**
     * @param $mageMCHelper
     */
    protected function setMailchimpHelper($mageMCHelper)
    {
        $this->mcHelper = $mageMCHelper;
    }

    /**
     * @return Ebizmarts_MailChimp_Helper_Data
     */
    protected function getMailchimpHelper()
    {
        return $this->mcHelper;
    }

    /**
     * @param $mapFields
     * @return array | returns an array of mapFields
     */
    protected function unserilizeMapFields($mapFields)
    {
        return unserialize($mapFields);
    }

    /**
     * @param $customAtt
     * @param $customer
     * @return array | returns an array with the address if it exists
     */
    protected function getAddressForCustomizedAttributes($customAtt, $customer)
    {
        $lastOrder = $this->getSubscriberLastOrder();
        $address = $this->getAddressFromLastOrder($lastOrder);
        if (!empty($address)) {
            $addr = explode('_', $customAtt);
            $address = $customer->getPrimaryAddress('default_' . $addr[0]);
        }

        return $address;
    }

    /**
     * @param $lastOrder
     * @return array | return an array with the address from the order if exist and the addressData is empty.
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
     * @param $itemId
     * @param $magentoStoreId
     * @return Mage_Newsletter_Model_Subscriber \ subcriberSyncDataItem newsletter/subscriber if exists.
     */
    protected function getSubscriberSyncDataItem($itemId, $magentoStoreId)
    {
        $subscriberSyndDataItem = null;
        $collection = Mage::getResourceModel('newsletter/subscriber_collection')
            ->addFieldToFilter('subscriber_id', array('eq' => $itemId))
            ->addFieldToFilter('store_id', array('eq' => $magentoStoreId))
            ->setCurPage(1)
            ->setPageSize(1);

        if ($collection->getSize()) {
            $subscriberSyndDataItem = $collection->getFirstItem();
        }

        return $subscriberSyndDataItem;
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
        $lastOrder = $this->getSubscriberLastOrder();
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
        $lastOrder = $this->getSubscriberLastOrder();
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
     * @param $attributeCode
     * @param $customer
     * @param $mergeVars
     * @param $key
     * @param $attribute
     * @return mixed
     */
    protected function getUnknownMergeField($attributeCode, $customer, $attribute)
    {
        $optionValue = null;

        $attrValue = $this->getCustomerGroupLabel($attributeCode, $customer);
        if ($attrValue!== null) {
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
     * @return string | returns the date of birth of the customer string format.
     */
    protected function getDateOfBirth($attributeCode, $customer)
    {
        return date("m/d", strtotime($this->getCustomerGroupLabel($attributeCode, $customer)));
    }

    /**
     * @param $helper
     * @param $subscriberEmail
     * @return mixed
     * @throws Mage_Core_Exception
     */
    protected function saveLastOrderInSession($subscriberEmail)
    {
        $lastOrder = $this->getLastOrderByEmail($subscriberEmail);
        if ($this->getSubscriberLastOrder()) {
            Mage::unregister('subscriber_last_order');
        }

        Mage::register('subscriber_last_order', $lastOrder);
        return $lastOrder;
    }

    /**
     * If orders with the given email exists, returns the date of the last order made.
     *
     * @param  $subscriberEmail
     * @return null
     */
    public function getLastDateOfPurchase($subscriberEmail)
    {
        $lastOrder = $this->getSubscriberLastOrder();
        $lastDateOfPurchase = null;
        if ($lastOrder === null) {
            $lastOrder = $this->getLastOrderByEmail($subscriberEmail);
        }

        if ($lastOrder !== null) {
            $lastDateOfPurchase = $lastOrder->getCreatedAt();
        }

        return $lastDateOfPurchase;
    }

    /**
     * @param $email
     * @return Mage_Sales_Model_Resource_Order_Collection | return the latest order made by the email passed by parameter if exists.
     *
     */
    public function getLastOrderByEmail($email)
    {
        $helper = $this->getMailchimpHelper();
        $orderCollection = $helper->getOrderCollectionByCustomerEmail($email);
        $lastOrder = null;
        if ($this->isNotEmptyOrderCollection($orderCollection)) {
            $lastOrder = $orderCollection->setOrder('created_at', 'DESC')->getFirstItem();
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
     * @return mixed
     */
    protected function getSubscriberLastOrder()
    {
        return Mage::registry('subscriber_last_order');
    }
}
