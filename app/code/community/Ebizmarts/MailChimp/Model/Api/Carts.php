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
        /** @var Ebizmarts_MailChimp_Helper_Data $helper */
        $helper = $this->getHelper();
        $allCarts = array();
        if (!$helper->isAbandonedCartEnabled($magentoStoreId)) {
            return $allCarts;
        }

        $this->_firstDate = $helper->getAbandonedCartFirstDate($magentoStoreId);
        $this->setCounter(0);

        $date = $helper->getDateMicrotime();
        $this->setBatchId('storeid-' . $magentoStoreId . '_' . Ebizmarts_MailChimp_Model_Config::IS_QUOTE . '_' . $date);
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
            "m4m.related_id = main_table.entity_id and m4m.type = '" . Ebizmarts_MailChimp_Model_Config::IS_QUOTE . "'
            AND m4m.mailchimp_store_id = '" . $mailchimpStoreId . "'",
            array('m4m.*')
        );
        // be sure that the quotes are already in mailchimp and not deleted
        $convertedCarts->getSelect()->where("m4m.mailchimp_sync_deleted = 0");
        // limit the collection
        $convertedCarts->getSelect()->limit($this->getBatchLimitFromConfig());
        foreach ($convertedCarts as $cart) {
            $cartId = $cart->getEntityId();
            // we need to delete all the carts associated with this email
            $allCartsForEmail = $this->getAllCartsByEmail($cart->getCustomerEmail(), $mailchimpStoreId, $magentoStoreId);
            foreach ($allCartsForEmail as $cartForEmail) {
                $alreadySentCartId = $cartForEmail->getEntityId();
                $counter = $this->getCounter();
                if ($alreadySentCartId != $cartId) {
                    $allCarts[$counter]['method'] = 'DELETE';
                    $allCarts[$counter]['path'] = '/ecommerce/stores/' . $mailchimpStoreId . '/carts/' . $alreadySentCartId;
                    $allCarts[$counter]['operation_id'] = $batchId . '_' . $alreadySentCartId;
                    $allCarts[$counter]['body'] = '';
                    $this->_updateSyncData($alreadySentCartId, $mailchimpStoreId, null, null, null, null, 1);
                    $this->setCounter($this->getCounter()+1);
                }
            }

            $allCartsForEmail->clear();
            $counter = $this->getCounter();
            $allCarts[$counter]['method'] = 'DELETE';
            $allCarts[$counter]['path'] = '/ecommerce/stores/' . $mailchimpStoreId . '/carts/' . $cartId;
            $allCarts[$counter]['operation_id'] = $batchId . '_' . $cartId;
            $allCarts[$counter]['body'] = '';
            $this->_updateSyncData($cartId, $mailchimpStoreId, null, null, null, null, 1);
            $this->setCounter($this->getCounter()+1);
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
            "m4m.related_id = main_table.entity_id and m4m.type = '" . Ebizmarts_MailChimp_Model_Config::IS_QUOTE . "'
            AND m4m.mailchimp_store_id = '" . $mailchimpStoreId . "'",
            array('m4m.*')
        );
        // be sure that the quotes are already in mailchimp and not deleted
        $modifiedCarts->getSelect()->where(
            "m4m.mailchimp_sync_deleted = 0
        AND m4m.mailchimp_sync_delta < updated_at"
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
                $allCartsForEmail = $this->getAllCartsByEmail($cart->getCustomerEmail(), $mailchimpStoreId, $magentoStoreId);
                foreach ($allCartsForEmail as $cartForEmail) {
                    $alreadySentCartId = $cartForEmail->getEntityId();
                    $counter = $this->getCounter();
                    if ($alreadySentCartId != $cartId) {
                        $allCarts[$counter]['method'] = 'DELETE';
                        $allCarts[$counter]['path'] = '/ecommerce/stores/' . $mailchimpStoreId . '/carts/' . $alreadySentCartId;
                        $allCarts[$counter]['operation_id'] = $batchId . '_' . $alreadySentCartId;
                        $allCarts[$counter]['body'] = '';
                        $this->_updateSyncData($alreadySentCartId, $mailchimpStoreId, null, null, null, null, 1);
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

            $cartJson = $this->_makeCart($cart, $mailchimpStoreId, $magentoStoreId, true);
            if ($cartJson != "") {
                $counter = $this->getCounter();
                $allCarts[$counter]['method'] = 'PATCH';
                $allCarts[$counter]['path'] = '/ecommerce/stores/' . $mailchimpStoreId . '/carts/'.$cartId;
                $allCarts[$counter]['operation_id'] = $batchId . '_' . $cartId;
                $allCarts[$counter]['body'] = $cartJson;
                $this->setCounter($this->getCounter()+1);
                $this->_updateSyncData($cartId, $mailchimpStoreId, null, null, null, null, null, $this->getToken());
            } else {
                $this->_updateSyncData($cartId, $mailchimpStoreId);
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
            $orderCollection->addFieldToFilter('main_table.customer_email', array('eq' => $cart->getCustomerEmail()));
            $orderCollection->addFieldToFilter('main_table.updated_at', array('from' => $cart->getUpdatedAt()));
            //if cart is empty or customer has an order made after the abandonment skip current cart.
            if (!count($cart->getAllVisibleItems()) || $orderCollection->getSize()) {
                $this->_updateSyncData($cartId, $mailchimpStoreId);
                continue;
            }

            $customer = $this->getCustomerModel();
            $customer->setWebsiteId($this->getWebSiteIdFromMagentoStoreId($magentoStoreId));
            $customer->loadByEmail($cart->getCustomerEmail());
            if ($customer->getEmail() != $cart->getCustomerEmail()) {
                $allCartsForEmail = $this->getAllCartsByEmail($cart->getCustomerEmail(), $mailchimpStoreId, $magentoStoreId);
                foreach ($allCartsForEmail as $cartForEmail) {
                    $counter = $this->getCounter();
                    $alreadySentCartId = $cartForEmail->getEntityId();
                    $allCarts[$counter]['method'] = 'DELETE';
                    $allCarts[$counter]['path'] = '/ecommerce/stores/' . $mailchimpStoreId . '/carts/' . $alreadySentCartId;
                    $allCarts[$counter]['operation_id'] = $batchId . '_' . $alreadySentCartId;
                    $allCarts[$counter]['body'] = '';
                    $this->_updateSyncData($alreadySentCartId, $mailchimpStoreId, null, null, null, null, 1);
                    $this->setCounter($this->getCounter()+1);
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
                $counter = $this->getCounter();
                $allCarts[$counter]['method'] = 'POST';
                $allCarts[$counter]['path'] = '/ecommerce/stores/' . $mailchimpStoreId . '/carts';
                $allCarts[$counter]['operation_id'] = $batchId . '_' . $cartId;
                $allCarts[$counter]['body'] = $cartJson;
                $this->setCounter($this->getCounter()+1);
                $this->_updateSyncData($cartId, $mailchimpStoreId, null, null, null, null, null, $this->getToken());
            } else {
                $this->_updateSyncData($cartId, $mailchimpStoreId);
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
            "m4m.related_id = main_table.entity_id and m4m.type = '" . Ebizmarts_MailChimp_Model_Config::IS_QUOTE . "'
            AND m4m.mailchimp_store_id = '" . $mailchimpStoreId . "'",
            array('m4m.*')
        );
        // be sure that the quotes are already in mailchimp and not deleted
        $allCartsForEmail->getSelect()->where("m4m.mailchimp_sync_deleted = 0 AND m4m.mailchimp_store_id = '" . $mailchimpStoreId . "'");
        return $allCartsForEmail;
    }

    /**
     * @param $cart
     * @param $mailchimpStoreId
     * @param $magentoStoreId
     * @param $isModified
     * @return string
     */
    protected function _makeCart($cart, $mailchimpStoreId, $magentoStoreId, $isModified = false)
    {
        $helper = $this->getHelper();
        $campaignId = $cart->getMailchimpCampaignId();
        $oneCart = array();
        $oneCart['id'] = $cart->getEntityId();
        $customer = $this->_getCustomer($cart, $mailchimpStoreId, $magentoStoreId);
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
        $lines = array();
        // get all items on the cart
        $items = $cart->getAllVisibleItems();
        $itemCount = 0;
        foreach ($items as $item) {
            $line = array();
            if ($item->getProductType() == 'bundle' || $item->getProductType() == 'grouped') {
                continue;
            }

            if ($item->getProductType() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
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
            $itemCount++;
            $line['id'] = (string)$itemCount;
            $line['product_id'] = $item->getProductId();
            $line['product_variant_id'] = $variantId;
            $line['quantity'] = (int)$item->getQty();
            $line['price'] = $item->getRowTotal();
            $lines[] = $line;
        }

        $jsonData = "";
        if ($itemCount) {
            $oneCart['lines'] = $lines;
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
     * Get URL for the cart.
     *
     * @param  $cart
     * @param $isModified
     * @return string
     */
    protected function _getCheckoutUrl($cart, $isModified)
    {
        if (!$isModified) {
            $token = md5(rand(0, 9999999));
        } else {
            $token = $cart->getMailchimpToken();
        }
        $url = Mage::getModel('core/url')->setStore($cart->getStoreId())->getUrl('', array('_nosid' => true, '_secure' => true)) . 'mailchimp/cart/loadquote?id=' . $cart->getEntityId() . '&token=' . $token;
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
     * @param  $mailchimpStoreId
     * @param  $magentoStoreId
     * @return array
     */
    protected function _getCustomer($cart, $mailchimpStoreId, $magentoStoreId)
    {
        $helper = $this->getHelper();
        $customer = array();
        try {
            $api = $helper->getApi($magentoStoreId);
        } catch (Ebizmarts_MailChimp_Helper_Data_ApiKeyException $e) {
            $helper->logError($e->getMessage());
            return $customer;
        }

        if ($cart->getCustomerId()) {
            try {
                $customer = $api->ecommerce->customers->get($mailchimpStoreId, $cart->getCustomerId(), 'email_address');
            } catch (MailChimp_Error $e) {
                $err = $e->getMailchimpTitle();
                if (!preg_match('/Resource Not Found for Api Call/', $err)) {
                    $msg = "Failed to lookup e-commerce customer via ID " . $cart->getCustomerId();
                    $helper->logError($msg . ': ' . $e->getFriendlyMessage());
                }
            }
            $custEmailAddr = null;
            if (isset($customer['email_address'])) {
                $custEmailAddr = $customer['email_address'];
            }
            $customer = array(
                "id" => $cart->getCustomerId(),
                "email_address" => ($custEmailAddr) ? $custEmailAddr : $cart->getCustomerEmail(),
                "opt_in_status" => Mage::getModel('mailchimp/api_customers')->getOptin($magentoStoreId)
            );
        } else {
            try {
                $customers = $api->ecommerce->customers->getByEmail($mailchimpStoreId, $cart->getCustomerEmail());
            } catch (MailChimp_Error $e) {
                $helper->logError($e->getFriendlyMessage());
            }

            if (isset($customers['total_items']) && $customers['total_items'] > 0) {
                $customer = array(
                    'id' => $customers['customers'][0]['id'],
                    "email_address" => $cart->getCustomerEmail(),
                    "opt_in_status" => false
                );
            } else {
                $date = $helper->getDateMicrotime();
                $customer = array(
                    "id" => ($cart->getCustomerId()) ? $cart->getCustomerId() : "GUEST-" . $date,
                    "email_address" => $cart->getCustomerEmail(),
                    "opt_in_status" => Mage::getModel('mailchimp/api_customers')->getOptin($magentoStoreId)
                );
            }
        }

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
            if ($street[0]) {
                $address['address1'] = $street[0];
            }

            if (count($street) > 1) {
                $address['address1'] = $street[1];
            }

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
                $address['country'] = Mage::getModel('directory/country')->loadByCode($billingAddress->getCountry())->getName();
                $address['country_code'] = $billingAddress->getCountry();
            }

            if (count($address)) {
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
     * update product sync data
     *
     * @param $cartId
     * @param $mailchimpStoreId
     * @param int|null $syncDelta
     * @param int|null $syncError
     * @param int|null $syncModified
     * @param int|null $syncedFlag
     * @param int|null $syncDeleted
     * @param string|null $token
     */
    protected function _updateSyncData($cartId, $mailchimpStoreId, $syncDelta = null, $syncError = null, $syncModified = 0, $syncedFlag = null, $syncDeleted = null, $token = null)
    {
        $helper = $this->getHelper();
        $helper->saveEcommerceSyncData($cartId, Ebizmarts_MailChimp_Model_Config::IS_QUOTE, $mailchimpStoreId, $syncDelta, $syncError, $syncModified, $syncDeleted, $token, $syncedFlag);
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
        $productData = Mage::getModel('mailchimp/api_products')->sendModifiedProduct($cart, $mailchimpStoreId, $magentoStoreId);
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
     * @param $newCarts
     * @param $mailchimpStoreId
     */
    public function joinMailchimpSyncDataWithoutWhere($newCarts, $mailchimpStoreId)
    {
        $mailchimpTableName = $this->getMailchimpEcommerceDataTableName();
        $newCarts->getSelect()->joinLeft(
            array('m4m' => $mailchimpTableName),
            "m4m.related_id = main_table.entity_id and m4m.type = '" . Ebizmarts_MailChimp_Model_Config::IS_QUOTE . "'
            AND m4m.mailchimp_store_id = '" . $mailchimpStoreId . "'",
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
}

