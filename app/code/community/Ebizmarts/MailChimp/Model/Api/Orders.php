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
    const PAID = 'paid';
    const PARTIALLY_PAID = 'parially_paid';
    const SHIPPED = 'shipped';
    const PARTIALLY_SHIPPED = 'parially_shipped';
    const PENDING = 'pending';
    const REFUNDED = 'refunded';
    const PARTIALLY_REFUNDED = 'partially_refunded';
    const CANCELED = 'canceled';
    const COMPLETE = 'complete';
    protected $_firstDate;
    protected $_counter;
    protected $_batchId;
    protected $_api = null;

    /**
     * Set the request for orders to be created on MailChimp
     *
     * @param $mailchimpStoreId
     * @return array
     */
    public function createBatchJson($mailchimpStoreId)
    {
        $batchArray = array();
        $this->_firstDate = Mage::getStoreConfig(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_FIRSTDATE);
        $this->_counter = 0;
        $this->_batchId = Ebizmarts_MailChimp_Model_Config::IS_ORDER . '_' . Mage::helper('mailchimp')->getDateMicrotime();

        // get all the carts modified but not converted in orders
        $batchArray = array_merge($batchArray, $this->_getModifiedOrders($mailchimpStoreId));
        // get new carts
        $batchArray = array_merge($batchArray, $this->_getNewOrders($mailchimpStoreId));

        return $batchArray;
    }

    protected function _getModifiedOrders($mailchimpStoreId)
    {
        $batchArray = array();
        //create missing products first
        $collection = Mage::getModel('sales/order')->getCollection()
            ->addAttributeToSelect('entity_id')
            ->addFieldToFilter('mailchimp_sync_delta', array('gt' => Mage::helper('mailchimp')->getMCMinSyncDateFlag()))
            ->addFieldToFilter('mailchimp_sync_modified', array('eq' => 1));
        if ($this->_firstDate) {
            $collection->addFieldToFilter('created_at', array('from' => $this->_firstDate));
        }

        $collection->getSelect()->limit(self::BATCH_LIMIT);

        foreach ($collection as $item) {
            try {
                $order = Mage::getModel('sales/order')->load($item->getEntityId());
                $productData = Mage::getModel('mailchimp/api_products')->sendModifiedProduct($order, $mailchimpStoreId);
                if (count($productData)) {
                    foreach ($productData as $p) {
                        $batchArray[$this->_counter] = $p;
                        $this->_counter++;
                    }
                }

                $orderJson = $this->GeneratePOSTPayload($order, $mailchimpStoreId);
                if (!empty($orderJson)) {
                    $batchArray[$this->_counter]['method'] = "PATCH";
                    $batchArray[$this->_counter]['path'] = '/ecommerce/stores/' . $mailchimpStoreId . '/orders/' . $order->getEntityId();
                    $batchArray[$this->_counter]['operation_id'] = $this->_batchId . '_' . $order->getEntityId();
                    $batchArray[$this->_counter]['body'] = $orderJson;
                } else {
                    $error = Mage::helper('mailchimp')->__('Something went wrong when retreiving product information.');
                    $order->setData("mailchimp_sync_error", $error);
                }

                //update order delta
                $order->setData("mailchimp_sync_delta", Varien_Date::now());
                $order->setMailchimpSyncModified(0);
                $order->setMailchimpUpdateObserverRan(true);
                $order->save();
                $this->_counter++;
            } catch (Exception $e) {
                Mage::helper('mailchimp')->logError($e->getMessage());
            }
        }

        return $batchArray;
    }

    protected function _getNewOrders($mailchimpStoreId)
    {
        $batchArray = array();
        //create missing products first
        $collection = Mage::getModel('sales/order')->getCollection()
            ->addAttributeToSelect('entity_id')
            ->addFieldToFilter(
                'mailchimp_sync_delta', array(
                array('null' => true),
                array('eq' => ''),
                array('lt' => Mage::helper('mailchimp')->getMCMinSyncDateFlag())
                )
            );
        if ($this->_firstDate) {
            $collection->addFieldToFilter('created_at', array('from' => $this->_firstDate));
        }

        $collection->getSelect()->limit(self::BATCH_LIMIT);

        foreach ($collection as $item) {
            try {
                $order = Mage::getModel('sales/order')->load($item->getEntityId());
                $productData = Mage::getModel('mailchimp/api_products')->sendModifiedProduct($order, $mailchimpStoreId);
                if (count($productData)) {
                    foreach ($productData as $p) {
                        $batchArray[$this->_counter] = $p;
                        $this->_counter++;
                    }
                }

                $orderJson = $this->GeneratePOSTPayload($order, $mailchimpStoreId);
                if (!empty($orderJson)) {
                    $batchArray[$this->_counter]['method'] = "POST";
                    $batchArray[$this->_counter]['path'] = '/ecommerce/stores/' . $mailchimpStoreId . '/orders';
                    $batchArray[$this->_counter]['operation_id'] = $this->_batchId . '_' . $order->getEntityId();
                    $batchArray[$this->_counter]['body'] = $orderJson;
                } else {
                    $error = Mage::helper('mailchimp')->__('Something went wrong when retreiving product information.');
                    $order->setData("mailchimp_sync_error", $error);
                }

                //update order delta
                $order->setData("mailchimp_sync_delta", Varien_Date::now());
                $order->setMailchimpSyncModified(0);
                $order->setMailchimpUpdateObserverRan(true);
                $order->save();
                $this->_counter++;
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
//    public function createCanceledBatchJson($mailchimpStoreId)
//    {
//        //create missing products first
//        $collection = Mage::getModel('sales/order')->getCollection()
//            ->addAttributeToSelect('status')
//            ->addAttributeToSelect('mailchimp_sync_delta')
//            ->addAttributeToSelect('entity_id')
//            ->addFieldToFilter('state', 'canceled')
//            ->addFieldToFilter(
//                'mailchimp_sync_delta', array(
//                array('null' => true),
//                array('eq' => ''),
//                array('lt' => Mage::helper('mailchimp')->getMCMinSyncDateFlag())
//                )
//            );
//        $collection->getSelect()->limit(self::BATCH_LIMIT);
//
//        $batchArray = array();
//        $counter = 0;
//        foreach ($collection as $order) {
//            if (!empty($orderJson)) {
//                $batchArray[$counter]['method'] = "DELETE";
//                $batchArray[$counter]['path'] = '/ecommerce/stores/' . $mailchimpStoreId . '/orders/' . $order->getEntityId();
//
//                //update order delta
//                $order->setData("mailchimp_sync_delta", Varien_Date::now());
//                $order->save();
//            }
//            $counter++;
//        }
//
//        return $batchArray;
//    }

    /**
     * Set all the data for each order to be sent
     *
     * @param $order
     * @param $mailchimpStoreId
     * @return string
     */
    protected function GeneratePOSTPayload($order, $mailchimpStoreId)
    {
//        $order = Mage::getModel('sales/order')->load($orderFromCollection->getEntityId());

        $data = array();
        $data['id'] = $order->getEntityId();
        if ($order->getMailchimpCampaignId()) {
            $data['campaign_id'] = $order->getMailchimpCampaignId();
        }

        if ($order->getMailchimpLandingPage()) {
            $data['landing_site'] = $order->getMailchimpLandingPage();
        }

        $data['currency_code'] = $order->getOrderCurrencyCode();
        $data['order_total'] = $order->getGrandTotal();
        $data['tax_total'] = $order->getTaxAmount();
        $data['discount_total'] = abs($order->getDiscountAmount());
        $data['shipping_total'] = $order->getShippingAmount();
        $statusArray = $this->_getMailChimpStatus($order);
        if (isset($statusArray['financial_status'])) {
            $data['financial_status'] = $statusArray['financial_status'];
        }

        if (isset($statusArray['fulfillment_status'])) {
            $data['fulfillment_status'] = $statusArray['fulfillment_status'];
        }

        $data['processed_at_foreign'] = $order->getCreatedAt();
        $data['updated_at_foreign'] = $order->getUpdatedAt();
        if ($order->getState() == Mage_Sales_Model_Order::STATE_CANCELED) {
            $orderCancelDate = null;
            $commentCollection = $order->getStatusHistoryCollection();
            foreach ($commentCollection as $comment) {
                if ($comment->getStatus() === Mage_Sales_Model_Order::STATE_CANCELED) {
                    $orderCancelDate = $comment->getCreatedAt();
                }
            }

            if ($orderCancelDate) {
                $data['cancelled_at_foreign'] = $orderCancelDate;
            }
        }

        $data['lines'] = array();

        //order lines
        $items = $order->getAllVisibleItems();
        $itemCount = 0;
        foreach ($items as $item) {
            if ($item->getProductType() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
                $options = $item->getProductOptions();
                $sku = $options['simple_sku'];
                $variant = Mage::getModel('catalog/product')->getIdBySku($sku);
                if (!$variant) {
                    continue;
                }
            } else {
                $variant = $item->getProductId();
            }

            // load the product and check if the product was already sent to mailchimp
            $syncDelta = Mage::getResourceModel('catalog/product')->getAttributeRawValue($item->getProductId(), 'mailchimp_sync_delta', $order->getStoreId());
            $syncError = Mage::getResourceModel('catalog/product')->getAttributeRawValue($item->getProductId(), 'mailchimp_sync_error', $order->getStoreId());

            if ($syncDelta && $syncError == 0) {
                $itemCount++;
                $data["lines"][] = array(
                    "id" => (string)$itemCount,
                    "product_id" => $item->getProductId(),
                    "product_variant_id" => $variant,
                    "quantity" => (int)$item->getQtyOrdered(),
                    "price" => $item->getPrice(),
                    "discount" => $item->getDiscountAmount()
                );
            }
        }

        if (!$itemCount) {
            unset($data['lines']);
            return "";
        }

        //customer data
        $api = $this->_getApi();
        $customers = array();
        try {
            $customers = $api->ecommerce->customers->getByEmail($mailchimpStoreId, $order->getCustomerEmail());
        } catch (Mailchimp_Error $e) {
            Mage::helper('mailchimp')->logError($e->getFriendlyMessage());
        }

        if (!$this->_isModifiedOrder($order)) {
            if (isset($customers['total_items']) && $customers['total_items'] > 0) {
                $id = $customers['customers'][0]['id'];
                $data['customer'] = array(
                    'id' => $id
                );
//            $guestCustomer = Mage::getModel('mailchimp/api_customers')->createGuestCustomer($id, $order);
//            $mergeFields = Mage::getModel('mailchimp/api_customers')->getMergeVars($guestCustomer);
//            if (is_array($mergeFields)) {
//                $data['customer'] = array_merge($mergeFields, $data['customer']);
//            }
            } else {
                if ((bool)$order->getCustomerIsGuest()) {
                    $guestId = "GUEST-" . Mage::helper('mailchimp')->getDateMicrotime();
                    $data["customer"] = array(
                        "id" => $guestId,
                        "email_address" => $order->getCustomerEmail(),
                        "opt_in_status" => false
                    );
//                $guestCustomer = Mage::getModel('mailchimp/api_customers')->createGuestCustomer($guestId, $order);
//                $mergeFields = Mage::getModel('mailchimp/api_customers')->getMergeVars($guestCustomer);
//                if (is_array($mergeFields)) {
//                    $data['customer'] = array_merge($mergeFields, $data['customer']);
//                }
                } else {
                    $custEmailAddr = null;
                    try {
                        $customer = $api->ecommerce->customers->get($mailchimpStoreId, $order->getCustomerId(), 'email_address');
                        if (isset($customer['email_address'])) {
                            $custEmailAddr = $customer['email_address'];
                        }
                    } catch (Mailchimp_Error $e) {
                    }

                    $data["customer"] = array(
                        "id" => ($order->getCustomerId()) ? $order->getCustomerId() : $guestId = "CUSTOMER-" . Mage::helper('mailchimp')->getDateMicrotime(),
                        "email_address" => ($custEmailAddr) ? $custEmailAddr : $order->getCustomerEmail(),
                        "opt_in_status" => Mage::getModel('mailchimp/api_customers')->getOptin()
                    );
                }
            }
        } else {
            if (isset($customers['customers'][0]['id'])) {
                $id = $customers['customers'][0]['id'];
                $data['customer'] = array(
                    'id' => $id
                );
            }
        }

        $store = Mage::getModel('core/store')->load($order->getStoreId());
        $data['order_url'] = $store->getUrl(
            'sales/order/view/', array(
            'order_id' => $order->getId(),
            '_nosid' => true,
            '_secure' => true
            )
        );
        if ($order->getCustomerFirstname()) {
            $data["customer"]["first_name"] = $order->getCustomerFirstname();
        }

        if ($order->getCustomerLastname()) {
            $data["customer"]["last_name"] = $order->getCustomerLastname();
        }

        $billingAddress = $order->getBillingAddress();
        $street = $billingAddress->getStreet();
        $address = array();

        if ($street[0]) {
            $address["address1"] = $street[0];
            $data['billing_address']["address1"] = $street[0];
        }

        if (count($street) > 1) {
            $address["address2"] = $street[1];
            $data['billing_address']["address2"] = $street[1];
        }

        if ($billingAddress->getCity()) {
            $address["city"] = $billingAddress->getCity();
            $data['billing_address']["city"] = $billingAddress->getCity();
        }

        if ($billingAddress->getRegion()) {
            $address["province"] = $billingAddress->getRegion();
            $data['billing_address']["province"] = $billingAddress->getRegion();
        }

        if ($billingAddress->getRegionCode()) {
            $address["province_code"] = $billingAddress->getRegionCode();
            $data['billing_address']["province_code"] = $billingAddress->getRegionCode();
        }

        if ($billingAddress->getPostcode()) {
            $address["postal_code"] = $billingAddress->getPostcode();
            $data['billing_address']["postal_code"] = $billingAddress->getPostcode();
        }

        if ($billingAddress->getCountry()) {
            $address["country"] = Mage::getModel('directory/country')->loadByCode($billingAddress->getCountry())->getName();
            $address["country_code"] = $billingAddress->getCountry();
            $data['billing_address']["country"] = Mage::getModel('directory/country')->loadByCode($billingAddress->getCountry())->getName();
            $data['billing_address']["country_code"] = $billingAddress->getCountry();
        }

        if (count($address)) {
            $data["customer"]["address"] = $address;
        }

        if ($billingAddress->getName()) {
            $data['billing_address']['name'] = $billingAddress->getName();
        }

        //company
        if ($billingAddress->getCompany()) {
            $data["customer"]["company"] = $billingAddress->getCompany();
            $data["billing_address"]["company"] = $billingAddress->getCompany();
        }

        $shippingAddress = $order->getShippingAddress();
        if ($shippingAddress) {
            $street = $shippingAddress->getStreet();
            if ($shippingAddress->getName()) {
                $data['shipping_address']['name'] = $shippingAddress->getName();
            }

            if (isset($street[0]) && $street[0]) {
                $data['shipping_address']['address1'] = $street[0];
            }

            if (isset($street[1]) && $street[1]) {
                $data['shipping_address']['address2'] = $street[1];
            }

            if ($shippingAddress->getCity()) {
                $data['shipping_address']['city'] = $shippingAddress->getCity();
            }

            if ($shippingAddress->getRegion()) {
                $data['shipping_address']['province'] = $shippingAddress->getRegion();
            }

            if ($shippingAddress->getRegionCode()) {
                $data['shipping_address']['province_code'] = $shippingAddress->getRegionCode();
            }

            if ($shippingAddress->getPostcode()) {
                $data['shipping_address']['postal_code'] = $shippingAddress->getPostcode();
            }

            if ($shippingAddress->getCountry()) {
                $data['shipping_address']['country'] = Mage::getModel('directory/country')->loadByCode($shippingAddress->getCountry())->getName();
                $data['shipping_address']['country_code'] = $shippingAddress->getCountry();
            }

            if ($shippingAddress->getCompamy()) {
                $data["shipping_address"]["company"] = $shippingAddress->getCompany();
            }
        }

        //customer orders data
        $orderCollection = Mage::getModel('sales/order')->getCollection()
            ->addFieldToFilter('state', array('eq' => 'complete'))
            ->addAttributeToFilter('customer_email', array('eq' => $order->getCustomerEmail()))
            ->addFieldToFilter('mailchimp_sync_delta', array('notnull' => true))
            ->addFieldToFilter('mailchimp_sync_delta', array('neq' => ''))
            ->addFieldToFilter('mailchimp_sync_delta', array('gt' => Mage::helper('mailchimp')->getMCMinSyncDateFlag()))
            ->addFieldToFilter('mailchimp_sync_error', array('eq' => ""));
        if ($this->_firstDate) {
            $orderCollection->addFieldToFilter('created_at', array('from' => $this->_firstDate));
        }

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
            Mage::helper('mailchimp')->logError("Order " . $order->getEntityId() . " json encode failed");
        }

        return $jsonData;
    }

    /**
     * Return true if order has been already sent to MailChimp and has been modified afterwards.
     *
     * @param $order
     * @return bool
     */
    protected function _isModifiedOrder($order)
    {
        return ($order->getMailchimpSyncModified() && $order->getMailchimpSyncDelta() > Mage::helper('mailchimp')->getMCMinSyncDateFlag());
    }

    protected function _getMailChimpStatus($order)
    {
        $mailChimpFinancialStatus = null;
        $mailChimpFulfillmentStatus = null;
        $totalItemsOrdered = $order->getData('total_qty_ordered');
        $shippedItemAmount = 0;
        $invoicedItemAmount = 0;
        $refundedItemAmount = 0;
        $mailChimpStatus = array();

        foreach ($order->getAllVisibleItems() as $item) {
            $shippedItemAmount += $item->getQtyShipped();
            $invoicedItemAmount += $item->getQtyInvoiced();
            $refundedItemAmount += $item->getQtyRefunded();
        }

        if ($shippedItemAmount > 0) {
            if ($totalItemsOrdered > $shippedItemAmount) {
                $mailChimpFulfillmentStatus = self::PARTIALLY_SHIPPED;
            } else {
                $mailChimpFulfillmentStatus = self::SHIPPED;
            }
        }

        if ($refundedItemAmount > 0) {
            if ($totalItemsOrdered > $refundedItemAmount) {
                $mailChimpFinancialStatus = self::PARTIALLY_REFUNDED;
            } else {
                $mailChimpFinancialStatus = self::REFUNDED;
            }
        }

        if ($invoicedItemAmount > 0) {
            if ($refundedItemAmount == 0 || $refundedItemAmount != $invoicedItemAmount) {
                if ($totalItemsOrdered > $invoicedItemAmount) {
                    $mailChimpFinancialStatus = self::PARTIALLY_PAID;
                } else {
                    $mailChimpFinancialStatus = self::PAID;
                }
            }
        }

        if (!$mailChimpFinancialStatus && $order->getState() == Mage_Sales_Model_Order::STATE_CANCELED) {
            $mailChimpFinancialStatus = self::CANCELED;
        }

        if (!$mailChimpFinancialStatus) {
            $mailChimpFinancialStatus = self::PENDING;
        }

        if ($mailChimpFinancialStatus) {
            $mailChimpStatus['financial_status'] = $mailChimpFinancialStatus;
        }

        if ($mailChimpFulfillmentStatus) {
            $mailChimpStatus['fulfillment_status'] = $mailChimpFulfillmentStatus;
        }

        return $mailChimpStatus;
    }

    public function update($order)
    {
        if (Mage::helper('mailchimp')->isEcomSyncDataEnabled()) {
            $order->setData('mailchimp_sync_error', '');
            $order->setData('mailchimp_sync_modified', 1);
        }
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