<?php
/**
 * mailchimp-lib Magento Component
 *
 * @category  Ebizmarts
 * @package   mailchimp-lib
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     4/29/16 4:12 PM
 * @file:     EcommerceStoresCarts.php
 */
class MailChimp_EcommerceCarts extends MailChimp_Abstract
{
    /**
     * @param       $storeId        The store id.
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
    public function getAll($storeId, $fields = null, $excludeFields = null, $count = null, $offset = null)
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

        return $this->_master->call('ecommerce/stores/' . $storeId . '/carts', $_params, Ebizmarts_MailChimp::GET);
    }
}
