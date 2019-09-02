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
     * @throws Exception
     */
    public function send($email, $name = null, array $variables = array())
    {
        $emailConfig = $this->getDesignConfig();
        $storeId = (integer) $emailConfig->getStore();
        $mandrillHelper = $this->makeMandrillHelper();
        if (!$mandrillHelper->isMandrillEnabled($storeId)) {
            return $this->parentSend($email, $name, $variables);
        }

        if (!$this->isValidForSend()) {
            Mage::logException(new Exception('This letter cannot be sent.')); // translation is intentionally omitted
            return false;
        }

        $emails = array_values((array)$email);
        $names = $this->_getEmailsNames($emails, $name);

        // Get message
        $this->setUseAbsoluteLinks(true);
        $variables['email'] = reset($emails);
        $variables['name'] = reset($names);
        $message = $this->getProcessedTemplate($variables, true);
        $subject = $this->getProcessedTemplateSubject($variables);

        $email = array('subject' => $subject, 'to' => array());
        $setReturnPath = $this->getSendingSetReturnPath();
        switch ($setReturnPath) {
        case 1:
            $returnPathEmail = $this->getSenderEmail();
            break;
        case 2:
            $returnPathEmail = $this->getSendingReturnPathEmail();
            break;
        default:
            $returnPathEmail = null;
            break;
        }

        $mail = $this->getMail();
        $email['to'] = $this->_getEmailsTo($emails, $names, $mail);
        $email['from_email'] = $this->_getEmailFrom($mail);
        $email['from_name'] = $this->getSenderName();
        $email['tags'] = $this->_getEmailsTags($variables);

        $headers = $mail->getHeaders();
        $headers[] = $mandrillHelper->getUserAgent();
        $email['headers'] = $headers;

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
            $result = $this->sendMail($email, $mail);
            $this->_mail = null;
        } catch (Exception $e) {
            $this->_mail = null;
            Mage::logException($e);
            return false;
        }

        return true;
    }

    /**
     * @param $emails
     * @param $name
     * @return array
     */
    protected function _getEmailsNames($emails, $name)
    {
        $names = is_array($name) ? $name : (array)$name;
        $names = array_values($names);
        foreach ($emails as $key => $email) {
            if (!isset($names[$key])) {
                $names[$key] = substr($email, 0, strpos($email, '@'));
            }
        }

        return $names;
    }

    /**
     * @param $emails
     * @param $names
     * @param $mail
     * @return array
     */
    protected function _getEmailsTo($emails, $names, $mail)
    {
        $to = array();
        $max = count($emails);
        for ($i = 0; $i < $max; $i++) {
            if (isset($names[$i])) {
                $to[] = array(
                    'email' => $emails[$i],
                    'name' => $names[$i]
                );
            } else {
                $to[] = array(
                    'email' => $emails[$i],
                    'name' => ''
                );
            }
        }

        foreach ($mail->getBcc() as $bcc) {
            $to[] = array(
                'email' => $bcc,
                'type' => 'bcc'
            );
        }

        return $to;
    }

    /**
     * @param $mail
     * @return mixed
     */
    protected function _getEmailFrom($mail)
    {
        $fromEmail = $this->getSenderEmail();
        $mandrillSenders = $this->getSendersDomains($mail);
        $senderExists = false;

        foreach ($mandrillSenders as $sender) {
            if (isset($sender['domain'])) {
                $emailArray = explode('@', $fromEmail);

                if (count($emailArray) > 1 && $emailArray[1] == $sender['domain']) {
                    $senderExists = true;
                }
            }
        }

        if (!$senderExists) {
            $fromEmail = $this->getGeneralEmail();
        }

        return $fromEmail;
    }

    /**
     * @param $variables
     * @return array|null
     */
    protected function _getEmailsTags($variables)
    {
        $tags = null;

        if (isset($variables['tags']) && !empty($variables['tags'])) {
            $tags = $variables['tags'];
        } else {
            $templateId = (string)$this->getId();
            $templates = parent::getDefaultTemplates();

            if (isset($templates[$templateId]) && isset($templates[$templateId]['label'])) {
                $tags = array(substr($templates[$templateId]['label'], 0, 50));
            } else {
                if ($this->getTemplateCode()) {
                    $tags = array(substr($this->getTemplateCode(), 0, 50));
                } else {
                    if ($templateId) {
                        $tags = array(substr($templateId, 0, 50));
                    } else {
                        $tags = array('default_tag');
                    }
                }
            }
        }

        return $tags;
    }

    /**
     * @return Mandrill_Message|null|Zend_Mail
     * @throws Mage_Core_Model_Store_Exception
     * @throws Mandrill_Error
     */
    public function getMail()
    {
        $helper = $this->makeMandrillHelper();
        $emailConfig = $this->getDesignConfig();
        $storeId = (integer) $emailConfig->getStore();

        if (!$this->isMandrillEnabled($storeId)) {
            return parent::getMail();
        }

        if ($this->_mail) {
            return $this->_mail;
        } else {
            $helper->log("store: $storeId API: " . $helper->getMandrillApiKey($storeId), $storeId);
            return $this->createMandrillMessage($storeId);
        }
    }

    /**
     * @return Ebizmarts_MailChimp_Helper_Mandrill
     */
    protected function makeMandrillHelper()
    {
        return Mage::helper('mailchimp/mandrill');
    }

    /**
     * @param $email
     * @param $name
     * @param array $variables
     * @return bool
     */
    protected function parentSend($email, $name, array $variables)
    {
        return parent::send($email, $name, $variables);
    }

    /**
     * @return mixed
     */
    protected function getSendingSetReturnPath()
    {
        return Mage::getStoreConfig(self::XML_PATH_SENDING_SET_RETURN_PATH);
    }

    /**
     * @param $mail
     * @return mixed
     */
    protected function getSendersDomains($mail)
    {
        $mandrillSenders = array();
        try {
            $mandrillSenders = $mail->senders->domains();
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, 'Mandrill.log', true);
        }

        return $mandrillSenders;
    }

    /**
     * @param $email
     * @param $mail
     * @return mixed
     */
    protected function sendMail($email, $mail)
    {
        $mailSent = false;
        try {
            $mailSent = $mail->messages->send($email);
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, 'Mandrill.log', true);
        }

        return $mailSent;
    }

    /**
     * @return mixed
     */
    protected function getSendingReturnPathEmail()
    {
        return Mage::getStoreConfig(self::XML_PATH_SENDING_RETURN_PATH_EMAIL);
    }

    /**
     * @return mixed
     */
    protected function getGeneralEmail()
    {
        return Mage::getStoreConfig('trans_email/ident_general/email');
    }

    /**
     * @param $storeId
     * @return mixed
     */
    protected function isMandrillEnabled($storeId)
    {
        return Mage::getStoreConfig(Ebizmarts_MailChimp_Model_Config::MANDRILL_ACTIVE, $storeId);
    }

    /**
     * @param $storeId
     * @return Mandrill_Message
     * @throws Mandrill_Error
     */
    protected function createMandrillMessage($storeId)
    {
        return $this->_mail = new Mandrill_Message($this->makeMandrillHelper()->getMandrillApiKey($storeId));
    }
}
