<?php

/**
 * mailchimp-lib Magento Component
 *
 * @category Ebizmarts
 * @package mailchimp-lib
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Ebizmarts_MailChimp_Model_Api_Batches
{
    /**
     * Get Results and send Ecommerce Batches.
     */
    public function handleEcommerceBatches()
    {
        $stores = Mage::app()->getStores();
        foreach ($stores as $store) {
            $this->_getResults($store->getId());
            $this->_sendEcommerceBatch($store->getId());
        }

        foreach ($stores as $store) {
            if (Mage::helper('mailchimp')->getIsReseted($store->getId())) {
                $scopeToReset = Mage::helper('mailchimp')->getMailChimpScopeByStoreId($store->getId());
                if ($scopeToReset) {
                    Mage::helper('mailchimp')->resetMCEcommerceData($scopeToReset['scope_id'], $scopeToReset['scope'], true);
                    $configValue = array(array(Ebizmarts_MailChimp_Model_Config::GENERAL_MCSTORE_RESETED, 0, $scopeToReset['scope'], $scopeToReset['scope_id']));
                    Mage::helper('mailchimp')->saveMailchimpConfig($configValue, $scopeToReset['scope_id'], $scopeToReset['scope']);
                }
            }
        }
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
     */
    protected function _getResults($magentoStoreId)
    {
        $mailchimpStoreId = Mage::helper('mailchimp')->getMCStoreId($magentoStoreId);
        $collection = Mage::getModel('mailchimp/synchbatches')->getCollection()
            ->addFieldToFilter('store_id', array('eq' => $mailchimpStoreId))
            ->addFieldToFilter('status', array('eq' => 'pending'));
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
     * @param $magentoStoreId
     * @return null
     */
    public function _sendEcommerceBatch($magentoStoreId)
    {
        $mailchimpStoreId = Mage::helper('mailchimp')->getMCStoreId($magentoStoreId);
        try {
            if (Mage::helper('mailchimp')->isMailChimpEnabled($magentoStoreId) && Mage::helper('mailchimp')->isEcomSyncDataEnabled($magentoStoreId)) {
                $batchArray = array();
                //customer operations
                $customersArray = Mage::getModel('mailchimp/api_customers')->createBatchJson($mailchimpStoreId, $magentoStoreId);
                $customerAmount = count($customersArray);
                $batchArray['operations'] = $customersArray;
                //product operations
                $productsArray = Mage::getModel('mailchimp/api_products')->createBatchJson($mailchimpStoreId, $magentoStoreId);
                $productAmount = count($productsArray);
                $batchArray['operations'] = array_merge($batchArray['operations'], $productsArray);
                //order operations
                $cartsArray = Mage::getModel('mailchimp/api_carts')->createBatchJson($mailchimpStoreId, $magentoStoreId);
                $batchArray['operations'] = array_merge($batchArray['operations'], $cartsArray);
                $ordersArray = Mage::getModel('mailchimp/api_orders')->createBatchJson($mailchimpStoreId, $magentoStoreId);
                $orderAmount = count($ordersArray);
                $batchArray['operations'] = array_merge($batchArray['operations'], $ordersArray);
                try {
                    /**
                     * @var $mailchimpApi \Ebizmarts_Mailchimp
                     */
                    $mailchimpApi = Mage::helper('mailchimp')->getApi($magentoStoreId);
                    if (!empty($batchArray['operations'])) {
                        $batchJson = json_encode($batchArray);
                        if (!$batchJson || $batchJson == '') {
                            Mage::helper('mailchimp')->logRequest('An empty operation was detected', $magentoStoreId);
                        } else {
                            if (!Mage::helper('mailchimp')->getIsReseted($magentoStoreId)) {
                                $batchResponse = $mailchimpApi->batchOperation->add($batchJson);
                                Mage::helper('mailchimp')->logRequest($batchJson, $magentoStoreId, $batchResponse['id']);
                                //save batch id to db
                                $batch = Mage::getModel('mailchimp/synchbatches');
                                $batch->setStoreId($mailchimpStoreId)
                                    ->setBatchId($batchResponse['id'])
                                    ->setStatus($batchResponse['status']);
                                $batch->save();
                            }
                        }
                    }

                    $itemAmount = ($customerAmount + $productAmount + $orderAmount);
                    if (Mage::helper('mailchimp')->getMCIsSyncing($magentoStoreId) && $itemAmount == 0) {
                        $isSyncing = false;
                        $mailchimpApi->ecommerce->stores->edit($mailchimpStoreId, null, null, null, $isSyncing);
                        $scopeToEdit = Mage::helper('mailchimp')->getMailChimpScopeByStoreId($magentoStoreId);
                        $configValue = array(array(Ebizmarts_MailChimp_Model_Config::GENERAL_MCISSYNCING, 0));
                        Mage::helper('mailchimp')->saveMailchimpConfig($configValue, $scopeToEdit['scope_id'], $scopeToEdit['scope']);
                    }
                } catch (Mailchimp_Error $e) {
                    Mage::helper('mailchimp')->logError($e->getFriendlyMessage(), $magentoStoreId);
                } catch (Exception $e) {
                    Mage::helper('mailchimp')->logError($e->getMessage(), $magentoStoreId);
                    Mage::helper('mailchimp')->logError("Json encode fails", $magentoStoreId);
                    Mage::helper('mailchimp')->logError($batchArray, $magentoStoreId);
                }
            }
        } catch (Mailchimp_Error $e) {
            Mage::helper('mailchimp')->logError($e->getFriendlyMessage(), $magentoStoreId);
        } catch (Exception $e) {
            Mage::helper('mailchimp')->logError($e->getMessage(), $magentoStoreId);
        }
    }

    /**
     * Send Subscribers batch on each store view, return array of batches responses.
     *
     * @return array
     */
    protected function _sendSubscriberBatches()
    {
        $subscriberLimit = Ebizmarts_MailChimp_Model_Api_subscribers::BATCH_LIMIT;
        $stores = Mage::app()->getStores();
        $batchResponses = array();
        foreach ($stores as $store) {
            $storeId = $store->getId();
            $this->_getResults($storeId, false);
            if ($subscriberLimit > 0) {
                list($batchResponses[], $subscriberLimit) = $this->sendStoreSubscriberBatch($storeId, $subscriberLimit);
            } else {
                break;
            }
        }

        $this->_getResults(0, false);
        if ($subscriberLimit > 0) {
            list($batchResponses[], $subscriberLimit) = $this->sendStoreSubscriberBatch(0, $subscriberLimit);
        }

        return $batchResponses;
    }

    /**
     * Send Subscribers batch on particular store view, return batch response.
     *
     * @param $storeId
     * @param $limit
     * @return array|null
     */
    public function sendStoreSubscriberBatch($storeId, $limit)
    {
        try {
            $subscribersArray = array();
            if (Mage::helper('mailchimp')->isMailChimpEnabled($storeId)) {
                $listId = Mage::helper('mailchimp')->getGeneralList($storeId);

                $batchArray = array();

                //subscriber operations
                $subscribersArray = Mage::getModel('mailchimp/api_subscribers')->createBatchJson($listId, $storeId, $limit);
                $limit -= count($subscribersArray);
            }

            $batchArray['operations'] = $subscribersArray;

            if (!empty($batchArray['operations'])) {
                $batchJson = json_encode($batchArray);
                if (!$batchJson || $batchJson == '') {
                    Mage::helper('mailchimp')->logRequest('An empty operation was detected', $storeId);
                } else {
                    $mailchimpApi = Mage::helper('mailchimp')->getApi($storeId);
                    $batchResponse = $mailchimpApi->batchOperation->add($batchJson);
                    Mage::helper('mailchimp')->logRequest($batchJson, $storeId, $batchResponse['id']);

                    //save batch id to db
                    $batch = Mage::getModel('mailchimp/synchbatches');
                    $batch->setStoreId($storeId)
                        ->setBatchId($batchResponse['id'])
                        ->setStatus($batchResponse['status']);
                    $batch->save();
                    return array($batchResponse, $limit);
                }
            }
        } catch (Mailchimp_Error $e) {
            Mage::helper('mailchimp')->logError($e->getFriendlyMessage(), $storeId);
        } catch (Exception $e) {
            Mage::helper('mailchimp')->logError($e->getMessage(), $storeId);
        }

        return null;
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
            $api = Mage::helper('mailchimp')->getApi($magentoStoreId);
            // check the status of the job
            $response = $api->batchOperation->status($batchId);
            if (isset($response['status']) && $response['status'] == 'finished') {
                // get the tar.gz file with the results
                $fileUrl = urldecode($response['response_body_url']);
                $fileName = $baseDir . DS . 'var' . DS . 'mailchimp' . DS . $batchId;
                $fd = fopen($fileName . '.tar.gz', 'w');
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $fileUrl);
                curl_setopt($ch, CURLOPT_FILE, $fd);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // this will follow redirects
                $r = curl_exec($ch);
                curl_close($ch);
                fclose($fd);
                mkdir($baseDir . DS . 'var' . DS . 'mailchimp' . DS . $batchId, 0750);
                $archive = new Mage_Archive();
                $archive->unpack($fileName . '.tar.gz', $baseDir . DS . 'var' . DS . 'mailchimp' . DS . $batchId);
                $archive->unpack($baseDir . DS . 'var' . DS . 'mailchimp' . DS . $batchId . '/' . $batchId . '.tar', $baseDir . DS . 'var' . DS . 'mailchimp' . DS . $batchId);
                $dir = scandir($baseDir . DS . 'var' . DS . 'mailchimp' . DS . $batchId);
                foreach ($dir as $d) {
                    $name = pathinfo($d);
                    if ($name['extension'] == 'json') {
                        $files[] = $baseDir . DS . 'var' . DS . 'mailchimp' . DS . $batchId . '/' . $d;
                    }
                }

                unlink($baseDir . DS . 'var' . DS . 'mailchimp' . DS . $batchId . '/' . $batchId . '.tar');
                unlink($fileName . '.tar.gz');
            }
        } catch (Mailchimp_Error $e) {
            $files['error'] = $e->getFriendlyMessage();
            Mage::helper('mailchimp')->logError($e->getFriendlyMessage(), $magentoStoreId);
        } catch (Exception $e) {
            Mage::helper('mailchimp')->logError($e->getMessage(), $magentoStoreId);
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

                    $error = $response->title . " : " . $response->detail;

                    Mage::helper('mailchimp')->saveEcommerceSyncData($id, $type, $mailchimpStoreId, null, $error, 0, null, null, true);
//                    switch ($type) {
//                        case Ebizmarts_MailChimp_Model_Config::IS_PRODUCT:
//                            $p = Mage::getModel('catalog/product')->load($id);
//                            if ($p->getId() == $id) {
//                                $p->setData("mailchimp_sync_error", $error);
//                                $p->getResource()->saveAttribute($p, 'mailchimp_sync_error');
//                            } else {
//                                Mage::helper('mailchimp')->logError("Error: product " . $id . " not found");
//                            }
//                            break;
//                        case Ebizmarts_MailChimp_Model_Config::IS_CUSTOMER:
//                            $c = Mage::getModel('customer/customer')->load($id);
//                            if ($c->getId() == $id) {
//                                $c->setData("mailchimp_sync_error", $error);
//                                $c->getResource()->saveAttribute($c, 'mailchimp_sync_error');
//                            } else {
//                                Mage::helper('mailchimp')->logError("Error: customer " . $id . " not found");
//                            }
//                            break;
//                        case Ebizmarts_MailChimp_Model_Config::IS_ORDER:
//                            $o = Mage::getModel('sales/order')->load($id);
//                            if ($o->getId() == $id) {
//                                $o->setData("mailchimp_sync_error", $error);
//                                $o->setMailchimpSyncModified(0);
//                                $o->setMailchimpUpdateObserverRan(true);
//                                $o->save();
//                            } else {
//                                Mage::helper('mailchimp')->logError("Error: order " . $id . " not found");
//                            }
//                            break;
//                        case Ebizmarts_MailChimp_Model_Config::IS_QUOTE:
//                            $q = Mage::getModel('sales/quote')->load($id);
//                            if ($q->getId() == $id) {
//                                $q->setData("mailchimp_sync_error", $error);
//                                $q->save();
//                            } else {
//                                Mage::helper('mailchimp')->logError("Error: quote " . $id . " not found");
//                            }
//                            break;
//                        case Ebizmarts_MailChimp_Model_Config::IS_SUBSCRIBER:
//                            $s = Mage::getModel('newsletter/subscriber')->load($id);
//                            if ($s->getId() == $id) {
//                                $s->setData("mailchimp_sync_error", $error);
//                                $s->save();
//                            } else {
//                                Mage::helper('mailchimp')->logError("Error: subscriber " . $id . " not found");
//                            }
//                            break;
//                        default:
//                            Mage::helper('mailchimp')->logError("Error: no identification " . $type . " found");
//                            break;
//                    }

                    $mailchimpErrors->setType($response->type);
                    $mailchimpErrors->setTitle($response->title);
                    $mailchimpErrors->setStatus($item->status_code);
                    $mailchimpErrors->setErrors($errorDetails);
                    $mailchimpErrors->setRegtype($type);
                    $mailchimpErrors->setOriginalId($id);
                    $mailchimpErrors->setBatchId($batchId);
                    $mailchimpErrors->setStoreId($store[1]);
                    $mailchimpErrors->save();
                    Mage::helper('mailchimp')->logError($error, $store[1]);
                }
            }

            unlink($file);
        }
    }
}