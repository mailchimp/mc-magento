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
    public function renderresendecomAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function resetLocalErrorsAction()
    {
        $helper = $this->makeHelper();
        $mageApp = $helper->getMageApp();
        $request = $mageApp->getRequest();
        $scope = $request->getParam('scope');
        $scopeId = $request->getParam('scope_id');
        $success = 1;

        try {
            $stores = $mageApp->getStores();

            if ($scopeId == 0) {
                foreach ($stores as $store) {
                    $helper->resetErrors($store->getId());
                }
            }

            $helper->resetErrors($scopeId, $scope);
        } catch (Exception $e) {
            $helper->logError($e->getMessage());
            $success = 0;
        }

        $mageApp->getResponse()->setBody($success);
    }

    public function resendEcommerceDataAction()
    {
        $helper = $this->makeHelper();
        $mageApp = $helper->getMageApp();
        $request = $this->getRequest();
        $filters = $request->getParam('filter');
        $scope = $request->getParam('scope');
        $scopeId = $request->getParam('scope_id');
        $success = 0;

        if (is_array($filters) && empty($filters)) {
            $this->addWarning($helper->__('At least one type of eCommerce data should be selected to Resend.'));
            $success = $helper->__('Redirecting... ')
                . '<script type="text/javascript">window.top.location.reload();</script>';
        } else {
            try {
                $helper->resendMCEcommerceData($scopeId, $scope, $filters);

                $this->addSuccess($helper->__('Ecommerce data resent succesfully'));
                $success = $helper->__('Redirecting... ')
                    . '<script type="text/javascript">window.top.location.reload();</script>';
            } catch (MailChimp_Error $e) {
                $helper->logError($e->getFriendlyMessage());
                $this->addError($e->getFriendlyMessage());
            } catch (Exception $e) {
                $helper->logError($e->getMessage());
                $this->addError($e->getMessage());
            }
        }

        $mageApp->getResponse()->setBody($success);
    }

    public function createMergeFieldsAction()
    {
        $helper = $this->makeHelper();
        $mageApp = $helper->getMageApp();
        $request = $mageApp->getRequest();
        $scope = $request->getParam('scope');
        $scopeId = $request->getParam('scope_id');
        $success = 0;
        $subEnabled = $helper->isSubscriptionEnabled($scopeId, $scope);

        if ($subEnabled) {
            $success = $helper->createMergeFields($scopeId, $scope);
        }

        $mageApp->getResponse()->setBody($success);
    }

    protected function _isAllowed()
    {
        switch ($this->getRequest()->getActionName()) {
        case 'resetLocalErrors':
        case 'renderresendecom':
        case 'resendEcommerceData':
        case 'createMergeFields':
            $acl = 'system/config/mailchimp';
            break;
        }

        return $this->getAdminSession()->isAllowed($acl);
    }

    /**
     * @return Ebizmarts_MailChimp_Helper_Data
     */
    protected function makeHelper()
    {
        return Mage::helper('mailchimp');
    }

    /**
     * @return Mage_Adminhtml_Model_Session
     */
    protected function getAdminSession()
    {
        return Mage::getSingleton('admin/session');
    }

    public function addWarning($message)
    {
        Mage::getSingleton('core/session')->addWarning($message);
    }

    public function addSuccess($message)
    {
        Mage::getSingleton('core/session')->addSuccess($message);
    }

    public function addError($message)
    {
        Mage::getSingleton('core/session')->addError($message);
    }
}
