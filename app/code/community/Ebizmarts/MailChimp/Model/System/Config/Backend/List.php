<?php
/**
 * mc-magento Magento Component
 *
 * @category  Ebizmarts
 * @package   mc-magento
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     8/4/16 8:28 PM
 * @file:     List.php
 */
class Ebizmarts_MailChimp_Model_System_Config_Backend_List extends Mage_Core_Model_Config_Data
{
    protected function _afterSave()
    {
        $groups = $this->getData('groups');
        $helper = $this->getMailchimpHelper();
        $scopeId = $this->getScopeId();
        $scope = $this->getScope();
        $moduleIsActive = (isset($groups['general']['fields']['active']['value'])) ? $groups['general']['fields']['active']['value'] : $helper->isMailChimpEnabled($scopeId, $scope);
        $apiKey = (isset($groups['general']['fields']['apikey']['value'])) ? $groups['general']['fields']['apikey']['value'] : $helper->getApiKey($scopeId, $scope);
        $thisScopeHasSubMinSyncDateFlag = $helper->getIfConfigExistsForScope(Ebizmarts_MailChimp_Model_Config::GENERAL_SUBMINSYNCDATEFLAG, $scopeId, $scope);

        if ($this->isValueChanged() && !$this->getValue()) {
            $configValue = array(array(Ebizmarts_MailChimp_Model_Config::GENERAL_ACTIVE, false));
            $helper->saveMailchimpConfig($configValue, $scopeId, $scope);
            $message = $helper->__('Please note the extension has been disabled due to the lack of an api key or list configured.');
            $this->getAdminSession()->addWarning($message);
        }

        if ($this->isValueChanged() && ($moduleIsActive || $thisScopeHasSubMinSyncDateFlag) && $this->getValue())
        {
            $configValues = array(array(Ebizmarts_MailChimp_Model_Config::GENERAL_SUBMINSYNCDATEFLAG, Varien_Date::now()));
            $helper->saveMailchimpConfig($configValues, $scopeId, $scope);
        }

        if (isset($groups['ecommerce']['fields']['active']) && isset($groups['ecommerce']['fields']['active']['value'])) {
            $ecommerceActive = $groups['ecommerce']['fields']['active']['value'];
        } else {
            $ecommerceActive = $helper->isEcommerceEnabled($scopeId, $scope);
        }
        $thisScopeHasMCStoreId = $helper->getIfConfigExistsForScope(Ebizmarts_MailChimp_Model_Config::GENERAL_MCSTOREID, $scopeId, $scope);

        if ($apiKey && $this->isValueChanged() && $thisScopeHasMCStoreId) {
            $helper->removeEcommerceSyncData($scopeId, $scope);
            $helper->resetCampaign($scopeId, $scope);
            $helper->clearErrorGrid($scopeId, $scope, true);
            $helper->deleteStore($scopeId, $scope);
        }

        if ($apiKey && $moduleIsActive && $ecommerceActive && $this->getValue() && !$thisScopeHasMCStoreId) {
            $helper->createStore($this->getValue(), $scopeId, $scope);
        }

        if ($apiKey && $moduleIsActive && $this->isValueChanged()) {
            $helper->handleWebhookChange($scopeId, $scope);
        }
    }

    /**
     * @return Ebizmarts_MailChimp_Helper_Data
     */
    protected function getMailchimpHelper()
    {
        return Mage::helper('mailchimp');
    }

    /**
     * @return Mage_Adminhtml_Model_Session
     */
    protected function getAdminSession()
    {
        return Mage::getSingleton('adminhtml/session');
    }
}
