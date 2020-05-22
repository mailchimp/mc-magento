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
        $dateHelper = $this->getMailchimpDateHelper();
        $webhookHelper = $this->getMailchimpWebhookHelper();
        $scopeId = $this->getScopeId();
        $scope = $this->getScope();
        $valueChanged = $this->isValueChanged();

        $moduleIsActive = (isset($groups['general']['fields']['active']['value']))
            ? $groups['general']['fields']['active']['value']
            : $helper->isMailChimpEnabled($scopeId, $scope);
        $apiKey = (isset($groups['general']['fields']['apikey']['value']))
            ? $groups['general']['fields']['apikey']['value']
            : $helper->getApiKey($scopeId, $scope);
        $thisScopeHasSubMinSyncDateFlag = $helper->getIfConfigExistsForScope(
            Ebizmarts_MailChimp_Model_Config::GENERAL_SUBMINSYNCDATEFLAG,
            $scopeId,
            $scope
        );

        if ($valueChanged && !$this->getValue()) {
            $configValue = array(array(Ebizmarts_MailChimp_Model_Config::GENERAL_ACTIVE, false));
            $helper->saveMailchimpConfig($configValue, $scopeId, $scope);
            $message = $helper->__(
                'Please note the extension has been disabled due to the lack of an api key or audience configured.'
            );
            $this->getAdminSession()->addWarning($message);
        }

        if ($valueChanged && ($moduleIsActive || $thisScopeHasSubMinSyncDateFlag) && $this->getValue()) {
            $configValues = array(
                array(
                    Ebizmarts_MailChimp_Model_Config::GENERAL_SUBMINSYNCDATEFLAG,
                    $dateHelper->formatDate(null, "Y-m-d H:i:s")
                )
            );
            $helper->saveMailchimpConfig($configValues, $scopeId, $scope);
        }

        if ($apiKey && $moduleIsActive && $valueChanged) {
            $webhookHelper->handleWebhookChange($scopeId, $scope);
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
     * @return Ebizmarts_MailChimp_Helper_Date
     */
    protected function getMailchimpDateHelper()
    {
        return Mage::helper('mailchimp/date');
    }

    /**
     * @return Ebizmarts_MailChimp_Helper_Webhook
     */
    protected function getMailchimpWebhookHelper()
    {
        return Mage::helper('mailchimp/webhook');
    }

    /**
     * @return Mage_Adminhtml_Model_Session
     */
    protected function getAdminSession()
    {
        return Mage::getSingleton('adminhtml/session');
    }
}
