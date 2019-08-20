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
class Ebizmarts_MailChimp_Model_Api_Carts
{
    const BATCH_LIMIT = 100;

    protected $_firstDate;
    protected $_counter;
    protected $_batchId;

    protected $_api = null;
    protected $_token = null;

    /**
     * @param $mailchimpStoreId
     * @param $magentoStoreId
     * @return array
     */
    public function createBatchJson($mailchimpStoreId, $magentoStoreId)
    {
        $helper = $this->getHelper();
        $dateHelper = $this->getDateHelper();
        $allCarts = array();

        if (!$helper->isAbandonedCartEnabled($magentoStoreId)) {
            return $allCarts;
        }

        $this->_firstDate = $helper->getAbandonedCartFirstDate($magentoStoreId);
        $this->setCounter(0);

        $date = $dateHelper->getDateMicrotime();
        $this->setBatchId(
            'storeid-'
            . $magentoStoreId . '_'
            . Ebizmarts_MailChimp_Model_Config::IS_QUOTE . '_'
            . $date
        );
        $resendTurn = $helper->getResendTurn($magentoStoreId);

        if (!$resendTurn) {
            // get all the carts converted in orders (must be deleted on mailchimp)
            $allCarts = array_merge($allCarts, $this->_getConvertedQuotes($mailchimpStoreId, $magentoStoreId));
            // get all the carts modified but not converted in orders
            $allCarts = array_merge($allCarts, $this->_getModifiedQuotes($mailchimpStoreId, $magentoStoreId));
        }

        // get new carts
        $allCarts = array_merge($allCarts, $this->_getNewQuotes($mailchimpStoreId, $magentoStoreId));

        return $allCarts;
    }

    /**
     * @param $mailchimpStoreId
     * @param $magentoStoreId
     * @return array
     */
    public function _getConvertedQuotes($mailchimpStoreId, $magentoStoreId)
    {
        $mailchimpTableName = $this->getMailchimpEcommerceDataTableName();
        $batchId = $this->getBatchId();
        $allCarts = array();
        $convertedCarts = $this->getQuoteCollection();
        // get only the converted quotes
        $convertedCarts->addFieldToFilter('store_id', array('eq' => $magentoStoreId));
        $convertedCarts->addFieldToFilter('is_active', array('eq' => 0));
        //join with mailchimp_ecommerce_sync_data table to filter by sync data.
        $convertedCarts->getSelect()->joinLeft(
            array('m4m' => $mailchimpTableName),
            "m4m.related_id = main_table.entity_id "
            . "AND m4m.type = '" . Ebizmarts_MailChimp_Model_Config::IS_QUOTE
            . "' AND m4m.mailchimp_store_id = '" . $mailchimpStoreId . "'",
            array('m4m.*')
        );
        // be sure that the quotes are already in mailchimp and not deleted
        $convertedCarts->getSelect()->where("m4m.mailchimp_sync_deleted = 0");
        // limit the collection
        $convertedCarts->getSelect()->limit($this->getBatchLimitFromConfig());

        foreach ($convertedCarts as $cart) {
            $cartId = $cart->getEntityId();
            // we need to delete all the carts associated with this email
            $allCartsForEmail = $this->getAllCartsByEmail(
                $cart->getCustomerEmail(),
                $mailchimpStoreId,
                $magentoStoreId
            );

            foreach ($allCartsForEmail as $cartForEmail) {
                $alreadySentCartId = $cartForEmail->getEntityId();
                $counter = $this->getCounter();

                if ($alreadySentCartId != $cartId) {
                    $allCarts[$counter]['method'] = 'DELETE';
                    $allCarts[$counter]['path'] = '/ecommerce/stores/'
                        . $mailchimpStoreId . '/carts/'
                        . $alreadySentCartId;
                    $allCarts[$counter]['operation_id'] = $batchId . '_' . $alreadySentCartId;
                    $allCarts[$counter]['body'] = '';
                    $this->_updateSyncData(
                        $alreadySentCartId,
                        $mailchimpStoreId,
                        null,
                        null,
                        0,
                        null,
                        1
                    );
                    $this->setCounter($this->getCounter() + 1);
                }
            }

            $allCartsForEmail->clear();
            $counter = $this->getCounter();
            $allCarts[$counter]['method'] = 'DELETE';
            $allCarts[$counter]['path'] = '/ecommerce/stores/' . $mailchimpStoreId . '/carts/' . $cartId;
            $allCarts[$counter]['operation_id'] = $batchId . '_' . $cartId;
            $allCarts[$counter]['body'] = '';
            $this->_updateSyncData(
                $cartId,
                $mailchimpStoreId,
                null,
                null,
                0,
                null,
                1
            );
            $this->setCounter($this->getCounter() + 1);
        }

        return $allCarts;
    }

