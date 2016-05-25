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

//        if($isEnabled)
//        {
//            try {
//
//                /**
//                 * CREATE MAILCHIMP STORE
//                 */
//                $mailchimpStore = Mage::getModel('mailchimp/api_stores')->getMailChimpStore();
//                if(!$mailchimpStore) {
//                    Mage::helper('mailchimp')->resetMCEcommerceData();
//                }
//
//            } catch (Exception $e)
//            {
//                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
//            }
//        }

        $webhooksKey = Mage::helper('mailchimp')->getWebhooksKey();

        //Generating Webhooks URL
        $hookUrl = Mage::getModel('core/url')->getUrl(Ebizmarts_MailChimp_Model_ProcessWebhook::WEBHOOKS_PATH, array('wkey' => $webhooksKey));

        if (FALSE != strstr($hookUrl, '?', true)) {
            $hookUrl = strstr($hookUrl, '?', true);
        }

//        if ($api->errorCode) {
//            Mage::getSingleton('adminhtml/session')->addError($api->errorMessage);
//            return $observer;
//        }
//
//        $lists = $api->lists();
//
//        $selectedLists = array($listId);
        $this->_saveCustomerGroups($listId, $apiKey, $hookUrl);

        return $observer;
    }

    protected function _saveCustomerGroups($listId, $apiKey, $hookUrl)
    {
        $api = new Ebizmarts_Mailchimp($apiKey);
        $webhookId = Mage::helper('mailchimp')->getMCStoreId();
        $events = array(
            'subscribe' => true,
            'unsubscribe' => true,
            'profile' => true,
            'cleaned' => true,
            'upemail' => true,
            'campaign' => false
        );
        $sources = array(
            'user' => true,
            'admin' => true,
            'api' => true
        );
        try {
            $response = $api->lists->webhooks->getAll($listId);
            if(count($response['webhooks'][0]['url']) == $hookUrl) {
                $api->lists->webhooks->add($listId, $hookUrl, $events, $sources);
                }
            }
        catch (Exception $e){
            Mage::helper('mailchimp')->log($e->getMessage());
        }
//        $api->listWebhookAdd($list['id'], $hookUrl);
//
//        //If webhook was not added, add a message on Admin panel
//        if ($api->errorCode && Mage::helper('mailchimp')->isAdmin()) {
//
//            //Don't show an error if webhook already in, otherwise, show error message and code
//            if ($api->errorMessage !== "Setting up multiple WebHooks for one URL is not allowed.") {
//                $message = Mage::helper('mailchimp')->__('Could not add Webhook "%s" for list "%s", error code %s, %s', $hookUrl, $list['name'], $api->errorCode, $api->errorMessage);
//                Mage::getSingleton('adminhtml/session')->addError($message);
//            }
//
//        }
    }

    public function alterNewsletterGrid(Varien_Event_Observer $observer){

        $block = $observer->getEvent()->getBlock();
        if (!isset($block)) {
            return $this;
        }
        if($block instanceof Mage_Adminhtml_Block_Newsletter_Subscriber_Grid) {

            $block->addColumnAfter('firstname', array(
                'header' => Mage::helper('newsletter')->__('Customer First Name'),
                'index' => 'customer_firstname',
                'renderer' => 'mailchimp/adminhtml_newsletter_subscriber_renderer_firstname',
            ), 'type'
            );

            $block->addColumnAfter('lastname', array(
                'header' => Mage::helper('newsletter')->__('Customer Last Name'),
                'index' => 'customer_lastname',
                'renderer' => 'mailchimp/adminhtml_newsletter_subscriber_renderer_lastname'
            ), 'firstname');
        }
        return $observer;
    }

    public function customerSaveAfter(Varien_Event_Observer $observer)
    {
        $customer = $observer->getEvent()->getCustomer();

        if($customer->getMailchimpUpdateObserverRan())
        {
            return;
        }else{
            $customer->setMailchimpUpdateObserverRan(true);
        }

        //update mailchimp ecommerce data for that customer
        Mage::getModel('mailchimp/api_customers')->Update($customer);
    }

    public function productSaveAfter(Varien_Event_Observer $observer)
    {
        $product = $observer->getEvent()->getProduct();

        if($product->getMailchimpUpdateObserverRan())
        {
            return;
        }else{
            $product->setMailchimpUpdateObserverRan(true);
        }

        //update mailchimp ecommerce data for that product variant
        Mage::getModel('mailchimp/api_products')->Update($product);
    }
}
