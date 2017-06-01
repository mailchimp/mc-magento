<?php
/**
 * mc-magento Magento Component
 *
 * @category  Ebizmarts
 * @package   mc-magento
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     2/3/17 4:40 PM
 * @file:     Name.php
 */
class Ebizmarts_MailChimp_Model_System_Config_Backend_Name extends Mage_Core_Model_Config_Data
{
    protected function _afterSave() 
    {
        if ($this->isValueChanged()) {
            $name = $this->getValue();
            if ($name == '') {
                Mage::getConfig()->cleanCache();
                $name = Mage::app()->getDefaultStoreView()->getWebsite()->getDefaultStore()->getFrontendName();
            }

            Mage::helper('mailchimp')->changeName($name, $this->getScopeId(), $this->getScope());
        }
    }
}