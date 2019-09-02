<?php

/**
 * mailchimp-lib Magento Component
 *
 * @category  Ebizmarts
 * @package   mailchimp-lib
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     4/29/16 4:34 PM
 * @file:     EcommerceProducts.php
 */
class MailChimp_EcommercePromoRulesPromoCodes extends MailChimp_Abstract
{

    /**
     * @param string $storeId            The MailChimp store id.
     * @param int    $promoRuleId        A unique identifier for the promo rule.
     * @param int    $promoCodeId        A unique identifier for the promo code associated to the rule above.
     * @param string $code               The discount code. Restricted to UTF-8 characters with max length 50.
     * @param string $redemptionUrl      The url that should be used in the promotion campaign restricted to
     *                                   UTF-8 characters with max length 2000.
     * @param int    $usageCount         Number of times promo code has been used.
     * @param null   $enabled            Whether the promo rule is currently enabled.
     * @param null   $createdAtForeign   The date and time the promotion was created in ISO 8601 format.
     * @param null   $updatedAtForeign   The date and time the promotion was updated in ISO 8601 format.
     *
     * @return mixed
     * @throws MailChimp_Error
     * @throws MailChimp_HttpError
     */
    public function add(
        $storeId,
        $promoRuleId,
        $promoCodeId,
        $code,
        $redemptionUrl,
        $usageCount,
        $enabled = null,
        $createdAtForeign = null,
        $updatedAtForeign = null
    ){
        $_params = array('id' => $promoCodeId, 'code' => $code, 'redemption_url' => $redemptionUrl);

        if ($usageCount) {
            $_params['usage_count'] = $usageCount;
        }

        if ($enabled) {
            $_params['enabled'] = $enabled;
        }

        if ($createdAtForeign) {
            $_params['created_at_foreign'] = $createdAtForeign;
        }

        if ($updatedAtForeign) {
            $_params['updated_at_foreign'] = $updatedAtForeign;
        }

        return $this->_master->call(
            'ecommerce/stores/' . $storeId . '/promo-rules/' . $promoRuleId
            . '/promo-codes', $_params, Ebizmarts_MailChimp::POST
        );
    }

    /**
     * @param string $storeId       The MailChimp store id.
     * @param int    $promoRuleId   A unique identifier for the promo rule.
     * @param null   $fields        A comma-separated list of fields to return. Reference parameters of sub-objects
     *                              with dot notation.
     * @param null   $excludeFields A comma-separated list of fields to exclude. Reference parameters of sub-objects
     *                              with dot notation.
     * @param null   $count         The number of records to return.
     * @param null   $offset        The number of records from a collection to skip. Iterating over large collections
     *                              with this parameter can be slow.
     * @return mixed
     * @throws MailChimp_Error
     * @throws MailChimp_HttpError
     */
    public function getAll($storeId, $promoRuleId, $fields = null, $excludeFields = null, $count = null, $offset = null)
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

        return $this->_master->call(
            'ecommerce/stores/' . $storeId . '/promo-rules/' . $promoRuleId
            . '/promo-codes', $_params, Ebizmarts_MailChimp::GET
        );
    }

    /**
     * @param string $storeId       The MailChimp store id.
     * @param int    $promoRuleId   The id for the promo rule of a store.
     * @param int    $promoCodeId   A unique identifier for the promo code associated to the rule above.
     * @param null   $fields        A comma-separated list of fields to return. Reference parameters of sub-objects
     *                              with dot notation.
     * @param null   $excludeFields A comma-separated list of fields to exclude. Reference parameters of sub-objects
     *                              with dot notation.
     * @return mixed
     * @throws MailChimp_Error
     * @throws MailChimp_HttpError
     */
    public function get($storeId, $promoRuleId, $promoCodeId, $fields = null, $excludeFields = null)
    {
        $_params = array();
        if ($fields) {
            $_params['fields'] = $fields;
        }

        if ($excludeFields) {
            $_params['exclude_fields'] = $excludeFields;
        }

        $url = 'ecommerce/stores/' . $storeId . '/promo-rules//' . $promoRuleId . '/promo-codes/' . $promoCodeId;

        return $this->_master->call($url, $_params, Ebizmarts_MailChimp::GET);
    }

    /**
     * @param string $storeId            The MailChimp store id.
     * @param int    $promoRuleId        A unique identifier for the promo rule.
     * @param int    $promoCodeId        A unique identifier for the promo code associated to the rule above.
     * @param string $code               The discount code. Restricted to UTF-8 characters with max length 50.
     * @param string $redemptionUrl      The url that should be used in the promotion campaign restricted to UTF-8
     *                                      characters with max length 2000.
     * @param int    $usageCount         Number of times promo code has been used.
     * @param null   $enabled            Whether the promo rule is currently enabled.
     * @param null   $createdAtForeign   The date and time the promotion was created in ISO 8601 format.
     * @param null   $updatedAtForeign   The date and time the promotion was updated in ISO 8601 format.
     *
     * @return mixed
     * @throws MailChimp_Error
     * @throws MailChimp_HttpError
     */
    public function modify(
        $storeId,
        $promoRuleId,
        $promoCodeId,
        $code = null,
        $redemptionUrl = null,
        $usageCount = null,
        $enabled = null,
        $createdAtForeign = null,
        $updatedAtForeign = null
    ) {
        $_params = array();

        if ($code) {
            $_params['code'] = $code;
        }

        if ($redemptionUrl) {
            $_params['redemption_url'] = $redemptionUrl;
        }

        if ($usageCount) {
            $_params['usage_count'] = $usageCount;
        }

        if ($enabled) {
            $_params['enabled'] = $enabled;
        }

        if ($createdAtForeign) {
            $_params['created_at_foreign'] = $createdAtForeign;
        }

        if ($updatedAtForeign) {
            $_params['updated_at_foreign'] = $updatedAtForeign;
        }

        return $this->_master->call(
            'ecommerce/stores/' . $storeId . '/promo-rules/' . $promoRuleId . '/promo-codes/'
            . $promoCodeId, $_params, Ebizmarts_MailChimp::PATCH
        );
    }

    /**
     * @param int $storeId     The MailChimp store id.
     * @param int $promoRuleId The id for the promo rule of a store.
     * @param int $promoCodeId A unique identifier for the promo code associated to the rule above.
     * @return mixed
     * @throws MailChimp_Error
     * @throws MailChimp_HttpError
     */
    public function delete($storeId, $promoRuleId, $promoCodeId)
    {
        $url = 'ecommerce/stores/' . $storeId . '/promo-rules/' . $promoRuleId . '/promo-codes/' . $promoCodeId;

        return $this->_master->call($url, null, Ebizmarts_MailChimp::DELETE);
    }
}
