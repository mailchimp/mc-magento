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
        $listId = $data['data']['list_id']; //According to the docs, the events are always related to a list_id
        //$store = Mage::helper('mailchimp')->getStoreByList($listId);

//        if (!is_null($store)) {
//            $curstore = Mage::app()->getStore();
//            Mage::app()->setCurrentStore($store);
//        }

        //Object for cache clean
        $object = new stdClass();
        $object->requestParams = array();
        $object->requestParams['id'] = $listId;

        if (isset($data['data']['email'])) {
            $object->requestParams['email_address'] = $data['data']['email'];
        }
        $cacheHelper = Mage::helper('mailchimp/cache');

        switch ($data['type']) {
            case 'subscribe':
                $this->_subscribe($data);
                $cacheHelper->clearCache('listSubscribe', $object);
                break;
            case 'unsubscribe':
                $this->_unsubscribe($data);
                $cacheHelper->clearCache('listUnsubscribe', $object);
                break;
            case 'cleaned':
                $this->_clean($data);
                $cacheHelper->clearCache('listUnsubscribe', $object);
                break;
//            case 'campaign':
//                $this->_campaign($data);
//                break;
            case 'upemail':
                $this->_updateEmail($data);
                $cacheHelper->clearCache('listUpdateMember', $object);
                break;
//            case 'profile':
//                $this->_profile($data);
//                $cacheHelper->clearCache('listUpdateMember', $object);
//                break;
        }

//        if (!is_null($store)) {
//            Mage::app()->setCurrentStore($curstore);
//        }
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

            //@Todo Handle merge vars on the configuration
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

        if (Mage::helper('mailchimp')->isAdminNotificationEnabled()) {
            $text = Mage::helper('mailchimp')->__('MailChimp Cleaned Emails: %s %s at %s reason: %s', $data['data']['email'], $data['type'], $data['fired_at'], $data['data']['reason']);

            $this->_getInbox()
                ->setTitle($text)
                ->setDescription($text)
                ->save();
        }

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

//    /**
//     * Add "Campaign Sending Status" notification to Adminnotification Inbox <campaign>
//     *
//     * @param array $data
//     * @return void
//     */
//    protected function _campaign(array $data)
//    {
//
//        if (Mage::helper('mailchimp')->isAdminNotificationEnabled()) {
//            $text = Mage::helper('mailchimp')->__('MailChimp Campaign Send: %s %s at %s', $data['data']['subject'], $data['data']['status'], $data['fired_at']);
//
//            $this->_getInbox()
//                ->setTitle($text)
//                ->setDescription($text)
//                ->save();
//        }
//
//    }

    /**
     * Subscribe email to Magento list, store aware
     *
     * @param array $data
     * @return void
     */
    protected function _subscribe(array $data)
    {
        try {

            //TODO: El método subscribe de Subscriber (Magento) hace un load by email
            // entonces si existe en un store, lo acutaliza y lo cambia de store, no lo agrega a otra store
            //VALIDAR si es lo que se requiere

            $subscriber = Mage::getModel('newsletter/subscriber')
                ->loadByEmail($data['data']['email']);
            if ($subscriber->getId()) {
                $subscriber->setStatus(Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED)
                    ->save();
            } else {
                $subscriber = Mage::getModel('newsletter/subscriber')->setImportMode(TRUE);
                if(isset($data['data']['fname'])){
                    $subscriber->setSubscriberFirstname($data['data']['fname']);
                }
                if(isset($data['data']['lname'])){
                    $subscriber->setSubscriberLastname($data['data']['lname']);
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
        $storeId = $subscriber->getStoreId();

        $customerCollection = Mage::getModel('customer/customer')->getCollection()
            ->addFieldToFilter('email', array('eq' => $email));
        if (count($customerCollection) > 0) {
            $toUpdate = $customerCollection->getFirstItem();
        } else {
            $toUpdate = $subscriber;
        }
        $toUpdate->setFirstname($data['data']['merges']['FNAME']);
        $toUpdate->setLastname($data['data']['merges']['LNAME']);
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
            ->addFieldToFilter('store_id', Mage::app()->getStore()->getId())
            ->getFirstItem();
    }

}