    /**
     * @param $mailchimpStoreId
     * @param $magentoStoreId
     * @return array
     */
    public function _getModifiedQuotes($mailchimpStoreId, $magentoStoreId)
    {
        $mailchimpTableName = $this->getMailchimpEcommerceDataTableName();
        $batchId = $this->getBatchId();
        $allCarts = array();
        $modifiedCarts = $this->getQuoteCollection();
        // select carts with no orders
        $modifiedCarts->addFieldToFilter('is_active', array('eq' => 1));
        // select carts for the current Magento store id
        $modifiedCarts->addFieldToFilter('store_id', array('eq' => $magentoStoreId));
        //join with mailchimp_ecommerce_sync_data table to filter by sync data.
        $modifiedCarts->getSelect()->joinLeft(
            array('m4m' => $mailchimpTableName),
            "m4m.related_id = main_table.entity_id AND m4m.type = '"
            . Ebizmarts_MailChimp_Model_Config::IS_QUOTE
            . "' AND m4m.mailchimp_store_id = '" . $mailchimpStoreId . "'",
            array('m4m.*')
        );
        // be sure that the quotes are already in mailchimp and not deleted
        $modifiedCarts->getSelect()->where(
            "m4m.mailchimp_sync_delta < updated_at"
        );
        // limit the collection
        $modifiedCarts->getSelect()->limit($this->getBatchLimitFromConfig());

        foreach ($modifiedCarts as $cart) {
            $cartId = $cart->getEntityId();
            /**
             * @var $customer Mage_Customer_Model_Customer
             */
            $customer = $this->getCustomerModel();
            $customer->setWebsiteId($this->getWebSiteIdFromMagentoStoreId($magentoStoreId));
            $customer->loadByEmail($cart->getCustomerEmail());

            if ($customer->getEmail() != $cart->getCustomerEmail()) {
                $allCartsForEmail = $this->getAllCartsByEmail(
                    $cart->getCustomerEmail(),
                    $mailchimpStoreId,
                    $magentoStoreId
                );

                foreach ($allCartsForEmail as $cartForEmail) {
                    $alreadySentCartId = $cartForEmail->getEntityId();
                    $counter = $this->getCounter();

                    if ($alreadySentCartId != $cartId) {
                        $allCarts[$counter]['method'] = 'DELETE';
                        $allCarts[$counter]['path'] = '/ecommerce/stores/'
                            . $mailchimpStoreId
                            . '/carts/'
                            . $alreadySentCartId;
                        $allCarts[$counter]['operation_id'] = $batchId . '_' . $alreadySentCartId;
                        $allCarts[$counter]['body'] = '';
                        $this->_updateSyncData(
                            $alreadySentCartId,
                            $mailchimpStoreId,
                            null,
                            null,
                            0,
                            null,
                            1
                        );
                        $this->setCounter($this->getCounter() + 1);
                    }
                }

                $allCartsForEmail->clear();
            }

            // avoid carts abandoned as guests when customer email associated to a registered customer.
            if (!$cart->getCustomerId() && $customer->getEmail() == $cart->getCustomerEmail()) {
                $this->_updateSyncData($cartId, $mailchimpStoreId);
                continue;
            }

            // send the products that not already sent
            $allCarts = $this->addProductNotSentData($mailchimpStoreId, $magentoStoreId, $cart, $allCarts);

            /*
             * If item was deleted for an emptied cart or cart containing
             * only unsupported products then re-create cart if new cart
             * contains at least one supported product.
             */
            $action = ($cart->getMailchimpSyncDeleted() ? 'POST' : 'PATCH');
            $path = '/ecommerce/stores/' . $mailchimpStoreId . '/carts';
            if ($action == 'PATCH') {
                $path .= '/' . $cartId;
            }

            $cartJson = $this->_makeCart($cart, $mailchimpStoreId, $magentoStoreId, true);

            if ($cartJson != "") {
                $this->getHelper()->modifyCounterSentPerBatch(Ebizmarts_MailChimp_Helper_Data::QUO_MOD);

                $counter = $this->getCounter();
                $allCarts[$counter]['method'] = $action;
                $allCarts[$counter]['path'] = $path;
                $allCarts[$counter]['operation_id'] = $batchId . '_' . $cartId;
                $allCarts[$counter]['body'] = $cartJson;
                $this->setCounter($this->getCounter() + 1);
                $this->_updateSyncData(
                    $cartId,
                    $mailchimpStoreId,
                    null,
                    null,
                    0,
                    null,
                    0,
                    $this->getToken()
                );
            } else {
                /*
                 * Item is empty or has no supported products
                 */
                if (!$cart->getMailchimpSyncDeleted()) {
                    /*
                     * Cart previously existed so we need to delete it.
                     */
                    $this->getHelper()->modifyCounterSentPerBatch(Ebizmarts_MailChimp_Helper_Data::QUO_DEL);

                    $counter = $this->getCounter();
                    $allCarts[$counter]['method'] = 'DELETE';
                    $allCarts[$counter]['path'] = '/ecommerce/stores/' . $mailchimpStoreId . '/carts/' . $cartId;
                    $allCarts[$counter]['operation_id'] = $batchId . '_' . $cartId;
                    $allCarts[$counter]['body'] = '';
                    $this->_updateSyncData(
                        $cartId,
                        $mailchimpStoreId,
                        null,
                        null,
                        0,
                        null,
                        1
                    );
                    $this->setCounter($this->getCounter() + 1);
                } else {
                    /*
                     * Cart was previously empty so we just mark the sync
                     * item as invalid or empty so that it isn't deleted
                     * as an unsent item.
                     */
                    $this->_updateSyncData(
                        $cartId,
                        $mailchimpStoreId,
                        null,
                        null,
                        0,
                        null,
                        null,
                        null,
                        true
                    );
                }
            }

            $this->setToken(null);
        }

        return $allCarts;
    }

