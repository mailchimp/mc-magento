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
class Ebizmarts_MailChimp_Model_Api_Orders extends Ebizmarts_MailChimp_Model_Api_SyncItem
{

    const BATCH_LIMIT = 50;
    const BATCH_LIMIT_ONLY_ORDERS = 500;
    const PAID = 'paid';
    const PARTIALLY_PAID = 'partially_paid';
    const SHIPPED = 'shipped';
    const PARTIALLY_SHIPPED = 'partially_shipped';
    const PENDING = 'pending';
    const REFUNDED = 'refunded';
    const PARTIALLY_REFUNDED = 'partially_refunded';
    const CANCELED = 'canceled';
    const COMPLETE = 'complete';
    protected $_firstDate;
    protected $_counter;
    protected $_batchId;
    protected $_api = null;
    protected $_listsCampaignIds = array();

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Set the request for orders to be created on MailChimp
     *
     * @param $mailchimpStoreId
     * @param $magentoStoreId
     * @return array
     * @throws Mage_Core_Exception
     */
    public function createBatchJson($mailchimpStoreId, $magentoStoreId)
    {
        $helper = $this->getHelper();
        $dateHelper = $this->getDateHelper();
        $oldStore = $helper->getCurrentStoreId();
        $helper->setCurrentStore($magentoStoreId);

        $batchArray = array();
        $this->_firstDate = $helper->getEcommerceFirstDate($magentoStoreId);
        $this->_counter = 0;
        $this->_batchId = 'storeid-'
            . $magentoStoreId . '_'
            . Ebizmarts_MailChimp_Model_Config::IS_ORDER
            . '_' . $dateHelper->getDateMicrotime();
        $resendTurn = $helper->getResendTurn($magentoStoreId);

        if (!$resendTurn) {
            // get all the orders modified
            $batchArray = array_merge($batchArray, $this->_getModifiedOrders($mailchimpStoreId, $magentoStoreId));
        }

        // get new orders
        $batchArray = array_merge($batchArray, $this->_getNewOrders($mailchimpStoreId, $magentoStoreId));
        $helper->setCurrentStore($oldStore);

        return $batchArray;
    }

    /**
     * @param $mailchimpStoreId
     * @param $magentoStoreId
     * @return array
     */
    protected function _getModifiedOrders($mailchimpStoreId, $magentoStoreId)
    {
        $helper = $this->getHelper();
        $dateHelper = $this->getDateHelper();
        $mailchimpTableName = $this->getMailchimpEcommerceDataTableName();
        $batchArray = array();
        $modifiedOrders = $this->getResourceModelOrderCollection();
        // select orders for the current Magento store id
        $modifiedOrders->addFieldToFilter('store_id', array('eq' => $magentoStoreId));
        //join with mailchimp_ecommerce_sync_data table to filter by sync data.
        $modifiedOrders->getSelect()->joinLeft(
            array('m4m' => $mailchimpTableName),
            "m4m.related_id = main_table.entity_id AND m4m.type = '"
            . Ebizmarts_MailChimp_Model_Config::IS_ORDER
            . "' AND m4m.mailchimp_store_id = '" . $mailchimpStoreId . "'",
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
                $orderJson = $this->GeneratePOSTPayload($order, $mailchimpStoreId, $magentoStoreId);

                if ($orderJson !== false) {
                    if (!empty($orderJson)) {
                        $helper->modifyCounterSentPerBatch(Ebizmarts_MailChimp_Helper_Data::ORD_MOD);

                        $batchArray[$this->_counter]['method'] = "PATCH";
                        $batchArray[$this->_counter]['path'] = '/ecommerce/stores/' . $mailchimpStoreId
                            . '/orders/' . $incrementId;
                        $batchArray[$this->_counter]['operation_id'] = $this->_batchId . '_' . $orderId;
                        $batchArray[$this->_counter]['body'] = $orderJson;
                        //update order delta
                        $this->addSyncData($orderId, $mailchimpStoreId);
                        $this->_counter++;
                    } else {
                        $error = $helper->__('Something went wrong when retrieving product information.');

                        $this->addSyncDataError(
                            $orderId,
                            $mailchimpStoreId,
                            $error,
                            null,
                            false,
                            $dateHelper->formatDate(null, "Y-m-d H:i:s")
                        );
                        continue;
                    }
                } else {
                    $jsonErrorMsg = json_last_error_msg();$this->logSyncError(
                        "Order " . $order->getEntityId() . " json encode failed (".$jsonErrorMsg.")",
                        Ebizmarts_MailChimp_Model_Config::IS_ORDER,
                        $mailchimpStoreId, $magentoStoreId
                    );

                    $this->addSyncDataError(
                        $orderId,
                        $mailchimpStoreId,
                        $jsonErrorMsg,
                        null,
                        false,
                        $dateHelper->formatDate(null, "Y-m-d H:i:s")
                    );
                }
            } catch (Exception $e) {
                $this->logSyncError(
                    $e->getMessage(),
                    Ebizmarts_MailChimp_Model_Config::IS_ORDER,
                    $mailchimpStoreId, $magentoStoreId
                );
            }
        }

