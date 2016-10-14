<?php
/**
 * mc-magento Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 8/4/16 5:56 PM
 * @file: Apikey.php
 */
class Ebizmarts_MailChimp_Model_System_Config_Backend_Apikey extends Mage_Core_Model_Config_Data
{
    protected function _beforeSave()
    {
        $groups = $this->getData('groups');
        $active = (isset($groups['ecommerce']['fields']['active']['value'])) ? $groups['ecommerce']['fields']['active']['value'] : false;
        if ($this->isValueChanged() && $active) {
            if ($this->getScope() == 'default') {
                if ($this->getOldValue()) {
                    Mage::helper('mailchimp')->deleteStore();
                    Mage::helper('mailchimp')->resetErrors();
                    Mage::helper('mailchimp')->resetCampaign();
                }
            }
        }
    }
}