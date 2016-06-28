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
     * @throws Exception
     */
    public function getMailChimpStore()
    {
        $apiKey = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_APIKEY);

        if (!is_null($apiKey) && $apiKey != "") {
            $api = new Ebizmarts_Mailchimp($apiKey);
            $storeExists = null;
            $storeId = Mage::helper('mailchimp')->getMCStoreId();

            if (is_null($storeId) || $storeId == "") {
                return null;
            }

            try {
                $store = $api->ecommerce->stores->get($storeId);
                if(is_array($store) && isset($store['id'])){
                    $storeExists = $store;
                }
            }
            catch(Mailchimp_Error $e){
                Mage::helper('mailchimp')->logError($e->getFriendlyMessage());
            }
            catch (Exception $e){
                Mage::helper('mailchimp')->logError($e->getMessage());
            }

            return $storeExists;

        } else {
            throw new Exception ('You must provide a MailChimp API key');
        }
    }

    /**
     * @throws Exception
     */
    public function createMailChimpStore($store_id)
    {
        $apiKey = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_APIKEY);
        if (!is_null($apiKey) && $apiKey != "") {
            $listId = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_LIST);

            if (!is_null($listId) && $listId != "")
            {
                $storeName = Mage::helper('mailchimp')->getMCStoreName();
                $store_email = Mage::helper('mailchimp')->getConfigValue('trans_email/ident_general/email');
                if (strpos($store_email, 'example.com') !== false) {
                    $store_email = null;
                    throw new Exception ('Please, change the general email in Store Email Addresses/General Contact');
                }

                $currencyCode = Mage::helper('mailchimp')->getConfigValue(Mage_Directory_Model_Currency::XML_PATH_CURRENCY_DEFAULT);

                $api = new Ebizmarts_Mailchimp($apiKey);
                $api->ecommerce->stores->add($store_id, $listId, $storeName, 'Magento', null, $store_email, $currencyCode);

            } else {
                throw new Exception ('You don\'t have any lists configured in MailChimp');
            }
        } else {
            throw new Exception ('You must provide a MailChimp API key');
        }
    }

    /**
     * @param $storeId
     */
    public function deleteStore($storeId)
    {
        if (Mage::helper('mailchimp')->isEcomSyncDataEnabled()) {
            $apiKey = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_APIKEY);
            $api = new Ebizmarts_Mailchimp($apiKey);
            $api->ecommerce->stores->delete($storeId);
            $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
            $resource = Mage::getResourceModel('mailchimp/synchbatches');
            $connection->update($resource->getMainTable(),array('status'=>'canceled'),"status = 'pending'");
        }
    }
}