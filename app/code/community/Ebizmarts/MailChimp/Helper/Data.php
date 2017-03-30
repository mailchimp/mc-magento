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
     * Get Config value for certain scope.
     * 
     * @param $path
     * @param $scopeId
     * @param null $scope
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function getConfigValueForScope($path, $scopeId, $scope = null)
    {
        if ($scope == 'websites') {
            $configValue = Mage::app()->getWebsite($scopeId)->getConfig($path);
        } else {
            $configValue = Mage::getStoreConfig($path, $scopeId);
        }
        return $configValue;
    }

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
     * Create string for current scope with format scope-scopeId.
     *
     * @return string
     */
    public function getScopeString() {
        $scopeArray = $this->getConfigScopeId();
        if (isset($scopeArray['websiteId'])) {
            $scopeString = 'websites-'.$scopeArray['websiteId'];
        } elseif (isset($scopeArray['storeId'])) {
            $scopeString = 'stores-'.$scopeArray['storeId'];
        } else {
            $scopeString = 'default-0';
        }
        return $scopeString;
    }

    /**
     * Get MC store name for given scope.
     *
     * @param $scopeId
     * @param $scope
     * @return null|string
     * @throws Mage_Core_Exception
     */
    public function getMCStoreName($scopeId, $scope)
    {
        $storeName = null;
        switch ($scope) {
            case 'stores':
                $store = Mage::app()->getStore($scopeId);
                $storeName = $store->getFrontendName();
                break;
            case 'websites':
                $website = Mage::app()->getWebsite($scopeId);
                $storeName = $website->getDefaultStore()->getFrontendName();
                break;
            case 'default':
                $storeView = Mage::app()->getDefaultStoreView();
                $storeName = $storeView->getWebsite()->getDefaultStore()->getFrontendName();
                break;
        }
        return $storeName;
    }

    /**
     * Get local store_id value of the MC store.
     *
     * @return string
     */
    public function getStoreRelation()
    {
        $stores = Mage::app()->getStores();
        $storeRelation = array();
        foreach ($stores as $storeId => $store) {
            $mcStoreId = $this->getMCStoreId($storeId);
            if ($mcStoreId) {
                if (!array_key_exists($mcStoreId, $storeRelation)) {
                    $storeRelation[$mcStoreId] = array();
                }
                $storeRelation[$mcStoreId][] = $storeId;
            }
        }
        return $storeRelation;
    }


    /**
     * Get all Magento stores associated to the MailChimp store configured for the given scope.
     *
     * @param $scopeId
     * @param $scope
     * @return null
     */
    public function getMagentoStoresForMCStoreIdByScope($scopeId, $scope)
    {
        $ret = array();
        $storeRelation = $this->getStoreRelation();
        $mailchimpStoreIdForScope = $this->getMCStoreId($scopeId, $scope);
        if ($mailchimpStoreIdForScope) {
            foreach ($storeRelation as $mailchimpStoreId => $magentoStoreIds) {
                if ($mailchimpStoreIdForScope == $mailchimpStoreId) {
                    $ret = $magentoStoreIds;
                }
            }
        }
        return $ret;
    }

    /**
     * Return if module is enabled for given scope.
     * 
     * @param $scopeId
     * @param null $scope
     * @return mixed
     */
    public function isMailChimpEnabled($scopeId, $scope = null)
    {
        return $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::GENERAL_ACTIVE, $scopeId, $scope);
    }

    /**
     * Return Api Key if exists for given scope.
     *
     * @param $scope
     * @param $scopeId
     * @return mixed
     */
    public function getApiKey($scopeId, $scope = null)
    {
        return $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::GENERAL_APIKEY, $scopeId, $scope);
    }

    /**
     * Get local store_id value of the MC store for given scope.
     *
     * @param $scopeId
     * @param null $scope
     * @return mixed
     */
    public function getMCStoreId($scopeId, $scope = null)
    {
        return $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::GENERAL_MCSTOREID, $scopeId, $scope);
    }

    /**
     * Remove local store_id value of the MC store for given scope.
     *
     * @param $scopeId
     * @param null $scope
     * @return mixed
     */
    public function deleteLocalMCStoreData($scopeId, $scope = null)
    {
        Mage::getConfig()->deleteConfig(Ebizmarts_MailChimp_Model_Config::GENERAL_MCSTOREID, $scope, $scopeId);
        Mage::getConfig()->deleteConfig(Ebizmarts_MailChimp_Model_Config::GENERAL_MCISSYNCING, $scope, $scopeId);
        Mage::getConfig()->deleteConfig(Ebizmarts_MailChimp_Model_Config::GENERAL_MCSTORE_RESETED, $scope, $scopeId);
        Mage::getConfig()->deleteConfig(Ebizmarts_MailChimp_Model_Config::GENERAL_MCMINSYNCDATEFLAG, $scope, $scopeId);
        Mage::getConfig()->cleanCache();
    }

    /**
     * Return if Ecommerce configuration is enabled for given scope.
     *
     * @param $scopeId
     * @param null $scope
     * @return mixed
     */
    public function isEcommerceEnabled($scopeId, $scope = null)
    {
        return $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_ACTIVE, $scopeId, $scope);
    }

    /**
     * Get general list configured for the given scope.
     *
     * @param $scopeId
     * @param null $scope
     * @return mixed
     */
    public function getGeneralList($scopeId, $scope = null)
    {
        return $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::GENERAL_LIST, $scopeId, $scope);
    }

    /**
     * Get map fields configured for the given scope.
     * 
     * @param $scopeId
     * @param null $scope
     * @return mixed
     */
    public function getMapFields($scopeId, $scope = null)
    {
        return $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::GENERAL_MAP_FIELDS, $scopeId, $scope);
    }

    /**
     * Get custom merge fields configured for the given scope.
     * 
     * @param $scopeId
     * @param null $scope
     * @return mixed
     */
    public function getCustomMergeFieldsSerialized($scopeId, $scope = null)
    {
        return $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::GENERAL_CUSTOM_MAP_FIELDS, $scopeId, $scope);
    }
    
    /**
     * Get if store has been reseted for given scope.
     * 
     * @param $scopeId
     * @param null $scope
     * @return mixed
     */
    public function getIsReseted($scopeId, $scope = null)
    {
        return $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::GENERAL_MCSTORE_RESETED, $scopeId, $scope);
    }
    
    /**
     * Get if Abandoned Cart module is enabled.
     * 
     * @param $scopeId
     * @param null $scope
     * @return mixed
     */
    public function isAbandonedCartEnabled($scopeId, $scope = null)
    {
        return $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::ABANDONEDCART_ACTIVE, $scopeId, $scope);
    }
    
    /**
     * Get date configured for carts to be sent for the given scope.
     * 
     * @param $scopeId
     * @param null $scope
     * @return mixed
     */
    public function getAbandonedCartFirstDate($scopeId, $scope = null)
    {
        return $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::ABANDONEDCART_FIRSTDATE, $scopeId, $scope);
    }
    
    /**
     * Get date configured for ecommerce data to be sent for the given scope.
     * 
     * @param $scopeId
     * @param null $scope
     * @return mixed
     */
    public function getEcommerceFirstDate($scopeId, $scope = null)
    {
        return $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_FIRSTDATE, $scopeId, $scope);
    }

    /**
     * Get local is_syncing value of the MC store for given scope.
     *
     * @param $scopeId
     * @param null $scope
     * @return mixed
     */
    public function getMCIsSyncing($scopeId, $scope = null)
    {
        return $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::GENERAL_MCISSYNCING, $scopeId, $scope);
    }

    /**
     * Minimum date for which ecommerce data needs to be re-uploaded for given scope.
     *
     * @param $scopeId
     * @param null $scope
     * @return mixed
     */
    public function getMCMinSyncDateFlag($scopeId, $scope = null)
    {
        return $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::GENERAL_MCMINSYNCDATEFLAG, $scopeId, $scope);
    }

    /**
     * Get if logs are enabled for given scope.
     *
     * @param int $scopeId
     * @param null $scope
     * @return mixed
     */
    public function getLogsEnabled($scopeId = 0, $scope = null)
    {
        return $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::GENERAL_LOG, $scopeId, $scope);
    }

    /**
     * Get if two way sync is enabled for given scope.
     *
     * @param int $scopeId
     * @param null $scope
     * @return mixed
     */
    public function getTwoWaySyncEnabled($scopeId = 0, $scope = null)
    {
        return $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::GENERAL_TWO_WAY_SYNC, $scopeId, $scope);
    }

    /**
     * Get if monkey should be displayed in order grid.
     *
     * @param int $scopeId
     * @param null $scope
     * @return mixed
     */
    public function getMonkeyInGrid($scopeId = 0, $scope = null)
    {
        return $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::MONKEY_GRID, $scopeId, $scope);
    }

    /**
     * Get if Email Catcher popup is enabled for given scope.
     *
     * @param int $scopeId
     * @param null $scope
     * @return mixed
     */
    public function isEmailCatcherEnabled($scopeId = 0, $scope = null)
    {
        return $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::ENABLE_POPUP, $scopeId, $scope);
    }

    /**
     * Call deleteStore and CreateStore functions.
     * Update config values.
     *
     * @param bool $deleteDataInMailchimp
     * @param $scopeId
     * @param $scope
     */
    public function resetMCEcommerceData($scopeId, $scope, $deleteDataInMailchimp = false)
    {
        $listId = $this->getGeneralList($scopeId, $scope);
        //delete store id and data from mailchimp
        if ($deleteDataInMailchimp && $this->getMCStoreId($scopeId, $scope) && $this->getMCStoreId($scopeId, $scope) != "") {
            $this->removeEcommerceSyncData($scopeId, $scope);
            $this->resetCampaign($scopeId, $scope);
            $this->clearErrorGrid($scopeId, $scope, true);
            $this->deleteStore($scopeId, $scope);
        }

        if ($this->isEcomSyncDataEnabled($scopeId, $scope, true)) {
            $this->createStore($listId, $scopeId, $scope);
        }
    }

    /**
     * Remove items from mailchimp_ecommerce_sync_data table to allow them to be sent
     *
     * @param $scopeId
     * @param $scope
     * @param bool $deleteErrorsOnly
     */
    public function removeEcommerceSyncData($scopeId, $scope, $deleteErrorsOnly = false)
    {
        $collection = Mage::getModel('mailchimp/ecommercesyncdata')->getCollection()
            ->addFieldToFilter('mailchimp_store_id', array('eq' => $this->getMCStoreId($scopeId, $scope)));
        if ($deleteErrorsOnly) {
            $collection->addFieldToFilter('mailchimp_sync_error', array('neq' => ''));
        }
        foreach ($collection as $item) {
            $item->delete();
        }
    }

    /**
     * Check if Ecommerce data is configured to be sent.
     *
     * @param $scopeId
     * @param null $scope
     * @param bool $isStoreCreation
     * @return bool
     */
    public function isEcomSyncDataEnabled($scopeId, $scope = null, $isStoreCreation = false)
    {
        $apiKey = $this->getApiKey($scopeId, $scope);
        $moduleEnabled = $this->isMailChimpEnabled($scopeId, $scope);
        $ecommerceEnabled = $this->isEcommerceEnabled($scopeId, $scope);
        $generalList = $this->getGeneralList($scopeId, $scope);
        $ret = (!is_null($this->getMCStoreId($scopeId, $scope)) || $isStoreCreation) && !is_null($apiKey)
            && $apiKey != "" && $moduleEnabled && $ecommerceEnabled && $generalList;
        return $ret;
    }

    /**
     * Save error response from MailChimp's API in "MailChimp_Error.log" file.
     * 
     * @param $message
     * @param int $scopeId
     * @param string $scope
     */
    public function logError($message, $scopeId = 0, $scope = 'default')
    {
        if ($this->getLogsEnabled($scopeId, $scope)) {
            Mage::log($message, null, 'MailChimp_Errors.log', true);
        }
    }

    /**
     * Save request made to MailChimp's API in "MailChimp_Requests.log" file.
     *
     * @param $message
     * @param $scopeId
     * @param null $batchId
     */
    public function logRequest($message, $scopeId, $batchId=null)
    {
        if ($this->getLogsEnabled($scopeId)) {
            if (!$batchId) {
                Mage::log($message, null, 'MailChimp_Requests.log', true);
            } else {
                $logDir  = Mage::getBaseDir('var') . DS . 'log';
                if (!file_exists($logDir)) {
                    mkdir($logDir, 0750);
                }
                $logDir  .= DS . 'MailChimp_Requests';
                if (!file_exists($logDir)) {
                    mkdir($logDir, 0750);
                }
                $fileName = $logDir.DS.$batchId.'.Request.log';
                $oldPermission = umask(0046);
                file_put_contents($fileName, $message);
                umask($oldPermission);
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
     * Reset error messages from Products, Subscribers, Customers, Orders, Quotes and set them to be sent again for given scope.
     *
     * @param $scopeId
     * @param $scope
     */
    public function resetErrors($scopeId, $scope)
    {

        // reset subscribers with errors
        $collection = Mage::getModel('newsletter/subscriber')->getCollection()
            ->addFieldToFilter('mailchimp_sync_error', array('neq' => ''));
        $collection = $this->addStoresToFilter($collection, $scopeId, $scope);
        foreach ($collection as $subscriber) {
            $subscriber->setData("mailchimp_sync_delta", '0000-00-00 00:00:00');
            $subscriber->setData("mailchimp_sync_error", '');
            $subscriber->save();
        }

        // reset ecommerce data with errors
        $this->removeEcommerceSyncData($scopeId, $scope, true);
        $this->clearErrorGrid($scopeId, $scope);
    }

    /**
     * Clear mailchimp_errors grid for given scope.
     * Exclude subscriber if flag set to true.
     *
     * @param $scopeId
     * @param $scope
     * @param bool $excludeSubscribers
     */
    public function clearErrorGrid($scopeId, $scope, $excludeSubscribers = false)
    {
        //Make sure there are no errors without MailChimp store id due to older versions.
        $this->handleOldErrors();

        $mailchimpStoreId = Mage::helper('mailchimp')->getMCStoreId($scopeId, $scope);
        $storesForScope = $this->getMagentoStoresForMCStoreIdByScope($scopeId, $scope);
        if ($scopeId == 0) {
            $storesForScope[] = 0;
        }
        $errorCollection = Mage::getModel('mailchimp/mailchimperrors')->getCollection()
            ->addFieldToFilter('mailchimp_store_id', array('eq' => $mailchimpStoreId));
        if ($excludeSubscribers) {
            $errorCollection->addFieldToFilter('regtype', array('neq' => Ebizmarts_MailChimp_Model_Config::IS_SUBSCRIBER));
        }
        foreach ($errorCollection as $item) {
            $item->delete();
        }
    }

    /**
     * Set the correspondent MailChimp store id to each error.
     */
    public function handleOldErrors() {
        $errorCollection = Mage::getModel('mailchimp/mailchimperrors')->getCollection()
            ->addFieldToFilter('mailchimp_store_id', array('eq' => ''));
        foreach ($errorCollection as $error) {
            $storeId = $error->getStoreId();
            $mailchimpStoreId = $this->getMCStoreId($storeId);
            if ($mailchimpStoreId) {
                $error->setMailchimpStoreId($mailchimpStoreId)
                    ->save();
            }
        }
    }

    /**
     * Set mailchimp_campaign_id field to 0 for all orders.
     *
     * @param $scopeId
     * @param $scope
     */
    public function resetCampaign($scopeId, $scope)
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
        $orderCollection = $this->addStoresToFilter($orderCollection, $scopeId, $scope);
        foreach ($orderCollection as $order) {
            $order->setMailchimpCampaignId(0);
            $order->save();
        }
    }

    /**
     * Call createMailChimpStore function and save configuration values associated to the new store.
     *
     * @param $listId
     * @param $scopeId
     * @param $scope
     */
    public function createStore($listId, $scopeId, $scope)
    {
        if ($listId) {
            //generate store id
            $date = $this->getDateMicrotime();
            $mailchimpStoreId = md5($this->getMCStoreName($scopeId, $scope). '_' . $date);
            //create store in mailchimp
            try {
                Mage::getModel('mailchimp/api_stores')->createMailChimpStore($mailchimpStoreId, $listId, $scopeId, $scope);
                //save in config
                $configValues = array(
                    array(Ebizmarts_MailChimp_Model_Config::GENERAL_MCSTOREID, $mailchimpStoreId),
                    array(Ebizmarts_MailChimp_Model_Config::GENERAL_MCISSYNCING, 1),
                    array(Ebizmarts_MailChimp_Model_Config::GENERAL_MCMINSYNCDATEFLAG, Varien_Date::now()),
                    array(Ebizmarts_MailChimp_Model_Config::GENERAL_MCSTORE_RESETED, 1)
                    );
                $this->saveMailchimpConfig($configValues, $scopeId, $scope);
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
    }

    /**
     * Call deleteMailChimpStore store function and delete configurations associated to the new store.
     *
     * @param $scopeId
     * @param $scope
     */
    public function deleteStore($scopeId, $scope)
    {
        $mailchimpStoreId = $this->getMCStoreId($scopeId, $scope);
        if (!empty($mailchimpStoreId)) {
            try {
                Mage::getModel('mailchimp/api_stores')->deleteMailChimpStore($mailchimpStoreId, $scopeId, $scope);
            } catch (Mailchimp_Error $e) {
                Mage::helper('mailchimp')->logError($e->getFriendlyMessage(), $scopeId, $scope);
            }

            //clear store config values
            $this->deleteLocalMCStoreData($scopeId, $scope);
        }
    }

    /**
     * Create MailChimp merge fields existing in the extension configuration page.
     *
     * @param $scopeId
     * @param $scope
     */
    public function createMergeFields($scopeId, $scope)
    {
        $listId = $this->getGeneralList($scopeId, $scope);
        $maps = unserialize($this->getMapFields($scopeId, $scope));
        $customFieldTypes = unserialize($this->getCustomMergeFieldsSerialized($scopeId, $scope));
        $api = $this->getApi($scopeId, $scope);
        $mailchimpFields = array();
        if ($api) {
            try {
                $mailchimpFields = $api->lists->mergeFields->getAll($listId, null, null, 50);
            } catch (Mailchimp_Error $e) {
                Mage::helper('mailchimp')->logError($e->getFriendlyMessage(), $scopeId, $scope);
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
                                    Mage::helper('mailchimp')->logError($e->getFriendlyMessage(), $scopeId, $scope);
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
                                        $api->lists->mergeFields->add($listId, $label, 'birthday', null, $chimpTag);
                                    } else {
                                        $api->lists->mergeFields->add($listId, $label, 'text', null, $chimpTag);
                                    }
                                }
                            } catch (Mailchimp_Error $e) {
                                Mage::helper('mailchimp')->logError($e->getFriendlyMessage(), $scopeId, $scope);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * get Date with Microtime.
     *
     * @return string
     */
    public function getDateMicrotime()
    {
        $microtime = explode(' ', microtime());
        $msec = $microtime[0];
        $msecArray = explode('.', $msec);
        $date = date('Y-m-d-H-i-s') . '-' . $msecArray[1];
        return $date;
    }

    /**
     * Get Api object for given scope.
     *
     * @param $scopeId
     * @param null $scope
     * @return Ebizmarts_Mailchimp|null
     */
    public function getApi($scopeId, $scope = null)
    {
        $apiKey = $this->getApiKey($scopeId, $scope);
        $api = null;
        if ($apiKey != null && $apiKey != "") {
            $api = new Ebizmarts_Mailchimp($apiKey, null, 'Mailchimp4Magento' . (string)Mage::getConfig()->getNode('modules/Ebizmarts_MailChimp/version'));
        }

        return $api;
    }

    /**
     * If conditions are met call the modifyName function to modify the name of the MailChimp store for given scope.
     * 
     * @param $name
     * @param $scopeId
     * @param $scope
     */
    public function changeName($name, $scopeId, $scope)
    {
        if ($this->getMCStoreId($scopeId, $scope) && $this->getIfMCStoreIdExistsForScope($scopeId, $scope)) {
            try {
                Mage::getModel('mailchimp/api_stores')->modifyName($name, $scopeId, $scope);
            } catch (Mailchimp_Error $e) {
                Mage::helper('mailchimp')->logError($e->getFriendlyMessage(), $scopeId, $scope);
            }
        }
    }

    /**
     * Save entry for ecommerce_sync_data table overwriting old item if exists or creating a new one if it does not.
     *
     * @param $itemId
     * @param $itemType
     * @param $mailchimpStoreId
     * @param null $syncDelta
     * @param null $syncError
     * @param int $syncModified
     * @param null $syncDeleted
     * @param null $token
     * @param bool $saveOnlyIfexists
     */
    public function saveEcommerceSyncData($itemId, $itemType, $mailchimpStoreId, $syncDelta = null, $syncError = null,
                                          $syncModified = 0, $syncDeleted = null, $token = null, $saveOnlyIfexists = false)
    {
        $ecommerceSyncDataItem = $this->getEcommerceSyncDataItem($itemId, $itemType, $mailchimpStoreId);
        if (!$saveOnlyIfexists || $ecommerceSyncDataItem->getMailchimpSyncDelta()) {
            if ($syncDelta) {
                $ecommerceSyncDataItem->setData("mailchimp_sync_delta", $syncDelta);
            }
            if ($syncError) {
                $ecommerceSyncDataItem->setData("mailchimp_sync_error", $syncError);
            }
            //Always set modified value to 0 when saving sync delta or errors.
            $ecommerceSyncDataItem->setData("mailchimp_sync_modified", $syncModified);
            if ($syncDeleted) {
                $ecommerceSyncDataItem->setData("mailchimp_sync_deleted", $syncDeleted);
            }
            if ($token) {
                $ecommerceSyncDataItem->setData("mailchimp_token", $token);
            }
            $ecommerceSyncDataItem->save();
        }
    }

    /**
     *  Load Ecommerce Sync Data Item if exists or set the values for a new one and return it.
     *
     * @param $itemId
     * @param $itemType
     * @param $mailchimpStoreId
     * @return Varien_Object
     */
    public function getEcommerceSyncDataItem($itemId, $itemType, $mailchimpStoreId)
    {
        $collection = Mage::getModel('mailchimp/ecommercesyncdata')->getCollection()
            ->addFieldToFilter('related_id', array('eq' => $itemId))
            ->addFieldToFilter('type', array('eq' => $itemType))
            ->addFieldToFilter('mailchimp_store_id', array('eq' => $mailchimpStoreId));
        if (count($collection)) {
            $ecommerceSyndDataItem = $collection->getFirstItem();
        } else {
            $ecommerceSyndDataItem = Mage::getModel('mailchimp/ecommercesyncdata')
                ->setData("related_id", $itemId)
                ->setData("type", $itemType)
                ->setData("mailchimp_store_id", $mailchimpStoreId);
        }
        return $ecommerceSyndDataItem;
    }

    /**
     * Filter collection by all the stores associated to MailChimp for given scope.
     *
     * @param $collection
     * @param $scopeId
     * @param $scope
     * @return mixed
     */
    public function addStoresToFilter($collection, $scopeId, $scope)
    {
        $filterArray = array();
        $storesForScope = $this->getMagentoStoresForMCStoreIdByScope($scopeId, $scope);
        if ($storesForScope) {
            if ($scopeId === 0) {
                $filterArray[] = array('eq' => 0);
            }
            foreach ($storesForScope as $storeId) {
                $filterArray[] = array('eq' => $storeId);
            }
        }
        if (count($filterArray)) {
            $collection->addFieldToFilter('store_id', $filterArray);
        }
        return $collection;
    }

    /**
     * Return true if a MailChimp store has been created specifically for the given scope.
     *
     * @param $scopeId
     * @param $scope
     * @return mixed|null
     */
    public function getIfMCStoreIdExistsForScope($scopeId, $scope)
    {
        $mcStoreAssociatedToScope = false;
        $collection = Mage::getModel('core/config_data')->getCollection()
            ->addFieldToFilter('path', array('eq' => Ebizmarts_MailChimp_Model_Config::GENERAL_MCSTOREID))
            ->addFieldToFilter('scope', array('eq' => $scope))
            ->addFieldToFilter('scope_id', array('eq' => $scopeId));
        if (count($collection)) {
            $mcStoreAssociatedToScope = true;
        }

        return $mcStoreAssociatedToScope;
    }

    /**
     * Get actual scope where the MailChimp store was created if exists.
     *
     * @param $scopeId
     * @param null $scope
     * @return array|null
     */
    public function getMailChimpScopeByStoreId($scopeId, $scope = null)
    {
        $mailchimpScope = null;
        $mailChimpStoreId = $this->getMCStoreId($scopeId, $scope);
        $collection = Mage::getModel('core/config_data')->getCollection()
            ->addFieldToFilter('path', array('eq' => Ebizmarts_MailChimp_Model_Config::GENERAL_MCSTOREID))
            ->addFieldToFilter('value', array('eq' => $mailChimpStoreId));
        if (count($collection)) {
            $configEntry = $collection->getFirstItem();
            $mailchimpScope = array('scope' => $configEntry->getScope(), 'scope_id' => $configEntry->getScopeId());
        }
        return $mailchimpScope;
    }

    /**
     * Return default store id for the configured scope on MailChimp.
     *
     * @param $magentoStoreId
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function getDefaultStoreIdForMailChimpScope($magentoStoreId)
    {
        $scopeArray = $this->getMailChimpScopeByStoreId($magentoStoreId);
        if ($scopeArray) {
            if ($scopeArray['scope'] == 'websites') {
                $magentoStoreId = Mage::app()
                    ->getWebsite($scopeArray['scope_id'])
                    ->getDefaultGroup()
                    ->getDefaultStoreId();
            } elseif ($scopeArray['scope'] == 'default') {
                $magentoStoreId = Mage::app()
                    ->getWebsite(true)
                    ->getDefaultGroup()
                    ->getDefaultStoreId();
            }
        } else {
            $magentoStoreId = null;
        }
        return $magentoStoreId;
    }

    /**
     * Save configValues on core_config_data for given scope.
     *
     * @param $configValues
     * @param $scopeId
     * @param $scope
     */
    public function saveMailchimpConfig($configValues, $scopeId, $scope)
    {
        foreach ($configValues as $configValue) {
            Mage::getConfig()->saveConfig($configValue[0], $configValue[1], $scope, $scopeId);
        }
        Mage::getConfig()->cleanCache();
    }

    /**
     * If $productImageUrl not null returns it, else return $parentImageUrl.
     * Both parameters could be null.
     * 
     * @param $parentImageUrl
     * @param $productImageUrl
     * @return mixed
     */
    public function getMailChimpProductImageUrl($parentImageUrl, $productImageUrl)
    {
        return ($productImageUrl) ? $productImageUrl : $parentImageUrl;
    }

    /**
     * Returns product image url by id, if it does not have one returns null.
     *
     * @param $productId
     * @return null
     */
    public function getImageUrlById($productId)
    {
        $productMediaConfig = Mage::getModel('catalog/product_media_config');
        $product = Mage::getModel('catalog/product')->load($productId);
        if ($product->getImage() == 'no_selection') {
            $imageUrl = null;
        } else {
            $imageUrl = $productMediaConfig->getMediaUrl($product->getImage());
        }
        return $imageUrl;
    }

    /**
     * If orders with the given email exists, returns the date of the last order made.
     * 
     * @param $subscriberEmail
     * @return null
     */
    public function getLastDateOfPurchase($subscriberEmail)
    {
        $orderCollection = Mage::getModel('sales/order')->getCollection()
            ->addFieldToFilter('customer_email', array('eq' => $subscriberEmail));
        $lastDateOfPurchase = null;
        foreach ($orderCollection as $order) {
            $dateOfPurchase = $order->getCreatedAt();
            if (!$lastDateOfPurchase || $lastDateOfPurchase < $dateOfPurchase) {
                $lastDateOfPurchase = $dateOfPurchase;
            }
        }
        return $lastDateOfPurchase;
    }

    /**
     * Return true if there is a custom entry with the value given.
     * 
     * @param $value
     * @param $scopeId
     * @param $scope
     * @return bool
     */
    public function customMergeFieldAlreadyExists($value, $scopeId, $scope)
    {
        $customMergeFields = $this->getCustomMergeFields($scopeId, $scope);
        foreach ($customMergeFields as $customMergeField) {
            if ($customMergeField['value'] == $value) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get custom merge fields for given scope as an array.
     * 
     * @param $scopeId
     * @param null $scope
     * @return array|mixed
     */
    public function getCustomMergeFields($scopeId, $scope = null)
    {
        $customMergeFields = unserialize($this->getCustomMergeFieldsSerialized($scopeId, $scope));
        if (!$customMergeFields) {
            $customMergeFields = array();
        }
        return $customMergeFields;
    }
}