    /**
     * @param $mailchimpStoreId
     * @return array
     */
    public function _getNewQuotes($mailchimpStoreId, $magentoStoreId)
    {
        $helper = $this->getHelper();
        $batchId = $this->getBatchId();
        $allCarts = array();
        $newCarts = $this->getQuoteCollection();
        $newCarts->addFieldToFilter('is_active', array('eq' => 1));
        $newCarts->addFieldToFilter('customer_email', array('notnull' => true));
        $newCarts->addFieldToFilter('items_count', array('gt' => 0));
        // select carts for the current Magento store id
        $newCarts->addFieldToFilter('store_id', array('eq' => $magentoStoreId));
        $helper->addResendFilter($newCarts, $magentoStoreId, Ebizmarts_MailChimp_Model_Config::IS_QUOTE);
        // filter by first date if exists.
        if ($this->getFirstDate()) {
            $newCarts->addFieldToFilter('updated_at', array('gt' => $this->getFirstDate()));
        }

        //join with mailchimp_ecommerce_sync_data table to filter by sync data.
        $this->joinMailchimpSyncDataWithoutWhere($newCarts, $mailchimpStoreId);
        // be sure that the quotes are already in mailchimp and not deleted
        $newCarts->getSelect()->where("m4m.mailchimp_sync_delta IS NULL");
        // limit the collection
        $newCarts->getSelect()->limit($this->getBatchLimitFromConfig());

        foreach ($newCarts as $cart) {
            $cartId = $cart->getEntityId();
            $orderCollection = $this->getOrderCollection();
            $orderCollection->addFieldToFilter(
                'main_table.customer_email',
                array('eq' => $cart->getCustomerEmail())
            );
            $orderCollection->addFieldToFilter('main_table.updated_at', array('from' => $cart->getUpdatedAt()));
            //if cart is empty or customer has an order made after the abandonment skip current cart.
            if (empty($cart->getAllVisibleItems()) || $orderCollection->getSize()) {
                $this->_updateSyncData($cartId, $mailchimpStoreId);
                continue;
            }

            $customer = $this->getCustomerModel();
            $customer->setWebsiteId($this->getWebSiteIdFromMagentoStoreId($magentoStoreId));
            $customer->loadByEmail($cart->getCustomerEmail());

            if ($customer->getEmail() != $cart->getCustomerEmail()) {
                $allCartsForEmail = $this->getAllCartsByEmail(
                    $cart->getCustomerEmail(),
                    $mailchimpStoreId,
                    $magentoStoreId
                );

                foreach ($allCartsForEmail as $cartForEmail) {
                    $counter = $this->getCounter();
                    $alreadySentCartId = $cartForEmail->getEntityId();
                    $allCarts[$counter]['method'] = 'DELETE';
                    $allCarts[$counter]['path'] = '/ecommerce/stores/'
                        . $mailchimpStoreId
                        . '/carts/'
                        . $alreadySentCartId;
                    $allCarts[$counter]['operation_id'] = $batchId . '_' . $alreadySentCartId;
                    $allCarts[$counter]['body'] = '';
                    $this->_updateSyncData(
                        $alreadySentCartId,
                        $mailchimpStoreId,
                        null,
                        null,
                        0,
                        null,
                        1
                    );
                    $this->setCounter($this->getCounter() + 1);
                }

                $allCartsForEmail->clear();
            }

            // don't send the carts for guest customers who are registered
            if (!$cart->getCustomerId() && $customer->getEmail() == $cart->getCustomerEmail()) {
                $this->_updateSyncData($cartId, $mailchimpStoreId);
                continue;
            }

            // send the products that not already sent
            $allCarts = $this->addProductNotSentData($mailchimpStoreId, $magentoStoreId, $cart, $allCarts);
            $cartJson = $this->_makeCart($cart, $mailchimpStoreId, $magentoStoreId);

            if ($cartJson != "") {
                $helper->modifyCounterSentPerBatch(Ebizmarts_MailChimp_Helper_Data::QUO_NEW);

                $counter = $this->getCounter();
                $allCarts[$counter]['method'] = 'POST';
                $allCarts[$counter]['path'] = '/ecommerce/stores/' . $mailchimpStoreId . '/carts';
                $allCarts[$counter]['operation_id'] = $batchId . '_' . $cartId;
                $allCarts[$counter]['body'] = $cartJson;
                $this->setCounter($this->getCounter() + 1);
                $this->_updateSyncData(
                    $cartId,
                    $mailchimpStoreId,
                    null,
                    null,
                    0,
                    null,
                    null,
                    $this->getToken()
                );
            } else {
                /*
                 * Item contains only unsupported products. Mark it as
                 * unsupported so that the item is not deleted with any other
                 * unsent items (batch ID null) or it will synchronise
                 * indefinitely.
                 */
                $this->_updateSyncData(
                    $cartId,
                    $mailchimpStoreId,
                    null,
                    null,
                    0,
                    null,
                    1,
                    null,
                    true
                );
            }

            $this->setToken(null);
        }

        return $allCarts;
    }

