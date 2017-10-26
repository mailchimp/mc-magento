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
class Ebizmarts_MailChimp_Model_Api_Orders
{

    const BATCH_LIMIT = 50;
    const BATCH_LIMIT_ONLY_ORDERS = 500;
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
     * @param  $mailchimpStoreId
     * @param  $magentoStoreId
     * @return array
     */
    public function createBatchJson($mailchimpStoreId, $magentoStoreId)
    {
        $helper = $this->getHelper();
        $batchArray = array();
        $this->_firstDate = $helper->getEcommerceFirstDate($magentoStoreId);
        $this->_counter = 0;
        $this->_batchId = 'storeid-' . $magentoStoreId . '_' . Ebizmarts_MailChimp_Model_Config::IS_ORDER . '_' . Mage::helper('mailchimp')->getDateMicrotime();
        $resendTurn = $helper->getResendTurn($magentoStoreId);
        if (!$resendTurn) {
            // get all the orders modified
            $batchArray = array_merge($batchArray, $this->_getModifiedOrders($mailchimpStoreId, $magentoStoreId));
        }
        // get new orders
        $batchArray = array_merge($batchArray, $this->_getNewOrders($mailchimpStoreId, $magentoStoreId));

        return $batchArray;
    }

    protected function _getModifiedOrders($mailchimpStoreId, $magentoStoreId)
    {
        $helper = $this->getHelper();
        $mailchimpTableName = Mage::getSingleton('core/resource')->getTableName('mailchimp/ecommercesyncdata');
        $batchArray = array();
        $modifiedOrders = Mage::getResourceModel('sales/order_collection');
        // select orders for the current Magento store id
        $modifiedOrders->addFieldToFilter('store_id', array('eq' => $magentoStoreId));
        //join with mailchimp_ecommerce_sync_data table to filter by sync data.
        $modifiedOrders->getSelect()->joinLeft(
            array('m4m' => $mailchimpTableName),
            "m4m.related_id = main_table.entity_id AND m4m.type = '" . Ebizmarts_MailChimp_Model_Config::IS_ORDER . "'
            AND m4m.mailchimp_store_id = '" . $mailchimpStoreId . "'",
            array('m4m.*')
        );
        // be sure that the order are already in mailchimp and not deleted
        $modifiedOrders->getSelect()->where("m4m.mailchimp_sync_modified = 1");
        // limit the collection
        $modifiedOrders->getSelect()->limit($this->getBatchLimitFromConfig());

        foreach ($modifiedOrders as $item) {
            try {
                $orderId = $item->getEntityId();
                $order = Mage::getModel('sales/order')->load($orderId);
                $incrementId = $order->getIncrementId();
                //create missing products first
                $batchArray = $this->addProductNotSentData($mailchimpStoreId, $magentoStoreId, $order, $batchArray);

                $orderJson = $this->GeneratePOSTPayload($order, $mailchimpStoreId, $magentoStoreId, true);
                if (!empty($orderJson)) {
                    $batchArray[$this->_counter]['method'] = "PATCH";
                    $batchArray[$this->_counter]['path'] = '/ecommerce/stores/' . $mailchimpStoreId . '/orders/' . $incrementId;
                    $batchArray[$this->_counter]['operation_id'] = $this->_batchId . '_' . $orderId;
                    $batchArray[$this->_counter]['body'] = $orderJson;
                    //update order delta
                    $this->_updateSyncData($orderId, $mailchimpStoreId, Varien_Date::now());
                    $this->_counter++;
                } else {
                    $error = $helper->__('Something went wrong when retrieving product information.');
                    $this->_updateSyncData($orderId, $mailchimpStoreId, Varien_Date::now(), $error);
                    continue;
                }
            } catch (Exception $e) {
                Mage::helper('mailchimp')->logError($e->getMessage(), $magentoStoreId);
            }
        }

        return $batchArray;
    }

