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
class Ebizmarts_MailChimp_Model_System_Config_Backend_Store extends Mage_Core_Model_Config_Data
{
    protected function _afterSave()
    {
        $helper = $this->makeHelper();
        $scopeId = $this->getScopeId();
        $scope = $this->getScope();
        $groups = $this->getData('groups');

        $mailchimpStoreId = (isset($groups['general']['fields']['storeid']['value'])) ? $groups['general']['fields']['storeid']['value'] : $helper->getMCStoreId($scopeId, $scope);
        $isSyncing = $helper->getMCIsSyncing($mailchimpStoreId, $scopeId, $scope);
        $ecommMinSyncDate = $helper->getEcommMinSyncDateFlag($mailchimpStoreId, $scopeId, $scope);

        if ($this->isValueChanged() && $this->getValue()) {
            $helper->deleteConfiguredMCStoreLocalData($mailchimpStoreId, $scopeId, $scope);
            if ($ecommMinSyncDate === null) {
                $configValues = array(array(Ebizmarts_MailChimp_Model_Config::GENERAL_ECOMMMINSYNCDATEFLAG."_$mailchimpStoreId", Varien_Date::now()));
                $helper->saveMailchimpConfig($configValues, 0, 'default');
            }
            if ($isSyncing === null) {
                $configValues = array(array(Ebizmarts_MailChimp_Model_Config::GENERAL_MCISSYNCING . "_$mailchimpStoreId", true));
                $helper->saveMailchimpConfig($configValues, $scopeId, $scope);
            }
        }
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
        return Mage::getSingleton('adminhtml/session');
    }
}
