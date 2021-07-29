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

    /**
     * @var Ebizmarts_MailChimp_Model_Api_Subscribers_InterestGroupHandle
     */
    protected $_interestGroupHandle;

    /**
     * @var Ebizmarts_MailChimp_Model_Api_Subscribers_MailchimpTags
     */
    protected $_tags;

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

        $this->_tags = Mage::getModel('mailchimp/api_subscribers_MailchimpTags');
        $this->_interestGroupHandle = Mage::getModel('mailchimp/api_subscribers_InterestGroupHandle');
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
        try {
            $subscribe = true;
            $this->getMailchimpTagsModel()->processMergeFields($data, $subscribe);
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
        $this->getMailchimpTagsModel()->processMergeFields($data);
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

    protected function _getStoreId()
    {
        return Mage::app()->getStore()->getId();
    }

    /**
     * @return false|Mage_Core_Model_Abstract
     */
    protected function getInterestGroupHandleModel()
    {
        return $this->_interestGroupHandle;
    }

    /**
     * @return Ebizmarts_MailChimp_Model_Api_Subscribers_MailchimpTags
     */
    protected function getMailchimpTagsModel()
    {
        return $this->_tags;
    }

    /**
     * @return Ebizmarts_MailChimp_Helper_Data
     */
    protected function getHelper($type='')
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
