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
        $scopeId = $this->getScopeId();
        $scope = $this->getScope();
        $helper = $this->makeHelper();
        $groups = $this->getData('groups');
        //If settings are inherited get from config.
        $moduleIsActive = (isset($groups['general']['fields']['active']['value']))
            ? $groups['general']['fields']['active']['value']
            : $helper->isMailChimpEnabled($this->getScopeId(), $this->getScope());
        $apiKey = (isset($groups['general']['fields']['apikey']['value']))
            ? $groups['general']['fields']['apikey']['value']
            : $helper->getApiKey($this->getScopeId(), $this->getScope());
        if (isset($groups['general']['fields']['list']) && isset($groups['general']['fields']['list']['value'])) {
            $listId = $groups['general']['fields']['list']['value'];
        } else {
            $listId = $helper->getGeneralList($this->getScopeId(), $this->getScope());
        }

        if ((!$apiKey || !$moduleIsActive || !$listId) && $this->isValueChanged() && $this->getValue()) {
            $configValue = array(array(Ebizmarts_MailChimp_Model_Config::GENERAL_ACTIVE, false));
            $helper->saveMailchimpConfig($configValue, $scopeId, $scope);
            $message = $helper->__('Please add an api key and select an audience before enabling the extension.');
            $helper->getAdminSession()->addError($message);
        }
    }

    /**
     * @return Ebizmarts_MailChimp_Helper_Data
     */
    protected function makeHelper()
    {
        return Mage::helper('mailchimp');
    }
}
