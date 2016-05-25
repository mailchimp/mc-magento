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

    /**
     * @return bool|mixed
     * @throws Mailchimp_Error
     */
    public function getMailChimpStore()
    {
        $apiKey = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_APIKEY);

        if (!is_null($apiKey) && $apiKey != "") {
            $api = new Ebizmarts_Mailchimp($apiKey);
            $storeExists = false;
            $storeId = Mage::helper('mailchimp')->getMCStoreId();

            if (is_null($storeId) || $storeId == "") {
                throw new Mailchimp_Error ('Invalid MailChimp Store Id');
            }

            $storeExists = $api->ecommerce->stores->get($storeId);
            $storeExists = json_decode($storeExists);

            return $storeExists;

        } else {
            throw new Mailchimp_Error ('You must provide a MailChimp API key');
        }
    }

    /**
     * @throws Mailchimp_Error
     */
    public function createMailChimpStore()
    {
        $apiKey = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_APIKEY);

        if (!is_null($apiKey) && $apiKey != "") {
            $listId = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_LIST);

            if (!is_null($listId) && $listId != "") {
                $storeId = Mage::helper('mailchimp')->getMCStoreId();
                $storeName = Mage::helper('mailchimp')->getMCStoreName();
                $store_email = Mage::helper('mailchimp')->getConfigValue('trans_email/ident_general/email');
                $currencyCode = Mage::helper('mailchimp')->getConfigValue(Mage_Directory_Model_Currency::XML_PATH_CURRENCY_DEFAULT);

                $api = new Ebizmarts_Mailchimp($apiKey);
                $api->ecommerce->stores->add($storeId, $listId, $storeName, 'Magento', null, $store_email, $currencyCode);

            } else {
                throw new Mailchimp_Error ('You don\'t have any lists configured in MailChimp');
            }
        } else {
            throw new Mailchimp_Error ('You must provide a MailChimp API key');
        }
    }

    /**
     * @param $storeId
     */
    public function deleteStore($storeId)
    {
        if (Mage::helper('mailchimp')->isEcommerceSyncDataEnabled()) {
            $apiKey = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_APIKEY);
            $api = new Ebizmarts_Mailchimp($apiKey);
            $api->ecommerce->stores->delete($storeId);
        }
    }
}