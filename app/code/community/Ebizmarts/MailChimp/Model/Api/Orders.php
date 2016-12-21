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

    const BATCH_LIMIT = 50;
    protected $_api = null;

    /**
     * Set the request for orders to be created on MailChimp
     * 
     * @param $mailchimpStoreId
     * @return array
     */
    public function createBatchJson($mailchimpStoreId)
    {
        //create missing products first
        $collection = Mage::getModel('sales/order')->getCollection()
            ->addAttributeToSelect('entity_id')
            ->addFieldToFilter('state', 'complete')
            ->addFieldToFilter('mailchimp_sync_delta', array(
                array('null' => true),
                array('eq' => ''),
                array('lt' => Mage::helper('mailchimp')->getMCMinSyncDateFlag())
            ));
        $collection->getSelect()->limit(self::BATCH_LIMIT);

        $batchArray = array();

        $batchId = Ebizmarts_MailChimp_Model_Config::IS_ORDER.'_'. Mage::helper('mailchimp')->getDateMicrotime();
        $counter = 0;
        foreach ($collection as $item) {
            try {
                $order = Mage::getModel('sales/order')->load($item->getEntityId());
                $productData = Mage::getModel('mailchimp/api_products')->sendModifiedProduct($order, $mailchimpStoreId);
                if (count($productData)) {
                    foreach ($productData as $p) {
                        $batchArray[$counter] = $p;
                        $counter++;
                    }
                }
                $orderJson = $this->GeneratePOSTPayload($order, $mailchimpStoreId);
                if (!empty($orderJson)) {
                    $batchArray[$counter]['method'] = "POST";
                    $batchArray[$counter]['path'] = '/ecommerce/stores/' . $mailchimpStoreId . '/orders';
                    $batchArray[$counter]['operation_id'] = $batchId . '_' . $order->getEntityId();
                    $batchArray[$counter]['body'] = $orderJson;

                } else {
                    $error = Mage::helper('mailchimp')->__('Something went wrong when retreiving product information.');
                    $order->setData("mailchimp_sync_error", $error);
                }
                //update order delta
                $order->setData("mailchimp_sync_delta", Varien_Date::now());
                $order->save();
                $counter++;
            } catch (Exception $e) {
                Mage::helper('mailchimp')->logError($e->getMessage());
            }
        }

        return $batchArray;
    }

    /**
     * Set the orders to be removed from MailChimp because they were canceled
     * 
     * @param $mailchimpStoreId
     * @return array
     */
    public function createCanceledBatchJson($mailchimpStoreId)
    {
        //create missing products first
        $collection = Mage::getModel('sales/order')->getCollection()
            ->addAttributeToSelect('status')
            ->addAttributeToSelect('mailchimp_sync_delta')
            ->addAttributeToSelect('entity_id')
            ->addFieldToFilter('state', 'canceled')
            ->addFieldToFilter(
                'mailchimp_sync_delta', array(
                array('null' => true),
                array('eq' => ''),
                array('lt' => Mage::helper('mailchimp')->getMCMinSyncDateFlag())
                )
            );
        $collection->getSelect()->limit(self::BATCH_LIMIT);

        $batchArray = array();
        $counter = 0;
        foreach ($collection as $order) {
            if (!empty($orderJson)) {
                $batchArray[$counter]['method'] = "DELETE";
                $batchArray[$counter]['path'] = '/ecommerce/stores/' . $mailchimpStoreId . '/orders/' . $order->getEntityId();

                //update order delta
                $order->setData("mailchimp_sync_delta", Varien_Date::now());
                $order->save();
            }
            $counter++;
        }

        return $batchArray;
    }

    /**
     * Set all the data for each order to be sent
     *
     * @param $orderFromCollection
     * @param $mailchimpStoreId
     * @return string
     */
    protected function GeneratePOSTPayload($order,$mailchimpStoreId)
    {
//        $order = Mage::getModel('sales/order')->load($orderFromCollection->getEntityId());

        $data = array();
        $data['id'] = $order->getEntityId();
        if ($order->getMailchimpCampaignId()) {
            $data['campaign_id'] = $order->getMailchimpCampaignId();
        }
        $data['currency_code'] = $order->getOrderCurrencyCode();
        $data['order_total'] = $order->getGrandTotal();
        $data['tax_total'] = $order->getTaxAmount();
        $data['shipping_total'] = $order->getShippingAmount();
        $data['processed_at_foreign'] = $order->getCreatedAt();
        $data['lines'] = array();

        //order lines
        $items = $order->getAllVisibleItems();
        $itemCount = 0;
        foreach ($items as $item) {
            if ($item->getProductType()==Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
                $options = $item->getProductOptions();
                $sku = $options['simple_sku'];
                $variant = Mage::getModel('catalog/product')->getIdBySku($sku);
            } else {
                $variant = $item->getProductId();
            }
            // load the product and check if the product was already sent to mailchimp
            $syncDelta = Mage::getResourceModel('catalog/product')->getAttributeRawValue($item->getProductId(), 'mailchimp_sync_delta', $order->getStoreId());
            $syncError = Mage::getResourceModel('catalog/product')->getAttributeRawValue($item->getProductId(), 'mailchimp_sync_error', $order->getStoreId());

            if ($syncDelta&&$syncError==0) {
                $itemCount++;
                $data["lines"][] = array(
                    "id" => (string)$itemCount,
                    "product_id" => $item->getProductId(),
                    "product_variant_id" => $variant,
                    "quantity" => (int)$item->getQtyOrdered(),
                    "price" => $item->getPrice(),
                );
            }
        }
        if (!$itemCount) {
            return "";
            unset($data['lines']);
        }
        //customer data
        $api = $this->_getApi();
        $customers = array();
        try {
            $customers = $api->ecommerce->customers->getByEmail($mailchimpStoreId, $order->getCustomerEmail());
        } catch (Mailchimp_Error $e) {
            Mage::helper('mailchimp')->logError($e->getFriendlyMessage());
        }
        if (isset($customers['total_items']) && $customers['total_items'] > 0) {
            $id = $customers['customers'][0]['id'];
            $data['customer'] = array(
                'id' => $id
            );
            $guestCustomer = Mage::getModel('mailchimp/api_customers')->createGuestCustomer($id, $order);
            $mergeFields = Mage::getModel('mailchimp/api_customers')->getMergeVars($guestCustomer);
            if (is_array($mergeFields)) {
                $data['customer'] = array_merge($mergeFields, $data['customer']);
            }
        } else {
            if ((bool)$order->getCustomerIsGuest()) {
                $guestId = "GUEST-" . Mage::helper('mailchimp')->getDateMicrotime();
                $data["customer"] = array(
                    "id" => $guestId,
                    "email_address" => $order->getCustomerEmail(),
                    "opt_in_status" => false
                );
                $guestCustomer = Mage::getModel('mailchimp/api_customers')->createGuestCustomer($guestId, $order);
                $mergeFields = Mage::getModel('mailchimp/api_customers')->getMergeVars($guestCustomer);
                if (is_array($mergeFields)) {
                    $data['customer'] = array_merge($mergeFields, $data['customer']);
                }
            } else {
                $data["customer"] = array(
                    "id" => $order->getCustomerId(),
                    "email_address" => $order->getCustomerEmail(),
                    "opt_in_status" => Mage::getModel('mailchimp/api_customers')->getOptin()
                );
            }
            if($order->getCustomerFirstname()) {
                $data["customer"]["first_name"] = $order->getCustomerFirstname();
            }
            if($order->getCustomerLastname()) {
                $data["customer"]["last_name"] = $order->getCustomerLastname();
            }
        }
        $billingAddress = $order->getBillingAddress();
        $street = $billingAddress->getStreet();
        $data["customer"]["address"] = array(
            "address1" => $street[0],
            "address2" => count($street) > 1 ? $street[1] : "",
            "city" => $billingAddress->getCity(),
            "province" => $billingAddress->getRegion() ? $billingAddress->getRegion() : "",
            "province_code" => $billingAddress->getRegionCode() ? $billingAddress->getRegionCode() : "",
            "postal_code" => $billingAddress->getPostcode(),
            "country" => $billingAddress->getCountry() ? Mage::getModel('directory/country')->loadByCode($billingAddress->getCountry())->getName() : "",
            "country_code" => $billingAddress->getCountry()
        );
        //company
        if ($billingAddress->getCompany()) {
            $data["customer"]["company"] = $billingAddress->getCompany();
        }
        //customer orders data
        $orderCollection = Mage::getModel('sales/order')->getCollection()
            ->addFieldToFilter('state', array('eq' => 'complete'))
            ->addAttributeToFilter('customer_email', array('eq' => $order->getCustomerEmail()))
            ->addFieldToFilter('mailchimp_sync_delta', array('notnull' => true))
            ->addFieldToFilter('mailchimp_sync_delta', array('neq' => ''))
            ->addFieldToFilter('mailchimp_sync_delta', array('gt' => Mage::helper('mailchimp')->getMCMinSyncDateFlag()))
            ->addFieldToFilter('mailchimp_sync_error', array('eq' => ""));
        $totalOrders = 1;
        $totalAmountSpent = (int)$order->getGrandTotal();
        foreach ($orderCollection as $orderAlreadySent) {
            $totalOrders++;
            $totalAmountSpent += (int)$orderAlreadySent->getGrandTotal();
        }
        $data["customer"]["orders_count"] = $totalOrders;
        $data["customer"]["total_spent"] = $totalAmountSpent;
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

    /**
     * Get Api Object
     *
     * @return Ebizmarts_Mailchimp|null
     */
    protected function _getApi()
    {
        if (!$this->_api) {
            $this->_api = Mage::helper('mailchimp')->getApi();
        }
        return $this->_api;
    }
}