    /**
     * Get all existing carts in the current store view for a given email address.
     *
     * @param  $email
     * @param  $mailchimpStoreId
     * @param  $magentoStoreId
     * @return object
     */
    public function getAllCartsByEmail($email, $mailchimpStoreId, $magentoStoreId)
    {
        $mailchimpTableName = $this->getMailchimpEcommerceDataTableName();
        $allCartsForEmail = $this->getQuoteCollection();
        $allCartsForEmail->addFieldToFilter('is_active', array('eq' => 1));
        $allCartsForEmail->addFieldToFilter('store_id', array('eq' => $magentoStoreId));
        $allCartsForEmail->addFieldToFilter('customer_email', array('eq' => $email));
        $allCartsForEmail->getSelect()->joinLeft(
            array('m4m' => $mailchimpTableName),
            "m4m.related_id = main_table.entity_id AND m4m.type = '"
            . Ebizmarts_MailChimp_Model_Config::IS_QUOTE
            . "' AND m4m.mailchimp_store_id = '" . $mailchimpStoreId . "'",
            array('m4m.*')
        );
        // be sure that the quotes are already in mailchimp and not deleted
        $allCartsForEmail->getSelect()->where(
            "m4m.mailchimp_sync_deleted = 0 "
            . "AND m4m.mailchimp_store_id = '" . $mailchimpStoreId . "'"
        );

        return $allCartsForEmail;
    }

