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
     * Get configuration value from back end and front end unless storeId is sent, in this last case it gets the configuration from the store Id sent
     *
     * @param $path
     * @param null $storeId  If this is null it gets the config for the current store (works for back end and front end)
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
        if (isset($scopeArray['websiteId']) && $scopeArray['websiteId']) {
            //Website scope
            if (Mage::app()->getWebsite($scopeArray['websiteId'])->getConfig($path)) {
                $configValue = Mage::app()->getWebsite($scopeArray['websiteId'])->getConfig($path);
            }
        } elseif (isset($scopeArray['storeId']) && $scopeArray['storeId']) {
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
        $ecommerceEnabled = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_ACTIVE);
        //delete store id and data from mailchimp
        if($deleteDataInMailchimp && $this->getMCStoreId() && $this->getMCStoreId() != "")
        {
            Mage::getModel('mailchimp/api_stores')->deleteStore($this->getMCStoreId());
            //clear store config values
            Mage::getConfig()->saveConfig(Ebizmarts_MailChimp_Model_Config::GENERAL_MCSTOREID, null);
        }
        if($ecommerceEnabled) {
            //generate store id
            $date = date('Y-m-d-His');
            $store_id = parse_url(Mage::getBaseUrl(), PHP_URL_HOST) . '_' . $date;

            //create store in mailchimp
            Mage::getModel('mailchimp/api_stores')->createMailChimpStore($store_id);

            //save in config
            Mage::getConfig()->saveConfig(Ebizmarts_MailChimp_Model_Config::GENERAL_MCSTOREID, $store_id);
        }
        //reset mailchimp minimum date to sync flag
        Mage::getConfig()->saveConfig(Ebizmarts_MailChimp_Model_Config::GENERAL_MCMINSYNCDATEFLAG, Varien_Date::now());
            Mage::getConfig()->cleanCache();
    }

    /**
     * Check if API key is set and the mailchimp store id was configured
     *
     * @return bool
     */
    public function isEcomSyncDataEnabled()
    {
        $api_key = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_APIKEY);
        $moduleEnabled = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_ACTIVE);
        $ecommerceEnabled = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_ACTIVE);
        $ret = !is_null($this->getMCStoreId()) && $this->getMCStoreId() != null
            && !is_null($api_key) && $api_key != "" && $moduleEnabled && $ecommerceEnabled;
        return $ret;
    }

    public function logError($message)
    {
        if($this->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_LOG)) {
            Mage::log($message, null, 'MailChimp_Errors.log', true);
        }
    }

    public function logRequest($message)
    {
        if($this->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_LOG)) {
            Mage::log($message, null, 'MailChimp_Requests.log', true);
        }
    }

    public function getWebhooksKey()
    {
        $crypt = md5((string)Mage::getConfig()->getNode('global/crypt/key'));
        $key = substr($crypt, 0, (strlen($crypt) / 2));

        return $key;
    }
    public function resetErrors()
    {
        // reset products with errors
        $collection = Mage::getModel('catalog/product')->getCollection()
            ->addAttributeToFilter(array(
                array('attribute' => 'mailchimp_sync_error', 'neq' => '')
            ), '', 'left');
        foreach ($collection as $product) {
            $product->setData("mailchimp_sync_delta", null);
            $product->setData("mailchimp_sync_error", '');
            $product->setMailchimpUpdateObserverRan(true);
            $product->save();
        }

        // reset subscribers with errors
        $collection = Mage::getModel('newsletter/subscriber')->getCollection()
            ->addFieldToFilter('mailchimp_sync_error', array('neq' => ''));
        foreach ($collection as $subscriber) {
            $subscriber->setData("mailchimp_sync_delta", '0000-00-00 00:00:00');
            $subscriber->setData("mailchimp_sync_error", '');
            $subscriber->save();
        }

        // reset customers with errors
        $collection = Mage::getModel('customer/customer')->getCollection()
//            ->addAttributeToSelect('mailchimp_sync_delta')
            ->addAttributeToFilter(array(
                array('attribute' => 'mailchimp_sync_error', 'neq' => '')
            ));
        foreach ($collection as $customer) {
            $customer->setData("mailchimp_sync_delta", '0000-00-00 00:00:00');
            $customer->setData("mailchimp_sync_error", '');
            $customer->setMailchimpUpdateObserverRan(true);
            $customer->save();
        }

        // reset orders with errors
        $connection = Mage::getSingleton('core/resource')->getConnection('core_write');

        $resource = Mage::getResourceModel('sales/order');
        $connection->update($resource->getMainTable(),array('mailchimp_sync_error'=>'','mailchimp_sync_delta'=>'0000-00-00 00:00:00'),"mailchimp_sync_error <> ''");
        // reset quotes with errors
        $resource = Mage::getResourceModel('sales/quote');
        $connection->update($resource->getMainTable(),array('mailchimp_sync_error'=>'','mailchimp_sync_delta'=>'0000-00-00 00:00:00'),"mailchimp_sync_error <> ''");
        $resource = Mage::getResourceModel('mailchimp/mailchimperrors');
        $connection->query('TRUNCATE TABLE '.$resource->getMainTable());
    }

    /**
     * Get status to send confirmation if Need to Confirm enabled on Magento
     *
     * @param null $subscriber
     * @return string
     */
    public function getStatus($subscriber = null)
    {
        if($this->getConfigValue(Mage_Newsletter_Model_Subscriber::XML_PATH_CONFIRMATION_FLAG) && (!$subscriber || $subscriber->getStatus() != 'subscribed'))
        {
            $status = 'pending';
        }
        else
        {
            $status = 'subscribed';
        }
        return $status;
    }
}
