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
class Ebizmarts_MailChimp_Adminhtml_EcommerceController extends Mage_Adminhtml_Controller_Action
{
    public function resetLocalErrorsAction()
    {
        $param = Mage::app()->getRequest()->getParam('scope');
        $scopeArray = explode('-', $param);
        $result = 1;
        try {
            $stores = Mage::app()->getStores();
            if ($scopeArray[1] == 0) {
                foreach ($stores as $store) {
                    Mage::helper('mailchimp')->resetErrors($store->getId());
                }
            }
            Mage::helper('mailchimp')->resetErrors($scopeArray[1], $scopeArray[0]);
        } catch(Exception $e)
        {
            $result = 0;
        }

        Mage::app()->getResponse()->setBody($result);
    }

    public function resetEcommerceDataAction()
    {
        $param = Mage::app()->getRequest()->getParam('scope');
        $scopeArray = explode('-', $param);
        $result = 1;
        try {
            Mage::helper('mailchimp')->resetMCEcommerceData($scopeArray[1], $scopeArray[0], true);
        }
        catch(MailChimp_Error $e) {
            Mage::helper('mailchimp')->logError($e->getFriendlyMessage(), $scopeArray[1], $scopeArray[0]);
            $result = 0;
        }
        catch(Exception $e) {
            Mage::helper('mailchimp')->logError($e->getMessage(), $scopeArray[1], $scopeArray[0]);
        }

        Mage::app()->getResponse()->setBody($result);
    }

    public function resendEcommerceDataAction()
    {
        $param = Mage::app()->getRequest()->getParam('scope');
        $scopeArray = explode('-', $param);
        $result = 1;
        try {
            Mage::helper('mailchimp')->resetMCEcommerceData($scopeArray[1], $scopeArray[0], false);
        }
        catch(MailChimp_Error $e) {
            Mage::helper('mailchimp')->logError($e->getFriendlyMessage(), $scopeArray[1], $scopeArray[0]);
            $result = 0;
        }
        catch(Exception $e) {
            Mage::helper('mailchimp')->logError($e->getMessage(), $scopeArray[1], $scopeArray[0]);
        }

        Mage::app()->getResponse()->setBody($result);
    }

    public function createMergeFieldsAction()
    {
        $param = Mage::app()->getRequest()->getParam('scope');
        $scopeArray = explode('-', $param);
        $result = 1;
        try {
            Mage::helper('mailchimp')->createMergeFields($scopeArray[1], $scopeArray[0]);
        }
        catch(MailChimp_Error $e) {
            Mage::helper('mailchimp')->logError($e->getFriendlyMessage(), $scopeArray[1], $scopeArray[0]);
            $result = 0;
        }
        catch(Exception $e) {
            Mage::helper('mailchimp')->logError($e->getMessage(), $scopeArray[1], $scopeArray[0]);
        }

        Mage::app()->getResponse()->setBody($result);
    }

    protected function _isAllowed()
    {
        switch ($this->getRequest()->getActionName()) {
        case 'resetLocalErrors':
        case 'resetEcommerceData':
        case 'resendEcommerceData':
        case 'createMergeFields':
            $acl = 'system/config/mailchimp';
            break;
        }

        return Mage::getSingleton('admin/session')->isAllowed($acl);
    }
}