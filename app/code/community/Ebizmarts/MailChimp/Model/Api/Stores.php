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
class Ebizmarts_MailChimp_Model_Api_Stores
{

    public function getMailChimpStore()
    {
        $apiKey = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_APIKEY);
        $api = new Ebizmarts_Mailchimp($apiKey);
        $storeExists = false;
        $storeId = Mage::helper('mailchimp')->getStoreId();
        try {
            $storeExists = $api->ecommerce->stores->get($storeId);

            $storeExists = json_decode($storeExists);

        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, 'MailChimp_Errors.log', true);
        }
        return $storeExists;
    }

    public function createMailChimpStore()
    {
        $apiKey = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_APIKEY);
        $listId = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_LIST);
        $storeId = Mage::helper('mailchimp')->getStoreId();
        $storeName = Mage::helper('mailchimp')->getStoreName();
        $store_email = Mage::helper('mailchimp')->getConfigValue('trans_email/ident_general/email');
        $store_email = 'santiago@ebizmarts.com';
        $currencyCode = Mage::helper('mailchimp')->getConfigValue(Mage_Directory_Model_Currency::XML_PATH_CURRENCY_DEFAULT);

        try {
            $api = new Ebizmarts_Mailchimp($apiKey);
            $response = $api->ecommerce->stores->add($storeId, $listId, $storeName, 'Magento', null, $store_email, $currencyCode);
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, 'MailChimp_Errors.log', true);
        }
        return $response;
    }
}