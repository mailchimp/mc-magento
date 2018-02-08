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
        $helper = $this->makeHelper();
        $mageApp = $helper->getMageApp();
        $param = $mageApp->getRequest()->getParam('scope');
        $scopeArray = explode('-', $param);
        $result = 1;
        try {
            $stores = $mageApp->getStores();
            if ($scopeArray[1] == 0) {
                foreach ($stores as $store) {
                    $helper->resetErrors($store->getId());
                }
            }
            $helper->resetErrors($scopeArray[1], $scopeArray[0]);
        } catch(Exception $e)
        {
            $result = 0;
        }

        $mageApp->getResponse()->setBody($result);
    }

    public function resetEcommerceDataAction()
    {
        $helper = $this->makeHelper();
        $mageApp = $helper->getMageApp();
        $param = $mageApp->getRequest()->getParam('scope');
        $scopeArray = explode('-', $param);
        $result = 0;
        try {
            $helper->resetMCEcommerceData($scopeArray[1], $scopeArray[0], true);
            $result = 1;
        } catch(MailChimp_Error $e) {
            $helper->logError($e->getFriendlyMessage());
        } catch(Exception $e) {
            $helper->logError($e->getMessage());
        }

        $mageApp->getResponse()->setBody($result);
    }

    public function resendEcommerceDataAction()
    {
        $helper = $this->makeHelper();
        $mageApp = $helper->getMageApp();
        $param = $mageApp->getRequest()->getParam('scope');
        $scopeArray = explode('-', $param);
        $result = 0;
        try {
            $helper->resetMCEcommerceData($scopeArray[1], $scopeArray[0], false);
            $result = 1;
        } catch(MailChimp_Error $e) {
            $helper->logError($e->getFriendlyMessage(), $scopeArray[1], $scopeArray[0]);
        } catch(Exception $e) {
            $helper->logError($e->getMessage(), $scopeArray[1], $scopeArray[0]);
        }

        $mageApp->getResponse()->setBody($result);
    }

    public function createMergeFieldsAction()
    {
        $helper = $this->makeHelper();
        $mageApp = $helper->getMageApp();
        $param = $mageApp->getRequest()->getParam('scope');
        $scopeArray = explode('-', $param);
        $result = 0;
        $subEnabled = $helper->isSubscriptionEnabled($scopeArray[1], $scopeArray[0]);
        if ($subEnabled) {
            try {
                $helper->createMergeFields($scopeArray[1], $scopeArray[0]);
                $result = 1;
            } catch (MailChimp_Error $e) {
                $helper->logError($e->getFriendlyMessage(), $scopeArray[1], $scopeArray[0]);
            } catch (Exception $e) {
                $helper->logError($e->getMessage(), $scopeArray[1], $scopeArray[0]);
            }
        }

        $mageApp->getResponse()->setBody($result);
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

    /**
     * @return Ebizmarts_MailChimp_Helper_Data
     */
    protected function makeHelper()
    {
        return Mage::helper('mailchimp');
    }
}
