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

    public function SendBatch($mailchimpStoreId)
    {
        $apiKey = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_APIKEY);
        if ($apiKey) {

            $batchJson = '{"operations": [';

            //customer operations
            $customersJson = Mage::getModel('mailchimp/api_customers')->CreateBatchJson($mailchimpStoreId);
            $batchJson .= $customersJson;

            //product operations
            $productsJson = Mage::getModel('mailchimp/api_products')->CreateBatchJson($mailchimpStoreId);
            $batchJson .= $customersJson != "" && $productsJson != "" ? ",".$productsJson : $productsJson;

            //order operations
            $ordersJson = Mage::getModel('mailchimp/api_orders')->CreateBatchJson($mailchimpStoreId);
            $batchJson .= ($customersJson != "" || $ordersJson != "") && $ordersJson != "" ? ",".$ordersJson : $ordersJson;

            $batchJson .= ']}';

            echo "<h1>REQUEST</h1>";
            var_dump($batchJson);

            $mailchimpApi = new Ebizmarts_Mailchimp($apiKey);
            $batchResponse = $mailchimpApi->batchOperation->add($batchJson);

            //@toDo
            //save batch id to db

            return $batchResponse;
        }

        return null;
    }

    protected function GeneratePOSTPayload($customer)
    {
        $data = array();
        $data["id"] = $customer->getId();
        $data["email_address"] = $customer->getEmail();
//        $data["first_name"] = $customer->getFirstname();
//        $data["last_name"] = $customer->getLastname();

        $data["opt_in_status"] = self::DEFAULT_OPT_IN;

        $jsonData = "";

        //enconde to JSON
        try {

            $jsonData = json_encode($data);

        } catch (Exception $e) {
            //json encode failed
            //@toDo log somewhere
        }

        return $jsonData;
    }
}