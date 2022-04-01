<?php

/**
 * MailChimp For Magento
 *
 * @category  Ebizmarts_MailChimp
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     3/20/2020 11:14 AM
 * @file:     Webhook.php
 */
class Ebizmarts_MailChimp_Helper_Migration extends Mage_Core_Helper_Abstract
{
    /**
     * @var Ebizmarts_MailChimp_Helper_Data
     */
    protected $_helper;

    /**
     * @var Ebizmarts_MailChimp_Helper_Date
     */
    protected $_dateHelper;

    /**
     * @var Ebizmarts_MailChimp_Helper_Webhook
     */
    protected $_webhookHelper;

    public function __construct()
    {
        $this->_helper = Mage::helper('mailchimp');
        $this->_dateHelper = Mage::helper('mailchimp/date');
        $this->_webhookHelper = Mage::helper('mailchimp/webhook');
    }

    /**
     * Handle data migration for versions that require it.
     */
    public function handleMigrationUpdates()
    {
        $helper = $this->getHelper();
        $dateHelper = $this->getDateHelper();

        $initialTime = $dateHelper->getTimestamp();
        $migrateFrom115 = $helper->getConfigValueForScope(
            Ebizmarts_MailChimp_Model_Config::GENERAL_MIGRATE_FROM_115,
            0,
            'default'
        );
        $migrateFrom116 = $helper->getConfigValueForScope(
            Ebizmarts_MailChimp_Model_Config::GENERAL_MIGRATE_FROM_116,
            0,
            'default'
        );
        $migrateFrom1164 = $helper->getConfigValueForScope(
            Ebizmarts_MailChimp_Model_Config::GENERAL_MIGRATE_FROM_1164,
            0,
            'default'
        );
        $migrateFrom1120 = $helper->getConfigValueForScope(
            Ebizmarts_MailChimp_Model_Config::GENERAL_MIGRATE_FROM_1120,
            0,
            'default'
        );

        if ($migrateFrom115) {
            $this->_migrateFrom115($initialTime);
        } elseif ($migrateFrom116 && !$dateHelper->timePassed($initialTime)) {
            $this->_migrateFrom116($initialTime);
        } elseif ($migrateFrom1164 && !$dateHelper->timePassed($initialTime)) {
            $this->_migrateFrom1164($initialTime);
        } elseif ($migrateFrom1120 && !$dateHelper->timePassed($initialTime)) {
            $this->_migrateFrom1120($initialTime);
        }
    }

    /**
     * Migrate data from version 1.1.5 to the mailchimp_ecommerce_sync_data table.
     *
     * @param  $initialTime
     * @throws Mage_Core_Exception
     */
    protected function _migrateFrom115($initialTime)
    {
        $helper = $this->getHelper();
        $helper->logError("Start migration from 115");
        $dateHelper = $this->getDateHelper();
        $arrayMigrationConfigData = array('115' => true, '116' => false, '1164' => false);
        //migrate data from older version to the new schemma
        if ($helper->isEcommerceEnabled(0)) {
            $mailchimpStoreId = $helper->getMCStoreId(0);

            //migrate customers
            $this->_migrateCustomersFrom115($mailchimpStoreId, $initialTime);

            if (!$dateHelper->timePassed($initialTime)) {
                //migrate products
                $this->_migrateProductsFrom115($mailchimpStoreId, $initialTime);

                if (!$dateHelper->timePassed($initialTime)) {
                    //migrate orders
                    $this->_migrateOrdersFrom115($mailchimpStoreId, $initialTime);

                    if (!$dateHelper->timePassed($initialTime)) {
                        //migrate carts
                        $finished = $this->_migrateCartsFrom115($mailchimpStoreId, $initialTime);

                        if ($finished) {
                            $this->_migrateFrom115dropColumn($arrayMigrationConfigData);
                        }
                    }
                }
            }
        } else {
            $this->handleDeleteMigrationConfigData($arrayMigrationConfigData);
        }
    }