    /**
     * @param $cart
     * @param $magentoStoreId
     * @param $isModified
     * @return string
     */
    public function _makeCart($cart, $mailchimpStoreId, $magentoStoreId, $isModified = false)
    {
        $helper = $this->getHelper();
        $apiProduct = $this->getApiProducts();
        $campaignId = $cart->getMailchimpCampaignId();
        $oneCart = array();
        $oneCart['id'] = $cart->getEntityId();
        $customer = $this->_getCustomer($cart, $magentoStoreId);

        if (empty($customer)) {
            return "";
        }

        $oneCart['customer'] = $customer;

        if ($campaignId) {
            $oneCart['campaign_id'] = $campaignId;
        }

        $oneCart['checkout_url'] = $this->_getCheckoutUrl($cart, $isModified);
        $oneCart['currency_code'] = $cart->getQuoteCurrencyCode();
        $oneCart['order_total'] = $cart->getGrandTotal();
        $oneCart['tax_total'] = 0;
        // get all items on the cart
        $lines = $this->_processCartLines(
            $cart->getAllVisibleItems(), $mailchimpStoreId, $magentoStoreId, $apiProduct
        );

        $jsonData = "";

        if ($lines['count']) {
            $oneCart['lines'] = $lines['lines'];
            //enconde to JSON
            try {
                $jsonData = json_encode($oneCart);
            } catch (Exception $e) {
                //json encode failed
                $helper->logError("Carts " . $cart->getId() . " json encode failed");
            }
        }

        return $jsonData;
    }

    /**
     * @param $items
     * @param $mailchimpStoreId
     * @param $magentoStoreId
     * @param $apiProduct
     * @return array
     */
    protected function _processCartLines($items, $mailchimpStoreId, $magentoStoreId, $apiProduct)
    {
        $helper = $this->getHelper();
        $lines = array();
        $itemCount = 0;

        foreach ($items as $item) {
            $productId = $item->getProductId();
            $isTypeProduct = $this->isTypeProduct();
            $productSyncData = $helper->getEcommerceSyncDataItem($productId, $isTypeProduct, $mailchimpStoreId);
            $line = array();

            if ($item->getProductType() == 'bundle' || $item->getProductType() == 'grouped') {
                continue;
            }

            if ($this->isProductTypeConfigurable($item)) {
                $variant = null;

                if ($item->getOptionByCode('simple_product')) {
                    $variant = $item->getOptionByCode('simple_product')->getProduct();
                }

                if (!$variant) {
                    continue;
                }

                $variantId = $variant->getId();
            } else {
                $variantId = $item->getProductId();
            }

            //id can not be 0 so we add 1 to $itemCount before setting the id.
            $productSyncError = $productSyncData->getMailchimpSyncError();
            $isProductEnabled = $apiProduct->isProductEnabled($productId, $magentoStoreId);

            if (!$isProductEnabled || ($productSyncData->getMailchimpSyncDelta() && $productSyncError == '')) {
                $itemCount++;
                $line['id'] = (string)$itemCount;
                $line['product_id'] = $productId;
                $line['product_variant_id'] = $variantId;
                $line['quantity'] = (int)$item->getQty();
                $line['price'] = $item->getRowTotal();
                $lines[] = $line;

                if (!$isProductEnabled) {
                    // update disabled products to remove the product from mailchimp after sending the order
                    $apiProduct->updateDisabledProducts($productId, $mailchimpStoreId);
                }
            }
        }

        return array('lines' => $lines, 'count' => $itemCount);
    }

