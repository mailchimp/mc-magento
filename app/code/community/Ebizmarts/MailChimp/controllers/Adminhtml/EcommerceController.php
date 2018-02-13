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
        } catch(Exception $e)
        {
            $success = 0;
        }

        $mageApp->getResponse()->setBody($success);
    }

    public function resetEcommerceDataAction()
    {
        $helper = $this->makeHelper();
        $mageApp = $helper->getMageApp();
        $request = $mageApp->getRequest();
        $scope = $request->getParam('scope');
        $scopeId = $request->getParam('scope_id');
        $success = 0;
        try {
            $helper->resetMCEcommerceData($scopeId, $scope, true);
            $success = 1;
        } catch(MailChimp_Error $e) {
            $helper->logError($e->getFriendlyMessage());
        } catch(Exception $e) {
            $helper->logError($e->getMessage());
        }

        $mageApp->getResponse()->setBody($success);
    }

    public function resendEcommerceDataAction()
    {
        $helper = $this->makeHelper();
        $mageApp = $helper->getMageApp();
        $request = $mageApp->getRequest();
        $scope = $request->getParam('scope');
        $scopeId = $request->getParam('scope_id');
        $success = 0;
        try {
            $helper->resetMCEcommerceData($scopeId, $scope, false);
            $success = 1;
        } catch(MailChimp_Error $e) {
            $helper->logError($e->getFriendlyMessage());
        } catch(Exception $e) {
            $helper->logError($e->getMessage());
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
            try {
                $helper->createMergeFields($scopeId, $scope);
                $success = 1;
            } catch (MailChimp_Error $e) {
                $helper->logError($e->getFriendlyMessage());
            } catch (Exception $e) {
                $helper->logError($e->getMessage());
            }
        }

        $mageApp->getResponse()->setBody($success);
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
