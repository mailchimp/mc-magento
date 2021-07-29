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
    const DEFAULT_SIZE = '0';
    const SMALL_SIZE = '1';
    const THUMBNAIL_SIZE = '2';
    const ORIGINAL_SIZE = '3';

    const SUB_MOD = "SubscriberModified";
    const SUB_NEW = "SubscriberNew";
    const PRO_MOD = "ProductModified";
    const PRO_NEW = "ProductNew";
    const CUS_MOD = "CustomerModified";
    const CUS_NEW = "CustomerNew";
    const ORD_MOD = "OrderModified";
    const ORD_NEW = "OrderNew";
    const QUO_MOD = "QuoteModified";
    const QUO_NEW = "QuoteNew";

    const DATA_NOT_SENT_TO_MAILCHIMP = 'NOT SENT';
    const DATA_SENT_TO_MAILCHIMP = 'SENT';

    const BATCH_STATUS_LOG = 'Mailchimp_Batch_Status.log';
    const BATCH_CANCELED = 'canceled';
    const BATCH_PENDING = 'pending';

    protected $_countersSendBatch = array();
    protected $_countersSubscribers = array();
    protected $_countersGetResponseBatch = array();

    /**
     * All MailChimp available language codes
     *
     * @var array
     */
    public static $LANGUAGES = array(
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
     * @param       $path
     * @param       $scopeId
     * @param null  $scope
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
     * @param $scopeArray
     * @return false|string
     * @throws Mage_Core_Exception
     * @throws Mage_Core_Model_Store_Exception
     */
    public function getScopeName($scopeArray)
    {
        $storeName = false;

        if (isset($scopeArray['scope'])) {
            switch ($scopeArray['scope']) {
            case 'stores':
                $store = $this->getMageApp()->getStore($scopeArray['scope_id']);
                $storeName = $store->getName();
                break;
            case 'websites':
                $website = $this->getMageApp()->getWebsite($scopeArray['scope_id']);
                $storeName = $website->getName();
                break;
            case 'default':
                $storeName = 'Default Config';
                break;
            }
        }

        return $storeName;
    }

    /**
     * @param $scopeId
     * @param $scope
     * @return bool
     * @throws Mage_Core_Exception
     */
    public function isUsingConfigStoreName($scopeId, $scope)
    {
        $storeName = $this->getConfigValueForScope(
            Mage_Core_Model_Store::XML_PATH_STORE_STORE_NAME,
            $scopeId,
            $scope
        );

        if ($storeName == '') {
            $usingConfigName = false;
        } else {
            $usingConfigName = true;
        }

        return $usingConfigName;
    }

    /**
     * @param $scopeId
     * @param $scope
     * @return mixed|string
     * @throws Mage_Core_Exception
     * @throws Mage_Core_Model_Store_Exception
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
            $domain = $this->getConfigValueForScope(
                Mage_Core_Model_Store::XML_PATH_UNSECURE_BASE_LINK_URL,
                $scopeId,
                $scope
            );
        }

        return $domain;
    }

    /**
     * Get local store_id value of the MC store.
     *
     * @return array
     * @throws Mage_Core_Exception
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
     * @return array|mixed
     * @throws Mage_Core_Exception
     */
    public function getMagentoStoresForMCStoreIdByScope($scopeId, $scope)
    {
        $ret = array();
        $storeRelation = $this->getStoreRelation();
        $mailchimpStoreIdForScope = $this->getMCStoreId($scopeId, $scope);
        $isThereAnyStore = array_key_exists($mailchimpStoreIdForScope, $storeRelation);

        if ($mailchimpStoreIdForScope && $isThereAnyStore) {
            $ret = $storeRelation[$mailchimpStoreIdForScope];
        }

        return $ret;
    }

    /**
     * Validate if api key exists, could still be incorrect
     *
     * @param       $scopeId
     * @param  null $scope
     * @return mixed
     */
    public function validateApiKey($scopeId, $scope = null)
    {
        $apiKey = $this->getApiKey($scopeId, $scope);
        $isApiKeyValid = $apiKey !== null && $apiKey != "";

        return $isApiKeyValid;
    }

    /**
     * Return if module is enabled for given scope.
     *
     * @param       $scopeId
     * @param null  $scope
     * @return mixed
     */
    public function isMailChimpEnabled($scopeId, $scope = null)
    {
        return $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::GENERAL_ACTIVE, $scopeId, $scope);
    }

    /**
     * @param $scopeId
     * @return bool | returns true if useMagentoEmails is enabled
     */
    public function isUseMagentoEmailsEnabled($scopeId)
    {
        return (int)$this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::GENERAL_MAGENTO_MAIL, $scopeId);
    }

    /**
     * Return if module is enabled and list selected for given scope.
     *
     * @param       $scopeId
     * @param null  $scope
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
     * @param       $scopeId
     * @param null  $scope
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function getApiKey($scopeId, $scope = null)
    {
        $apiKey = $this->getConfigValueForScope(
            Ebizmarts_MailChimp_Model_Config::GENERAL_APIKEY,
            $scopeId,
            $scope
        );

        return $this->decryptData($apiKey);
    }

    /**
     * Return decrypted data.
     *
     * @param  $data
     * @return mixed
     */
    public function decryptData($data)
    {
        return Mage::helper('core')->decrypt($data);
    }

    /**
     * Return encrypted data.
     *
     * @param  $data
     * @return mixed
     */
    public function encryptData($data)
    {
        return Mage::helper('core')->encrypt($data);
    }

    /**
     * Get local store_id value of the MC store for given scope.
     *
     * @param       $scopeId
     * @param null  $scope
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function getMCStoreId($scopeId, $scope = null)
    {
        return $this->getConfigValueForScope(
            Ebizmarts_MailChimp_Model_Config::GENERAL_MCSTOREID,
            $scopeId,
            $scope
        );
    }

    /**
     * Delete all data related to the configured store in a given scope.
     *
     * @param           $scopeId
     * @param string    $scope
     */
    public function deletePreviousConfiguredMCStoreLocalData($mailchimpStoreId, $scopeId, $scope = 'stores')
    {
        $config = $this->getConfig();

        if ($mailchimpStoreId !== null && $mailchimpStoreId !== '') {
            foreach ($this->getAllStoresForScope($scopeId, $scope) as $storeId) {
                $config->deleteConfig(
                    Ebizmarts_MailChimp_Model_Config::GENERAL_MCISSYNCING . "_$mailchimpStoreId",
                    'stores',
                    $storeId
                );
            }

            $config->deleteConfig(
                Ebizmarts_MailChimp_Model_Config::GENERAL_MCISSYNCING . "_$mailchimpStoreId",
                $scope,
                $scopeId
            );
        }

        $config->deleteConfig(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_CUSTOMER_LAST_ID, $scope, $scopeId);
        $config->deleteConfig(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_PRODUCT_LAST_ID, $scope, $scopeId);
        $config->deleteConfig(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_ORDER_LAST_ID, $scope, $scopeId);
        $config->deleteConfig(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_CART_LAST_ID, $scope, $scopeId);
        $config->deleteConfig(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_PCD_LAST_ID, $scope, $scopeId);
        $config->deleteConfig(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_RESEND_ENABLED, $scope, $scopeId);
        $config->deleteConfig(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_RESEND_TURN, $scope, $scopeId);
        $config->cleanCache();

        $resource = $this->getCoreResource();
        $connection = $resource->getConnection('core_write');
        $tableName = $resource->getTableName('mailchimp/synchbatches');
        $where = $connection->quoteInto("status = 'pending' AND store_id = ?", $mailchimpStoreId);
        $connection->update($tableName, array('status' => 'canceled'), $where);
    }

    /**
     * Delete all data related to the configured store in a given scope.
     *
     * @param           $scopeId
     * @param string    $scope
     */
    public function deleteAllConfiguredMCStoreLocalData($mailchimpStoreId, $scopeId, $scope = 'stores')
    {
        $configValues = array(array(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_ACTIVE, 0));
        $this->saveMailchimpConfig($configValues, $scopeId, $scope, false);
        $config = $this->getConfig();
        $config->deleteConfig(Ebizmarts_MailChimp_Model_Config::GENERAL_MCSTOREID, $scope, $scopeId);
        $this->deletePreviousConfiguredMCStoreLocalData($mailchimpStoreId, $scopeId, $scope = 'stores');
    }

    /**
     * @return Ebizmarts_MailChimp_Model_Resource_SynchBatches
     */
    protected function getSyncBatchesResource()
    {
        return Mage::getResourceModel('mailchimp/synchbatches');
    }

    public function deleteAllMCStoreData($mailchimpStoreId)
    {
        //Delete default configurations for this store.
        $config = $this->getConfig();
        $config->deleteConfig(
            Ebizmarts_MailChimp_Model_Config::ECOMMERCE_SYNC_DATE . "_$mailchimpStoreId", 'default', 0
        );
        $config->deleteConfig(
            Ebizmarts_MailChimp_Model_Config::ECOMMERCE_MC_JS_URL . "_$mailchimpStoreId", 'default', 0
        );

        //Delete local ecommerce data and errors for this store.
        $this->removeEcommerceSyncDataByMCStore($mailchimpStoreId);
        $this->clearErrorGridByMCStore($mailchimpStoreId);

        //Delete particular scopes configuraion flags for this store
        $scopeArrayIfExist = $this->getScopeByMailChimpStoreId($mailchimpStoreId);

        if ($scopeArrayIfExist !== false) {
            $this->deleteAllConfiguredMCStoreLocalData(
                $mailchimpStoreId,
                $scopeArrayIfExist['scope_id'],
                $scopeArrayIfExist['scope']
            );
        }
    }

    /**
     * Return if Ecommerce configuration is enabled for given scope.
     *
     * @param       $scopeId
     * @param null  $scope
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function isEcommerceEnabled($scopeId, $scope = null)
    {
        return $this->getConfigValueForScope(
            Ebizmarts_MailChimp_Model_Config::ECOMMERCE_ACTIVE,
            $scopeId,
            $scope
        );
    }

    /**
     * Get general list configured for the given scope.
     *
     * @param       $scopeId
     * @param null  $scope
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function getGeneralList($scopeId, $scope = null)
    {
        return $this->getConfigValueForScope(
            Ebizmarts_MailChimp_Model_Config::GENERAL_LIST,
            $scopeId,
            $scope
        );
    }

    /**
     * Get map fields configured for the given scope.
     *
     * @param       $scopeId
     * @param null  $scope
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function getMapFields($scopeId, $scope = null)
    {
        return $this->getConfigValueForScope(
            Ebizmarts_MailChimp_Model_Config::GENERAL_MAP_FIELDS,
            $scopeId,
            $scope
        );
    }

    /**
     * Get custom merge fields configured for the given scope.
     *
     * @param       $scopeId
     * @param null  $scope
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function getCustomMergeFieldsSerialized($scopeId, $scope = null)
    {
        return $this->getConfigValueForScope(
            Ebizmarts_MailChimp_Model_Config::GENERAL_CUSTOM_MAP_FIELDS, $scopeId, $scope
        );
    }

    /**
     * Get if Abandoned Cart module is enabled.
     *
     * @param       $scopeId
     * @param null  $scope
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function isAbandonedCartEnabled($scopeId, $scope = null)
    {
        return $this->getConfigValueForScope(
            Ebizmarts_MailChimp_Model_Config::ABANDONEDCART_ACTIVE,
            $scopeId,
            $scope
        );
    }

    /**
     * Get date configured for carts to be sent for the given scope.
     *
     * @param       $scopeId
     * @param null  $scope
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function getAbandonedCartFirstDate($scopeId, $scope = null)
    {
        return $this->getConfigValueForScope(
            Ebizmarts_MailChimp_Model_Config::ABANDONEDCART_FIRSTDATE,
            $scopeId,
            $scope
        );
    }

    /**
     * Get date configured for ecommerce data to be sent for the given scope.
     *
     * @param       $scopeId
     * @param null  $scope
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function getEcommerceFirstDate($scopeId, $scope = null)
    {
        return $this->getConfigValueForScope(
            Ebizmarts_MailChimp_Model_Config::ECOMMERCE_FIRSTDATE,
            $scopeId,
            $scope
        );
    }

    /**
     * Get local is_syncing value of the MC store.
     * If data was saved in the old way get it from the scope and update it to the new way.
     *
     * @param           $mailchimpStoreId
     * @param int       $scopeId
     * @param string    $scope
     * @return mixed|null
     * @throws Mage_Core_Exception
     */
    public function getMCIsSyncing($mailchimpStoreId, $scopeId = 0, $scope = 'stores')
    {
        $oldSyncingFlag = $this->getConfigValueForScope(
            Ebizmarts_MailChimp_Model_Config::GENERAL_MCISSYNCING,
            $scopeId,
            $scope
        );
        $syncingFlag = $this->getConfigValueForScope(
            Ebizmarts_MailChimp_Model_Config::GENERAL_MCISSYNCING . "_$mailchimpStoreId",
            $scopeId, $scope
        );

        //Save old value in new place.
        if ($syncingFlag === null && $oldSyncingFlag !== null) {
            $configValue = array(
                array(Ebizmarts_MailChimp_Model_Config::GENERAL_MCISSYNCING . "_$mailchimpStoreId", $oldSyncingFlag)
            );
            $this->saveMailchimpConfig($configValue, $scopeId, $scope);
        }

        //Delete old entry if exists particularly in this scope.
        if ($oldSyncingFlag !== null && $this->getIfConfigExistsForScope(
            Ebizmarts_MailChimp_Model_Config::GENERAL_MCISSYNCING,
            $scopeId,
            $scope
        )
        ) {
            $config = $this->getConfig();
            $config->deleteConfig(Ebizmarts_MailChimp_Model_Config::GENERAL_MCISSYNCING, $scope, $scopeId);
            $config->cleanCache();
        }

        return ($syncingFlag !== null) ? $syncingFlag : $oldSyncingFlag;
    }

    /**
     * Get if logs are enabled.
     * Logs can only be enabled in default scope.
     *
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function getLogsEnabled()
    {
        return $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::GENERAL_LOG, 0);
    }

    /**
     * Get if two way sync is enabled for given scope.
     *
     * @param int   $scopeId
     * @param null  $scope
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function getTwoWaySyncEnabled($scopeId = 0, $scope = null)
    {
        return $this->getConfigValueForScope(
            Ebizmarts_MailChimp_Model_Config::GENERAL_TWO_WAY_SYNC,
            $scopeId,
            $scope
        );
    }

    /**
     * Get if monkey should be displayed in order grid.
     *
     * @param int   $scopeId
     * @param null  $scope
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function getMonkeyInGrid($scopeId = 0, $scope = null)
    {
        return $this->getConfigValueForScope(
            Ebizmarts_MailChimp_Model_Config::GENERAL_ORDER_GRID,
            $scopeId,
            $scope
        );
    }

    /**
     * Get if Email Catcher popup is enabled for given scope.
     *
     * @param int   $scopeId
     * @param null  $scope
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function isEmailCatcherEnabled($scopeId = 0, $scope = null)
    {
        return $this->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::ENABLE_POPUP, $scopeId, $scope);
    }

    /**
     * @param int   $scopeId
     * @param null  $scope
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function getDateSyncFinishByStoreId($scopeId = 0, $scope = null)
    {
        $mailchimpStoreId = $this->getMCStoreId($scopeId, $scope);
        return $this->getConfigValueForScope(
            Ebizmarts_MailChimp_Model_Config::ECOMMERCE_SYNC_DATE . "_$mailchimpStoreId",
            0,
            'default'
        );
    }

    /**
     * @param $mailchimpStoreId
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function getDateSyncFinishByMailChimpStoreId($mailchimpStoreId)
    {
        return $this->getConfigValueForScope(
            Ebizmarts_MailChimp_Model_Config::ECOMMERCE_SYNC_DATE . "_$mailchimpStoreId",
            0,
            'default'
        );
    }

    /**
     * Set the values to send all the items again.
     *
     * @param $scopeId
     * @param $scope
     * @param null $filters
     * @throws Mage_Core_Exception
     */
    public function resendMCEcommerceData($scopeId, $scope, $filters = null)
    {
        if ($this->getMCStoreId($scopeId, $scope) && $this->getMCStoreId($scopeId, $scope) != "") {
            if (!$this->getResendEnabled($scopeId, $scope)) {
                $this->saveLastItemsSent($scopeId, $scope, $filters);
            }

            $this->removeEcommerceSyncData($scopeId, $scope, false, $filters);
            $this->clearErrorGrid($scopeId, $scope, true, $filters);

            if ($filters !== null && in_array(Ebizmarts_MailChimp_Model_Config::IS_PRODUCT, $filters)) {
                $this->deleteFlushMagentoCacheFlag();
            }
        }
    }

    /**
     * Remove items from mailchimp_ecommerce_sync_data table to allow them to be sent.
     * If scopeId is 0 remova from all scopes.
     *
     * @param       $scopeId
     * @param       $scope
     * @param bool  $deleteErrorsOnly
     * @param null  $filters
     * @throws Mage_Core_Exception
     */
    public function removeEcommerceSyncData($scopeId, $scope, $deleteErrorsOnly = false, $filters = null)
    {
        if ($scopeId == 0 && $deleteErrorsOnly) {
            $this->removeAllEcommerceSyncDataErrors($filters);
        } else {
            $mailchimpStoreId = $this->getMCStoreId($scopeId, $scope);
            $this->removeEcommerceSyncDataByMCStore($mailchimpStoreId, $deleteErrorsOnly, $filters);
        }
    }

    /**
     * @param null $filters
     * @throws Mage_Core_Exception
     */
    public function removeAllEcommerceSyncDataErrors($filters = null)
    {
        $resource = $this->getCoreResource();
        $connection = $resource->getConnection('core_write');
        $tableName = $resource->getTableName('mailchimp/ecommercesyncdata');
        $where = array();
        $where[] = "mailchimp_sync_error != ''";

        if ($filters !== null) {
            $where[] = $connection->quoteInto('type IN (?)', $filters);
        }

        try {
            $connection->delete($tableName, $where);
        } catch (Exception $e) {
            $this->logError($e->getMessage());
        }
    }

    /**
     * @param       $mailchimpStoreId
     * @param bool  $deleteErrorsOnly
     * @param null  $filters
     */
    public function removeEcommerceSyncDataByMCStore($mailchimpStoreId, $deleteErrorsOnly = false, $filters = null)
    {
        $resource = $this->getCoreResource();
        $connection = $resource->getConnection('core_write');
        $tableName = $resource->getTableName('mailchimp/ecommercesyncdata');
        $where = array();

        if ($deleteErrorsOnly) {
            $where[] = $connection->quoteInto(
                "mailchimp_store_id = ? AND mailchimp_sync_error != ''",
                $mailchimpStoreId
            );
        } else {
            $where[] = $connection->quoteInto("mailchimp_store_id = ?", $mailchimpStoreId);
        }

        if ($filters !== null) {
            $where[] = $connection->quoteInto('type IN (?)', $filters);
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
     * @param       $scopeId
     * @param null  $scope
     * @param bool  $isStoreCreation
     * @return bool
     * @throws Mage_Core_Exception
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

            $ret = ($mailchimpStoreId !== null || $isStoreCreation)
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
     * Save the message errors for the data sent
     * succesfully or not to Mailchimp
     * in the file "Mailchimp_Batch_Status.log"
     *
     * @param  $message
     * @throws Mage_Core_Exception
     */
    public function logBatchStatus($message)
    {
        if ($this->isRequestLogEnabled()) {
            Mage::log($message . "\n", null, self::BATCH_STATUS_LOG, true);
        }
    }

    /**
     * Save how many data was sent to Mailchimp,
     * how many data was successfully and not sent to Mailchimp
     * in the file "Mailchimp_Batch_Status.log"
     *
     * @param  $message
     * @throws Mage_Core_Exception
     */
    public function logBatchQuantity($message)
    {
        if ($this->isRequestLogEnabled()) {
            Mage::log($message, null, self::BATCH_STATUS_LOG, true);
        }
    }

    /**
     * Save request made to MailChimp's API in "MailChimp_Requests.log" file.
     *
     * @param       $message
     * @param null  $batchId
     */
    public function logRequest($message, $batchId = null)
    {
        $logRequestEnabled = $this->isRequestLogEnabled();
        $logErrorEnabled = $this->isErrorLogEnabled();

        if (!$batchId && ($logRequestEnabled || $logErrorEnabled)) {
            Mage::log($message, null, 'MailChimp_Failing_Requests.log', true);
        } elseif ($logRequestEnabled) {
            $logDir = Mage::getBaseDir('var') . DS . 'log';
            $fileHelper = $this->getFileHelper();
            $fileHelper->open(array('path'=>$logDir));

            if (!$fileHelper->fileExists($logDir, false)) {
                $fileHelper->mkDir($logDir, 0750);
            }

            $logDir .= DS . 'MailChimp_Requests';

            if (!$fileHelper->fileExists($logDir, false)) {
                $fileHelper->mkDir($logDir, 0750);
            }

            $fileName = $logDir . DS . $batchId . '.Request.log';
            $oldPermission = umask(0033);
            $fileHelper->filePutContent($fileName, $message);
            umask($oldPermission);
        }
    }

    /**
     * @return bool
     * @throws Mage_Core_Exception
     */
    protected function isRequestLogEnabled()
    {
        $logEnabled = false;
        $logConfig = $this->getLogsEnabled();

        if ($logConfig == Ebizmarts_MailChimp_Model_System_Config_Source_Log::REQUEST_LOG
            || $logConfig == Ebizmarts_MailChimp_Model_System_Config_Source_Log::BOTH
        ) {
            $logEnabled = true;
        }

        return $logEnabled;
    }

    /**
     * @return bool
     * @throws Mage_Core_Exception
     */
    public function isErrorLogEnabled()
    {
        $logEnabled = false;
        $logConfig = $this->getLogsEnabled();

        if ($logConfig == Ebizmarts_MailChimp_Model_System_Config_Source_Log::ERROR_LOG
            || $logConfig == Ebizmarts_MailChimp_Model_System_Config_Source_Log::BOTH
        ) {
            $logEnabled = true;
        }

        return $logEnabled;
    }

    /**
     * Reset error messages from Products, Subscribers, Customers, Orders, Quotes
     * and set them to be sent again for given scope.
     *
     * @param           $scopeId
     * @param string    $scope
     * @throws Mage_Core_Exception
     */
    public function resetErrors($scopeId, $scope = 'stores')
    {
        $this->removeErrorsFromSubscribers($scopeId, $scope);

        // reset ecommerce data with errors
        $this->removeEcommerceSyncData($scopeId, $scope, true);
        $this->clearErrorGrid($scopeId, $scope);
    }

    /**
     * Clear mailchimp_errors grid for given scope.
     * Exclude subscriber if flag set to true.
     *
     * @param       $scopeId
     * @param       $scope
     * @param bool  $excludeSubscribers
     * @param null  $filters
     * @throws Mage_Core_Exception
     */
    public function clearErrorGrid($scopeId, $scope, $excludeSubscribers = false, $filters = null)
    {
        //Make sure there are no errors without no MailChimp store id due to older versions.
        $this->handleOldErrors();

        $mailchimpStoreId = $this->getMCStoreId($scopeId, $scope);

        if ($excludeSubscribers) {
            $this->clearErrorGridByMCStore($mailchimpStoreId, $filters);
        } else {
            $this->clearErrorGridByStoreId($scopeId, $filters);
        }
    }

    /**
     * @param $mailchimpStoreId
     * @param null $filters
     */
    public function clearErrorGridByMCStore($mailchimpStoreId, $filters = null)
    {
        $resource = $this->getCoreResource();
        $connection = $resource->getConnection('core_write');
        $tableName = $resource->getTableName('mailchimp/mailchimperrors');
        $where = array();
        $where[] = $connection->quoteInto("mailchimp_store_id = ?", $mailchimpStoreId);

        if ($filters !== null) {
            $where[] = $connection->quoteInto('regtype IN (?)', $filters);
        }

        $connection->delete($tableName, $where);
    }

    /**
     * @param $scopeId
     * @param null $filters
     */
    public function clearErrorGridByStoreId($scopeId, $filters = null)
    {
        $resource = $this->getCoreResource();
        $connection = $resource->getConnection('core_write');
        $tableName = $resource->getTableName('mailchimp/mailchimperrors');
        $where = array();

        if ($scopeId !== 0) {
            $where[] = $connection->quoteInto("store_id = ?", $scopeId);
        }

        if ($filters !== null) {
            $where[] = $connection->quoteInto('type IN (?)', $filters);
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
                $this->_saveErrorItem($error, $mailchimpStoreId);
            }
        }
    }

    /**
     * @param $error
     * @param $mailchimpStoreId
     */
    protected function _saveErrorItem($error, $mailchimpStoreId)
    {
        $error->setMailchimpStoreId($mailchimpStoreId)->save();
    }

    /**
     * @param $scopeId
     * @param $scope
     * @param null $filters
     * @throws Mage_Core_Exception
     */
    public function saveLastItemsSent($scopeId, $scope, $filters = null)
    {
        $mailchimpStoreId = $this->getMCStoreId($scopeId, $scope);
        $isSyncing = $this->getMCIsSyncing($mailchimpStoreId, $scopeId, $scope);

        if ($isSyncing != 1 && $filters !== null) {
            $configValues = array();

            if ($this->getCustomerResendLastId($scopeId, $scope) === null
                && in_array(Ebizmarts_MailChimp_Model_Config::IS_CUSTOMER, $filters)
            ) {
                $customerLastId = $this->getLastCustomerSent($scopeId, $scope);
                $configValues[] = array(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_CUSTOMER_LAST_ID, $customerLastId);
            }

            if ($this->getProductResendLastId($scopeId, $scope) === null
                && in_array(Ebizmarts_MailChimp_Model_Config::IS_PRODUCT, $filters)
            ) {
                $productLastId = $this->getLastProductSent($scopeId, $scope);
                $configValues[] = array(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_PRODUCT_LAST_ID, $productLastId);
            }

            if ($this->getOrderResendLastId($scopeId, $scope) === null
                && in_array(Ebizmarts_MailChimp_Model_Config::IS_ORDER, $filters)
            ) {
                $orderLastId = $this->getLastOrderSent($scopeId, $scope);
                $configValues[] = array(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_ORDER_LAST_ID, $orderLastId);
            }

            if ($this->getCartResendLastId($scopeId, $scope) === null
                && in_array(Ebizmarts_MailChimp_Model_Config::IS_QUOTE, $filters)
            ) {
                $cartLastId = $this->getLastCartSent($scopeId, $scope);
                $configValues[] = array(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_CART_LAST_ID, $cartLastId);
            }

            if ($this->getPromoCodeResendLastId($scopeId, $scope) === null
                && in_array(
                    Ebizmarts_MailChimp_Model_Config::IS_PROMO_CODE . ', '
                    . Ebizmarts_MailChimp_Model_Config::IS_PROMO_RULE, $filters
                )
            ) {
                $promoCodeLastId = $this->getLastPromoCodeSent($scopeId, $scope);
                $configValues[] = array(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_PCD_LAST_ID, $promoCodeLastId);
            }

            $configValues[] = array(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_RESEND_ENABLED, 1);
            $configValues[] = array(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_RESEND_TURN, 1);
            $this->saveMailchimpConfig($configValues, $scopeId, $scope);
        }
    }

    /**
     * @param $data
     * @return string
     */
    public function mcEscapeQuote($data)
    {
        return htmlspecialchars($data, ENT_QUOTES, null, false);
    }

    /**
     * @param $scopeId
     * @param $scope
     * @return int
     */
    protected function getLastCustomerSent($scopeId, $scope)
    {
        $lastCustomerSent = null;
        $mcStoreId = $this->getMCStoreId($scopeId, $scope);
        $syncDataCollection = $this->getMailchimpEcommerceSyncDataModel()->getCollection()
            ->addFieldToFilter('mailchimp_store_id', array('eq' => $mcStoreId))
            ->addFieldToFilter('type', array('eq' => Ebizmarts_MailChimp_Model_Config::IS_CUSTOMER))
            ->setOrder('related_id', 'DESC')
            ->setPageSize(1);

        if ($syncDataCollection->getSize()) {
            $customerSyncData = $syncDataCollection->getLastItem();
            $lastCustomerSent = $customerSyncData->getRelatedId();
        }

        return $lastCustomerSent;
    }

    /**
     * @param $scopeId
     * @param $scope
     * @return int
     */
    protected function getLastProductSent($scopeId, $scope)
    {
        $lastProductSent = null;
        $mcStoreId = $this->getMCStoreId($scopeId, $scope);
        $syncDataCollection = $this->getMailchimpEcommerceSyncDataModel()->getCollection()
            ->addFieldToFilter('mailchimp_store_id', array('eq' => $mcStoreId))
            ->addFieldToFilter('type', array('eq' => Ebizmarts_MailChimp_Model_Config::IS_PRODUCT))
            ->setOrder('related_id', 'DESC')
            ->setPageSize(1);

        if ($syncDataCollection->getSize()) {
            $productSyncData = $syncDataCollection->getLastItem();
            $lastProductSent = $productSyncData->getRelatedId();
        }

        return $lastProductSent;
    }

    /**
     * @param $scopeId
     * @param $scope
     * @return int
     */
    protected function getLastOrderSent($scopeId, $scope)
    {
        $lastOrderSent = null;
        $mcStoreId = $this->getMCStoreId($scopeId, $scope);
        $syncDataCollection = $this->getMailchimpEcommerceSyncDataModel()->getCollection()
            ->addFieldToFilter('mailchimp_store_id', array('eq' => $mcStoreId))
            ->addFieldToFilter('type', array('eq' => Ebizmarts_MailChimp_Model_Config::IS_ORDER))
            ->setOrder('related_id', 'DESC')
            ->setPageSize(1);

        if ($syncDataCollection->getSize()) {
            $orderSyncData = $syncDataCollection->getLastItem();
            $lastOrderSent = $orderSyncData->getRelatedId();
        }

        return $lastOrderSent;
    }

    /**
     * @param $scopeId
     * @param $scope
     * @return int
     */
    protected function getLastCartSent($scopeId, $scope)
    {
        $lastCartSent = null;
        $mcStoreId = $this->getMCStoreId($scopeId, $scope);
        $syncDataCollection = $this->getMailchimpEcommerceSyncDataModel()->getCollection()
            ->addFieldToFilter('mailchimp_store_id', array('eq' => $mcStoreId))
            ->addFieldToFilter('type', array('eq' => Ebizmarts_MailChimp_Model_Config::IS_QUOTE))
            ->setOrder('related_id', 'DESC')
            ->setPageSize(1);

        if ($syncDataCollection->getSize()) {
            $cartSyncData = $syncDataCollection->getLastItem();
            $lastCartSent = $cartSyncData->getRelatedId();
        }

        return $lastCartSent;
    }

    /**
     * @param $scopeId
     * @param $scope
     * @return int
     */
    protected function getLastPromoCodeSent($scopeId, $scope)
    {
        $lastPromoCodeSent = null;
        $mcStoreId = $this->getMCStoreId($scopeId, $scope);
        $syncDataCollection = Mage::getResourceModel('mailchimp/ecommercesyncdata_collection')
            ->addFieldToFilter('mailchimp_store_id', array('eq' => $mcStoreId))
            ->addFieldToFilter('type', array('eq' => Ebizmarts_MailChimp_Model_Config::IS_PROMO_CODE))
            ->setOrder('related_id', 'DESC')
            ->setPageSize(1);

        if ($syncDataCollection->getSize()) {
            $promoCodeSyncData = $syncDataCollection->getLastItem();
            $lastPromoCodeSent = $promoCodeSyncData->getRelatedId();
        }

        return $lastPromoCodeSent;
    }

    /**
     * Create MailChimp merge fields existing in the extension configuration page.
     *
     * @param  $scopeId
     * @param  $scope
     * @throws Exception
     */
    public function createMergeFields($scopeId, $scope)
    {
        $success = 0;
        $listId = $this->getGeneralList($scopeId, $scope);
        $maps = $this->unserialize($this->getMapFields($scopeId, $scope));
        $customFieldTypes = $this->unserialize($this->getCustomMergeFieldsSerialized($scopeId, $scope));

        if (count($maps) > 30) {
            $success = 2;
        } else {
            try {
                $api = $this->getApi($scopeId, $scope);
                $mailchimpFields = array();

                try {
                    $mailchimpFields = $api->getLists()->getMergeFields()->getAll(
                        $listId,
                        null,
                        null,
                        50
                    );
                } catch (MailChimp_Error $e) {
                    $this->logError($e->getFriendlyMessage());
                }

                if (!empty($mailchimpFields)) {
                    $success = $this->_mapFieldsIteration($maps, $mailchimpFields, $customFieldTypes, $api, $listId);
                }
            } catch (Ebizmarts_MailChimp_Helper_Data_ApiKeyException $e) {
                $this->logError($e->getMessage());
            }
        }

        return $success;
    }

    /**
     * @param $maps
     * @param $mailchimpFields
     * @param $customFieldTypes
     * @param $api
     * @param $listId
     * @return int
     */
    protected function _mapFieldsIteration($maps, $mailchimpFields, $customFieldTypes, $api, $listId)
    {
        foreach ($maps as $map) {
            $customAtt = $map['magento'];
            $chimpTag = $map['mailchimp'];
            $alreadyExists = false;

            foreach ($mailchimpFields['merge_fields'] as $mailchimpField) {
                if ($mailchimpField['tag'] == $chimpTag || strtoupper($chimpTag) == 'EMAIL') {
                    $alreadyExists = true;
                }
            }

            if (!$alreadyExists) {
                $this->_createCustomFieldTypes($customFieldTypes, $api, $customAtt, $listId, $chimpTag);
            }
        }

        $success = 1;

        return $success;
    }

    /**
     * @param $customFieldTypes
     * @param $api
     * @param $customAtt
     * @param $listId
     * @param $chimpTag
     */
    protected function _createCustomFieldTypes($customFieldTypes, $api, $customAtt, $listId, $chimpTag)
    {
        $created = false;

        foreach ($customFieldTypes as $customFieldType) {
            if ($customFieldType['value'] == $customAtt) {
                try {
                    $api->lists->mergeFields->add(
                        $listId,
                        $customFieldType['label'],
                        $customFieldType['field_type'],
                        null,
                        $chimpTag
                    );
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

            $this->_addMergeFieldByLabel($api, $label, $customAtt, $listId, $chimpTag);
        }
    }

    protected function _addMergeFieldByLabel($api, $label, $customAtt, $listId, $chimpTag)
    {
        try {
            if ($label) {
                //Shipping and Billing Address
                if ($customAtt == 13 || $customAtt == 14) {
                    $api->lists->mergeFields->add(
                        $listId,
                        $label,
                        'address',
                        null,
                        $chimpTag
                    );
                    //Birthday
                } elseif ($customAtt == 11) {
                    $api->lists->mergeFields->add(
                        $listId,
                        $label,
                        'birthday',
                        null,
                        $chimpTag
                    );
                } else {
                    $api->lists->mergeFields->add(
                        $listId,
                        $label,
                        'text',
                        null,
                        $chimpTag
                    );
                }
            }
        } catch (MailChimp_Error $e) {
            $this->logError($e->getFriendlyMessage());
        }
    }

    /**
     * Get Api object for given scope.
     *
     * @param       $scopeId
     * @param null  $scope
     * @return Ebizmarts_MailChimp|null
     * @throws Ebizmarts_MailChimp_Helper_Data_ApiKeyException
     */
    public function getApi($scopeId, $scope = null)
    {
        $apiKey = $this->getApiKey($scopeId, $scope);
        $api = null;

        if ($apiKey != null && $apiKey != "") {
            $timeOut = $this->getConfigValueForScope(
                Ebizmarts_MailChimp_Model_Config::GENERAL_TIME_OUT,
                $scopeId,
                $scope
            );
            $api = new Ebizmarts_MailChimp(
                $apiKey,
                array('timeout' => $timeOut),
                'Mailchimp4Magento'
                . (string)$this->getConfig()->getNode('modules/Ebizmarts_MailChimp/version')
            );
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
     * @param  $apiKey
     * @return Ebizmarts_MailChimp|null
     * @throws Ebizmarts_MailChimp_Helper_Data_ApiKeyException
     * @throws MailChimp_Error
     */
    public function getApiByKey($apiKey)
    {
        $api = null;

        if ($apiKey != null && $apiKey != "") {
            $api = new Ebizmarts_MailChimp(
                $apiKey,
                null,
                'Mailchimp4Magento'
                . (string)$this->getConfig()->getNode('modules/Ebizmarts_MailChimp/version')
            );
        } else {
            $e = new Ebizmarts_MailChimp_Helper_Data_ApiKeyException('You must provide a MailChimp API key');
            throw $e;
        }

        return $api;
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

        if (!empty($filterArray)) {
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
        $mailchimpScope = $this->getFirstScopeFromConfig(
            Ebizmarts_MailChimp_Model_Config::GENERAL_MCSTOREID,
            $mailChimpStoreId
        );

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
     * @param       $configValues
     * @param       $scopeId
     * @param       $scope
     * @param bool  $cleanCache
     */
    public function saveMailchimpConfig($configValues, $scopeId, $scope, $cleanCache = true)
    {
        foreach ($configValues as $configValue) {
            $this->getConfig()->saveConfig($configValue[0], $configValue[1], $scope, $scopeId);
        }

        if ($cleanCache) {
            $this->getConfig()->cleanCache();
        }
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
     * @return null|string
     * @throws Mage_Core_Model_Store_Exception
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

            if ($configImageSize == self::ORIGINAL_SIZE) {
                $imageUrl = $this->getOriginalPath($productImage);
            } else {
                $imageUrl = $this->getImageUrlForSize($imageSize, $productModel);
            }

            $this->setCurrentStore($curStore);
        }

        return $imageUrl;
    }

    /**
     * @param $productModel
     * @param $imageSize
     * @return string
     */
    public function getImageUrl($productModel, $imageSize)
    {
        return (string)$this->_getImageHelper()->init($productModel, $imageSize);
    }

    /**
     * Returns imageSize converted to camel case, and concatenates with functionName
     *
     * @param  $imageSize
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
     * @param  $imageSize
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
     * @param  $imageArray
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
     * @param  $functionName
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

    /**
     * @param       $scopeId
     * @param null  $scope
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function getImageSize($scopeId, $scope = null)
    {
        return $this->getConfigValueForScope(
            Ebizmarts_MailChimp_Model_Config::ECOMMERCE_IMAGE_SIZE,
            $scopeId,
            $scope
        );
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
     * @throws Mage_Core_Model_Store_Exception
     */
    public function getCurrentStoreId()
    {
        $curStore = $this->getMageApp()->getStore()->getId();

        return $curStore;
    }

    /**
     * @param $magentoStoreId
     */
    public function setCurrentStore($magentoStoreId)
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
     * @param       $scopeId
     * @param null  $scope
     * @return array|mixed
     */
    public function getCustomMergeFields($scopeId, $scope = null)
    {
        $customMergeFields = $this->unserialize($this->getCustomMergeFieldsSerialized($scopeId, $scope));

        if (!$customMergeFields) {
            $customMergeFields = array();
        }

        return $customMergeFields;
    }

    /**
     * @param $subscriberEmail
     * @return mixed
     */
    public function getOrderCollectionByCustomerEmail($subscriberEmail)
    {
        return Mage::getResourceModel('sales/order_collection')
            ->addFieldToFilter('customer_email', array('eq' => $subscriberEmail));
    }

    /**
     * Return html code for adding the MailChimp javascript.
     *
     * @return string
     * @throws Mage_Core_Exception
     * @throws Mage_Core_Model_Store_Exception
     */
    public function getMCJs()
    {
        $script = '';
        $url = null;
        $storeId = $this->getMageApp()->getStore()->getId();
        $mailchimpStoreId = $this->getMCStoreId($storeId);

        if ($this->isEcomSyncDataEnabled($storeId)) {
            $currentUrl = $this->getConfigValueForScope(
                Ebizmarts_MailChimp_Model_Config::ECOMMERCE_MC_JS_URL . "_$mailchimpStoreId",
                0,
                'default'
            );

            if (!empty($currentUrl)) {
                $url = $currentUrl;
            } else {
                $url = $this->retrieveAndSaveMCJsUrlInConfig($storeId);
            }

            $script = '<script type="text/javascript" src="' . $url . '" defer></script>';
        }

        return $script;
    }

    /**
     * Retrieve store data and save the MCJs URL for the current store in config table.
     *
     * @param           $scopeId
     * @param string    $scope
     * @return bool
     * @throws Mage_Core_Exception
     */
    public function retrieveAndSaveMCJsUrlInConfig($scopeId, $scope = 'stores')
    {
        $mcJsUrlSaved = false;

        try {
            $api = $this->getApi($scopeId, $scope);
            $mailchimpStoreId = $this->getMCStoreId($scopeId, $scope);
            $response = $api->getEcommerce()->getStores()->get($mailchimpStoreId, 'connected_site');

            if (isset($response['connected_site']['site_script']['url'])) {
                $url = $response['connected_site']['site_script']['url'];
                $configValues = array(
                    array(
                        Ebizmarts_MailChimp_Model_Config::ECOMMERCE_MC_JS_URL . "_$mailchimpStoreId",
                        $url
                    )
                );
                $this->saveMailchimpConfig($configValues, 0, 'default');
                $mcJsUrlSaved = true;
            }
        } catch (Ebizmarts_MailChimp_Helper_Data_ApiKeyException $e) {
            $this->logError($e->getMessage());
        } catch (MailChimp_Error $e) {
            $this->logError($e->getFriendlyMessage());
        } catch (Exception $e) {
            $this->logError($e->getMessage());
        }

        return $mcJsUrlSaved;
    }

    /**
     * @return Ebizmarts_MailChimp_Helper_Webhook
     */
    protected function getWebhookHelper()
    {
        return Mage::helper('mailchimp/webhook');
    }

    /**
     * Return the list of magento store IDs configured to synchronise to
     * the given mailchimp list ID.
     *
     * @param  $listId
     * @return array|mixed|null
     * @throws Mage_Core_Exception
     */
    public function getMagentoStoreIdsByListId($listId)
    {
        $storeIds = Mage::registry('mailchimp_store_ids_for_list_' . $listId);

        if ($storeIds === null) {
            $stores = $this->getMageApp()->getStores();
            $storeIds = array();

            foreach ($stores as $storeId => $store) {
                if ($this->isSubscriptionEnabled($storeId)) {
                    $storeListId = $this->getConfigValueForScope(
                        Ebizmarts_MailChimp_Model_Config::GENERAL_LIST,
                        $storeId
                    );
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
     * @param  $listId
     * @param  $email
     * @return false|Mage_Core_Model_Abstract|null
     * @throws Mage_Core_Exception
     */
    public function loadListSubscriber($listId, $email)
    {
        $subscriber = null;
        $storeIds = $this->getMagentoStoreIdsByListId($listId);
        //add store id 0 for those created from the back end.
        $storeIds[] = 0;

        if (!empty($storeIds)) {
            $subscriber = Mage::getModel('newsletter/subscriber')->getCollection()
                ->addFieldToFilter('store_id', array('in' => $storeIds))
                ->addFieldToFilter('subscriber_email', $email)
                ->setPageSize(1)->getLastItem();

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
     * @param  $listId
     * @param  $email
     * @return Mage_Core_Model_Abstract|null
     * @throws Mage_Core_Exception
     */
    public function loadListCustomer($listId, $email)
    {
        $customer = null;
        $storeIds = $this->getMagentoStoreIdsByListId($listId);

        if (!empty($storeIds)) {
            $customer = Mage::getResourceModel('customer/customer_collection')
                ->addFieldToFilter('store_id', array('in' => $storeIds))
                ->addFieldToFilter('email', array('eq' => $email))
                ->setPageSize(1)->getLastItem();

            if ($customer->getId()) {
                $customer = Mage::getModel('customer/customer')->load($customer->getId());
            } else {
                $customer = null;
            }
        }

        return $customer;
    }

    /**
     * @param           $path
     * @param           $scopeId
     * @param string    $scope
     * @return array|null
     */
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
     * Return true if the configPath has been saved specifically for the given scope.
     *
     * @param           $configPath
     * @param           $scopeId
     * @param string    $scope
     * @return bool|mixed
     * @throws Mage_Core_Exception
     */
    public function getIfConfigExistsForScope($configPath, $scopeId, $scope = 'stores')
    {
        $configPathArray = explode('/', $configPath);
        $configName = $configPathArray[2];
        $configAssociatedToScope = Mage::registry(
            'mailchimp_' . $configName . '_exists_for_scope_' . $scope . '_' . $scopeId
        );

        if ($configAssociatedToScope === null) {
            $configAssociatedToScope = false;
            $collection = Mage::getResourceModel('core/config_data_collection')
                ->addFieldToFilter('path', array('eq' => $configPath))
                ->addFieldToFilter('scope', array('eq' => $scope))
                ->addFieldToFilter('scope_id', array('eq' => $scopeId));

            if ($collection->getSize()) {
                foreach ($collection as $config) {
                    if ($config->getValue() !== null) {
                        $configAssociatedToScope = true;
                        break;
                    }
                }
            }

            Mage::register(
                'mailchimp_' . $configName
                . '_exists_for_scope_' . $scope
                . '_' . $scopeId,
                $configAssociatedToScope
            );
        }

        return $configAssociatedToScope;
    }

    /**
     *  Will return the first scope it finds, intended for Api calls usage.
     *
     * @param  $mailChimpStoreId
     * @return array
     */
    public function getScopeByMailChimpStoreId($mailChimpStoreId)
    {
        $mailchimpScope = null;
        $collection = Mage::getResourceModel('core/config_data_collection')
            ->addFieldToFilter('path', array('eq' => Ebizmarts_MailChimp_Model_Config::GENERAL_MCSTOREID))
            ->addFieldToFilter('value', array('eq' => $mailChimpStoreId))
            ->setPageSize(1);

        if ($collection->getSize()) {
            $configEntry = $collection->getLastItem();
            $mailchimpScope = array('scope' => $configEntry->getScope(), 'scope_id' => $configEntry->getScopeId());
        }

        return $mailchimpScope;
    }

    /**
     * Will return the first scope it finds,from core_config_data.
     *
     * @param  $path
     * @param  $value
     * @return array|null
     */
    public function getFirstScopeFromConfig($path, $value)
    {
        $mailchimpScope = null;
        $collection = Mage::getResourceModel('core/config_data_collection')
            ->addFieldToFilter('path', array('eq' => $path))
            ->addFieldToFilter('value', array('eq' => $value))
            ->setPageSize(1);

        if ($collection->getSize()) {
            $configEntry = $collection->getLastItem();
            $mailchimpScope = array('scope' => $configEntry->getScope(), 'scope_id' => $configEntry->getScopeId());
        }

        return $mailchimpScope;
    }

    /**
     * Return true if the config entry does not belong to the store required or website that contains that store.
     *
     * @param  $config
     * @param  $scope
     * @param  $scopeId
     * @param  $websiteId
     * @return bool
     */
    protected function isExtraEntry($config, $scope, $scopeId, $websiteId)
    {
        return $this->isNotDefaultScope($config)
            && ($this->isIncorrectScope($config, $scope)
                || $this->isDifferentWebsite($config, $scope, $websiteId)
                || $this->isDifferentStoreView($config, $scope, $scopeId));
    }

    public function updateSubscriberSyndData(
        $itemId,
        $syncDelta = null,
        $syncError = null,
        $syncModified = 0,
        $syncDeleted = null
    ) {
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

            $subscriber->setSubscriberSource(Ebizmarts_MailChimp_Model_Subscriber::MAILCHIMP_SUBSCRIBE);
            $subscriber->save();
        }
    }

    /**
     * Get date configured for subscriber data to be sent for the given scope.
     *
     * @param           $scopeId
     * @param  string   $scope
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function getSubMinSyncDateFlag($scopeId, $scope = 'stores')
    {
        return $this->getConfigValueForScope(
            Ebizmarts_MailChimp_Model_Config::GENERAL_SUBMINSYNCDATEFLAG,
            $scopeId,
            $scope
        );
    }

    /**
     * @param $batchArray
     * @param $productData
     * @param $counter
     * @return mixed
     */
    public function addEntriesToArray($batchArray, $productData, $counter)
    {
        if (!empty($productData)) {
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
    public function getApiStores()
    {
        return Mage::getModel('mailchimp/api_stores');
    }

    /**
     * @param           $scopeId
     * @param string    $scope
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function getCheckoutSubscribeValue($scopeId, $scope = 'stores')
    {
        return $this->getConfigValueForScope(
            Ebizmarts_MailChimp_Model_Config::GENERAL_CHECKOUT_SUBSCRIBE,
            $scopeId,
            $scope
        );
    }

    /**
     * @param           $scopeId
     * @param string    $scope
     * @return bool
     * @throws Mage_Core_Exception
     */
    public function isCheckoutSubscribeEnabled($scopeId, $scope = 'stores')
    {
        return ($this->isSubscriptionEnabled($scopeId, $scope)
            && $this->getCheckoutSubscribeValue($scopeId, $scope)
            != Ebizmarts_MailChimp_Model_System_Config_Source_Checkoutsubscribe::DISABLED);
    }

    /**
     * Modify is_syncing value if initial sync finished in given scope.
     *
     * @param           $syncValue
     * @param           $scopeId
     * @param string    $scope
     * @throws Mage_Core_Exception
     */
    public function setIsSyncingIfFinishedPerScope($syncValue, $scopeId, $scope = 'stores')
    {
        try {
            $mailchimpApi = $this->getApi($scopeId, $scope);
            $mailchimpStoreId = $this->getMCStoreId($scopeId, $scope);
            $isSyncing = $this->getMCIsSyncing($mailchimpStoreId, $scopeId, $scope);

            if ($mailchimpStoreId && $isSyncing != 1) {
                $this->getApiStores()->editIsSyncing($mailchimpApi, $syncValue, $mailchimpStoreId);
            }
        } catch (Ebizmarts_MailChimp_Helper_Data_ApiKeyException $e) {
            $this->logError($e->getMessage());
        }
    }

    /**
     * @param           $value
     * @param           $scopeId
     * @param string    $scope
     */
    public function setResendTurn($value, $scopeId, $scope = 'stores')
    {
        $configValue = array(array(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_RESEND_TURN, $value));
        $this->saveMailchimpConfig($configValue, $scopeId, $scope);
    }

    /**
     * @param           $scopeId
     * @param string    $scope
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function getResendTurn($scopeId, $scope = 'stores')
    {
        return $this->getConfigValueForScope(
            Ebizmarts_MailChimp_Model_Config::ECOMMERCE_RESEND_TURN,
            $scopeId,
            $scope
        );
    }

    /**
     * @param           $scopeId
     * @param string    $scope
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function getResendEnabled($scopeId, $scope = 'stores')
    {
        return $this->getConfigValueForScope(
            Ebizmarts_MailChimp_Model_Config::ECOMMERCE_RESEND_ENABLED,
            $scopeId,
            $scope
        );
    }

    /**
     * @param           $scopeId
     * @param string    $scope
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function getCustomerResendLastId($scopeId, $scope = 'stores')
    {
        return $this->getConfigValueForScope(
            Ebizmarts_MailChimp_Model_Config::ECOMMERCE_CUSTOMER_LAST_ID,
            $scopeId,
            $scope
        );
    }

    /**
     * @param           $scopeId
     * @param string    $scope
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function getProductResendLastId($scopeId, $scope = 'stores')
    {
        return $this->getConfigValueForScope(
            Ebizmarts_MailChimp_Model_Config::ECOMMERCE_PRODUCT_LAST_ID,
            $scopeId,
            $scope
        );
    }

    /**
     * @param           $scopeId
     * @param string    $scope
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function getOrderResendLastId($scopeId, $scope = 'stores')
    {
        return $this->getConfigValueForScope(
            Ebizmarts_MailChimp_Model_Config::ECOMMERCE_ORDER_LAST_ID,
            $scopeId,
            $scope
        );
    }

    /**
     * @param           $scopeId
     * @param string    $scope
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function getCartResendLastId($scopeId, $scope = 'stores')
    {
        return $this->getConfigValueForScope(
            Ebizmarts_MailChimp_Model_Config::ECOMMERCE_CART_LAST_ID,
            $scopeId,
            $scope
        );
    }

    /**
     * @param           $scopeId
     * @param string    $scope
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function getPromoCodeResendLastId($scopeId, $scope = 'stores')
    {
        return $this->getConfigValueForScope(
            Ebizmarts_MailChimp_Model_Config::ECOMMERCE_PCD_LAST_ID,
            $scopeId,
            $scope
        );
    }

    /**
     * @param $collection
     * @param $magentoStoreId
     * @param $itemType
     * @throws Mage_Core_Exception
     */
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
                    $this->logError(
                        $this->__(
                            'The item type sent in the filter does not match any of the available options.'
                        )
                    );
            }

            if ($resendTurn) {
                $collection->addFieldToFilter($keyCol, array('lteq' => $lastItemSent));
            } elseif ($lastItemSent !== null) {
                $collection->addFieldToFilter($keyCol, array('gt' => $lastItemSent));
            }
        }
    }

    /**
     * Check if all items have been sent and delete config values used in the resend process
     *
     * @param           $scopeId
     * @param string    $scope
     */
    public function handleResendFinish($scopeId, $scope = 'stores')
    {
        $allItemsSent = $this->allResendItemsSent($scopeId, $scope);

        if ($allItemsSent) {
            $this->deleteResendConfigValues($scopeId, $scope);
        }
    }

    /**
     * @param           $scopeId
     * @param string    $scope
     */
    protected function deleteResendConfigValues($scopeId, $scope = 'stores')
    {
        $config = $this->getConfig();
        $config->deleteConfig(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_CUSTOMER_LAST_ID, $scope, $scopeId);
        $config->deleteConfig(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_PRODUCT_LAST_ID, $scope, $scopeId);
        $config->deleteConfig(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_ORDER_LAST_ID, $scope, $scopeId);
        $config->deleteConfig(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_CART_LAST_ID, $scope, $scopeId);
        $config->deleteConfig(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_PCD_LAST_ID, $scope, $scopeId);
        $config->deleteConfig(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_RESEND_ENABLED, $scope, $scopeId);
        $config->deleteConfig(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_RESEND_TURN, $scope, $scopeId);
        $config->cleanCache();
    }

    /**
     * @param           $scopeId
     * @param string    $scope
     * @return bool
     */
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

    /**
     * @throws Mage_Core_Exception
     */
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

    /**
     * @throws Mage_Core_Exception
     */
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
     * @throws Mage_Core_Exception
     */
    protected function allResendItemsSentPerStoreView($storeId)
    {
        $customerId = $this->getCustomerResendLastId($storeId);

        if ($customerId) {
            $isMissingCustomer = $this->isMissingItemLowerThanId(
                $customerId,
                Ebizmarts_MailChimp_Model_Config::IS_CUSTOMER,
                $storeId
            );
        } else {
            $isMissingCustomer = false;
        }

        $productId = $this->getProductResendLastId($storeId);

        if ($productId) {
            $isMissingProduct = $this->isMissingItemLowerThanId(
                $productId,
                Ebizmarts_MailChimp_Model_Config::IS_PRODUCT,
                $storeId
            );
        } else {
            $isMissingProduct = false;
        }

        $orderId = $this->getOrderResendLastId($storeId);

        if ($orderId) {
            $isMissingOrder = $this->isMissingItemLowerThanId(
                $orderId,
                Ebizmarts_MailChimp_Model_Config::IS_ORDER,
                $storeId
            );
        } else {
            $isMissingOrder = false;
        }

        $cartId = $this->getCartResendLastId($storeId);

        if ($cartId) {
            $isMissingCart = $this->isMissingItemLowerThanId(
                $cartId,
                Ebizmarts_MailChimp_Model_Config::IS_QUOTE,
                $storeId
            );
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
     * @throws Mage_Core_Exception
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

    /**
     * @param $itemId
     * @param $itemType
     * @param $mailchimpStoreId
     * @return bool
     */
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

    /**
     * @param $itemId
     * @param $storeId
     * @return bool
     */
    protected function isMissingCustomerLowerThanId($itemId, $storeId)
    {
        $mailchimpStoreId = $this->getMCStoreId($storeId);
        $customerCollection = Mage::getResourceModel('customer/customer_collection')
            ->addFieldToFilter('store_id', array('eq' => $storeId))
            ->addFieldToFilter('entity_id', array('lteq' => $itemId));
        Mage::getModel('mailchimp/api_customers')
            ->joinMailchimpSyncDataWithoutWhere($customerCollection, $mailchimpStoreId);
        $customerCollection->getSelect()->where("m4m.mailchimp_sync_delta IS null");

        if ($customerCollection->getSize()) {
            $isMissing = true;
        } else {
            $isMissing = false;
        }

        return $isMissing;
    }

    /**
     * @param $itemId
     * @param $storeId
     * @return bool
     */
    protected function isMissingProductLowerThanId($itemId, $storeId)
    {
        $apiProducts = Mage::getModel('mailchimp/api_products');
        $mailchimpStoreId = $this->getMCStoreId($storeId);
        $productCollection = Mage::getResourceModel('catalog/product_collection')
            ->addStoreFilter($storeId)
            ->addFieldToFilter('entity_id', array('lteq' => $itemId));
        $productCollection->addFinalPrice();
        $apiProducts->joinQtyAndBackorders($productCollection);
        $apiProducts->joinMailchimpSyncData($productCollection, $mailchimpStoreId);
        $productCollection->getSelect()->where("m4m.mailchimp_sync_delta IS null");

        if ($productCollection->getSize()) {
            $isMissing = true;
        } else {
            $isMissing = false;
        }

        return $isMissing;
    }

    /**
     * @param $itemId
     * @param $storeId
     * @return bool
     */
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

        Mage::getModel('mailchimp/api_orders')
            ->joinMailchimpSyncDataWithoutWhere($orderCollection, $mailchimpStoreId);
        $orderCollection->getSelect()->where("m4m.mailchimp_sync_delta IS null");

        if ($orderCollection->getSize()) {
            $isMissing = true;
        } else {
            $isMissing = false;
        }

        return $isMissing;
    }

    /**
     * @param $itemId
     * @param $storeId
     * @return bool
     */
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

        Mage::getModel('mailchimp/api_carts')
            ->joinMailchimpSyncDataWithoutWhere($quoteCollection, $mailchimpStoreId);
        $quoteCollection->getSelect()->where("m4m.mailchimp_sync_delta IS null");

        if ($quoteCollection->getSize()) {
            $isMissing = true;
        } else {
            $isMissing = false;
        }

        return $isMissing;
    }

    /**
     * @param           $campaignId
     * @param           $scopeId
     * @param string    $scope
     * @return null
     */
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

    /**
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function getCustomerAmountLimit()
    {
        return $this->getConfigValueForScope(
            Ebizmarts_MailChimp_Model_Config::ECOMMERCE_CUSTOMER_AMOUNT,
            0, 'default'
        );
    }

    /**
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function getProductAmountLimit()
    {
        return $this->getConfigValueForScope(
            Ebizmarts_MailChimp_Model_Config::ECOMMERCE_PRODUCT_AMOUNT,
            0,
            'default'
        );
    }

    /**
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function getOrderAmountLimit()
    {
        return $this->getConfigValueForScope(
            Ebizmarts_MailChimp_Model_Config::ECOMMERCE_ORDER_AMOUNT,
            0,
            'default'
        );
    }

    /**
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function getPromoRuleAmountLimit()
    {
        return $this->getConfigValueForScope(
            Ebizmarts_MailChimp_Model_Config::ECOMMERCE_ORDER_AMOUNT,
            0,
            'default'
        );
    }

    /**
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function getCartAmountLimit()
    {
        return $this->getConfigValueForScope(
            Ebizmarts_MailChimp_Model_Config::ABANDONEDCART_AMOUNT,
            0,
            'default'
        );
    }

    /**
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function getSubscriberAmountLimit()
    {
        return $this->getConfigValueForScope(
            Ebizmarts_MailChimp_Model_Config::GENERAL_SUBSCRIBER_AMOUNT,
            0,
            'default'
        );
    }

    /**
     * @param           $scopeId
     * @param string    $scope
     * @return string
     * @throws Mage_Core_Exception
     * @throws Mage_Core_Model_Store_Exception
     */
    public function getStoreLanguageCode($scopeId, $scope = 'stores')
    {
        $isAdmin = $this->isAdmin();
        $userLangCode = Mage::app()->getLocale()->getLocaleCode();

        if ($isAdmin || '' == $lang = $this->_langToMCLanguage($userLangCode)) {
            // IS Admin OR if users lang is not supported, try store views default locale
            $userLangCode = $this->getConfigValueForScope(
                Mage_Core_Model_Locale::XML_PATH_DEFAULT_LOCALE,
                $scopeId,
                $scope
            );
            $lang = $this->_langToMCLanguage($userLangCode);
        }

        return $lang;
    }

    /**
     * @param           $scopeId
     * @param string    $scope
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function getStoreTimeZone($scopeId, $scope = 'stores')
    {
        return $this->getConfigValueForScope(Mage_Core_Model_Locale::XML_PATH_DEFAULT_TIMEZONE, $scopeId, $scope);
    }

    /**
     * @param           $scopeId
     * @param string    $scope
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function getStorePhone($scopeId, $scope = 'stores')
    {
        return $this->getConfigValueForScope('general/store_information/phone', $scopeId, $scope);
    }

    /**
     * @return array
     */
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

    /**
     * @param       $subscriber
     * @param bool  $forceUpdateStatus
     */
    public function subscribeMember($subscriber, $forceUpdateStatus = false)
    {
        $subscriber->setStatus(Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED);
        $subscriber->setSubscriberConfirmCode($subscriber->randomSequence());

        if ($forceUpdateStatus) {
            $subscriber->setMailchimpSyncModified(1);
        }

        $this->setMemberGeneralData($subscriber);
    }

    /**
     * @param $subscriber
     */
    public function unsubscribeMember($subscriber)
    {
        $subscriber->setStatus(Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED);
        $this->setMemberGeneralData($subscriber);
    }

    /**
     * @param $subscriber
     */
    protected function setMemberGeneralData($subscriber)
    {
        $subscriber->setImportMode(true);
        $subscriber->setSubscriberSource(Ebizmarts_MailChimp_Model_Subscriber::MAILCHIMP_SUBSCRIBE);
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
     * @param $date
     * @param string $format
     * @return bool
     */
    public function validateDate($date, $format = 'Y-m-d H:i:s')
    {
        if ($date !== null && $date !== '') {
            $d = DateTime::createFromFormat($format, $date);
        } else {
            $d = null;
        }

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

    /**
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function wasProductImageCacheFlushed()
    {
        return $this->getConfigValueForScope(
            Ebizmarts_MailChimp_Model_Config::PRODUCT_IMAGE_CACHE_FLUSH,
            0,
            'default'
        );
    }

    /**
     * @param $mailchimpStoreId
     * @return Ebizmarts_MailChimp|null
     * @throws Exception
     */
    public function getApiByMailChimpStoreId($mailchimpStoreId)
    {
        $scopeArray = $this->getFirstScopeFromConfig(
            Ebizmarts_MailChimp_Model_Config::GENERAL_MCSTOREID,
            $mailchimpStoreId
        );
        try {
            $api = $this->getApi($scopeArray['scope_id'], $scopeArray['scope']);
        } catch (Ebizmarts_MailChimp_Helper_Data_ApiKeyException $e) {
            $this->logError($e->getMessage());
        }

        return $api;
    }

    /**
     * Compare old api key with new one and return true if they belong to the same MailChimp account.
     *
     * @param  $oldApiKey
     * @param  $newApiKey
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

    /**
     * @param           $scopeId
     * @param string    $scope
     */
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

    /**
     * @param $scopeId
     * @param $scope
     * @return array
     */
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

        if (!empty($storesForScope)) {
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

        if (!empty($storesForScope)) {
            $whereString .= ")";
        }

        return $whereString;
    }

    /**
     * Convert language into Mailchimp compatible language code.
     *
     * @param string $languageCode
     * @return string   Returns empty string if not MC Language match found
     */
    protected function _langToMCLanguage($languageCode = '')
    {
        $mailchimpLanguage = '';

        if (in_array($languageCode, self::$LANGUAGES)) {
            $mailchimpLanguage = $languageCode;
        } else {
            $langIso = substr($languageCode, 0, 2);

            if (in_array($langIso, self::$LANGUAGES)) {
                $mailchimpLanguage = $langIso;
            }
        }

        return $mailchimpLanguage;
    }

    /**
     * @param           $scopeId
     * @param string    $scope
     * @return bool
     * @throws Mage_Core_Exception
     */
    public function isSubscriptionConfirmationEnabled($scopeId, $scope = 'stores')
    {
        return (bool)$this->getConfigValueForScope(
            Mage_Newsletter_Model_Subscriber::XML_PATH_CONFIRMATION_FLAG,
            $scopeId,
            $scope
        );
    }

    /**
     * @param       $scopeId
     * @param null  $scope
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function getPromoConfig($scopeId, $scope = null)
    {
        return $this->getConfigValueForScope(
            Ebizmarts_MailChimp_Model_Config::ECOMMERCE_SEND_PROMO,
            $scopeId,
            $scope
        );
    }

    /**
     * @param $dat
     * @param $string
     * @return string
     */
    public function getSyncFlagDataHtml($dat, $string)
    {
        $syncFlagDataArray = $this->getSyncFlagDataArray($dat);
        $syncFlagStatus = Ebizmarts_MailChimp_Model_System_Config_Source_Account::SYNC_FLAG_STATUS;
        $inProgress = Ebizmarts_MailChimp_Model_System_Config_Source_Account::IN_PROGRESS;

        if ($syncFlagDataArray[$syncFlagStatus] != $inProgress) {
            $FINISHED = Ebizmarts_MailChimp_Model_System_Config_Source_Account::FINISHED;

            if ($syncFlagDataArray[$syncFlagStatus] == $FINISHED) {
                $string .=
                    "<li>{$syncFlagDataArray[Ebizmarts_MailChimp_Model_System_Config_Source_Account::SYNC_FLAG_LABEL]}"
                    . ": <span style='color:forestgreen;font-weight: bold;'>{$this->__('Finished')}</span></li>";
            } else {
                $string .=
                    "<li>{$syncFlagDataArray[Ebizmarts_MailChimp_Model_System_Config_Source_Account::SYNC_FLAG_LABEL]}"
                    . ": <span style='color:forestgreen;font-weight: bold;'>"
                    . $this->__(
                        'Finished at %s',
                        $syncFlagDataArray[$syncFlagStatus]
                    ) . "</span></li>";
            }
        } else {
            $string .=
                "<li>{$syncFlagDataArray[Ebizmarts_MailChimp_Model_System_Config_Source_Account::SYNC_FLAG_LABEL]}"
                . ": <span style='color:#ed6502;font-weight: bold;'>{$this->__('In Progress')}</span></li>";
        }

        return $string;
    }

    /**
     * @param $dat
     * @return array
     */
    protected function getSyncFlagDataArray($dat)
    {
        $textArray = explode(': ', $dat['label']);
        //textArray indexes = 0 -> label / 1 -> status
        $textArray = $this->fixTimeTextIfNecessary($textArray);

        return $textArray;
    }

    /**
     * @param $textArray
     * @return array
     */
    protected function fixTimeTextIfNecessary($textArray)
    {
        if ($this->isDate($textArray)) {
            $textArray[1] = "$textArray[1]:$textArray[2]:$textArray[3]";
        }

        return $textArray;
    }

    /**
     * @param $textArray
     * @return bool
     */
    protected function isDate($textArray)
    {
        return count($textArray) == 4;
    }

    /**
     * @param $apiKey
     * @param $mailchimpStoreId
     * @return bool
     */
    public function getListIdByApiKeyAndMCStoreId($apiKey, $mailchimpStoreId)
    {
        $listId = false;

        try {
            $api = $this->getApiByKey($apiKey);
            $mcStore = $api->getEcommerce()->getStores()->get($mailchimpStoreId, 'list_id');

            if (isset($mcStore['list_id'])) {
                $listId = $mcStore['list_id'];
            }
        } catch (Ebizmarts_MailChimp_Helper_Data_ApiKeyException $e) {
            $this->logError($e->getMessage());
        } catch (MailChimp_Error $e) {
            $this->logError($e->getFriendlyMessage());
        } catch (Exception $e) {
            $this->logError($e->getMessage());
        }

        return $listId;
    }

    /**
     * @param $apiKey
     * @param $listId
     * @return array
     */
    public function getListInterestCategoriesByKeyAndList($apiKey, $listId)
    {
        $interestGroupsArray = array();

        try {
            $api = $this->getApiByKey($apiKey);
            $interestCategories = $api->getLists()->getInterestCategory()->getAll($listId, 'categories');

            foreach ($interestCategories['categories'] as $interestCategory) {
                $interestGroupsArray[] = array(
                    'id' => $interestCategory['id'],
                    'title' => $interestCategory['title'],
                    'type' => $interestCategory['type']
                );
            }
        } catch (Ebizmarts_MailChimp_Helper_Data_ApiKeyException $e) {
            $this->logError($e->getMessage());
        } catch (MailChimp_Error $e) {
            $this->logError($e->getFriendlyMessage());
        } catch (Exception $e) {
            $this->logError($e->getMessage());
        }

        return $interestGroupsArray;
    }

    /**
     * @param           $scopeId
     * @param string    $scope
     * @return array
     * @throws Exception
     */
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

    /**
     * @param           $scopeId
     * @param string    $scope
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function getLocalInterestCategories($scopeId, $scope = 'stores')
    {
        return $this->getConfigValueForScope(
            Ebizmarts_MailChimp_Model_Config::GENERAL_INTEREST_CATEGORIES,
            $scopeId,
            $scope
        );
    }

    /**
     * @param           $scopeId
     * @param string    $scope
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function getCheckoutSuccessHtmlBefore($scopeId, $scope = 'stores')
    {
        return $this->getConfigValueForScope(
            Ebizmarts_MailChimp_Model_Config::GENERAL_INTEREST_SUCCESS_BEFORE,
            $scopeId,
            $scope
        );
    }

    /**
     * @param           $scopeId
     * @param string    $scope
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function getCheckoutSuccessHtmlAfter($scopeId, $scope = 'stores')
    {
        return $this->getConfigValueForScope(
            Ebizmarts_MailChimp_Model_Config::GENERAL_INTEREST_SUCCESS_AFTER,
            $scopeId,
            $scope
        );
    }

    /**
     * @param $storeId
     * @return array
     * @throws Mage_Core_Exception
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
                    $rc[$item['id']]['interest'] =
                        array(
                            'id' => $item['id'],
                            'title' => $item['title'],
                            'type' => $item['type']
                        );
                }
            }

            $apiInterestCategoryInterest = $apiInterestCategory->getInterests();

            foreach ($interest as $interestId) {
                $mailchimpInterest = $apiInterestCategoryInterest->getAll($listId, $interestId, null, null, 100);

                foreach ($mailchimpInterest['interests'] as $mi) {
                    $rc[$mi['category_id']]['category'][$mi['display_order']] =
                        array(
                            'id' => $mi['id'],
                            'name' => $mi['name'],
                            'checked' => false
                        );
                }
            }
        } catch (MailChimp_Error $e) {
            $this->logError($e->getFriendlyMessage());
        }

        return $rc;
    }

    /**
     * @param           $customerId
     * @param           $subscriberId
     * @param           $storeId
     * @param null      $interest
     * @return array|null
     * @throws Mage_Core_Exception
     * @throws MailChimp_Error
     */
    public function getInterestGroups($customerId, $subscriberId, $storeId, $interest = null)
    {
        if ($this->isSubscriptionEnabled($storeId)) {
            if (!$interest) {
                $interest = $this->getInterest($storeId);
            }

            $interestGroup = $this->getInterestGroupModel();
            $interestGroup->getByRelatedIdStoreId($customerId, $subscriberId, $storeId);

            if ($interestGroup->getId()) {
                $interest = $this->_getInsterestChecked($interestGroup, $interest);
            }

            return $interest;
        } else {
            return array();
        }
    }

    protected function _getInsterestChecked($interestGroup, $interest)
    {
        $groups = $this->arrayDecode($interestGroup->getGroupdata());

        foreach ($groups as $key => $value) {
            if (isset($interest[$key])) {
                if (is_array($value)) {
                    foreach ($value as $groupId) {
                        $interest = $this->_getInterestCheckedByGroupId($interest, $key, $groupId);
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

        return $interest;
    }

    protected function _getInterestCheckedByGroupId($interest, $key, $groupId)
    {
        foreach ($interest[$key]['category'] as $gkey => $gvalue) {
            if ($gvalue['id'] == $groupId) {
                $interest[$key]['category'][$gkey]['checked'] = true;
            } elseif (!isset($interest[$key]['category'][$gkey]['checked'])) {
                $interest[$key]['category'][$gkey]['checked'] = false;
            }
        }

        return $interest;
    }

    /**
     * Format array to save in database.
     *
     * @param  $array
     * @return string
     */
    public function arrayEncode($array)
    {
        return json_encode($array);
    }

    /**
     * Set database encoded array to normal array
     *
     * @param  $encodedArray
     * @return mixed
     */
    public function arrayDecode($encodedArray)
    {
        return json_decode($encodedArray, true);
    }

    /**
     * @param       $params
     * @param       $storeId
     * @param null  $customerId
     * @param null  $subscriber
     * @throws Mage_Core_Model_Store_Exception
     */
    public function saveInterestGroupData($params, $storeId, $customerId = null, $subscriber = null)
    {
        $dateHelper = $this->getDateHelper();
        $groups = $this->getInterestGroupsIfAvailable($params);

        if ($groups) {
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

            $encodedGroups = $this->arrayEncode($groups);
            $interestGroup->setGroupdata($encodedGroups);
            //Avoid creating a new entry if no groupData available. (Customer creation)
            if ($interestGroup->getGroupdata()) {
                if ($storeId) {
                    $interestGroup->setStoreId($storeId);
                }

                $interestGroup->setUpdatedAt($dateHelper->formatDate(null, 'Y-m-d H:i:s'));
                $interestGroup->save();
            }
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
     * Return original path for the imageURL (not the catched one)
     *
     * @param  $productImage
     * @return string
     */
    protected function getOriginalPath($productImage)
    {
        return Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product' . $productImage;
    }

    /**
     * @return array
     */
    public function getAllApiKeys()
    {
        $ret = array();
        $stores = $this->getMageApp()->getStores();

        foreach ($stores as $storeId => $store) {
            try {
                $apiKey = $this->getApiKey($storeId);

                if (!isset($ret[$apiKey])) {
                    $ret[$apiKey] = $apiKey;
                }
            } catch (Exception $e) {
                continue;
            }
        }

        return $ret;
    }

    /**
     * @param int    $scopeId
     * @param string $scope
     * @return bool \ return true if image cache was flushed
     * @throws Mage_Core_Exception
     */
    public function isImageCacheFlushed($scopeId = 0, $scope = 'default')
    {
        return (bool)$this->getConfigValueForScope(
            Ebizmarts_MailChimp_Model_Config::PRODUCT_IMAGE_CACHE_FLUSH,
            $scopeId,
            $scope
        );
    }

    /**
     * @param $message add a warning with the message that receive as param
     */
    public function addAdminWarning($message)
    {
        $this->getAdminSession()->addWarning($message);
    }

    /**
     * @return Mage_Adminhtml_Model_Session
     */
    public function getAdminSession()
    {
        return Mage::getSingleton('adminhtml/session');
    }

    /**
     * @return mixed
     */
    public function getUrlForNotification()
    {
        $scopeArray = $this->getCurrentScope();
        $url = Mage::helper('adminhtml')
            ->getUrl(
                'adminhtml/ecommerce/resendEcommerceData',
                array('scope' => $scopeArray['scope'], 'scope_id' => $scopeArray['scope_id']
                )
            );

        return $url;
    }

    /**
     * delete flag from image cache flush after resend ecommerce data
     */
    public function deleteFlushMagentoCacheFlag()
    {
        $config = $this->getConfig();
        $config->deleteConfig(
            Ebizmarts_MailChimp_Model_Config::PRODUCT_IMAGE_CACHE_FLUSH,
            'default',
            0
        );
        $config->cleanCache();
    }

    /**
     * @return bool \ return true if is enabled include the taxes in the price of the products.
     * @throws Mage_Core_Exception
     */
    public function isIncludeTaxesEnabled($scopeId = 0, $scope = 'default')
    {
        return $this->getConfigValueForScope(
            Ebizmarts_MailChimp_Model_Config::ECOMMERCE_XML_INCLUDE_TAXES,
            $scopeId,
            $scope
        );
    }

    /**
     * @param $scopeId
     * @param $scope
     * @return bool \ return true if is enabled show interest gruops in the checkout success.
     * @throws Mage_Core_Exception
     */
    public function isInterestGroupEnabled($scopeId = 0, $scope = null)
    {
        return $this->getConfigValueForScope(
            Ebizmarts_MailChimp_Model_Config::GENERAL_INTEREST_SUCCESS_ACTIVE,
            $scopeId,
            $scope
        );
    }

    /**
     * @return Ebizmarts_MailChimp_Model_Ecommercesyncdata
     */
    protected function getMailchimpEcommerceSyncDataModel()
    {
        return Mage::getModel('mailchimp/ecommercesyncdata');
    }

    /**
     * @param       $index
     * @param int   $increment
     */
    public function modifyCounterSentPerBatch($index, $increment = 1)
    {
        if (array_key_exists($index, $this->_countersSendBatch)) {
            $this->_countersSendBatch[$index] = $this->_countersSendBatch[$index] + $increment;
        } else {
            $this->_countersSendBatch[$index] = 1;
        }
    }

    public function resetCountersSentPerBatch()
    {
        $this->_countersSendBatch = array();
    }

    /**
     * @return array
     */
    public function getCountersSentPerBatch()
    {
        return $this->_countersSendBatch;
    }

    /**
     * @param       $index
     * @param int   $increment
     */
    public function modifyCounterSubscribers($index, $increment = 1)
    {
        if (array_key_exists($index, $this->_countersSubscribers)) {
            $this->_countersSubscribers[$index] = $this->_countersSubscribers[$index] + $increment;
        } else {
            $this->_countersSubscribers[$index] = 1;
        }
    }

    public function resetCountersSubscribers()
    {
        $this->_countersSubscribers = array();
    }

    /**
     * @return array
     */
    public function getCountersSubscribers()
    {
        return $this->_countersSubscribers;
    }

    /**
     * @param       $index
     * @param bool  $hasError
     * @param int   $increment
     */
    public function modifyCounterDataSentToMailchimp($index, $hasError = false, $increment = 1)
    {
        $counterGetResponsesBatch = $this->getCountersDataSentToMailchimp();
        $statusChanged = self::DATA_SENT_TO_MAILCHIMP;

        if ($hasError === true) {
            $count = isset($counterGetResponsesBatch[$index][self::DATA_NOT_SENT_TO_MAILCHIMP])
                ? $counterGetResponsesBatch[$index][self::DATA_NOT_SENT_TO_MAILCHIMP]
                : 0;
            $statusChanged = self::DATA_NOT_SENT_TO_MAILCHIMP;
        } else {
            $count = isset($counterGetResponsesBatch[$index][self::DATA_SENT_TO_MAILCHIMP])
                ? $counterGetResponsesBatch[$index][self::DATA_SENT_TO_MAILCHIMP]
                : 0;
        }

        $this->setCountersDataSentToMailchimp($index, $statusChanged, $count + $increment);
    }

    public function resetCountersDataSentToMailchimp()
    {
        $this->_countersGetResponseBatch = array();
    }

    /**
     * @return array
     */
    public function getCountersDataSentToMailchimp()
    {
        return $this->_countersGetResponseBatch;
    }

    /**
     * @param $index
     * @param $statusChanged
     * @param $value
     * @return array
     */
    public function setCountersDataSentToMailchimp($index, $statusChanged, $value)
    {
        return $this->_countersGetResponseBatch[$index][$statusChanged] = $value;
    }

    /**
     * @param $str
     * @param string $prefix
     * @return string
     */
    public function mask($str, $prefix = '')
    {
        return $prefix . substr($str, 0, 6)
            . str_repeat('*', strlen($str) - 4 - strlen($prefix))
            . substr($str, -4);
    }

    /**
     * @param $apiKey
     * @return bool
     */
    public function isApiKeyObscure($apiKey)
    {
        return ($apiKey === '******');
    }

    /**
     * @return string
     * @throws Mage_Core_Exception
     */
    public function getApiKeyValue()
    {
        $scopeArray = $this->getCurrentScope();

        return $this->getApiKey($scopeArray['scope_id'], $scopeArray['scope']);
    }

    /**
     * @return Mage_Sales_Model_Order
     */
    public function getSessionLastRealOrder()
    {
        $checkoutSession = $this->getCheckOutSession();
        $order = $checkoutSession->getLastRealOrder();

        if ($order === null) {
            $orderId = $checkoutSession->getLastOrderId();
            $order = $this->getSalesOrderModel()->load($orderId);
        }

        return $order;
    }

    /**
     * @return Mage_Checkout_Model_Session
     */
    protected function getCheckOutSession()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * @return Mage_Sales_Model_Order
     */
    protected function getSalesOrderModel()
    {
        return Mage::getModel('sales/order');
    }

    /**
     * @return Ebizmarts_MailChimp_Helper_Date
     */
    public function getDateHelper()
    {
        return Mage::helper('mailchimp/date');
    }

    /**
     * @param $scopeId
     * @param $scope
     * @param $where
     * @throws Mage_Core_Exception
     */
    protected function removeErrorsFromSubscribers($scopeId, $scope)
    {
        $where = "mailchimp_sync_error <> ''";
        $storeIdsAsString = null;

        if ($scopeId != 0) {
            $storeIds = $this->getMagentoStoresForMCStoreIdByScope($scopeId, $scope);

            if (!empty($storeIds)) {
                $storeIdsAsString = implode(',', $storeIds);
            }
        }

        $resource = $this->getCoreResource();
        $conn = $resource->getConnection('core_write');
        $where = empty($storeIdsAsString) ?
            $where : $conn->quoteInto($where . " AND store_id IN (?)", $storeIdsAsString);
        $conn->update(
            $resource->getTableName('newsletter/subscriber'),
            array(
                'mailchimp_sync_delta' => '0000-00-00 00:00:00',
                'mailchimp_sync_error' => ''
            ),
            $where
        );
    }

    /**
     * @param $mailchimpStore
     * @param $fromStatus
     * @param $toStatus
     */
    public function markAllBatchesAs($mailchimpStore, $fromStatus, $toStatus)
    {
        $resource = $this->getCoreResource();
        $connection = $resource->getConnection('core_write');
        $tableName = $resource->getTableName('mailchimp/synchbatches');
        $connection->update(
            $tableName,
            array('status' => $toStatus),
            "store_id = '" . $mailchimpStore . "' and status = '" . $fromStatus . "'"
        );
    }

    /**
     * @param $mailchimpStore
     */
    public function cancelAllPendingBatches($mailchimpStore)
    {
        $this->markAllBatchesAs($mailchimpStore, self::BATCH_PENDING, self::BATCH_CANCELED);
    }

    /**
     * @param $mailchimpStore
     */
    public function restoreAllCanceledBatches($mailchimpStore)
    {
        $this->markAllBatchesAs($mailchimpStore, self::BATCH_CANCELED, self::BATCH_PENDING);
    }

    /**
     * Generates a storable representation of a value using the default adapter.
     *
     * @param mixed $value
     * @param array $options
     * @return string
     * @throws Zend_Serializer_Exception
     */
    public function serialize($value, array $options = array())
    {
        $parser = Mage::getModel('dataflow/convert_parser_serialize');
        $parser->setData($value);
        $parser->unparse();
        return $parser->getData();
    }

    /**
     * Creates a PHP value from a stored representation using the default adapter.
     *
     * @param string $serialized
     * @param array $options
     * @return mixed
     * @throws Zend_Serializer_Exception
     */
    public function unserialize($serialized, array $options = array())
    {
        $parser = Mage::getModel('dataflow/convert_parser_serialize');
        $parser->setData($serialized);
        $parser->parse();
        return $parser->getData();
    }

    /**
     * Check if Mailchimp API is available
     *
     * @param  $storeId
     * @return boolean
     */
    public function ping($storeId)
    {
        try {
            $api = $this->getApi($storeId);
            $api->getRoot()->info();
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * @return Ebizmarts_MailChimp_Helper_File
     */
    protected function getFileHelper()
    {
        return Mage::helper('mailchimp/file');
    }

    public function getTotalNewItemsSent()
    {
        $totalAmount = 0;
        $itemArray = array (self::ORD_NEW, self::SUB_NEW, self::PRO_NEW, self::CUS_NEW, self::QUO_NEW);

        foreach ($itemArray as $item) {
            if (array_key_exists($item, $this->_countersSendBatch)) {
                $totalAmount += $this->_countersSendBatch[$item];
            }
        }

        return $totalAmount;
    }
}