    /**
     * Get URL for the cart.
     *
     * @param  $cart
     * @param  $isModified
     * @return string
     */
    protected function _getCheckoutUrl($cart, $isModified)
    {
        if (!$isModified) {
            $token = md5(rand(0, 9999999));
        } else {
            $token = $cart->getMailchimpToken();
        }

        $url = Mage::getModel('core/url')->setStore($cart->getStoreId())->getUrl(
            '',
            array('_nosid' => true, '_secure' => true)
        )
            . 'mailchimp/cart/loadquote?id=' . $cart->getEntityId() . '&token=' . $token;
        $this->setToken($token);
        return $url;
    }

    /**
     * @return int
     */
    protected function getBatchLimitFromConfig()
    {
        $helper = $this->getHelper();
        return $helper->getCustomerAmountLimit();
    }

    /**
     * Get Customer data for the cart.
     *
     * @param  $cart
     * @param  $magentoStoreId
     * @return array
     */
    public function _getCustomer($cart, $magentoStoreId)
    {
        $customer = array(
            "id" => md5(strtolower($cart->getCustomerEmail())),
            "email_address" => $cart->getCustomerEmail(),
            "opt_in_status" => $this->getApiCustomersOptIn($magentoStoreId)
        );

        $firstName = $cart->getCustomerFirstname();

        if ($firstName) {
            $customer["first_name"] = $firstName;
        }

        $lastName = $cart->getCustomerLastname();

        if ($lastName) {
            $customer["last_name"] = $lastName;
        }

        $billingAddress = $cart->getBillingAddress();

        if ($billingAddress) {
            $street = $billingAddress->getStreet();
            $address = array();

            if (isset($street[0])) {
                $address['address1'] = $street[0];

                if (count($street) > 1) {
                    $address['address2'] = $street[1];
                }
            }

            $address = $this->_addBillingAddress($address, $billingAddress);

            if (!empty($address)) {
                $customer['address'] = $address;
            }
        }

        //company
        if ($billingAddress->getCompany()) {
            $customer["company"] = $billingAddress->getCompany();
        }

        return $customer;
    }

    /**
     * @param $address
     * @param $billingAddress
     * @return array
     */
    protected function _addBillingAddress($address, $billingAddress)
    {
        if ($billingAddress->getCity()) {
            $address['city'] = $billingAddress->getCity();
        }

        if ($billingAddress->getRegion()) {
            $address['province'] = $billingAddress->getRegion();
        }

        if ($billingAddress->getRegionCode()) {
            $address['province_code'] = $billingAddress->getRegionCode();
        }

        if ($billingAddress->getPostcode()) {
            $address['postal_code'] = $billingAddress->getPostcode();
        }

        if ($billingAddress->getCountry()) {
            $address['country'] = $this->getCountryModel($billingAddress);
            $address['country_code'] = $billingAddress->getCountry();
        }

        return $address;
    }

    /**
     * update product sync data
     *
     * @param $cartId
     * @param $mailchimpStoreId
     * @param int|null         $syncDelta
     * @param int|null         $syncError
     * @param int|null         $syncModified
     * @param int|null         $syncedFlag
     * @param int|null         $syncDeleted
     * @param string|null      $token
     * @param bool             $invalidOrEmpty
     */
    protected function _updateSyncData(
        $cartId,
        $mailchimpStoreId,
        $syncDelta = null,
        $syncError = null,
        $syncModified = 0,
        $syncedFlag = null,
        $syncDeleted = null,
        $token = null,
        $invalidOrEmpty = false
    ) {
        $helper = $this->getHelper();
        $helper->saveEcommerceSyncData(
            $cartId,
            Ebizmarts_MailChimp_Model_Config::IS_QUOTE,
            $mailchimpStoreId,
            $syncDelta,
            $syncError,
            $syncModified,
            $syncDeleted,
            $token,
            $syncedFlag,
            false,
            null,
            !$invalidOrEmpty,
            $invalidOrEmpty
        );
    }

