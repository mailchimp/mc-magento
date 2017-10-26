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
class Ebizmarts_MailChimp_Model_System_Config_Backend_Twowaysync extends Mage_Core_Model_Config_Data
{
    protected function _afterSave()
    {
        $helper = $this->getHelper();
        $moduleIsActive = (isset($groups['general']['fields']['active']['value'])) ? $groups['general']['fields']['active']['value'] : $helper->isMailChimpEnabled($this->getScopeId(), $this->getScope());
        $syncingFlag = $helper->getMCIsSyncing($this->getScopeId(), $this->getScope());
        $ecommerceEnabled = $helper->isEcommerceEnabled($this->getScopeId(), $this->getScope());
        $listId = $helper->getGeneralList($this->getScopeId(), $this->getScope());
        if ($moduleIsActive && (!$ecommerceEnabled && $listId || $syncingFlag != 1)) {
            $helper->handleWebhookChange($this->getScopeId(), $this->getScope());
        }
    }

    protected function getHelper()
    {
        return Mage::helper('mailchimp');
    }
}