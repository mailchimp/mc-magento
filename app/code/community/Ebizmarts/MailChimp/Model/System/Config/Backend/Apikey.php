<?php
/**
 * mc-magento Magento Component
 *
 * @category  Ebizmarts
 * @package   mc-magento
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     8/4/16 5:56 PM
 * @file:     Apikey.php
 */
class Ebizmarts_MailChimp_Model_System_Config_Backend_Apikey extends Mage_Core_Model_Config_Data
{
    protected function _afterSave()
    {
        $helper = $this->makeHelper();
        $groups = $this->getData('groups');
        $moduleIsActive = (isset($groups['general']['fields']['active']['value'])) ? $groups['general']['fields']['active']['value'] : $helper->isMailChimpEnabled($this->getScopeId(), $this->getScope());
        $thisScopeHasMCStoreId = $helper->getIfConfigExistsForScope(Ebizmarts_MailChimp_Model_Config::GENERAL_MCSTOREID, $this->getScopeId(), $this->getScope());

        if ($this->isValueChanged() && $moduleIsActive && $thisScopeHasMCStoreId) {
            $helper->removeEcommerceSyncData($this->getScopeId(), $this->getScope());
            $helper->resetCampaign($this->getScopeId(), $this->getScope());
            $helper->clearErrorGrid($this->getScopeId(), $this->getScope(), true);
            $helper->deleteStore($this->getScopeId(), $this->getScope());
        }
    }

    /**
     * @return Mage_Core_Helper_Abstract
     */
    protected function makeHelper()
    {
        return Mage::helper('mailchimp');
    }
}
