<?php
/**
 * mc-magento Magento Component
 *
 * @category  Ebizmarts
 * @package   mc-magento
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     8/30/16 3:31 PM
 * @file:     Mandrill.php
 */
class Ebizmarts_MailChimp_Helper_Mandrill extends Mage_Core_Helper_Abstract
{
    /**
     * @param $message
     * @param $storeId
     */
    public function log($message, $storeId)
    {
        if (Mage::helper('mailchimp/mandrill')->isMandrillLogEnabled($storeId)) {
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
     * Get if Mandrill logs are enabled for given scope.
     *
     * @param  int  $scopeId
     * @param  null $scope
     * @return mixed
     */
    public function isMandrillLogEnabled($scopeId = 0, $scope = null)
    {
        return Mage::helper('mailchimp')->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::MANDRILL_LOG, $scopeId, $scope);
    }

    /**
     * Get if Mandrill module is enabled for given scope.
     *
     * @param  int  $scopeId
     * @param  null $scope
     * @return mixed
     */
    public function isMandrillEnabled($scopeId = 0, $scope = null)
    {
        return Mage::helper('mailchimp')->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::MANDRILL_ACTIVE, $scopeId, $scope);
    }

    /**
     * Get if Mandrill Api Key for given scope.
     * 
     * @param  int  $scopeId
     * @param  null $scope
     * @return mixed
     */
    public function getMandrillApiKey($scopeId = 0, $scope = null)
    {
        return Mage::helper('mailchimp')->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::MANDRILL_APIKEY, $scopeId, $scope);
    }
}