    protected function _getNewOrders($mailchimpStoreId, $magentoStoreId)
    {
        $helper = $this->getHelper();
        $batchArray = array();
        $newOrders = Mage::getResourceModel('sales/order_collection');
        // select carts for the current Magento store id
        $newOrders->addFieldToFilter('store_id', array('eq' => $magentoStoreId));
        $helper->addResendFilter($newOrders, $magentoStoreId);
        // filter by first date if exists.
        if ($this->_firstDate) {
            $newOrders->addFieldToFilter('created_at', array('gt' => $this->_firstDate));
        }

        $this->joinMailchimpSyncDataWithoutWhere($newOrders, $mailchimpStoreId);
        // be sure that the orders are not in mailchimp
        $newOrders->getSelect()->where("m4m.mailchimp_sync_delta IS NULL");
        // limit the collection
        $newOrders->getSelect()->limit($this->getBatchLimitFromConfig());

        foreach ($newOrders as $item) {
            try {
                $orderId = $item->getEntityId();
                $order = Mage::getModel('sales/order')->load($orderId);
                //create missing products first
                $batchArray = $this->addProductNotSentData($mailchimpStoreId, $magentoStoreId, $order, $batchArray);

                $orderJson = $this->GeneratePOSTPayload($order, $mailchimpStoreId, $magentoStoreId);
                if (!empty($orderJson)) {
                    $batchArray[$this->_counter]['method'] = "POST";
                    $batchArray[$this->_counter]['path'] = '/ecommerce/stores/' . $mailchimpStoreId . '/orders';
                    $batchArray[$this->_counter]['operation_id'] = $this->_batchId . '_' . $orderId;
                    $batchArray[$this->_counter]['body'] = $orderJson;
                    //update order delta
                    $this->_updateSyncData($orderId, $mailchimpStoreId, Varien_Date::now());
                    $this->_counter++;
                } else {
                    $error = Mage::helper('mailchimp')->__('Something went wrong when retrieving product information.');
                    $this->_updateSyncData($orderId, $mailchimpStoreId, Varien_Date::now(), $error);
                    continue;
                }
            } catch (Exception $e) {
                Mage::helper('mailchimp')->logError($e->getMessage(), $magentoStoreId);
            }
        }

        return $batchArray;
    }

    /**
     * Set all the data for each order to be sent
     *
     * @param  $order
     * @param  $mailchimpStoreId
     * @param  $magentoStoreId
     * @param  $isModifiedOrder
     * @return string
     */
    protected function GeneratePOSTPayload($order, $mailchimpStoreId, $magentoStoreId, $isModifiedOrder = false)
    {
        $data = array();
        $data['id'] = $order->getIncrementId();
        if ($order->getMailchimpCampaignId()) {
            $data['campaign_id'] = $order->getMailchimpCampaignId();
        }

        if ($order->getMailchimpLandingPage()) {
            $data['landing_site'] = $order->getMailchimpLandingPage();
        }

        $data['currency_code'] = $order->getStoreCurrencyCode();
        $data['order_total'] = $order->getBaseGrandTotal();
        $data['tax_total'] = $this->returnZeroIfNull($order->getBaseTaxAmount());
        $data['discount_total'] = abs($order->getBaseDiscountAmount());
        $data['shipping_total'] = $this->returnZeroIfNull($order->getBaseShippingAmount());
        $data['promos'] = $this->getPromoData();
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
            $productId = $item->getProductId();
            $productSyncData = Mage::helper('mailchimp')->getEcommerceSyncDataItem($productId, Ebizmarts_MailChimp_Model_Config::IS_PRODUCT, $mailchimpStoreId);
            if ($item->getProductType() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
                $options = $item->getProductOptions();
                $sku = $options['simple_sku'];
                $variant = Mage::getModel('catalog/product')->getIdBySku($sku);
                if (!$variant) {
                    continue;
                }
            } elseif ($item->getProductType() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE || $item->getProductType() == Mage_Catalog_Model_Product_Type::TYPE_GROUPED) {
                continue;
            } else {
                $variant = $productId;
            }

            if ($productSyncData->getMailchimpSyncDelta() && $productSyncData->getMailchimpSyncError() == '') {
                $itemCount++;
                $data["lines"][] = array(
                    "id" => (string)$itemCount,
                    "product_id" => $productId,
                    "product_variant_id" => $variant,
                    "quantity" => (int)$item->getQtyOrdered(),
                    "price" => $item->getPrice(),
                    "discount" => abs($item->getDiscountAmount())
                );
            }
        }

