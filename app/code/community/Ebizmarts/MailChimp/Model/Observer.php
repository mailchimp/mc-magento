<?php
/**
 * MailChimp For Magento
 *
 * @category Ebizmarts_MailChimp
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 4/29/16 3:55 PM
 * @file: Observer.php
 */
class Ebizmarts_MailChimp_Model_Observer
{

    /**
     * Handle save of System -> Configuration, section <mailchimp>
     *
     * @param Varien_Event_Observer $observer
     * @return void|Varien_Event_Observer
     */
    public function saveConfig(Varien_Event_Observer $observer)
    {
        $apiKey = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_APIKEY);
        $isEnabled = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_ACTIVE);
        $listId = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_LIST);
        $storeId = Mage::helper('mailchimp')->getStoreId();
        $storeName = Mage::helper('mailchimp')->getStoreName();
        $store_email = Mage::helper('mailchimp')->getConfigValue('trans_email/ident_general/email');
        $store_email = 'santiago@ebizmarts.com';
        $currencyCode = Mage::helper('mailchimp')->getConfigValue(Mage_Directory_Model_Currency::XML_PATH_CURRENCY_DEFAULT);

        if($isEnabled) {
            //Check if the api key exist
            if (!$apiKey) {
                $message = Mage::helper('mailchimp')->__('There is no API Key provided. Please add an API Key to get this working.');
                Mage::getSingleton('adminhtml/session')->addError($message);
            }else{
                $api = new Ebizmarts_Mailchimp($apiKey);
                $storeExists = false;
                try {
                    $storeExists = $api->ecommerce->stores->get($storeId);

                }catch (Exception $e){
                    Mage::log($e->getMessage(), null, 'MailChimp_Errors.log', true);
                }
                try{
                    if(!$storeExists) {
                        $response = $api->ecommerce->stores->add($storeId, $listId, $storeName, 'Magento', null, $store_email, $currencyCode);
                    }
                }catch(Exception $e){
                    Mage::log($e->getMessage(), null, 'MailChimp_Errors.log', true);
                }
            }
        }

        return $observer;

    }

}
