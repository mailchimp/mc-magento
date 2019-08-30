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
class MailChimp_EcommercePromoRules extends MailChimp_Abstract
{
    /**
     * @var MailChimp_EcommercePromoRulesPromoCodes
     */
    public $promoCodes;

    /**
     * @param string $storeId           The MailChimp store id.
     * @param int    $promoRuleId       A unique identifier for the promo rule.
     * @param string $description       The description of a promotion restricted to UTF-8 characters with max length 255.
     * @param float  $amount            The amount of the promo code discount. If ‘type’ is ‘fixed’, the amount is treated as a
     *                                   monetary value. If ‘type’ is ‘percentage’, amount must be a decimal value between 0.0 and 1.0,
     *                                   inclusive.
     * @param string $type              Type of discount. For free shipping set type to fixed. Possible Values: 'fixed', 'percentage'
     * @param string $target            The target that the discount applies to. Possible Values: 'per_item', 'total', 'shipping'
     * @param null   $startsAt          The date and time when the promotion is in effect in ISO 8601 format.
     * @param null   $endsAt            The date and time when the promotion ends. Must be after starts_at and in ISO 8601 format.
     * @param null   $enabled           Whether the promo rule is currently enabled.
     * @param null   $createdAtForeign  The date and time the promotion was created in ISO 8601 format.
     * @param null   $updatedAtForeign  The date and time the promotion was updated in ISO 8601 format.
     * @param null   $title             The title of a promo rule.
     *
     * @return mixed
     * @throws MailChimp_Error
     * @throws MailChimp_HttpError
     */
    public function add(
        $storeId,
        $promoRuleId,
        $description,
        $amount,
        $type,
        $target,
        $startsAt = null,
        $endsAt = null,
        $enabled = null,
        $createdAtForeign = null,
        $updatedAtForeign = null,
        $title = null
    ) {
        $_params = array(
            'id' => $promoRuleId, 'description' => $description, 'amount' => $amount,
            'type' => $type, 'target' => $target
        );

        if ($title) {
            $_params['title'] = $title;
        }

        if ($startsAt) {
            $_params['starts_at'] = $startsAt;
        }

        if ($endsAt) {
            $_params['ends_at'] = $endsAt;
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
            'ecommerce/stores/' . $storeId . '/promo-rules', $_params, Ebizmarts_MailChimp::POST
        );
    }

    /**
     * @param string $storeId       The MailChimp store id.
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

        return $this->_master->call(
            'ecommerce/stores/' . $storeId . '/promo-rules', $_params, Ebizmarts_MailChimp::GET
        );
    }

    /**
     * @param string $storeId       The MailChimp store id.
     * @param int    $promoRuleId   The id for the promo rule of a store.
     * @param null   $fields        A comma-separated list of fields to return. Reference parameters of sub-objects
     *                              with dot notation.
     * @param null   $excludeFields A comma-separated list of fields to exclude. Reference parameters of sub-objects
     *                              with dot notation.
     * @return mixed
     * @throws MailChimp_Error
     * @throws MailChimp_HttpError
     */
    public function get($storeId, $promoRuleId, $fields = null, $excludeFields = null)
    {
        $_params = array();
        if ($fields) {
            $_params['fields'] = $fields;
        }

        if ($excludeFields) {
            $_params['exclude_fields'] = $excludeFields;
        }

        $url = 'ecommerce/stores/' . $storeId . '/promo-rules/' . $promoRuleId;

        return $this->_master->call($url, $_params, Ebizmarts_MailChimp::GET);
    }

    /**
     * @param string $storeId           The MailChimp store id.
     * @param int    $promoRuleId       The id for the promo rule of a store.
     * @param string $description       The description of a promotion restricted to UTF-8 characters with
     *                                      max length 255.
     * @param float  $amount            The amount of the promo code discount. If ‘type’ is ‘fixed’, the amount is
     *                                      treated as a monetary value. If ‘type’ is ‘percentage’, amount must be a
     *                                      decimal value between 0.0 and 1.0, inclusive.
     * @param string $type              Type of discount. For free shipping set type to fixed.
     *                                      Possible Values: 'fixed', 'percentage'
     * @param string $target            The target that the discount applies to. Possible Values: 'per_item',
     *                                      'total', 'shipping'
     * @param null   $startsAt          The date and time when the promotion is in effect in ISO 8601 format.
     * @param null   $endsAt            The date and time when the promotion ends. Must be after starts_at and
     *                                      in ISO 8601 format.
     * @param null   $enabled           Whether the promo rule is currently enabled.
     * @param null   $createdAtForeign  The date and time the promotion was created in ISO 8601 format.
     * @param null   $updatedAtForeign  The date and time the promotion was updated in ISO 8601 format.
     * @param null   $title             The title that will show up in promotion campaign. Restricted to UTF-8 characters with max length 100.
     * @return mixed
     */
    public function modify(
        $storeId,
        $promoRuleId,
        $description,
        $amount,
        $type,
        $target,
        $startsAt = null,
        $endsAt = null,
        $enabled = null,
        $createdAtForeign = null,
        $updatedAtForeign = null,
        $title = null
    ) {
        $_params = array();
        if ($title) {
            $_params['title'] = $title;
        }

        if ($description) {
            $_params['description'] = $description;
        }

        if ($amount) {
            $_params['amount'] = $amount;
        }

        if ($type) {
            $_params['type'] = $type;
        }

        if ($target) {
            $_params['target'] = $target;
        }

        if ($startsAt) {
            $_params['starts_at'] = $startsAt;
        }

        if ($endsAt) {
            $_params['ends_at'] = $endsAt;
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
            'ecommerce/stores/' . $storeId . '/promo-rules/' . $promoRuleId, $_params, Ebizmarts_MailChimp::PATCH
        );
    }

    /**
     * @param int $storeId     The MailChimp store id.
     * @param int $promoRuleId The id for the promo rule of a store.
     * @return mixed
     * @throws MailChimp_Error
     * @throws MailChimp_HttpError
     */
    public function delete($storeId, $promoRuleId)
    {
        $url = 'ecommerce/stores/' . $storeId . '/promo-rules/' . $promoRuleId;

        return $this->_master->call($url, null, Ebizmarts_MailChimp::DELETE);
    }
}
