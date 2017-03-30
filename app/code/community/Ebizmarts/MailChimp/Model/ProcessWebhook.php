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

        $old = $data['data']['old_email'];
        $new = $data['data']['new_email'];

        $oldSubscriber = $this->loadByEmail($old);
        $newSubscriber = $this->loadByEmail($new);

        if (!$newSubscriber->getId() && $oldSubscriber->getId()) {
            $oldSubscriber->setSubscriberEmail($new)
                ->save();
        } elseif (!$newSubscriber->getId() && !$oldSubscriber->getId()) {
            Mage::getModel('newsletter/subscriber')
                ->setImportMode(TRUE)
                ->setStoreId(Mage::app()->getStore()->getId())
                ->subscribe($new);
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
        $s = $this->loadByEmail($data['data']['email']);

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
            $subscriber = Mage::getModel('newsletter/subscriber')
                ->loadByEmail($data['data']['email']);
            if ($subscriber->getId()) {
                $subscriber->setStatus(Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED)
                    ->save();
            } else {
                $subscriber = Mage::getModel('newsletter/subscriber')->setImportMode(TRUE);
                if (isset($data['data']['merges']['FNAME'])) {
                    $subscriberFname = filter_var($data['data']['merges']['FNAME'], FILTER_SANITIZE_STRING);
                    $subscriber->setSubscriberFirstname($subscriberFname);
                }

                if (isset($data['data']['merges']['LNAME'])) {
                    $subscriberLname = filter_var($data['data']['merges']['LNAME'], FILTER_SANITIZE_STRING);
                    $subscriber->setSubscriberLastname($subscriberLname);
                }

                $subscriber->subscribe($data['data']['email']);
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
        $subscriber = $this->loadByEmail($data['data']['email']);

        if (!$subscriber->getId()) {
            $subscriber = Mage::getModel('newsletter/subscriber')
                ->loadByEmail($data['data']['email']);
        }

        if ($subscriber->getId()) {
            try {
                switch ($data['data']['action']) {
                    case 'delete' :
                        //if config setting "Webhooks Delete action" is set as "Delete customer account"
                        if (Mage::getStoreConfig("mailchimp/general/webhook_delete") == 1) {
                            $subscriber->delete();
                        } else {
                            $subscriber->setImportMode(TRUE)->unsubscribe();
                        }
                        break;
                    case 'unsub':
                        $subscriber->setImportMode(TRUE)->unsubscribe();
                        break;
                }
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
    }

    protected function _profile(array $data)
    {
        $email = $data['data']['email'];
        $subscriber = $this->loadByEmail($email);

        $customerCollection = Mage::getModel('customer/customer')->getCollection()
            ->addFieldToFilter('email', array('eq' => $email));
        if (count($customerCollection) > 0) {
            $customerId = $customerCollection->getFirstItem()->getEntityId();
        }

        if ($customerId) {
            $toUpdate = Mage::getModel('customer/customer')->load($customerId);
            $toUpdate->setFirstname($data['data']['merges']['FNAME']);
            $toUpdate->setLastname($data['data']['merges']['LNAME']);
        } else {
            $toUpdate = $subscriber;
            $toUpdate->setSubscriberFirstname($data['data']['merges']['FNAME']);
            $toUpdate->setSubscriberLastname($data['data']['merges']['LNAME']);
        }

        $toUpdate->save();


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

    /**
     * Load newsletter_subscriber by email
     *
     * @param string $email
     * @return Mage_Newsletter_Model_Subscriber
     */
    public function loadByEmail($email)
    {
        return Mage::getModel('newsletter/subscriber')
            ->getCollection()
            ->addFieldToFilter('subscriber_email', $email)
            ->getFirstItem();
    }

}
