<?php
/**
 * mc-magento Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 8/30/16 3:31 PM
 * @file: Mandrill.php
 */
class Ebizmarts_MailChimp_Helper_Mandrill extends Mage_Core_Helper_Abstract
{
    /**
     * @param $message
     */
    public function log($message)
    {
        if (Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::MANDRILL_LOG)) {
            Mage::log($message, null, 'Mandrill_Request.log', true);
        }
    }
    /**
     * Get module User-Agent to use on API requests
     *
     * @return string
     */
    public function getUserAgent()
    {
        $modules = Mage::getConfig()->getNode('modules')->children();
        $modulesArray = (array)$modules;

        $aux = (array_key_exists('Enterprise_Enterprise', $modulesArray)) ? 'EE' : 'CE';
        $v = (string)Mage::getConfig()->getNode('modules/Ebizmarts_Mandrill/version');
        $version = strpos(Mage::getVersion(), '-') ? substr(Mage::getVersion(), 0, strpos(Mage::getVersion(), '-')) : Mage::getVersion();
        return (string)'Ebizmarts_Mandrill' . $v . '/Mage' . $aux . $version;
    }

    /**
     * Returns a boolean value to represent if Mandrill is enabled or not.
     *
     * @param  null|string|integer  $storeId
     * @return boolean
     */
    public function isEnabled($storeId = null)
    {
        return (bool) Mage::getStoreConfigFlag(Ebizmarts_MailChimp_Model_Config::MANDRILL_ACTIVE, $storeId);
    }

    /**
     * Returns the Mandrill API Key
     * @param  null|string|integer$storeId [description]
     * @return [type]          [description]
     */
    public function getMandrillApiKey($storeId = null)
    {
        return Mage::getStoreConfig(Ebizmarts_MailChimp_Model_Config::MANDRILL_APIKEY, $storeId);
    }
}
