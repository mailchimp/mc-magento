<?php

/**
 * MailChimp For Magento
 *
 * @category  Ebizmarts_MailChimp
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     4/29/16 3:55 PM
 * @file:     Data.php
 */
class Ebizmarts_MailChimp_Helper_Data extends Mage_Core_Helper_Abstract
{
    const DEFAULT_SIZE      = '0';
    const SMALL_SIZE        = '1';
    const THUMBNAIL_SIZE    = '2';
    const ORIGINAL_SIZE     = '3';

    /**
     * All MailChimp available language codes
     * @var array
     */
    public static $MAILCHIMP_LANGUAGES = array(
        'en', // English
        'ar', // Arabic
        'af', // Afrikaans
        'be', // Belarusian
        'bg', // Bulgarian
        'ca', // Catalan
        'zh', // Chinese
        'hr', // Croatian
        'cs', // Czech
        'da', // Danish
        'nl', // Dutch
        'et', // Estonian
        'fa', // Farsi
        'fi', // Finnish
        'fr', // French (France)
        'fr_CA', // French (Canada)
        'de', // German
        'el', // Greek
        'he', // Hebrew
        'hi', // Hindi
        'hu', // Hungarian
        'is', // Icelandic
        'id', // Indonesian
        'ga', // Irish
        'it', // Italian
        'ja', // Japanese
        'km', // Khmer
        'ko', // Korean
        'lv', // Latvian
        'lt', // Lithuanian
        'mt', // Maltese
        'ms', // Malay
        'mk', // Macedonian
        'no', // Norwegian
        'pl', // Polish
        'pt', // Portuguese (Brazil)
        'pt_PT', // Portuguese (Portugal)
        'ro', // Romanian
        'ru', // Russian
        'sr', // Serbian
        'sk', // Slovak
        'sl', // Slovenian
        'es', // Spanish (Mexico)
        'es_ES', // Spanish (Spain)
        'sw', // Swahili
        'sv', // Swedish
        'ta', // Tamil
        'th', // Thai
        'tr', // Turkish
        'uk', // Ukrainian
        'vi', // Vietnamese
    );

    /**
     * Get Config value for certain scope.
     *
     * @param  $path
     * @param  $scopeId
     * @param  null $scope
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function getConfigValueForScope($path, $scopeId, $scope = null)
    {
        if ($scope == 'websites') {
            $configValue = $this->getMageApp()->getWebsite($scopeId)->getConfig($path);
        } else {
            $configValue = Mage::getStoreConfig($path, $scopeId);
        }
        return $configValue;
    }

    /**
     * Get storeId and/or websiteId if scope selected on back end
     *
     * @param  null $storeId
     * @param  null $websiteId
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
            $websiteId = $this->getCoreWebsite()->load($code)->getId();
            $storeId = $this->getMageApp()->getWebsite($websiteId)->getDefaultStore()->getId();
        }
        $scopeArray['websiteId'] = $websiteId;
        $scopeArray['storeId'] = $storeId;
        return $scopeArray;
    }

    /**
     * Create string for current scope with format scope-scopeId.
     *
     * @return array
     */
    public function getCurrentScope()
    {
        $scopeIdArray = $this->getConfigScopeId();
        $scopeArray = array();
        if (isset($scopeIdArray['websiteId'])) {
            $scopeArray['scope'] = 'websites';
            $scopeArray['scope_id'] = $scopeIdArray['websiteId'];
        } elseif (isset($scopeIdArray['storeId'])) {
            $scopeArray['scope'] = 'stores';
            $scopeArray['scope_id'] = $scopeIdArray['storeId'];
        } else {
            $scopeArray['scope'] = 'default';
            $scopeArray['scope_id'] = 0;
        }
        return $scopeArray;
    }

    /**
     * Get MC store name for given scope.
     *
     * @param $scopeId
     * @param $scope
     * @param bool $forceParentName
     * @return mixed|null|string
     */
    public function getMCStoreName($scopeId, $scope, $forceParentName = false)
    {
        $storeName = null;
        switch ($scope) {
            case 'stores':
                $storeName = $this->getConfigValueForScope(Mage_Core_Model_Store::XML_PATH_STORE_STORE_NAME, $scopeId, $scope);
                if ($storeName == '' || $forceParentName) {
                    $store = $this->getMageApp()->getStore($scopeId);
                    $storeName = $store->getGroup()->getName() . ' - ' . $store->getName();
                }
                break;
            case 'websites':
                $storeName = $this->getConfigValueForScope(Mage_Core_Model_Store::XML_PATH_STORE_STORE_NAME, $scopeId, $scope);
                if ($storeName == '' || $forceParentName) {
                    $website = $this->getMageApp()->getWebsite($scopeId);
                    $storeName = $website->getName();
                }
                break;
            case 'default':
                $storeName = $this->getConfigValueForScope(Mage_Core_Model_Store::XML_PATH_STORE_STORE_NAME, $scopeId, $scope);
                if ($storeName == '' || $forceParentName) {
                    $storeName = $this->getConfigValueForScope('web/unsecure/base_url', 0);
                }
                break;
        }
        return $storeName;
    }

    public function isUsingConfigStoreName($scopeId, $scope)
    {
        $storeName = $this->getConfigValueForScope(Mage_Core_Model_Store::XML_PATH_STORE_STORE_NAME, $scopeId, $scope);
        if ($storeName == '') {
            $usingConfigName = false;
        } else {
            $usingConfigName = true;
        }
        return $usingConfigName;
    }

    /**
     * Get store unsecure URL for given scope.
     *
     * @param  $scopeId
     * @param  $scope
     * @return mixed
     */
    public function getStoreDomain($scopeId, $scope)
    {
        if ($scope == 'stores') {
            $domain = $this->getMageApp()->getStore($scopeId)->getBaseUrl();
        } elseif ($scope == 'websites') {
            $website = Mage::getModel('core/website')->load($scopeId);
            $websiteCode = $website->getCode();
            $domain = (string)Mage::getConfig()->getNode('web/unsecure/base_url', 'website', $websiteCode);
        } else {
            $domain = $this->getConfigValueForScope(Mage_Core_Model_Store::XML_PATH_UNSECURE_BASE_LINK_URL, $scopeId, $scope);
        }
        return $domain;
    }

    /**
     * Get local store_id value of the MC store.
     *
     * @return string
     */
    public function getStoreRelation()
    {
        $stores = $this->getMageApp()->getStores();
        $storeRelation = array();
        foreach ($stores as $storeId => $store) {
            if ($this->isEcomSyncDataEnabled($storeId)) {
                $mcStoreId = $this->getMCStoreId($storeId);
                if ($mcStoreId) {
                    if (!array_key_exists($mcStoreId, $storeRelation)) {
                        $storeRelation[$mcStoreId] = array();
                    }
                    $storeRelation[$mcStoreId][] = $storeId;
                }
            }
        }
        return $storeRelation;
    }


    /**
     * Get all Magento stores associated to the MailChimp store configured for the given scope.
     *
     * @param  $scopeId
     * @param  $scope
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
     * Validate if api key exists, could still be incorrect
     *
     * @param  $scopeId
     * @param  null $scope
     * @return mixed
     */
    public function validateApiKey($scopeId, $scope = null)
    {
        $apiKey = $this->getApiKey($scopeId, $scope);
        $isApiKeyValid = !is_null($apiKey) && $apiKey != "";
        return $isApiKeyValid;
    }

