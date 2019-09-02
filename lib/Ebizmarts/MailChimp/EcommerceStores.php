<?php

/**
 * mailchimp-lib Magento Component
 *
 * @category  Ebizmarts
 * @package   mailchimp-lib
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     4/29/16 4:08 PM
 * @file:     EcommerceStores.php
 */
class MailChimp_EcommerceStore extends MailChimp_Abstract
{
    /**
     * @param $id              The unique identifier for the store
     * @param $listId          The unique identifier for the MailChimp List associated with the store. The list_id for
     *                              a specific store cannot change.
     * @param $name            The name of the store.
     * @param $platform        The e-commerce platform of the store.
     * @param $domain          The store domain.
     * @param $emailAddress    The email address for the store.
     * @param $currencyCode    The three-letter ISO 4217 code for the currency that the store accepts.
     * @param $isSyncing       The boolean value that enables Automation usage when false.
     * @param $moneyFormat     The currency format for the store. For example: $, £, etc.
     * @param $primaryLocale   The primary locale for the store. For example: en, de, etc.
     * @param $timezone        The timezone for the store.
     * @param $phone           The store phone number.
     * @param $address         The store address.
     * @return mixed
     * @throws MailChimp_Error
     */
    public function add(
        $id,
        $listId,
        $name,
        $currencyCode,
        $isSyncing,
        $platform = null,
        $domain = null,
        $emailAddress = null,
        $moneyFormat = null,
        $primaryLocale = null,
        $timezone = null,
        $phone = null,
        $address = null
    ) {
        $_params = array('id' => $id, 'list_id' => $listId, 'name' => $name, 'currency_code' => $currencyCode);
        if ($platform) {
            $_params['platform'] = $platform;
        }

        if ($domain) {
            $_params['domain'] = $domain;
        }

        if ($emailAddress) {
            $_params['email_address'] = $emailAddress;
        }

        if ($isSyncing) {
            $_params['is_syncing'] = $isSyncing;
        }

        if ($moneyFormat) {
            $_params['money_format'] = $moneyFormat;
        }

        if ($primaryLocale) {
            $_params['primary_locale'] = $primaryLocale;
        }

        if ($timezone) {
            $_params['timezone'] = $timezone;
        }

        if ($phone) {
            $_params['phone'] = $phone;
        }

        if ($address) {
            $_params['address'] = $address;
        }

        return $this->_master->call('ecommerce/stores', $_params, Ebizmarts_MailChimp::POST);
    }

    /**
     * @param $id               The store id.
     * @param $fields           A comma-separated list of fields to return. Reference parameters of sub-objects with
     *                          dot notation.
     * @param $excludeFields    A comma-separated list of fields to exclude. Reference parameters of sub-objects with
     *                          dot notation.
     * @param $count            The number of records to return.
     * @param $offset           The number of records from a collection to skip. Iterating over large collections with
     *                          this parameter can be slow.
     * @return mixed
     * @throws MailChimp_Error
     */
    public function get($id = null, $fields = null, $excludeFields = null, $count = null, $offset = null)
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

        if ($id) {
            return $this->_master->call('ecommerce/stores/' . $id, $_params, Ebizmarts_MailChimp::GET);
        } else {
            return $this->_master->call('ecommerce/stores', $_params, Ebizmarts_MailChimp::GET);
        }
    }

    /**
     * @param $storeId         The store id.
     * @param $name            The store name.
     * @param $platform        The e-commerce platform of the store.
     * @param $domain          The store domain.
     * @param $emailAddress    The email address for the store.
     * @param $currencyCode    The three-letter ISO 4217 code for the currency that the store accepts.
     * @param $isSyncing       The boolean value that enables Automation usage when false.
     * @param $moneyFormat     The currency format for the store. For example: $, £, etc.
     * @param $primaryLocale   The primary locale for the store. For example: en, de, etc.
     * @param $timezone        The timezone for the store.
     * @param $phone           The store phone number.
     * @param $address         The store address.
     * @return mixed
     * @throws MailChimp_Error
     */
    public function edit(
        $storeId,
        $name = null,
        $platform = null,
        $domain = null,
        $isSyncing = null,
        $emailAddress = null,
        $currencyCode = null,
        $moneyFormat = null,
        $primaryLocale = null,
        $timezone = null,
        $phone = null,
        $address = null
    ) {

        $_params = array();
        if ($name) {
            $_params['name'] = $name;
        }

        if ($platform) {
            $_params['platform'] = $platform;
        }

        if ($domain) {
            $_params['domain'] = $domain;
        }

        if ($emailAddress) {
            $_params['email_address'] = $emailAddress;
        }

        if ($currencyCode) {
            $_params['currency_code'] = $currencyCode;
        }

        if ($isSyncing !== null) {
            $_params['is_syncing'] = $isSyncing;
        }

        if ($moneyFormat) {
            $_params['money_format'] = $moneyFormat;
        }

        if ($primaryLocale) {
            $_params['primary_locale'] = $primaryLocale;
        }

        if ($timezone) {
            $_params['timezone'] = $timezone;
        }

        if ($phone) {
            $_params['phone'] = $phone;
        }

        if ($address) {
            $_params['address'] = $address;
        }

        return $this->_master->call('ecommerce/stores/' . $storeId, $_params, Ebizmarts_MailChimp::PATCH);
    }

    /**
     * @param $storeId      The store id.
     * @return mixed
     * @throws MailChimp_Error
     */
    public function delete($storeId)
    {
        return $this->_master->call('ecommerce/stores/' . $storeId, null, Ebizmarts_MailChimp::DELETE);
    }
}
