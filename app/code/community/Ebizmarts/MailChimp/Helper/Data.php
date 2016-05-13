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

    public function getStoreName()
    {
//        $name = null;
//        if ($code = Mage::getSingleton('adminhtml/config_data')->getStore()) // store level
//        {
//            $store = Mage::getModel('core/store')->load($code);
//            $storeName = $store->getGroup()->getName();
//            $storeViewName = $store->getName();
//            $websiteName = Mage::getModel('core/store')->load($code)->getWebsite()->getName();
//            $name = $websiteName.'-'.$storeName.'-'.$storeViewName;
//        }
//        elseif ($code = Mage::getSingleton('adminhtml/config_data')->getWebsite()) // website level
//        {
//            $websiteName = Mage::getModel('core/website')->load($code)->getName();
//            $name = $websiteName;
//        }
//        else
//        {
            $name = 'Default Scope';
//        }
        return $name;

    }

    public function getStoreId()
    {
//        $id = null;
//        if ($code = Mage::getSingleton('adminhtml/config_data')->getStore()) // store level
//        {
//            $store = Mage::getModel('core/store')->load($code);
//            $storeName = $store->getGroup()->getName();
//            $storeNameCode = strtolower(str_replace(' ', '_', $storeName));
//            $websiteCode = Mage::getModel('core/store')->load($code)->getWebsite()->getCode();
//            $id = $websiteCode.'-'.$storeNameCode.'-'.$code;
//        }
//        elseif ($code = Mage::getSingleton('adminhtml/config_data')->getWebsite()) // website level
//        {
//            $website = Mage::getModel('core/website')->load($code);
//            $websiteCode = $website->getCode();
//            $id = $websiteCode;
//        }
//        else
//        {
            $id = 'default_scope';
//        }
        return $id;
    }

    public function getMailChimpStore(){
        $apiKey = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_APIKEY);
        $api = new Ebizmarts_Mailchimp($apiKey);
        $storeExists = false;
        $storeId = Mage::helper('mailchimp')->getStoreId();
        try {
            $storeExists = $api->ecommerce->stores->get($storeId);

        }catch (Exception $e){
            Mage::log($e->getMessage(), null, 'MailChimp_Errors.log', true);
        }
        return $storeExists;
    }

    public function createMailChimpStore(){
        $apiKey = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_APIKEY);
        $listId = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_LIST);
        $storeId = Mage::helper('mailchimp')->getStoreId();
        $storeName = Mage::helper('mailchimp')->getStoreName();
        $store_email = Mage::helper('mailchimp')->getConfigValue('trans_email/ident_general/email');
        $store_email = 'santiago@ebizmarts.com';
        $currencyCode = Mage::helper('mailchimp')->getConfigValue(Mage_Directory_Model_Currency::XML_PATH_CURRENCY_DEFAULT);

        try{
            $api = new Ebizmarts_Mailchimp($apiKey);
            $response = $api->ecommerce->stores->add($storeId, $listId, $storeName, 'Magento', null, $store_email, $currencyCode);
        }catch(Exception $e){
            Mage::log($e->getMessage(), null, 'MailChimp_Errors.log', true);
        }
        return $response;
    }

    public function log($message)
    {
        Mage::log($message, null, 'MailChimp_Errors.log', true);
    }

}
