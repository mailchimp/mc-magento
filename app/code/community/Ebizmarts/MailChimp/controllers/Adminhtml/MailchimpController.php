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
        $customerId = (int) $this->getRequest()->getParam('id');
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
        } catch(Exception $e)
        {
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


    protected function _isAllowed()
    {
        $acl = null;
        switch ($this->getRequest()->getActionName()) {
            case 'index':
            case 'resendSubscribers':
            case 'createWebhook':
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
}
