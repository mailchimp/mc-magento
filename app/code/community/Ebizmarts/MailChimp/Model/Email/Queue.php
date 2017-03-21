<?php
/**
 * mc-magento Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 8/30/16 2:46 PM
 * @file: Queue.php
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
        $storeId = Mage::app()->getStore()->getId();

        /** @var $collection Mage_Core_Model_Resource_Email_Queue_Collection */
        $collection = Mage::getModel('core/email_queue')->getCollection()
            ->addOnlyForSendingFilter()
            ->setPageSize(self::MESSAGES_LIMIT_PER_CRON_RUN)
            ->setCurPage(1)
            ->load();

        /** @var $message Mage_Core_Model_Email_Queue */
        foreach ($collection as $message) {
            if ($message->getId()) {
                $parameters = new Varien_Object($message->getMessageParameters());
                $mandrillEnabled = Mage::helper('mailchimp/mandrill')->isEnabled($storeId);

                if ($mandrillEnabled) {
                    // send email via Mandrill
                    $mailer = $this->getMail($storeId);
                } else {
                    // send email via a Zend_Mail transport
                    if ($parameters->getReturnPathEmail() !== null) {
                        $mailTransport = new Zend_Mail_Transport_Sendmail("-f" . $parameters->getReturnPathEmail());
                        Zend_Mail::setDefaultTransport($mailTransport);
                    }

                    $mailer = new Zend_Mail('utf-8');
                }

                /**
                 * DRYer version of setting the mailer values and sending it.
                 * No need repeating it for Mandrill and Zend_Mail
                 */

                $mailer->setFrom($parameters->getFromEmail(), $parameters->getFromName());
                if ($mandrillEnabled) {
                    $mailer->setSubject($parameters->getSubject());
                } else {
                    // Zend_Mail version of subject
                    $mailer->setSubject('=?utf-8?B?' . base64_encode($parameters->getSubject()) . '?=');
                }

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
                                'mailer'            => $mailer,
                                'message'           => $message,
                                'mail_transport'    => false
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
            }
        }

        return $this;
    }

    /**
     * @param $storeId
     * @return Mandrill_Message|null
     */
    public function getMail($storeId = null)
    {
        if (!Mage::helper('mailchimp/mandrill')->isEnabled) {
            return null;
        } else {
            $apiKey = Mage::helper('mailchimp/mandrill')->getMandrillApiKey($storeId);

            Mage::helper('mailchimp/mandrill')->log("store: $storeId API: " . $apiKey);
            $mail = new Mandrill_Message($apiKey);
            return $mail;
        }
    }
}
