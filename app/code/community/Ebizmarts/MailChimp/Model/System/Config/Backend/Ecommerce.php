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
class Ebizmarts_MailChimp_Model_System_Config_Backend_Ecommerce extends Mage_Core_Model_Config_Data
{
    protected function _afterSave()
    {
        $groups = $this->getData('groups');
        //If settings are inherited get from config.
        $moduleIsActive = (isset($groups['general']['fields']['active']['value'])) ? $groups['general']['fields']['active']['value'] : Mage::helper('mailchimp')->isMailChimpEnabled($this->getScopeId(), $this->getScope());
        if (isset($groups['general']['fields']['list']) && isset($groups['general']['fields']['list']['value'])) {
            $listId = $groups['general']['fields']['list']['value'];
        } else {
            $listId = Mage::helper('mailchimp')->getGeneralList($this->getScopeId(), $this->getScope());
        }

        $thisScopeHasMCStoreId = Mage::helper('mailchimp')->getIfConfigExistsForScope(Ebizmarts_MailChimp_Model_Config::GENERAL_MCSTOREID, $this->getScopeId(), $this->getScope());

        if ($this->isValueChanged() && $moduleIsActive && $listId && $this->getValue() && !$thisScopeHasMCStoreId) {
            Mage::helper('mailchimp')->createStore($listId, $this->getScopeId(), $this->getScope());
        }
    }
}