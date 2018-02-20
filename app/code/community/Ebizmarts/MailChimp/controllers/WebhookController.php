<?php

/**
 * MailChimp For Magento
 *
 * @category  Ebizmarts_MailChimp
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     5/19/16 3:55 PM
 * @file:     WebhookController.php
 */
class Ebizmarts_MailChimp_WebhookController extends Mage_Core_Controller_Front_Action
{
    private $mailchimpHelper = null;

    /**
     * @return Ebizmarts_MailChimp_Helper_Data|Mage_Core_Helper_Abstract
     */
    protected function getHelper()
    {
        if (!$this->mailchimpHelper) {
            $this->mailchimpHelper = Mage::helper('mailchimp');
        }
        return $this->mailchimpHelper;
    }

    /**
     * Entry point for all webhook operations
     */
    public function indexAction()
    {
        $request = $this->getRequest();
        $requestKey = $request->getParam('wkey');
        $moduleName = $request->getModuleName();
        $data = $request->getPost();
        $helper = $this->getHelper();
        if ($moduleName == 'monkey') {

            if (isset($data['data']['list_id'])) {
                $listId = $data['data']['list_id'];
                $storeIds = $helper->getMagentoStoreIdsByListId($listId);
                if (count($storeIds)) {
                    $storeId = $storeIds[0];
                    if ($helper->isSubscriptionEnabled($storeId)) {
                        try {
                            $api = $helper->getApi($storeId);
                        } catch (Ebizmarts_MailChimp_Helper_Data_ApiKeyException $e) {
                            $helper->logError($e->getMessage());
                            $api = null;
                        }
                        if (!$api) {
                            try {
                                $webhooks = $api->lists->webhooks->getAll($listId);
                                foreach ($webhooks['webhooks'] as $webhook) {
                                    if (strpos($webhook['url'], 'monkey/webhook') !== false) {
                                        $api->lists->webhooks->delete($listId, $webhook['id']);
                                    }
                                }
                            } catch (MailChimp_Error $e) {
                                $helper->logError($e->getFriendlyMessage());

                            }
                        }
                    }
                }
            }
        } else {

            //Checking if "wkey" para is present on request, we cannot check for !isPost()
            //because Mailchimp pings the URL (GET request) to validate webhook
            if (!$requestKey) {
                $this->getResponse()
                    ->setHeader('HTTP/1.1', '403 Forbidden')
                    ->sendResponse();
                return $this;
            }

            $myKey = $helper->getWebhooksKey();

            //Validate "wkey" GET parameter
            if ($myKey == $requestKey) {
                if ($request->getPost('type')) {
                    Mage::getModel('mailchimp/processWebhook')->saveWebhookRequest($data);
                } else {
                    $helper->logError($this->__('Webhook successfully created.'));
                }
            } else {
                $helper->logError($this->__('Webhook Key invalid! Key Request: %s - My Key: %s', $requestKey, $myKey));
                $helper->logError($this->__('Webhook call ended'));
            }
        }
    }

}
