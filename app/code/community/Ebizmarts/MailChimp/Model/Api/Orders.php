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
        $data['id'] = $order->getEntityId();
        if($order->getMailchimpCampaignId()) {
            $data['campaign_id'] = $order->getMailchimpCampaignId();
        }
        $data['currency_code'] = $order->getOrderCurrencyCode();
        $data['order_total'] = $order->getGrandTotal();
        $data['processed_at_foreign'] = $order->getCreatedAt();
        $data['lines'] = array();

        //order lines
        $items = $order->getAllVisibleItems();
        $item_count = 0;
        foreach ($items as $item) {
            if($item->getProductType()==Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
                $options = $item->getProductOptions();
                $sku = $options['simple_sku'];
                $variant = Mage::getModel('catalog/product')->getIdBySku($sku);
            }
            else {
                $variant = $item->getProductId();
            }
            // load the product and check if the product was already sent to mailchimp
            $syncDelta = Mage::getResourceModel('catalog/product')->getAttributeRawValue($item->getProductId(), 'mailchimp_sync_delta',$order->getStoreId());
            $syncError = Mage::getResourceModel('catalog/product')->getAttributeRawValue($item->getProductId(), 'mailchimp_sync_error',$order->getStoreId());

            if($syncDelta&&$syncError==0) {
                $item_count += 1;
                $data["lines"][] = array(
                    "id" => (string)$item_count,
                    "product_id" => $item->getProductId(),
                    "product_variant_id" => $variant,
                    "quantity" => (int)$item->getQtyOrdered(),
                    "price" => $item->getPrice(),
                );
            }
        }
        if(!$item_count)
        {
            return "";
            unset($data['lines']);
        }
        //customer data
        if ((bool)$order->getCustomerIsGuest()) {
            $data["customer"] = array(
                "id" => "GUEST-" . date('Y-m-d-H-i-s'),
                "email_address" => $order->getCustomerEmail(),
                "opt_in_status" => false
            );
        } else {
            $data["customer"] = array(
                "id" => $order->getCustomerId(),
                "email_address" => $order->getCustomerEmail(),
                "opt_in_status" => Ebizmarts_MailChimp_Model_Api_Customers::DEFAULT_OPT_IN
            );
            $billingAddress = $order->getBillingAddress();
            $street = $billingAddress->getStreet();
            $data["customer"]["first_name"] = $order->getCustomerFirstname();
            $data["customer"]["last_name"] = $order->getCustomerLastname();
            $data["customer"]["address"] = array(
                "address1" => $street[0],
                "address2" => count($street)>1 ? $street[1] : "",
                "city" => $billingAddress->getCity(),
                "province" => $billingAddress->getRegion() ? $billingAddress->getRegion() : "",
                "province_code" => $billingAddress->getRegionCode() ? $billingAddress->getRegionCode() : "",
                "postal_code" => $billingAddress->getPostcode(),
                "country" => Mage::getModel('directory/country')->loadByCode($billingAddress->getCountry())->getName(),
                "country_code" => $billingAddress->getCountry()
            );
            //company
            if ($billingAddress->getCompany()) {
                $data["customer"]["company"] = $billingAddress->getCompany();
            }
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