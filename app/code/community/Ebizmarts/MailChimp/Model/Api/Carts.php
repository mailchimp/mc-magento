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
        $allCarts = array();
        if (!$helper->isAbandonedCartEnabled($magentoStoreId)) {
            return $allCarts;
        }

        $this->_firstDate = $helper->getAbandonedCartFirstDate($magentoStoreId);
        $this->_counter = 0;

        $date = $helper->getDateMicrotime();
        $this->_batchId = 'storeid-' . $magentoStoreId . '_' . Ebizmarts_MailChimp_Model_Config::IS_QUOTE.'_'.$date;
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
    protected function _getConvertedQuotes($mailchimpStoreId, $magentoStoreId)
    {
        $mailchimpTableName = Mage::getSingleton('core/resource')->getTableName('mailchimp/ecommercesyncdata');
        $allCarts = array();
        $convertedCarts = Mage::getResourceModel('sales/quote_collection');
        // get only the converted quotes
        $convertedCarts->addFieldToFilter('store_id', array('eq' => $magentoStoreId));
        $convertedCarts->addFieldToFilter('is_active', array('eq' => 0));
        //join with mailchimp_ecommerce_sync_data table to filter by sync data.
        $convertedCarts->getSelect()->joinLeft(
            array('m4m' => $mailchimpTableName),
            "m4m.related_id = main_table.entity_id and m4m.type = '".Ebizmarts_MailChimp_Model_Config::IS_QUOTE."' 
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
            $allCartsForEmail = $this->_getAllCartsByEmail($cart->getCustomerEmail(), $mailchimpStoreId, $magentoStoreId);
            foreach ($allCartsForEmail as $cartForEmail) {
                $alreadySentCartId = $cartForEmail->getEntityId();
                if ($alreadySentCartId != $cartId) {
                    $allCarts[$this->_counter]['method'] = 'DELETE';
                    $allCarts[$this->_counter]['path'] = '/ecommerce/stores/' . $mailchimpStoreId . '/carts/' . $alreadySentCartId;
                    $allCarts[$this->_counter]['operation_id'] = $this->_batchId . '_' . $alreadySentCartId;
                    $allCarts[$this->_counter]['body'] = '';
                    $this->_updateSyncData($alreadySentCartId, $mailchimpStoreId, Varien_Date::now(), null, null, 1);
                    $this->_counter += 1;
                }
            }

            $allCartsForEmail->clear();
            $allCarts[$this->_counter]['method'] = 'DELETE';
            $allCarts[$this->_counter]['path'] = '/ecommerce/stores/' . $mailchimpStoreId . '/carts/' . $cartId;
            $allCarts[$this->_counter]['operation_id'] = $this->_batchId . '_' . $cartId;
            $allCarts[$this->_counter]['body'] = '';
            $this->_updateSyncData($cartId, $mailchimpStoreId, Varien_Date::now(), null, null, 1);
            $this->_counter += 1;
        }

        return $allCarts;
    }

    /**
     * @param $mailchimpStoreId
     * @param $magentoStoreId
     * @return array
     */
    protected function _getModifiedQuotes($mailchimpStoreId, $magentoStoreId)
    {
        $mailchimpTableName = Mage::getSingleton('core/resource')->getTableName('mailchimp/ecommercesyncdata');
        $allCarts = array();
        $modifiedCarts = Mage::getResourceModel('sales/quote_collection');
        // select carts with no orders
        $modifiedCarts->addFieldToFilter('is_active', array('eq'=>1));
        // select carts for the current Magento store id
        $modifiedCarts->addFieldToFilter('store_id', array('eq' => $magentoStoreId));
        //join with mailchimp_ecommerce_sync_data table to filter by sync data.
        $modifiedCarts->getSelect()->joinLeft(
            array('m4m' => $mailchimpTableName),
            "m4m.related_id = main_table.entity_id and m4m.type = '".Ebizmarts_MailChimp_Model_Config::IS_QUOTE."'
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
            $allCarts[$this->_counter]['method'] = 'DELETE';
            $allCarts[$this->_counter]['path'] = '/ecommerce/stores/' . $mailchimpStoreId . '/carts/' . $cartId;
            $allCarts[$this->_counter]['operation_id'] = $this->_batchId . '_' . $cartId;
            $allCarts[$this->_counter]['body'] = '';
            $this->_counter += 1;
            /**
             * @var $customer Mage_Customer_Model_Customer
             */
            $customer = Mage::getModel("customer/customer");
            $customer->setWebsiteId(Mage::getModel('core/store')->load($magentoStoreId)->getWebsiteId());
            $customer->loadByEmail($cart->getCustomerEmail());
            if ($customer->getEmail() != $cart->getCustomerEmail()) {
                $allCartsForEmail = $this->_getAllCartsByEmail($cart->getCustomerEmail(), $mailchimpStoreId, $magentoStoreId);
                foreach ($allCartsForEmail as $cartForEmail) {
                    $alreadySentCartId = $cartForEmail->getEntityId();
                    if($alreadySentCartId != $cartId) {
                        $allCarts[$this->_counter]['method'] = 'DELETE';
                        $allCarts[$this->_counter]['path'] = '/ecommerce/stores/' . $mailchimpStoreId . '/carts/' . $alreadySentCartId;
                        $allCarts[$this->_counter]['operation_id'] = $this->_batchId . '_' . $alreadySentCartId;
                        $allCarts[$this->_counter]['body'] = '';
                        $this->_updateSyncData($alreadySentCartId, $mailchimpStoreId, Varien_Date::now(), null, null, 1);
                        $this->_counter += 1;
                    }
                }

                $allCartsForEmail->clear();
            }

            // avoid carts abandoned as guests when customer email associated to a registered customer.
            if (!$cart->getCustomerId() && $customer->getEmail()==$cart->getCustomerEmail()) {
                $this->_updateSyncData($cartId, $mailchimpStoreId, Varien_Date::now());
                continue;
            }

            // send the products that not already sent
            $allCarts = $this->addProductNotSentData($mailchimpStoreId, $magentoStoreId, $cart, $allCarts);

            $cartJson = $this->_makeCart($cart, $mailchimpStoreId, $magentoStoreId);
            if ($cartJson!="") {
                $allCarts[$this->_counter]['method'] = 'POST';
                $allCarts[$this->_counter]['path'] = '/ecommerce/stores/' . $mailchimpStoreId . '/carts';
                $allCarts[$this->_counter]['operation_id'] = $this->_batchId . '_' . $cartId;
                $allCarts[$this->_counter]['body'] = $cartJson;
                $this->_counter += 1;
                $this->_updateSyncData($cartId, $mailchimpStoreId, Varien_Date::now(), null, null, null, $this->_token);
            } else {
                $this->_updateSyncData($cartId, $mailchimpStoreId, Varien_Date::now());
            }

            $this->_token = null;
        }

        return $allCarts;
    }

    /**
     * @param $mailchimpStoreId
     * @return array
     */
    protected function _getNewQuotes($mailchimpStoreId, $magentoStoreId)
    {
        $helper = $this->getHelper();
        $allCarts = array();
        $newCarts = Mage::getResourceModel('sales/quote_collection');
        $newCarts->addFieldToFilter('is_active', array('eq'=>1));
        $newCarts->addFieldToFilter('customer_email', array('notnull'=>true));
        $newCarts->addFieldToFilter('items_count', array('gt'=>0));
        // select carts for the current Magento store id
        $newCarts->addFieldToFilter('store_id', array('eq' => $magentoStoreId));
        $helper->addResendFilter($newCarts, $magentoStoreId);
        // filter by first date if exists.
        if ($this->_firstDate) {
            $newCarts->addFieldToFilter('updated_at', array('gt' => $this->_firstDate));
        }

        //join with mailchimp_ecommerce_sync_data table to filter by sync data.
        $this->joinMailchimpSyncDataWithoutWhere($newCarts, $mailchimpStoreId);
        // be sure that the quotes are already in mailchimp and not deleted
        $newCarts->getSelect()->where("m4m.mailchimp_sync_delta IS NULL");
        // limit the collection
        $newCarts->getSelect()->limit($this->getBatchLimitFromConfig());

        foreach ($newCarts as $cart) {
            $cartId = $cart->getEntityId();
            $orderCollection = Mage::getResourceModel('sales/order_collection');
            $orderCollection->addFieldToFilter('main_table.customer_email', array('eq' => $cart->getCustomerEmail()))
                ->addFieldToFilter('main_table.updated_at', array('from' => $cart->getUpdatedAt()));
            //if cart is empty or customer has an order made after the abandonment skip current cart.
            if (!count($cart->getAllVisibleItems()) || $orderCollection->getSize()) {
                $this->_updateSyncData($cartId, $mailchimpStoreId, Varien_Date::now());
                continue;
            }

            $customer = Mage::getModel("customer/customer");
            $customer->setWebsiteId(Mage::getModel('core/store')->load($magentoStoreId)->getWebsiteId());
            $customer->loadByEmail($cart->getCustomerEmail());
            if ($customer->getEmail() != $cart->getCustomerEmail()) {
                $allCartsForEmail = $this->_getAllCartsByEmail($cart->getCustomerEmail(), $mailchimpStoreId, $magentoStoreId);
                foreach ($allCartsForEmail as $cartForEmail) {
                    $alreadySentCartId = $cartForEmail->getEntityId();
                    $allCarts[$this->_counter]['method'] = 'DELETE';
                    $allCarts[$this->_counter]['path'] = '/ecommerce/stores/' . $mailchimpStoreId . '/carts/' . $alreadySentCartId;
                    $allCarts[$this->_counter]['operation_id'] = $this->_batchId . '_' . $alreadySentCartId;
                    $allCarts[$this->_counter]['body'] = '';
                    $this->_updateSyncData($alreadySentCartId, $mailchimpStoreId, Varien_Date::now(), null, null, 1);
                    $this->_counter += 1;
                }

                $allCartsForEmail->clear();
            }

            // don't send the carts for guest customers who are registered
            if (!$cart->getCustomerId()&&$customer->getEmail()==$cart->getCustomerEmail()) {
                $this->_updateSyncData($cartId, $mailchimpStoreId, Varien_Date::now());
                continue;
            }

            // send the products that not already sent
            $allCarts = $this->addProductNotSentData($mailchimpStoreId, $magentoStoreId, $cart, $allCarts);

            $cartJson = $this->_makeCart($cart, $mailchimpStoreId, $magentoStoreId);
            if ($cartJson!="") {
                $allCarts[$this->_counter]['method'] = 'POST';
                $allCarts[$this->_counter]['path'] = '/ecommerce/stores/' . $mailchimpStoreId . '/carts';
                $allCarts[$this->_counter]['operation_id'] = $this->_batchId . '_' . $cartId;
                $allCarts[$this->_counter]['body'] = $cartJson;
                $this->_counter += 1;
                $this->_updateSyncData($cartId, $mailchimpStoreId, Varien_Date::now(), null, null, null, $this->_token);
            } else {
                $this->_updateSyncData($cartId, $mailchimpStoreId, Varien_Date::now());
            }

            $this->_token = null;
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
    protected function _getAllCartsByEmail($email, $mailchimpStoreId, $magentoStoreId)
    {
        $mailchimpTableName = Mage::getSingleton('core/resource')->getTableName('mailchimp/ecommercesyncdata');
        $allCartsForEmail = Mage::getResourceModel('sales/quote_collection');
        $allCartsForEmail->addFieldToFilter('is_active', array('eq' => 1));
        $allCartsForEmail->addFieldToFilter('store_id', array('eq' => $magentoStoreId));
        $allCartsForEmail->addFieldToFilter('customer_email', array('eq' => $email));
        $allCartsForEmail->getSelect()->joinLeft(
            array('m4m' => $mailchimpTableName),
            "m4m.related_id = main_table.entity_id and m4m.type = '".Ebizmarts_MailChimp_Model_Config::IS_QUOTE."'
            AND m4m.mailchimp_store_id = '" . $mailchimpStoreId . "'",
            array('m4m.*')
        );
        // be sure that the quotes are already in mailchimp and not deleted
        $allCartsForEmail->getSelect()->where("m4m.mailchimp_sync_deleted = 0 AND m4m.mailchimp_store_id = '".$mailchimpStoreId."'");
        return $allCartsForEmail;
    }

    /**
     * @param $cart
     * @param $mailchimpStoreId
     * @param $magentoStoreId
     * @return string
     */
    protected function _makeCart($cart, $mailchimpStoreId, $magentoStoreId)
    {
        $campaignId = $cart->getMailchimpCampaignId();
        $oneCart = array();
        $oneCart['id'] = $cart->getEntityId();
        $oneCart['customer'] = $this->_getCustomer($cart, $mailchimpStoreId, $magentoStoreId);
        if ($campaignId) {
            $oneCart['campaign_id'] = $campaignId;
        }

        $oneCart['checkout_url'] = $this->_getCheckoutUrl($cart);
        $oneCart['currency_code'] = $cart->getQuoteCurrencyCode();
        $oneCart['order_total'] = $cart->getGrandTotal();
        $oneCart['tax_total'] = 0;
        $lines = array();
        // get all items on the cart
        $items = $cart->getAllVisibleItems();
        $itemCount = 0;
        foreach ($items as $item) {
            $line = array();
            if ($item->getProductType()=='bundle'||$item->getProductType()=='grouped') {
                continue;
            }

            if ($item->getProductType()==Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
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
                Mage::helper('mailchimp')->logError("Carts " . $cart->getId() . " json encode failed", $magentoStoreId);
            }
        }

        return $jsonData;
    }

    /**
     * Get URL for the cart.
     * 
     * @param  $cart
     * @return string
     */
    protected function _getCheckoutUrl($cart)
    {
        $token = md5(rand(0, 9999999));
        $url = Mage::getModel('core/url')->setStore($cart->getStoreId())->getUrl('', array('_nosid' => true,'_secure' => true)) . 'mailchimp/cart/loadquote?id=' . $cart->getEntityId() . '&token=' . $token;
        $this->_token = $token;
        return $url;
    }

    /**
     * @return mixed
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
        $api = Mage::helper('mailchimp')->getApi($magentoStoreId);
        $customers = array();
        try {
            $customers = $api->ecommerce->customers->getByEmail($mailchimpStoreId, $cart->getCustomerEmail());
        } catch (MailChimp_Error $e) {
            Mage::helper('mailchimp')->logError($e->getFriendlyMessage(), $magentoStoreId);
        }

        if (isset($customers['total_items']) && $customers['total_items'] > 0) {
            $customer = array(
              'id' => $customers['customers'][0]['id']
            );
        } else {
            if (!$cart->getCustomerId()) {
                $date = Mage::helper('mailchimp')->getDateMicrotime();
                $customer = array(
                    "id" => "GUEST-" . $date,
                    "email_address" => $cart->getCustomerEmail(),
                    "opt_in_status" => false
                );
            } else {
                $customer = array(
                    "id" => $cart->getCustomerId(),
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
     * @param null             $syncDelta
     * @param null             $syncError
     * @param int              $syncModified
     * @param null             $syncDeleted
     * @param null             $token
     */
    protected function _updateSyncData($cartId, $mailchimpStoreId, $syncDelta = null, $syncError = null, $syncModified = 0, $syncDeleted = null, $token = null)
    {
        Mage::helper('mailchimp')->saveEcommerceSyncData($cartId, Ebizmarts_MailChimp_Model_Config::IS_QUOTE, $mailchimpStoreId, $syncDelta, $syncError, $syncModified, $syncDeleted, $token);
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
        $productData = Mage::getModel('mailchimp/api_products')->sendModifiedProduct($cart, $mailchimpStoreId, $magentoStoreId);
        $productDataArray = Mage::helper('mailchimp')->addEntriesToArray($allCarts, $productData, $this->_counter);
        $allCarts = $productDataArray[0];
        $this->_counter = $productDataArray[1];
        return $allCarts;
    }

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
        $mailchimpTableName = Mage::getSingleton('core/resource')->getTableName('mailchimp/ecommercesyncdata');
        $newCarts->getSelect()->joinLeft(
            array('m4m' => $mailchimpTableName),
            "m4m.related_id = main_table.entity_id and m4m.type = '" . Ebizmarts_MailChimp_Model_Config::IS_QUOTE . "'
            AND m4m.mailchimp_store_id = '" . $mailchimpStoreId . "'",
            array('m4m.*')
        );
    }
}