        return $batchArray;
    }

    /**
     * @param $mailchimpStoreId
     * @param $magentoStoreId
     * @return array
     * @throws Mage_Core_Exception
     */
    protected function _getNewOrders($mailchimpStoreId, $magentoStoreId)
    {
        $helper = $this->getHelper();
        $dateHelper = $this->getDateHelper();

        $batchArray = array();
        $newOrders = $this->getResourceModelOrderCollection();
        // select carts for the current Magento store id
        $newOrders->addFieldToFilter('store_id', array('eq' => $magentoStoreId));
        $helper->addResendFilter($newOrders, $magentoStoreId, Ebizmarts_MailChimp_Model_Config::IS_ORDER);
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

                if ($orderJson !== false) {
                    if (!empty($orderJson)) {
                        $helper->modifyCounterSentPerBatch(Ebizmarts_MailChimp_Helper_Data::ORD_NEW);

                        $batchArray[$this->_counter]['method'] = "POST";
                        $batchArray[$this->_counter]['path'] = '/ecommerce/stores/' . $mailchimpStoreId . '/orders';
                        $batchArray[$this->_counter]['operation_id'] = $this->_batchId . '_' . $orderId;
                        $batchArray[$this->_counter]['body'] = $orderJson;
                        //update order delta
                        $this->addSyncData($orderId, $mailchimpStoreId);
                        $this->_counter++;
                    } else {
                        $error = $helper->__('Something went wrong when retrieving product information.');

                        $this->addSyncDataError(
                            $orderId,
                            $mailchimpStoreId,
                            $error,
                            null,
                            false,
                            $dateHelper->formatDate(null, "Y-m-d H:i:s")
                        );
                        continue;
                    }
                } else {
                    $jsonErrorMsg = json_last_error_msg();
                    $this->logSyncError(
                        "Order " . $order->getEntityId() . " json encode failed (".$jsonErrorMsg.")",
                        Ebizmarts_MailChimp_Model_Config::IS_ORDER,
                        $mailchimpStoreId, $magentoStoreId
                    );

                    $this->addSyncDataError(
                        $orderId,
                        $mailchimpStoreId,
                        $jsonErrorMsg,
                        null,
                        false,
                        $dateHelper->formatDate(null, "Y-m-d H:i:s")
                    );
                }
            } catch (Exception $e) {
                $this->logSyncError(
                    $e->getMessage(),
                    Ebizmarts_MailChimp_Model_Config::IS_ORDER,
                    $mailchimpStoreId, $magentoStoreId
                );
            }
        }

        return $batchArray;
    }

    /**
     * Set all the data for each order to be sent
     *
     * @param $order
     * @param $mailchimpStoreId
     * @param $magentoStoreId
     * @return false|string
     * @throws Mage_Core_Model_Store_Exception
     */
    public function GeneratePOSTPayload($order, $mailchimpStoreId, $magentoStoreId)
    {
        $data = $this->_getPayloadData($order, $magentoStoreId);
        $lines = $this->_getPayloadDataLines($order, $mailchimpStoreId, $magentoStoreId);
        $data['lines'] = $lines['lines'];

        if (!$lines['itemsCount']) {
            unset($data['lines']);
            return "";
        }

        //customer data
        $data["customer"]["id"] = md5(strtolower($order->getCustomerEmail()));
        $data["customer"]["email_address"] = $order->getCustomerEmail();
        $data["customer"]["opt_in_status"] = false;

        $subscriber = $this->getSubscriberModel();

        if ($subscriber->getOptIn($magentoStoreId)) {
            $isSubscribed = $subscriber->loadByEmail($order->getCustomerEmail())->getSubscriberId();

            if (!$isSubscribed) {
                $subscriber->subscribe($order->getCustomerEmail());
            }
        }

        $subscriber = null;

        $store = $this->getStoreModelFromMagentoStoreId($magentoStoreId);
        $data['order_url'] = $store->getUrl(
            'sales/order/view/',
            array(
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
            $this->_getPayloadBilling($data, $billingAddress, $street);
        }

       $shippingAddress = $order->getShippingAddress();

        if ($shippingAddress) {
            $this->_getPayloadShipping($data, $shippingAddress);
        }

        $jsonData = "";
        //encode to JSON
        $jsonData = json_encode($data);

        return $jsonData;
    }

    /**
     * @param $order
     * @param $magentoStoreId
     * @return array
     * @throws Exception
     */
    protected function _getPayloadData($order, $magentoStoreId)
    {
        $data = array();
        $data['id'] = $order->getIncrementId();
        $dataPromo = $this->getPromoData($order);
        $mailchimpCampaignId = $order->getMailchimpCampaignId();

        if ($this->shouldSendCampaignId($mailchimpCampaignId, $magentoStoreId)) {
            $data['campaign_id'] = $mailchimpCampaignId;
        }

        if ($order->getMailchimpLandingPage()) {
            $data['landing_site'] = $order->getMailchimpLandingPage();
        }

        $data['currency_code'] = $order->getOrderCurrencyCode();
        $data['order_total'] = $order->getGrandTotal();
        $data['tax_total'] = $this->returnZeroIfNull($order->getTaxAmount());
        $data['discount_total'] = abs($order->getDiscountAmount());
        $data['shipping_total'] = $this->returnZeroIfNull($order->getShippingAmount());

        if ($dataPromo !== null) {
            $data['promos'] = $dataPromo;
        }

        $statusArray = $this->_getMailChimpStatus($order);

        if (isset($statusArray['financial_status'])) {
            $data['financial_status'] = $statusArray['financial_status'];
        }

        if (isset($statusArray['fulfillment_status'])) {
            $data['fulfillment_status'] = $statusArray['fulfillment_status'];
        }

        $data['processed_at_foreign'] = $order->getCreatedAt();
        $data['updated_at_foreign'] = $order->getUpdatedAt();

        if ($this->isOrderCanceled($order)) {
            $orderCancelDate = $this->_processCanceledOrder($order);

            if ($orderCancelDate) {
                $data['cancelled_at_foreign'] = $orderCancelDate;
            }
        }

        return $data;
    }

    /**
     * @param $order
     * @param $mailchimpStoreId
     * @param $magentoStoreId
     * @return array
     */
    protected function _getPayloadDataLines($order, $mailchimpStoreId, $magentoStoreId)
    {
        $helper = $this->getHelper();
        $apiProduct = $this->getApiProduct();

        $lines = array();
        $items = $order->getAllVisibleItems();
        $itemCount = 0;

        foreach ($items as $item) {
            $productId = $item->getProductId();
            $isTypeProduct = $this->isTypeProduct();
            $productSyncData = $helper->getEcommerceSyncDataItem($productId, $isTypeProduct, $mailchimpStoreId);

            if ($this->isItemConfigurable($item)) {
                $options = $item->getProductOptions();
                $sku = $options['simple_sku'];
                $variant = $this->getModelProduct()->getIdBySku($sku);

                if (!$variant) {
                    continue;
                }
            } elseif ($item->getProductType() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE
                || $item->getProductType() == Mage_Catalog_Model_Product_Type::TYPE_GROUPED
            ) {
                continue;
            } else {
                $variant = $productId;
            }

            $productSyncError = $productSyncData->getMailchimpSyncError();
            $isProductEnabled = $apiProduct->isProductEnabled($productId, $magentoStoreId);

            if (!$isProductEnabled || ($productSyncData->getMailchimpSyncDelta() && $productSyncError == '')) {
                $itemCount++;
                $lines[] = array(
                    "id" => (string)$itemCount,
                    "product_id" => $productId,
                    "product_variant_id" => $variant,
                    "quantity" => (int)$item->getQtyOrdered(),
                    "price" => $item->getPrice(),
                    "discount" => abs($item->getDiscountAmount())
                );

                if (!$isProductEnabled) {
                    // update disabled products to remove the product from mailchimp after sending the order
                    $apiProduct->updateDisabledProducts($productId, $mailchimpStoreId);
                }
            }
        }

        return array('lines' => $lines, 'itemsCount' => $itemCount);
    }

    /**
     * @param $data
     * @param $billingAddress
     * @param $street
     */
    protected function _getPayloadBilling($data, $billingAddress, $street)
    {
        $address = array();

        $this->_getPayloadBillingStreet($data, $address, $street);

        if ($billingAddress->getCity()) {
            $address["city"] = $data['billing_address']["city"] = $billingAddress->getCity();
        }

        if ($billingAddress->getRegion()) {
            $address["province"] = $data['billing_address']["province"] = $billingAddress->getRegion();
        }

        if ($billingAddress->getRegionCode()) {
            $address["province_code"] =
            $data['billing_address']["province_code"] =
                $billingAddress->getRegionCode();
        }

        if ($billingAddress->getPostcode()) {
            $address["postal_code"] = $data['billing_address']["postal_code"] = $billingAddress->getPostcode();
        }

        if ($billingAddress->getCountry()) {
            $countryName = $this->getCountryModelNameFromBillingAddress($billingAddress);
            $address["country"] = $data['billing_address']["country"] = $countryName;
            $address["country_code"] = $data['billing_address']["country_code"] = $billingAddress->getCountry();
        }

        if (!empty($address)) {
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

    /**
     * @param $data
     * @param $address
     * @param $street
     */
    protected function _getPayloadBillingStreet($data, $address, $street)
    {
        if ($street[0]) {
            $address["address1"] = $data['billing_address']["address1"] = $street[0];
        }

        if (count($street) > 1) {
            $address["address2"] = $data['billing_address']["address2"] = $street[1];
        }
    }

    /**
     * @param $data
     * @param $shippingAddress
     */
    protected function _getPayloadShipping($data, $shippingAddress)
    {
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
            $data['shipping_address']['country'] = $this->getCountryModelNameFromShippingAddress($shippingAddress);
            $data['shipping_address']['country_code'] = $shippingAddress->getCountry();
        }

        if ($shippingAddress->getCompamy()) {
            $data["shipping_address"]["company"] = $shippingAddress->getCompany();
        }
    }

    /**
     * @return mixed
     */
    protected function _processCanceledOrder($order)
    {
        $orderCancelDate = null;
        $commentCollection = $order->getStatusHistoryCollection();

        foreach ($commentCollection as $comment) {
            if ($this->isTheOrderCommentCanceled($comment)) {
                $orderCancelDate = $comment->getCreatedAt();
            }
        }

        return $orderCancelDate;
    }

    /**
     * @return mixed
     */
    protected function getBatchLimitFromConfig()
    {
        $helper = $this->getHelper();
        return $helper->getOrderAmountLimit();
    }

    /**
     * @param $value
     * @return int
     */
    protected function returnZeroIfNull($value)
    {
        $returnValue = $value;
        if ($value === null) {
            $returnValue = 0;
        }

        return $returnValue;
    }

    /**
     * @param $order
     * @return array
     */
    protected function _getMailChimpStatus($order)
    {
        $totalItemsOrdered = $order->getData('total_qty_ordered');
        $mailChimpStatus = array();

        $financialFulfillment = $this->_getFinancialFulfillmentStatus(
            $order->getAllVisibleItems(), $totalItemsOrdered
        );

        if (!$financialFulfillment['financialStatus'] && $this->isOrderCanceled($order)) {
            $financialFulfillment['financialStatus'] = self::CANCELED;
        }

        if (!$financialFulfillment['financialStatus']) {
            $financialFulfillment['financialStatus'] = self::PENDING;
        }

        if ($financialFulfillment['financialStatus']) {
            $mailChimpStatus['financial_status'] = $financialFulfillment['financialStatus'];
        }

        if ($financialFulfillment['fulfillmentStatus']) {
            $mailChimpStatus['fulfillment_status'] = $financialFulfillment['fulfillmentStatus'];
        }

        return $mailChimpStatus;
    }

    /**
     * @param $orderItems
     * @param $totalItemsOrdered
     * @return array
     */
    protected function _getFinancialFulfillmentStatus($orderItems, $totalItemsOrdered)
    {
        $items = array(
            'shippedItemAmount' => 0,
            'invoicedItemAmount' => 0,
            'refundedItemAmount' => 0
        );
        $mailchimpStatus = array(
            'financialStatus' => null,
            'fulfillmentStatus' => null
        );

        foreach ($orderItems as $item) {
            $items['invoicedItemAmount'] += $item->getQtyShipped();
            $items['invoicedItemAmount'] += $item->getQtyInvoiced();
            $items['refundedItemAmount'] += $item->getQtyRefunded();
        }

        if ($items['shippedItemAmount'] > 0) {
            if ($totalItemsOrdered > $items['shippedItemAmount']) {
                $mailchimpStatus['fulfillmentStatus'] = self::PARTIALLY_SHIPPED;
            } else {
                $mailchimpStatus['fulfillmentStatus'] = self::SHIPPED;
            }
        }

        if ($items['refundedItemAmount'] > 0) {
            if ($mailchimpStatus > $items['refundedItemAmount']) {
                $mailchimStatus['financialStatus'] = self::PARTIALLY_REFUNDED;
            } else {
                $mailchimpStatus['financialStatus'] = self::REFUNDED;
            }
        }

        if ($items['invoicedItemAmount'] > 0) {
            if ($items['refundedItemAmount'] == 0
                || $items['refundedItemAmount'] != $items['invoicedItemAmount']
            ) {
                if ($totalItemsOrdered > $items['invoicedItemAmount']) {
                    $mailchimpStatus['financialStatus'] = self::PARTIALLY_PAID;
                } else {
                    $mailchimpStatus['financialStatus'] = self::PAID;
                }
            }
        }

        return $mailchimpStatus;
    }

    /**
     * @param $orderId
     * @param $magentoStoreId
     */
    public function update($orderId, $magentoStoreId)
    {
        $helper = $this->getHelper();
        if ($helper->isEcomSyncDataEnabled($magentoStoreId)) {
            $mailchimpStoreId = $helper->getMCStoreId($magentoStoreId);
            $this->markSyncDataAsModified($orderId, $mailchimpStoreId);
        }
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
        $helper = $this->getHelper();
        $dateHelper = $this->getDateHelper();
        $this->_counter = 0;
        $this->_batchId = 'storeid-'
            . $magentoStoreId . '_'
            . Ebizmarts_MailChimp_Model_Config::IS_ORDER . '_'
            . $dateHelper->getDateMicrotime();
        $lastId = $helper->getConfigValueForScope(
            Ebizmarts_MailChimp_Model_Config::GENERAL_MIGRATE_LAST_ORDER_ID,
            $magentoStoreId,
            'stores'
        );
        $mailchimpTableName = $this->getMailchimpEcommerceDataTableName();
        $batchArray = array();
        $config = array();
        $orderCollection = $this->getResourceModelOrderCollection();
        // select carts for the current Magento store id
        $orderCollection->addFieldToFilter('store_id', array('eq' => $magentoStoreId));
        if ($lastId) {
            $orderCollection->addFieldToFilter('entity_id', array('gt' => $lastId));
        }

        $orderCollection->getSelect()->joinLeft(
            array('m4m' => $mailchimpTableName),
            "m4m.related_id = main_table.entity_id AND m4m.type = '"
            . Ebizmarts_MailChimp_Model_Config::IS_ORDER
            . "' AND m4m.mailchimp_store_id = '" . $mailchimpStoreId . "'",
            array('m4m.*')
        );
        // be sure that the orders are not in mailchimp
        $orderCollection->getSelect()->where(
            "m4m.mailchimp_sync_delta IS NOT NULL AND m4m.mailchimp_sync_error = ''"
        );
        $orderCollection->getSelect()->limit(self::BATCH_LIMIT_ONLY_ORDERS);
        foreach ($orderCollection as $order) {
            //Delete order
            $orderId = $order->getEntityId();
            $config = array(array(Ebizmarts_MailChimp_Model_Config::GENERAL_MIGRATE_LAST_ORDER_ID, $orderId));
            if (!$dateHelper->timePassed($initialTime)) {
                $batchArray[$this->_counter]['method'] = "DELETE";
                $batchArray[$this->_counter]['path'] = '/ecommerce/stores/' . $mailchimpStoreId . '/orders/' . $orderId;
                $batchArray[$this->_counter]['operation_id'] = $this->_batchId . '_' . $orderId;
                $batchArray[$this->_counter]['body'] = '';
                $this->_counter += 1;

                //Create order
                $orderJson = $this->GeneratePOSTPayload($order, $mailchimpStoreId, $magentoStoreId);

                if ($orderJson !== false) {
                    if (!empty($orderJson)) {
                        $batchArray[$this->_counter]['method'] = "POST";
                        $batchArray[$this->_counter]['path'] = '/ecommerce/stores/' . $mailchimpStoreId . '/orders';
                        $batchArray[$this->_counter]['operation_id'] = $this->_batchId . '_' . $orderId;
                        $batchArray[$this->_counter]['body'] = $orderJson;
                        $this->_counter += 1;
                    } else {
                        $error = $helper->__(
                            'Something went wrong when retrieving product information during migration from 1.1.6.'
                        );
                        $this->addSyncDataError(
                            $orderId,
                            $mailchimpStoreId,
                            $error,
                            null,
                            false,
                            $dateHelper->formatDate(null, "Y-m-d H:i:s")
                        );
                        continue;
                    }
                } else {
                    $error = $helper->__("Json error during migration from 1.1.6");
                    $this->addSyncDataError(
                        $orderId,
                        $mailchimpStoreId,
                        $error,
                        null,
                        false,
                        $dateHelper->formatDate(null, "Y-m-d H:i:s")
                    );
                    continue;
                }
            } else {
                if (empty($batchArray)) {
                    $batchArray[] = $helper->__('Time passed.');
                }

                $helper->saveMailchimpConfig($config, $magentoStoreId, 'stores');
                break;
            }
        }

        $helper->saveMailchimpConfig($config, $magentoStoreId, 'stores');
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
        $helper = $this->getHelper();
        $productData = $this->getApiProduct()->sendModifiedProduct($order, $mailchimpStoreId, $magentoStoreId);
        $productDataArray = $helper->addEntriesToArray($batchArray, $productData, $this->_counter);
        $batchArray = $productDataArray[0];
        $this->_counter = $productDataArray[1];

        return $batchArray;
    }

    /**
     * @param $newOrders
     * @param $mailchimpStoreId
     */
    public function joinMailchimpSyncDataWithoutWhere($newOrders, $mailchimpStoreId)
    {
        $mailchimpTableName = $this->getMailchimpEcommerceDataTableName();
        $newOrders->getSelect()->joinLeft(
            array('m4m' => $mailchimpTableName),
            "m4m.related_id = main_table.entity_id AND m4m.type = '"
            . Ebizmarts_MailChimp_Model_Config::IS_ORDER
            . "' AND m4m.mailchimp_store_id = '" . $mailchimpStoreId . "'",
            array('m4m.*')
        );
    }

    /**
     * @param $order
     * @return array
     */

    public function getPromoData($order)
    {
        $promo = null;

        $couponCode = $order->getCouponCode();

        if ($couponCode !== null) {
            $code = $this->makeSalesRuleCoupon()->load($couponCode, 'code');
            if ($code->getCouponId() !== null) {
                $rule = $this->makeSalesRule()->load($code->getRuleId());
                if ($rule->getRuleId() !== null) {
                    $amountDiscounted = $order->getBaseDiscountAmount();

                    $type = $rule->getSimpleAction();
                    if ($type == 'by_percent') {
                        $type = 'percentage';
                    } else {
                        $type = 'fixed';
                    }

                    $promo = array(array(
                        'code' => $couponCode,
                        'amount_discounted' => abs($amountDiscounted),
                        'type' => $type
                    ));
                }
            }
        }

        return $promo;
    }

    /**
     * @return false|Mage_Core_Model_Abstract
     */
    protected function makeSalesRuleCoupon()
    {
        return Mage::getModel('salesrule/coupon');
    }

    /**
     * @return false|Mage_Core_Model_Abstract
     */
    protected function makeSalesRule()
    {
        return Mage::getModel('salesrule/rule');
    }

    /**
     * @param $orderId
     * @param $mailchimpStoreId
     * @return array
     */
    public function getSyncedOrder($orderId, $mailchimpStoreId)
    {
        $helper = $this->getHelper();
        $result = $helper->getEcommerceSyncDataItem(
            $orderId,
            Ebizmarts_MailChimp_Model_Config::IS_ORDER,
            $mailchimpStoreId
        );

        $mailchimpSyncedFlag = $result->getMailchimpSyncedFlag();
        $mailchimpOrderId = $result->getId();

        return array('synced_status' => $mailchimpSyncedFlag, 'order_id' => $mailchimpOrderId);
    }

    /**
     * @param $order
     * @return bool
     */
    protected function isOrderCanceled($order)
    {
        return $order->getState() == Mage_Sales_Model_Order::STATE_CANCELED;
    }

    /**
     * @param $comment
     * @return bool
     */
    protected function isTheOrderCommentCanceled($comment)
    {
        return $comment->getStatus() === Mage_Sales_Model_Order::STATE_CANCELED;
    }

    /**
     * @param $item
     * @return bool
     */
    protected function isItemConfigurable($item)
    {
        return $item->getProductType() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE;
    }

    /**
     * @return false|Mage_Core_Model_Abstract
     */
    protected function getModelProduct()
    {
        return Mage::getModel('catalog/product');
    }

    /**
     * @return string
     */
    protected function isTypeProduct()
    {
        return Ebizmarts_MailChimp_Model_Config::IS_PRODUCT;
    }

    /**
     * @return false|Ebizmarts_MailChimp_Model_Api_Customers
     */
    protected function getCustomerModel()
    {
        return Mage::getModel('mailchimp/api_customers');
    }

    /**
     * @param $magentoStoreId
     * @return Mage_Core_Model_Abstract
     */
    protected function getStoreModelFromMagentoStoreId($magentoStoreId)
    {
        return Mage::getModel('core/store')->load($magentoStoreId);
    }

    /**
     * @param $billingAddress
     * @return mixed
     */
    protected function getCountryModelNameFromBillingAddress($billingAddress)
    {
        return Mage::getModel('directory/country')->loadByCode($billingAddress->getCountry())->getName();
    }

    /**
     * @param $shippingAddress
     * @return mixed
     */
    protected function getCountryModelNameFromShippingAddress($shippingAddress)
    {
        return Mage::getModel('directory/country')->loadByCode($shippingAddress->getCountry())->getName();
    }

    /**
     * @return Mage_Sales_Model_Resource_Order_Collection
     */
    protected function getResourceModelOrderCollection()
    {
        return Mage::getResourceModel('sales/order_collection');
    }

    /**
     * @param $mailchimpCampaignId
     * @return bool \ return true if the campaign is from the current list.
     * @throws Exception
     */
    public function shouldSendCampaignId($mailchimpCampaignId, $magentoStoreId)
    {
        $isCampaingFromCurrentList = false;
        if ($mailchimpCampaignId) {
            $helper = $this->getHelper();
            $listId = $helper->getGeneralList($magentoStoreId);
            try {
                $apiKey = $helper->getApiKey($magentoStoreId);
                if ($apiKey) {
                    if (isset($this->_listsCampaignIds[$apiKey][$listId][$mailchimpCampaignId])) {
                        $isCampaingFromCurrentList = $this->_listsCampaignIds[$apiKey][$listId][$mailchimpCampaignId];
                    } else {
                        $api = $helper->getApi($magentoStoreId);
                        $campaignData = $api->getCampaign()->get($mailchimpCampaignId, 'recipients');
                        if (isset($campaignData['recipients']['list_id'])
                            && $campaignData['recipients']['list_id'] == $listId
                        ) {
                            $this->_listsCampaignIds[$apiKey][$listId][$mailchimpCampaignId] =
                            $isCampaingFromCurrentList =
                                true;
                        } else {
                            $this->_listsCampaignIds[$apiKey][$listId][$mailchimpCampaignId] =
                            $isCampaingFromCurrentList =
                                false;
                        }
                    }
                }
            } catch (Ebizmarts_MailChimp_Helper_Data_ApiKeyException $e) {
                $this->_listsCampaignIds[$apiKey][$listId][$mailchimpCampaignId] = $isCampaingFromCurrentList = true;
                $this->logSyncError(
                    $e->getMessage(),
                    Ebizmarts_MailChimp_Model_Config::IS_ORDER,
                    null, $magentoStoreId
                );
            } catch (MailChimp_Error $e) {
                $this->_listsCampaignIds[$apiKey][$listId][$mailchimpCampaignId] = $isCampaingFromCurrentList = false;
                $this->logSyncError(
                    $e->getFriendlyMessage(),
                    Ebizmarts_MailChimp_Model_Config::IS_ORDER,
                    null, $magentoStoreId
                );
            } catch (Exception $e) {
                $this->_listsCampaignIds[$apiKey][$listId][$mailchimpCampaignId] = $isCampaingFromCurrentList = true;
                $this->logSyncError(
                    $e->getMessage(),
                    Ebizmarts_MailChimp_Model_Config::IS_ORDER,
                    null, $magentoStoreId
                );
            }
        }

        return $isCampaingFromCurrentList;
    }

    /**
     * @return Ebizmarts_MailChimp_Model_Api_Products
     */
    protected function getApiProduct()
    {
        return Mage::getModel('mailchimp/api_products');
    }

    /**
     * @return false|Mage_Newsletter_Model_Subscriber
     */
    protected function getSubscriberModel()
    {
        return Mage::getModel('newsletter/subscriber');
    }

    /**
     * @return string
     */
    protected function getClassConstant()
    {
        return Ebizmarts_MailChimp_Model_Config::IS_ORDER;
    }
}
