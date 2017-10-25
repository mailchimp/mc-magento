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

    private $mcHelper;

    public function __construct()
    {
        $this->mcHelper = Mage::helper('mailchimp');
    }

    public function createBatchJson($listId, $storeId, $limit)
    {
        $helper = $this->mcHelper;
        $thisScopeHasSubMinSyncDateFlag = $helper->getIfConfigExistsForScope(Ebizmarts_MailChimp_Model_Config::GENERAL_SUBMINSYNCDATEFLAG, $storeId);
        $thisScopeHasList = $helper->getIfConfigExistsForScope(Ebizmarts_MailChimp_Model_Config::GENERAL_LIST, $storeId);
        $moduleIsActive = $helper->isMailChimpEnabled($storeId);
        $subscriberArray = array();

        if ($moduleIsActive) {
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
                    $helper->logError($errorMessage, $storeId);
                }

                if (!empty($subscriberJson)) {
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
        }

        return $subscriberArray;
    }

    protected function _buildSubscriberData($subscriber)
    {
        $helper = $this->mcHelper;
        $storeId = $subscriber->getStoreId();
        $data = array();
        $data["email_address"] = $subscriber->getSubscriberEmail();
        $mergeVars = $this->getMergeVars($subscriber);
        if ($mergeVars) {
            $data["merge_fields"] = $mergeVars;
        }

        $status = $this->translateMagentoStatusToMailchimpStatus($subscriber->getStatus(), $storeId);
        $data["status_if_new"] = $status;
        if ($subscriber->getMailchimpSyncModified()) {
            $data["status"] = $status;
        }
        $data["language"] = $helper->getStoreLanguageCode($storeId);

        return $data;
    }

    public function getMergeVars($subscriber)
    {
        $helper = $this->mcHelper;
        $storeId = $subscriber->getStoreId();
        $mapFields = $helper->getMapFields($storeId);
        $maps = unserialize($mapFields);
        $websiteId = Mage::getModel('core/store')->load($storeId)->getWebsiteId();
        $attrSetId = Mage::getResourceModel('eav/entity_attribute_collection')
            ->setEntityTypeFilter(1)
            ->addSetInfo()
            ->getData();
        $mergeVars = array();
        $subscriberEmail = $subscriber->getSubscriberEmail();
        $customer = Mage::getModel('customer/customer')->setWebsiteId($websiteId)->load($subscriber->getCustomerId());

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
                            switch ($attributeCode) {
                                case 'email':
                                    break;
                                case 'default_billing':
                                case 'default_shipping':
                                    $address = $customer->getPrimaryAddress($attributeCode);
                                    $addressData = $this->getAddressData($address);
                                    if (count($addressData)) {
                                        $eventValue = $mergeVars[$key] = $addressData;
                                    }
                                    break;
                                case 'gender':
                                    if ($customer->getData($attributeCode)) {
                                        $genderValue = $customer->getData($attributeCode);
                                        if ($genderValue == 1) {
                                            $eventValue = $mergeVars[$key] = 'Male';
                                        } elseif ($genderValue == 2) {
                                            $eventValue = $mergeVars[$key] = 'Female';
                                        }
                                    }
                                    break;
                                case 'group_id':
                                    if ($customer->getData($attributeCode)) {
                                        $group_id = (int)$customer->getData($attributeCode);
                                        $customerGroup = Mage::helper('customer')->getGroups()->toOptionHash();
                                        $eventValue = $mergeVars[$key] = $customerGroup[$group_id];
                                    } else {
                                        $eventValue = $mergeVars[$key] = 'NOT LOGGED IN';
                                    }
                                    break;
                                case 'firstname':
                                    $firstName = $customer->getFirstname();

                                    if (!$firstName) {
                                        $firstName = $subscriber->getSubscriberFirstname();
                                    }

                                    if ($firstName) {
                                        $eventValue = $mergeVars[$key] = $firstName;
                                    }
                                    break;
                                case 'lastname':
                                    $lastName = $customer->getLastname();

                                    if (!$lastName) {
                                        $lastName = $subscriber->getSubscriberLastname();
                                    }

                                    if ($lastName) {
                                        $eventValue = $mergeVars[$key] = $lastName;
                                    }
                                    break;
                                case 'store_id':
                                    $eventValue = $mergeVars[$key] = $storeId;
                                    break;
                                case 'website_id':
                                    $websiteId = Mage::getModel('core/store')->load($storeId)->getWebsiteId();
                                    $eventValue = $mergeVars[$key] = $websiteId;
                                    break;
                                case 'created_in':
                                    if ($customer->getData($attributeCode)) {
                                        $eventValue = $mergeVars[$key] = $customer->getData($attributeCode);
                                    } else {
                                        $storeCode = Mage::getModel('core/store')->load($storeId)->getCode();
                                        $eventValue = $mergeVars[$key] = $storeCode;
                                    }
                                    break;
                                case 'dob':
                                    if ($customer->getData($attributeCode)) {
                                        $eventValue = $mergeVars[$key] = date("m/d", strtotime($customer->getData($attributeCode)));
                                    }
                                    break;
                                default:
                                    if ($customer->getData($attributeCode)) {
                                        $eventValue = $mergeVars[$key] = $customer->getData($attributeCode);
                                    }
                                    break;
                            }

                            Mage::dispatchEvent(
                                'mailchimp_merge_field_send_before', array(
                                    'customer_id' => $customer->getId(),
                                    'subscriber_email' => $subscriberEmail,
                                    'merge_field_tag' => $attributeCode,
                                    'merge_field_value' => &$eventValue
                                )
                            );
                        }
                    }
                } else {
                    switch ($customAtt) {
                        case 'billing_company':
                        case 'shipping_company':
                            $addr = explode('_', $customAtt);
                            $address = $customer->getPrimaryAddress('default_' . $addr[0]);

                            if ($address) {
                                $company = $address->getCompany();
                                if ($company) {
                                    $eventValue = $mergeVars[$key] = $company;
                                }
                            }
                            break;
                        case 'billing_telephone':
                        case 'shipping_telephone':
                            $addr = explode('_', $customAtt);
                            $address = $customer->getPrimaryAddress('default_' . $addr[0]);

                            if ($address) {
                                $telephone = $address->getTelephone();
                                if ($telephone) {
                                    $eventValue = $mergeVars[$key] = $telephone;
                                }
                            }
                            break;
                        case 'billing_country':
                        case 'shipping_country':
                            $addr = explode('_', $customAtt);
                            $address = $customer->getPrimaryAddress('default_' . $addr[0]);

                            if ($address) {
                                $countryCode = $address->getCountry();
                                if ($countryCode) {
                                    $countryName = Mage::getModel('directory/country')->loadByCode($countryCode)->getName();
                                    $eventValue = $mergeVars[$key] = $countryName;
                                }
                            }
                            break;
                        case 'billing_zipcode':
                        case 'shipping_zipcode':
                            $addr = explode('_', $customAtt);
                            $address = $customer->getPrimaryAddress('default_' . $addr[0]);

                            if ($address) {
                                $zipCode = $address->getPostcode();
                                if ($zipCode) {
                                    $eventValue = $mergeVars[$key] = $zipCode;
                                }
                            }
                            break;
                        case 'dop':
                            $dop = $helper->getLastDateOfPurchase($subscriberEmail);
                            if ($dop) {
                                $eventValue = $mergeVars[$key] = $dop;
                            }
                            break;
                    }

                    Mage::dispatchEvent(
                        'mailchimp_merge_field_send_before', array(
                            'customer_id' => $customer->getId(),
                            'subscriber_email' => $subscriberEmail,
                            'merge_field_tag' => $customAtt,
                            'merge_field_value' => &$eventValue
                        )
                    );
                }

                if ($eventValue) {
                    $mergeVars[$key] = $eventValue;
                }
            }
        }

        $newVars = new Varien_Object;

        Mage::dispatchEvent(
            'mailchimp_merge_field_send_after', array(
                'subscriber' => $subscriber,
                'vars' => $mergeVars,
                'new_vars' => $newVars
            )
        );

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
        $isAdmin = Mage::app()->getStore()->isAdmin();
        $helper = $this->mcHelper;
        $storeId = $subscriber->getStoreId();
        $listId = $helper->getGeneralList($storeId);
        $newStatus = $this->translateMagentoStatusToMailchimpStatus($subscriber->getStatus(), $storeId);
        $forceStatus = ($updateStatus) ? $newStatus : null;
        $api = $helper->getApi($storeId);
        $mergeVars = $this->getMergeVars($subscriber);
        $md5HashEmail = md5(strtolower($subscriber->getSubscriberEmail()));
        try {
            $api->lists->members->addOrUpdate(
                $listId, $md5HashEmail, $subscriber->getSubscriberEmail(), $newStatus, null, $forceStatus, $mergeVars,
                null, null, null, null
            );
            $subscriber->setData("mailchimp_sync_delta", Varien_Date::now());
            $subscriber->setData("mailchimp_sync_error", "");
            $subscriber->setData("mailchimp_sync_modified", 0);
        } catch (MailChimp_Error $e) {
            if ($newStatus === 'subscribed' && $subscriber->getIsStatusChanged()) {
                if (strstr($e->getMailchimpDetails(), 'is in a compliance state')) {
                    try {
                        $api->lists->members->update($listId, $md5HashEmail, null, 'pending', $mergeVars);
                        $subscriber->setSubscriberStatus(Mage_Newsletter_Model_Subscriber::STATUS_UNCONFIRMED);
                        $message = $helper->__('To begin receiving the newsletter, you must first confirm your subscription');
                        Mage::getSingleton('core/session')->addWarning($message);
                    } catch (MailChimp_Error $e) {
                        $helper->logError($e->getFriendlyMessage(), $storeId);
                        $this->addError($isAdmin, $e);
                        $subscriber->unsubscribe();
                    } catch (Exception $e) {
                        $helper->logError($e->getMessage(), $storeId);
                    }
                } else {
                    $helper->logError($e->getFriendlyMessage(), $storeId);
                    $this->addError($isAdmin, $e);
                    $subscriber->unsubscribe();
                }
            } else {
                $helper->logError($e->getFriendlyMessage(), $storeId);
                $this->addError($isAdmin, $e);
            }
        } catch (Exception $e) {
            $helper->logError($e->getMessage(), $storeId);
        }
    }

    /**
    * @param $e
    * @param $storeId
    */
    protected function _processUpdateSubscriberError($e, $storeId)
    {
        $message = $e->getFriendlyMessage();
        Mage::helper('mailchimp')->logError($message, $storeId);
        if (Mage::getDesign()->getArea() === 'frontend') {
            $message = Mage::helper('mailchimp')->__('Please, try again later');
        }
        Mage::getSingleton('core/session')->addError($message);
    }

    /**
     * @param $status
     * @param $storeId
     * @return string
     */
    public function translateMagentoStatusToMailchimpStatus($status, $storeId)
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

    public function removeSubscriber($subscriber)
    {
        $helper = $this->mcHelper;
        $storeId = $subscriber->getStoreId();
        $listId = $helper->getGeneralList($storeId);
        $api = $helper->getApi($storeId);
        try {
            $md5HashEmail = md5(strtolower($subscriber->getSubscriberEmail()));
            $api->lists->members->update($listId, $md5HashEmail, null, 'unsubscribed');
        } catch (MailChimp_Error $e) {
            $helper->logError($e->getFriendlyMessage(), $storeId);
            Mage::getSingleton('adminhtml/session')->addError($e->getFriendlyMessage());
        } catch (Exception $e) {
            $helper->logError($e->getMessage(), $storeId);
        }
    }

    /**
     * @param $subscriber
     */
    public function deleteSubscriber($subscriber)
    {
        $helper = $this->mcHelper;
        $storeId = $subscriber->getStoreId();
        $listId = $helper->getGeneralList($storeId);
        $api = $helper->getApi($storeId);
        try {
            $md5HashEmail = md5(strtolower($subscriber->getSubscriberEmail()));
            $api->lists->members->update($listId, $md5HashEmail, null, 'unsubscribed');
        } catch (MailChimp_Error $e) {
            $helper->logError($e->getFriendlyMessage(), $storeId);
            Mage::getSingleton('adminhtml/session')->addError($e->getFriendlyMessage());
        } catch (Exception $e) {
            $helper->logError($e->getMessage(), $storeId);
        }
    }

    public function update($emailAddress, $storeId)
    {
        $helper = $this->mcHelper;
        $subscriber = Mage::getSingleton('newsletter/subscriber')->loadByEmail($emailAddress);
        if ($subscriber->getStatus() == Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED && $subscriber->getMailchimpSyncDelta() != $helper->getSubMinSyncDateFlag($storeId)) {
            $subscriber->setMailchimpSyncModified(1)
                ->save();
        }
    }

    /**
     * @param $address
     * @return array
     */
    protected function getAddressData($address)
    {
        $addressData = array();
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
        return $addressData;
    }

    /**
     * @param $isAdmin
     * @param $e
     */
    protected function addError($isAdmin, $e)
    {
        if ($isAdmin) {
            Mage::getSingleton('core/session')->addError($e->getFriendlyMessage());
        }
    }
}
