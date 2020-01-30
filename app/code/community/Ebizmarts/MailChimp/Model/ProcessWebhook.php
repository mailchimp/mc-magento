<?php

/**
 * MailChimp For Magento
 *
 * @category  Ebizmarts_MailChimp
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     5/19/16 3:55 PM
 * @file:     ProdcessWebhook.php
 */
class Ebizmarts_MailChimp_Model_ProcessWebhook
{
    const BATCH_LIMIT = 200;

    /**
     * @var Ebizmarts_MailChimp_Helper_Data
     */
    protected $_helper;
    protected $_dateHelper;
    protected $_groups = array();

    /**
     * Webhooks request url path
     *
     * @const string
     */

    const WEBHOOKS_PATH = 'mailchimp/webhook/index/';

    public function __construct()
    {
        $this->_helper = Mage::helper('mailchimp');
        $this->_dateHelper = Mage::helper('mailchimp/date');

        $this->_loadGroups();
    }

    public function saveWebhookRequest(array $data)
    {
        Mage::getModel('mailchimp/webhookrequest')
            ->setType($data['type'])
            ->setFiredAt($data['fired_at'])
            ->setDataRequest($this->_helper->serialize($data['data']))
            ->save();
    }

    /**
     * Process Webhook request
     *
     * @return void
     */
    public function processWebhookData()
    {
        $collection = Mage::getModel('mailchimp/webhookrequest')->getCollection();
        $collection->addFieldToFilter('processed', array('eq' => 0));
        $collection->getSelect()->limit(self::BATCH_LIMIT);

        foreach ($collection as $webhookRequest) {
            $data = $this->_helper->unserialize($webhookRequest->getDataRequest());

            if ($data) {
                switch ($webhookRequest->getType()) {
                    case 'subscribe':
                        $this->_subscribe($data);
                        break;
                    case 'unsubscribe':
                        $this->_unsubscribe($data);
                        break;
                    case 'cleaned':
                        $this->_clean($data);
                        break;
                    case 'upemail':
                        $this->_updateEmail($data);
                        break;
                    case 'profile':
                        $this->_profile($data);
                }
            }

            $this->_saveProcessedWebhook($webhookRequest);
        }
    }

    /**
     * @param $webhookRequest
     */
    protected function _saveProcessedWebhook($webhookRequest)
    {
        $webhookRequest->setProcessed(1)->save();
    }

    /**
     * Update customer email <upemail>
     *
     * @param array $data
     * @return void
     */
    protected function _updateEmail(array $data)
    {
        $helper = $this->getHelper();
        $listId = $data['list_id'];
        $old = $data['old_email'];
        $new = $data['new_email'];

        $oldSubscriber = $helper->loadListSubscriber($listId, $old);
        $newSubscriber = $helper->loadListSubscriber($listId, $new);

        if ($oldSubscriber) {
            if (!$newSubscriber->getId()) {
                if ($oldSubscriber->getId()) {
                    $oldSubscriber->setSubscriberEmail($new);
                    $oldSubscriber->setSubscriberSource(Ebizmarts_MailChimp_Model_Subscriber::MAILCHIMP_SUBSCRIBE);
                    $oldSubscriber->save();
                } else {
                    $helper->subscribeMember($newSubscriber);
                }
            }
        }
    }