    /**
     * Return if module is enabled for given scope.
     *
     * @param  $scopeId
     * @param  null $scope
     * @return mixed
     */
    public function isMailChimpEnabled($scopeId, $scope = null)
    {
        return $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::GENERAL_ACTIVE, $scopeId, $scope);
    }

    /**
     * Return if module is enabled and list selected for given scope.
     *
     * @param  $scopeId
     * @param  null $scope
     * @return mixed
     */
    public function isSubscriptionEnabled($scopeId, $scope = null)
    {
        $apiKeyValid = $this->validateApiKey($scopeId, $scope);
        $mailChimpEnabled = $this->isMailChimpEnabled($scopeId, $scope);
        $generalList = $this->getGeneralList($scopeId, $scope);

        return $apiKeyValid && $mailChimpEnabled && $generalList;
    }

    /**
     * Return Api Key if exists for given scope.
     *
     * @param  $scope
     * @param  $scopeId
     * @return mixed
     */
    public function getApiKey($scopeId, $scope = null)
    {
        return $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::GENERAL_APIKEY, $scopeId, $scope);
    }

    /**
     * Get local store_id value of the MC store for given scope.
     *
     * @param  $scopeId
     * @param  null $scope
     * @return mixed
     */
    public function getMCStoreId($scopeId, $scope = null)
    {
        return $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::GENERAL_MCSTOREID, $scopeId, $scope);
    }

    /**
     * Remove local store_id value of the MC store for given scope.
     *
     * @param  $mailchimpStoreId
     * @param  $scopeId
     * @param  null $scope
     * @return mixed
     */
    public function deleteLocalMCStoreData($mailchimpStoreId, $scopeId, $scope = null)
    {
        $config = $this->getConfig();
        $config->deleteConfig(Ebizmarts_MailChimp_Model_Config::GENERAL_MCSTOREID, $scope, $scopeId);
        $config->deleteConfig(Ebizmarts_MailChimp_Model_Config::GENERAL_MCISSYNCING, $scope, $scopeId);
        $config->deleteConfig(Ebizmarts_MailChimp_Model_Config::GENERAL_ECOMMMINSYNCDATEFLAG, $scope, $scopeId);
        $config->deleteConfig(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_MC_JS_URL, $scope, $scopeId);
        $config->deleteConfig(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_CUSTOMER_LAST_ID, $scope, $scopeId);
        $config->deleteConfig(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_PRODUCT_LAST_ID, $scope, $scopeId);
        $config->deleteConfig(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_ORDER_LAST_ID, $scope, $scopeId);
        $config->deleteConfig(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_CART_LAST_ID, $scope, $scopeId);
        $config->deleteConfig(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_PCD_LAST_ID, $scope, $scopeId);
        $config->deleteConfig(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_RESEND_ENABLED, $scope, $scopeId);
        $config->deleteConfig(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_RESEND_TURN, $scope, $scopeId);
        $config->deleteConfig(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_SYNC_DATE . "_$mailchimpStoreId", 'default', 0);
        $config->cleanCache();
    }

    /**
     * Return if Ecommerce configuration is enabled for given scope.
     *
     * @param  $scopeId
     * @param  null $scope
     * @return mixed
     */
    public function isEcommerceEnabled($scopeId, $scope = null)
    {
        return $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_ACTIVE, $scopeId, $scope);
    }

    /**
     * Get general list configured for the given scope.
     *
     * @param  $scopeId
     * @param  null $scope
     * @return mixed
     */
    public function getGeneralList($scopeId, $scope = null)
    {
        return $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::GENERAL_LIST, $scopeId, $scope);
    }

    /**
     * Get map fields configured for the given scope.
     *
     * @param  $scopeId
     * @param  null $scope
     * @return mixed
     */
    public function getMapFields($scopeId, $scope = null)
    {
        return $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::GENERAL_MAP_FIELDS, $scopeId, $scope);
    }

    /**
     * Get custom merge fields configured for the given scope.
     *
     * @param  $scopeId
     * @param  null $scope
     * @return mixed
     */
    public function getCustomMergeFieldsSerialized($scopeId, $scope = null)
    {
        return $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::GENERAL_CUSTOM_MAP_FIELDS, $scopeId, $scope);
    }

    /**
     * Get if Abandoned Cart module is enabled.
     *
     * @param  $scopeId
     * @param  null $scope
     * @return mixed
     */
    public function isAbandonedCartEnabled($scopeId, $scope = null)
    {
        return $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::ABANDONEDCART_ACTIVE, $scopeId, $scope);
    }

    /**
     * Get date configured for carts to be sent for the given scope.
     *
     * @param  $scopeId
     * @param  null $scope
     * @return mixed
     */
    public function getAbandonedCartFirstDate($scopeId, $scope = null)
    {
        return $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::ABANDONEDCART_FIRSTDATE, $scopeId, $scope);
    }

    /**
     * Get date configured for ecommerce data to be sent for the given scope.
     *
     * @param  $scopeId
     * @param  null $scope
     * @return mixed
     */
    public function getEcommerceFirstDate($scopeId, $scope = null)
    {
        return $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_FIRSTDATE, $scopeId, $scope);
    }

    /**
     * Get local is_syncing value of the MC store for given scope.
     *
     * @param  $scopeId
     * @param  null $scope
     * @return mixed
     */
    public function getMCIsSyncing($scopeId, $scope = null)
    {
        return $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::GENERAL_MCISSYNCING, $scopeId, $scope);
    }

    /**
     * Minimum date for which ecommerce data needs to be re-uploaded for given scope.
     *
     * @param  $scopeId
     * @param  null $scope
     * @return mixed
     */
    public function getEcommMinSyncDateFlag($scopeId, $scope = null)
    {
        return $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::GENERAL_ECOMMMINSYNCDATEFLAG, $scopeId, $scope);
    }

    /**
     * Get if logs are enabled for given scope.
     *
     * @return mixed
     */
    public function getLogsEnabled()
    {
        return $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::GENERAL_LOG, 0);
    }

    /**
     * Get if two way sync is enabled for given scope.
     *
     * @param  int $scopeId
     * @param  null $scope
     * @return mixed
     */
    public function getTwoWaySyncEnabled($scopeId = 0, $scope = null)
    {
        return $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::GENERAL_TWO_WAY_SYNC, $scopeId, $scope);
    }

    /**
     * Get webhook Id.
     *
     * @param  int $scopeId
     * @param  null $scope
     * @return mixed
     */
    public function getWebhookId($scopeId = 0, $scope = null)
    {
        return $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::GENERAL_WEBHOOK_ID, $scopeId, $scope);
    }

    /**
     * Get if monkey should be displayed in order grid.
     *
     * @param  int $scopeId
     * @param  null $scope
     * @return mixed
     */
    public function getMonkeyInGrid($scopeId = 0, $scope = null)
    {
        return $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::GENERAL_ORDER_GRID, $scopeId, $scope);
    }

    /**
     * Get if Email Catcher popup is enabled for given scope.
     *
     * @param  int $scopeId
     * @param  null $scope
     * @return mixed
     */
    public function isEmailCatcherEnabled($scopeId = 0, $scope = null)
    {
        return $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::ENABLE_POPUP, $scopeId, $scope);
    }

    public function getDateSyncFinishByStoreId($scopeId = 0, $scope = null)
    {
        $mailchimpStoreId = $this->getMCStoreId($scopeId, $scope);
        return $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_SYNC_DATE . "_$mailchimpStoreId", 0, 'default');
    }

    public function getDateSyncFinishByMailChimpStoreId($mailchimpStoreId)
    {
        return $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_SYNC_DATE . "_$mailchimpStoreId", 0, 'default');
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
        if ($deleteDataInMailchimp) {
            $mailchimpStoreId = $this->getMCStoreId($scopeId, $scope);
            if ($mailchimpStoreId && $mailchimpStoreId != "") {
                $this->removeEcommerceSyncData($scopeId, $scope);
                $this->resetCampaign($scopeId, $scope);
                $this->clearErrorGrid($scopeId, $scope, true);
                $this->deleteStore($scopeId, $scope);
            }

            if ($this->isEcomSyncDataEnabled($scopeId, $scope, true)) {
                $this->createStore($listId, $scopeId, $scope);
            }
        } else {
            if ($this->getMCStoreId($scopeId, $scope) && $this->getMCStoreId($scopeId, $scope) != "") {
                if (!$this->getResendEnabled($scopeId, $scope)) {
                    $this->saveLastItemsSent($scopeId, $scope);
                }
                $this->removeEcommerceSyncData($scopeId, $scope);
                $this->clearErrorGrid($scopeId, $scope, true);
            }
        }
    }

    /**
     * Remove items from mailchimp_ecommerce_sync_data table to allow them to be sent. If scopeId is 0 remova from all scopes.
     *
     * @param $scopeId
     * @param $scope
     * @param bool $deleteErrorsOnly
     */
    public function removeEcommerceSyncData($scopeId, $scope, $deleteErrorsOnly = false)
    {
        $mailchimpStoreId = $this->getMCStoreId($scopeId, $scope);
        $resource = $this->getCoreResource();
        $connection = $resource->getConnection('core_write');
        $tableName = $resource->getTableName('mailchimp/ecommercesyncdata');
        if ($deleteErrorsOnly) {
            if ($scopeId === 0) {
                $where = "mailchimp_sync_error != ''";
            } else {
                $where = array("mailchimp_store_id = ? and mailchimp_sync_error != ''" => $mailchimpStoreId);
            }
        } else {
            $where = array("mailchimp_store_id = ?" => $mailchimpStoreId);
        }
        try {
            $connection->delete($tableName, $where);
        } catch (Exception $e) {
            $this->logError($e->getMessage());
        }
    }

    /**
     * Check if Ecommerce data is configured to be sent.
     *
     * @param  $scopeId
     * @param  null $scope
     * @param  bool $isStoreCreation
     * @return bool
     */
    public function isEcomSyncDataEnabled($scopeId, $scope = null, $isStoreCreation = false)
    {
        //If store id does not exist return false. (For deleted stores i.e.: order grid synced status)
        if ($scopeId === null) {
            $ret = false;
        } else {
            $subscriptionEnabled = $this->isSubscriptionEnabled($scopeId, $scope);
            $ecommerceEnabled = $this->isEcommerceEnabled($scopeId, $scope);
            $mailchimpStoreId = $this->getMCStoreId($scopeId, $scope);

            $ret = (!is_null($mailchimpStoreId) || $isStoreCreation)
                && $subscriptionEnabled && $ecommerceEnabled;
        }

        return $ret;
    }

    /**
     * Check if Ecommerce data is configured to be sent in any scope.
     *
     * @return bool
     */
    public function isEcomSyncDataEnabledInAnyScope()
    {
        $stores = $this->getMageApp()->getStores();
        foreach ($stores as $storeId => $store) {
            $ecomEnabled = $this->isEcomSyncDataEnabled($storeId);
            if ($ecomEnabled) {
                return true;
            }
        }
        return false;
    }

    /**
     * Save error response from MailChimp's API in "MailChimp_Error.log" file.
     *
     * @param $message
     */
    public function logError($message)
    {
        if ($this->isErrorLogEnabled()) {
            Mage::log($message, null, 'MailChimp_Errors.log', true);
        }
    }

    /**
     * Save request made to MailChimp's API in "MailChimp_Requests.log" file.
     *
     * @param $message
     * @param null $batchId
     */
    public function logRequest($message, $batchId = null)
    {
        $logRequestEnabled = $this->isRequestLogEnabled();
        $logErrorEnabled = $this->isErrorLogEnabled();
        if (!$batchId && ($logRequestEnabled || $logErrorEnabled)) {
            Mage::log($message, null, 'MailChimp_Failing_Requests.log', true);
        } elseif ($logRequestEnabled) {
            $logDir = Mage::getBaseDir('var') . DS . 'log';
            if (!file_exists($logDir)) {
                mkdir($logDir, 0750);
            }
            $logDir .= DS . 'MailChimp_Requests';
            if (!file_exists($logDir)) {
                mkdir($logDir, 0750);
            }
            $fileName = $logDir . DS . $batchId . '.Request.log';
            $oldPermission = umask(0033);
            file_put_contents($fileName, $message);
            umask($oldPermission);
        }

    }

    /**
     * @return bool
     */
    protected function isRequestLogEnabled()
    {
        $logEnabled = false;
        $logConfig = $this->getLogsEnabled();
        if ($logConfig == Ebizmarts_MailChimp_Model_System_Config_Source_Log::REQUEST_LOG || $logConfig == Ebizmarts_MailChimp_Model_System_Config_Source_Log::BOTH) {
            $logEnabled = true;
        }

        return $logEnabled;
    }

    /**
     * @return bool
     */
    protected function isErrorLogEnabled()
    {
        $logEnabled = false;
        $logConfig = $this->getLogsEnabled();
        if ($logConfig == Ebizmarts_MailChimp_Model_System_Config_Source_Log::ERROR_LOG || $logConfig == Ebizmarts_MailChimp_Model_System_Config_Source_Log::BOTH) {
            $logEnabled = true;
        }

        return $logEnabled;
    }

    /**
     * @return string
     */
    public function getWebhooksKey()
    {
        $crypt = md5((string)$this->getConfig()->getNode('global/crypt/key'));
        $key = substr($crypt, 0, (strlen($crypt) / 2));

        return $key;
    }

    /**
     * Reset error messages from Products, Subscribers, Customers, Orders, Quotes and set them to be sent again for given scope.
     *
     * @param $scopeId
     * @param $scope
     */
    public function resetErrors($scopeId, $scope = 'stores')
    {
        // reset subscribers with errors
        $collection = Mage::getResourceModel('newsletter/subscriber_collection')
            ->addFieldToFilter('mailchimp_sync_error', array('neq' => ''));
        if ($scopeId != 0) {
            $collection = $this->addStoresToFilter($collection, $scopeId, $scope);
        }
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
        //Make sure there are no errors without no MailChimp store id due to older versions.
        $this->handleOldErrors();

        $mailchimpStoreId = $this->getMCStoreId($scopeId, $scope);
        $resource = $this->getCoreResource();
        $connection = $resource->getConnection('core_write');
        $tableName = $resource->getTableName('mailchimp/mailchimperrors');
        if ($excludeSubscribers) {
            $where = array("mailchimp_store_id = ?" => $mailchimpStoreId);
        } else {
            if ($scopeId !== 0) {
                $where = array("store_id = ?" => $scopeId);
            } else {
                $where = '';
            }
        }
        $connection->delete($tableName, $where);
    }

    /**
     * Set the correspondent MailChimp store id to each error.
     */
    public function handleOldErrors()
    {
        $errorCollection = Mage::getModel('mailchimp/mailchimperrors')->getCollection()
            ->addFieldToFilter('type', array('neq' => 'SUB'))
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
        $resource = $this->getCoreResource();
        $connection = $resource->getConnection('core_write');
        $whereString = $this->makeWhereString($connection, $scopeId, $scope);
        $tableName = $resource->getTableName('sales/order');
        $where = array($whereString);
        $connection->update($tableName, array('mailchimp_campaign_id' => NULL), $where);
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
            $mailchimpStoreId = md5($this->getMCStoreName($scopeId, $scope) . '_' . $date);
            //create store in mailchimp
            try {
                $response = $this->getApiStores()->createMailChimpStore($mailchimpStoreId, $listId, $scopeId, $scope);
                //save in config
                $configValues = array(
                    array(Ebizmarts_MailChimp_Model_Config::GENERAL_MCSTOREID, $mailchimpStoreId),
                    array(Ebizmarts_MailChimp_Model_Config::GENERAL_MCISSYNCING, 1),
                    array(Ebizmarts_MailChimp_Model_Config::GENERAL_ECOMMMINSYNCDATEFLAG, Varien_Date::now()),
                );
                if (isset($response['connected_site']['site_script']['url'])) {
                    $configValues[] = array(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_MC_JS_URL, $response['connected_site']['site_script']['url']);
                    $this->saveMailchimpConfig($configValues, $scopeId, $scope);
                }
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
        $listId = $this->getGeneralList($scopeId, $scope);
        $apiKey = $this->getApiKey($scopeId, $scope);
        if ($mailchimpStoreId) {
            if ($listId && $apiKey) {
                try {
                    $this->getApiStores()->deleteMailChimpStore($mailchimpStoreId, $scopeId, $scope);
                } catch (MailChimp_Error $e) {
                    $this->logError($e->getFriendlyMessage());
                }
            }

            //delete configured webhook
            $this->deleteCurrentWebhook($scopeId, $scope, $listId);
            //clear store config values
            $this->deleteLocalMCStoreData($mailchimpStoreId, $scopeId, $scope);
        }
    }

    public function saveLastItemsSent($scopeId, $scope)
    {
        $isSyncing = $this->getMCIsSyncing($scopeId, $scope);
        if ($isSyncing != 1) {
            $customerLastId = $this->getLastCustomerSent($scopeId, $scope);
            $productLastId = $this->getLastProductSent($scopeId, $scope);
            $orderLastId = $this->getLastOrderSent($scopeId, $scope);
            $cartLastId = $this->getLastCartSent($scopeId, $scope);
            $promoCodeLastId = $this->getLastPromoCodeSent($scopeId, $scope);

            $configValues = array();
            $configValues[] = array(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_CUSTOMER_LAST_ID, $customerLastId);
            $configValues[] = array(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_PRODUCT_LAST_ID, $productLastId);
            $configValues[] = array(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_ORDER_LAST_ID, $orderLastId);
            $configValues[] = array(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_CART_LAST_ID, $cartLastId);
            $configValues[] = array(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_PCD_LAST_ID, $promoCodeLastId);
            $configValues[] = array(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_RESEND_ENABLED, 1);
            $configValues[] = array(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_RESEND_TURN, 1);
            $this->saveMailchimpConfig($configValues, $scopeId, $scope);
        }
    }

    protected function getLastCustomerSent($scopeId, $scope)
    {
        $lastCustomerSent = 0;
        $mcStoreId = $this->getMCStoreId($scopeId, $scope);
        $syncDataCollection = Mage::getModel('mailchimp/ecommercesyncdata')->getCollection()
            ->addFieldToFilter('mailchimp_store_id', array('eq' => $mcStoreId))
            ->addFieldToFilter('type', array('eq' => Ebizmarts_MailChimp_Model_Config::IS_CUSTOMER))
            ->setOrder('related_id', 'DESC')
            ->setPageSize(1);

        if ($syncDataCollection->getSize()) {
            $customerSyncData = $syncDataCollection->getFirstItem();
            $lastCustomerSent = $customerSyncData->getRelatedId();
        }

        return $lastCustomerSent;
    }

    protected function getLastProductSent($scopeId, $scope)
    {
        $lastProductSent = 0;
        $mcStoreId = $this->getMCStoreId($scopeId, $scope);
        $syncDataCollection = Mage::getModel('mailchimp/ecommercesyncdata')->getCollection()
            ->addFieldToFilter('mailchimp_store_id', array('eq' => $mcStoreId))
            ->addFieldToFilter('type', array('eq' => Ebizmarts_MailChimp_Model_Config::IS_PRODUCT))
            ->setOrder('related_id', 'DESC')
            ->setPageSize(1);

        if ($syncDataCollection->getSize()) {
            $productSyncData = $syncDataCollection->getFirstItem();
            $lastProductSent = $productSyncData->getRelatedId();
        }

        return $lastProductSent;
    }

    protected function getLastOrderSent($scopeId, $scope)
    {
        $lastOrderSent = 0;
        $mcStoreId = $this->getMCStoreId($scopeId, $scope);
        $syncDataCollection = Mage::getModel('mailchimp/ecommercesyncdata')->getCollection()
            ->addFieldToFilter('mailchimp_store_id', array('eq' => $mcStoreId))
            ->addFieldToFilter('type', array('eq' => Ebizmarts_MailChimp_Model_Config::IS_ORDER))
            ->setOrder('related_id', 'DESC')
            ->setPageSize(1);

        if ($syncDataCollection->getSize()) {
            $orderSyncData = $syncDataCollection->getFirstItem();
            $lastOrderSent = $orderSyncData->getRelatedId();
        }

        return $lastOrderSent;
    }

    protected function getLastCartSent($scopeId, $scope)
    {
        $lastCartSent = 0;
        $mcStoreId = $this->getMCStoreId($scopeId, $scope);
        $syncDataCollection = Mage::getModel('mailchimp/ecommercesyncdata')->getCollection()
            ->addFieldToFilter('mailchimp_store_id', array('eq' => $mcStoreId))
            ->addFieldToFilter('type', array('eq' => Ebizmarts_MailChimp_Model_Config::IS_QUOTE))
            ->setOrder('related_id', 'DESC')
            ->setPageSize(1);

        if ($syncDataCollection->getSize()) {
            $cartSyncData = $syncDataCollection->getFirstItem();
            $lastCartSent = $cartSyncData->getRelatedId();
        }
        return $lastCartSent;
    }

    protected function getLastPromoCodeSent($scopeId, $scope)
    {
        $lastPromoCodeSent = 0;
        $mcStoreId = $this->getMCStoreId($scopeId, $scope);
        $syncDataCollection = Mage::getResourceModel('mailchimp/ecommercesyncdata_collection')
            ->addFieldToFilter('mailchimp_store_id', array('eq' => $mcStoreId))
            ->addFieldToFilter('type', array('eq' => Ebizmarts_MailChimp_Model_Config::IS_PROMO_CODE))
            ->setOrder('related_id', 'DESC')
            ->setPageSize(1);

        if ($syncDataCollection->getSize()) {
            $promoCodeSyncData = $syncDataCollection->getFirstItem();
            $lastPromoCodeSent = $promoCodeSyncData->getRelatedId();
        }

        return $lastPromoCodeSent;
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
        try {
            $api = $this->getApi($scopeId, $scope);
            $mailchimpFields = array();
            try {
                $mailchimpFields = $api->lists->mergeFields->getAll($listId, null, null, 50);
            } catch (MailChimp_Error $e) {
                $this->logError($e->getFriendlyMessage());
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
                            if ($customFieldType['value'] == $customAtt) {
                                try {
                                    $api->lists->mergeFields->add($listId, $customFieldType['label'], $customFieldType['field_type'], null, $chimpTag);
                                } catch (MailChimp_Error $e) {
                                    $this->logError($e->getFriendlyMessage());
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
                            } catch (MailChimp_Error $e) {
                                $this->logError($e->getFriendlyMessage());
                            }
                        }
                    }
                }
            }
        } catch (Ebizmarts_MailChimp_Helper_Data_ApiKeyException $e) {
            $this->logError($e->getMessage());
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
     * @return Ebizmarts_MailChimp|null
     * @throws Exception
     */
    public function getApi($scopeId, $scope = null)
    {
        $apiKey = $this->getApiKey($scopeId, $scope);
        $api = null;
        if ($apiKey != null && $apiKey != "") {
            $timeOut = $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::GENERAL_TIME_OUT, $scopeId, $scope);
            $api = new Ebizmarts_MailChimp($apiKey, array('timeout'=>$timeOut), 'Mailchimp4Magento' . (string)$this->getConfig()->getNode('modules/Ebizmarts_MailChimp/version'));
        } else {
            $e = new Ebizmarts_MailChimp_Helper_Data_ApiKeyException('You must provide a MailChimp API key');
            $this->logError($e->getTraceAsString());
            throw $e;
        }

        return $api;
    }

    /**
     * Get Api object by ApiKey.
     *
     * @param $apiKey
     * @return Ebizmarts_MailChimp|null
     * @throws Ebizmarts_MailChimp_Helper_Data_ApiKeyException
     */
    public function getApiByKey($apiKey)
    {
        $api = null;
        if ($apiKey != null && $apiKey != "") {
            $api = new Ebizmarts_MailChimp($apiKey, null, 'Mailchimp4Magento' . (string)$this->getConfig()->getNode('modules/Ebizmarts_MailChimp/version'));
        } else {
            $e = new Ebizmarts_MailChimp_Helper_Data_ApiKeyException('You must provide a MailChimp API key');
            throw $e;
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
        if ($this->getMCStoreId($scopeId, $scope) && $this->getIfConfigExistsForScope(Ebizmarts_MailChimp_Model_Config::GENERAL_MCSTOREID, $scopeId, $scope)) {
            try {
                $this->getApiStores()->modifyName($name, $scopeId, $scope);
            } catch (MailChimp_Error $e) {
                $this->logError($e->getFriendlyMessage());
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
     * @param null $syncedFlag
     * @param bool $saveOnlyIfexists
     * @param null $deletedRelatedId
     * @param bool $allowBatchRemoval
     */
    public function saveEcommerceSyncData($itemId, $itemType, $mailchimpStoreId, $syncDelta = null, $syncError = null,
                                          $syncModified = 0, $syncDeleted = null, $token = null, $syncedFlag = null,
                                          $saveOnlyIfexists = false, $deletedRelatedId = null, $allowBatchRemoval = true
    )
    {
        $ecommerceSyncDataItem = $this->getEcommerceSyncDataItem($itemId, $itemType, $mailchimpStoreId);
        if (!$saveOnlyIfexists || $ecommerceSyncDataItem->getMailchimpSyncDelta()) {
            if ($syncDelta) {
                $ecommerceSyncDataItem->setData("mailchimp_sync_delta", $syncDelta);
            } elseif ($allowBatchRemoval) {
                $ecommerceSyncDataItem->setData("batch_id", null);
            }
            if ($syncError) {
                $ecommerceSyncDataItem->setData("mailchimp_sync_error", $syncError);
            }
            //Always set modified value to 0 when saving sync delta or errors.
            $ecommerceSyncDataItem->setData("mailchimp_sync_modified", $syncModified);
            if ($syncDeleted !== null) {
                $ecommerceSyncDataItem->setData("mailchimp_sync_deleted", $syncDeleted);
            }
            if ($token) {
                $ecommerceSyncDataItem->setData("mailchimp_token", $token);
            }
            if ($deletedRelatedId) {
                $ecommerceSyncDataItem->setData("deleted_related_id", $deletedRelatedId);
            }
            if ($syncedFlag !== null) {
                $ecommerceSyncDataItem->setData("mailchimp_synced_flag", $syncedFlag);
            }
            $ecommerceSyncDataItem->save();
        }
    }

    /**
     *  Load Ecommerce Sync Data Item if exists or set the values for a new one and return it.
     *
     * @param  $itemId
     * @param  $itemType
     * @param  $mailchimpStoreId
     * @return Varien_Object
     */
    public function getEcommerceSyncDataItem($itemId, $itemType, $mailchimpStoreId)
    {
        $collection = Mage::getModel('mailchimp/ecommercesyncdata')->getCollection()
            ->addFieldToFilter('related_id', array('eq' => $itemId))
            ->addFieldToFilter('type', array('eq' => $itemType))
            ->addFieldToFilter('mailchimp_store_id', array('eq' => $mailchimpStoreId))
            ->setCurPage(1)
            ->setPageSize(1);

        if ($collection->getSize()) {
            $ecommerceSyndDataItem = $collection->getFirstItem();
        } else {
            $ecommerceSyndDataItem = Mage::getModel('mailchimp/ecommercesyncdata')
                ->setData("related_id", $itemId)
                ->setData("type", $itemType)
                ->setData("mailchimp_store_id", $mailchimpStoreId);
        }
        return $ecommerceSyndDataItem;
    }

    public function getAllEcommerceSyncDataItemsPerId($itemId, $itemType)
    {
        $collection = Mage::getModel('mailchimp/ecommercesyncdata')->getCollection()
            ->addFieldToFilter('related_id', array('eq' => $itemId))
            ->addFieldToFilter('type', array('eq' => $itemType));

        return $collection;
    }

    /**
     * Filter collection by all the stores associated to MailChimp for given scope.
     *
     * @param  $collection
     * @param  $scopeId
     * @param  $scope
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
     * Get actual scope where the MailChimp store was created if exists.
     *
     * @param  $storeId
     * @return array|null
     */
    public function getMailChimpScopeByStoreId($storeId)
    {
        $mailchimpScope = null;
        $mailChimpStoreId = $this->getMCStoreId($storeId);
        $mailchimpScope = $this->getScopeByMailChimpStoreId($mailChimpStoreId);
        return $mailchimpScope;
    }

    /**
     * Return default store id for the configured scope on MailChimp.
     *
     * @param  $magentoStoreId
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function getDefaultStoreIdForMailChimpScope($magentoStoreId)
    {
        $scopeArray = $this->getMailChimpScopeByStoreId($magentoStoreId);
        if ($scopeArray) {
            if ($scopeArray['scope'] == 'websites') {
                $magentoStoreId = $this->getMageApp()
                    ->getWebsite($scopeArray['scope_id'])
                    ->getDefaultGroup()
                    ->getDefaultStoreId();
            } elseif ($scopeArray['scope'] == 'default') {
                $magentoStoreId = $this->getMageApp()
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
            $this->getConfig()->saveConfig($configValue[0], $configValue[1], $scope, $scopeId);
        }
        $this->getConfig()->cleanCache();
    }

    /**
     * If $productImageUrl not null returns it, else return $parentImageUrl.
     * Both parameters could be null.
     *
     * @param  $parentImageUrl
     * @param  $productImageUrl
     * @return mixed
     */
    public function getMailChimpProductImageUrl($parentImageUrl, $productImageUrl)
    {
        return ($productImageUrl) ? $productImageUrl : $parentImageUrl;
    }

    /**
     * Returns product image url by id, if it does not have one returns null.
     *
     * @param  $productId
     * @param  $magentoStoreId
     * @return null
     */
    public function getImageUrlById($productId, $magentoStoreId)
    {
        $productResourceModel = $this->getProductResourceModel();
        $productModel = $this->getProductModel();
        $configImageSize = $this->getImageSize($magentoStoreId);
        switch ($configImageSize) {
            case self::DEFAULT_SIZE:
                $imageSize = Ebizmarts_MailChimp_Model_Config::IMAGE_SIZE_DEFAULT;
                break;
            case self::SMALL_SIZE:
                $imageSize = Ebizmarts_MailChimp_Model_Config::IMAGE_SIZE_SMALL;
                break;
            case self::THUMBNAIL_SIZE:
                $imageSize = Ebizmarts_MailChimp_Model_Config::IMAGE_SIZE_THUMBNAIL;
                break;
            case self::ORIGINAL_SIZE:
                $imageSize = Ebizmarts_MailChimp_Model_Config::IMAGE_SIZE_DEFAULT;
                break;
            default:
                $imageSize = Ebizmarts_MailChimp_Model_Config::IMAGE_SIZE_DEFAULT;
                break;
        }
        $productImage = $productResourceModel->getAttributeRawValue($productId, $imageSize, $magentoStoreId);
        $productModel->setData($imageSize, $productImage);

        if ($productImage == 'no_selection' || $productImage == null) {
            $imageUrl = null;
        } else {
            $curStore = $this->getCurrentStoreId();
            $this->setCurrentStore($magentoStoreId);
            if ($configImageSize == self::ORIGINAL_SIZE){
                $imageUrl = $this->getOriginalPath($productImage);
            } else {
                $imageUrl = $this->getImageUrlForSize($imageSize, $productModel);
            }
            $this->setCurrentStore($curStore);
        }

        return $imageUrl;
    }

    public function getImageUrl($productModel, $imageSize)
    {
        return (string)$this->_getImageHelper()->init($productModel, $imageSize);
    }

    /**
     * Returns imageSize converted to camel case, and concatenates with functionName
     *
     * @param $imageSize
     * @return string
     */

    public function getImageFunctionName($imageSize)
    {

        $imageArray = $this->setImageSizeVarToArray($imageSize);
        $upperCaseImage = $this->setWordToCamelCase($imageArray);
        $functionName = $this->setFunctionName($upperCaseImage);

        return $functionName;
    }

    /**
     * @param $imageSize
     * @param $productModel
     * @return string
     */
    protected function getImageUrlForSize($imageSize, $productModel)
    {
        $upperCaseImage = (string)$this->getImageFunctionName($imageSize);
        $imageUrl = $productModel->$upperCaseImage();
        return $imageUrl;
    }

    /**
     * Returns imageSize separated word by word in array
     *
     * @param $imageSize
     * @return array
     */

    public function setImageSizeVarToArray($imageSize)
    {
        $imageArray = explode('_', $imageSize);

        return $imageArray;
    }

    /**
     * Returns imageSize in camel case concatenated
     *
     * @param $imageArray
     * @return string
     */

    public function setWordToCamelCase($imageArray)
    {
        $upperCaseImage = '';
        foreach ($imageArray as $word) {
            $word = ucwords($word);
            $upperCaseImage .= $word;
        }

        return $upperCaseImage;
    }

    /**
     * Returns imageSize in camel case concatenated with functionName
     *
     * @param $functionName
     * @return string
     */

    public function setFunctionName($functionName)
    {
        $functionName = "get" . $functionName . "Url";
        return $functionName;
    }


    /**
     * Return Catalog Product Image helper instance
     *
     * @return Mage_Catalog_Helper_Image
     */
    protected function _getImageHelper()
    {
        return Mage::helper('catalog/image');
    }

    public function getImageSize($scopeId, $scope = null)
    {
        return $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_IMAGE_SIZE, $scopeId, $scope);
    }

    /**
     * @return Mage_Catalog_Model_Resource_Product
     */
    public function getProductResourceModel()
    {
        $productResourceModel = Mage::getResourceModel('catalog/product');
        return $productResourceModel;
    }

    /**
     * @return false|Mage_Core_Model_Abstract
     */
    protected function getProductModel()
    {
        $productModel = Mage::getModel('catalog/product');
        return $productModel;
    }

    /**
     * @return Mage_Core_Model_App
     */
    public function getMageApp()
    {
        return Mage::app();
    }

    /**
     * @return int
     */
    protected function getCurrentStoreId()
    {
        $curStore = $this->getMageApp()->getStore()->getId();
        return $curStore;
    }

    /**
     * @param $magentoStoreId
     */
    protected function setCurrentStore($magentoStoreId)
    {
        $this->getMageApp()->setCurrentStore($magentoStoreId);
    }

    /**
     * @return Mage_Core_Model_Resource
     */
    public function getCoreResource()
    {
        return Mage::getSingleton('core/resource');
    }

    /**
     * @return Mage_Core_Model_Website
     */
    protected function getCoreWebsite()
    {
        return Mage::getModel('core/website');
    }

    /**
     * If orders with the given email exists, returns the date of the last order made.
     *
     * @param  $subscriberEmail
     * @return null
     */
    public function getLastDateOfPurchase($subscriberEmail)
    {
        $lastDateOfPurchase = null;

        $orderCollection = $this->getOrderCollectionByCustomerEmail($subscriberEmail);
        if ($orderCollection->getSize()) {
            $lastOrder = $orderCollection->setOrder('created_at', 'DESC')->getFirstItem();
            $lastDateOfPurchase = $lastOrder->getCreatedAt();
        }

        return $lastDateOfPurchase;
    }

    /**
     * Return true if there is a custom entry with the value given.
     *
     * @param  $value
     * @param  $scopeId
     * @param  $scope
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
     * @param  $scopeId
     * @param  null $scope
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

    /**
     * @param $subscriberEmail
     * @return mixed
     */
    protected function getOrderCollectionByCustomerEmail($subscriberEmail)
    {
        return Mage::getResourceModel('sales/order_collection')
            ->addFieldToFilter('customer_email', array('eq' => $subscriberEmail));
    }

    /**
     * Return html code for adding the MailChimp javascript.
     *
     * @return string
     */
    public function getMCJs()
    {
        $script = '';
        $url = null;
        $storeId = $this->getMageApp()->getStore()->getId();
        if ($this->isEcomSyncDataEnabled($storeId)) {
            $currentUrl = $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_MC_JS_URL, $storeId);
            if ($this->areJsUrlAndListScopesEqual($storeId)) {
                $url = $currentUrl;
            }

            if (!$url) {
                $url = $this->getApiStores()->getMCJsUrl($storeId, 'stores');
            }
            $script = '<script type="text/javascript" src="' . $url . '" defer></script>';
        }
        return $script;
    }

    /**
     * Handle data migration for versions that require it.
     */
    public function handleMigrationUpdates()
    {
        $initialTime = time();
        $migrateFrom115 = $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::GENERAL_MIGRATE_FROM_115, 0, 'default');
        $migrateFrom116 = $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::GENERAL_MIGRATE_FROM_116, 0, 'default');
        $migrateFrom1164 = $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::GENERAL_MIGRATE_FROM_1164, 0, 'default');

        if ($migrateFrom115) {
            $this->_migrateFrom115($initialTime);
        } elseif ($migrateFrom116 && !$this->timePassed($initialTime)) {
            $this->_migrateFrom116($initialTime);
        } elseif ($migrateFrom1164 && !$this->timePassed($initialTime)) {
            $this->_migrateFrom1164($initialTime);
        }
    }

    /**
     * Migrate data from version 1.1.5 to the mailchimp_ecommerce_sync_data table.
     *
     * @param $initialTime
     */
    protected function _migrateFrom115($initialTime)
    {
        //migrate data from older version to the new schemma
        if ($this->isEcommerceEnabled(0)) {
            $mailchimpStoreId = $this->getMCStoreId(0);

            //migrate customers
            $this->_migrateCustomersFrom115($mailchimpStoreId, $initialTime);

            if (!$this->timePassed($initialTime)) {
                //migrate products
                $this->_migrateProductsFrom115($mailchimpStoreId, $initialTime);
                if (!$this->timePassed($initialTime)) {
                    //migrate orders
                    $this->_migrateOrdersFrom115($mailchimpStoreId, $initialTime);
                    if (!$this->timePassed($initialTime)) {
                        //migrate carts
                        $finished = $this->_migrateCartsFrom115($mailchimpStoreId, $initialTime);
                        if ($finished) {

                            $this->getConfig()->deleteConfig(Ebizmarts_MailChimp_Model_Config::GENERAL_MIGRATE_FROM_115, 'default', 0);
                            $this->getConfig()->cleanCache();

                            //Remove attributes no longer used
                            $setup = Mage::getResourceModel('catalog/setup', 'catalog_setup');
                            try {
                                $setup->removeAttribute('catalog_product', 'mailchimp_sync_delta');
                                $setup->removeAttribute('catalog_product', 'mailchimp_sync_error');
                                $setup->removeAttribute('catalog_product', 'mailchimp_sync_modified');
                                $setup->removeAttribute('customer', 'mailchimp_sync_delta');
                                $setup->removeAttribute('customer', 'mailchimp_sync_error');
                                $setup->removeAttribute('customer', 'mailchimp_sync_modified');
                            } catch (Exception $e) {
                                $this->logError($e->getMessage());
                            }

                            $coreResource = $this->getCoreResource();
                            try {
                                $quoteTable = $coreResource->getTableName('sales/quote');
                                $setup->getConnection()->dropColumn($quoteTable, 'mailchimp_sync_delta');
                                $setup->getConnection()->dropColumn($quoteTable, 'mailchimp_sync_error');
                                $setup->getConnection()->dropColumn($quoteTable, 'mailchimp_deleted');
                                $setup->getConnection()->dropColumn($quoteTable, 'mailchimp_token');
                            } catch (Exception $e) {
                                $this->logError($e->getMessage());
                            }

                            try {
                                $orderTable = $coreResource->getTableName('sales/order');
                                $setup->getConnection()->dropColumn($orderTable, 'mailchimp_sync_delta');
                                $setup->getConnection()->dropColumn($orderTable, 'mailchimp_sync_error');
                                $setup->getConnection()->dropColumn($orderTable, 'mailchimp_sync_modified');
                            } catch (Exception $e) {
                                $this->logError($e->getMessage());
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Migrate Customers from version 1.1.5 to the mailchimp_ecommerce_sync_data table.
     *
     * @param $mailchimpStoreId
     * @param $initialTime
     */
    protected function _migrateCustomersFrom115($mailchimpStoreId, $initialTime)
    {
        try {
            $entityType = Mage::getSingleton('eav/config')->getEntityType('customer');
            $attribute = Mage::getModel('customer/attribute')->loadByCode($entityType, 'mailchimp_sync_delta');
            if ($attribute->getId()) {
                $mailchimpTableName = $this->getCoreResource()->getTableName('mailchimp/ecommercesyncdata');
                $customerCollection = Mage::getResourceModel('customer/customer_collection');
                $customerCollection->addAttributeToFilter('mailchimp_sync_delta', array('gt' => '0000-00-00 00:00:00'));
                $customerCollection->getSelect()->joinLeft(
                    array('m4m' => $mailchimpTableName),
                    "m4m.related_id = e.entity_id and m4m.type = '" . Ebizmarts_MailChimp_Model_Config::IS_CUSTOMER . "'
            AND m4m.mailchimp_store_id = '" . $mailchimpStoreId . "'",
                    array('m4m.*')
                );
                $customerCollection->getSelect()->where(
                    "m4m.mailchimp_sync_delta IS null"
                );
                $this->_makeForCollectionItem(
                    $customerCollection, $mailchimpStoreId, $initialTime, function ($customer, $mailchimpStoreId) {
                    $customerId = $customer->getEntityId();
                    $customerObject = Mage::getModel('customer/customer')->load($customerId);
                    $syncError = null;
                    $syncModified = null;
                    $syncDelta = $customerObject->getMailchimpSyncDelta();
                    if ($customer->getMailchimpSyncError()) {
                        $syncError = $customer->getMailchimpSyncError();
                    }

                    if ($customer->getMailchimpSyncModified()) {
                        $syncModified = $customer->getMailchimpSyncModified();
                    }

                    $this->saveEcommerceSyncData($customerId, Ebizmarts_MailChimp_Model_Config::IS_CUSTOMER, $mailchimpStoreId, $syncDelta, $syncError, $syncModified);
                }
                );
            }
        } catch (Exception $e) {
            $this->logError($e->getMessage());
        }
    }

    /**
     * Migrate Products from version 1.1.5 to the mailchimp_ecommerce_sync_data table.
     *
     * @param $mailchimpStoreId
     * @param $initialTime
     */
    protected function _migrateProductsFrom115($mailchimpStoreId, $initialTime)
    {
        try {
            $entityType = Mage_Catalog_Model_Product::ENTITY;
            $attributeCode = 'mailchimp_sync_delta';
            $attribute = Mage::getModel('eav/entity_attribute')
                ->loadByCode($entityType, $attributeCode);
            if ($attribute->getId()) {
                $mailchimpTableName = $this->getCoreResource()->getTableName('mailchimp/ecommercesyncdata');
                $productCollection = Mage::getResourceModel('catalog/product_collection');
                $productCollection->addAttributeToFilter('mailchimp_sync_delta', array('gt' => '0000-00-00 00:00:00'));
                $productCollection->getSelect()->joinLeft(
                    array('m4m' => $mailchimpTableName),
                    "m4m.related_id = e.entity_id and m4m.type = '" . Ebizmarts_MailChimp_Model_Config::IS_PRODUCT . "'
            AND m4m.mailchimp_store_id = '" . $mailchimpStoreId . "'",
                    array('m4m.*')
                );
                $productCollection->getSelect()->where(
                    "m4m.mailchimp_sync_delta IS null"
                );
                $this->_makeForCollectionItem(
                    $productCollection, $mailchimpStoreId, $initialTime, function ($product, $mailchimpStoreId) {
                    $productId = $product->getEntityId();
                    $_resource = Mage::getResourceSingleton('catalog/product');
                    $syncDelta = $_resource->getAttributeRawValue($productId, 'mailchimp_sync_delta', $this->getMageApp()->getStore());
                    $syncError = null;
                    $syncModified = null;
                    if ($product->getMailchimpSyncError()) {
                        $syncError = $product->getMailchimpSyncError();
                    }

                    if ($product->getMailchimpSyncModified()) {
                        $syncModified = $product->getMailchimpSyncModified();
                    }

                    $this->saveEcommerceSyncData($productId, Ebizmarts_MailChimp_Model_Config::IS_PRODUCT, $mailchimpStoreId, $syncDelta, $syncError, $syncModified);
                }
                );
            }
        } catch (Exception $e) {
            $this->logError($e->getMessage());
        }
    }

    /**
     * Migrate Orders from version 1.1.5 to the mailchimp_ecommerce_sync_data table.
     *
     * @param $mailchimpStoreId
     * @param $initialTime
     */
    protected function _migrateOrdersFrom115($mailchimpStoreId, $initialTime)
    {
        try {
            $resource = $this->getCoreResource();
            $readConnection = $resource->getConnection('core_read');
            $tableName = $resource->getTableName('sales/order');
            $orderFields = $readConnection->describeTable($tableName);
            if (isset($orderFields['mailchimp_sync_delta'])) {
                $mailchimpTableName = $resource->getTableName('mailchimp/ecommercesyncdata');
                $orderCollection = Mage::getResourceModel('sales/order_collection');

                $orderCollection->getSelect()->joinLeft(
                    array('m4m' => $mailchimpTableName),
                    "m4m.related_id = main_table.entity_id AND m4m.type = '" . Ebizmarts_MailChimp_Model_Config::IS_ORDER . "'
            AND m4m.mailchimp_store_id = '" . $mailchimpStoreId . "'",
                    array('m4m.*')
                );
                $orderCollection->getSelect()->where("m4m.mailchimp_sync_delta IS NULL AND main_table.mailchimp_sync_delta > '0000-00-00 00:00:00'");
                $this->_makeForCollectionItem(
                    $orderCollection, $mailchimpStoreId, $initialTime, function ($order, $mailchimpStoreId) {
                    $orderId = $order->getEntityId();
                    $syncError = null;
                    $syncModified = null;
                    $orderObject = Mage::getModel('sales/order')->load($orderId);
                    $syncDelta = $orderObject->getMailchimpSyncDelta();
                    if ($order->getMailchimpSyncError()) {
                        $syncError = $order->getMailchimpSyncError();
                    }

                    if ($order->getMailchimpSyncModified()) {
                        $syncModified = $order->getMailchimpSyncModified();
                    }

                    $this->saveEcommerceSyncData($orderId, Ebizmarts_MailChimp_Model_Config::IS_ORDER, $mailchimpStoreId, $syncDelta, $syncError, $syncModified);
                }
                );
            }
        } catch (Exception $e) {
            $this->logError($e->getMessage());
        }
    }

    /**
     * Migrate Carts from version 1.1.5 to the mailchimp_ecommerce_sync_data table.
     *
     * @param  $mailchimpStoreId
     * @param  $initialTime
     * @return bool
     */
    protected function _migrateCartsFrom115($mailchimpStoreId, $initialTime)
    {
        try {
            $resource = $this->getCoreResource();
            $readConnection = $resource->getConnection('core_read');
            $tableName = $resource->getTableName('sales/quote');
            $quoteFields = $readConnection->describeTable($tableName);
            if (isset($quoteFields['mailchimp_sync_delta'])) {
                $mailchimpTableName = $resource->getTableName('mailchimp/ecommercesyncdata');
                $quoteCollection = Mage::getResourceModel('sales/quote_collection');
                $quoteCollection->getSelect()->joinLeft(
                    array('m4m' => $mailchimpTableName),
                    "m4m.related_id = main_table.entity_id and m4m.type = '" . Ebizmarts_MailChimp_Model_Config::IS_QUOTE . "'
            AND m4m.mailchimp_store_id = '" . $mailchimpStoreId . "'",
                    array('m4m.*')
                );
                // be sure that the quotes are already in mailchimp and not deleted
                $quoteCollection->getSelect()->where("m4m.mailchimp_sync_delta IS NULL AND main_table.mailchimp_sync_delta > '0000-00-00 00:00:00'");
                $finished = $this->_makeForCollectionItem(
                    $quoteCollection, $mailchimpStoreId, $initialTime, function ($quote, $mailchimpStoreId) {
                    $quoteId = $quote->getEntityId();
                    $syncError = null;
                    $syncDeleted = null;
                    $token = null;
                    $quoteObject = Mage::getModel('sales/order')->load($quoteId);
                    $syncDelta = $quoteObject->getMailchimpSyncDelta();
                    if ($quote->getMailchimpSyncError()) {
                        $syncError = $quote->getMailchimpSyncError();
                    }

                    if ($quote->getMailchimpSyncDeleted()) {
                        $syncDeleted = $quote->getMailchimpSyncDeleted();
                    }

                    if ($quote->getMailchimpToken()) {
                        $token = $quote->getMailchimpToken();
                    }

                    $this->saveEcommerceSyncData($quoteId, Ebizmarts_MailChimp_Model_Config::IS_QUOTE, $mailchimpStoreId, $syncDelta, $syncError, null, $syncDeleted, $token);
                }
                );
            } else {
                $finished = true;
            }

            return $finished;
        } catch (Exception $e) {
            $this->logError($this->__('Unexpected error happened during migration from version 1.1.5 to 1.1.6. Please contact our support at ') . 'mailchimp@ebizmarts-desk.zendesk.com' . $this->__(' See error details below.'));
            $this->logError($e->getMessage());
            return false;
        }
    }

    /**
     * Helper function for data migration from version 1.1.5.
     *
     * @param  $collection
     * @param  $mailchimpStoreId
     * @param  $initialTime
     * @param  Closure $callback
     * @return bool
     */
    protected function _makeForCollectionItem($collection, $mailchimpStoreId, $initialTime, Closure $callback)
    {
        $finished = false;
        if (!$collection->getSize()) {
            $finished = true;
        }
        //        $collection->addFieldToFilter('mailchimp_sync_delta', array('gt' => '0000-00-00 00:00:00'));
        $collection->setPageSize(100);

        $pages = $collection->getLastPageNumber();
        $currentPage = 1;

        do {
            $collection->setCurPage($currentPage);
            $collection->load();

            foreach ($collection as $collectionItem) {
                $callback($collectionItem, $mailchimpStoreId);
            }

            $currentPage++;
            // clear collection (if not done, the same page will be loaded each loop) - will also free memory
            $collection->clear();
            if ($this->timePassed($initialTime)) {
                break;
            }
            if ($currentPage == $pages) {
                $finished = true;
            }
        } while ($currentPage <= $pages);
        return $finished;

    }

    /**
     * Check if more than 270 seconds passed since the migration started to prevent the job to take too long.
     *
     * @param  $initialTime
     * @return bool
     */
    public function timePassed($initialTime)
    {
        $storeCount = count($this->getMageApp()->getStores());
        $timePassed = false;
        $finalTime = time();
        $difference = $finalTime - $initialTime;
        //Set minimum of 30 seconds per store view.
        $timeForAllStores = (30 * $storeCount);
        //Set total time in 4:30 minutes if it is lower.
        $timeAmount = ($timeForAllStores < 270) ? 270 : $timeForAllStores;
        if ($difference > $timeAmount) {
            $timePassed = true;
        }
        return $timePassed;
    }

    /**
     * Migrate data from version 1.1.6.
     *
     * @param $initialTime
     */
    protected function _migrateFrom116($initialTime)
    {
        $this->_setIsSyncingIfFinishedInAllStores(true);
        $finished = $this->_migrateOrdersFrom116($initialTime);
        if ($finished) {
            $this->_setIsSyncingIfFinishedInAllStores(false);
            $this->delete116MigrationConfigData();
        }
    }

    /**
     * Modify is_syncing value if initial sync finished for all stores.
     *
     * @param $syncValue
     */
    protected function _setIsSyncingIfFinishedInAllStores($syncValue)
    {
        $stores = $this->getMageApp()->getStores();
        foreach ($stores as $storeId => $store) {
            $ecommEnabled = $this->isEcomSyncDataEnabled($storeId);
            if ($ecommEnabled) {
                $this->setIsSyncingIfFinishedPerScope($syncValue, $storeId);
            }
        }
    }


    /**
     * Update Order ids to the Increment id in MailChimp.
     *
     * @param  $initialTime
     * @return bool
     */
    protected function _migrateOrdersFrom116($initialTime)
    {
        $finished = false;
        if (!$this->timePassed($initialTime)) {
            $finished = true;
            $stores = $this->getMageApp()->getStores();
            foreach ($stores as $storeId => $store) {
                if ($this->isEcomSyncDataEnabled($storeId)) {
                    Mage::getModel('mailchimp/api_batches')->replaceAllOrders($initialTime, $storeId);
                }
                if ($this->timePassed($initialTime)) {
                    $finished = false;
                    break;
                }
            }
        }
        return $finished;
    }

    /**
     * Return if migration has finished checking the config values.
     *
     * @return bool
     */
    public function migrationFinished()
    {
        $migrationFinished = false;

        $migrateFrom115 = $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::GENERAL_MIGRATE_FROM_115, 0, 'default');
        $migrateFrom116 = $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::GENERAL_MIGRATE_FROM_116, 0, 'default');
        $migrateFrom1164 = $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::GENERAL_MIGRATE_FROM_1164, 0, 'default');

        if (!$migrateFrom115 && !$migrateFrom116 && !$migrateFrom1164) {
            $migrationFinished = true;
        }
        return $migrationFinished;
    }

    /**
     * Delete config data for migration from 1.1.6.
     */
    public function delete116MigrationConfigData()
    {
        $stores = $this->getMageApp()->getStores();
        $this->getConfig()->deleteConfig(Ebizmarts_MailChimp_Model_Config::GENERAL_MIGRATE_FROM_116, 'default', 0);
        foreach ($stores as $storeId => $store) {
            $this->getConfig()->deleteConfig(Ebizmarts_MailChimp_Model_Config::GENERAL_MIGRATE_LAST_ORDER_ID, 'stores', $storeId);
        }
        $this->getConfig()->cleanCache();
    }

    /**
     * Migrate data from version 1.1.6.4.
     *
     * @param $initialTime
     */
    protected function _migrateFrom1164($initialTime)
    {
        if (!$this->timePassed($initialTime)) {
            $write_connection = $this->getCoreResource()->getConnection('core_write');
            $resource = Mage::getResourceModel('mailchimp/ecommercesyncdata');
            $write_connection->update($resource->getMainTable(), array('batch_id' => '1'), "batch_id = 0");
            $this->getConfig()->deleteConfig(Ebizmarts_MailChimp_Model_Config::GENERAL_MIGRATE_FROM_1164, 'default', 0);
            $this->getConfig()->cleanCache();
        }
    }

    /**
     * Return the list of magento store IDs configured to synchronise to
     * the given mailchimp list ID.
     *
     * @param   string $listId Mailchimp List ID
     * @returns int[] $magentoStoreIds Magento store IDs that sync to this list.
     */
    public function getMagentoStoreIdsByListId($listId)
    {
        $storeIds = Mage::registry('mailchimp_store_ids_for_list_' . $listId);
        if ($storeIds === null) {
            $stores = $this->getMageApp()->getStores();
            $storeIds = array();
            foreach ($stores as $storeId => $store) {
                if ($this->isSubscriptionEnabled($storeId)) {
                    $storeListId = $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::GENERAL_LIST, $storeId);
                    if ($storeListId == $listId) {
                        $storeIds[] = $storeId;
                    }
                }
            }
            Mage::register('mailchimp_store_ids_for_list_' . $listId, $storeIds);
        }

        return $storeIds;
    }

    /**
     * Return the newsletter subscriber model for the given subscriber email
     * address for magento stores subscribed to the given Mailchimp List ID.
     *
     * @param   string $listId Mailchimp list ID
     * @param   string $email Subscriber email address.
     * @returns Mage_Newsletter_Model_Subscriber $subscriber
     */
    public function loadListSubscriber($listId, $email)
    {
        $subscriber = null;

        $storeIds = $this->getMagentoStoreIdsByListId($listId);
        //add store id 0 for those created from the back end.
        $storeIds[] = 0;
        if (count($storeIds) > 0) {
            $subscriber = Mage::getModel('newsletter/subscriber')->getCollection()
                ->addFieldToFilter('store_id', array('in' => $storeIds))
                ->addFieldToFilter('subscriber_email', $email)
                ->getFirstItem();

            if (!$subscriber->getId()) {
                /**
                 * No subscriber exists. Try to find a customer based
                 * on email address for the given stores instead.
                 */
                $subscriber = Mage::getModel('newsletter/subscriber');
                $subscriber->setEmail($email);
                $customer = $this->loadListCustomer($listId, $email);
                if ($customer) {
                    $subscriber->setStoreId($customer->getStoreId());
                    $subscriber->setCustomerId($customer->getId());
                } else {
                    /**
                     * No customer with that address. Just assume the first
                     * store ID is the correct one as there is no other way
                     * to tell which store this mailchimp list guest subscriber
                     * belongs to.
                     */
                    $subscriber->setStoreId($storeIds[0]);
                }
            }
        }
        return $subscriber;
    }

    /**
     * Return the customer model for the given subscriber email
     * address for magento stores subscribed to the given Mailchimp List ID.
     *
     * @param   string $listId Mailchimp list ID
     * @param   string $email Subscriber email address.
     * @returns Mage_Customer_Model_Customer|null $customer
     */
    public function loadListCustomer($listId, $email)
    {
        $customer = null;

        $storeIds = $this->getMagentoStoreIdsByListId($listId);
        if (count($storeIds) > 0) {
            $customer = Mage::getResourceModel('customer/customer_collection')
                ->addFieldToFilter('store_id', array('in' => $storeIds))
                ->addFieldToFilter('email', array('eq' => $email))
                ->getFirstItem();
            if ($customer->getId()) {
                $customer = Mage::getModel('customer/customer')->load($customer->getId());
            } else {
                $customer = null;
            }
        }

        return $customer;
    }

    public function createWebhookIfRequired($scopeId, $scope = 'stores')
    {
        $webhookId = $this->getWebhookId($scopeId, $scope);
        $enabled = $this->isSubscriptionEnabled($scopeId, $scope);
        if ($enabled && !$webhookId) {
            $this->handleWebhookChange($scopeId, $scope);
        }
    }

    public function handleWebhookChange($scopeId, $scope = 'stores')
    {
        $webhookScope = $this->getRealScopeForConfig(Ebizmarts_MailChimp_Model_Config::GENERAL_LIST, $scopeId, $scope);
        $listId = $this->getGeneralList($scopeId, $scope);
        $this->deleteCurrentWebhook($webhookScope['scope_id'], $webhookScope['scope'], $listId);
        if ($this->isSubscriptionEnabled($scopeId, $scope)) {
            $this->createNewWebhook($webhookScope['scope_id'], $webhookScope['scope'], $listId);
        }
    }

    protected function deleteCurrentWebhook($scopeId, $scope, $listId)
    {
        try {
            $api = $this->getApi($scopeId, $scope);
            $webhookId = $this->getWebhookId($scopeId, $scope);
            $apiKey = $this->getApiKey($scopeId, $scope);
            if ($webhookId && $apiKey && $listId) {
                try {
                    $api->lists->webhooks->delete($listId, $webhookId);
                } catch (MailChimp_Error $e) {
                    $this->logError($e->getFriendlyMessage());
                } catch (Exception $e) {
                    $this->logError($e->getMessage());
                }
                $this->getConfig()->deleteConfig(Ebizmarts_MailChimp_Model_Config::GENERAL_WEBHOOK_ID, $scope, $scopeId);
            } else {
                $webhookUrl = $this->getWebhookUrl($scopeId, $scope);
                try {
                    if ($listId) {
                        $webhooks = $api->lists->webhooks->getAll($listId);
                        foreach ($webhooks['webhooks'] as $webhook) {
                            if (strpos($webhook['url'], $webhookUrl) !== false) {
                                $api->lists->webhooks->delete($listId, $webhook['id']);
                            }
                        }
                    }
                } catch (MailChimp_Error $e) {
                    $this->logError($e->getFriendlyMessage());
                } catch (Exception $e) {
                    $this->logError($e->getMessage());
                }
            }
        } catch (Ebizmarts_MailChimp_Helper_Data_ApiKeyException $e) {
            $this->logError($e->getMessage());
        }
    }

    /**
     * Returns true on successful creation, or error message if it fails
     */
    public function createNewWebhook($scopeId, $scope, $listId)
    {
        $hookUrl = $this->getWebhookUrl();
        try {
            $api = $this->getApi($scopeId, $scope);
            if ($this->getTwoWaySyncEnabled($scopeId, $scope)) {
                $events = array(
                    'subscribe' => true,
                    'unsubscribe' => true,
                    'profile' => false,
                    'cleaned' => true,
                    'upemail' => true,
                    'campaign' => false
                );
                $sources = array(
                    'user' => true,
                    'admin' => true,
                    'api' => true
                );
            } else {
                $events = array(
                    'subscribe' => true,
                    'unsubscribe' => true,
                    'profile' => false,
                    'cleaned' => false,
                    'upemail' => false,
                    'campaign' => false
                );
                $sources = array(
                    'user' => true,
                    'admin' => true,
                    'api' => false
                );
            }
            try {
                $response = $api->lists->webhooks->getAll($listId);
                $createWebhook = true;
                if (isset($response['total_items']) && $response['total_items'] > 0) {
                    foreach ($response['webhooks'] as $webhook) {
                        if ($webhook['url'] == $hookUrl) {
                            $createWebhook = false;
                        }
                    }
                }
                if ($createWebhook) {
                    $newWebhook = $api->lists->webhooks->add($listId, $hookUrl, $events, $sources);
                    $newWebhookId = $newWebhook['id'];
                    $configValues = array(array(Ebizmarts_MailChimp_Model_Config::GENERAL_WEBHOOK_ID, $newWebhookId));
                    $this->saveMailchimpConfig($configValues, $scopeId, $scope);
                    return true;
                } else {
                    return $this->__('The webhook already exists.');
            }
            } catch (MailChimp_Error $e) {
                $errorMessage = $e->getFriendlyMessage();
                $this->logError($errorMessage);
                $textToCompare = 'The resource submitted could not be validated. For field-specific details, see the \'errors\' array.';
                if ($e->getMailchimpDetails() == $textToCompare) {
                    $errorMessage = 'Your store could not be accessed by MailChimp\'s Api. Please confirm the URL: ' . $hookUrl . ' is accessible externally to allow the webhook creation.';
                    $this->logError($errorMessage);
                }
                return $this->__($errorMessage);
            } catch (Exception $e) {
                $this->logError($e->getMessage());
            }
        } catch (Ebizmarts_MailChimp_Helper_Data_ApiKeyException $e) {
            $this->logError($e->getMessage());
        }
    }


    protected function getWebhookUrl()
    {
        $store = $this->getMageApp()->getDefaultStoreView();
        $webhooksKey = $this->getWebhooksKey();
        //Generating Webhooks URL
        $url = Ebizmarts_MailChimp_Model_ProcessWebhook::WEBHOOKS_PATH;
        $hookUrl = $store->getUrl(
            $url, array(
                'wkey' => $webhooksKey,
                '_nosid' => true,
                '_secure' => true,
            )
        );

        if (false != strstr($hookUrl, '?', true)) {
            $hookUrl = strstr($hookUrl, '?', true);
        }
        return $hookUrl;
    }

    public function getRealScopeForConfig($path, $scopeId, $scope = 'stores')
    {
        $websiteId = null;
        if ($scope == 'stores') {
            $websiteId = Mage::getModel('core/store')->load($scopeId)->getWebsiteId();
            $scopeIdsArray = array($scopeId, $websiteId, 0);
        } else {
            $scopeIdsArray = array($scopeId, 0);
        }
        $configCollection = Mage::getResourceModel('core/config_data_collection')
            ->addFieldToFilter('path', array('eq' => $path))
            ->addFieldToFilter('scope_id', array('in' => $scopeIdsArray));

        $scopeSoFar = null;
        foreach ($configCollection as $config) {
            //Discard possible extra website or store
            if ($this->isExtraEntry($config, $scope, $scopeId, $websiteId)) {
                continue;
            }
            switch ($config->getScope()) {
                case 'stores':
                    $scopeSoFar = array('scope_id' => $config->getScopeId(), 'scope' => $config->getScope());
                    break;
                case 'websites':
                    if (!$scopeSoFar || $scopeSoFar['scope'] == 'default') {
                        $scopeSoFar = array('scope_id' => $config->getScopeId(), 'scope' => $config->getScope());
                    }
                    break;
                case 'default':
                    if ($scopeSoFar['scope'] != 'stores') {
                        $scopeSoFar = array('scope_id' => $config->getScopeId(), 'scope' => $config->getScope());
                    }
                    break;
            }

        }
        return $scopeSoFar;
    }

    /**
     * Return true if the config entry does not belong to the store required or website that contains that store.
     *
     * @param $config
     * @param $scope
     * @param $scopeId
     * @param $websiteId
     * @return bool
     */
    protected function isExtraEntry($config, $scope, $scopeId, $websiteId)
    {
        return $this->isNotDefaultScope($config) && ($this->isIncorrectScope($config, $scope) || $this->isDifferentWebsite($config, $scope, $websiteId) || $this->isDifferentStoreView($config, $scope, $scopeId));
    }

    public function updateSubscriberSyndData($itemId, $syncDelta = null, $syncError = null, $syncModified = 0, $syncDeleted = null)
    {
        $subscriber = Mage::getModel('newsletter/subscriber')->load($itemId);
        if ($subscriber->getId()) {
            if ($syncDelta) {
                $subscriber->setData("mailchimp_sync_delta", $syncDelta);
            }
            if ($syncError) {
                $subscriber->setData("mailchimp_sync_error", $syncError);
            }
            $subscriber->setData("mailchimp_sync_modified", $syncModified);
            if ($syncDeleted) {
                $subscriber->setData("mailchimp_sync_deleted", $syncDeleted);
            }
            $subscriber->setSubscriberSource(Ebizmarts_MailChimp_Model_Subscriber::SUBSCRIBE_SOURCE);
            $subscriber->save();
        }
    }

    /**
     * Get date configured for subscriber data to be sent for the given scope.
     *
     * @param $scopeId
     * @param string $scope
     * @return mixed
     */
    public function getSubMinSyncDateFlag($scopeId, $scope = 'stores')
    {
        return $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::GENERAL_SUBMINSYNCDATEFLAG, $scopeId, $scope);
    }

    /**
     * Return true if the configPath has been saved specifically for the given scope.
     *
     * @param $configPath
     * @param $scopeId
     * @param string $scope
     * @return bool|mixed
     */
    public function getIfConfigExistsForScope($configPath, $scopeId, $scope = 'stores')
    {
        $configPathArray = explode('/', $configPath);
        $configName = $configPathArray[2];
        $configAssociatedToScope = Mage::registry('mailchimp_' . $configName . '_exists_for_scope_' . $scope . '_' . $scopeId);
        if ($configAssociatedToScope === null) {
            $configAssociatedToScope = false;
            $numEntries = Mage::getResourceModel('core/config_data_collection')
                ->addFieldToFilter('path', array('eq' => $configPath))
                ->addFieldToFilter('scope', array('eq' => $scope))
                ->addFieldToFilter('scope_id', array('eq' => $scopeId))
                ->getSize();
            if ($numEntries) {
                $configAssociatedToScope = true;
            }
            Mage::register('mailchimp_' . $configName . '_exists_for_scope_' . $scope . '_' . $scopeId, $configAssociatedToScope);
        }

        return $configAssociatedToScope;
    }

    /**
     * @param $batchArray
     * @param $productData
     * @param $counter
     * @return mixed
     */
    public function addEntriesToArray($batchArray, $productData, $counter)
    {
        if (count($productData)) {
            foreach ($productData as $p) {
                if (!empty($p)) {
                    $batchArray[$counter] = $p;
                    $counter++;
                }
            }
        }
        return array($batchArray, $counter);
    }

    /**
     * @return Ebizmarts_MailChimp_Model_Api_Stores
     */
    protected function getApiStores()
    {
        return Mage::getModel('mailchimp/api_stores');
    }

    public function getCheckoutSubscribeValue($scopeId, $scope = 'stores')
    {
        return $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::GENERAL_CHECKOUT_SUBSCRIBE, $scopeId, $scope);
    }

    public function isCheckoutSubscribeEnabled($scopeId, $scope = 'stores')
    {
        return ($this->isSubscriptionEnabled($scopeId, $scope) && $this->getCheckoutSubscribeValue($scopeId, $scope) != Ebizmarts_MailChimp_Model_System_Config_Source_Checkoutsubscribe::DISABLED);
    }

    /**
     * Modify is_syncing value if initial sync finished in given scope.
     *
     * @param $syncValue
     * @param $scopeId
     * @param $scope
     */
    protected function setIsSyncingIfFinishedPerScope($syncValue, $scopeId, $scope = 'stores')
    {
        try {
            $mailchimpApi = $this->getApi($scopeId, $scope);
            $mailchimpStoreId = $this->getMCStoreId($scopeId, $scope);
            $isSyncing = $this->getMCIsSyncing($scopeId, $scope);
            if ($mailchimpStoreId && $isSyncing != 1) {
                $this->getApiStores()->editIsSyncing($mailchimpApi, $syncValue, $mailchimpStoreId);
            }
        } catch (Ebizmarts_MailChimp_Helper_Data_ApiKeyException $e) {
            $this->logError($e->getMessage());
        }
    }

    public function setResendTurn($value, $scopeId, $scope = 'stores')
    {
        $configValue = array(array(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_RESEND_TURN, $value));
        $this->saveMailchimpConfig($configValue, $scopeId, $scope);
    }

    public function getResendTurn($scopeId, $scope = 'stores')
    {
        return $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_RESEND_TURN, $scopeId, $scope);
    }

    public function getResendEnabled($scopeId, $scope = 'stores')
    {
        return $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_RESEND_ENABLED, $scopeId, $scope);
    }

    public function getCustomerResendLastId($scopeId, $scope = 'stores')
    {
        return $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_CUSTOMER_LAST_ID, $scopeId, $scope);
    }

    public function getProductResendLastId($scopeId, $scope = 'stores')
    {
        return $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_PRODUCT_LAST_ID, $scopeId, $scope);
    }

    public function getOrderResendLastId($scopeId, $scope = 'stores')
    {
        return $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_ORDER_LAST_ID, $scopeId, $scope);
    }

    public function getCartResendLastId($scopeId, $scope = 'stores')
    {
        return $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_CART_LAST_ID, $scopeId, $scope);
    }

    public function getPromoCodeResendLastId($scopeId, $scope = 'stores')
    {
        return $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_PCD_LAST_ID, $scopeId, $scope);
    }

    public function addResendFilter($collection, $magentoStoreId, $itemType)
    {
        $resendEnabled = $this->getResendEnabled($magentoStoreId);
        if ($resendEnabled) {
            $resendTurn = $this->getResendTurn($magentoStoreId);
            $keyCol = 'entity_id';
            switch ($itemType) {
                case Ebizmarts_MailChimp_Model_Config::IS_ORDER:
                    $lastItemSent = $this->getOrderResendLastId($magentoStoreId);
                    break;
                case Ebizmarts_MailChimp_Model_Config::IS_PRODUCT:
                    $lastItemSent = $this->getProductResendLastId($magentoStoreId);
                    break;
                case Ebizmarts_MailChimp_Model_Config::IS_CUSTOMER:
                    $lastItemSent = $this->getCustomerResendLastId($magentoStoreId);
                    break;
                case Ebizmarts_MailChimp_Model_Config::IS_QUOTE:
                    $lastItemSent = $this->getCartResendLastId($magentoStoreId);
                    break;
                case Ebizmarts_MailChimp_Model_Config::IS_PROMO_CODE:
                    $keyCol = "coupon_id";
                    $lastItemSent = $this->getPromoCodeResendLastId($magentoStoreId);
                    break;
                default:
                    $lastItemSent = 0;
                    $this->logError($this->__('The item type sent in the filter does not match any of the available options.'));
            }
            if ($resendTurn) {
                $collection->addFieldToFilter($keyCol, array('lteq' => $lastItemSent));
            } else {
                $collection->addFieldToFilter($keyCol, array('gt' => $lastItemSent));
            }
        }
    }

    /**
     * Check if all items have been sent and delete config values used in the resend process
     *
     * @param $scopeId
     * @param string $scope
     */
    public function handleResendFinish($scopeId, $scope = 'stores')
    {
        $allItemsSent = $this->allResendItemsSent($scopeId, $scope);
        if ($allItemsSent) {
            $this->deleteResendConfigValues($scopeId, $scope);
        }
    }

    protected function deleteResendConfigValues($scopeId, $scope = 'stores')
    {
        $this->getConfig()->deleteConfig(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_CUSTOMER_LAST_ID, $scope, $scopeId);
        $this->getConfig()->deleteConfig(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_PRODUCT_LAST_ID, $scope, $scopeId);
        $this->getConfig()->deleteConfig(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_ORDER_LAST_ID, $scope, $scopeId);
        $this->getConfig()->deleteConfig(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_CART_LAST_ID, $scope, $scopeId);
        $this->getConfig()->deleteConfig(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_PCD_LAST_ID, $scope, $scopeId);
        $this->getConfig()->deleteConfig(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_RESEND_ENABLED, $scope, $scopeId);
        $this->getConfig()->deleteConfig(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_RESEND_TURN, $scope, $scopeId);
        $this->getConfig()->cleanCache();
    }

    protected function allResendItemsSent($scopeId, $scope = 'stores')
    {
        if ($scope == 'stores') {
            $allItemsSent = $this->allResendItemsSentPerStoreView($scopeId);
        } else {
            if ($scope == 'websites') {
                $website = $this->getCoreWebsite()->load($scopeId);
                $storeIds = $website->getStoreIds();
                $allItemsSent = $this->allResendItemsSentPerScope($storeIds);
            } else {
                $stores = $this->getMageApp()->getStores();
                $allItemsSent = $this->allResendItemsSentPerScope($stores);
            }
        }

        return $allItemsSent;
    }

    public function handleResendDataBefore()
    {
        $configCollection = $this->getResendTurnConfigCollection();

        foreach ($configCollection as $config) {
            $scope = $config->getScope();
            $scopeId = $config->getScopeId();
            $resendTurn = $this->getResendTurn($scopeId, $scope);
            $resendEnabled = $this->getResendEnabled($scopeId, $scope);
            $ecommEnabled = $this->isEcomSyncDataEnabled($scopeId, $scope);
            if ($ecommEnabled && $resendEnabled && $resendTurn) {
                $this->setIsSyncingIfFinishedPerScope(true, $scopeId, $scope);
            }
        }
    }

    public function handleResendDataAfter()
    {
        $configCollection = $this->getResendTurnConfigCollection();

        foreach ($configCollection as $config) {
            $scope = $config->getScope();
            $scopeId = $config->getScopeId();
            $resendTurn = $this->getResendTurn($scopeId, $scope);
            $ecommEnabled = $this->isEcomSyncDataEnabled($scopeId, $scope);
            if ($ecommEnabled) {
                if ($resendTurn) {
                    $this->setIsSyncingIfFinishedPerScope(false, $scopeId, $scope);
                    $this->setResendTurn(0, $scopeId, $scope);
                } else {
                    $this->setResendTurn(1, $scopeId, $scope);
                }
                $this->handleResendFinish($scopeId, $scope);
            } else {
                //if ecommerce data sync is disabled delete old config values.
                $this->deleteResendConfigValues($scopeId, $scope);
            }
        }
    }

    /**
     * @param $storeId
     * @return bool
     */
    protected function allResendItemsSentPerStoreView($storeId)
    {
        $customerId = $this->getCustomerResendLastId($storeId);
        if ($customerId) {
            $isMissingCustomer = $this->isMissingItemLowerThanId($customerId, Ebizmarts_MailChimp_Model_Config::IS_CUSTOMER, $storeId);
        } else {
            $isMissingCustomer = false;
        }
        $productId = $this->getProductResendLastId($storeId);
        if ($productId) {
            $isMissingProduct = $this->isMissingItemLowerThanId($productId, Ebizmarts_MailChimp_Model_Config::IS_PRODUCT, $storeId);
        } else {
            $isMissingProduct = false;
        }
        $orderId = $this->getOrderResendLastId($storeId);
        if ($orderId) {
            $isMissingOrder = $this->isMissingItemLowerThanId($orderId, Ebizmarts_MailChimp_Model_Config::IS_ORDER, $storeId);
        } else {
            $isMissingOrder = false;
        }
        $cartId = $this->getCartResendLastId($storeId);
        if ($cartId) {
            $isMissingCart = $this->isMissingItemLowerThanId($cartId, Ebizmarts_MailChimp_Model_Config::IS_QUOTE, $storeId);
        } else {
            $isMissingCart = false;
        }

        if (!$isMissingCustomer && !$isMissingProduct && !$isMissingOrder && !$isMissingCart) {
            $allItemsSent = true;
        } else {
            $allItemsSent = false;
        }
        return $allItemsSent;
    }

    /**
     * @param $stores
     * @return bool
     */
    protected function allResendItemsSentPerScope($stores)
    {
        $allItemsSent = true;
        foreach ($stores as $store) {
            if ($store instanceof Mage_Core_Model_Store) {
                $storeId = $store->getId();
            } else {
                $storeId = $store;
            }
            $allItemsSentInCurrentStore = $this->allResendItemsSentPerStoreView($storeId);
            if (!$allItemsSentInCurrentStore) {
                $allItemsSent = false;
            }
        }
        return $allItemsSent;
    }

    protected function isMissingItemLowerThanId($itemId, $itemType, $mailchimpStoreId)
    {
        switch ($itemType) {
            case Ebizmarts_MailChimp_Model_Config::IS_CUSTOMER:
                $isMissing = $this->isMissingCustomerLowerThanId($itemId, $mailchimpStoreId);
                break;
            case Ebizmarts_MailChimp_Model_Config::IS_PRODUCT:
                $isMissing = $this->isMissingProductLowerThanId($itemId, $mailchimpStoreId);
                break;
            case Ebizmarts_MailChimp_Model_Config::IS_ORDER:
                $isMissing = $this->isMissingOrderLowerThanId($itemId, $mailchimpStoreId);
                break;
            case Ebizmarts_MailChimp_Model_Config::IS_QUOTE:
                $isMissing = $this->isMissingQuoteLowerThanId($itemId, $mailchimpStoreId);
                break;
            default:
                $isMissing = false;
                break;
        }
        return $isMissing;
    }

    protected function isMissingCustomerLowerThanId($itemId, $storeId)
    {
        $mailchimpStoreId = $this->getMCStoreId($storeId);
        $customerCollection = Mage::getResourceModel('customer/customer_collection')
            ->addFieldToFilter('store_id', array('eq' => $storeId))
            ->addFieldToFilter('entity_id', array('lteq' => $itemId));
        Mage::getModel('mailchimp/api_customers')->joinMailchimpSyncDataWithoutWhere($customerCollection, $mailchimpStoreId);
        $customerCollection->getSelect()->where("m4m.mailchimp_sync_delta IS null");
        if ($customerCollection->getSize()) {
            $isMissing = true;
        } else {
            $isMissing = false;
        }
        return $isMissing;
    }

    protected function isMissingProductLowerThanId($itemId, $storeId)
    {
        $apiProducts = Mage::getModel('mailchimp/api_products');
        $mailchimpStoreId = $this->getMCStoreId($storeId);
        $productCollection = Mage::getResourceModel('catalog/product_collection')
            ->addStoreFilter($storeId)
            ->addFieldToFilter('entity_id', array('lteq' => $itemId));
        $apiProducts->joinQtyAndBackorders($productCollection);
        $apiProducts->joinMailchimpSyncDataWithoutWhere($productCollection, $mailchimpStoreId);
        $productCollection->getSelect()->where("m4m.mailchimp_sync_delta IS null");
        if ($productCollection->getSize()) {
            $isMissing = true;
        } else {
            $isMissing = false;
        }
        return $isMissing;
    }

    protected function isMissingOrderLowerThanId($itemId, $storeId)
    {
        $mailchimpStoreId = $this->getMCStoreId($storeId);
        $orderCollection = Mage::getResourceModel('sales/order_collection')
            ->addFieldToFilter('store_id', array('eq' => $storeId))
            ->addFieldToFilter('entity_id', array('lteq' => $itemId));
        $firstDate = $this->getEcommerceFirstDate($storeId);
        if ($firstDate) {
            $orderCollection->addFieldToFilter('created_at', array('gt' => $firstDate));
        }
        Mage::getModel('mailchimp/api_orders')->joinMailchimpSyncDataWithoutWhere($orderCollection, $mailchimpStoreId);
        $orderCollection->getSelect()->where("m4m.mailchimp_sync_delta IS null");
        if ($orderCollection->getSize()) {
            $isMissing = true;
        } else {
            $isMissing = false;
        }
        return $isMissing;
    }

    protected function isMissingQuoteLowerThanId($itemId, $storeId)
    {
        $mailchimpStoreId = $this->getMCStoreId($storeId);
        $quoteCollection = Mage::getResourceModel('sales/quote_collection')
            ->addFieldToFilter('store_id', array('eq' => $storeId))
            ->addFieldToFilter('entity_id', array('lteq' => $itemId))
            ->addFieldToFilter('is_active', array('eq' => 1))
            ->addFieldToFilter('customer_email', array('notnull' => true))
            ->addFieldToFilter('items_count', array('gt' => 0));
        $firstDate = $this->getAbandonedCartFirstDate($storeId);
        if ($firstDate) {
            $quoteCollection->addFieldToFilter('updated_at', array('gt' => $firstDate));
        }
        Mage::getModel('mailchimp/api_carts')->joinMailchimpSyncDataWithoutWhere($quoteCollection, $mailchimpStoreId);
        $quoteCollection->getSelect()->where("m4m.mailchimp_sync_delta IS null");
        if ($quoteCollection->getSize()) {
            $isMissing = true;
        } else {
            $isMissing = false;
        }
        return $isMissing;
    }

    public function getMailChimpCampaignNameById($campaignId, $scopeId, $scope = 'stores')
    {
        $campaignName = null;
        try {
            $api = $this->getApi($scopeId, $scope);
            $campaignData = $api->campaigns->get($campaignId);
            if (isset($campaignData['settings'])) {
                if (isset($campaignData['settings']['title'])) {
                    $campaignName = $campaignData['settings']['title'];
                }
                if ($campaignName == '' && isset($campaignData['settings']['subject_line'])) {
                    $campaignName = $campaignData['settings']['subject_line'];
                }
            }
        } catch (Ebizmarts_MailChimp_Helper_Data_ApiKeyException $e) {
            $this->logError($e->getMessage());
        } catch (MailChimp_Error $e) {
            $this->logError($e->getFriendlyMessage());
        } catch (Exception $e) {
            $this->logError($e->getMessage());
        }
        return $campaignName;
    }

    public function getCustomerAmountLimit()
    {
        return $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_CUSTOMER_AMOUNT, 0, 'default');
    }

    public function getProductAmountLimit()
    {
        return $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_PRODUCT_AMOUNT, 0, 'default');
    }

    public function getOrderAmountLimit()
    {
        return $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_ORDER_AMOUNT, 0, 'default');
    }

    public function getPromoRuleAmountLimit()
    {
        return $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_ORDER_AMOUNT, 0, 'default');
    }

    public function getCartAmountLimit()
    {
        return $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::CART_AMOUNT, 0, 'default');
    }

    public function getSubscriberAmountLimit()
    {
        return $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::GENERAL_SUBSCRIBER_AMOUNT, 0, 'default');
    }

    public function getStoreLanguageCode($scopeId, $scope = 'stores')
    {
        $isAdmin = $this->isAdmin();
        $userLangCode = Mage::app()->getLocale()->getLocaleCode();
        if ($isAdmin || '' == $lang = $this->_lang2MCLanguage($userLangCode)) {
            // IS Admin OR if users lang is not supported, try store views default locale
            $userLangCode = $this->getConfigValueForScope(Mage_Core_Model_Locale::XML_PATH_DEFAULT_LOCALE, $scopeId, $scope);
            $lang = $this->_lang2MCLanguage($userLangCode);
        }

        return $lang;
    }

    public function getStoreTimeZone($scopeId, $scope = 'stores')
    {
        return $this->getConfigValueForScope(Mage_Core_Model_Locale::XML_PATH_DEFAULT_TIMEZONE, $scopeId, $scope);
    }

    public function getStorePhone($scopeId, $scope = 'stores')
    {
        return $this->getConfigValueForScope('general/store_information/phone', $scopeId, $scope);
    }

    public function getAllMailChimpStoreIds()
    {
        $collection = Mage::getResourceModel('core/config_data_collection')
            ->addFieldToFilter('path', array('eq' => Ebizmarts_MailChimp_Model_Config::GENERAL_MCSTOREID));
        $mailchimpStoreIdsArray = array();
        foreach ($collection as $row) {
            $scopeData = $row->getScope() . '_' . $row->getScopeId();
            $mailchimpStoreIdsArray[$scopeData] = $row->getValue();
        }
        return $mailchimpStoreIdsArray;
    }

    public function subscribeMember($subscriber, $forceUpdateStatus = false)
    {
        $subscriber->setStatus(Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED);
        $subscriber->setSubscriberConfirmCode($subscriber->randomSequence());
        if ($forceUpdateStatus) {
            $subscriber->setMailchimpSyncModified(1);
        }
        $this->setMemberGeneralData($subscriber);
    }

    public function unsubscribeMember($subscriber)
    {
        $subscriber->setStatus(Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED);
        $this->setMemberGeneralData($subscriber);
    }

    protected function setMemberGeneralData($subscriber)
    {
        $subscriber->setImportMode(true);
        $subscriber->setSubscriberSource(Ebizmarts_MailChimp_Model_Subscriber::SUBSCRIBE_SOURCE);
        $subscriber->setIsStatusChanged(true);
        $subscriber->save();
    }

    /**
     * @param $config
     * @param $scope
     * @param $scopeId
     * @return bool
     */
    protected function isDifferentStoreView($config, $scope, $scopeId)
    {
        return $config->getScope() == 'stores' && $scope == 'stores' && $scopeId != $config->getScopeId();
    }

    /**
     * @param $config
     * @param $scope
     * @param $websiteId
     * @return bool
     */
    protected function isDifferentWebsite($config, $scope, $websiteId)
    {
        return ($config->getScope() == 'websites' && $scope == 'stores' && $config->getScopeId() != $websiteId);
    }

    /**
     * @param $config
     * @param $scope
     * @return bool
     */
    protected function isIncorrectScope($config, $scope)
    {
        return ($config->getScope() == 'stores' && $scope != 'stores');
    }

    /**
     * @param $config
     * @return bool
     */
    protected function isNotDefaultScope($config)
    {
        return $config->getScopeId() != 0;
    }

    /**
     * @param $storeId
     * @return bool
     */
    protected function areJsUrlAndListScopesEqual($storeId)
    {
        $scopesMatch = false;
        $realScopeList = $this->getRealScopeForConfig(Ebizmarts_MailChimp_Model_Config::GENERAL_LIST, $storeId);
        $realScopeJs = $this->getRealScopeForConfig(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_MC_JS_URL, $storeId);
        if ($realScopeList && $realScopeJs && $realScopeList['scope'] == $realScopeJs['scope'] && $realScopeList['scope_id'] == $realScopeJs['scope_id']) {
            $scopesMatch = true;
        }
        return $scopesMatch;
    }

    public function validateDate($date, $format = 'Y-m-d H:i:s')
    {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) == $date;
    }

    /**
     * @return mixed
     */
    protected function getResendTurnConfigCollection()
    {
        $configCollection = Mage::getResourceModel('core/config_data_collection')
            ->addFieldToFilter('path', array('eq' => Ebizmarts_MailChimp_Model_Config::ECOMMERCE_RESEND_TURN));
        return $configCollection;
    }

    /**
     * @return Mage_Core_Model_Config
     */
    public function getConfig()
    {
        return Mage::getConfig();
    }

    public function wasProductImageCacheFlushed()
    {
        return $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::PRODUCT_IMAGE_CACHE_FLUSH, 0, 'default');
    }


    /**
     *  Will return the first scope it finds, intended for Api calls usage.
     *
     * @param $mailChimpStoreId
     * @return array
     */
    protected function getScopeByMailChimpStoreId($mailChimpStoreId)
    {
        $mailchimpScope = null;
        $collection = Mage::getResourceModel('core/config_data_collection')
            ->addFieldToFilter('path', array('eq' => Ebizmarts_MailChimp_Model_Config::GENERAL_MCSTOREID))
            ->addFieldToFilter('value', array('eq' => $mailChimpStoreId));
        if ($collection->getSize()) {
            $configEntry = $collection->getFirstItem();
            $mailchimpScope = array('scope' => $configEntry->getScope(), 'scope_id' => $configEntry->getScopeId());
        }
        return $mailchimpScope;
    }

    /**
     * @param $mailchimpStoreId
     * @return Ebizmarts_MailChimp|null
     */
    public function getApiByMailChimpStoreId($mailchimpStoreId)
    {
        $scopeArray = $this->getScopeByMailChimpStoreId($mailchimpStoreId);
        try {
            $api = $this->getApi($scopeArray['scope_id'], $scopeArray['scope']);
            return $api;
        } catch (Ebizmarts_MailChimp_Helper_Data_ApiKeyException $e) {
            $this->logError($e->getMessage());
        }
    }

    public function changeStoreNameIfRequired($groupStoreName, $scopeId, $scope)
    {
        $mailchimpStoreId = $this->getMCStoreId($scopeId, $scope);
        if ($mailchimpStoreId) {
            $realScope = $this->getRealScopeForConfig(Ebizmarts_MailChimp_Model_Config::GENERAL_MCSTOREID, $scopeId, $scope);
            if ($realScope['scope_id'] == $scopeId && $realScope['scope'] == $scope) {
                $ecomEnabled = $this->isEcomSyncDataEnabled($realScope['scope_id'], $realScope['scope']);
                if ($ecomEnabled) {
                    $storeName = $this->getConfigValueForScope(Mage_Core_Model_Store::XML_PATH_STORE_STORE_NAME, $realScope['scope_id'], $realScope['scope']);
                    if ($storeName == '') {
                        $this->changeName($groupStoreName, $scopeId, $scope);
                    } else {
                        $this->changeName($storeName, $scopeId, $scope);
                    }
                }
            }
        }
    }

    /**
     * Compare old api key with new one and return true if they belong to the same MailChimp account.
     *
     * @param $oldApiKey
     * @param $newApiKey
     * @return bool
     */
    public function isNewApiKeyForSameAccount($oldApiKey, $newApiKey)
    {
        $isNewApiKeyForSameAccount = false;

        if ($oldApiKey && $newApiKey) {
            if ($oldApiKey == $newApiKey) {
                $isNewApiKeyForSameAccount = true;
            } else {
                try {
                    $api = $this->getApiByKey($oldApiKey);
                    $oldInfo = $api->getRoot()->info('account_id');
                    $oldAccountId = $oldInfo['account_id'];
                    $api = $this->getApiByKey($newApiKey);
                    $newInfo = $api->getRoot()->info('account_id');
                    $newAccountId = $newInfo['account_id'];
                    if ($oldAccountId == $newAccountId) {
                        $isNewApiKeyForSameAccount = true;
                    }
                } catch (Ebizmarts_MailChimp_Helper_Data_ApiKeyException $e) {
                    $this->logError($e->getMessage());
                } catch (MailChimp_Error $e) {
                    $this->logError($e->getFriendlyMessage());
                }
            }
        }
        return $isNewApiKeyForSameAccount;
    }

    public function resendSubscribers($scopeId, $scope = 'stores')
    {
        $storeIdArray = $this->getAllStoresForScope($scopeId, $scope);
        $resource = $this->getCoreResource();
        $connection = $resource->getConnection('core_write');
        $tableName = $resource->getTableName('newsletter/subscriber');

        foreach ($storeIdArray as $storeId) {
            $where = array("store_id = ?" => $storeId);
            $setCondition = array('mailchimp_sync_delta' => '0000-00-00 00:00:00', 'mailchimp_sync_error' => '');
            $connection->update($tableName, $setCondition, $where);
        }
    }

    protected function getAllStoresForScope($scopeId, $scope)
    {
        $storesResult = array();

        switch ($scope) {
            case 'default':
                $stores = $this->getMageApp()->getStores();
                foreach ($stores as $storeId => $store) {
                    $storesResult[] = $storeId;
                }
                break;
            case 'websites':
                $website = $this->getCoreWebsite()->load($scopeId);
                $storesResult = $website->getStoreIds();
                break;
            case 'stores':
                $storesResult[] = $scopeId;
                break;
        }

        return $storesResult;
    }

    /**
     * @param $connection
     * @param $scopeId
     * @param $scope
     * @return string
     */
    protected function makeWhereString($connection, $scopeId, $scope)
    {
        $storesForScope = $this->getMagentoStoresForMCStoreIdByScope($scopeId, $scope);
        $whereString = "mailchimp_campaign_id IS NOT NULL";
        if (count($storesForScope)) {
            $whereString .= " AND (";
        }
        $counter = 0;
        foreach ($storesForScope as $storeId) {
            if ($counter) {
                $whereString .= " OR ";
            }
            $whereString .= $connection->quoteInto("store_id = ?", $storeId);
            $counter++;
        }
        if (count($storesForScope)) {
            $whereString .= ")";
        }
        return $whereString;
    }

    /**
     * Convert language into Mailchimp compatible language code.
     * @param string $languageCode
     * @return string   Returns empty string if not MC Language match found
     */
    private function _lang2MCLanguage($languageCode = '')
    {
        $mailchimpLanguage = '';

        if (in_array($languageCode, self::$MAILCHIMP_LANGUAGES)) {
            $mailchimpLanguage = $languageCode;
        } else {
            $langIso2 = substr($languageCode, 0, 2);
            if (in_array($langIso2, self::$MAILCHIMP_LANGUAGES)) {
                $mailchimpLanguage = $langIso2;
            }
        }

        return $mailchimpLanguage;
    }

    public function isSubscriptionConfirmationEnabled($scopeId, $scope = 'stores')
    {
        return (bool)$this->getConfigValueForScope(Mage_Newsletter_Model_Subscriber::XML_PATH_CONFIRMATION_FLAG, $scopeId, $scope);
    }

    public function getPromoConfig($scopeId, $scope = null)
    {
        return $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_SEND_PROMO, $scopeId, $scope);
    }


    public function getListInterestCategories($scopeId, $scope = 'stores')
    {
        $interestGroupsArray = array();
        $api = $this->getApi($scopeId, $scope);
        $listId = $this->getGeneralList($scopeId, $scope);
        try {
            $interestCategories = $api->getLists()->getInterestCategory()->getAll($listId, 'categories');
            foreach ($interestCategories['categories'] as $interestCategory) {
                $interestGroupsArray[] = array(
                    'id' => $interestCategory['id'],
                    'title' => $interestCategory['title'],
                    'type' => $interestCategory['type']
                );
            }
        } catch (Exception $e) {
            $this->logError($e->getMessage());
        }
        return $interestGroupsArray;
    }

    public function getListInterestGroups($scopeId, $scope = 'stores')
    {
        $interestGroupsArray = array();
        $api = $this->getApi($scopeId, $scope);
        $listId = $this->getGeneralList($scopeId, $scope);
        try {
            $apiInterestCategory = $api->getLists()->getInterestCategory();
            $interestCategories = $apiInterestCategory->getAll($listId, 'categories');
            foreach ($interestCategories['categories'] as $interestCategory) {
                $interestGroups = $apiInterestCategory->getInterests()->getAll($listId, $interestCategory['id']);
                $groups = array();
                foreach ($interestGroups['interests'] as $interestGroup) {
                    $groups[$interestGroup['id']] = $interestGroup['name'];
                }
                $interestGroupsArray[] = array(
                    'id' => $interestCategory['id'],
                    'title' => $interestCategory['title'],
                    'type' => $interestCategory['type'],
                    'groups' => $groups
                );
            }
        } catch (Exception $e) {
            $this->logError($e->getMessage());
        }
        return $interestGroupsArray;
    }

    public function getLocalInterestCategories($scopeId, $scope = 'stores')
    {
        return $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::GENERAL_INTEREST_CATEGORIES, $scopeId, $scope);
    }

    public function getCheckoutSuccessHtmlBefore($scopeId, $scope = 'stores')
    {
        return $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::GENERAL_INTEREST_SUCCESS_BEFORE, $scopeId, $scope);
    }

    public function getCheckoutSuccessHtmlAfter($scopeId, $scope = 'stores')
    {
        return $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::GENERAL_INTEREST_SUCCESS_AFTER, $scopeId, $scope);
    }

    /**
     * @param $storeId
     * @return array
     * @throws MailChimp_Error
     */
    public function getInterest($storeId)
    {
        $rc = array();
        $interest = $this->getLocalInterestCategories($storeId);
        if ($interest != '') {
            $interest = explode(",", $interest);

        } else {
            $interest = array();
        }
        $api = $this->getApi($storeId);
        $listId = $this->getGeneralList($storeId);
        try {
            $apiInterestCategory = $api->getLists()->getInterestCategory();
            $allInterest = $apiInterestCategory->getAll($listId);
            foreach ($allInterest['categories'] as $item) {
                if (in_array($item['id'], $interest)) {
                    $rc[$item['id']]['interest'] = array('id' => $item['id'], 'title' => $item['title'], 'type' => $item['type']);
                }
            }
            $apiInterestCategoryInterest = $apiInterestCategory->getInterests();
            foreach ($interest as $interestId) {
                $mailchimpInterest = $apiInterestCategoryInterest->getAll($listId, $interestId);
                foreach ($mailchimpInterest['interests'] as $mi) {
                    $rc[$mi['category_id']]['category'][$mi['display_order']] = array('id' => $mi['id'], 'name' => $mi['name'], 'checked' => false);
                }
            }
        } catch (MailChimp_Error $e) {
            $this->logError($e->getFriendlyMessage());
        }
        return $rc;
    }

    /**
     * @param $customerId
     * @param $subscriberId
     * @param $storeId
     * @param null $interest
     * @return array|null
     * @throws MailChimp_Error
     */
    public function getInterestGroups($customerId, $subscriberId, $storeId, $interest = null)
    {
        if (!$interest) {
            $interest = $this->getInterest($storeId);
        }
        $interestGroup = $this->getInterestGroupModel();
        $interestGroup->getByRelatedIdStoreId($customerId, $subscriberId, $storeId);
        if ($interestGroup->getId()) {
            $groups = $this->arrayDecode($interestGroup->getGroupdata());
            foreach ($groups as $key => $value) {
                if (isset($interest[$key])) {
                    if (is_array($value)) {
                        foreach ($value as $groupId) {
                            foreach ($interest[$key]['category'] as $gkey => $gvalue) {
                                if ($gvalue['id'] == $groupId) {
                                    $interest[$key]['category'][$gkey]['checked'] = true;
                                } elseif (!isset($interest[$key]['category'][$gkey]['checked'])) {
                                    $interest[$key]['category'][$gkey]['checked'] = false;
                                }
                            }
                        }
                    } else {
                        foreach ($interest[$key]['category'] as $gkey => $gvalue) {
                            if ($gvalue['id'] == $value) {
                                $interest[$key]['category'][$gkey]['checked'] = true;
                            } else {
                                $interest[$key]['category'][$gkey]['checked'] = false;
                            }
                        }
                    }
                }
            }
        }
        return $interest;
    }


    /**
     * Format array to save in database.
     *
     * @param $array
     * @return string
     */
    public function arrayEncode($array)
    {
        return json_encode($array);
    }

    /**
     * Set database encoded array to normal array
     *
     * @param $encodedArray
     * @return mixed
     */
    public function arrayDecode($encodedArray)
    {
        return json_decode($encodedArray, true);
    }

    /**
     * @param $params
     * @param $storeId
     * @param null $customerId
     * @param null $subscriber
     * @throws Mage_Core_Model_Store_Exception
     */
    public function saveInterestGroupData($params, $storeId, $customerId = null, $subscriber = null)
    {
        $groups = $this->getInterestGroupsIfAvailable($params);
        if (!$customerId) {
            $customerSession = $this->getCustomerSession();
            if ($this->isAdmin()) {
                $customerId = $params['customer_id'];
            } elseif ($customerSession->isLoggedIn()) {
                $customerData = $customerSession->getCustomer();
                $customerId = $customerData->getId();
            }
        }
        $subscriberId = null;
        if ($subscriber) {
            $subscriberId = $subscriber->getSubscriberId();
        }
        $interestGroup = $this->getInterestGroupModel();
        $interestGroup->getByRelatedIdStoreId($customerId, $subscriberId, $storeId);
        $origSubscriberId = $interestGroup->getSubscriberId();
        $origCustomerId = $interestGroup->getCustomerId();
        if (!$origSubscriberId || $subscriberId && $origSubscriberId != $subscriberId) {
            $interestGroup->setSubscriberId($subscriberId);
        }
        if (!$origCustomerId || $customerId && $origCustomerId != $customerId) {
            $interestGroup->setCustomerId($customerId);
        }
        if ($groups) {
            $encodedGroups = $this->arrayEncode($groups);
            $interestGroup->setGroupdata($encodedGroups);
        }
        //Avoid creating a new entry if no groupData available. (Customer creation)
        if ($interestGroup->getGroupdata()) {
            if ($storeId) {
                $interestGroup->setStoreId($storeId);
            }
            $interestGroup->setUpdatedAt($this->getCurrentDateTime());
            $interestGroup->save();
        }
    }

    /**
     * @param $params
     * @return mixed
     */
    public function getInterestGroupsIfAvailable($params)
    {
        $groups = null;
        if (isset($params['customer']) && isset($params['customer']['interestgroup'])) {
            $groups = $params['customer']['interestgroup'];
        } elseif (isset($params['group'])) {
            $groups = $params['group'];
        }
        return $groups;
    }

    /**
     * @return bool
     * @throws Mage_Core_Model_Store_Exception
     */
    public function isAdmin()
    {
        return Mage::app()->getStore()->isAdmin();
    }

    /**
     * @return Ebizmarts_MailChimp_Model_Interestgroup
     */
    protected function getInterestGroupModel()
    {
        return Mage::getModel('mailchimp/interestgroup');
    }

    /**
     * @return Mage_Customer_Model_Session
     */
    protected function getCustomerSession()
    {
        return Mage::getSingleton('customer/session');
    }

    /**
     * @return mixed
     */
    protected function getCurrentDateTime()
    {
        return Mage::getModel('core/date')->date('d-m-Y H:i:s');
    }

    /**
     * Return original path for the imageURL (not the catched one)
     *
     * @param $productImage
     * @return string
     */
    protected function getOriginalPath($productImage)
    {
        return Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product' . $productImage;
    }
}
