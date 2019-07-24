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
    /**
     * Webhooks request url path
     *
     * @const string
     */

    const WEBHOOKS_PATH = 'mailchimp/webhook/index/';

    public function __construct()
    {
        $this->_helper = Mage::helper('mailchimp');
    }

    /**
     * @return Ebizmarts_MailChimp_Helper_Data
     */
    protected function getHelper()
    {
        return $this->_helper;
    }

    public function saveWebhookRequest(array $data)
    {
        try {
            $request = Mage::getModel('mailchimp/webhookrequest')
                ->setType($data['type'])
                ->setFiredAt($data['fired_at'])
                ->setDataRequest(serialize($data['data']))
                ->save();
            $this->getHelper()->logInfo(
                "Queued web hook request ID " . $request->getId(). ": type "
                . "{$data['type']} list_id {$data['data']['list_id']}"
            );
        } catch (Exception $e) {
            $this->getHelper()->logError(
                "Failed to queue web hook request: " . $request->getDataRequest()
            );
        }
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
            $data = unserialize($webhookRequest->getDataRequest());
            if ($data) {
                $this->getHelper()->logInfo(
                    "Processing web hook request " . $webhookRequest->getId()
                    . ": type " . $webhookRequest->getType(). " list_id "
                    . "{$data['list_id']}"
                );
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

        try {
            $oldSubscriber = $helper->loadListSubscriber($listId, $old);
            $newSubscriber = $helper->loadListSubscriber($listId, $new);

            if ($oldSubscriber && $newSubscriber) {
                if (!$newSubscriber->getId()) {
                    if ($oldSubscriber->getId()) {
                        $helper->logDebug(
                            "Update email web hook request for list ID $listId "
                            . "for $old to $new: replacing email address for "
                            . "subscriber ID " . $oldSubscriber->getId()
                        );
                        $oldSubscriber->setSubscriberEmail($new);
                        $oldSubscriber->setSubscriberSource(Ebizmarts_MailChimp_Model_Subscriber::SUBSCRIBE_SOURCE);
                        $oldSubscriber->save();
                        $helper->logInfo(
                            "Update email web hook request for list ID $listId "
                            . "for $old to $new: replaced email address for "
                            . "subscriber ID " . $oldSubscriber->getId()
                        );
                    } else {
                        $helper->logDebug(
                            "Update email web hook request for list ID $listId "
                            . "for $old to $new: adding new subscriber"
                        );
                        $helper->subscribeMember($newSubscriber);
                        $helper->logInfo(
                            "Update email web hook request for list ID $listId "
                            . "for $old to $new: added new subscriber "
                            . $newSubscriber->getId()
                        );
                    }
                } else {
                    $oldId = $oldSubscriber->getId() ? $oldSubscriber->getId() : 'NULL';
                    $helper->logWarning(
                        "Unable to update email web hook request for list ID "
                        . "$listId for $old (ID $oldId) to $new (ID "
                        . $newSubscriber->getId() . ": subscriber already exists"
                    );
                }
            } else {
                $helper->logWarning(
                    "Ignoring update email web hook request for list ID $listId "
                    . "from old $old to $new: no magento store is synchronised "
                    . "to that MailChimp list!"
                );
            }
        } catch (Exception $e) {
            $helper->logError(
                "Failed to process update email web hook request for list ID "
                . "$listId from old $old to $new: " . $e->getMessage()
            );
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
        $helper = $this->getHelper();
        $listId = $data['list_id'];
        $email = $data['email'];
        try {
            $s = $helper->loadListSubscriber($listId, $email);

            if ($s) {
                if ($s->getId()) {
                    $helper->logDebug(
                        "Clean subscriber web hook request for subscriber $email "
                        . "on list ID $listId: removing subscriber ID "
                        . $s->getId()
                    );
                    $s->delete();
                    $helper->logInfo(
                        "Clean subscriber web hook request for subscriber $email "
                        . "on list ID $listId: removed subscriber ID "
                        . $s->getId()
                    );
                } else {
                    $helper->logWarning(
                        "Clean subscriber web hook request for subscriber $email "
                        . "on list ID $listId: no such subscriber exists"
                    );
                }
            } else {
                $helper->logWarning(
                    "Ignoring clean web hook request for subscriber $email on "
                    . "list ID $listId: no magento store is synchronised to that "
                    . "MailChimp list!"
                );
            }
        } catch (Exception $e) {
            $helper->logError(
                "Failed to process clean web hook request for subscriber $email "
                . "on list ID $listId: " . $e->getMessage()
            );
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
        $helper = $this->getHelper();
        try {
            $listId = $data['list_id'];
            $email = $data['email'];
            $subscriber = $helper->loadListSubscriber($listId, $email);
            if ($subscriber) {
                if ($subscriber->getId()) {
                    if ($subscriber->getSubscriberStatus() != Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED) {
                        $helper->logDebug(
                            "Subscribe web hook request for $email on list ID "
                            . "$listId: changing subscriber ID "
                            . $subscriber->getId() . " member status to subscribed"
                        );
                        $helper->subscribeMember($subscriber);
                        $helper->logInfo(
                            "Subscribe web hook request for $email on list ID "
                            . "$listId: changed subscriber ID "
                            . $subscriber->getId() . " member status to subscribed"
                        );
                    } else {
                        $helper->logInfo(
                            "Subscribe web hook request for $email on list ID "
                            . "$listId: subscriber ID "
                            . $subscriber->getId() . " already subscribed"
                        );
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

                    $helper->logDebug(
                        "Subscribe web hook request for $email on list ID $listId: "
                        . "adding new subscriber"
                    );
                    $helper->subscribeMember($subscriber);
                    $helper->logInfo(
                        "Subscribe web hook request for $email on list ID $listId: "
                        . "added new subscriber ID " . $subscriber->getId()
                    );
                }
            } else {
                $helper->logWarning(
                    "Ignoring subscribe web hook request for member $email on "
                    . "list ID $listId: no magento store is "
                    . "synchronised to that MailChimp list!"
                );
            }
        } catch (Exception $e) {
            $helper->logError(
                "Failed to process subscribe web hook request for member $email "
                . "on list ID $listId: " . $e->getMessage()
            );
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
        try {
            $subscriber = $helper->loadListSubscriber($data['list_id'], $data['email']);
            if ($subscriber) {
                if ($subscriber->getId()) {
                    $action = isset($data['action']) ? $data['action'] : 'delete';
                    switch ($action) {
                        case 'delete' :
                            //if config setting "Webhooks Delete action" is set as "Delete customer account"
                            if (Mage::getStoreConfig(
                                Ebizmarts_MailChimp_Model_Config::GENERAL_UNSUBSCRIBE,
                                $subscriber->getStoreId()
                            )
                            ) {
                                $helper->logDebug(
                                    "Un-subscribe web hook request for member "
                                    . "{$data['email']} on list ID {$data['list_id']}: "
                                    . "deleting subscriber ID " . $subscriber->getId()
                                );
                                $subscriber->delete();
                                $helper->logInfo(
                                    "Un-subscribe web hook request for member "
                                    . "{$data['email']} on list ID {$data['list_id']}: "
                                    . "deleted subscriber ID " . $subscriber->getId()
                                );
                            } elseif ($subscriber->getSubscriberStatus() !=
                                Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED) {
                                $helper->logDebug(
                                    "Un-subscribe web hook request for member "
                                    . "{$data['email']} on list ID {$data['list_id']}: "
                                    . "un-subscribing subscriber ID " . $subscriber->getId()
                                );
                                $helper->unsubscribeMember($subscriber);
                                $helper->logInfo(
                                    "Un-subscribe web hook request for member "
                                    . "{$data['email']} on list ID {$data['list_id']}: "
                                    . "un-subscribed subscriber ID " . $subscriber->getId()
                                );
                            }
                            break;
                        case 'unsub':
                            if ($subscriber->getSubscriberStatus() !=
                                Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED) {
                                $helper->logDebug(
                                    "Un-subscribe web hook request for member "
                                    . "{$data['email']} on list ID {$data['list_id']}: "
                                    . "un-subscribing subscriber ID " . $subscriber->getId()
                                );
                                $helper->unsubscribeMember($subscriber);
                                $helper->logInfo(
                                    "Un-subscribe web hook request for member "
                                    . "{$data['email']} on list ID {$data['list_id']}: "
                                    . "un-subscribed subscriber ID " . $subscriber->getId()
                                );
                            } else {
                                $helper->logInfo(
                                    "Un-subscribe web hook request for member "
                                    . "{$data['email']} on list ID {$data['list_id']}: "
                                    . "subscriber ID " . $subscriber->getId()
                                    . " is already un-subscribed"
                                );
                            }
                            break;
                    }
                } else {
                    $helper->logWarning(
                        "Ignoring un-subscribe web hook request for member "
                        . "{$data['email']} on list ID {$data['list_id']}: "
                        . "no such subscriber exists!"
                    );
                }
            } else {
                $helper->logWarning(
                    "Ignoring un-subscribe web hook request for member "
                    . "{$data['email']} on list ID {$data['list_id']}: no "
                    . "magento store is synchronised to that MailChimp list!"
                );
            }
        } catch (Exception $e) {
            $helper->logError(
                "Failed to process un-subscribe web hook request for member "
                . "{$data['email']} on list ID {$data['list_id']}: "
                . $e->getMessage()
            );
            Mage::logException($e);
        }
    }

    protected function _getLogString($string, $nullValue = '<NONE>')
    {
        return (empty($string) ? $nullValue : $string);
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
        $fnameDisplay = $this->_getLogString($fname);
        $lnameDisplay = $this->_getLogString($lname);

        if (empty($fname) && empty($lname)) {
            $helper->logWarning(
                "Profile web hook request for member $email on list ID $listId: "
                . "first name and last name are missing or empty: ignoring!"
            );
        } else {
            try {
                /** @var Mage_Customer_Model_Customer $customer */
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
                        $fn = $this->_getLogString($customer->getFirstname());
                        $ln = $this->_getLogString($customer->getLastname());
                        $helper->logDebug(
                            "Profile web hook request for member $email on list "
                            . "ID $listId: updating customer ID " . $customer->getId()
                            . " first name from $fn to $fnameDisplay and last "
                            . "name from $ln to $lnameDisplay"
                        );
                        $customer->save();
                        $helper->logInfo(
                            "Profile web hook request for member $email on list "
                            . "ID $listId: updated customer ID " . $customer->getId()
                            . " first name from $fn to $fnameDisplay and last "
                            . "name from $ln to $lnameDisplay"
                        );
                    } else {
                        $helper->logInfo(
                            "Profile web hook request for member $email on list "
                            . "ID $listId: customer ID " . $customer->getId()
                            . " first name $fnameDisplay and last name "
                            . "$lnameDisplay unchanged"
                        );
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
                                $fn = $this->_getLogString($subscriber->getFirstname());
                                $ln = $this->_getLogString($subscriber->getLastname());
                                $helper->logDebug(
                                    "Profile web hook request for member $email "
                                    . "on list ID $listId: updating subscriber ID "
                                    . $subscriber->getId() . " first name from $fn "
                                    . "to $fnameDisplay and last name from $ln "
                                    . "to $lnameDisplay"
                                );
                                $subscriber->save();
                                $helper->logInfo(
                                    "Profile web hook request for member $email "
                                    . "on list ID $listId: updated subscriber ID "
                                    . $subscriber->getId() . " first name from "
                                    . "$fn to $fnameDisplay and last name from "
                                    . "$ln to $lnameDisplay"
                                );
                            } else {
                                $helper->logInfo(
                                    "Profile web hook request for member $email "
                                    . "on list ID $listId: no change to subscriber "
                                    . "ID " . $subscriber->getId() . " with first "
                                    . "name $fnameDisplay and last name "
                                    . "$lnameDisplay unchanged"
                                );
                            }
                        } else {
                            /**
                             * Mailchimp subscriber not currently in magento newsletter subscribers.
                             * Get mailchimp subscriber status and add missing newsletter subscriber.
                             */
                            $this->_addSubscriberData($subscriber, $fname, $lname, $email, $listId);
                        }
                    } else {
                        $helper->logWarning(
                            "Ignoring profile web hook request for member $email "
                            . "fname $fnameDisplay lname $lnameDisplay on list "
                            . "ID $listId: no magento store is synchronised to "
                            . "that MailChimp list!"
                        );
                    }
                }
            } catch (MailChimp_Error $e) {
                $helper->logError(
                    "Failed to process profile web hook request for member $email "
                    . "fname $fnameDisplay lname $lnameDisplay on list ID $listId: "
                    . $e->getFriendlyMessage()
                );
            } catch (Exception $e) {
                $helper->logError(
                    "Failed to process profile web hook request for member $email "
                    . "fname $fnameDisplay lname $lnameDisplay on list ID $listId: "
                    . $e->getMessage()
                );
                Mage::logException($e);
            }
        }
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
            $md5HashEmail = md5(strtolower($email));
            $member = $api->getLists()->getMembers()->get(
                $listId,
                $md5HashEmail,
                null,
                null
            );
            if ($member['status'] == 'subscribed') {
                $helper->logDebug(
                    "Profile web hook request for member $email on list ID $listId: "
                    . "adding new subscribed member"
                );
                $helper->subscribeMember($subscriber);
                $helper->logInfo(
                    "Profile web hook request for member $email on list ID $listId: "
                    . "added new subscribed member as ID " . $subscriber->getId()
                );
            } elseif ($member['status'] == 'unsubscribed') {
                if (!$helper->getWebhookDeleteAction($subscriber->getStoreId())) {
                    $helper->logDebug(
                        "Profile web hook request for member $email on list ID "
                        . "$listId: adding new un-subscribed member"
                    );
                    $helper->unsubscribeMember($subscriber);
                    $helper->logInfo(
                        "Profile web hook request for member $email on list ID "
                        . "$listId: added new un-subscribed member as ID "
                        . $subscriber->getId()
                    );
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
}