    /**
     * @param $mailchimpStoreId
     * @param $magentoStoreId
     * @param $cart
     * @param $allCarts
     * @return mixed
     */
    public function addProductNotSentData($mailchimpStoreId, $magentoStoreId, $cart, $allCarts)
    {
        $helper = $this->getHelper();
        $productData = $this->getApiProducts()->sendModifiedProduct($cart, $mailchimpStoreId, $magentoStoreId);
        $productDataArray = $helper->addEntriesToArray($allCarts, $productData, $this->getCounter());
        $allCarts = $productDataArray[0];
        $this->setCounter($productDataArray[1]);
        return $allCarts;
    }

    /**
     * @return Ebizmarts_MailChimp_Helper_Data
     */
    protected function getHelper()
    {
        return Mage::helper('mailchimp');
    }

    /**
     * @return Ebizmarts_MailChimp_Helper_Date
     */
    protected function getDateHelper()
    {
        return Mage::helper('mailchimp/date');
    }

    /**
     * @param $newCarts
     * @param $mailchimpStoreId
     */
    public function joinMailchimpSyncDataWithoutWhere($newCarts, $mailchimpStoreId)
    {
        $mailchimpTableName = $this->getMailchimpEcommerceDataTableName();
        $newCarts->getSelect()->joinLeft(
            array('m4m' => $mailchimpTableName),
            "m4m.related_id = main_table.entity_id AND m4m.type = '" . Ebizmarts_MailChimp_Model_Config::IS_QUOTE
            . "' AND m4m.mailchimp_store_id = '" . $mailchimpStoreId . "'",
            array('m4m.*')
        );
    }

    /**
     * @return mixed
     */
    public function getMailchimpEcommerceDataTableName()
    {
        return Mage::getSingleton('core/resource')->getTableName('mailchimp/ecommercesyncdata');
    }

    /**
     * @return Mage_Sales_Model_Resource_Quote_Collection
     */
    public function getQuoteCollection()
    {
        return Mage::getResourceModel('sales/quote_collection');
    }

    /**
     * @return false|Mage_Core_Model_Abstract
     */
    public function getCustomerModel()
    {
        return Mage::getModel("customer/customer");
    }

    /**
     * @param $magentoStoreId
     * @return mixed
     */
    public function getWebSiteIdFromMagentoStoreId($magentoStoreId)
    {
        return Mage::getModel('core/store')->load($magentoStoreId)->getWebsiteId();
    }

    /**
     * @return int
     */
    public function getCounter()
    {
        return $this->_counter;
    }

    /**
     * @param $counter
     */
    public function setCounter($counter)
    {
        $this->_counter = $counter;
    }

    /**
     * Return the batchId for the batchJson of the carts.
     *
     * @return string
     */
    public function getBatchId()
    {
        return $this->_batchId;
    }

    /**
     * @param $batchId
     */
    public function setBatchId($batchId)
    {
        $this->_batchId = $batchId;
    }

    /**
     * Token for cart validation.
     *
     * @return string|null
     */
    public function getToken()
    {
        return $this->_token;
    }

    /**
     * @param string $token
     */
    public function setToken($token)
    {

        $this->_token = $token;
    }

    /**
     * Returns first date of abandoned cart if exists.
     *
     * @return string|null
     */
    protected function getFirstDate()
    {
        return $this->_firstDate;
    }

    /**
     * @return Mage_Sales_Model_Resource_Order_Collection
     */
    protected function getOrderCollection()
    {
        return Mage::getResourceModel('sales/order_collection');
    }

    /**
     * @param $magentoStoreId
     * @return mixed
     */
    protected function getApiCustomersOptIn($magentoStoreId)
    {
        return Mage::getModel('mailchimp/api_customers')->getOptIn($magentoStoreId);
    }

    /**
     * @param $billingAddress
     * @return mixed
     */
    protected function getCountryModel($billingAddress)
    {
        return Mage::getModel('directory/country')->loadByCode($billingAddress->getCountry())->getName();
    }

    /**
     * @param $item
     * @return bool
     */
    protected function isProductTypeConfigurable($item)
    {
        return $item->getProductType() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE;
    }

    /**
     * @return string
     */
    protected function isTypeProduct()
    {
        return Ebizmarts_MailChimp_Model_Config::IS_PRODUCT;
    }

    /**
     * @return false|Mage_Core_Model_Abstract
     */
    protected function getApiProducts()
    {
        return Mage::getModel('mailchimp/api_products');
    }
}
