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
class Ebizmarts_MailChimp_Model_System_Config_Backend_Active extends Mage_Core_Model_Config_Data
{
    protected function _afterSave()
    {
        $helper = $this->makeHelper();
        $webhookHelper = $this->makeWebhookHelper();
        $scopeId = $this->getScopeId();
        $scope = $this->getScope();
        $groups = $this->getData('groups');

        $apiKey = (isset($groups['general']['fields']['apikey']['value']))
            ? $groups['general']['fields']['apikey']['value']
            : $helper->getApiKey($scopeId, $scope);
        //If settings are inherited get from config.
        if (isset($groups['general']['fields']['list']) && isset($groups['general']['fields']['list']['value'])) {
            $listId = $groups['general']['fields']['list']['value'];
        } else {
            $listId = $helper->getGeneralList($scopeId, $scope);
        }

        if ($this->isValueChanged() && $this->getValue()) {
            if ($apiKey && $listId) {
                $webhookHelper->createNewWebhook($scopeId, $scope, $listId);
            } else {
                $configValue = array(array(Ebizmarts_MailChimp_Model_Config::GENERAL_ACTIVE, false));
                $helper->saveMailchimpConfig($configValue, $scopeId, $scope);
                $message = $helper->__('Please add an api key and select an audience before enabling the extension.');
                $this->getAdminSession()->addError($message);
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
     * @return Ebizmarts_MailChimp_Helper_Webhook
     */
    protected function makeWebhookHelper()
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
