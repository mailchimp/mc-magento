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
    /**
     * Webhooks request url path
     *
     * @const string
     */

    const WEBHOOKS_PATH = 'mailchimp/webhook/index/';

    private $_helper;


    public function __construct()
    {
        $this->_helper = Mage::helper('mailchimp');
    }

    public function saveWebhookRequest(array $data)
    {
        try {
            $request = Mage::getModel('mailchimp/webhookrequest')
                ->setType($data['type'])
                ->setFiredAt($data['fired_at'])
                ->setDataRequest(serialize($data['data']))
                ->save();
            $this->_helper->logInfo("Queued web hook request ID {$request->getId()}: type {$data['type']} list_id {$data['data']['list_id']}");
        } catch (Exception $e) {
            $this->_helper->logError("Failed to queue web hook request: ".$request->getDataRequest());
        }
    }

    /**
     * Process Webhook request
     *
     * @return void
     */
    public function processWebhookData()
    {
        $collection = Mage::getResourceModel('mailchimp/webhookrequest_collection');
        foreach ($collection as $webhookRequest) {
            $data = unserialize($webhookRequest->getDataRequest());
            $this->_helper->logInfo("Processing web hook request {$webhookRequest->getId()}: type {$webhookRequest->getType()} list_id {$data['list_id']}");
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
        $listId = $data['list_id'];
        $old = $data['old_email'];
        $new = $data['new_email'];

        try {
            $oldSubscriber = $this->_helper->loadListSubscriber($listId, $old);
            $newSubscriber = $this->_helper->loadListSubscriber($listId, $new);

            if (!$newSubscriber->getId()) {
                if ($oldSubscriber->getId()) {
                    $this->_helper->logDebug("Update email web hook request for list ID $listId for $old to $new: replacing email address for subscriber ID ".$oldSubscriber->getId());
                    $oldSubscriber->setSubscriberEmail($new)
                        ->save();
                    $this->_helper->logInfo("Update email web hook request for list ID $listId for $old to $new: replaced email address for subscriber ID ".$oldSubscriber->getId());
                } else {
                    $this->_helper->logDebug("Update email web hook request for list ID $listId for $old to $new: adding new subscriber");
                    $this->subscribeMember($newSubscriber);
                    $this->_helper->logInfo("Update email web hook request for list ID $listId for $old to $new: added new subscriber ".$newSubscriber->getId());
                }
            } else {
                $oldId = $oldSubscriber->getId() ? $oldSubscriber->getId() : 'NULL';
                $this->_helper->logWarning("Unable to update email web hook request for list ID $listId for $old (ID $oldId) to $new (ID {$newSubscriber->getId()}: subscriber already exists");
            }
        } catch (Exception $e) {
            $this->_helper->logError("Failed to process update email web hook request for list ID $listId from old $old to $new: ".$e->getMessage());
            Mage::logException($e);
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
        try {
            $s = $this->_helper->loadListSubscriber($data['list_id'], $data['email']);
            if ($s->getId()) {
                $this->_helper->logDebug("Clean subscriber web hook request for subscriber {$data['email']} on list ID {$data['list_id']}: removing subscriber ID ".$s->getId());
                $s->delete();
                $this->_helper->logInfo("Clean subscriber web hook request for subscriber {$data['email']} on list ID {$data['list_id']}: removed subscriber ID ".$s->getId());
            } else {
                $this->_helper->logWarning("Clean subscriber web hook request for subscriber {$data['email']} on list ID {$data['list_id']}: no such subscriber exists");
            }
        } catch (Exception $e) {
            $this->_helper->logError("Failed to process clean web hook request for subscriber {$data['email']} on list ID {$data['list_id']}: ".$e->getMessage());
            Mage::logException($e);
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
        try {
            $listId = $data['list_id'];
            $email = $data['email'];
            $subscriber = $this->_helper->loadListSubscriber($listId, $email);
            if ($subscriber->getId()) {
                if ($subscriber->getSubscriberStatus() != Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED) {
                    $this->_helper->logDebug("Subscribe web hook request for {$data['email']} on list ID {$data['list_id']}: changing subscriber ID {$subscriber->getId()} member status to subscribed");
                    $this->subscribeMember($subscriber);
                    $this->_helper->logInfo("Subscribe web hook request for {$data['email']} on list ID {$data['list_id']}: changed subscriber ID {$subscriber->getId()} member status to subscribed");
                } else {
                    $this->_helper->logDebug("Subscribe web hook request for {$data['email']} on list ID {$data['list_id']}: subscriber ID {$subscriber->getId()} already subscribed");
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
                $this->_helper->logDebug("Subscribe web hook request for {$data['email']} on list ID {$data['list_id']}: adding new subscriber");
                $this->subscribeMember($subscriber);
                $this->_helper->logInfo("Subscribe web hook request for {$data['email']} on list ID {$data['list_id']}: added new subscriber ID {$subscriber->getId()}");
            }
        } catch (Exception $e) {
            $this->_helper->logError("Failed to process subscribe web hook request for member $email on list ID $listId: ".$e->getMessage());
            Mage::logException($e);
        }
    }

    protected function subscribeMember($subscriber)
    {
        $subscriber->setStatus(Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED);
        $subscriber->setSubscriberConfirmCode($subscriber->randomSequence());
        $this->setMemberGeneralData($subscriber);
    }

    protected function unsubscribeMember($subscriber)
    {
        $subscriber->setStatus(Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED);
        $this->setMemberGeneralData($subscriber);
    }

    protected function setMemberGeneralData($subscriber)
    {
        $subscriber->setImportMode(true);
        $subscriber->setSubscriberSource(Ebizmarts_MailChimp_Model_Subscriber::SUBSCRIBE_SOURCE);
        $subscriber->setIsStatusChanged(true);
        $subscriber->save();
    }

    /**
     * Unsubscribe or delete email from Magento list, store aware
     *
     * @param  array $data
     * @return void
     */
    protected function _unsubscribe(array $data)
    {
        try {
            $subscriber = $this->_helper->loadListSubscriber($data['list_id'], $data['email']);
            if ($subscriber->getId()) {
                $action = isset($data['action']) ? $data['action'] : 'delete';
                switch ($action) {
                case 'delete' :
                    //if config setting "Webhooks Delete action" is set as "Delete customer account"
                    if (Mage::getStoreConfig("mailchimp/general/webhook_delete", $subscriber->getStoreId())) {
                        $this->_helper->logDebug("Un-subscribe web hook request for member {$data['email']} on list ID {$data['list_id']}: deleting subscriber ID {$subscriber->getId()}");
                        $subscriber->delete();
                        $this->_helper->logInfo("Un-subscribe web hook request for member {$data['email']} on list ID {$data['list_id']}: deleted subscriber ID {$subscriber->getId()}");
                    } elseif ($subscriber->getSubscriberStatus() != Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED) {
                        $this->_helper->logDebug("Un-subscribe web hook request for member {$data['email']} on list ID {$data['list_id']}: un-subscribing subscriber ID {$subscriber->getId()}");
                        $this->unsubscribeMember($subscriber);
                        $this->_helper->logInfo("Un-subscribe web hook request for member {$data['email']} on list ID {$data['list_id']}: un-subscribed subscriber ID {$subscriber->getId()}");
                    }
                    break;
                case 'unsub':
                    if ($subscriber->getSubscriberStatus() != Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED) {
                        $this->_helper->logDebug("Un-subscribe web hook request for member {$data['email']} on list ID {$data['list_id']}: un-subscribing subscriber ID {$subscriber->getId()}");
                        $this->unsubscribeMember($subscriber);
                        $this->_helper->logInfo("Un-subscribe web hook request for member {$data['email']} on list ID {$data['list_id']}: un-subscribed subscriber ID {$subscriber->getId()}");
                    } else {
                        $this->_helper->logInfo("Un-subscribe web hook request for member {$data['email']} on list ID {$data['list_id']}: subscriber ID {$subscriber->getId()} is already un-subscribed");
                    }
                    break;
                }
            } else {
                $this->_helper->logWarning("Ignoring un-subscribe web hook request for member {$data['email']} on list ID {$data['list_id']}: no such subscriber exists!");
            }
        } catch (Exception $e) {
            $this->_helper->logError("Failed to process un-subscribe web hook request for member {$data['email']} on list ID {$data['list_id']}: ".$e->getMessage());
            Mage::logException($e);
        }
    }

    protected function _getLogString($string, $nullValue = '<NONE>')
    {
        return (empty($string) ? $nullValue : $string);
    }

    protected function _profile(array $data)
    {
        $listId = $data['list_id'];
        $email = $data['email'];
        $fname = isset($data['merges']['FNAME']) ? $data['merges']['FNAME'] : null;
        $lname = isset($data['merges']['LNAME']) ? $data['merges']['LNAME'] : null;
        $fnameDisplay = $this->_getLogString($fname);
        $lnameDisplay = $this->_getLogString($lname);

        try {
            /** @var Mage_Customer_Model_Customer $customer */
            $customer = $this->_helper->loadListCustomer($listId, $email);
            if ($customer) {
                $fn = $this->_getLogString($customer->getFirstname());
                $ln = $this->_getLogString($customer->getLastname());
                $this->_helper->logDebug("Profile web hook request for member $email on list ID $listId: updating customer ID {$customer->getId()} first name from $fnameDisplay to $fn and last name from $lnameDisplay to $ln");
                $customer->setFirstname($fname);
                $customer->setLastname($lname);
                $customer->save();
                $this->_helper->logInfo("Profile web hook request for member $email on list ID $listId: updated customer ID {$customer->getId()} first name from $fnameDisplay to $fn and last name from $lnameDisplay to $ln");
            } else {
                $subscriber = $this->_helper->loadListSubscriber($listId, $email);
                if ($subscriber) {
                    if ($subscriber->getId()) {
                        if ($fname !== $subscriber->getSubscriberFirstname() || $lname !== $subscriber->getSubscriberLastname()) {
                            $fn = $this->_getLogString($subscriber->getFirstname());
                            $ln = $this->_getLogString($subscriber->getLastname());
                            $this->_helper->logDebug("Profile web hook request for member $email on list ID $listId: updating subscriber ID {$subscriber->getId()} first name from $fnameDisplay to $fn and last name from $lnameDisplay to $ln");
                            $subscriber->setSubscriberFirstname($fname);
                            $subscriber->setSubscriberLastname($lname);
                            $subscriber->save();
                            $this->_helper->logInfo("Profile web hook request for member $email on list ID $listId: updated subscriber ID {$subscriber->getId()} first name from $fnameDisplay to $fn and last name from $lnameDisplay to $ln");
                        } else {
                            $this->_helper->logInfo("Profile web hook request for member $email on list ID $listId: no change to subscriber ID {$subscriber->getId()} with first name $fnameDisplay and last name $lnameDisplay");
                        }
                    } else {
                        /**
                         * Mailchimp subscriber not currently in magento newsletter subscribers.
                         * Get mailchimp subscriber status and add missing newsletter subscriber.
                         */
                        $api = $this->_helper->getApi($subscriber->getStoreId());
                        $subscriber->setSubscriberFirstname($fname);
                        $subscriber->setSubscriberLastname($lname);
                        $md5HashEmail = md5(strtolower($email));
                        $member = $api->lists->members->get($listId, $md5HashEmail, null, null);
                        if ($member['status'] == 'subscribed') {
                            $this->_helper->logDebug("Profile web hook request for member $email on list ID $listId: adding new subscribed member");
                            $this->subscribeMember($subscriber);
                            $this->_helper->logInfo("Profile web hook request for member $email on list ID $listId: added new subscribed member as ID ".$subscriber->getId());
                        } elseif ($member['status'] == 'unsubscribed') {
                            if (!Mage::getStoreConfig("mailchimp/general/webhook_delete", $subscriber->getStoreId())) {
                                $this->_helper->logDebug("Profile web hook request for member $email on list ID $listId: adding new un-subscribed member");
                                $this->unsubscribeMember($subscriber);
                                $this->_helper->logInfo("Profile web hook request for member $email on list ID $listId: added new un-subscribed member as ID ".$subscriber->getId());
                            }
                        }
                    }
                } else {
                    $this->_helper->logWarning("Ignoring profile web hook request for member $email on list ID $listId: no magento store is synchronised to that MailChimp list!");
                }
            }
        } catch (MailChimp_Error $e) {
            $this->_helper->logError("Failed to process profile web hook request for member $email fname $fnameDisplay lname $lnameDisplay on list ID {$data['list_id']}: ".$e->getFriendlyMessage());
        } catch (Exception $e) {
            $this->_helper->logError("Failed to process profile web hook request for member $email fname $fnameDisplay lname $lnameDisplay on list ID {$data['list_id']}: ".$e->getMessage());
            Mage::logException($e);
        }
    }
}
