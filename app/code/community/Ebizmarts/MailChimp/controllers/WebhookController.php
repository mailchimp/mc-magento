<?php
/**
 * MailChimp For Magento
 *
 * @category Ebizmarts_MailChimp
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 5/19/16 3:55 PM
 * @file: WebhookController.php
 */
class Ebizmarts_MailChimp_WebhookController extends Mage_Core_Controller_Front_Action
{

    /**
     * Entry point for all webhook operations
     */
    public function indexAction()
    {
        $requestKey = $this->getRequest()->getParam('wkey');

        //Checking if "wkey" para is present on request, we cannot check for !isPost()
        //because Mailchimp pings the URL (GET request) to validate webhook
        if (!$requestKey) {
            $this->getResponse()
                ->setHeader('HTTP/1.1', '403 Forbidden')
                ->sendResponse();
            return $this;
        }

        $data = $this->getRequest()->getPost('data');
        $myKey = Mage::helper('mailchimp')->getWebhooksKey();

        //Validate "wkey" GET parameter
        if ($myKey == $requestKey) {
            if ($this->getRequest()->getPost('type')) {
                Mage::getModel('mailchimp/processWebhook')->processWebhookData($this->getRequest()->getPost());
            } else {
                Mage::helper('mailchimp')->logError($this->__('Something went wrong with the Webhook Data'));
                Mage::helper('mailchimp')->logError($this->__($data));
            }
        } else {
            Mage::helper('mailchimp')->logError($this->__('Webhook Key invalid! Key Request: %s - My Key: %s', $requestKey, $myKey));
            Mage::helper('mailchimp')->logError($this->__('Webhook call ended'));
        }

    }

}
