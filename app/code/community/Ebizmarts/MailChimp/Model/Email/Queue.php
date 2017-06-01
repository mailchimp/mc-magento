<?php
/**
 * mc-magento Magento Component
 *
 * @category  Ebizmarts
 * @package   mc-magento
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     8/30/16 2:46 PM
 * @file:     Queue.php
 */
class Ebizmarts_MailChimp_Model_Email_Queue extends Mage_Core_Model_Email_Queue
{
    /**
     * Send all messages in a queue via mandrill
     *
     * @return Mage_Core_Model_Email_Queue
     */
    public function send()
    {
        /**
 * @var $collection Mage_Core_Model_Resource_Email_Queue_Collection 
*/
        $collection = Mage::getResourceModel('core/email_queue_collection')
            ->addOnlyForSendingFilter()
            ->setPageSize(self::MESSAGES_LIMIT_PER_CRON_RUN)
            ->setCurPage(1)
            ->load();
        /**
 * @var $message Mage_Core_Model_Email_Queue 
*/
        foreach ($collection as $message) {
            if ($message->getId()) {
                if ($message->getEntityType() == 'order') {
                    $order = Mage::getModel('sales/order')->load($message->getEntityId());
                    $storeId = $order->getStoreId();
                } else {
                    //If email is not an order confirmation email, it will check if Mandrill enable in default config
                    $storeId = Mage::app()->getStore()->getId();
                }

                if (Mage::helper('mailchimp/mandrill')->isMandrillEnabled($storeId)) {
                    $parameters = new Varien_Object($message->getMessageParameters());
                    $mailer = $this->getMail($storeId);
                    $mailer->setFrom($parameters->getFromEmail(), $parameters->getFromName());
                    $mailer->setSubject($parameters->getSubject());
                    if ($parameters->getIsPlain()) {
                        $mailer->setBodyText($message->getMessageBody());
                    } else {
                        $mailer->setBodyHtml($message->getMessageBody());
                    }

                    try {
                        foreach ($message->getRecipients() as $recipient) {
                            list($email, $name, $type) = $recipient;
                            switch ($type) {
                            case self::EMAIL_TYPE_TO:
                            case self::EMAIL_TYPE_CC:
                                $mailer->addTo($email, $name);
                                break;
                            case self::EMAIL_TYPE_BCC:
                                $mailer->addBcc($email);
                                break;
                            }
                        }

                        if ($parameters->getReplyTo() !== null) {
                            $mailer->setReplyTo($parameters->getReplyTo());
                        }

                        if ($parameters->getReturnTo() !== null) {
                            $mailer->setReturnPath($parameters->getReturnTo());
                        }

                        try {
                            Mage::dispatchEvent(
                                'fooman_emailattachments_before_send_queue',
                                array(
                                    'mailer' => $mailer,
                                    'message' => $message,
                                    'mail_transport' => false

                                )
                            );
                            $mailer->send();
                        } catch (Exception $e) {
                            Mage::logException($e);
                        }

                        unset($mailer);
                        $message->setProcessedAt(Varien_Date::formatDate(true));
                        $message->save();
                    } catch (Exception $e) {
                        Mage::logException($e);
                    }
                } else {
                    $parameters = new Varien_Object($message->getMessageParameters());
                    if ($parameters->getReturnPathEmail() !== null) {
                        $mailTransport = new Zend_Mail_Transport_Sendmail("-f" . $parameters->getReturnPathEmail());
                        Zend_Mail::setDefaultTransport($mailTransport);
                    }

                    $mailer = new Zend_Mail('utf-8');
                    foreach ($message->getRecipients() as $recipient) {
                        list($email, $name, $type) = $recipient;
                        switch ($type) {
                        case self::EMAIL_TYPE_BCC:
                            $mailer->addBcc($email, '=?utf-8?B?' . base64_encode($name) . '?=');
                            break;
                        case self::EMAIL_TYPE_TO:
                        case self::EMAIL_TYPE_CC:
                        default:
                            $mailer->addTo($email, '=?utf-8?B?' . base64_encode($name) . '?=');
                            break;
                        }
                    }

                    if ($parameters->getIsPlain()) {
                        $mailer->setBodyText($message->getMessageBody());
                    } else {
                        $mailer->setBodyHTML($message->getMessageBody());
                    }

                    $mailer->setSubject('=?utf-8?B?' . base64_encode($parameters->getSubject()) . '?=');
                    $mailer->setFrom($parameters->getFromEmail(), $parameters->getFromName());
                    if ($parameters->getReplyTo() !== null) {
                        $mailer->setReplyTo($parameters->getReplyTo());
                    }

                    if ($parameters->getReturnTo() !== null) {
                        $mailer->setReturnPath($parameters->getReturnTo());
                    }

                    try {
                        Mage::dispatchEvent(
                            'fooman_emailattachments_before_send_queue',
                            array(
                                'mailer'         => $mailer,
                                'message'        => $message,
                                'mail_transport' => false

                            )
                        );
                        $mailer->send();
                    } catch (Exception $e) {
                        Mage::logException($e);
                    }

                    unset($mailer);
                    $message->setProcessedAt(Varien_Date::formatDate(true));
                    $message->save();
                }
            }
        }

        return $this;
    }

    /**
     * @param $storeId
     * @return Mandrill_Message|Zend_Mail
     */
    public function getMail($storeId)
    {
        if (!Mage::helper('mailchimp/mandrill')->isMandrillEnabled($storeId)) {
            return null;
        }

        $apiKey = Mage::helper('mailchimp/mandrill')->getMandrillApiKey($storeId);
        Mage::helper('mailchimp/mandrill')->log("store: $storeId API: " . $apiKey, $storeId);
        $mail = new Mandrill_Message($apiKey);
        return $mail;
    }
}
