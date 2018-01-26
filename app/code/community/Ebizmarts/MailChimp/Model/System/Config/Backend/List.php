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
        $helper = $this->getMailchimpHelper();
        $moduleIsActive = (isset($groups['general']['fields']['active']['value'])) ? $groups['general']['fields']['active']['value'] : $helper->isMailChimpEnabled($this->getScopeId(), $this->getScope());
        $thisScopeHasSubMinSyncDateFlag = $helper->getIfConfigExistsForScope(Ebizmarts_MailChimp_Model_Config::GENERAL_SUBMINSYNCDATEFLAG, $this->getScopeId(), $this->getScope());

        if ($this->isValueChanged() && ($moduleIsActive || $thisScopeHasSubMinSyncDateFlag) && $this->getValue())
        {
            $configValues = array(array(Ebizmarts_MailChimp_Model_Config::GENERAL_SUBMINSYNCDATEFLAG, Varien_Date::now()));
            $helper->saveMailchimpConfig($configValues, $this->getScopeId(), $this->getScope());
        }

        if (isset($groups['ecommerce']['fields']['active']) && isset($groups['ecommerce']['fields']['active']['value'])) {
            $ecommerceActive = $groups['ecommerce']['fields']['active']['value'];
        } else {
            $ecommerceActive = $helper->isEcommerceEnabled($this->getScopeId(), $this->getScope());
        }
        $thisScopeHasMCStoreId = $helper->getIfConfigExistsForScope(Ebizmarts_MailChimp_Model_Config::GENERAL_MCSTOREID, $this->getScopeId(), $this->getScope());

        if ($this->isValueChanged() && $thisScopeHasMCStoreId) {
            $helper->removeEcommerceSyncData($this->getScopeId(), $this->getScope());
            $helper->resetCampaign($this->getScopeId(), $this->getScope());
            $helper->clearErrorGrid($this->getScopeId(), $this->getScope(), true);
            $helper->deleteStore($this->getScopeId(), $this->getScope());
        }

        if ($moduleIsActive && $ecommerceActive && $this->getValue() && !$thisScopeHasMCStoreId) {
            $helper->createStore($this->getValue(), $this->getScopeId(), $this->getScope());
        }

        if ($moduleIsActive && $this->isValueChanged()) {
            $helper->handleWebhookChange($this->getScopeId(), $this->getScope());
        }
    }

    /**
     * @return Ebizmarts_MailChimp_Helper_Data
     */
    protected function getMailchimpHelper()
    {
        return Mage::helper('mailchimp');
    }
}
