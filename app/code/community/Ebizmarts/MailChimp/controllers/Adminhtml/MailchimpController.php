<?php

/**
 * mc-magento Magento Component
 *
 * @category  Ebizmarts
 * @package   mc-magento
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     5/27/16 1:50 PM
 * @file:     EcommerceController.php
 */
class Ebizmarts_MailChimp_Adminhtml_MailchimpController extends Mage_Adminhtml_Controller_Action
{

    public function indexAction()
    {
        $customerId = (int)$this->getRequest()->getParam('id');
        if ($customerId) {
            $block = $this->getLayout()
                ->createBlock('mailchimp/adminhtml_customer_edit_tab_mailchimp', 'admin.customer.mailchimp')
                ->setCustomerId($customerId)
                ->setUseAjax(true);
            $html = $this->getHtml($block);
            $this->getResponse()->setBody($html);
        }
    }

    public function resendSubscribersAction()
    {
        $helper = $this->makeHelper();
        $mageApp = $helper->getMageApp();
        $request = $mageApp->getRequest();
        $scope = $request->getParam('scope');
        $scopeId = $request->getParam('scope_id');
        $success = 1;

        try {
            $helper->resendSubscribers($scopeId, $scope);
        } catch (Exception $e) {
            $success = 0;
        }

        $mageApp->getResponse()->setBody($success);
    }

    public function createWebhookAction()
    {
        $helper = $this->makeHelper();
        $mageApp = $helper->getMageApp();
        $request = $mageApp->getRequest();
        $scope = $request->getParam('scope');
        $scopeId = $request->getParam('scope_id');
        $listId = $helper->getGeneralList($scopeId);

        $message = $helper->createNewWebhook($scopeId, $scope, $listId);

        $mageApp->getResponse()->setBody($message);
    }

    public function getStoresAction()
    {
        $apiKey = $this->getRequest()->getParam('api_key');

        $data = $this->getSourceStoreOptions($apiKey);
        $jsonData = json_encode($data);

        $response = $this->getResponse();
        $response->setHeader('Content-type', 'application/json');
        $response->setBody($jsonData);
    }

    public function getInfoAction()
    {
        $helper = $this->makeHelper();
        $request = $this->getRequest();
        $mcStoreId = $request->getParam('mailchimp_store_id');
        $apiKey = $request->getParam('api_key');

        $data = $this->getSourceAccountInfoOptions($apiKey, $mcStoreId);
        foreach ($data as $key => $element) {
            $liElement = '';
            if ($element['value'] == Ebizmarts_MailChimp_Model_System_Config_Source_Account::SYNC_LABEL_KEY) {
                $liElement = $helper->getSyncFlagDataHtml($element, $liElement);
                $data[$key]['label'] = $liElement;
            }
        }
        $jsonData = json_encode($data);

        $response = $this->getResponse();
        $response->setHeader('Content-type', 'application/json');
        $response->setBody($jsonData);

    }

    public function getListAction()
    {
        $request = $this->getRequest();
        $apiKey = $request->getParam('api_key');
        $mcStoreId = $request->getParam('mailchimp_store_id');

        $data = $this->getSourceListOptions($apiKey, $mcStoreId);
        $jsonData = json_encode($data);

        $response = $this->getResponse();
        $response->setHeader('Content-type', 'application/json');
        $response->setBody($jsonData);
    }

    public function getInterestAction()
    {
        $request = $this->getRequest();
        $apiKey = $request->getParam('api_key');
        $listId = $request->getParam('list_id');

        $data = $this->getSourceInterestOptions($apiKey, $listId);
        $jsonData = json_encode($data);

        $response = $this->getResponse();
        $response->setHeader('Content-type', 'application/json');
        $response->setBody($jsonData);
    }

    /**
     * @param $mailchimpStoreId
     * @return mixed
     * @throws Mage_Core_Exception
     */
    protected function _getDateSync($mailchimpStoreId)
    {
        return $this->makeHelper()->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_SYNC_DATE . "_$mailchimpStoreId", 0, 'default');
    }

    /**
     * @return mixed
     */
    protected function _isAllowed()
    {
        $acl = null;
        switch ($this->getRequest()->getActionName()) {
            case 'index':
            case 'resendSubscribers':
            case 'createWebhook':
            case 'getStores':
            case 'getList':
            case 'getInfo':
            case 'getInterest':
                $acl = 'system/config/mailchimp';
                break;
        }

        return Mage::getSingleton('admin/session')->isAllowed($acl);
    }

    /**
     * @return Ebizmarts_MailChimp_Helper_Data
     */
    protected function makeHelper()
    {
        return Mage::helper('mailchimp');
    }

    /**
     * @param $block
     * @return mixed
     */
    protected function getHtml($block)
    {
        return $block->toHtml();
    }

    /**
     * @param $apiKey
     * @return Ebizmarts_MailChimp_Model_System_Config_Source_Store
     */
    protected function getSourceStoreOptions($apiKey)
    {
        return Mage::getModel('Ebizmarts_MailChimp_Model_System_Config_Source_Store', array('api_key' => $apiKey))->toOptionArray();
    }

    /**
     * @param $apiKey
     * @param $mcStoreId
     * @return Ebizmarts_MailChimp_Model_System_Config_Source_Account
     */
    protected function getSourceAccountInfoOptions($apiKey, $mcStoreId)
    {
        return Mage::getModel('Ebizmarts_MailChimp_Model_System_Config_Source_Account', array('api_key' => $apiKey, 'mailchimp_store_id' => $mcStoreId))->toOptionArray();
    }

    /**
     * @param $apiKey
     * @param $mcStoreId
     * @return Ebizmarts_MailChimp_Model_System_Config_Source_List
     */
    protected function getSourceListOptions($apiKey, $mcStoreId)
    {
        return Mage::getModel('Ebizmarts_MailChimp_Model_System_Config_Source_List', array('api_key' => $apiKey, 'mailchimp_store_id' => $mcStoreId))->toOptionArray();
    }

    /**
     * @param $apiKey
     * @param $listId
     * @return Ebizmarts_MailChimp_Model_System_Config_Source_CustomerGroup
     */
    protected function getSourceInterestOptions($apiKey, $listId)
    {
        return Mage::getModel('Ebizmarts_MailChimp_Model_System_Config_Source_CustomerGroup', array('api_key' => $apiKey, 'list_id' => $listId))->toOptionArray();
    }
}
