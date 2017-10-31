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
    /**
     * @var Ebizmarts_MailChimp_Helper_Data
     */
    private $mailchimpHelper;

    /**
     * @var Ebizmarts_MailChimp_Model_Api_Customers
     */
    private $apiCustomers;

    /**
     * @var Ebizmarts_MailChimp_Model_Api_Products
     */
    private $apiProducts;

    /**
     * @var Ebizmarts_MailChimp_Model_Api_Carts
     */
    private $apiCarts;

    /**
     * @var Ebizmarts_MailChimp_Model_Api_Orders
     */
    private $apiOrders;

    /**
     * @var Ebizmarts_MailChimp_Model_Api_PromoRules
     */
    private $apiPromoRules;

    /**
     * @var Ebizmarts_MailChimp_Model_Api_PromoCodes
     */
    private $apiPromoCodes;

    public function __construct()
    {
        $this->mailchimpHelper = Mage::helper('mailchimp');
        $this->apiCustomers = Mage::getModel('mailchimp/api_customers');
        $this->apiProducts = Mage::getModel('mailchimp/api_products');
        $this->apiCarts = Mage::getModel('mailchimp/api_carts');
        $this->apiOrders = Mage::getModel('mailchimp/api_orders');
        $this->apiPromoRules = Mage::getModel('mailchimp/api_promoRules');
        $this->apiPromoCodes = Mage::getModel('mailchimp/api_promoCodes');
    }

    /**
     * @return Ebizmarts_MailChimp_Helper_Data|Mage_Core_Helper_Abstract
     */
    protected function getHelper()
    {
        return $this->mailchimpHelper;
    }

    /**
     * @return Ebizmarts_MailChimp_Model_Api_Customers|false|Mage_Core_Model_Abstract
     */
    protected function getApiCustomers()
    {
        return $this->apiCustomers;
    }

    /**
     * @return Ebizmarts_MailChimp_Model_Api_Products|false|Mage_Core_Model_Abstract
     */
    public function getApiProducts()
    {
        return $this->apiProducts;
    }

    /**
     * @return Ebizmarts_MailChimp_Model_Api_Carts|false|Mage_Core_Model_Abstract
     */
    public function getApiCarts()
    {
        return $this->apiCarts;
    }

    /**
     * @return Ebizmarts_MailChimp_Model_Api_Orders|false|Mage_Core_Model_Abstract
     */
    public function getApiOrders()
    {
        return $this->apiOrders;
    }

    /**
     * @return Ebizmarts_MailChimp_Model_Api_PromoRules|false|Mage_Core_Model_Abstract
     */
    public function getApiPromoRules()
    {
        return $this->apiPromoRules;
    }

    /**
     * @return Ebizmarts_MailChimp_Model_Api_PromoCodes|false|Mage_Core_Model_Abstract
     */
    public function getApiPromoCodes()
    {
        return $this->apiPromoCodes;
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
                $this->_getResults($storeId);
                $this->_sendEcommerceBatch($storeId);
            }
        }
        $helper->handleResendDataAfter();

        $syncedDateArray = array();
        foreach ($stores as $store) {
            $storeId = $store->getId();
            $this->handleResetIfNecessary($storeId);
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
     * @param $magentoStoreId
     * @param bool $isEcommerceData
     */
    protected function _getResults($magentoStoreId, $isEcommerceData = true)
    {
        $mailchimpStoreId = $this->getHelper()->getMCStoreId($magentoStoreId);
        $collection = Mage::getResourceModel('mailchimp/synchbatches_collection')
            ->addFieldToFilter('status', array('eq' => 'pending'));
        if ($isEcommerceData) {
            $collection->addFieldToFilter('store_id', array('eq' => $mailchimpStoreId));
        } else {
            $collection->addFieldToFilter('store_id', array('eq' => $magentoStoreId));
        }

        foreach ($collection as $item) {
            try {
                $files = $this->getBatchResponse($item->getBatchId(), $magentoStoreId);
                if (count($files)) {
                    if (isset($files['error'])) {
                        $item->setStatus('error');
                        $item->save();
                    } else {
                        $this->processEachResponseFile($files, $item->getBatchId(), $mailchimpStoreId);
                        $item->setStatus('completed');
                        $item->save();
                    }
                }

                $baseDir = Mage::getBaseDir();
                if (is_dir($baseDir . DS . 'var' . DS . 'mailchimp' . DS . $item->getBatchId())) {
                    rmdir($baseDir . DS . 'var' . DS . 'mailchimp' . DS . $item->getBatchId());
                }
            } catch (Exception $e) {
                Mage::log("Error with a response: " . $e->getMessage());
            }
        }
    }

    /**
     * Send Customers, Products, Orders, Carts to MailChimp store for given scope.
     * Return true if MailChimp store is reseted in the process.
     *
     * @param  $magentoStoreId
     * @return null
     */
    public function _sendEcommerceBatch($magentoStoreId)
    {
        $helper = $this->getHelper();
        $mailchimpStoreId = $helper->getMCStoreId($magentoStoreId);
        try {
            $this->deleteUnsentItems();
            if ($helper->isMailChimpEnabled($magentoStoreId) && $helper->isEcomSyncDataEnabled($magentoStoreId)) {
                $batchArray = array();
                //customer operations
                $apiCustomers = $this->getApiCustomers();
                $customersArray = $apiCustomers->createBatchJson($mailchimpStoreId, $magentoStoreId);
                $customerAmount = count($customersArray);
                $batchArray['operations'] = $customersArray;
                //product operations
                $apiProducts = $this->getApiProducts();
                $productsArray = $apiProducts->createBatchJson($mailchimpStoreId, $magentoStoreId);
                $productAmount = count($productsArray);
                $batchArray['operations'] = array_merge($batchArray['operations'], $productsArray);
                //order operations
                $apiCarts = $this->getApiCarts();
                $cartsArray = $apiCarts->createBatchJson($mailchimpStoreId, $magentoStoreId);
                $batchArray['operations'] = array_merge($batchArray['operations'], $cartsArray);
                $apiOrders = $this->getApiOrders();
                $ordersArray = $apiOrders->createBatchJson($mailchimpStoreId, $magentoStoreId);
                $orderAmount = count($ordersArray);
                $batchArray['operations'] = array_merge($batchArray['operations'], $ordersArray);
                $apiPromoRules = $this->getApiPromoRules();
                $promoRulesArray = $apiPromoRules->createBatchJson($mailchimpStoreId, $magentoStoreId);
                $batchArray['operations'] = array_merge($batchArray['operations'], $promoRulesArray);
                $apiPromoCodes = $this->getApiPromoCodes();
                $promoCodesArray = $apiPromoCodes->createBatchJson($mailchimpStoreId, $magentoStoreId);
                $batchArray['operations'] = array_merge($batchArray['operations'], $promoCodesArray);
                $batchJson = null;
                $batchResponse = null;

                try {
                    /**
                     * @var $mailchimpApi \Ebizmarts_MailChimp
                     */
                    $mailchimpApi = $helper->getApi($magentoStoreId);
                    if (!empty($batchArray['operations'])) {
                        $batchJson = json_encode($batchArray);
                        if (!$batchJson || $batchJson == '') {
                            $helper->logRequest('An empty operation was detected', $magentoStoreId);
                        } else {
                            if (!$helper->getIsReseted($magentoStoreId)) {
                                $batchResponse = $mailchimpApi->batchOperation->add($batchJson);
                                $helper->logRequest($batchJson, $magentoStoreId, $batchResponse['id']);
                                //save batch id to db
                                $batch = Mage::getModel('mailchimp/synchbatches');
                                $batch->setStoreId($mailchimpStoreId)
                                    ->setBatchId($batchResponse['id'])
                                    ->setStatus($batchResponse['status']);
                                $batch->save();
                                $this->markItemsAsSent($batchResponse['id'], $mailchimpStoreId);
                            }
                        }
                    }

                    $itemAmount = ($customerAmount + $productAmount + $orderAmount);
                    $syncingFlag = $helper->getMCIsSyncing($magentoStoreId);
                    if ($helper->validateDate($syncingFlag) && $syncingFlag < $helper->getEcommMinSyncDateFlag($magentoStoreId)) {
                        $configValue = array(array(Ebizmarts_MailChimp_Model_Config::GENERAL_MCISSYNCING, 1));
                        $helper->saveMailchimpConfig($configValue, $magentoStoreId, 'stores');
                    } else {
                        if ($syncingFlag == 1 && $itemAmount == 0) {
                            $configValue = array(array(Ebizmarts_MailChimp_Model_Config::GENERAL_MCISSYNCING, date('Y-m-d H:i:s')));
                            $helper->saveMailchimpConfig($configValue, $magentoStoreId, 'stores');
                        }
                    }
                } catch (MailChimp_Error $e) {
                    $helper->logError($e->getFriendlyMessage(), $magentoStoreId);
                    if ($batchJson && !isset($batchResponse['id'])) {
                        $helper->logRequest($batchJson, $magentoStoreId);
                    }
                } catch (Exception $e) {
                    $helper->logError($e->getMessage(), $magentoStoreId);
                    $helper->logError("Json encode fails", $magentoStoreId);
                    $helper->logError($batchArray, $magentoStoreId);
                }
            }
        } catch (MailChimp_Error $e) {
            $helper->logError($e->getFriendlyMessage(), $magentoStoreId);
        } catch (Exception $e) {
            $helper->logError($e->getMessage(), $magentoStoreId);
        }
    }

    protected function deleteUnsentItems()
    {
        $ecommerceDataCollection = Mage::getResourceModel('mailchimp/ecommercesyncdata_collection')
            ->addFieldToFilter('batch_id', array('null' => true));
        Mage::getSingleton('core/resource_iterator')->walk($ecommerceDataCollection->getSelect(), array(array($this, 'ecommerceDeleteCallback')));
    }

    public function ecommerceDeleteCallback($args)
    {
        $ecommerceData = Mage::getModel('mailchimp/ecommercesyncdata');
        $ecommerceData->setData($args['row']);
        $ecommerceData->delete();
    }

    protected function markItemsAsSent($batchResponseId, $mailchimpStoreId)
    {
        $ecommerceDataCollection = Mage::getResourceModel('mailchimp/ecommercesyncdata_collection')
            ->addFieldToFilter('batch_id', array('null' => true))
            ->addFieldToFilter('mailchimp_store_id', array('eq' => $mailchimpStoreId));
        Mage::getSingleton('core/resource_iterator')->walk($ecommerceDataCollection->getSelect(), array(array($this, 'ecommerceSentCallback')));
        foreach ($ecommerceDataCollection as $ecommerceData) {
            $ecommerceData->setBatchId($batchResponseId)
                ->save();
        }
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
            array('id', 'related_id', 'type', 'mailchimp_store_id', 'mailchimp_sync_error', 'mailchimp_sync_delta', 'mailchimp_sync_modified', 'mailchimp_sync_deleted', 'mailchimp_token', 'batch_id')
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
            $subscribersArray = array();
            if ($helper->isMailChimpEnabled($storeId)) {
                $listId = $helper->getGeneralList($storeId);

                $batchArray = array();

                //subscriber operations
                $subscribersArray = Mage::getModel('mailchimp/api_subscribers')->createBatchJson($listId, $storeId, $limit);
                $limit -= count($subscribersArray);
            }

            $batchArray['operations'] = $subscribersArray;

            if (!empty($batchArray['operations'])) {
                $batchJson = json_encode($batchArray);
                if (!$batchJson || $batchJson == '') {
                    $helper->logRequest('An empty operation was detected', $storeId);
                } else {
                    try {
                        $mailchimpApi = $helper->getApi($storeId);
                        $batchResponse = $mailchimpApi->batchOperation->add($batchJson);
                        $helper->logRequest($batchJson, $storeId, $batchResponse['id']);

                        //save batch id to db
                        $batch = Mage::getModel('mailchimp/synchbatches');
                        $batch->setStoreId($storeId)
                            ->setBatchId($batchResponse['id'])
                            ->setStatus($batchResponse['status']);
                        $batch->save();
                        return array($batchResponse, $limit);
                    } catch (MailChimp_Error $e) {
                        $helper->logRequest($batchJson, $storeId);
                    }
                }
            }
        } catch (MailChimp_Error $e) {
            $helper->logError($e->getFriendlyMessage(), $storeId);
        } catch (Exception $e) {
            $helper->logError($e->getMessage(), $storeId);
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
        $files = array();
        try {
            $baseDir = Mage::getBaseDir();
            $api = $this->getHelper()->getApi($magentoStoreId);
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
                    mkdir($baseDir . DS . 'var' . DS . 'mailchimp' . DS . $batchId, 0750, true);
                    $archive = new Mage_Archive();
                    if (file_exists($fileName)) {
                        $archive->unpack($fileName, $baseDir . DS . 'var' . DS . 'mailchimp' . DS . $batchId);
                        $archive->unpack($baseDir . DS . 'var' . DS . 'mailchimp' . DS . $batchId . '/' . $batchId . '.tar', $baseDir . DS . 'var' . DS . 'mailchimp' . DS . $batchId);
                        $dir = scandir($baseDir . DS . 'var' . DS . 'mailchimp' . DS . $batchId);
                        foreach ($dir as $d) {
                            $name = pathinfo($d);
                            if ($name['extension'] == 'json') {
                                $files[] = $baseDir . DS . 'var' . DS . 'mailchimp' . DS . $batchId . '/' . $d;
                            }
                        }
                        unlink($baseDir . DS . 'var' . DS . 'mailchimp' . DS . $batchId . '/' . $batchId . '.tar');
                        unlink($fileName);
                    }
                }
            }
        } catch (MailChimp_Error $e) {
            $files['error'] = $e->getFriendlyMessage();
            $this->getHelper()->logError($e->getFriendlyMessage(), $magentoStoreId);
        } catch (Exception $e) {
            $this->getHelper()->logError($e->getMessage(), $magentoStoreId);
        }

        return $files;
    }

    /**
     * @param $files
     * @param $batchId
     * @param $mailchimpStoreId
     */
    protected function processEachResponseFile($files, $batchId, $mailchimpStoreId)
    {
        foreach ($files as $file) {
            $items = json_decode(file_get_contents($file));
            foreach ($items as $item) {
                if ($item->status_code != 200) {
                    $line = explode('_', $item->operation_id);
                    $store = explode('-', $line[0]);
                    $type = $line[1];
                    $id = $line[3];
                    if ($type == Ebizmarts_MailChimp_Model_Config::IS_ORDER) {
                        $order = Mage::getModel('sales/order')->load($id);
                        $id = $order->getEntityId();
                    }

                    $mailchimpErrors = Mage::getModel('mailchimp/mailchimperrors');

                    //parse error
                    $response = json_decode($item->response);
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

                    if (strstr($errorDetails, 'already exists in the account')) {
                        $this->saveSyncData($id, $type, $mailchimpStoreId, null, null, 1, null, null, true);
                        continue;
                    }
                    $error = $response->title . " : " . $response->detail;

                    $this->saveSyncData($id, $type, $mailchimpStoreId, null, $error, 0, null, null, true);

                    $mailchimpErrors->setType($response->type);
                    $mailchimpErrors->setTitle($response->title);
                    $mailchimpErrors->setStatus($item->status_code);
                    $mailchimpErrors->setErrors($errorDetails);
                    $mailchimpErrors->setRegtype($type);
                    $mailchimpErrors->setOriginalId($id);
                    $mailchimpErrors->setBatchId($batchId);
                    $mailchimpErrors->setStoreId($store[1]);
                    $mailchimpErrors->save();
                    $this->getHelper()->logError($error, $store[1]);
                }
            }

            unlink($file);
        }
    }

    /**
     * Handle batch for order id replacement with the increment id in MailChimp.
     *
     * @param $initialTime
     * @param $magentoStoreId
     */
    public function replaceAllOrders($initialTime, $magentoStoreId)
    {
        try {
            $this->_getResults($magentoStoreId);

            //handle order replacement
            $mailchimpStoreId = $this->getHelper()->getMCStoreId($magentoStoreId);

            $batchArray['operations'] = Mage::getModel('mailchimp/api_orders')->replaceAllOrdersBatch($initialTime, $mailchimpStoreId, $magentoStoreId);
            try {
                /**
                 * @var $mailchimpApi \Ebizmarts_MailChimp
                 */
                $mailchimpApi = $this->getHelper()->getApi($magentoStoreId);
                if (!empty($batchArray['operations'])) {
                    $batchJson = json_encode($batchArray);
                    if (!$batchJson || $batchJson == '') {
                        $this->getHelper()->logRequest('An empty operation was detected', $magentoStoreId);
                    } else {
                        if (!$this->getHelper()->getIsReseted($magentoStoreId)) {
                            $batchResponse = $mailchimpApi->batchOperation->add($batchJson);
                            $this->getHelper()->logRequest($batchJson, $magentoStoreId, $batchResponse['id']);
                            //save batch id to db
                            $batch = Mage::getModel('mailchimp/synchbatches');
                            $batch->setStoreId($mailchimpStoreId)
                                ->setBatchId($batchResponse['id'])
                                ->setStatus($batchResponse['status']);
                            $batch->save();
                        }
                    }
                }

            } catch (MailChimp_Error $e) {
                $this->getHelper()->logError($e->getFriendlyMessage(), $magentoStoreId);
            } catch (Exception $e) {
                $this->getHelper()->logError($e->getMessage(), $magentoStoreId);
                $this->getHelper()->logError("Json encode fails", $magentoStoreId);
                $this->getHelper()->logError($batchArray, $magentoStoreId);
            }
        } catch (MailChimp_Error $e) {
            $this->getHelper()->logError($e->getFriendlyMessage(), $magentoStoreId);
        } catch (Exception $e) {
            $this->getHelper()->logError($e->getMessage(), $magentoStoreId);
        }
    }

    protected function saveSyncData($itemId, $itemType, $mailchimpStoreId, $syncDelta = null, $syncError = null,
                                    $syncModified = 0, $syncDeleted = null, $token = null, $saveOnlyIfexists = false)
    {
        $helper = $this->getHelper();
        if ($itemType == Ebizmarts_MailChimp_Model_Config::IS_SUBSCRIBER) {
            $helper->updateSubscriberSyndData($itemId, $syncDelta, $syncError, 0, null);
        } else {
            $helper->saveEcommerceSyncData($itemId, $itemType, $mailchimpStoreId, $syncDelta, $syncError, $syncModified, $syncDeleted, $token, $saveOnlyIfexists);
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
        $mailchimpStoreId = $helper->getMCStoreId($storeId);
        $syncedDate = $helper->getMCIsSyncing($storeId);
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
                if ((int)$syncedDate === 1) {
                    $syncedDateArray[$mailchimpStoreId] = array($storeId => false);
                } elseif (!isset($syncedDateArray[$mailchimpStoreId])) {
                    $syncedDateArray[$mailchimpStoreId] = array($storeId => true);
                }
            }
        }
        return $syncedDateArray;
    }

    /**
     * @param $syncedDateArray
     */
    protected function handleSyncingValue($syncedDateArray)
    {
        $helper = $this->getHelper();
        foreach ($syncedDateArray as $mailchimpStoreId => $val) {
            $magentoStoreId = key($val);
            $date = $val[$magentoStoreId];
            if ($date) {
                $api = $helper->getApi($magentoStoreId);
                $isSyncingDate = $helper->getDateSyncFinishByMailChimpStoreId($mailchimpStoreId);
                if (!$isSyncingDate && $mailchimpStoreId) {
                    Mage::getModel('mailchimp/api_stores')->editIsSyncing($api, false, $mailchimpStoreId);
                    $scopeToEdit = Mage::helper('mailchimp')->getMailChimpScopeByStoreId($magentoStoreId);
                    if ($scopeToEdit['scope'] != 'stores') {
                        Mage::getConfig()->deleteConfig(Ebizmarts_MailChimp_Model_Config::GENERAL_MCISSYNCING, $scopeToEdit['scope'], $scopeToEdit['scope_id']);
                    }
                    $config = array(array(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_SYNC_DATE . "_$mailchimpStoreId", $date));
                    $helper->saveMailchimpConfig($config, 0, 'default');
                    $helper->handleWebhookChange($magentoStoreId);
                }
            }
        }
    }

    /**
     * @param $storeId
     */
    protected function handleResetIfNecessary($storeId)
    {
        $helper = $this->getHelper();
        if ($helper->getIsReseted($storeId)) {
            $scopeToReset = $helper->getMailChimpScopeByStoreId($storeId);
            if ($scopeToReset) {
                $helper->resetMCEcommerceData($scopeToReset['scope_id'], $scopeToReset['scope'], true);
                $configValue = array(array(Ebizmarts_MailChimp_Model_Config::GENERAL_MCSTORE_RESETED, 0));
                $helper->saveMailchimpConfig($configValue, $scopeToReset['scope_id'], $scopeToReset['scope']);
            }
        }
    }

    /**
     * @return array
     */
    protected function getStores()
    {
        return Mage::app()->getStores();
    }
}
