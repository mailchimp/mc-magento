<?php
/**
 * mailchimp-lib Magento Component
 *
 * @category  Ebizmarts
 * @package   mailchimp-lib
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     4/29/16 4:32 PM
 * @file:     EcommerceOrdersLines.php
 */
class MailChimp_EcommerceOrdersLines extends MailChimp_Abstract
{
    /**
     * @param $storeId              The store id.
     * @param $orderId              The id for the order in a store.
     * @param $id                   A unique identifier for the order line item.
     * @param $productId            A unique identifier for the product associated with the order line item.
     * @param $productVariantId     A unique identifier for the product variant associated with the order line item.
     * @param $quantity             The quantity of an order line item.
     * @param $price                The price of an order line item.
     * @return mixed
     * @throws MailChimp_Error
     * @throws MailChimp_HttpError
     */
    public function add($storeId, $orderId, $id, $productId, $productVariantId, $quantity, $price)
    {
        $_params = array(
            'id'=>$id,'product_id'=>$productId,'product_variant_id'=>$productVariantId,
            'quantity'=>$quantity, 'price'=>$price
        );
        $url = '/ecommerce/stores/' . $storeId . '/orders/' . $orderId . '/lines';

        return $this->_master->call($url, $_params, Ebizmarts_MailChimp::POST);
    }

    /**
     * @param       $storeId        The store id.
     * @param       $orderId        The id for the order in a store.
     * @param null  $fields         A comma-separated list of fields to return. Reference parameters of sub-objects
     *                                  with dot notation.
     * @param null  $excludeFields  A comma-separated list of fields to exclude. Reference parameters of sub-objects
     *                                  with dot notation.
     * @param null  $count          The number of records to return.
     * @param null  $offset         The number of records from a collection to skip. Iterating over large collections
     *                                  with this parameter can be slow.
     * @return mixed
     * @throws MailChimp_Error
     * @throws MailChimp_HttpError
     */
    public function getAll($storeId, $orderId, $fields = null, $excludeFields = null, $count = null, $offset = null)
    {
        $_params = array();
        if ($fields) {
            $_params['fields'] = $fields;
        }

        if ($excludeFields) {
            $_params['exclude_fields'] = $excludeFields;
        }

        if ($count) {
            $_params['count'] = $count;
        }

        if ($offset) {
            $_params['offset'] = $offset;
        }

        $url = '/ecommerce/stores/' . $storeId . '/orders/' . $orderId . '/lines';

        return $this->_master->call($url, $_params, Ebizmarts_MailChimp::GET);
    }

    /**
     * @param       $storeId        The store id.
     * @param       $orderId        The id for the order in a store.
     * @param       $lineId         The id for the line item of an order.
     * @param null  $fields         A comma-separated list of fields to return. Reference parameters of sub-objects
     *                                  with dot notation.
     * @param null  $excludeFields  A comma-separated list of fields to exclude. Reference parameters of sub-objects
     *                                  with dot notation.
     * @return mixed
     * @throws MailChimp_Error
     * @throws MailChimp_HttpError
     */
    public function get($storeId, $orderId, $lineId, $fields = null, $excludeFields = null)
    {
        $_params = array();

        if ($fields) {
            $_params['fields'] = $fields;
        }

        if ($excludeFields) {
            $_params['exclude_fields'] = $excludeFields;
        }

        $url = '/ecommerce/stores/' . $storeId . '/orders/' . $orderId . '/lines/' . $lineId;

        return $this->_master->call($url, $_params, Ebizmarts_MailChimp::GET);
    }

    /**
     * @param       $storeId            The store id.
     * @param       $orderId            The id for the order in a store.
     * @param       $lineId             The id for the line item of an order.
     * @param null  $productId          The unique identifier for the product associated with the order line item.
     * @param null  $productVariantId   A unique identifier for the product variant associated with the order line item.
     * @param null  $quantity           The quantity of an order line item.
     * @param null  $price              The price of an order line item.
     * @return mixed
     * @throws MailChimp_Error
     * @throws MailChimp_HttpError
     */
    public function modify(
        $storeId,
        $orderId,
        $lineId,
        $productId = null,
        $productVariantId = null,
        $quantity = null,
        $price = null
    ){
        $_params = array();

        if ($productId) {
            $_params['product_id'] = $productId;
        }

        if ($productVariantId) {
            $_params['product_variant_id'] = $productVariantId;
        }

        if ($quantity) {
            $_params['quantity'] = $quantity;
        }

        if ($price) {
            $_params['price'] = $price;
        }

        $url = '/ecommerce/stores/' . $storeId . '/orders/' . $orderId . '/lines/' . $lineId;

        return $this->_master->call($url, $_params, Ebizmarts_MailChimp::PATCH);
    }

    /**
     * @param $storeId                  The store id.
     * @param $orderId                  The id for the order in a store.
     * @param $lineId                   The id for the line item of an order.
     * @return mixed
     * @throws MailChimp_Error
     * @throws MailChimp_HttpError
     */
    public function delete($storeId, $orderId, $lineId)
    {
        $url = '/ecommerce/stores/' . $storeId . '/orders/' . $orderId . '/lines/' . $lineId;

        return $this->_master->call($url, null, Ebizmarts_MailChimp::DELETE);
    }
}
