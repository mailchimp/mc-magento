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

        $newMailchimpStoreId = (isset($groups['general']['fields']['storeid']['value']))
            ? $groups['general']['fields']['storeid']['value']
            : null;

        $oldMailchimpStoreId = $helper->getMCStoreId($scopeId, $scope);
        $isSyncing = $helper->getMCIsSyncing($newMailchimpStoreId, $scopeId, $scope);
        $helper->cancelAllPendingBatches($oldMailchimpStoreId);
        $helper->restoreAllCanceledBatches($newMailchimpStoreId);

        if ($this->isValueChanged() && $this->getValue()) {
            $helper->deletePreviousConfiguredMCStoreLocalData($oldMailchimpStoreId, $scopeId, $scope);

            if ($isSyncing === null) {
                $configValues = array(
                    array(
                        Ebizmarts_MailChimp_Model_Config::GENERAL_MCISSYNCING . "_$newMailchimpStoreId",
                        true
                    )
                );
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
     * @return Ebizmarts_MailChimp_Helper_Date
     */
    protected function makeDateHelper()
    {
        return Mage::helper('mailchimp/date');
    }

    /**
     * @return Mage_Adminhtml_Model_Session
     */
    protected function getAdminSession()
    {
        return Mage::getSingleton('adminhtml/session');
    }
}
