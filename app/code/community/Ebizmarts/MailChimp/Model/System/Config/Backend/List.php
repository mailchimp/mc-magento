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
        $moduleIsActive = (isset($groups['general']['fields']['active']['value'])) ? $groups['general']['fields']['active']['value'] : Mage::helper('mailchimp')->isMailChimpEnabled($this->getScopeId(), $this->getScope());
        $thisScopeHasSubMinSyncDateFlag = Mage::helper('mailchimp')->getIfConfigExistsForScope(Ebizmarts_MailChimp_Model_Config::GENERAL_SUBMINSYNCDATEFLAG, $this->getScopeId(), $this->getScope());

        if ($this->isValueChanged() && ($moduleIsActive || $thisScopeHasSubMinSyncDateFlag) && $this->getValue())
        {
            $configValues = array(array(Ebizmarts_MailChimp_Model_Config::GENERAL_SUBMINSYNCDATEFLAG, Varien_Date::now()));
            Mage::helper('mailchimp')->saveMailchimpConfig($configValues, $this->getScopeId(), $this->getScope());
        }

        if (isset($groups['ecommerce']['fields']['active']) && isset($groups['ecommerce']['fields']['active']['value'])) {
            $ecommerceActive = $groups['ecommerce']['fields']['active']['value'];
        } else {
            $ecommerceActive = Mage::helper('mailchimp')->isEcommerceEnabled($this->getScopeId(), $this->getScope());
        }
        $thisScopeHasMCStoreId = Mage::helper('mailchimp')->getIfConfigExistsForScope(Ebizmarts_MailChimp_Model_Config::GENERAL_MCSTOREID, $this->getScopeId(), $this->getScope());

        if ($this->isValueChanged() && $thisScopeHasMCStoreId) {
            Mage::helper('mailchimp')->removeEcommerceSyncData($this->getScopeId(), $this->getScope());
            Mage::helper('mailchimp')->resetCampaign($this->getScopeId(), $this->getScope());
            Mage::helper('mailchimp')->clearErrorGrid($this->getScopeId(), $this->getScope(), true);
            Mage::helper('mailchimp')->deleteStore($this->getScopeId(), $this->getScope());
        }

        if ($moduleIsActive && $ecommerceActive && $this->getValue() && !$thisScopeHasMCStoreId) {
            Mage::helper('mailchimp')->createStore($this->getValue(), $this->getScopeId(), $this->getScope());
        }
    }
}