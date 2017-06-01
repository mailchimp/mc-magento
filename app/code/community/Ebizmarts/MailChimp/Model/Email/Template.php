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
 * @file:     Template.php
 */
class Ebizmarts_MailChimp_Model_Email_Template extends Ebizmarts_MailChimp_Model_Email_TemplateBase
{
    protected $_mail = null;

    /**
     * @param array|string $email
     * @param null         $name
     * @param array        $variables
     * @return bool
     */
    public function send($email, $name = null, array $variables = array())
    {
        if (!Mage::getStoreConfig(Ebizmarts_MailChimp_Model_Config::MANDRILL_ACTIVE)) {
            return parent::send($email, $name, $variables);
        }

        if (!$this->isValidForSend()) {
            Mage::logException(new Exception('This letter cannot be sent.')); // translation is intentionally omitted
            return false;
        }

        $emails = array_values((array)$email);
        $names = is_array($name) ? $name : (array)$name;
        $names = array_values($names);
        foreach ($emails as $key => $email) {
            if (!isset($names[$key])) {
                $names[$key] = substr($email, 0, strpos($email, '@'));
            }
        }

        // Get message
        $this->setUseAbsoluteLinks(true);
        $variables['email'] = reset($emails);
        $variables['name'] = reset($names);
        $message = $this->getProcessedTemplate($variables, true);
        $subject = $this->getProcessedTemplateSubject($variables);

        $email = array('subject' => $subject, 'to' => array());
        $setReturnPath = Mage::getStoreConfig(self::XML_PATH_SENDING_SET_RETURN_PATH);
        switch ($setReturnPath) {
        case 1:
            $returnPathEmail = $this->getSenderEmail();
            break;
        case 2:
            $returnPathEmail = Mage::getStoreConfig(self::XML_PATH_SENDING_RETURN_PATH_EMAIL);
            break;
        default:
            $returnPathEmail = null;
            break;
        }

        $mail = $this->getMail();
        $max = count($emails);
        for ($i = 0; $i < $max; $i++) {
            if (isset($names[$i])) {
                $email['to'][] = array(
                    'email' => $emails[$i],
                    'name' => $names[$i]
                );
            } else {
                $email['to'][] = array(
                    'email' => $emails[$i],
                    'name' => ''
                );
            }
        }

        foreach ($mail->getBcc() as $bcc) {
            $email['to'][] = array(
                'email' => $bcc,
                'type' => 'bcc'
            );
        }

        $email['from_name'] = $this->getSenderName();
        $email['from_email'] = $this->getSenderEmail();
        $mandrillSenders = $mail->senders->domains();
        $senderExists = false;
        foreach ($mandrillSenders as $sender)
        {
            if (isset($sender['domain'])) {
                $emailArray = explode('@', $email['from_email']);
                if (count($emailArray) > 1 && $emailArray[1] == $sender['domain']) {
                    $senderExists = true;
                }
            }
        }

        if(!$senderExists) {
            $email['from_email'] = Mage::getStoreConfig('trans_email/ident_general/email');
        }

        $headers = $mail->getHeaders();
        $headers[] = Mage::helper('mailchimp/mandrill')->getUserAgent();
        $email['headers'] = $headers;
        if (isset($variables['tags']) && count($variables['tags'])) {
            $email ['tags'] = $variables['tags'];
        }

        if (isset($variables['tags']) && count($variables['tags'])) {
            $email ['tags'] = $variables['tags'];
        } else {
            $templateId = (string)$this->getId();
            $templates = parent::getDefaultTemplates();
            if (isset($templates[$templateId]) && isset($templates[$templateId]['label'])) {
                $email ['tags'] = array(substr($templates[$templateId]['label'], 0, 50));
            } else {
                if ($this->getTemplateCode()) {
                    $email ['tags'] = array(substr($this->getTemplateCode(), 0, 50));
                } else {
                    if ($templateId) {
                        $email ['tags'] = array(substr($templateId, 0, 50));
                    } else {
                        $email['tags'] = array('default_tag');
                    }
                }
            }
        }

        if ($att = $mail->getAttachments()) {
            $email['attachments'] = $att;
        }

        if ($this->isPlain()) {
            $email['text'] = $message;
        } else {
            $email['html'] = $message;
        }

        if ($this->hasQueue() && $this->getQueue() instanceof Mage_Core_Model_Email_Queue) {
            $emailQueue = $this->getQueue();
            $emailQueue->clearRecipients();
            $emailQueue->setMessageBody($message);
            $emailQueue->setMessageParameters(
                array(
                'subject'           => $subject,
                'return_path_email' => $returnPathEmail,
                'is_plain'          => $this->isPlain(),
                'from_email'        => $this->getSenderEmail(),
                'from_name'         => $this->getSenderName()
                )
            )
                ->addRecipients($emails, $names, Mage_Core_Model_Email_Queue::EMAIL_TYPE_TO)
                ->addRecipients($this->_bccEmails, array(), Mage_Core_Model_Email_Queue::EMAIL_TYPE_BCC);
            $emailQueue->addMessageToQueue();
            return true;
        }

        try {
            $result = $mail->messages->send($email);
        } catch (Exception $e) {
            Mage::logException($e);
            return false;
        }

        return true;

    }

    /**
     * @return Mandrill_Message|Zend_Mail
     */
    public function getMail()
    {
        $storeId = Mage::app()->getStore()->getId();
        if (!Mage::getStoreConfig(Ebizmarts_MailChimp_Model_Config::MANDRILL_ACTIVE, $storeId)) {
            return parent::getMail();
        }

        if ($this->_mail) {
            return $this->_mail;
        } else {
            $storeId = Mage::app()->getStore()->getId();
            Mage::helper('mailchimp/mandrill')->log("store: $storeId API: " . Mage::getStoreConfig(Ebizmarts_MailChimp_Model_Config::MANDRILL_APIKEY, $storeId), $storeId);
            $this->_mail = new Mandrill_Message(Mage::getStoreConfig(Ebizmarts_MailChimp_Model_Config::MANDRILL_APIKEY, $storeId));
            return $this->_mail;
        }
    }
}