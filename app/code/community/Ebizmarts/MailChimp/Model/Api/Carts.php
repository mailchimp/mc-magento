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
    const NEWCART = 'new';
    const OLDCART = 'old';


    public function createBatchJson($mailchimpStoreId)
    {
        $allCarts = array();
        if(!Mage::getConfig(Ebizmarts_MailChimp_Model_Config::ABANDONEDCART_ACTIVE))
        {
            return $allCarts;
        }
        $firstDate = Mage::getStoreConfig(Ebizmarts_MailChimp_Model_Config::ABANDONEDCART_FIRSTDATE);
        $counter = 0;
        $batchId = Ebizmarts_MailChimp_Model_Config::IS_QUOTE.'_'.date('Y-m-d-H-i-s');
        // get all the carts converted in orders (must be deleted on mailchimp)
        $convertedCarts = Mage::getModel('sales/quote')->getCollection();
        // get only the converted quotes
        $convertedCarts->addFieldToFilter('is_active',array('eq'=>0));
        // be sure that the quote are already in mailchimp
        $convertedCarts->addFieldToFilter('mailchimp_sync_delta',array(
           array('neq' => '0000-00-00 00:00:00'),
            array('null',false)
        ));
        // and not deleted
        $convertedCarts->addFieldToFilter('mailchimp_deleted',array('eq'=>0));
        // limit the collection
        $convertedCarts->getSelect()->limit(self::BATCH_LIMIT);
        foreach($convertedCarts as $cart)
        {
            if(!$cart->getCustomerId())
            {
                // we need to delete all the carts associated with this email
                $allCartsForEmail = Mage::getModel('sales/quote')->getCollection();
                $allCartsForEmail->addFieldToFilter('is_active',array('eq'=>1));
                $allCartsForEmail->addFieldToFilter('mailchimp_sync_delta',array(
                    array('neq' => '0000-00-00 00:00:00'),
                    array('null',false)
                ));
                $allCartsForEmail->addFieldToFilter('mailchimp_deleted',array('eq'=>0));
                $allCartsForEmail->addFieldToFilter('customer_email',array('eq'=>$cart->getCustomerEmail()));
                foreach($allCartsForEmail as $cartForEmail)
                {
                    $allCarts[$counter]['method'] = 'DELETE';
                    $allCarts[$counter]['path'] = '/ecommerce/stores/' . $mailchimpStoreId . '/carts/' . $cartForEmail->getEntityId();
                    $allCarts[$counter]['operation_id'] = $batchId . '_' . $cartForEmail->getEntityId();
                    $allCarts[$counter]['body'] = '';
                    $cartForEmail->setData("mailchimp_sync_delta", Varien_Date::now());
                    $cartForEmail->setMailchimpDeleted(1);
                    $cartForEmail->save();
                    $counter += 1;
                }
                $allCartsForEmail->clear();
            }
            $allCarts[$counter]['method'] = 'DELETE';
            $allCarts[$counter]['path'] = '/ecommerce/stores/' . $mailchimpStoreId . '/carts/' . $cart->getEntityId();
            $allCarts[$counter]['operation_id'] = $batchId . '_' . $cart->getEntityId();
            $allCarts[$counter]['body'] = '';
            $cart->setData("mailchimp_sync_delta", Varien_Date::now());
            $cart->setMailchimpDeleted(1);
            $cart->save();
            $counter += 1;
        }

        // get all the carts modified but not converted in orders

        // get new carts
        $newCarts = Mage::getModel('sales/quote')->getCollection();
        $newCarts->addFieldToFilter('is_active',array('eq'=>1))
            ->addFieldToFilter('mailchimp_sync_delta',
                array(
                    array('eq'=>'0000-00-00 00:00:00'),
                    array('null'=>true)
                )
            );
        $newCarts->addFieldToFilter('created_at',array('from'=>$firstDate));
        $newCarts->addFieldToFilter('customer_email',array('notnull'=>true));
        $newCarts->getSelect()->limit(self::BATCH_LIMIT);

        foreach($newCarts as $cart)
        {
            $cartJson = $this->_makeCart($cart,self::NEWCART);
            $allCarts[$counter]['method'] = 'POST';
            $allCarts[$counter]['path'] = '/ecommerce/stores/' . $mailchimpStoreId . '/carts';
            $allCarts[$counter]['operation_id'] = $batchId . '_' . $cart->getEntityId();
            $allCarts[$counter]['body'] = $cartJson;
            $cart->setData("mailchimp_sync_delta", Varien_Date::now());
            $cart->save();
            $counter += 1;
        }
        return $allCarts;
    }

    protected function _makeCart($cart,$mode)
    {
        $oneCart = array();
        $oneCart['id'] = $cart->getEntityId();
        $oneCart['customer'] = $this->_getCustomer($cart);
//        $oneCart['campaign_id'] = '';
        $oneCart['checkout_url'] = $this->_getCheckoutUrl($cart);
        $oneCart['currency_code'] = $cart->getQuoteCurrencyCode();
        $oneCart['order_total'] = $cart->getGrandTotal();
        $oneCart['tax_total'] = 0;
        $lines = array();
        // get all items on the cart
        $items = $cart->getAllVisibleItems();
        $item_count = 0;
        foreach($items as $item)
        {
            $line = array();
            if($item->getProductType()==Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
                $options = $item->getProductOptions();
                $sku = $options['simple_sku'];
                $variant = Mage::getModel('catalog/product')->getIdBySku($sku);
            }
            else {
                $variant = $item->getProductId();
            }

            $line['id'] = (string)$item_count;
            $line['product_id'] = $item->getProductId();
            $line['product_variant_id'] = $variant;
            $line['quantity'] = (int)$item->getQtyOrdered();
            $line['price'] = $item->getPrice();
            $lines[] = $line;
            $item_count += 1;
        }
        $oneCart['lines'] = $lines;

        $jsonData = "";

        //enconde to JSON
        try {

            $jsonData = json_encode($oneCart);

        } catch (Exception $e) {
            //json encode failed
            Mage::helper('mailchimp')->logError("Carts ".$cart->getId()." json encode failed");
        }

        return $jsonData;
    }
    // @todo calculate the checkout url for the cart
    protected function _getCheckoutUrl($cart)
    {
        $token = md5(rand(0, 9999999));
        $url = Mage::getModel('core/url')->setStore($cart->getStoreId())->getUrl('', array('_nosid' => true)) . 'mailchimp/cart/loadquote?id=' . $cart->getEntityId() . '&token=' . $token;
        $cart->setMailchimpToken($token);
        return $url;
    }
    protected function _getCustomer($cart)
    {
        if (!$cart->getCustomerId()) {
            $customer = array(
                "id" => "GUEST-" . date('Y-m-d-H-i-s'),
                "email_address" => $cart->getCustomerEmail(),
                "opt_in_status" => false
            );
        } else {
            $customer = array(
                "id" => $cart->getCustomerId(),
                "email_address" => $cart->getCustomerEmail(),
                "opt_in_status" => Ebizmarts_MailChimp_Model_Api_Customers::DEFAULT_OPT_IN
            );
            $billingAddress = $cart->getBillingAddress();
            $street = $billingAddress->getStreet();
            $customer["first_name"] = $cart->getCustomerFirstname();
            $customer["last_name"] = $cart->getCustomerLastname();
            $customer["address"] = array(
                "address1" => $street[0],
                "address2" => count($street) > 1 ? $street[1] : "",
                "city" => $billingAddress->getCity(),
                "province" => $billingAddress->getRegion() ? $billingAddress->getRegion() : "",
                "province_code" => $billingAddress->getRegionCode() ? $billingAddress->getRegionCode() : "",
                "postal_code" => $billingAddress->getPostcode(),
                "country" => Mage::getModel('directory/country')->loadByCode($billingAddress->getCountry())->getName(),
                "country_code" => $billingAddress->getCountry()
            );
            //company
            if ($billingAddress->getCompany()) {
                $customer["company"] = $billingAddress->getCompany();
            }
        }
        return $customer;
    }
}