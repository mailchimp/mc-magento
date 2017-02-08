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
    protected function _getConfigScopeId($storeId = null, $websiteId = null)
    {
        $scopeArray = array();
        if ($code = Mage::getSingleton('adminhtml/config_data')->getStore()) {
            // store level
            $storeId = Mage::getModel('core/store')->load($code)->getId();
        } elseif ($code = Mage::getSingleton('adminhtml/config_data')->getWebsite()) {
            // website level
            $websiteId = Mage::getModel('core/website')->load($code)->getId();
            $storeId = Mage::app()->getWebsite($websiteId)->getDefaultStore()->getId();
        }

        $scopeArray['websiteId'] = $websiteId;
        $scopeArray['storeId'] = $storeId;
        return $scopeArray;
    }

    /**
     *  Get configuration value from back end and front end unless storeId is sent, in this last case it gets the configuration from the store Id sent
     *
     * @param $path
     * @param null $storeId If this is null it gets the config for the current store (works for back end and front end)
     * @param bool $returnParentValueIfNull
     * @return mixed|null
     * @throws Mage_Core_Exception
     */
    public function getConfigValue($path, $storeId = null, $returnParentValueIfNull = false)
    {
        $scopeArray = array();
        $configValue = null;

        //Get store scope for back end or front end
        if (!$storeId) {
            $scopeArray = $this->_getConfigScopeId();
        } else {
            $scopeArray['storeId'] = $storeId;
        }

        if (!$returnParentValueIfNull) {
            if (isset($scopeArray['websiteId']) && $scopeArray['websiteId']) {
                //Website scope
                if (Mage::app()->getWebsite($scopeArray['websiteId'])->getConfig($path) !== null) {
                    $configValue = Mage::app()->getWebsite($scopeArray['websiteId'])->getConfig($path);
                }
            } elseif (isset($scopeArray['storeId']) && $scopeArray['storeId']) {
                //Store view scope
                if (Mage::getStoreConfig($path, $scopeArray['storeId']) !== null) {
                    $configValue = Mage::getStoreConfig($path, $scopeArray['storeId']);
                }
            } else {
                //Default config scope
                if (Mage::getStoreConfig($path) !== null) {
                    $configValue = Mage::getStoreConfig($path);
                }
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
        $storeView = Mage::app()->getDefaultStoreView();
        return $storeView->getWebsite()->getDefaultStore()->getFrontendName();
    }

    /**
     * Get local store_id value of the MC store.
     *
     * @return string
     */
    public function getMCStoreId()
    {
        return Mage::getStoreConfig(Ebizmarts_MailChimp_Model_Config::GENERAL_MCSTOREID);
    }

    /**
     * Get local is_syncing value of the MC store.
     *
     * @return mixed
     */
    public function getMCIsSyncing()
    {
        return Mage::getStoreConfig(Ebizmarts_MailChimp_Model_Config::GENERAL_MCISSYNCING);
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
        $apikey = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_APIKEY);
        $listId = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_LIST);
        //delete store id and data from mailchimp
        if ($deleteDataInMailchimp && $this->getMCStoreId() && $this->getMCStoreId() != "") {
            try {
                Mage::getModel('mailchimp/api_stores')->deleteStore($this->getMCStoreId());
            } catch(Mailchimp_Error $e) {
                Mage::helper('mailchimp')->logError($e->getFriendlyMessage());
            }

            //clear store config values
            Mage::getConfig()->deleteConfig(Ebizmarts_MailChimp_Model_Config::GENERAL_MCSTOREID);
        }

        if ($ecommerceEnabled && $apikey && $listId) {
            $this->createStore($listId);
        }

        //reset mailchimp minimum date to sync flag
        Mage::getConfig()->saveConfig(Ebizmarts_MailChimp_Model_Config::GENERAL_MCMINSYNCDATEFLAG, Varien_Date::now());
        Mage::getConfig()->saveConfig(Ebizmarts_MailChimp_Model_Config::GENERAL_MCSTORE_RESETED, 1);
        Mage::getConfig()->cleanCache();
    }

    /**
     * Check if API key is set and the mailchimp store id was configured
     *
     * @return bool
     */
    public function isEcomSyncDataEnabled()
    {
        $apiKey = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_APIKEY);
        $moduleEnabled = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_ACTIVE);
        $ecommerceEnabled = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_ACTIVE);
        $ret = !is_null($this->getMCStoreId()) && $this->getMCStoreId() != null
            && !is_null($apiKey) && $apiKey != "" && $moduleEnabled && $ecommerceEnabled;
        return $ret;
    }

    /**
     * Save error response from MailChimp's API in "MailChimp_Error.log" file.
     * 
     * @param $message
     */
    public function logError($message)
    {
        if ($this->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_LOG)) {
            Mage::log($message, null, 'MailChimp_Errors.log', true);
        }
    }

    /**
     * Save request made to MailChimp's API in "MailChimp_Requests.log" file.
     * 
     * @param $message
     */
    public function logRequest($message, $batchId=null)
    {
        if ($this->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_LOG)) {
            if (!$batchId) {
                Mage::log($message, null, 'MailChimp_Requests.log', true);
            } else {
                $logDir  = Mage::getBaseDir('var') . DS . 'log';
                $fileName = $logDir.DS.$batchId.'.Request.log';
                file_put_contents($fileName, $message);
            }
        }
    }

    /**
     * @return string
     */
    public function getWebhooksKey()
    {
        $crypt = md5((string)Mage::getConfig()->getNode('global/crypt/key'));
        $key = substr($crypt, 0, (strlen($crypt) / 2));

        return $key;
    }

    /**
     * Reset error messages from Products, Subscribers, Customers, Orders, Quotes and set them to be sent again.
     */
    public function resetErrors()
    {
        // reset products with errors
        $collection = Mage::getModel('catalog/product')->getCollection()
            ->addAttributeToFilter(
                array(
                array('attribute' => 'mailchimp_sync_error', 'neq' => '')
                ), '', 'left'
            );
        foreach ($collection as $product) {
            $product->setData("mailchimp_sync_delta", null);
            $product->setData("mailchimp_sync_error", '');
            $resource = $product->getResource();
            $resource->saveAttribute($product, 'mailchimp_sync_delta');
            $resource->saveAttribute($product, 'mailchimp_sync_error');
//            $product->setMailchimpUpdateObserverRan(true);
//            $product->save();
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
            ->addAttributeToFilter(
                array(
                array('attribute' => 'mailchimp_sync_error', 'neq' => '')
                )
            );
        foreach ($collection as $customer) {
            $customer->setData("mailchimp_sync_delta", '0000-00-00 00:00:00');
            $customer->setData("mailchimp_sync_error", '');
            $resource = $customer->getResource();
            $resource->saveAttribute($customer, 'mailchimp_sync_delta');
            $resource->saveAttribute($customer, 'mailchimp_sync_error');
//            $customer->setMailchimpUpdateObserverRan(true);
//            $customer->save();
        }

        // reset orders with errors
        $connection = Mage::getSingleton('core/resource')->getConnection('core_write');

        $resource = Mage::getResourceModel('sales/order');
        $connection->update($resource->getMainTable(), array('mailchimp_sync_error'=>'','mailchimp_sync_delta'=>'0000-00-00 00:00:00'), "mailchimp_sync_error <> ''");
        // reset quotes with errors
        $resource = Mage::getResourceModel('sales/quote');
        $connection->update($resource->getMainTable(), array('mailchimp_sync_error'=>'','mailchimp_sync_delta'=>'0000-00-00 00:00:00'), "mailchimp_sync_error <> ''");
        $errorCollection = Mage::getModel('mailchimp/mailchimperrors')->getCollection();
        foreach ($errorCollection as $item) {
            $item->delete();
        }
    }

    public function resetCampaign()
    {
        $orderCollection = Mage::getModel('sales/order')->getCollection()
            ->addFieldToFilter(
                'mailchimp_campaign_id', array(
                array('neq'=>0))
            )
            ->addFieldToFilter(
                'mailchimp_campaign_id', array(
                array('notnull'=>true)
                )
            );
        foreach ($orderCollection as $order) {
            $order->setMailchimpCampaignId(0);
            $order->save();
        }
    }
    public function createStore($listId)
    {
        if ($listId) {
            //generate store id
            $date = date('Y-m-d-His');
            $storeId = md5(parse_url(Mage::getBaseUrl(), PHP_URL_HOST) . '_' . $date);
            //create store in mailchimp
            try {
                Mage::getModel('mailchimp/api_stores')->createMailChimpStore($storeId, $listId);
                //save in config
                Mage::getConfig()->saveConfig(Ebizmarts_MailChimp_Model_Config::GENERAL_MCSTOREID, $storeId);
                Mage::getConfig()->saveConfig(Ebizmarts_MailChimp_Model_Config::GENERAL_MCISSYNCING, 1);
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
    }
    public function deleteStore()
    {
        $MCStoreId = $this->getMCStoreId();
        if (!empty($MCStoreId)) {
            try {
                Mage::getModel('mailchimp/api_stores')->deleteStore($MCStoreId);
            } catch (Mailchimp_Error $e) {
                Mage::helper('mailchimp')->logError($e->getFriendlyMessage());
            }

            //clear store config values
            Mage::getConfig()->deleteConfig(Ebizmarts_MailChimp_Model_Config::GENERAL_MCSTOREID);
        }
    }

    public function createMergeFields()
    {
        $listId = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_LIST);
        $maps = unserialize(Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_MAP_FIELDS));
        $customFieldTypes = unserialize(
            Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_CUSTOM_MAP_FIELDS)
        );
        $api = Mage::helper('mailchimp')->getApi();
        if ($api) {
            try {
                $mailchimpFields = $api->lists->mergeFields->getAll($listId, null, null, 50);
            } catch (Mailchimp_Error $e) {
                Mage::helper('mailchimp')->logError($e->getFriendlyMessage());
            }

            if (count($mailchimpFields) > 0) {
                foreach ($maps as $map) {
                    $customAtt = $map['magento'];
                    $chimpTag = $map['mailchimp'];
                    $alreadyExists = false;
                    $created = false;
                    foreach ($mailchimpFields['merge_fields'] as $mailchimpField) {
                        if ($mailchimpField['tag'] == $chimpTag || strtoupper($chimpTag) == 'EMAIL') {
                            $alreadyExists = true;
                        }
                    }

                    if (!$alreadyExists) {
                        foreach ($customFieldTypes as $customFieldType) {
                            if ($customFieldType['value'] == $chimpTag) {
                                try {
                                    $api->lists->mergeFields->add($listId, $customFieldType['label'], $customFieldType['field_type'], null, $chimpTag);
                                } catch (Mailchimp_Error $e) {
                                    Mage::helper('mailchimp')->logError($e->getFriendlyMessage());
                                }

                                $created = true;
                            }
                        }

                        if (!$created) {
                            $attrSetId = Mage::getResourceModel('eav/entity_attribute_collection')
                                ->setEntityTypeFilter(1)
                                ->addSetInfo()
                                ->getData();
                            $label = null;
                            foreach ($attrSetId as $option) {
                                if ($option['attribute_id'] == $customAtt && $option['frontend_label']) {
                                    $label = $option['frontend_label'];
                                }
                            }

                            try {
                                if ($label) {
                                    //Shipping and Billing Address
                                    if ($customAtt == 13 || $customAtt == 14) {
                                        $api->lists->mergeFields->add($listId, $label, 'address', null, $chimpTag);
                                        //Birthday
                                    } elseif ($customAtt == 11) {
                                        $api->lists->mergeFields->add($listId, $label, 'date', null, $chimpTag);
                                    } else {
                                        $api->lists->mergeFields->add($listId, $label, 'text', null, $chimpTag);
                                    }
                                }
                            } catch (Mailchimp_Error $e) {
                                Mage::helper('mailchimp')->logError($e->getFriendlyMessage());
                            }
                        }
                    }
                }
            }
        }
    }

    public function getDateMicrotime()
    {
        $microtime = explode(' ', microtime());
        $msec = $microtime[0];
        $msecArray = explode('.', $msec);
        $date = date('Y-m-d-H-i-s') . '-' . $msecArray[1];
        return $date;
    }

    public function getApi()
    {
        $apiKey = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_APIKEY);
        $api = null;
        if ($apiKey != null && $apiKey != "") {
            $api = new Ebizmarts_Mailchimp($apiKey, null, 'Mailchimp4Magento' . (string)Mage::getConfig()->getNode('modules/Ebizmarts_MailChimp/version'));
        }

        return $api;
    }
    public function changeName($name)
    {
        if (Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_ACTIVE)) {
            try {
                Mage::getModel('mailchimp/api_stores')->modifyName($name);
            } catch (Mailchimp_Error $e) {
                Mage::helper('mailchimp')->logError($e->getFriendlyMessage());
            }
        }
    }
}
