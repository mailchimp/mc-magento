<?php
/**
 * MailChimp For Magento
 *
 * @category Ebizmarts_MailChimp
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 4/29/16 3:55 PM
 * @file: Data.php
 */
class Ebizmarts_MailChimp_Helper_Data extends Mage_Core_Helper_Abstract
{

    /**
     * Get storeId and/or websiteId if scope selected on back end
     *
     * @param null $storeId
     * @param null $websiteId
     * @return array
     */
    public function getConfigScopeId($storeId = null, $websiteId = null)
    {
        $scopeArray = array();
        if ($code = Mage::getSingleton('adminhtml/config_data')->getStore()) // store level
        {
            $storeId = Mage::getModel('core/store')->load($code)->getId();
        }
        elseif ($code = Mage::getSingleton('adminhtml/config_data')->getWebsite()) // website level
        {
            $websiteId = Mage::getModel('core/website')->load($code)->getId();
            $storeId = Mage::app()->getWebsite($websiteId)->getDefaultStore()->getId();
        }
        $scopeArray['websiteId'] = $websiteId;
        $scopeArray['storeId'] = $storeId;
        return $scopeArray;
    }

    /**
     * Get configuration value from back end unless storeId is sent, in this last case it gets the configuration from the store Id sent
     *
     * @param $path
     * @param null $storeId  If this is null, assume the value is asked from back end
     * @return mixed|null
     */
    public function getConfigValue($path, $storeId = null)
    {
        $scopeArray = array();

        //Get store scope for back end or front end
        if(!$storeId) {
            $scopeArray = $this->getConfigScopeId();
        }else{
            $scopeArray['storeId'] = $storeId;
        }
        $configValue = null;
        if ($scopeArray['websiteId']) {
            //Website scope
            if (Mage::app()->getWebsite($scopeArray['websiteId'])->getConfig($path)) {
                $configValue = Mage::app()->getWebsite($scopeArray['websiteId'])->getConfig($path);
            }
        } elseif ($scopeArray['storeId']) {
            //Store view scope
            if (Mage::getStoreConfig($path, $scopeArray['storeId'])) {
                $configValue = Mage::getStoreConfig($path, $scopeArray['storeId']);
            }
        } else {
            //Default config scope
            if (Mage::getStoreConfig($path)) {
                $configValue = Mage::getStoreConfig($path);
            }
        }
        return $configValue;
    }

    /**
     * Get MC store name
     *
     * @return string
     */
    public function getMCStoreName()
    {
        //@toDo return installation name
        return "Default Store Name";
    }

    /**
     * @return string
     */
    public function getMCStoreId()
    {
        //@toDo return generated store id form config
        return "default_store";
    }

    /**
     * Minimum date for which ecommerce data needs to be re-uploaded.
     */
    public function getMCMinSyncDateFlag()
    {
        //@toDo return generated minimum date for sync elements in config
    }

    /**
     * delete MC ecommerce store
     * reset mailchimp store id in the config
     * reset all deltas
     *
     * @param bool|false $deleteDataInMailchimp
     */
    public function resetMCEcommerceData($deleteDataInMailchimp=false)
    {

        //delete store id and data from mailchimp
        if($deleteDataInMailchimp && $this->getMCStoreId() && $this->getMCStoreId() != "")
        {
            Mage::getModel('mailchimp/api_stores')->deleteStore($this->getMCStoreId());
        }

        //@toDo generate a new store id in the config (upload store_id before saving just in case)
        /**
         * FORMAT: <STORE_DOMAIN>_<TIMESTAMP>
         */

        //@toDo reset flag of "minimum date to sync" so sync CRON can start uploading all data again
    }

    /**
     * Check if API key is set and the mailchimp store id was configured
     *
     * @return bool
     */
    public function isEcommerceSyncDataEnabled()
    {
        $api_key = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_APIKEY);

        return !is_null($this->getMCStoreId()) && $this->getMCStoreId() != null
        && !is_null($api_key) && $api_key != "";
    }

    public function log($message)
    {
        Mage::log($message, null, 'MailChimp_Errors.log', true);
    }

    public function getWebhooksKey()
    {
        $crypt = md5((string)Mage::getConfig()->getNode('global/crypt/key'));
        $key = substr($crypt, 0, (strlen($crypt) / 2));

        return $key;
    }

}
