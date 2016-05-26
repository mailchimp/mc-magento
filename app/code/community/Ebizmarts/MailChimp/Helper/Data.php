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
        return parse_url(Mage::getBaseUrl(), PHP_URL_HOST);
    }

    /**
     * @return string
     */
    public function getMCStoreId()
    {
        return Mage::getStoreConfig(Ebizmarts_MailChimp_Model_Config::GENERAL_MCSTOREID);
    }

    /**
     * Minimum date for which ecommerce data needs to be re-uploaded.
     */
    public function getMCMinSyncDateFlag()
    {
        return Mage::getStoreConfig(Ebizmarts_MailChimp_Model_Config::GENERAL_MCMINSYNCDATEFLAG);
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

        //generate store id
        $date = Mage::helper('core')->formatDate();
        $store_id = parse_url(Mage::getBaseUrl(), PHP_URL_HOST). '_' . $date;

        //create store in mailchimp
        Mage::getModel('mailchimp/api_stores')->createMailChimpStore($store_id);

        //save in config
        Mage::getConfig()->saveConfig(Ebizmarts_MailChimp_Model_Config::GENERAL_MCSTOREID, $store_id);


        //reset mailchimp minimum date to sync flag
        Mage::getConfig()->saveConfig(Ebizmarts_MailChimp_Model_Config::GENERAL_MCMINSYNCDATEFLAG, $date);
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