    /**
     * Helper function for data migration from version 1.1.5.
     *
     * @param           $collection
     * @param           $mailchimpStoreId
     * @param           $initialTime
     * @param Closure   $callback
     * @return bool
     */
    protected function _makeForCollectionItem($collection, $mailchimpStoreId, $initialTime, Closure $callback)
    {
        $dateHelper = $this->getDateHelper();
        $finished = false;

        if (!$collection->getSize()) {
            $finished = true;
        }

        $collection->setPageSize(100);

        $pages = $collection->getLastPageNumber();
        $currentPage = 1;

        do {
            $collection->setCurPage($currentPage);
            $this->_loadItemCollection($collection);

            foreach ($collection as $collectionItem) {
                $callback($collectionItem, $mailchimpStoreId);
            }

            $currentPage++;
            // clear collection,
            // if not done, the same page will be loaded each loop
            // - will also free memory
            $collection->clear();

            if ($dateHelper->timePassed($initialTime)) {
                break;
            }

            if ($currentPage == $pages) {
                $finished = true;
            }
        } while ($currentPage <= $pages);

        return $finished;
    }

    /**
     * @param $collection
     */
    protected function _loadItemCollection($collection)
    {
        $collection->load();
    }

    protected function _migrateFrom115dropColumn($arrayMigrationConfigData)
    {
        $helper = $this->getHelper();
        $this->handleDeleteMigrationConfigData($arrayMigrationConfigData);

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
            $helper->logError($e->getMessage());
        }

        $coreResource = $helper->getCoreResource();

        try {
            $quoteTable = $coreResource->getTableName('sales/quote');
            $connectionQuote = $setup->getConnection();
            $connectionQuote->dropColumn($quoteTable, 'mailchimp_sync_delta');
            $connectionQuote->dropColumn($quoteTable, 'mailchimp_sync_error');
            $connectionQuote->dropColumn($quoteTable, 'mailchimp_deleted');
            $connectionQuote->dropColumn($quoteTable, 'mailchimp_token');
        } catch (Exception $e) {
            $helper->logError($e->getMessage());
        }

