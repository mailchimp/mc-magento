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
class Ebizmarts_MailChimp_Model_Api_Orders
{

    const BATCH_LIMIT = 100;

    public function createBatchJson($mailchimpStoreId)
    {
            //create missing products first
            $collection = Mage::getModel('sales/order')->getCollection()
                ->addAttributeToSelect('status')
                ->addAttributeToSelect('mailchimp_sync_delta')
                ->addAttributeToSelect('entity_id')
                ->addFieldToFilter('status', 'complete')
                ->addFieldToFilter('mailchimp_sync_delta', array(
                    array('null' => true),
                    array('eq' => ''),
                    array('lt' => Mage::helper('mailchimp')->getMCMinSyncDateFlag())
                ));
            $collection->getSelect()->limit(self::BATCH_LIMIT);

            $batchArray = array();
            $batchId = Ebizmarts_MailChimp_Model_Config::IS_ORDER.'_'.date('Y-m-d-H-i-s');
            $counter = 0;
            foreach ($collection as $order) {
                $orderJson = $this->GeneratePOSTPayload($order);
                if (!empty($orderJson)) {
                    $batchArray[$counter]['method'] = "POST";
                    $batchArray[$counter]['path'] = '/ecommerce/stores/' . $mailchimpStoreId . '/orders';
                    $batchArray[$counter]['operation_id'] = $batchId . '_' . $order->getEntityId();
                    $batchArray[$counter]['body'] = $orderJson;

                    //update order delta
                    $order->setData("mailchimp_sync_delta", Varien_Date::now());
                    $order->save();
                }
                $counter += 1;
            }

            return $batchArray;
    }

    protected function GeneratePOSTPayload($order_from_collection)
    {
        $order = Mage::getModel('sales/order')->load($order_from_collection->getEntityId());

        $data = array();
        $data["id"] = $order->getEntityId();
        $data["currency_code"] = $order->getOrderCurrencyCode();
        $data["order_total"] = $order->getGrandTotal();
        $data["lines"] = array();

        //order lines
        $items = $order->getAllVisibleItems();
        $item_count = 0;
        foreach ($items as $item) {
            $item_count += 1;
            $data["lines"][] = array(
                "id" => (string)$item_count,
                "product_id" => $item->getProductId(),
                "product_variant_id" => $item->getProductId(),
                "quantity" => (int)$item->getQtyOrdered(),
                "price" => $item->getPrice(),
            );
        }

        //customer data
        if ((bool)$order->getCustomerIsGuest()) {
            $data["customer"] = array(
                "id" => "GUEST-" . date('Y-m-d-H-i-s'),
                "email_address" => $order->getCustomerEmail(),
                "opt_in_status" => Ebizmarts_MailChimp_Model_Api_Customers::DEFAULT_OPT_IN
            );
        } else {
            $data["customer"] = array(
                "id" => $order->getCustomerId(),
                "email_address" => $order->getCustomerEmail(),
                "opt_in_status" => Ebizmarts_MailChimp_Model_Api_Customers::DEFAULT_OPT_IN
            );
        }

        $jsonData = "";

        //enconde to JSON
        try {

            $jsonData = json_encode($data);

        } catch (Exception $e) {
            //json encode failed
            Mage::helper('mailchimp')->logError("Order ".$order->getId()." json encode failed");
        }

        return $jsonData;
    }
}