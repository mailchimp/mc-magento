<?php

/**
 * mailchimp-lib Magento Component
 *
 * @category  Ebizmarts
 * @package   mailchimp-lib
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Ebizmarts_MailChimp_Model_Api_Batches
{
    const SEND_PROMO_ENABLED = 1;

    /**
     * @var Ebizmarts_MailChimp_Helper_Data
     */
    protected $_mailchimpHelper;

    /**
     * @var Ebizmarts_MailChimp_Helper_Date
     */
    protected $_mailchimpDateHelper;

    /**
     * @var Ebizmarts_MailChimp_Model_Api_Customers
     */
    protected $_apiCustomers;

    /**
     * @var Ebizmarts_MailChimp_Model_Api_Products
     */
    protected $_apiProducts;

    /**
     * @var Ebizmarts_MailChimp_Model_Api_Carts
     */
    protected $_apiCarts;

    /**
     * @var Ebizmarts_MailChimp_Model_Api_Orders
     */
    protected $_apiOrders;

    /**
     * @var Ebizmarts_MailChimp_Model_Api_PromoRules
     */
    protected $_apiPromoRules;

    /**
     * @var Ebizmarts_MailChimp_Model_Api_PromoCodes
     */
    protected $_apiPromoCodes;

    /**
     * @var Ebizmarts_MailChimp_Model_Api_Subscribers
     */
    protected $_apiSubscribers;

    /**
     * @var Ebizmarts_MailChimp_Model_Synchbatches
     */
    protected $_syncBatchesModel;

    public function __construct()
    {
        $this->_mailchimpHelper = Mage::helper('mailchimp');
        $this->_mailchimpDateHelper = Mage::helper('mailchimp/date');
        $this->_apiCustomers = Mage::getModel('mailchimp/api_customers');
        $this->_apiProducts = Mage::getModel('mailchimp/api_products');
        $this->_apiCarts = Mage::getModel('mailchimp/api_carts');
        $this->_apiOrders = Mage::getModel('mailchimp/api_orders');
        $this->_apiPromoRules = Mage::getModel('mailchimp/api_promoRules');
        $this->_apiPromoCodes = Mage::getModel('mailchimp/api_promoCodes');
        $this->_apiSubscribers = Mage::getModel('mailchimp/api_subscribers');
        $this->_syncBatchesModel = Mage::getModel('mailchimp/synchbatches');
    }

    /**
     * @return Ebizmarts_MailChimp_Helper_Data
     */
    protected function getHelper()
    {
        return $this->_mailchimpHelper;
    }

    /**
     * @return Ebizmarts_MailChimp_Helper_Date
     */
    protected function getDateHelper()
    {
        return $this->_mailchimpDateHelper;
    }

    /**
     * @return Ebizmarts_MailChimp_Model_Api_Stores
     */
    protected function getApiStores()
    {
        return Mage::getModel('mailchimp/api_stores');
    }

    /**
     * @return Ebizmarts_MailChimp_Model_Api_Customers
     */
    protected function getApiCustomers()
    {
        return $this->_apiCustomers;
    }

    /**
     * @return Ebizmarts_MailChimp_Model_Api_Products
     */
    public function getApiProducts()
    {
        return $this->_apiProducts;
    }

    /**
     * @return Ebizmarts_MailChimp_Model_Api_Carts
     */
    public function getApiCarts()
    {
        return $this->_apiCarts;
    }

    /**
     * @return Ebizmarts_MailChimp_Model_Api_Orders
     */
    public function getApiOrders()
    {
        return $this->_apiOrders;
    }

    /**
     * @return Ebizmarts_MailChimp_Model_Api_PromoRules
     */
    public function getApiPromoRules()
    {
        return $this->_apiPromoRules;
    }

    /**
     * @return Ebizmarts_MailChimp_Model_Api_PromoCodes
     */
    public function getApiPromoCodes()
    {
        return $this->_apiPromoCodes;
    }

    /**
     * @return Ebizmarts_MailChimp_Model_Api_Subscribers
     */
    protected function getApiSubscribers()
    {
        return $this->_apiSubscribers;
    }

    /**
     * @return Ebizmarts_MailChimp_Model_Synchbatches
     */
    protected function getSyncBatchesModel()
    {
        return $this->_syncBatchesModel;
    }

    /**
     * @return array
     */
    protected function getStores()
    {
        return Mage::app()->getStores();
    }

    /**
     * @return string
     */
    public function getMagentoBaseDir()
    {
        return Mage::getBaseDir();
    }

    /**
     * @param $baseDir
     * @param $batchId
     * @return bool
     */
    public function batchDirExists($baseDir, $batchId)
    {
        return is_dir($baseDir . DS . 'var' . DS . 'mailchimp' . DS . $batchId);
    }

    /**
     * @param $baseDir
     * @param $batchId
     * @return bool
     */
    public function removeBatchDir($baseDir, $batchId)
    {
        return rmdir($baseDir . DS . 'var' . DS . 'mailchimp' . DS . $batchId);
    }

    /**
     * Get Results and send Ecommerce Batches.
     */
    public function handleEcommerceBatches()
    {
        $helper = $this->getHelper();
        $stores = $this->getStores();
        $helper->handleResendDataBefore();

        foreach ($stores as $store) {
            $storeId = $store->getId();

            if ($helper->isEcomSyncDataEnabled($storeId)) {
                if ($helper->ping($storeId)) {
                    $this->_getResults($storeId);
                    $this->_sendEcommerceBatch($storeId);
                } else {
                    $helper->logError(
                        'Could not connect to MailChimp: Make sure the API Key is correct "
                        . "and there is an internet connection'
                    );
                    return;
                }
            }
        }

        $helper->handleResendDataAfter();
        $syncedDateArray = array();

        foreach ($stores as $store) {
            $storeId = $store->getId();
            $syncedDateArray = $this->addSyncValueToArray($storeId, $syncedDateArray);
        }

        $this->handleSyncingValue($syncedDateArray);
    }

    /**
     * Get Results and send Subscriber Batches.
     */
    public function handleSubscriberBatches()
    {
        $this->_sendSubscriberBatches();
    }

    /**
     * Get results of batch operations sent to MailChimp.
     *
     * @param  $magentoStoreId
     * @param  bool           $isEcommerceData
     * @throws Mage_Core_Exception
     */
    public function _getResults(
        $magentoStoreId,
        $isEcommerceData = true,
        $status = Ebizmarts_MailChimp_Helper_Data::BATCH_PENDING
    ) {
        $helper = $this->getHelper();
        $mailchimpStoreId = $helper->getMCStoreId($magentoStoreId);
        $collection = $this->getSyncBatchesModel()->getCollection()
            ->addFieldToFilter('status', array('eq' => $status));
        if ($isEcommerceData) {
            $collection->addFieldToFilter('store_id', array('eq' => $mailchimpStoreId));
            $enabled = $helper->isEcomSyncDataEnabled($magentoStoreId);
        } else {
            $collection->addFieldToFilter('store_id', array('eq' => $magentoStoreId));
            $enabled = $helper->isSubscriptionEnabled($magentoStoreId);
        }

        if ($enabled) {
            $helper->logBatchStatus('Get results from Mailchimp');
            foreach ($collection as $item) {
                try {
                    $batchId = $item->getBatchId();
                    $files = $this->getBatchResponse($batchId, $magentoStoreId);
                    if (!empty($files)) {
                        if (isset($files['error'])) {
                            $item->setStatus('error');
                            $item->save();
                            $helper->logBatchStatus('There was an error getting the result ');
                        } else {
                            $this->processEachResponseFile($files, $batchId, $mailchimpStoreId, $magentoStoreId);
                            $item->setStatus('completed');
                            $item->save();
                        }
                    }

                    $baseDir = $this->getMagentoBaseDir();
                    if ($this->batchDirExists($baseDir, $batchId)) {
                        $this->removeBatchDir($baseDir, $batchId);
                    }
                } catch (Exception $e) {
                    Mage::log("Error with a response: " . $e->getMessage());
                }
            }
        }
    }

    /**
     * Send Customers, Products, Orders, Carts to MailChimp store for given scope.
     * Return true if MailChimp store is reset in the process.
     *
     * @param  $magentoStoreId
     * @throws Mage_Core_Exception
     */
    public function _sendEcommerceBatch($magentoStoreId)
    {
        $helper = $this->getHelper();
        $mailchimpStoreId = $helper->getMCStoreId($magentoStoreId);
        try {
            $this->deleteUnsentItems();
            if ($helper->isEcomSyncDataEnabled($magentoStoreId)) {
                $helper->resetCountersSentPerBatch();
                $batchArray = array();
                //customer operations
                $helper->logBatchStatus('Generate Customers Payload');
                $apiCustomers = $this->getApiCustomers();
                $customersArray = $apiCustomers->createBatchJson($mailchimpStoreId, $magentoStoreId);
                $customerAmount = count($customersArray);
                $batchArray['operations'] = $customersArray;
                //product operations
                $helper->logBatchStatus('Generate Products Payload');
                $apiProducts = $this->getApiProducts();
                $productsArray = $apiProducts->createBatchJson($mailchimpStoreId, $magentoStoreId);
                $productAmount = count($productsArray);
                $batchArray['operations'] = array_merge($batchArray['operations'], $productsArray);
                //cart operations
                $helper->logBatchStatus('Generate Carts Payload');
                $apiCarts = $this->getApiCarts();
                $cartsArray = $apiCarts->createBatchJson($mailchimpStoreId, $magentoStoreId);
                $batchArray['operations'] = array_merge($batchArray['operations'], $cartsArray);
                //order operations
                $helper->logBatchStatus('Generate Orders Payload');
                $apiOrders = $this->getApiOrders();
                $ordersArray = $apiOrders->createBatchJson($mailchimpStoreId, $magentoStoreId);
                $orderAmount = count($ordersArray);
                $batchArray['operations'] = array_merge($batchArray['operations'], $ordersArray);
                if ($helper->getPromoConfig($magentoStoreId) == self::SEND_PROMO_ENABLED) {
                    //promo rule operations
                    $helper->logBatchStatus('Generate Promo Rules Payload');
                    $apiPromoRules = $this->getApiPromoRules();
                    $promoRulesArray = $apiPromoRules->createBatchJson($mailchimpStoreId, $magentoStoreId);
                    $batchArray['operations'] = array_merge($batchArray['operations'], $promoRulesArray);
                    //promo code operations
                    $helper->logBatchStatus('Generate Promo Codes Payload');
                    $apiPromoCodes = $this->getApiPromoCodes();
                    $promoCodesArray = $apiPromoCodes->createBatchJson($mailchimpStoreId, $magentoStoreId);
                    $batchArray['operations'] = array_merge($batchArray['operations'], $promoCodesArray);
                }

                //deleted product operations
                $deletedProductsArray = $apiProducts->createDeletedProductsBatchJson(
                    $mailchimpStoreId,
                    $magentoStoreId
                );
                $batchArray['operations'] = array_merge($batchArray['operations'], $deletedProductsArray);
                $batchJson = null;
                $batchResponse = null;

                try {
                    $this->_processBatchOperations($batchArray, $mailchimpStoreId, $magentoStoreId);
                    $this->_updateSyncingFlag(
                        $customerAmount, $productAmount, $orderAmount,
                        $mailchimpStoreId, $magentoStoreId
                    );
                } catch (Ebizmarts_MailChimp_Helper_Data_ApiKeyException $e) {
                    $helper->logError($e->getMessage());
                } catch (MailChimp_Error $e) {
                    $helper->logError($e->getFriendlyMessage());
                    if ($batchJson && !isset($batchResponse['id'])) {
                        $helper->logRequest($batchJson);
                    }
                } catch (Exception $e) {
                    $helper->logError($e->getMessage());
                    $helper->logError("Json encode fails");
                    $helper->logError($batchArray);
                }
            }
        } catch (MailChimp_Error $e) {
            $helper->logError($e->getFriendlyMessage());
        } catch (Exception $e) {
            $helper->logError($e->getMessage());
        }
    }

    /**
     * @param $batchArray
     * @param $mailchimpStoreId
     * @param $magentoStoreId
     * @throws Mage_Core_Exception
     * @throws MailChimp_Error
     */
    protected function _processBatchOperations($batchArray, $mailchimpStoreId, $magentoStoreId)
    {
        $helper = $this->getHelper();
        $mailchimpApi = $helper->getApi($magentoStoreId);

        if (!empty($batchArray['operations'])) {
            $batchJson = json_encode($batchArray);
            if (!$batchJson || $batchJson == '') {
                $helper->logRequest('An empty operation was detected');
            } else {
                $batchResponse = $mailchimpApi->getBatchOperation()->add($batchJson);
                $helper->logRequest($batchJson, $batchResponse['id']);
                //save batch id to db
                $batch = $this->getSyncBatchesModel();
                $batch->setStoreId($mailchimpStoreId)
                    ->setBatchId($batchResponse['id'])
                    ->setStatus($batchResponse['status']);
                $batch->save();
                $this->markItemsAsSent($batchResponse['id'], $mailchimpStoreId);
                $this->_showResumeEcommerce($batchResponse['id'], $magentoStoreId);
            }
        }
    }

    /**
     * @param $customerAmount
     * @param $productAmount
     * @param $orderAmount
     * @param $mailchimpStoreId
     * @param $magentoStoreId
     * @throws Mage_Core_Exception
     */
    protected function _updateSyncingFlag(
        $customerAmount,
        $productAmount,
        $orderAmount,
        $mailchimpStoreId,
        $magentoStoreId
    ) {
        $helper = $this->getHelper();
        $dateHelper = $this->getDateHelper();
        $itemAmount = ($customerAmount + $productAmount + $orderAmount);
        $syncingFlag = $helper->getMCIsSyncing($mailchimpStoreId, $magentoStoreId);

        if ($this->shouldFlagAsSyncing(
            $magentoStoreId,
            $syncingFlag,
            $itemAmount,
            $helper,
            $mailchimpStoreId
        )
        ) {
            //Set is syncing per scope in 1 until sync finishes.
            $configValue = array(
                array(Ebizmarts_MailChimp_Model_Config::GENERAL_MCISSYNCING . "_$mailchimpStoreId", 1)
            );
            $helper->saveMailchimpConfig($configValue, $magentoStoreId, 'stores');
        } else {
            if ($this->shouldFlagAsSynced($syncingFlag, $itemAmount)) {
                //Set is syncing per scope to a date because it is not sending any more items.
                $configValue = array(
                    array(
                        Ebizmarts_MailChimp_Model_Config::GENERAL_MCISSYNCING . "_$mailchimpStoreId",
                        $dateHelper->formatDate(null, 'Y-m-d H:i:s')
                    )
                );
                $helper->saveMailchimpConfig($configValue, $magentoStoreId, 'stores');
            }
        }
    }

    /**
     * @param $batchId
     */
    protected function deleteBatchItems($batchId)
    {
        $helper = $this->getHelper();
        $resource = $helper->getCoreResource();
        $connection = $resource->getConnection('core_write');
        $tableName = $resource->getTableName('mailchimp/ecommercesyncdata');
        $where = array("batch_id = '$batchId'");
        $connection->delete($tableName, $where);
    }

    protected function deleteUnsentItems()
    {
        $helper = $this->getHelper();
        $resource = $helper->getCoreResource();
        $connection = $resource->getConnection('core_write');
        $tableName = $resource->getTableName('mailchimp/ecommercesyncdata');
        $where = array("batch_id IS NULL AND mailchimp_sync_modified != 1");
        $connection->delete($tableName, $where);
    }

    public function ecommerceDeleteCallback($args)
    {
        $ecommerceData = Mage::getModel('mailchimp/ecommercesyncdata');
        $ecommerceData->setData($args['row']);
        $ecommerceData->delete();
    }

    protected function markItemsAsSent($batchResponseId, $mailchimpStoreId)
    {
        $helper = $this->getHelper();
        $dateHelper = $this->getDateHelper();

        $resource = $helper->getCoreResource();
        $connection = $resource->getConnection('core_write');
        $tableName = $resource->getTableName('mailchimp/ecommercesyncdata');
        $where = array("batch_id IS NULL AND mailchimp_store_id = ?" => $mailchimpStoreId);
        $connection->update(
            $tableName,
            array(
                'batch_id' => $batchResponseId,
                'mailchimp_sync_delta' => $dateHelper->formatDate(null, 'Y-m-d H:i:s')
            ),
            $where
        );
    }

    public function ecommerceSentCallback($args)
    {
        $ecommerceData = Mage::getModel('mailchimp/ecommercesyncdata');
        $ecommerceData->setData($args['row']); // map data to customer model
        $writeAdapter = Mage::getSingleton('core/resource')->getConnection('core_write');
        $insertData = array(
            'id' => $ecommerceData->getId(),
            'related_id' => $ecommerceData->getRelatedId(),
            'type' => $ecommerceData->getType(),
            'mailchimp_store_id' => $ecommerceData->getMailchimpStoreId(),
            'mailchimp_sync_error' => $ecommerceData->getMailchimpSyncError(),
            'mailchimp_sync_delta' => $ecommerceData->getMailchimpSyncDelta(),
            'mailchimp_sync_modified' => $ecommerceData->getMailchimpSyncModified(),
            'mailchimp_sync_deleted' => $ecommerceData->getMailchimpSyncDeleted(),
            'mailchimp_token' => $ecommerceData->getMailchimpToken(),
            'batch_id' => $ecommerceData->getBatchId()
        );
        $resource = Mage::getResourceModel('mailchimp/ecommercesyncdata');
        $writeAdapter->insertOnDuplicate(
            $resource->getMainTable(),
            $insertData,
            array(
                'id',
                'related_id',
                'type',
                'mailchimp_store_id',
                'mailchimp_sync_error',
                'mailchimp_sync_delta',
                'mailchimp_sync_modified',
                'mailchimp_sync_deleted',
                'mailchimp_token',
                'batch_id'
            )
        );
    }

    /**
     * Send Subscribers batch on each store view, return array of batches responses.
     *
     * @return array
     */
    protected function _sendSubscriberBatches()
    {
        $helper = $this->getHelper();

        $subscriberLimit = $helper->getSubscriberAmountLimit();
        $stores = $this->getStores();
        $batchResponses = array();
        foreach ($stores as $store) {
            $storeId = $store->getId();
            $this->_getResults($storeId, false);
            if ($subscriberLimit > 0) {
                list($batchResponse, $subscriberLimit) = $this->sendStoreSubscriberBatch($storeId, $subscriberLimit);
                if ($batchResponse) {
                    $batchResponses[] = $batchResponse;
                }
            } else {
                break;
            }
        }

        $this->_getResults(0, false);
        if ($subscriberLimit > 0) {
            list($batchResponse, $subscriberLimit) = $this->sendStoreSubscriberBatch(0, $subscriberLimit);
            if ($batchResponse) {
                $batchResponses[] = $batchResponse;
            }
        }

        return $batchResponses;
    }

    /**
     * Send Subscribers batch on particular store view, return batch response.
     *
     * @param  $storeId
     * @param  $limit
     * @return array|null
     */
    public function sendStoreSubscriberBatch($storeId, $limit)
    {
        $helper = $this->getHelper();
        try {
            if ($helper->isSubscriptionEnabled($storeId)) {
                $helper->resetCountersSubscribers();

                $listId = $helper->getGeneralList($storeId);

                $batchArray = array();

                //subscriber operations
                $subscribersArray = $this->getApiSubscribers()->createBatchJson($listId, $storeId, $limit);
                $limit -= count($subscribersArray);

                $batchArray['operations'] = $subscribersArray;

                if (!empty($batchArray['operations'])) {
                    $batchJson = json_encode($batchArray);
                    if (!$batchJson || $batchJson == '') {
                        $helper->logRequest('An empty operation was detected');
                    } else {
                        try {
                            $mailchimpApi = $helper->getApi($storeId);
                            $batchResponse = $mailchimpApi->getBatchOperation()->add($batchJson);
                            $helper->logRequest($batchJson, $batchResponse['id']);

                            //save batch id to db
                            $batch = $this->getSyncBatchesModel();
                            $batch->setStoreId($storeId)
                                ->setBatchId($batchResponse['id'])
                                ->setStatus($batchResponse['status']);
                            $batch->save();
                            $this->_showResumeSubscriber($batchResponse['id'], $storeId);
                            return array($batchResponse, $limit);
                        } catch (Ebizmarts_MailChimp_Helper_Data_ApiKeyException $e) {
                            $helper->logError($e->getMessage());
                        } catch (MailChimp_Error $e) {
                            $helper->logRequest($batchJson);
                            $helper->logError($e->getFriendlyMessage());
                        }
                    }
                }
            }
        } catch (MailChimp_Error $e) {
            $helper->logError($e->getFriendlyMessage());
        } catch (Exception $e) {
            $helper->logError($e->getMessage());
        }

        return array(null, $limit);
    }

    /**
     * @param $batchId
     * @param $magentoStoreId
     * @return array
     */
    public function getBatchResponse($batchId, $magentoStoreId)
    {
        $helper = $this->getHelper();
        $files = array();
        try {
            $baseDir = $this->getMagentoBaseDir();
            $api = $helper->getApi($magentoStoreId);
            if ($api) {
                // check the status of the job
                $response = $api->batchOperation->status($batchId);
                if (isset($response['status']) && $response['status'] == 'finished') {
                    // get the tar.gz file with the results
                    $fileUrl = urldecode($response['response_body_url']);
                    $fileName = $baseDir . DS . 'var' . DS . 'mailchimp' . DS . $batchId . '.tar.gz';
                    $fd = fopen($fileName, 'w');

                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $fileUrl);
                    curl_setopt($ch, CURLOPT_FILE, $fd);
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // this will follow redirects
                    $r = curl_exec($ch);

                    curl_close($ch);
                    fclose($fd);
                    mkdir(
                        $baseDir . DS . 'var' . DS . 'mailchimp' . DS . $batchId,
                        0750,
                        true
                    );
                    $archive = new Mage_Archive();
                    if (file_exists($fileName)) {
                        $files = $this->_unpackBatchFile($files, $batchId, $archive, $fileName, $baseDir);
                    }
                }
            }
        } catch (Ebizmarts_MailChimp_Helper_Data_ApiKeyException $e) {
            $helper->logError($e->getMessage());
            $files['error'] = $e->getMessage();
        } catch (MailChimp_Error $e) {
            $this->deleteBatchItems($batchId);
            $files['error'] = $e->getFriendlyMessage();
            $helper->logError($e->getFriendlyMessage());
        } catch (Exception $e) {
            $files['error'] = $e->getMessage();
            $helper->logError($e->getMessage());
        }

        return $files;
    }

    /**
     * @param $files
     * @param $batchId
     * @param $archive
     * @param $fileName
     * @param $baseDir
     * @return array
     */
    protected function _unpackBatchFile($files, $batchId, $archive, $fileName, $baseDir)
    {
        $archive->unpack($fileName, $baseDir . DS . 'var' . DS . 'mailchimp' . DS . $batchId);
        $archive->unpack(
            $baseDir . DS . 'var' . DS . 'mailchimp' . DS . $batchId . '/' . $batchId . '.tar',
            $baseDir . DS . 'var' . DS . 'mailchimp' . DS . $batchId
        );
        $dir = scandir($baseDir . DS . 'var' . DS . 'mailchimp' . DS . $batchId);
        foreach ($dir as $d) {
            $name = pathinfo($d);
            if ($name['extension'] == 'json') {
                $files[] = $baseDir . DS . 'var' . DS . 'mailchimp' . DS . $batchId . '/' . $d;
            }
        }

        unlink(
            $baseDir . DS . 'var' . DS . 'mailchimp' . DS . $batchId . '/' . $batchId . '.tar'
        );
        unlink($fileName);

        return $files;
    }

    /**
     * @param $files
     * @param $batchId
     * @param $mailchimpStoreId
     * @param $magentoStoreId
     * @throws Mage_Core_Exception
     */
    protected function processEachResponseFile($files, $batchId, $mailchimpStoreId, $magentoStoreId)
    {
        $helper = $this->getHelper();
        $helper->resetCountersDataSentToMailchimp();

        foreach ($files as $file) {
            $items = json_decode(file_get_contents($file));

            if ($items !== false) {
                foreach ($items as $item) {
                    $line = explode('_', $item->operation_id);
                    $store = explode('-', $line[0]);
                    $type = $line[1];
                    $id = $line[3];

                    if ($item->status_code != 200) {
                        $mailchimpErrors = Mage::getModel('mailchimp/mailchimperrors');

                        //parse error
                        $response = json_decode($item->response);
                        $errorDetails = $this->_processFileErrors($response);

                        if (strstr($errorDetails, 'already exists')) {
                            $this->setItemAsModified($helper, $mailchimpStoreId, $id, $type);
                            $helper->modifyCounterDataSentToMailchimp($type);
                            continue;
                        }

                        $error = $this->_getError($type, $mailchimpStoreId, $id, $response);
                        $this->saveSyncData(
                            $id,
                            $type,
                            $mailchimpStoreId,
                            null,
                            $error,
                            0,
                            null,
                            null,
                            null,
                            true
                        );

                        $mailchimpErrors->setType($response->type);
                        $mailchimpErrors->setTitle($response->title);
                        $mailchimpErrors->setStatus($item->status_code);
                        $mailchimpErrors->setErrors($errorDetails);
                        $mailchimpErrors->setRegtype($type);
                        $mailchimpErrors->setOriginalId($id);
                        $mailchimpErrors->setBatchId($batchId);
                        $mailchimpErrors->setStoreId($store[1]);

                        if ($type != Ebizmarts_MailChimp_Model_Config::IS_SUBSCRIBER) {
                            $mailchimpErrors->setMailchimpStoreId($mailchimpStoreId);
                        }

                        $mailchimpErrors->save();
                        $helper->modifyCounterDataSentToMailchimp($type, true);
                        $helper->logError($error);
                    } else {
                        $syncDataItem = $this->getDataProduct($helper, $mailchimpStoreId, $id, $type);

                        if (!$syncDataItem->getMailchimpSyncModified()) {
                            $syncModified = $this->enableMergeFieldsSending($type, $syncDataItem);

                            $this->saveSyncData(
                                $id,
                                $type,
                                $mailchimpStoreId,
                                null,
                                '',
                                $syncModified,
                                null,
                                null,
                                1,
                                true
                            );
                            $helper->modifyCounterDataSentToMailchimp($type);
                        }
                    }
                }
            }

            unlink($file);
        }

        $this->_showResumeDataSentToMailchimp($magentoStoreId);
    }

    /**
     * @param $type
     * @param $mailchimpStoreId
     * @param $id
     * @param $response
     * @return string
     */
    protected function _getError($type, $mailchimpStoreId, $id, $response)
    {
        $error = $response->title . " : " . $response->detail;
        $helper = $this->getHelper();

        if ($type == Ebizmarts_MailChimp_Model_Config::IS_PRODUCT) {
            $dataProduct = $this->getDataProduct($helper, $mailchimpStoreId, $id, $type);
            $isProductDisabledInMagento = Ebizmarts_MailChimp_Model_Api_Products::PRODUCT_DISABLED_IN_MAGENTO;

            if ($dataProduct->getMailchimpSyncDeleted()
                || $dataProduct->getMailchimpSyncError() == $isProductDisabledInMagento
            ) {
                $error = $isProductDisabledInMagento;
            }
        }

        return $error;
    }

    /**
     * @param $response
     * @return string
     */
    protected function _processFileErrors($response)
    {
        $errorDetails = "";

        if (!empty($response->errors)) {
            foreach ($response->errors as $error) {
                if (isset($error->field) && isset($error->message)) {
                    $errorDetails .= $errorDetails != "" ? " / " : "";
                    $errorDetails .= $error->field . " : " . $error->message;
                }
            }
        }

        if ($errorDetails == "") {
            $errorDetails = $response->detail;
        }

        return $errorDetails;
    }

    /**
     * Handle batch for order id replacement with the increment id in MailChimp.
     *
     * @param $initialTime
     * @param $magentoStoreId
     */
    public function replaceAllOrders($initialTime, $magentoStoreId)
    {
        $helper = $this->getHelper();
        try {
            $this->_getResults($magentoStoreId);

            //handle order replacement
            $mailchimpStoreId = $helper->getMCStoreId($magentoStoreId);

            $batchArray['operations'] = Mage::getModel('mailchimp/api_orders')->replaceAllOrdersBatch(
                $initialTime,
                $mailchimpStoreId,
                $magentoStoreId
            );
            try {
                /**
                 * @var $mailchimpApi Ebizmarts_MailChimp
                 */
                $mailchimpApi = $helper->getApi($magentoStoreId);

                if (!empty($batchArray['operations'])) {
                    $batchJson = json_encode($batchArray);
                    if (!$batchJson || $batchJson == '') {
                        $helper->logRequest('An empty operation was detected');
                    } else {
                        $batchResponse = $mailchimpApi->batchOperation->add($batchJson);
                        $helper->logRequest($batchJson, $batchResponse['id']);
                        //save batch id to db
                        $batch = $this->getSyncBatchesModel();
                        $batch->setStoreId($mailchimpStoreId)
                            ->setBatchId($batchResponse['id'])
                            ->setStatus($batchResponse['status']);
                        $batch->save();
                    }
                }
            } catch (Ebizmarts_MailChimp_Helper_Data_ApiKeyException $e) {
                $helper->logError($e->getMessage());
            } catch (MailChimp_Error $e) {
                $helper->logError($e->getFriendlyMessage());
            } catch (Exception $e) {
                $helper->logError($e->getMessage());
                $helper->logError("Json encode fails");
                $helper->logError($batchArray);
            }
        } catch (MailChimp_Error $e) {
            $helper->logError($e->getFriendlyMessage());
        } catch (Exception $e) {
            $helper->logError($e->getMessage());
        }
    }

    /**
     * @param $itemId
     * @param $itemType
     * @param $mailchimpStoreId
     * @param null             $syncDelta
     * @param null             $syncError
     * @param int              $syncModified
     * @param null             $syncDeleted
     * @param null             $token
     * @param null             $syncedFlag
     * @param bool             $saveOnlyIfexists
     */
    protected function saveSyncData(
        $itemId,
        $itemType,
        $mailchimpStoreId,
        $syncDelta = null,
        $syncError = null,
        $syncModified = 0,
        $syncDeleted = null,
        $token = null,
        $syncedFlag = null,
        $saveOnlyIfexists = false
    ) {
        $helper = $this->getHelper();
        if ($itemType == Ebizmarts_MailChimp_Model_Config::IS_SUBSCRIBER) {
            $helper->updateSubscriberSyndData($itemId, $syncDelta, $syncError, 0, null);
        } else {
            $helper->saveEcommerceSyncData(
                $itemId,
                $itemType,
                $mailchimpStoreId,
                $syncDelta,
                $syncError,
                $syncModified,
                $syncDeleted,
                $token,
                $syncedFlag,
                $saveOnlyIfexists,
                null,
                false
            );
        }
    }

    /**
     * @param $storeId
     * @param $syncedDateArray
     * @return mixed
     */
    protected function addSyncValueToArray($storeId, $syncedDateArray)
    {
        $helper = $this->getHelper();
        $ecomEnabled = $helper->isEcomSyncDataEnabled($storeId);

        if ($ecomEnabled) {
            $mailchimpStoreId = $helper->getMCStoreId($storeId);
            $syncedDate = $helper->getMCIsSyncing($mailchimpStoreId, $storeId);

            // Check if $syncedDate is in date format to support previous versions.
            if (isset($syncedDateArray[$mailchimpStoreId]) && $syncedDateArray[$mailchimpStoreId]) {
                if ($helper->validateDate($syncedDate)) {
                    if ($syncedDate > $syncedDateArray[$mailchimpStoreId]) {
                        $syncedDateArray[$mailchimpStoreId] = array($storeId => $syncedDate);
                    }
                } elseif ((int)$syncedDate === 1) {
                    $syncedDateArray[$mailchimpStoreId] = array($storeId => false);
                }
            } else {
                if ($helper->validateDate($syncedDate)) {
                    $syncedDateArray[$mailchimpStoreId] = array($storeId => $syncedDate);
                } else {
                    if ((int)$syncedDate === 1 || $syncedDate === null) {
                        $syncedDateArray[$mailchimpStoreId] = array($storeId => false);
                    } elseif (!isset($syncedDateArray[$mailchimpStoreId])) {
                        $syncedDateArray[$mailchimpStoreId] = array($storeId => true);
                    }
                }
            }
        }

        return $syncedDateArray;
    }

    /**
     * @param $syncedDateArray
     * @throws Mage_Core_Exception
     */
    public function handleSyncingValue($syncedDateArray)
    {
        $helper = $this->getHelper();
        foreach ($syncedDateArray as $mailchimpStoreId => $val) {
            $magentoStoreId = key($val);
            $date = $val[$magentoStoreId];
            $ecomEnabled = $helper->isEcomSyncDataEnabled($magentoStoreId);
            if ($ecomEnabled && $date) {
                try {
                    $api = $helper->getApi($magentoStoreId);
                    $isSyncingDate = $helper->getDateSyncFinishByMailChimpStoreId($mailchimpStoreId);
                    if (!$isSyncingDate && $mailchimpStoreId) {
                        $this->getApiStores()->editIsSyncing($api, false, $mailchimpStoreId);
                        $config = array(
                            array(
                                Ebizmarts_MailChimp_Model_Config::ECOMMERCE_SYNC_DATE . "_$mailchimpStoreId",
                                $date
                            )
                        );
                        $helper->saveMailchimpConfig($config, 0, 'default');
                    }
                } catch (Ebizmarts_MailChimp_Helper_Data_ApiKeyException $e) {
                    $helper->logError($e->getMessage());
                } catch (MailChimp_Error $e) {
                    $helper->logError($e->getFriendlyMessage());
                } catch (Exception $e) {
                    $helper->logError($e->getMessage());
                }
            }
        }
    }

    /**
     * @param $helper
     * @param $mailchimpStoreId
     * @param $id
     * @param $type
     */
    protected function setItemAsModified($helper, $mailchimpStoreId, $id, $type)
    {
        $isMarkedAsDeleted = null;

        if ($type == Ebizmarts_MailChimp_Model_Config::IS_PRODUCT) {
            $dataProduct = $this->getDataProduct($helper, $mailchimpStoreId, $id, $type);
            $isMarkedAsDeleted = $dataProduct->getMailchimpSyncDeleted();
            $isProductDisabledInMagento = Ebizmarts_MailChimp_Model_Api_Products::PRODUCT_DISABLED_IN_MAGENTO;

            if (!$isMarkedAsDeleted || $dataProduct->getMailchimpSyncError() != $isProductDisabledInMagento) {
                $this->saveSyncData(
                    $id,
                    $type,
                    $mailchimpStoreId,
                    null,
                    null,
                    1,
                    0,
                    null,
                    1,
                    true
                );
            } else {
                $this->saveSyncData(
                    $id,
                    $type,
                    $mailchimpStoreId,
                    null,
                    $isProductDisabledInMagento,
                    0,
                    1,
                    null,
                    0,
                    true
                );
            }
        } else {
            $this->saveSyncData(
                $id,
                $type,
                $mailchimpStoreId,
                null,
                null,
                1,
                0,
                null,
                1,
                true
            );
        }
    }

    /**
     * @param $magentoStoreId
     * @param $syncingFlag
     * @param $itemAmount
     * @param $helper
     * @param $mailchimpStoreId
     * @return bool
     */
    protected function shouldFlagAsSyncing($magentoStoreId, $syncingFlag, $itemAmount, $helper, $mailchimpStoreId)
    {
        return $syncingFlag === null
            && $itemAmount !== 0
            || $helper->validateDate($syncingFlag)
            && $syncingFlag < $helper->getEcommMinSyncDateFlag($mailchimpStoreId, $magentoStoreId);
    }

    /**
     * @param $syncingFlag
     * @param $itemAmount
     * @return bool
     */
    protected function shouldFlagAsSynced($syncingFlag, $itemAmount)
    {
        return ($syncingFlag === '1' || $syncingFlag === null) && $itemAmount === 0;
    }

    /**
     * @param $helper
     * @param $mailchimpStoreId
     * @param $id
     * @param $type
     * @return Varien_Object
     */
    protected function getDataProduct($helper, $mailchimpStoreId, $id, $type)
    {
        return $helper->getEcommerceSyncDataItem($id, $type, $mailchimpStoreId);
    }

    /**
     * @param $batchId
     * @param $storeId
     * @throws Mage_Core_Exception
     */
    protected function _showResumeEcommerce($batchId, $storeId)
    {
        $helper = $this->getHelper();
        if (!empty($helper->getCountersSentPerBatch()) || $helper->getCountersSentPerBatch() != null) {
            $helper->logBatchStatus("Sent batch $batchId for store $storeId");
            $helper->logBatchQuantity($helper->getCountersSentPerBatch());
        } else {
            $helper->logBatchStatus("Nothing to sync for store $storeId");
        }
    }

    /**
     * @param $batchId
     * @param $storeId
     * @throws Mage_Core_Exception
     */
    protected function _showResumeSubscriber($batchId, $storeId)
    {
        $helper = $this->getHelper();
        if (!empty($helper->getCountersSubscribers()) || $helper->getCountersSubscribers() != null) {
            $helper->logBatchStatus("Sent batch $batchId for store $storeId");
            $helper->logBatchQuantity($helper->getCountersSubscribers());
        } else {
            $helper->logBatchStatus("Nothing to sync for store $storeId");
        }
    }

    /**
     * @param $storeId
     * @throws Mage_Core_Exception
     */
    protected function _showResumeDataSentToMailchimp($storeId)
    {
        $helper = $this->getHelper();
        if (!empty($helper->getCountersDataSentToMailchimp()) || $helper->getCountersDataSentToMailchimp() != null) {
            $helper->logBatchStatus("Processed data sent to Mailchimp for store $storeId");
            $counter = $helper->getCountersDataSentToMailchimp();
            $helper->logBatchQuantity($counter);
            if ($this->isSetAnyCounterSubscriberOrEcommerceNotSent($counter)) {
                if ($helper->isErrorLogEnabled()) {
                    $helper->logBatchStatus(
                        'Please check Mailchimp Errors grid or MailChimp_Errors.log for more details.'
                    );
                } else {
                    $helper->logBatchStatus(
                        'Please check Mailchimp Errors grid and enable MailChimp_Errors.log for more details.'
                    );
                }
            }
        } else {
            $helper->logBatchStatus("Nothing was processed for store $storeId");
        }
    }

    /**
     * @param $counter
     * @return bool
     */
    protected function isSetAnyCounterSubscriberOrEcommerceNotSent($counter)
    {
        return isset($counter['SUB']['NOT SENT'])
            || isset($counter['CUS']['NOT SENT'])
            || isset($counter['ORD']['NOT SENT'])
            || isset($counter['PRO']['NOT SENT'])
            || isset($counter['QUO']['NOT SENT']);
    }

    /**
     * @param Varien_Object $syncDataItem
     * @return bool
     */
    protected function isFirstArrival(Varien_Object $syncDataItem)
    {
        return (int)$syncDataItem->getMailchimpSyncedFlag() !== 1;
    }

    /**
     * @param $type
     * @param Varien_Object $syncDataItem
     * @return int
     */
    protected function enableMergeFieldsSending($type, Varien_Object $syncDataItem)
    {
        $syncModified = 0 ;
        if ($type == Ebizmarts_MailChimp_Model_Config::IS_CUSTOMER
            && $this->isFirstArrival($syncDataItem)) {
            $syncModified = 1;
        }

        return $syncModified;
    }
}
