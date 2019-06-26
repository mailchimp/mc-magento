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
    private $_storeId;

    public function __construct()
    {
        $mageMCHelper = Mage::helper('mailchimp');
        $this->setMailchimpHelper($mageMCHelper);
    }

    /**
     * @param $storeId
     */
    protected function setStoreId($storeId)
    {
        $this->_storeId = $storeId;
    }

    /**
     * @return mixed
     */
    public function getStoreId()
    {
        return $this->_storeId;
    }

    public function createBatchJson($listId, $storeId, $limit)
    {
        $this->setStoreId($storeId);
        $helper = $this->getMailchimpHelper();
        $thisScopeHasSubMinSyncDateFlag = $helper->getIfConfigExistsForScope(Ebizmarts_MailChimp_Model_Config::GENERAL_SUBMINSYNCDATEFLAG, $this->getStoreId());
        $thisScopeHasList = $helper->getIfConfigExistsForScope(Ebizmarts_MailChimp_Model_Config::GENERAL_LIST, $this->getStoreId());

        $subscriberArray = array();
        if ($thisScopeHasList && !$thisScopeHasSubMinSyncDateFlag || !$helper->getSubMinSyncDateFlag($this->getStoreId())) {
            $realScope = $helper->getRealScopeForConfig(Ebizmarts_MailChimp_Model_Config::GENERAL_LIST, $this->getStoreId());
            $configValues = array(array(Ebizmarts_MailChimp_Model_Config::GENERAL_SUBMINSYNCDATEFLAG, Varien_Date::now()));
            $helper->saveMailchimpConfig($configValues, $realScope['scope_id'], $realScope['scope']);
        }

        //get subscribers
        $collection = Mage::getResourceModel('newsletter/subscriber_collection')
            ->addFieldToFilter('subscriber_status', array('eq' => 1))
            ->addFieldToFilter('store_id', array('eq' => $this->getStoreId()))
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
                    array('lt' => $helper->getSubMinSyncDateFlag($this->getStoreId())),
                    array('eq' => 1)
                )
            );
        $collection->addFieldToFilter('mailchimp_sync_error', array('eq' => ''));
        $collection->getSelect()->limit($limit);
        $date = $helper->getDateMicrotime();
        $batchId = 'storeid-'.$this->getStoreId(). '_' .Ebizmarts_MailChimp_Model_Config::IS_SUBSCRIBER . '_'.$date;

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

        $mailChimpTags = Mage::getModel('mailchimp/api_subscribers_mailchimpTags');
        $mailChimpTags->setMailChimpHelper($helper);
        $mailChimpTags->setStoreId($storeId);
        $mailChimpTags->setSubscriber($subscriber);
        $mailChimpTags->setCustomer(
            $this->getCustomerByWebsiteAndId()
            ->setWebsiteId($this->getWebSiteByStoreId($storeId))->load($subscriber->getCustomerId())
        );
        $mailChimpTags->buildMailChimpTags();

        if ($mailChimpTags->getMailchimpTags()) {
            $data["merge_fields"] = $mailChimpTags->getMailchimpTags();
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

            $mailChimpTags = Mage::getModel('mailchimp/api_subscribers_MailchimpTags');
            $mailChimpTags->setMailChimpHelper($helper);
            $mailChimpTags->setStoreId($storeId);
            $mailChimpTags->setSubscriber($subscriber);
            $mailChimpTags->setCustomer(
                $this->getCustomerByWebsiteAndId()->
                setWebsiteId($this->getWebSiteByStoreId($storeId))->load($subscriber->getCustomerId())
            );

            $mailChimpTags->buildMailChimpTags();


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
                    $mailChimpTags->getMailchimpTags(),
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
                if ($newStatus === 'subscribed' && $subscriber->getIsStatusChanged()
                    && !$helper->isSubscriptionConfirmationEnabled($storeId)) {
                    if (strstr($e->getMailchimpDetails(), 'is in a compliance state')) {
                        try {
                            $api->lists->members->update(
                                $listId, $md5HashEmail, null, 'pending', $mailChimpTags->getMailchimpTags(), $interest
                            );
                            $subscriber->setSubscriberStatus(Mage_Newsletter_Model_Subscriber::STATUS_NOT_ACTIVE);
                            $saveSubscriber = true;
                            $message =
                            $helper->__('To begin receiving the newsletter, you must first confirm your subscription');
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

    public function update($emailAddress)
    {
        $subscriber = Mage::getSingleton('newsletter/subscriber')->loadByEmail($emailAddress);
        if ($subscriber->getId()) {
            $subscriber->setMailchimpSyncModified(1)
                ->save();
        }
    }


    /**
     * @param $errorMessage
     */
    protected function addError($errorMessage)
    {
        Mage::getSingleton('core/session')->addError($errorMessage);
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





}
