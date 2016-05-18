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
            ->addFieldToFilter('store_id',array('eq'=>$storeId))
            ->addFieldToFilter('status',array('eq'=>'pending'));
        foreach($collection as $item)
        {
            $files = $this->getBatchResponse($item->getBatchId(),$storeId);
            if(count($files)) {
                $this->processEachResponseFile($files);
                $item->setStatus('completed');
                $item->save();
            }
            $baseDir = Mage::getBaseDir();
            if(is_dir($baseDir.DS.'var'.DS.'mailchimp'.DS.$item->getBatchId())) {
                rmdir($baseDir . DS . 'var' . DS . 'mailchimp' . DS . $item->getBatchId());
            }
        }
    }
    public function SendBatch($mailchimpStoreId)
    {
        $apiKey = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_APIKEY);
        if ($apiKey) {

            $batchJson = '';

            //customer operations
            $customersJson = Mage::getModel('mailchimp/api_customers')->CreateBatchJson($mailchimpStoreId);
            $batchJson .= $customersJson;

//            echo "<h1>REQUEST</h1>";
//            var_dump($batchJson);

            //product operations
            $productsJson = Mage::getModel('mailchimp/api_products')->CreateBatchJson($mailchimpStoreId);
            $batchJson .= $customersJson != "" && $productsJson != "" ? ",".$productsJson : $productsJson;

            //order operations
            $ordersJson = Mage::getModel('mailchimp/api_orders')->CreateBatchJson($mailchimpStoreId);
            $batchJson .= ($customersJson != "" || $ordersJson != "") && $ordersJson != "" ? ",".$ordersJson : $ordersJson;

            if($batchJson!='') {
                $batchJson = '{"operations": ['.$batchJson.']}';
                Mage::log($batchJson,null,'Mailchimp_Request');
                $mailchimpApi = new Ebizmarts_Mailchimp($apiKey);
                $batchResponse = $mailchimpApi->batchOperation->add($batchJson);

                //save batch id to db
                $batch = Mage::getModel('mailchimp/synchbatches');
                $batch->setStoreId($mailchimpStoreId)
                    ->setBatchId($batchResponse['id'])
                    ->setStatus($batchResponse['status']);
                $batch->save();
                return $batchResponse;
            }
        }

        return null;
    }

    /**
     * @param $batchId
     * @param $storeId
     * @return array
     */
    protected function getBatchResponse($batchId,$storeId)
    {
        $files = array();
        $baseDir = Mage::getBaseDir();
        $apiKey = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_APIKEY);
        $api = new Ebizmarts_Mailchimp($apiKey);
        // check the status of the job
        $response = $api->batchOperation->status($batchId);
        if($response['status']=='finished')
        {
            // get the tar.gz file with the results
            $fileUrl = urldecode($response['response_body_url']);
            $fileName = $baseDir.DS.'var'.DS.'mailchimp'.DS.$batchId;
            $fd = fopen($fileName.'.tar.gz', 'w');
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
                    $files[] = $baseDir . DS . 'var' . DS . 'mailchimp' . DS . $batchId.'/'.$d;
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
        foreach($files as $file)
        {
            $items = json_decode(file_get_contents($file));
            foreach($items as $item)
            {
                if($item->status_code != 200) {
                    $line = explode('_', $item->operation_id);
                    $response = json_decode($item->response);
                    $error = $response->detail;
                    $type = $line[0];
                    $id = $line[2];
                    switch ($type) {
                        case Ebizmarts_MailChimp_Model_Config::IS_PRODUCT:
                            $p = Mage::getModel('catalog/product')->load($id);
                            $p->setData("mailchimp_sync_error",$error);
                            $p->save();
                            break;
                        case Ebizmarts_MailChimp_Model_Config::IS_CUSTOMER:
                            $c = Mage::getModel('customer/customer')->load($id);
                            $c->setData("mailchimp_sync_error",$error);
                            $c->save();
                            break;
                        case Ebizmarts_MailChimp_Model_Config::IS_ORDER:
                            $o = Mage::getModel('sales/order')->load($id);
                            $o->setData("mailchimp_sync_error",$error);
                            $o->save();
                            break;
                        case Ebizmarts_MailChimp_Model_Config::IS_QUOTE:
                            $q = Mage::getModel('sales/quote')->load($id);
                            $q->setData("mailchimp_sync_error",$error);
                            $q->save();
                            break;
                        default:
                            Mage::log("Error: no identification $type found",null,'Mailchimp_Errors');
                            break;
                    }
                    Mage::log($item,null,"Mailchimp_Errors");
                }
            }
            unlink($file);
        }
    }
}