        try {
            $orderTable = $coreResource->getTableName('sales/order');
            $connectionOrder = $setup->getConnection();
            $connectionOrder->dropColumn($orderTable, 'mailchimp_sync_delta');
            $connectionOrder->dropColumn($orderTable, 'mailchimp_sync_error');
            $connectionOrder->dropColumn($orderTable, 'mailchimp_sync_modified');
        } catch (Exception $e) {
            $helper->logError($e->getMessage());
        }
    }

    /**
     * @return Ebizmarts_MailChimp_Model_Ecommercesyncdata
     */
    protected function getMailchimpEcommerceSyncDataModel()
    {
        return Mage::getModel('mailchimp/ecommercesyncdata');
    }

    /**
     * Migrate Customers from version 1.1.5 to the mailchimp_ecommerce_sync_data table.
     *
     * @param  $mailchimpStoreId
     * @param  $initialTime
     * @throws Mage_Core_Exception
     */
    protected function _migrateCustomersFrom115($mailchimpStoreId, $initialTime)
    {
        $helper = $this->getHelper();
        $helper->logError("Migrate Customers from 115");

        try {
            $entityType = Mage::getSingleton('eav/config')->getEntityType('customer');
            $attribute = Mage::getModel('customer/attribute')->loadByCode($entityType, 'mailchimp_sync_delta');

            if ($attribute->getId()) {
                $mailchimpTableName = $helper->getCoreResource()->getTableName('mailchimp/ecommercesyncdata');
                $customerCollection = Mage::getResourceModel('customer/customer_collection');
                $customerCollection->addAttributeToFilter('mailchimp_sync_delta', array('gt' => '0000-00-00 00:00:00'));
                $customerCollection->getSelect()->joinLeft(
                    array('m4m' => $mailchimpTableName),
                    "m4m.related_id = e.entity_id AND m4m.type = '" . Ebizmarts_MailChimp_Model_Config::IS_CUSTOMER
                    . "' AND m4m.mailchimp_store_id = '" . $mailchimpStoreId . "'",
                    array('m4m.*')
                );
                $customerCollection->getSelect()->where(
                    "m4m.mailchimp_sync_delta IS null"
                );
                $this->_makeForCollectionItem(
                    $customerCollection,
                    $mailchimpStoreId,
                    $initialTime,
                    function ($customer, $mailchimpStoreId) {
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

                        $ecommerceSyncData = $this->getMailchimpEcommerceSyncDataModel();
                        $ecommerceSyncData->saveEcommerceSyncData(
                            $customerId,
                            Ebizmarts_MailChimp_Model_Config::IS_CUSTOMER,
                            $mailchimpStoreId,
                            $syncDelta,
                            $syncError,
                            $syncModified
                        );
                    }
                );
            }
        } catch (Exception $e) {
            $helper->logError($e->getMessage());
        }
    }

    /**
     * Migrate Products from version 1.1.5 to the mailchimp_ecommerce_sync_data table.
     *
     * @param  $mailchimpStoreId
     * @param  $initialTime
     * @throws Mage_Core_Exception
     */
    protected function _migrateProductsFrom115($mailchimpStoreId, $initialTime)
    {
        /**
         * @var $helper Ebizmarts_MailChimp_Helper_Migration
         */
        $helper = $this->getHelper();
        $helper->logError("Migrate Products from 115");

        try {
            $entityType = Mage_Catalog_Model_Product::ENTITY;
            $attributeCode = 'mailchimp_sync_delta';
            $attribute = Mage::getModel('eav/entity_attribute')->loadByCode($entityType, $attributeCode);

            if ($attribute->getId()) {
                $mailchimpTableName = $helper->getCoreResource()->getTableName('mailchimp/ecommercesyncdata');
                $productCollection = Mage::getResourceModel('catalog/product_collection');
                $productCollection->addAttributeToFilter('mailchimp_sync_delta', array('gt' => '0000-00-00 00:00:00'));
                $productCollection->getSelect()->joinLeft(
                    array('m4m' => $mailchimpTableName),
                    "m4m.related_id = e.entity_id AND m4m.type = '" . Ebizmarts_MailChimp_Model_Config::IS_PRODUCT
                    . "' AND m4m.mailchimp_store_id = '" . $mailchimpStoreId . "'",
                    array('m4m.*')
                );
                $productCollection->getSelect()->where("m4m.mailchimp_sync_delta IS null");
                $this->_makeForCollectionItem(
                    $productCollection,
                    $mailchimpStoreId,
                    $initialTime,
                    function ($product, $mailchimpStoreId) {
                        $helper = $this->getHelper();
                        $productId = $product->getEntityId();
                        $_resource = Mage::getResourceSingleton('catalog/product');
                        $syncDelta = $_resource->getAttributeRawValue(
                            $productId,
                            'mailchimp_sync_delta',
                            $helper->getMageApp()->getStore()
                        );
                        $syncError = null;
                        $syncModified = null;

                        if ($product->getMailchimpSyncError()) {
                            $syncError = $product->getMailchimpSyncError();
                        }

                        if ($product->getMailchimpSyncModified()) {
                            $syncModified = $product->getMailchimpSyncModified();
                        }

                        $ecommerceSyncData = $this->getMailchimpEcommerceSyncDataModel();
                        $ecommerceSyncData->saveEcommerceSyncData(
                            $productId,
                            Ebizmarts_MailChimp_Model_Config::IS_PRODUCT,
                            $mailchimpStoreId,
                            $syncDelta,
                            $syncError,
                            $syncModified
                        );
                    }
                );
            }
        } catch (Exception $e) {
            $helper->logError($e->getMessage());
        }
    }

    /**
     * @return Mage_Sales_Model_Order
     */
    protected function getSalesOrderModel()
    {
        return Mage::getModel('sales/order');
    }
   /**
     * @return Mage_Sales_Model_Quote
     */
    protected function getSalesQuoteModel()
    {
        return Mage::getModel('sales/quote');
    }

    /**
     * Migrate Orders from version 1.1.5 to the mailchimp_ecommerce_sync_data table.
     *
     * @param  $mailchimpStoreId
     * @param  $initialTime
     * @throws Mage_Core_Exception
     */
    protected function _migrateOrdersFrom115($mailchimpStoreId, $initialTime)
    {
        $helper = $this->getHelper();
        $helper->logError("Migrate Orders from 115");

        try {
            $resource = $helper->getCoreResource();
            $readConnection = $resource->getConnection('core_read');
            $tableName = $resource->getTableName('sales/order');
            $orderFields = $readConnection->describeTable($tableName);

            if (isset($orderFields['mailchimp_sync_delta'])) {
                $mailchimpTableName = $resource->getTableName('mailchimp/ecommercesyncdata');
                $orderCollection = Mage::getResourceModel('sales/order_collection');

                $orderCollection->getSelect()->joinLeft(
                    array('m4m' => $mailchimpTableName),
                    "m4m.related_id = main_table.entity_id AND m4m.type = '"
                    . Ebizmarts_MailChimp_Model_Config::IS_ORDER .
                    "' AND m4m.mailchimp_store_id = '" . $mailchimpStoreId . "'",
                    array('m4m.*')
                );
                $orderCollection->getSelect()
                    ->where(
                        "m4m.mailchimp_sync_delta IS NULL AND main_table.mailchimp_sync_delta > '0000-00-00 00:00:00'"
                    );
                $this->_makeForCollectionItem(
                    $orderCollection,
                    $mailchimpStoreId,
                    $initialTime,
                    function ($order, $mailchimpStoreId) {
                        $orderId = $order->getEntityId();
                        $syncError = null;
                        $syncModified = null;
                        $orderObject = $this->getSalesOrderModel()->load($orderId);
                        $syncDelta = $orderObject->getMailchimpSyncDelta();

                        if ($order->getMailchimpSyncError()) {
                            $syncError = $order->getMailchimpSyncError();
                        }

                        if ($order->getMailchimpSyncModified()) {
                            $syncModified = $order->getMailchimpSyncModified();
                        }

                        $ecommerceSyncData = $this->getMailchimpEcommerceSyncDataModel();
                        $ecommerceSyncData->saveEcommerceSyncData(
                            $orderId,
                            Ebizmarts_MailChimp_Model_Config::IS_ORDER,
                            $mailchimpStoreId,
                            $syncDelta,
                            $syncError,
                            $syncModified
                        );
                    }
                );
            }
        } catch (Exception $e) {
            $helper->logError($e->getMessage());
        }
    }

    /**
     * Migrate Carts from version 1.1.5 to the mailchimp_ecommerce_sync_data table.
     *
     * @param  $mailchimpStoreId
     * @param  $initialTime
     * @return bool
     * @throws Mage_Core_Exception
     */
    protected function _migrateCartsFrom115($mailchimpStoreId, $initialTime)
    {
        $helper = $this->getHelper();
        $helper->logError("Migrate Carts from 115");

        try {
            $resource = $helper->getCoreResource();
            $readConnection = $resource->getConnection('core_read');
            $tableName = $resource->getTableName('sales/quote');
            $quoteFields = $readConnection->describeTable($tableName);

            if (isset($quoteFields['mailchimp_sync_delta'])) {
                $mailchimpTableName = $resource->getTableName('mailchimp/ecommercesyncdata');
                $quoteCollection = Mage::getResourceModel('sales/quote_collection');
                $quoteCollection->getSelect()->joinLeft(
                    array('m4m' => $mailchimpTableName),
                    "m4m.related_id = main_table.entity_id AND m4m.type = '"
                    . Ebizmarts_MailChimp_Model_Config::IS_QUOTE
                    . "' AND m4m.mailchimp_store_id = '" . $mailchimpStoreId . "'",
                    array('m4m.*')
                );
                // be sure that the quotes are already in mailchimp and not deleted
                $quoteCollection->getSelect()
                    ->where(
                        "m4m.mailchimp_sync_delta IS NULL AND main_table.mailchimp_sync_delta > '0000-00-00 00:00:00'"
                    );
                $finished = $this->_makeForCollectionItem(
                    $quoteCollection,
                    $mailchimpStoreId,
                    $initialTime,
                    function ($quote, $mailchimpStoreId) {
                        $helper = $this->getHelper();
                        $quoteId = $quote->getEntityId();
                        $syncError = null;
                        $syncDeleted = null;
                        $token = null;
                        $quoteObject = $this->getSalesQuoteModel()->loadByIdWithoutStore($quoteId);
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

                        $ecommerceSyncData = $this->getMailchimpEcommerceSyncDataModel();
                        $ecommerceSyncData->saveEcommerceSyncData(
                            $quoteId,
                            Ebizmarts_MailChimp_Model_Config::IS_QUOTE,
                            $mailchimpStoreId,
                            $syncDelta,
                            $syncError,
                            null,
                            $syncDeleted,
                            $token
                        );
                    }
                );
            } else {
                $finished = true;
            }

            return $finished;
        } catch (Exception $e) {
            $helper->logError(
                $helper->__(
                    'Unexpected error happened during migration from version 1.1.5 to 1.1.6.'
                    . 'Please contact our support at '
                ) . 'mailchimp@ebizmarts-desk.zendesk.com'
                . $helper->__(' See error details below.')
            );
            $helper->logError($e->getMessage());

            return false;
        }
    }

    /**
     * Delete config data for migration from 1.1.5.
     */
    protected function delete115MigrationConfigData()
    {
        $helper = $this->getHelper();
        $helper->getConfig()->deleteConfig(
            Ebizmarts_MailChimp_Model_Config::GENERAL_MIGRATE_FROM_115,
            'default',
            0
        );
    }

    /**
     * Modify is_syncing value if initial sync finished for all stores.
     *
     * @param $syncValue
     */
    protected function _setIsSyncingIfFinishedInAllStores($syncValue)
    {
        $stores = $this->_helper->getMageApp()->getStores();

        foreach ($stores as $storeId => $store) {
            $ecommEnabled = $this->_helper->isEcomSyncDataEnabled($storeId);

            if ($ecommEnabled) {
                $this->_helper->setIsSyncingIfFinishedPerScope($syncValue, $storeId);
            }
        }
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
            $arrayMigrationConfigData = array('115' => false, '116' => true, '1164' => false);
            $this->handleDeleteMigrationConfigData($arrayMigrationConfigData);
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
        $helper = $this->getHelper();
        $helper->logError("Migrate Orders from 116");
        $dateHelper = $this->getDateHelper();
        $finished = false;

        if (!$dateHelper->timePassed($initialTime)) {
            $finished = true;
            $stores = $helper->getMageApp()->getStores();

            foreach ($stores as $storeId => $store) {
                if ($helper->isEcomSyncDataEnabled($storeId)) {
                    Mage::getModel('mailchimp/api_batches')->replaceAllOrders($initialTime, $storeId);
                }

                if ($dateHelper->timePassed($initialTime)) {
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
     * @throws Mage_Core_Exception
     */
    public function migrationFinished()
    {
        $helper = $this->getHelper();
        $migrationFinished = false;

        $migrateFrom115 = $helper->getConfigValueForScope(
            Ebizmarts_MailChimp_Model_Config::GENERAL_MIGRATE_FROM_115,
            0,
            'default'
        );

        $migrateFrom116 = $helper->getConfigValueForScope(
            Ebizmarts_MailChimp_Model_Config::GENERAL_MIGRATE_FROM_116,
            0,
            'default'
        );

        $migrateFrom1164 = $helper->getConfigValueForScope(
            Ebizmarts_MailChimp_Model_Config::GENERAL_MIGRATE_FROM_1164,
            0,
            'default'
        );

        $migrateFrom1120 = $helper->getConfigValueForScope(
            Ebizmarts_MailChimp_Model_Config::GENERAL_MIGRATE_FROM_1120,
            0,
            'default'
        );

        if (!$migrateFrom115 && !$migrateFrom116 && !$migrateFrom1164 && !$migrateFrom1120) {
            $migrationFinished = true;
        }

        return $migrationFinished;
    }

    /**
     * Delete config data for migration from 1.1.6.
     */
    public function delete116MigrationConfigData()
    {
        $helper = $this->getHelper();
        $stores = $helper->getMageApp()->getStores();
        $helper->getConfig()->deleteConfig(
            Ebizmarts_MailChimp_Model_Config::GENERAL_MIGRATE_FROM_116,
            'default',
            0
        );

        foreach ($stores as $storeId => $store) {
            $helper->getConfig()->deleteConfig(
                Ebizmarts_MailChimp_Model_Config::GENERAL_MIGRATE_LAST_ORDER_ID,
                'stores',
                $storeId
            );
        }
    }

    /**
     * Migrate data from version 1.1.6.4.
     *
     * @param $initialTime
     */
    protected function _migrateFrom1164($initialTime)
    {
        $helper = $this->getHelper();
        $helper->logError("Migrate from 1164");
        $dateHelper = $this->getDateHelper();

        if (!$dateHelper->timePassed($initialTime)) {
            $writeConnection = $helper->getCoreResource()->getConnection('core_write');
            $resource = Mage::getResourceModel('mailchimp/ecommercesyncdata');
            $writeConnection->update($resource->getMainTable(), array('batch_id' => '1'), "batch_id = 0");
            $arrayMigrationConfigData = array('115' => false, '116' => false, '1164' => true);
            $this->handleDeleteMigrationConfigData($arrayMigrationConfigData);
        }
    }

    /**
     * Delete config data for migration from 1.1.6.4.
     */
    protected function delete1164MigrationConfigData()
    {
        $helper = $this->getHelper();

        $helper->getConfig()->deleteConfig(
            Ebizmarts_MailChimp_Model_Config::GENERAL_MIGRATE_FROM_1164,
            'default',
            0
        );
    }

    /**
     * Migrate data from version 1.1.21.
     *
     * @param $initialTime
     */
    protected function _migrateFrom1120($initialTime)
    {
        $helper = $this->getHelper();
        $helper->logError("Migrate from 1120");
        $dateHelper = $this->getDateHelper();
        $webhookHelper = $this->getWebhookHelper();

        if (!$dateHelper->timePassed($initialTime)) {
            // Get all stores data.
            $stores = $helper->getMageApp()->getStores();

            $events = array(
                'subscribe' => true,
                'unsubscribe' => true,
                'profile' => true,
                'cleaned' => true,
                'upemail' => true,
                'campaign' => false
            );

            $sources = array(
                'user' => true,
                'admin' => true,
                'api' => false
            );

            foreach ($stores as $storeId => $store) {
                // Gets the ListId and WebhookId for the iterated store.
                if ($helper->isMailChimpEnabled( $storeId, 'stores')) {
                    $listId = $helper->getGeneralList($storeId, "stores");
                    if ($listId > 0) {
                        $webhookId = $webhookHelper->getWebhookId($storeId, "stores");

                        // Edits the webhook with the new $event array.
                        if ($webhookId) {
                            $helper
                                ->getApi($storeId, $store)
                                ->getLists()
                                ->getWebhooks()
                                ->edit($listId, $webhookId, null, $events, $sources);
                        }
                    }
                }
                if ($dateHelper->timePassed($initialTime)) {
                    $finished = false;
                    break;
                }
            }
            $arrayMigrationConfigData = array('115' => false, '116' => false, '1164' => false, '1120' => true);
            $this->handleDeleteMigrationConfigData($arrayMigrationConfigData);
        }
    }

    /**
     * Delete config data for migration from 1.1.21.
     */
    protected function delete1120MigrationConfigData()
    {
        $helper = $this->getHelper();

        $helper->getConfig()->deleteConfig(
            Ebizmarts_MailChimp_Model_Config::GENERAL_MIGRATE_FROM_1120,
            'default',
            0
        );
    }

    /**
     * @param $arrayMigrationConfigData
     */
    public function handleDeleteMigrationConfigData($arrayMigrationConfigData)
    {
        $helper = $this->getHelper();
        foreach ($arrayMigrationConfigData as $migrationConfigData => $value) {
            if ($migrationConfigData == '115' && $value) {
                $this->delete115MigrationConfigData();
            }

            if ($migrationConfigData == '116' && $value) {
                $this->delete116MigrationConfigData();
            }

            if ($migrationConfigData == '1164' && $value) {
                $this->delete1164MigrationConfigData();
            }

            if ($migrationConfigData == '1120' && $value) {
                $this->delete1120MigrationConfigData();
            }
        }

        $helper->getConfig()->cleanCache();
    }

    /**
     * @var Ebizmarts_MailChimp_Helper_Data
     */
    protected function getHelper($type='_migrateProductsFrom115')
    {
        return $this->_helper;
    }

    /**
     * @var Ebizmarts_MailChimp_Helper_Date
     */
    protected function getDateHelper()
    {
        return $this->_dateHelper;
    }

    /**
     * @var Ebizmarts_MailChimp_Helper_Webhook
     */
    protected function getWebhookHelper()
    {
        return $this->_webhookHelper;
    }
}