    /**
     * Add "Cleaned Emails" notification to Adminnotification Inbox <cleaned>
     *
     * @param array $data
     * @return void
     */
    protected function _clean(array $data)
    {
        //Delete subscriber from Magento
        $helper = $this->getHelper();
        $s = $helper->loadListSubscriber($data['list_id'], $data['email']);

        if ($s && $s->getId()) {
            try {
                $s->delete();
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
    }

    /**
     * Subscribe email to Magento list, store aware
     *
     * @param array $data
     * @return void
     */
    protected function _subscribe(array $data)
    {
        $helper = $this->getHelper();
        try {
            $listId = $data['list_id'];
            $email = $data['email'];
            $subscriber = $helper->loadListSubscriber($listId, $email);
            if ($subscriber) {
                if ($subscriber->getId()) {
                    if ($subscriber->getSubscriberStatus() != Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED) {
                        $helper->subscribeMember($subscriber);
                    }
                } else {
                    if (isset($data['merges']['FNAME'])) {
                        $subscriberFname = filter_var($data['merges']['FNAME'], FILTER_SANITIZE_STRING);
                        $subscriber->setSubscriberFirstname($subscriberFname);
                    }

                    if (isset($data['merges']['LNAME'])) {
                        $subscriberLname = filter_var($data['merges']['LNAME'], FILTER_SANITIZE_STRING);
                        $subscriber->setSubscriberLastname($subscriberLname);
                    }

                    $helper->subscribeMember($subscriber);

                    if (isset($data['merges']['GROUPINGS'])) {
                        $this->_processGroupsData($data['merges']['GROUPINGS'], $subscriber, true);
                    }
                }
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * Unsubscribe or delete email from Magento list, store aware
     *
     * @param array $data
     * @return void
     */
    protected function _unsubscribe(array $data)
    {
        $helper = $this->getHelper();
        $subscriber = $helper->loadListSubscriber($data['list_id'], $data['email']);
        if ($subscriber && $subscriber->getId()) {
            try {
                $action = isset($data['action']) ? $data['action'] : 'delete';
                $subscriberStatus = $subscriber->getSubscriberStatus();
                $statusUnsubscribed = Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED;

                switch ($action) {
                case 'delete':
                    //if config setting "Webhooks Delete action" is set as "Delete customer account"

                    if (Mage::getStoreConfig(
                        Ebizmarts_MailChimp_Model_Config::GENERAL_UNSUBSCRIBE, $subscriber->getStoreId()
                    )
                    ) {
                        $subscriber->delete();
                    } elseif ($subscriberStatus != $statusUnsubscribed) {
                        $helper->unsubscribeMember($subscriber);
                    }
                    break;
                case 'unsub':
                    if ($subscriberStatus != $statusUnsubscribed) {
                        $helper->unsubscribeMember($subscriber);
                    }
                    break;
                }
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
    }

    /**
     * @param array $data
     * @throws Mage_Core_Exception
     */
    public function _profile(array $data)
    {
        $helper = $this->getHelper();
        $listId = $data['list_id'];
        $email = $data['email'];
        $fname = isset($data['merges']['FNAME']) ? $data['merges']['FNAME'] : null;
        $lname = isset($data['merges']['LNAME']) ? $data['merges']['LNAME'] : null;
        $customer = $helper->loadListCustomer($listId, $email);

        $saveRequired = false;
        if ($customer) {
            if ($fname && $fname !== $customer->getFirstname()) {
                $customer->setFirstname($fname);
                $saveRequired = true;
            }

            if ($lname && $lname !== $customer->getLastname()) {
                $customer->setLastname($lname);
                $saveRequired = true;
            }

            if ($saveRequired) {
                $customer->save();
            }

            if (isset($data['merges']['GROUPINGS'])) {
                $this->_processGroupsData($data['merges']['GROUPINGS'], $customer);
            }
        } else {
            $subscriber = $helper->loadListSubscriber($listId, $email);
            if ($subscriber) {
                if ($subscriber->getId()) {
                    if ($fname && $fname !== $subscriber->getSubscriberFirstname()) {
                        $subscriber->setSubscriberFirstname($fname);
                        $saveRequired = true;
                    }

                    if ($lname && $lname !== $subscriber->getSubscriberLastname()) {
                        $subscriber->setSubscriberLastname($lname);
                        $saveRequired = true;
                    }

                    if ($saveRequired) {
                        $subscriber->setSubscriberSource(Ebizmarts_MailChimp_Model_Subscriber::MAILCHIMP_SUBSCRIBE);
                        $subscriber->save();
                    }
                } else {
                    /**
                     * Mailchimp subscriber not currently in magento newsletter subscribers.
                     * Get mailchimp subscriber status and add missing newsletter subscriber.
                     */
                    $this->_addSubscriberData($subscriber, $fname, $lname, $email, $listId);
                }

                if (isset($data['merges']['GROUPINGS'])) {
                    $this->_processGroupsData($data['merges']['GROUPINGS'], $subscriber, true);
                }
            }
        }
    }

    public function _processGroupsData($grouping, $customerData, $isDataSubscriber = false)
    {
        $helper = $this->getHelper();
        $dateHelper = $this->getDateHelper();
        $storeId = $this->_getStoreId();

        $customerEmail = $customerData->getEmail();
        $subscriber = $this->getSubscriberModel()->loadByEmail($customerEmail);

        if (!$isDataSubscriber) {
            $customerId = $customerData->getId();
        } else {
            $customerId = $customerData->getCustomerId();
        }

        $interestGroup = $this->getInterestGroupModel();

        $subscriberId = $subscriber->getSubscriberId();
        $interestGroup->getByRelatedIdStoreId($customerId, $subscriberId, $storeId);
        $encodedGroups = $helper->arrayEncode($grouping);

        $interestGroup->setGroupdata($encodedGroups);
        $interestGroup->setSubscriberId($subscriberId);
        $interestGroup->setCustomerId($customerId);
        $interestGroup->setStoreId($storeId);
        $interestGroup->setUpdatedAt($dateHelper->getCurrentDateTime());
        $interestGroup->save();
    }

    /**
     * @param $subscriber
     * @param $fname
     * @param $lname
     * @param $email
     * @param $listId
     * @throws Exception
     */
    protected function _addSubscriberData($subscriber, $fname, $lname, $email, $listId)
    {
        $helper = $this->getHelper();
        $scopeArray = $helper->getFirstScopeFromConfig(
            Ebizmarts_MailChimp_Model_Config::GENERAL_LIST,
            $listId
        );
        $api = $helper->getApi($scopeArray['scope_id'], $scopeArray['scope']);
        try {
            $subscriber->setSubscriberFirstname($fname);
            $subscriber->setSubscriberLastname($lname);
            $md5HashEmail = hash('md5', strtolower($email));
            $member = $api->getLists()->getMembers()->get(
                $listId,
                $md5HashEmail,
                null,
                null
            );
            if ($member['status'] == 'subscribed') {
                $helper->subscribeMember($subscriber);
            } elseif ($member['status'] == 'unsubscribed') {
                if (!$helper->getWebhookDeleteAction($subscriber->getStoreId())) {
                    $helper->unsubscribeMember($subscriber);
                }
            }
        } catch (MailChimp_Error $e) {
            $helper->logError($e->getFriendlyMessage());
        } catch (Exception $e) {
            $helper->logError($e->getMessage());
        }
    }

    public function deleteProcessed()
    {
        $helper = $this->getHelper();
        $resource = $helper->getCoreResource();
        $connection = $resource->getConnection('core_write');
        $tableName = $resource->getTableName('mailchimp/webhookrequest');
        $where = array("fired_at < NOW() - INTERVAL 30 DAY AND processed = 1");
        $connection->delete($tableName, $where);
    }

    protected function _loadGroups()
    {
        foreach ($this->_helper->getMageApp()->getStores() as $storeId => $val) {
            $listId = $this->_helper->getGeneralList($storeId);
            $api = $this->_helper->getApi($storeId);
            $interestsCat = $api->getLists()->interestCategory->getAll($listId);
            foreach ($interestsCat['categories'] as $cat) {
                $interests = $api->lists->interestCategory->interests->getAll($listId,$cat['id']);
                $this->_groups = array_merge_recursive($this->_groups, $interests['interests']);
            }
        }
    }

    /**
     * @return Ebizmarts_MailChimp_Model_Interestgroup
     */
    protected function getInterestGroupModel()
    {
        return Mage::getModel('mailchimp/interestgroup');
    }

    /**
     * @return false|Mage_Core_Model_Abstract
     */
    protected function getSubscriberModel()
    {
        return Mage::getModel('newsletter/subscriber');
    }

    protected function _getStoreId()
    {
        return Mage::app()->getStore()->getId();
    }

    /**
     * @return Ebizmarts_MailChimp_Helper_Data
     */
    protected function getHelper()
    {
        return $this->_helper;
    }

    /**
     * @return Ebizmarts_MailChimp_Helper_Date|Mage_Core_Helper_Abstract
     */
    protected function getDateHelper()
    {
        return $this->_dateHelper;
    }
}
