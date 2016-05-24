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
class Ebizmarts_MailChimp_Model_Api_Carts
{

    const BATCH_LIMIT = 1000;

    /**
     * WE WONT BE SENDING ABANDONED CARTS WITH BATCHES BUT INDIVIDUALLY
     *
     */
//    public function CreateBatchJson($mailchimpStoreId)
//    {
//        //create missing products first
//        $collection = Mage::getModel('sales/quote')->getCollection()
//            ->addAttributeToSelect('is_active')
//            ->addAttributeToSelect('mailchimp_sync_delta')
//            ->addAttributeToSelect('entity_id')
//            ->addFieldToFilter('is_active', "1")
//            ->addFieldToFilter('mailchimp_sync_delta', array('null' => true));
//        $collection->getSelect()->limit(self::BATCH_LIMIT);
//
//        $batchJson = '';
//        $operationsCount = 0;
//        $batchId = Ebizmarts_MailChimp_Model_Config::IS_QUOTE.'_'.date('Y-m-d-H-i-s');
//
//        foreach ($collection as $quote) {
//            $quoteJson = $this->GeneratePOSTPayload($quote);
//            if (!empty($quoteJson)) {
//                $operationsCount += 1;
//                if ($operationsCount > 1) {
//                    $batchJson .= ',';
//                }
//                $batchJson .= '{"method": "POST",';
//                $batchJson .= '"path": "/ecommerce/stores/' . $mailchimpStoreId . '/carts",';
//                $batchJson .= '"operation_id": "' . $batchId . '_' . $quote->getEntityId() . '",';
//                $batchJson .= '"body": "' . addcslashes($quoteJson, '"') . '"';
//                $batchJson .= '}';
//
//                //update order delta
////                $quote->setData("mailchimp_sync_delta", Varien_Date::now());
////                $quote->save();
//            }
//        }
//
//        return $batchJson;
//    }

//    protected function GeneratePOSTPayload($quote_from_collection)
//    {
//        $quote = Mage::getModel('sales/quote')->load($quote_from_collection->getEntityId());
//
//        $data = array();
//        $data["id"] = $quote->getEntityId();
//        $data["currency_code"] = $quote->getOrderCurrencyCode();
//        $data["order_total"] = $quote->getGrandTotal();
//        $data["lines"] = [];
//
//        //order lines
//        $items = $quote->getAllVisibleItems();
//        $item_count = 0;
//        foreach ($items as $item) {
//            $item_count += 1;
//            $data["lines"][] = [
//                "id" => (string)$item_count,
//                "product_id" => $item->getProductId(),
//                "product_variant_id" => $item->getProductId(),
//                "quantity" => (int)$item->getQtyOrdered(),
//                "price" => $item->getPrice(),
//            ];
//        }
//
//        //customer data
//        if ((bool)$quote->getCustomerIsGuest()) {
//            $data["customer"] = array(
//                "id" => "GUEST-" . date('Y-m-d-H-i-s'),
//                "email_address" => $quote->getCustomerEmail(),
//                "opt_in_status" => Ebizmarts_MailChimp_Model_Api_Customers::DEFAULT_OPT_IN
//            );
//        } else {
//            $data["customer"] = array(
//                "id" => $quote->getCustomerId()
//            );
//        }
//
//        $jsonData = "";
//
//        //enconde to JSON
//        try {
//
//            $jsonData = json_encode($data);
//
//        } catch (Exception $e) {
//            //json encode failed
//            //@toDo log somewhere
//        }
//
//        return $jsonData;
//    }
}