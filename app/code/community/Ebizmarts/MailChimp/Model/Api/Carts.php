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

    const BATCH_LIMIT = 100;

    protected $_firstDate;
    protected $_counter;
    protected $_batchId;
    protected $_api = null;

    /**
     * @param $mailchimpStoreId
     * @return array
     */
    public function createBatchJson($mailchimpStoreId)
    {
        $allCarts = array();
        if (!Mage::getStoreConfig(Ebizmarts_MailChimp_Model_Config::ABANDONEDCART_ACTIVE)) {
            return $allCarts;
        }

        $this->_firstDate = Mage::getStoreConfig(Ebizmarts_MailChimp_Model_Config::ABANDONEDCART_FIRSTDATE);
        $this->_counter = 0;

        $date = Mage::helper('mailchimp')->getDateMicrotime();
        $this->_batchId = Ebizmarts_MailChimp_Model_Config::IS_QUOTE.'_'.$date;
        // get all the carts converted in orders (must be deleted on mailchimp)
        $allCarts = array_merge($allCarts, $this->_getConvertedQuotes($mailchimpStoreId));
        // get all the carts modified but not converted in orders
        $allCarts = array_merge($allCarts, $this->_getModifiedQuotes($mailchimpStoreId));
        // get new carts
        $allCarts = array_merge($allCarts, $this->_getNewQuotes($mailchimpStoreId));
        return $allCarts;
    }

    /**
     * @param $mailchimpStoreId
     * @return array
     */
    protected function _getConvertedQuotes($mailchimpStoreId)
    {
        $allCarts = array();
        $convertedCarts = Mage::getModel('sales/quote')->getCollection();
        // get only the converted quotes
        $convertedCarts->addFieldToFilter('is_active', array('eq'=>0));
        // be sure that the quote are already in mailchimp
        $convertedCarts->addFieldToFilter('mailchimp_sync_delta', array('neq' => '0000-00-00 00:00:00'));
        $convertedCarts->addFieldToFilter('mailchimp_sync_delta', array('gt' => Mage::helper('mailchimp')->getMCMinSyncDateFlag()));
        // and not deleted
        $convertedCarts->addFieldToFilter('mailchimp_deleted', array('eq'=>0));
        if ($this->_firstDate) {
            $convertedCarts->addFieldToFilter('created_at', array('from' => $this->_firstDate));
        }

        // limit the collection
        $convertedCarts->getSelect()->limit(self::BATCH_LIMIT);
        foreach ($convertedCarts as $cart) {
            $cartId = $cart->getEntityId();
                // we need to delete all the carts associated with this email
            $allCartsForEmail = $this->_getAllCartsByEmail($cart->getCustomerEmail());
            foreach ($allCartsForEmail as $cartForEmail) {
                $alreadySentCartId = $cartForEmail->getEntityId();
                if ($alreadySentCartId != $cartId) {
                    $allCarts[$this->_counter]['method'] = 'DELETE';
                    $allCarts[$this->_counter]['path'] = '/ecommerce/stores/' . $mailchimpStoreId . '/carts/' . $alreadySentCartId;
                    $allCarts[$this->_counter]['operation_id'] = $this->_batchId . '_' . $alreadySentCartId;
                    $allCarts[$this->_counter]['body'] = '';
                    $cartForEmail->setData("mailchimp_sync_delta", Varien_Date::now());
                    $cartForEmail->setMailchimpDeleted(1);
                    $this->_saveCart($cartForEmail);
                    $this->_counter += 1;
                }
            }

            $allCartsForEmail->clear();
            $allCarts[$this->_counter]['method'] = 'DELETE';
            $allCarts[$this->_counter]['path'] = '/ecommerce/stores/' . $mailchimpStoreId . '/carts/' . $cartId;
            $allCarts[$this->_counter]['operation_id'] = $this->_batchId . '_' . $cartId;
            $allCarts[$this->_counter]['body'] = '';
            $cart->setData("mailchimp_sync_delta", Varien_Date::now());
            $cart->setMailchimpDeleted(1);
            $this->_saveCart($cart);
            $this->_counter += 1;
        }

        return $allCarts;
    }

    /**
     * @param $mailchimpStoreId
     * @return array
     */
    protected function _getModifiedQuotes($mailchimpStoreId)
    {
        $allCarts = array();
        $modifiedCarts = Mage::getModel('sales/quote')->getCollection();
        // select carts with no orders
        $modifiedCarts->addFieldToFilter('is_active', array('eq'=>1));
        // select carts already sent to mailchimp and moodifief after
        $modifiedCarts->addFieldToFilter('mailchimp_sync_delta', array('neq' => '0000-00-00 00:00:00'));
        $modifiedCarts->addFieldToFilter('mailchimp_sync_delta', array('gt' => Mage::helper('mailchimp')->getMCMinSyncDateFlag()));
        $modifiedCarts->addFieldToFilter('mailchimp_sync_delta', array('lt'=>new Zend_Db_Expr('updated_at')));
        // and not deleted in mailchimp
        $modifiedCarts->addFieldToFilter('mailchimp_deleted', array('eq'=>0));
        $modifiedCarts->getSelect()->limit(self::BATCH_LIMIT);
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
            $customer->setWebsiteId(Mage::getModel('core/store')->load($cart->getStoreId())->getWebsiteId());
            $customer->loadByEmail($cart->getCustomerEmail());
            if ($customer->getEmail() != $cart->getCustomerEmail()) {
                $allCartsForEmail = $this->_getAllCartsByEmail($cart->getCustomerEmail());
                foreach ($allCartsForEmail as $cartForEmail) {
                    $alreadySentCartId = $cartForEmail->getEntityId();
                    if($alreadySentCartId != $cartId) {
                        $allCarts[$this->_counter]['method'] = 'DELETE';
                        $allCarts[$this->_counter]['path'] = '/ecommerce/stores/' . $mailchimpStoreId . '/carts/' . $alreadySentCartId;
                        $allCarts[$this->_counter]['operation_id'] = $this->_batchId . '_' . $alreadySentCartId;
                        $allCarts[$this->_counter]['body'] = '';
                        $cartForEmail->setData("mailchimp_sync_delta", Varien_Date::now());
                        $cartForEmail->setMailchimpDeleted(1);
                        $this->_saveCart($cartForEmail);
                        $this->_counter += 1;
                    }
                }

                $allCartsForEmail->clear();
            }

            if (!$cart->getCustomerId()&&$customer->getEmail()==$cart->getCustomerEmail()) {
                $cart->setData("mailchimp_sync_delta", Varien_Date::now());
                $this->_saveCart($cart);
                continue;
            }

            // send the products that not already sent
            $productData = Mage::getModel('mailchimp/api_products')->sendModifiedProduct($cart, $mailchimpStoreId);
            if (count($productData)) {
                foreach($productData as $p) {
                    $allCarts[$this->_counter] = $p;
                    $this->_counter += 1;
                }
            }

            if (count($cart->getAllVisibleItems())) {
                $cartJson = $this->_makeCart($cart, $mailchimpStoreId);
                if ($cartJson!="") {
                    $allCarts[$this->_counter]['method'] = 'POST';
                    $allCarts[$this->_counter]['path'] = '/ecommerce/stores/' . $mailchimpStoreId . '/carts';
                    $allCarts[$this->_counter]['operation_id'] = $this->_batchId . '_' . $cartId;
                    $allCarts[$this->_counter]['body'] = $cartJson;
                    $this->_counter += 1;
                }
            }

            $cart->setData("mailchimp_sync_delta", Varien_Date::now());
            $this->_saveCart($cart);
        }

        return $allCarts;
    }

    /**
     * @param $mailchimpStoreId
     * @return array
     */
    protected function _getNewQuotes($mailchimpStoreId)
    {
        $allCarts = array();
        $newCarts = Mage::getModel('sales/quote')->getCollection();
        $newCarts->addFieldToFilter('is_active', array('eq'=>1))
            ->addFieldToFilter(
                'mailchimp_sync_delta', array(
                array('eq' => '0000-00-00 00:00:00'),
                array('lt' => Mage::helper('mailchimp')->getMCMinSyncDateFlag())
                )
            );
        $newCarts->addFieldToFilter('created_at', array('from'=>$this->_firstDate));
        $newCarts->addFieldToFilter('customer_email', array('notnull'=>true));
        $newCarts->addFieldToFilter('items_count', array('gt'=>0));
        $newCarts->getSelect()->limit(self::BATCH_LIMIT);
        foreach ($newCarts as $cart) {
            if (!count($cart->getAllVisibleItems())) {
                $cart->setData("mailchimp_sync_delta", Varien_Date::now());
                $this->_saveCart($cart);
                continue;
            }

            $customer = Mage::getModel("customer/customer");
            $customer->setWebsiteId(Mage::getModel('core/store')->load($cart->getStoreId())->getWebsiteId());
            $customer->loadByEmail($cart->getCustomerEmail());
            if ($customer->getEmail() != $cart->getCustomerEmail()) {
                $allCartsForEmail = $this->_getAllCartsByEmail($cart->getCustomerEmail());
                foreach ($allCartsForEmail as $cartForEmail) {
                    $allCarts[$this->_counter]['method'] = 'DELETE';
                    $allCarts[$this->_counter]['path'] = '/ecommerce/stores/' . $mailchimpStoreId . '/carts/' . $cartForEmail->getEntityId();
                    $allCarts[$this->_counter]['operation_id'] = $this->_batchId . '_' . $cartForEmail->getEntityId();
                    $allCarts[$this->_counter]['body'] = '';
                    $cartForEmail->setData("mailchimp_sync_delta", Varien_Date::now());
                    $cartForEmail->setMailchimpDeleted(1);
                    $this->_saveCart($cartForEmail);
                    $this->_counter += 1;
                }

                $allCartsForEmail->clear();
            }

            // don't send the carts for guest customers who are registered
            if (!$cart->getCustomerId()&&$customer->getEmail()==$cart->getCustomerEmail()) {
                $cart->setData("mailchimp_sync_delta", Varien_Date::now());
                $this->_saveCart($cart);
                continue;
            }

            // send the products that not already sent
            $productData = Mage::getModel('mailchimp/api_products')->sendModifiedProduct($cart, $mailchimpStoreId);
            if (count($productData)) {
                foreach($productData as $p) {
                    $allCarts[$this->_counter] = $p;
                    $this->_counter += 1;
                }
            }

            $cartJson = $this->_makeCart($cart, $mailchimpStoreId);
            if ($cartJson!="") {
                $allCarts[$this->_counter]['method'] = 'POST';
                $allCarts[$this->_counter]['path'] = '/ecommerce/stores/' . $mailchimpStoreId . '/carts';
                $allCarts[$this->_counter]['operation_id'] = $this->_batchId . '_' . $cart->getEntityId();
                $allCarts[$this->_counter]['body'] = $cartJson;
                $cart->setData("mailchimp_sync_delta", Varien_Date::now());
                $this->_saveCart($cart);
                $this->_counter += 1;
            }
        }

        return $allCarts;
    }

    /**
     *
     * @param $email
     * @return object
     */
    protected function _getAllCartsByEmail($email)
    {
        $allCartsForEmail = Mage::getModel('sales/quote')->getCollection();
        $allCartsForEmail->addFieldToFilter('is_active', array('eq'=>1));
        $allCartsForEmail->addFieldToFilter('mailchimp_sync_delta', array('neq' => '0000-00-00 00:00:00'));
        $allCartsForEmail->addFieldToFilter('mailchimp_sync_delta', array('gt' => Mage::helper('mailchimp')->getMCMinSyncDateFlag()));
        $allCartsForEmail->addFieldToFilter('mailchimp_deleted', array('eq'=>0));
        $allCartsForEmail->addFieldToFilter('customer_email', array('eq'=>$email));
        return $allCartsForEmail;
    }

    /**
     * @param $cart
     * @param $mailchimpStoreId
     * @return string
     */
    protected function _makeCart($cart,$mailchimpStoreId)
    {
        $campaignId = $cart->getMailchimpCampaignId();
        $oneCart = array();
        $oneCart['id'] = $cart->getEntityId();
        $oneCart['customer'] = $this->_getCustomer($cart, $mailchimpStoreId);
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
            $line['price'] = $item->getPrice();
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
                Mage::helper('mailchimp')->logError("Carts " . $cart->getId() . " json encode failed");
            }
        }

        return $jsonData;
    }
    // @todo calculate the checkout url for the cart
    protected function _getCheckoutUrl($cart)
    {
        $token = md5(rand(0, 9999999));
        $url = Mage::getModel('core/url')->setStore($cart->getStoreId())->getUrl('', array('_nosid' => true,'_secure' => true)) . 'mailchimp/cart/loadquote?id=' . $cart->getEntityId() . '&token=' . $token;
        $cart->setMailchimpToken($token);
        return $url;
    }
    protected function _getCustomer($cart,$mailchimpStoreId)
    {
        $api = $this->_getApi();
        $customers = array();
        try {
            $customers = $api->ecommerce->customers->getByEmail($mailchimpStoreId, $cart->getCustomerEmail());
        } catch (Mailchimp_Error $e) {
            Mage::helper('mailchimp')->logError($e->getFriendlyMessage());
        }

        if (isset($customers['total_items']) && $customers['total_items'] > 0) {
            $customer = array(
              'id' => $customers['customers'][0]['id']
            );
        } else {
            if (!$cart->getCustomerId()) {
                $date = Mage::helper('mailchimp')->getDateMicrotime();
                $this->_batchId = Ebizmarts_MailChimp_Model_Config::IS_QUOTE.'_'.$date;
                $customer = array(
                    "id" => "GUEST-" . $date,
                    "email_address" => $cart->getCustomerEmail(),
                    "opt_in_status" => false
                );
            } else {
                $customer = array(
                    "id" => $cart->getCustomerId(),
                    "email_address" => $cart->getCustomerEmail(),
                    "opt_in_status" => Mage::getModel('mailchimp/api_customers')->getOptin()
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
    protected function _getApi()
    {
        if (!$this->_api) {
            $this->_api = Mage::helper('mailchimp')->getApi();
        }

        return $this->_api;
    }

    /**
     * @param $cart Mage_Sales_Model_Quote
     */
    protected function _saveCart($cart)
    {

        $cart->save();
    }
}