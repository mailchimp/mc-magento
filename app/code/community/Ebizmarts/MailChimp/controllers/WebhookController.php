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
        Mage::helper('mailchimp')->log(var_export($this->getRequest()->getPost(), true));

        Mage::app()->setCurrentStore(Mage::app()->getDefaultStoreView());

        $data = $this->getRequest()->getPost('data');
        $myKey = Mage::helper('mailchimp')->getWebhooksKey(null, $data['list_id']);

        //Validate "wkey" GET parameter
        if ($this->getRequest()->getPost('type')) {
            Mage::getModel('mailchimp/processwebhook')->processWebhookData($this->getRequest()->getPost());
        } else {
            if ($myKey != $requestKey) {
                Mage::helper('mailchimp')->log($this->__('Webhook Key invalid! Key Request: %s - My Key: %s', $requestKey, $myKey));
            }

            Mage::helper('mailchimp')->log($this->__('Webhook call ended'));
        }


    }

}
