<?php
/**
 * MailChimp For Magento
 *
 * @category Ebizmarts_MailChimp
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 5/19/16 3:55 PM
 * @file: ProdcessWebhook.php
 */
class Ebizmarts_MailChimp_Model_ProcessWebhook
{
    /**
     * Webhooks request url path
     *
     * @const string
     */

    const WEBHOOKS_PATH = 'mailchimp/webhook/index/';

    /**
     * Process Webhook request
     *
     * @param array $data
     * @return void
     */
    public function processWebhookData(array $data)
    {
        switch ($data['type']) {
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

    /**
     * Update customer email <upemail>
     *
     * @param array $data
     * @return void
     */
    protected function _updateEmail(array $data)
    {

        $listId = $data['data']['list_id'];
        $old = $data['data']['old_email'];
        $new = $data['data']['new_email'];

        $oldSubscriber = Mage::helper('mailchimp')->loadListSubscriber($listId, $old);
        $newSubscriber = Mage::helper('mailchimp')->loadListSubscriber($listId, $new);

        if (!$newSubscriber->getId() && $oldSubscriber->getId()) {
            $oldSubscriber->setSubscriberEmail($new)
                ->save();
        } elseif (!$newSubscriber->getId() && !$oldSubscriber->getId()) {
            $this->_subscribeMember($newSubscriber);
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
        $s = Mage::helper('mailchimp')->loadListSubscriber($data['data']['list_id'], $data['data']['email']);

        if ($s->getId()) {
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
            $listId = $data['data']['list_id'];
            $email = $data['data']['email'];
            $subscriber = Mage::helper('mailchimp')->loadListSubscriber($listId, $email);
            if ($subscriber->getId()) {
                if ($subscriber->getSubscriberStatus() != Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED) {
                    $subscriber->setStatus(Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED)
                        ->save();
                }
            } else {
                if (isset($data['data']['merges']['FNAME'])) {
                    $subscriberFname = filter_var($data['data']['merges']['FNAME'], FILTER_SANITIZE_STRING);
                    $subscriber->setSubscriberFirstname($subscriberFname);
                }

                if (isset($data['data']['merges']['LNAME'])) {
                    $subscriberLname = filter_var($data['data']['merges']['LNAME'], FILTER_SANITIZE_STRING);
                    $subscriber->setSubscriberLastname($subscriberLname);
                }
                $this->_subscribeMember($subscriber);
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    protected function _subscribeMember($subscriber)
    {
        $subscriber->setStatus(Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED);
        $subscriber->setSubscriberConfirmCode($subscriber->randomSequence());
        $subscriber->setSubscriberSource('Mailchimp');
        $subscriber->setIsStatusChanged(true);
        $subscriber->save();
    }

    /**
     * Unsubscribe or delete email from Magento list, store aware
     *
     * @param array $data
     * @return void
     */
    protected function _unsubscribe(array $data)
    {
        $subscriber = Mage::helper('mailchimp')->loadListSubscriber($data['data']['list_id'], $data['data']['email']);
        if ($subscriber->getId()) {
            try {
                $action = isset($data['data']['action']) ? $data['data']['action'] : 'delete';
                switch ($action) {
                    case 'delete' :
                        //if config setting "Webhooks Delete action" is set as "Delete customer account"
                        if (Mage::getStoreConfig("mailchimp/general/webhook_delete", $subscriber->getStoreId())) {
                            $subscriber->delete();
                        } elseif ($subscriber->getSubscriberStatus() != Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED) {
                            $subscriber->setImportMode(TRUE)->unsubscribe();
                        }
                        break;
                    case 'unsub':
                        if ($subscriber->getSubscriberStatus() != Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED) {
                            $subscriber->setImportMode(TRUE)->unsubscribe();
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
        $listId = $data['data']['list_id'];
        $email = $data['data']['email'];
        $fname = isset($data['data']['merges']['FNAME']) ? $data['data']['merges']['FNAME'] : null;
        $lname = isset($data['data']['merges']['LNAME']) ? $data['data']['merges']['LNAME'] : null;
        $customer = Mage::helper('mailchimp')->loadListCustomer($listId, $email);
        if ($customer) {
            $customer->setFirstname($fname);
            $customer->setLastname($lname);
            $customer->save();
        } else {
            $subscriber = Mage::helper('mailchimp')->loadListSubscriber($listId, $email);
            if ($subscriber->getId()) {
                if ($fname !== $subscriber->getSubscriberFirstname() || $lname !== $subscriber->getSubscriberLastname()) {
                    $subscriber->setSubscriberFirstname($fname);
                    $subscriber->setSubscriberLastname($lname);
                    $subscriber->save();
                }
            } else {
                /**
                 * Mailchimp subscriber not currently in magento newsletter subscribers.
                 * Get mailchimp subscriber status and add missing newsletter subscriber.
                 */
                $api = Mage::helper('mailchimp')->getApi($subscriber->getStoreId());
                try {
                    $subscriber->setSubscriberFirstname($fname);
                    $subscriber->setSubscriberLastname($lname);
                    $md5HashEmail = md5(strtolower($email));
                    $member = $api->lists->members->get($listId, $md5HashEmail, null, null);
                    if ($member['status'] == 'subscribed') {
                        $subscriber->setSubscriberStatus(Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED);
                        $subscriber->save();
                    } elseif ($member['status'] == 'unsubscribed') {
                        if (!Mage::getStoreConfig("mailchimp/general/webhook_delete", $subscriber->getStoreId())) {
                            $subscriber->setSubscriberStatus(Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED);
                            $subscriber->save();
                        }
                    }
                } catch (MailChimp_Error $e) {
                    Mage::helper('mailchimp')->logError($e->getFriendlyMessage(), $subscriber->getStoreId());
                } catch (Exception $e) {
                    Mage::helper('mailchimp')->logError($e->getMessage(), $subscriber->getStoreId());
                }
            }
        }
    }

    /**
     * Return Inbox model instance
     *
     * @return Mage_AdminNotification_Model_Inbox
     */
    protected function _getInbox()
    {
        return Mage::getModel('adminnotification/inbox')
            ->setSeverity(4)//Notice
            ->setDateAdded(Mage::getModel('core/date')->gmtDate());
    }

}
