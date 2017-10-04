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
    private $helper;
    /**
     * Webhooks request url path
     *
     * @const string
     */

    const WEBHOOKS_PATH = 'mailchimp/webhook/index/';

    public function __construct()
    {
        $this->helper = Mage::helper('mailchimp');
    }

    protected function getHelper()
    {
        return $this->helper;
    }

    public function saveWebhookRequest(array $data)
    {
        Mage::getModel('mailchimp/webhookrequest')
            ->setType($data['type'])
            ->setFiredAt($data['fired_at'])
            ->setDataRequest(serialize($data['data']))
            ->save();
    }

    /**
     * Process Webhook request
     *
     * @return void
     */
    public function processWebhookData()
    {
        $collection = Mage::getResourceModel('mailchimp/webhookrequest_collection');
        $collection->addFieldToFilter('processed', array('eq' => 0));
        $collection->getSelect()->limit(self::BATCH_LIMIT);
        foreach ($collection as $webhookRequest) {
            $data = unserialize($webhookRequest->getDataRequest());
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
            $webhookRequest->setProcessed(1)
                ->save();
        }
    }

    /**
     * Update customer email <upemail>
     *
     * @param  array $data
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
                    $oldSubscriber->setSubscriberSource(Ebizmarts_MailChimp_Model_Subscriber::SUBSCRIBE_SOURCE);
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
     * @param  array $data
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
     * @param  array $data
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
                }
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * Unsubscribe or delete email from Magento list, store aware
     *
     * @param  array $data
     * @return void
     */
    protected function _unsubscribe(array $data)
    {
        $helper = $this->getHelper();
        $subscriber = $helper->loadListSubscriber($data['list_id'], $data['email']);
        if ($subscriber && $subscriber->getId()) {
            try {
                $action = isset($data['action']) ? $data['action'] : 'delete';
                switch ($action) {
                    case 'delete' :
                        //if config setting "Webhooks Delete action" is set as "Delete customer account"
                        if (Mage::getStoreConfig("mailchimp/general/webhook_delete", $subscriber->getStoreId())) {
                            $subscriber->delete();
                        } elseif ($subscriber->getSubscriberStatus() != Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED) {
                            $helper->unsubscribeMember($subscriber);
                        }
                        break;
                    case 'unsub':
                        if ($subscriber->getSubscriberStatus() != Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED) {
                            $helper->unsubscribeMember($subscriber);
                        }
                        break;
                }
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
    }

    protected function _profile(array $data)
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
                        $subscriber->setSubscriberSource(Ebizmarts_MailChimp_Model_Subscriber::SUBSCRIBE_SOURCE);
                        $subscriber->save();
                    }
                } else {
                    /**
                     * Mailchimp subscriber not currently in magento newsletter subscribers.
                     * Get mailchimp subscriber status and add missing newsletter subscriber.
                     */
                    $api = $helper->getApi($subscriber->getStoreId());
                    try {
                        $subscriber->setSubscriberFirstname($fname);
                        $subscriber->setSubscriberLastname($lname);
                        $md5HashEmail = md5(strtolower($email));
                        $member = $api->lists->members->get($listId, $md5HashEmail, null, null);
                        if ($member['status'] == 'subscribed') {
                            $helper->subscribeMember($subscriber);
                        } elseif ($member['status'] == 'unsubscribed') {
                            if (!Mage::getStoreConfig("mailchimp/general/webhook_delete", $subscriber->getStoreId())) {
                                $helper->unsubscribeMember($subscriber);
                            }
                        }
                    } catch (MailChimp_Error $e) {
                        $helper->logError($e->getFriendlyMessage(), $subscriber->getStoreId());
                    } catch (Exception $e) {
                        $helper->logError($e->getMessage(), $subscriber->getStoreId());
                    }
                }
            }
        }
    }

    public function deleteProcessed()
    {
        $to= Mage::app()->getLocale()->date()->sub(30,Zend_Date::DAY);
        $to=$to->toString('yyyy-MM-dd');

        $collection = Mage::getModel('mailchimp/webhookrequest')->getCollection()
            ->addFieldToFilter('processed', 1)
            ->addFieldToFilter('fired_at', array(
                'lt'=>$to
            ));
            $collection->getSelect()->limit(self::BATCH_LIMIT);

        foreach ($collection as $row) {
            $row->delete();
        }

    }

}