        if (!$itemCount) {
            unset($data['lines']);
            return "";
        }

        //customer data
        $api = Mage::helper('mailchimp')->getApi($magentoStoreId);
        $customers = array();
        try {
            $customers = $api->ecommerce->customers->getByEmail($mailchimpStoreId, $order->getCustomerEmail());
        } catch (MailChimp_Error $e) {
            Mage::helper('mailchimp')->logError($e->getFriendlyMessage(), $magentoStoreId);
        }

        if (!$isModifiedOrder) {
            if (isset($customers['total_items']) && $customers['total_items'] > 0) {
                $id = $customers['customers'][0]['id'];
                $data['customer'] = array(
                    'id' => $id
                );
            } else {
                if ((bool)$order->getCustomerIsGuest()) {
                    $guestId = "GUEST-" . Mage::helper('mailchimp')->getDateMicrotime();
                    $data["customer"] = array(
                        "id" => $guestId,
                        "email_address" => $order->getCustomerEmail(),
                        "opt_in_status" => false
                    );
                } else {
                    $custEmailAddr = null;
                    try {
                        $customer = $api->ecommerce->customers->get($mailchimpStoreId, $order->getCustomerId(), 'email_address');
                        if (isset($customer['email_address'])) {
                            $custEmailAddr = $customer['email_address'];
                        }
                    } catch (MailChimp_Error $e) {
                    }

                    $data["customer"] = array(
                        "id" => ($order->getCustomerId()) ? $order->getCustomerId() : $guestId = "CUSTOMER-" . Mage::helper('mailchimp')->getDateMicrotime(),
                        "email_address" => ($custEmailAddr) ? $custEmailAddr : $order->getCustomerEmail(),
                        "opt_in_status" => Mage::getModel('mailchimp/api_customers')->getOptin($magentoStoreId)
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

        $store = Mage::getModel('core/store')->load($magentoStoreId);
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
        if ($billingAddress) {
            $street = $billingAddress->getStreet();
            $address = array();
            if ($street[0]) {
                $address["address1"] = $data['billing_address']["address1"] = $street[0];
            }

            if (count($street) > 1) {
                $address["address2"] = $data['billing_address']["address2"] = $street[1];
            }

            if ($billingAddress->getCity()) {
                $address["city"] = $data['billing_address']["city"] = $billingAddress->getCity();
            }

            if ($billingAddress->getRegion()) {
                $address["province"] = $data['billing_address']["province"] = $billingAddress->getRegion();
            }

            if ($billingAddress->getRegionCode()) {
                $address["province_code"] = $data['billing_address']["province_code"] = $billingAddress->getRegionCode();
            }

            if ($billingAddress->getPostcode()) {
                $address["postal_code"] = $data['billing_address']["postal_code"] = $billingAddress->getPostcode();
            }

            if ($billingAddress->getCountry()) {
                $countryName = Mage::getModel('directory/country')->loadByCode($billingAddress->getCountry())->getName();
                $address["country"] = $data['billing_address']["country"] = $countryName;
                $address["country_code"] = $data['billing_address']["country_code"] = $billingAddress->getCountry();
            }

            if (count($address)) {
                $data["customer"]["address"] = $address;
            }

            if ($billingAddress->getName()) {
                $data['billing_address']['name'] = $billingAddress->getName();
            }

            //company
            if ($billingAddress->getCompany()) {
                $data["customer"]["company"] = $data["billing_address"]["company"] = $billingAddress->getCompany();
            }
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
        $orderCollection = Mage::getResourceModel('sales/order_collection')
            ->addFieldToFilter(
                'state',
                array(
                    array('neq' => Mage_Sales_Model_Order::STATE_CANCELED),
                    array('neq' => Mage_Sales_Model_Order::STATE_CLOSED)
                )
            )
            ->addAttributeToFilter('customer_email', array('eq' => $order->getCustomerEmail()));
        $totalOrders = 0;
        $totalAmountSpent = (int)$order->getGrandTotal();
        foreach ($orderCollection as $customerOrder) {
            $totalOrders++;
            $totalAmountSpent += ($customerOrder->getGrandTotal() - $customerOrder->getTotalRefunded() - $customerOrder->getTotalCanceled());
        }

        $data["customer"]["orders_count"] = (int)$totalOrders;
        $data["customer"]["total_spent"] = $totalAmountSpent;
        $jsonData = "";
        //enconde to JSON
        try {
            $jsonData = json_encode($data);
        } catch (Exception $e) {
            //json encode failed
            Mage::helper('mailchimp')->logError("Order " . $order->getEntityId() . " json encode failed", $magentoStoreId);
        }

        return $jsonData;
    }

    /**
     * @return mixed
     */
    protected function getBatchLimitFromConfig()
    {
        $helper = $this->getHelper();
        return $helper->getOrderAmountLimit();
    }

    protected function returnZeroIfNull($value)
    {
        $returnValue = $value;
        if ($value === null) {
            $returnValue = 0;
        }

        return $returnValue;
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

    /**
     * @param $orderId
     * @param $magentoStoreId
     */
    public function update($orderId, $magentoStoreId)
    {
        if (Mage::helper('mailchimp')->isEcomSyncDataEnabled($magentoStoreId)) {
            $mailchimpStoreId = Mage::helper('mailchimp')->getMCStoreId($magentoStoreId);
            $this->_updateSyncData($orderId, $mailchimpStoreId, null, null, 1, true);
        }
    }

    /**
     * update customer sync data
     *
     * @param $orderId
     * @param $mailchimpStoreId
     * @param null $syncDelta
     * @param null $syncError
     * @param int $syncModified
     * @param bool $saveOnlyIfexists
     */
    protected function _updateSyncData($orderId, $mailchimpStoreId, $syncDelta = null, $syncError = null, $syncModified = 0, $saveOnlyIfexists = false)
    {
        Mage::helper('mailchimp')->saveEcommerceSyncData($orderId, Ebizmarts_MailChimp_Model_Config::IS_ORDER, $mailchimpStoreId, $syncDelta, $syncError, $syncModified, null, null, $saveOnlyIfexists);
    }

    /**
     * Replace all orders with old id with the increment id on MailChimp.
     *
     * @param  $initialTime
     * @param  $mailchimpStoreId
     * @param  $magentoStoreId
     * @return array
     */
    public function replaceAllOrdersBatch($initialTime, $mailchimpStoreId, $magentoStoreId)
    {
        $this->_counter = 0;
        $this->_batchId = 'storeid-' . $magentoStoreId . '_' . Ebizmarts_MailChimp_Model_Config::IS_ORDER . '_' . Mage::helper('mailchimp')->getDateMicrotime();
        $lastId = Mage::helper('mailchimp')->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::GENERAL_MIGRATE_LAST_ORDER_ID, $magentoStoreId, 'stores');
        $mailchimpTableName = Mage::getSingleton('core/resource')->getTableName('mailchimp/ecommercesyncdata');
        $batchArray = array();
        $config = array();
        $orderCollection = Mage::getResourceModel('sales/order_collection');
        // select carts for the current Magento store id
        $orderCollection->addFieldToFilter('store_id', array('eq' => $magentoStoreId));
        if ($lastId) {
            $orderCollection->addFieldToFilter('entity_id', array('gt' => $lastId));
        }

        $orderCollection->getSelect()->joinLeft(
            array('m4m' => $mailchimpTableName),
            "m4m.related_id = main_table.entity_id AND m4m.type = '" . Ebizmarts_MailChimp_Model_Config::IS_ORDER . "'
            AND m4m.mailchimp_store_id = '" . $mailchimpStoreId . "'",
            array('m4m.*')
        );
        // be sure that the orders are not in mailchimp
        $orderCollection->getSelect()->where("m4m.mailchimp_sync_delta IS NOT NULL AND m4m.mailchimp_sync_error = ''");
        $orderCollection->getSelect()->limit(self::BATCH_LIMIT_ONLY_ORDERS);
        foreach ($orderCollection as $order) {
            //Delete order
            $orderId = $order->getEntityId();
            $config = array(array(Ebizmarts_MailChimp_Model_Config::GENERAL_MIGRATE_LAST_ORDER_ID, $orderId));
            if (!Mage::helper('mailchimp')->timePassed($initialTime)) {
                $batchArray[$this->_counter]['method'] = "DELETE";
                $batchArray[$this->_counter]['path'] = '/ecommerce/stores/' . $mailchimpStoreId . '/orders/' . $orderId;
                $batchArray[$this->_counter]['operation_id'] = $this->_batchId . '_' . $orderId;
                $batchArray[$this->_counter]['body'] = '';
                $this->_counter += 1;

                //Create order
                $orderJson = $this->GeneratePOSTPayload($order, $mailchimpStoreId, $magentoStoreId, true);
                if (!empty($orderJson)) {
                    $batchArray[$this->_counter]['method'] = "POST";
                    $batchArray[$this->_counter]['path'] = '/ecommerce/stores/' . $mailchimpStoreId . '/orders';
                    $batchArray[$this->_counter]['operation_id'] = $this->_batchId . '_' . $orderId;
                    $batchArray[$this->_counter]['body'] = $orderJson;
                    $this->_counter += 1;
                } else {
                    $error = Mage::helper('mailchimp')->__('Something went wrong when retrieving product information during migration from 1.1.6.');
                    $this->_updateSyncData($orderId, $mailchimpStoreId, Varien_Date::now(), $error);
                    continue;
                }
            } else {
                if (!count($batchArray)) {
                    $batchArray[] = Mage::helper('mailchimp')->__('Time passed.');
                }

                Mage::helper('mailchimp')->saveMailchimpConfig($config, $magentoStoreId, 'stores');
                break;
            }
        }

        Mage::helper('mailchimp')->saveMailchimpConfig($config, $magentoStoreId, 'stores');
        return $batchArray;
    }

    /**
     * @param $mailchimpStoreId
     * @param $magentoStoreId
     * @param $order
     * @param $batchArray
     * @return mixed
     */
    public function addProductNotSentData($mailchimpStoreId, $magentoStoreId, $order, $batchArray)
    {
        $productData = Mage::getModel('mailchimp/api_products')->sendModifiedProduct($order, $mailchimpStoreId, $magentoStoreId);
        $productDataArray = Mage::helper('mailchimp')->addEntriesToArray($batchArray, $productData, $this->_counter);
        $batchArray = $productDataArray[0];
        $this->_counter = $productDataArray[1];

        return $batchArray;
    }

    protected function getHelper()
    {
        return Mage::helper('mailchimp');
    }

    /**
     * @param $newOrders
     * @param $mailchimpStoreId
     */
    public function joinMailchimpSyncDataWithoutWhere($newOrders, $mailchimpStoreId)
    {
        $mailchimpTableName = Mage::getSingleton('core/resource')->getTableName('mailchimp/ecommercesyncdata');
        $newOrders->getSelect()->joinLeft(
            array('m4m' => $mailchimpTableName),
            "m4m.related_id = main_table.entity_id AND m4m.type = '" . Ebizmarts_MailChimp_Model_Config::IS_ORDER . "'
            AND m4m.mailchimp_store_id = '" . $mailchimpStoreId . "'",
            array('m4m.*')
        );
    }

    protected function getPromoData()
    {
        return array(array(
            'code' => 'abcd',
            'amount_discounted' => 10,
            'type' => 'percentage'
        ));
    }
}
