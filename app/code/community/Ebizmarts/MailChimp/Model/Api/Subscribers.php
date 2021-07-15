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

    /**
     * Ebizmarts_MailChimp_Helper_Data
     */
    protected $_mcHelper;
    protected $_mcDateHelper;
    protected $_storeId;

    /**
     * @var $_ecommerceSubscribersCollection Ebizmarts_MailChimp_Model_Resource_Ecommercesyncdata_Subscribers_Collection
     */
    protected $_ecommerceSubscribersCollection;

    public function __construct()
    {
        $mageMCHelper = Mage::helper('mailchimp');
        $this->setMailchimpHelper($mageMCHelper);
        $mageMCDateHelper = Mage::helper('mailchimp/date');
        $this->setMailchimpDateHelper($mageMCDateHelper);
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

    /**
     * @param $listId
     * @param $storeId
     * @param $limit
     * @return array
     * @throws Mage_Core_Exception
     * @throws Mage_Core_Model_Store_Exception
     */
    public function createBatchJson($listId, $storeId, $limit)
    {
        $this->setStoreId($storeId);
        $helper = $this->getMailchimpHelper();
        $dateHelper = $this->getMailchimpDateHelper();
        $thisScopeHasSubMinSyncDateFlag = $helper->getIfConfigExistsForScope(
            Ebizmarts_MailChimp_Model_Config::GENERAL_SUBMINSYNCDATEFLAG,
            $this->getStoreId()
        );
        $thisScopeHasList = $helper->getIfConfigExistsForScope(
            Ebizmarts_MailChimp_Model_Config::GENERAL_LIST,
            $this->getStoreId()
        );

        $this->_ecommerceSubscribersCollection = $this->getResourceCollection();
        $this->_ecommerceSubscribersCollection->setStoreId($this->getStoreId());

        $subscriberArray = array();

        if ($thisScopeHasList && !$thisScopeHasSubMinSyncDateFlag
            || !$helper->getSubMinSyncDateFlag($this->getStoreId())
        ) {
            $realScope = $helper->getRealScopeForConfig(
                Ebizmarts_MailChimp_Model_Config::GENERAL_LIST,
                $this->getStoreId()
            );
            $configValues = array(
                array(
                    Ebizmarts_MailChimp_Model_Config::GENERAL_SUBMINSYNCDATEFLAG,
                    $this->_mcDateHelper->formatDate(null, 'Y-m-d H:i:s')
                )
            );
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
        $this->_ecommerceSubscribersCollection->limitCollection($collection, $limit);
        $date = $dateHelper->getDateMicrotime();
        $batchId = 'storeid-' . $this->getStoreId() . '_'
            . Ebizmarts_MailChimp_Model_Config::IS_SUBSCRIBER . '_' . $date;
        $counter = 0;

        foreach ($collection as $subscriber) {
            $data = $this->_buildSubscriberData($subscriber);
            $emailHash = hash('md5', strtolower($subscriber->getSubscriberEmail()));

            //encode to JSON
            $subscriberJson = json_encode($data);

            if ($subscriberJson !== false) {
                if (!empty($subscriberJson)) {
                    if ($subscriber->getMailchimpSyncModified()) {
                        $helper->modifyCounterSubscribers(Ebizmarts_MailChimp_Helper_Data::SUB_MOD);
                    } else {
                        $helper->modifyCounterSubscribers(Ebizmarts_MailChimp_Helper_Data::SUB_NEW);
                    }

                    $subscriberArray[$counter]['method'] = "PUT";
                    $subscriberArray[$counter]['path'] = "/lists/" . $listId . "/members/" . $emailHash;
                    $subscriberArray[$counter]['operation_id'] = $batchId . '_' . $subscriber->getSubscriberId();
                    $subscriberArray[$counter]['body'] = $subscriberJson;

                    $this->_saveSubscriber(
                        $subscriber,
                        '',
                        $this->_mcDateHelper->formatDate(null, 'Y-m-d H:i:s'),
                        true
                    );
                }
            } else {
                //json encode failed
                $jsonErrorMsg = json_last_error_msg();
                $errorMessage = "Subscriber " . $subscriber->getSubscriberId()
                    . " json encode failed (" . $jsonErrorMsg . ")";
                $helper->logError($errorMessage);

                $this->_saveSubscriber($subscriber, $jsonErrorMsg);
            }

            $counter++;
        }

        return $subscriberArray;
    }

    /**
     * @param $subscriber
     * @param $error
     * @param null $syncDelta
     * @param bool $setSource
     */
    protected function _saveSubscriber($subscriber, $error, $syncDelta = null, $setSource = false)
    {
        if ($setSource) {
            $subscriber->setSubscriberSource(Ebizmarts_MailChimp_Model_Subscriber::MAILCHIMP_SUBSCRIBE);
        }

        $subscriber->setData("mailchimp_sync_delta", $syncDelta);
        $subscriber->setData("mailchimp_sync_error", $error);
        $subscriber->setData("mailchimp_sync_modified", 0);
        $subscriber->save();
    }

    /**
     * @param $subscriber
     * @return array
     * @throws Mage_Core_Exception
     * @throws Mage_Core_Model_Store_Exception
     */
    protected function _buildSubscriberData($subscriber)
    {
        $helper = $this->getMailchimpHelper();
        $storeId = $subscriber->getStoreId();
        $data = array();
        $data["email_address"] = $subscriber->getSubscriberEmail();

        $mailChimpTags = $this->_buildMailchimpTags($subscriber, $storeId);

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

        if (!empty($interest)) {
            $data['interests'] = $interest;
        }

        return $data;
    }

    /**
     * @param $subscriber
     * @return array
     * @throws Mage_Core_Exception
     * @throws MailChimp_Error
     */
    protected function _getInterest($subscriber)
    {
        $storeId = $subscriber->getStoreId();
        $rc = array();
        $helper = $this->getMailchimpHelper();
        $interestsAvailable = $helper->getInterest($storeId);
        $interest = $helper->getInterestGroups(null, $subscriber->getSubscriberId(), $storeId, $interestsAvailable);

        foreach ($interest as $i) {
            if (array_key_exists('category', $i)) {
                foreach ($i['category'] as $key => $value) {
                    $rc[$value['id']] = $value['checked'];
                }
            }
        }

        return $rc;
    }

    /**
     * @param       $subscriber
     * @param bool  $updateStatus If set to true, it will force the status update even for those already subscribed.
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

            $mailChimpTags = $this->_buildMailchimpTags($subscriber, $storeId);
            $language = $helper->getStoreLanguageCode($storeId);
            $interest = $this->_getInterest($subscriber);
            $emailHash = hash('md5', strtolower($subscriber->getSubscriberEmail()));

            try {
                $api->lists->members->addOrUpdate(
                    $listId,
                    $emailHash,
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
                $subscriber->setData("mailchimp_sync_delta", $this->_mcDateHelper->formatDate(null, 'Y-m-d H:i:s'));
                $subscriber->setData("mailchimp_sync_error", "");
                $subscriber->setData("mailchimp_sync_modified", 0);
                $saveSubscriber = true;
            } catch (MailChimp_Error $e) {
                if ($this->isSubscribed($newStatus) && $subscriber->getIsStatusChanged()
                    && !$helper->isSubscriptionConfirmationEnabled($storeId)
                ) {
                    if (strstr($e->getMailchimpDetails(), 'is in a compliance state')) {
                        try {
                            $this->_catchMailchimpNewstellerConfirm(
                                $api, $listId, $emailHash, $mailChimpTags, $subscriber, $interest
                            );
                            $saveSubscriber = true;
                        } catch (MailChimp_Error $e) {
                            $this->_catchMailchimpException($e, $subscriber, $isAdmin);
                            $saveSubscriber = true;
                        } catch (Exception $e) {
                            $helper->logError($e->getMessage());
                        }
                    } else {
                        $this->_catchMailchimpSubsNotAppliedIf($e, $isAdmin, $subscriber);
                        $saveSubscriber = true;
                    }
                } else {
                    $this->_catchMailchimpSubsNotAppliedElse($e, $isAdmin, $subscriber);
                }
            } catch (Exception $e) {
                $helper->logError($e->getMessage());
            }

            if ($saveSubscriber) {
                $subscriber->setSubscriberSource(Ebizmarts_MailChimp_Model_Subscriber::MAILCHIMP_SUBSCRIBE);
                $subscriber->save();
            }
        }
    }

    /**
     * @param $e
     * @param $isAdmin
     * @param $subscriber
     */
    protected function _catchMailchimpSubsNotAppliedIf($e, $isAdmin, $subscriber)
    {
        $helper = $this->getMailchimpHelper();
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

    /**
     * @param $e
     * @param $isAdmin
     * @param $subscriber
     */
    protected function _catchMailchimpSubsNotAppliedElse($e, $isAdmin, $subscriber)
    {
        $helper = $this->getMailchimpHelper();
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

    /**
     * @param $api
     * @param $listId
     * @param $emailHash
     * @param $mailChimpTags
     * @param $subscriber
     * @param $interest
     */
    protected function _catchMailchimpNewstellerConfirm(
        $api,
        $listId,
        $emailHash,
        $mailChimpTags,
        $subscriber,
        $interest
    ) {
        $helper = $this->getMailchimpHelper();
        $api->getLists()->getMembers()->update(
            $listId, $emailHash, null, 'pending', $mailChimpTags->getMailchimpTags(), $interest
        );
        $subscriber->setSubscriberStatus(Mage_Newsletter_Model_Subscriber::STATUS_NOT_ACTIVE);
        $message = $helper->__(
            'To begin receiving the newsletter, you must first confirm your subscription'
        );
        Mage::getSingleton('core/session')->addWarning($message);
    }

    /**
     * @param $e
     * @param $subscriber
     * @param $isAdmin
     */
    protected function _catchMailchimpException($e, $subscriber, $isAdmin)
    {
        $helper = $this->getMailchimpHelper();
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
     * @throws Mage_Core_Exception
     */
    public function deleteSubscriber($subscriber)
    {
        $helper = $this->getMailchimpHelper();
        $storeId = $subscriber->getStoreId();
        $listId = $helper->getGeneralList($storeId);

        try {
            $api = $helper->getApi($storeId);
            $emailHash = hash('md5', strtolower($subscriber->getSubscriberEmail()));
            $api->getLists()->getMembers()->update($listId, $emailHash, null, 'unsubscribed');
        } catch (Ebizmarts_MailChimp_Helper_Data_ApiKeyException $e) {
            $helper->logError($e->getMessage());
        } catch (MailChimp_Error $e) {
            $helper->logError($e->getFriendlyMessage());
            Mage::getSingleton('adminhtml/session')->addError($e->getFriendlyMessage());
        } catch (Exception $e) {
            $helper->logError($e->getMessage());
        }
    }

    /**
     * @param $emailAddress
     */
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
     * @param $mageMCHelper
     */
    public function setMailchimpHelper($mageMCHelper)
    {
        $this->_mcHelper = $mageMCHelper;
    }

    /**
     * @return Ebizmarts_MailChimp_Helper_Data
     */
    protected function getMailchimpHelper()
    {
        return $this->_mcHelper;
    }

    /**
     * @param $mageMCDateHelper
     */
    public function setMailchimpDateHelper($mageMCDateHelper)
    {
        $this->_mcDateHelper = $mageMCDateHelper;
    }

    /**
     * @return Ebizmarts_MailChimp_Helper_Date
     */
    protected function getMailchimpDateHelper()
    {
        return $this->_mcDateHelper;
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
        $subscriberSyncDataItem = null;
        $collection = Mage::getResourceModel('newsletter/subscriber_collection')
            ->addFieldToFilter('subscriber_id', array('eq' => $itemId))
            ->addFieldToFilter('store_id', array('eq' => $magentoStoreId))
            ->setCurPage(1)
            ->setPageSize(1);

        if ($collection->getSize()) {
            $subscriberSyncDataItem = $collection->getLastItem();
        }

        return $subscriberSyncDataItem;
    }

    /**
     * @param $status
     * @return bool
     */
    protected function isSubscribed($status)
    {
        if ($status === Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED) {
            return true;
        }
    }

    /**
     * @param $subscriber
     * @param $storeId
     * @return false|Mage_Core_Model_Abstract
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
     * @return Ebizmarts_MailChimp_Model_Resource_Ecommercesyncdata_Subscribers_Collection
     */
    public function getResourceCollection()
    {
        /**
         * @var $collection Ebizmarts_MailChimp_Model_Resource_Ecommercesyncdata_Subscribers_Collection
         */
        $collection = Mage::getResourceModel('mailchimp/ecommercesyncdata_subscribers_collection');

        return $collection;
    }

}
