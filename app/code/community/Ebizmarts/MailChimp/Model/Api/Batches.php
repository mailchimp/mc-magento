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

    public function getResults($storeId)
    {
        $collection = Mage::getModel('mailchimp/synchbatches')->getCollection()
            ->addFieldToFilter('store_id', array('eq' => $storeId))
            ->addFieldToFilter('status', array('eq' => 'pending'));
        foreach ($collection as $item) {
            $files = $this->getBatchResponse($item->getBatchId(), $storeId);
            if (count($files)) {
                $this->processEachResponseFile($files);
                $item->setStatus('completed');
                $item->save();
            }
            $baseDir = Mage::getBaseDir();
            if (is_dir($baseDir . DS . 'var' . DS . 'mailchimp' . DS . $item->getBatchId())) {
                rmdir($baseDir . DS . 'var' . DS . 'mailchimp' . DS . $item->getBatchId());
            }
        }
    }

    public function sendBatch($mailchimpStoreId)
    {

        try {

        if (Mage::helper('mailchimp')->isEcommerceSyncDataEnabled()) {

            $apiKey = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_APIKEY);

            $batchArray = array();
            $ordersArray = array();

            //customer operations
            $customersArray = Mage::getModel('mailchimp/api_customers')->createBatchJson($mailchimpStoreId);

            //product operations
            $productsArray = Mage::getModel('mailchimp/api_products')->createBatchJson($mailchimpStoreId);
            if(empty($productsArray)) {
                //order operations
                $ordersArray = Mage::getModel('mailchimp/api_orders')->createBatchJson($mailchimpStoreId);
            }
            $batchArray['operations'] = array_merge($customersArray,$productsArray,$ordersArray);

            if (!empty($batchArray['operations'])) {
                $batchJson = json_encode($batchArray);
                if(!$batchJson || $batchJson=='' )
                {
                    Mage::helper('mailchimp')->logRequest('An empty operation was detected');
                }
                else {
                    //log request
                    Mage::helper('mailchimp')->logRequest($batchJson);

                    $mailchimpApi = new Ebizmarts_Mailchimp($apiKey);
                    $batchResponse = $mailchimpApi->batchOperation->add($batchJson);

                    //save batch id to db
                    $batch = Mage::getModel('mailchimp/synchbatches');
                    $batch->setStoreId($mailchimpStoreId)
                        ->setBatchId($batchResponse['id'])
                        ->setStatus($batchResponse['status']);
                    $batch->save();
                }
                return $batchResponse;
            }
        }

        } catch (Mailchimp_Error $e)
        {
            Mage::helper('mailchimp')->logError($e->getFriendlyMessage());

        } catch (Exception $e)
        {
            Mage::helper('mailchimp')->logError($e->getMessage());
        }

        return null;
    }

    /**
     * @param $batchId
     * @param $storeId
     * @return array
     */
    protected function getBatchResponse($batchId, $storeId)
    {
        $files = array();
        $baseDir = Mage::getBaseDir();
        $apiKey = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_APIKEY);
        $api = new Ebizmarts_Mailchimp($apiKey);
        // check the status of the job
        $response = $api->batchOperation->status($batchId);
        if ($response['status'] == 'finished') {
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
            mkdir($baseDir . DS . 'var' . DS . 'mailchimp' . DS . $batchId);
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
        return $files;
    }

    /**
     * @param $files
     */
    protected function processEachResponseFile($files)
    {
        foreach ($files as $file) {
            $items = json_decode(file_get_contents($file));
            foreach ($items as $item)
            {
                if ($item->status_code != 200)
                {
                    $line = explode('_', $item->operation_id);
                    $type = $line[0];
                    $id = $line[2];

                    $mailchimpErrors = Mage::getModel('mailchimp/mailchimperrors');

                    //parse error
                    $response = json_decode($item->response);
                    $error_details = "";
                    if(!empty($response->errors))
                    {
                        foreach($response->errors as $error)
                        {
                            if(isset($error->field) && isset($error->message)){
                                $error_details .= $error_details != "" ? " / " : "";
                                $error_details .= $error->field . " : " . $error->message;
                            }
                        }
                    }
                    if($error_details == ""){
                        $error_details = $response->detail;
                    }

                    $error = $response->title . " : " . $response->detail;

                    switch ($type) {
                        case Ebizmarts_MailChimp_Model_Config::IS_PRODUCT:
                            $p = Mage::getModel('catalog/product')->load($id);
                            $p->setData("mailchimp_sync_error", $error);
                            $p->save();
                            break;
                        case Ebizmarts_MailChimp_Model_Config::IS_CUSTOMER:
                            $c = Mage::getModel('customer/customer')->load($id);
                            $c->setData("mailchimp_sync_error", $error);
                            $c->save();
                            break;
                        case Ebizmarts_MailChimp_Model_Config::IS_ORDER:
                            $o = Mage::getModel('sales/order')->load($id);
                            $o->setData("mailchimp_sync_error", $error);
                            $o->save();
                            break;
                        case Ebizmarts_MailChimp_Model_Config::IS_QUOTE:
                            $q = Mage::getModel('sales/quote')->load($id);
                            $q->setData("mailchimp_sync_error", $error);
                            $q->save();
                            break;
                        default:
                            Mage::helper('mailchimp')->logError("Error: no identification ".$type." found");
                            break;
                    }
                    $mailchimpErrors->setType($response->type);
                    $mailchimpErrors->setTitle($response->title);
                    $mailchimpErrors->setStatus($item->status_code);
                    $mailchimpErrors->setErrors($error_details);
                    $mailchimpErrors->setRegtype($type);
                    $mailchimpErrors->setOriginalId($id);
                    $mailchimpErrors->save();
                    Mage::helper('mailchimp')->logError($error);
                }
            }
            unlink($file);
        }
    }
}