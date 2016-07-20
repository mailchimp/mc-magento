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
        $generalEnabled = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_ACTIVE);
        $ecommerceEnabled = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_ACTIVE);
        $listId = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_LIST);
        if($generalEnabled) {
            if($ecommerceEnabled) {
                try {
                    /**
                     * CREATE MAILCHIMP STORE
                     */
                    $mailchimpStore = Mage::getModel('mailchimp/api_stores')->getMailChimpStore();
                    if (!$mailchimpStore) {
                        Mage::helper('mailchimp')->resetMCEcommerceData();
                    }
                    if (!Mage::helper('mailchimp')->getMCStoreId()) {
                        Mage::getSingleton('adminhtml/session')->addWarning('The MailChimp store was not created properly, please save your configuration to create it.');
                    }

                } catch (Mailchimp_Error $e) {
                    Mage::helper('mailchimp')->logError($e->getFriendlyMessage());
                    Mage::getSingleton('adminhtml/session')->addError($e->getFriendlyMessage());

                } catch (Exception $e) {
                    Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                }
            }

            $this->_createWebhook($listId, $apiKey);
        }

        return $observer;
    }

    protected function _createWebhook($listId, $apiKey)
    {
        $webhooksKey = Mage::helper('mailchimp')->getWebhooksKey();

        //Generating Webhooks URL
        $hookUrl = Mage::getModel('core/url')->getUrl(Ebizmarts_MailChimp_Model_ProcessWebhook::WEBHOOKS_PATH, array('wkey' => $webhooksKey));

        if (FALSE != strstr($hookUrl, '?', true)) {
            $hookUrl = strstr($hookUrl, '?', true);
        }
        $api = new Ebizmarts_Mailchimp($apiKey);
        if(Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_TWO_WAY_SYNC)) {
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
        }
        else
        {
            $events = array(
                'subscribe' => true,
                'unsubscribe' => false,
                'profile' => false,
                'cleaned' => false,
                'upemail' => false,
                'campaign' => false
            );
            $sources = array(
                'user' => false,
                'admin' => false,
                'api' => true
            );
        }
        try {
            $response = $api->lists->webhooks->getAll($listId);
            $createWebhook = true;
            if(isset($response['total_items']) && $response['total_items'] > 0)
            {
                foreach($response['webhooks'] as $webhook){
                    if($webhook['url'] == $hookUrl){
                        $createWebhook = false;
                    }
                }
            }
            if($createWebhook) {
                $api->lists->webhooks->add($listId, $hookUrl, $events, $sources);
            }
        }
        catch(Mailchimp_Error $e)
        {
            Mage::helper('mailchimp')->logError($e->getFriendlyMessage());
            Mage::getSingleton('adminhtml/session')->addError($e->getFriendlyMessage());
        }
        catch (Exception $e){
            Mage::helper('mailchimp')->logError($e->getMessage());
        }
    }

    public function handleSubscriber(Varien_Event_Observer $observer)
    {
        $isEnabled = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_ACTIVE);
        if($isEnabled){
            $subscriber = $observer->getEvent()->getSubscriber();
            if (TRUE === $subscriber->getIsStatusChanged()) {
                Mage::getModel('mailchimp/api_subscribers')->addGuestSubscriber($subscriber);
            }
        }
    }

    public function handleSubscriberDeletion(Varien_Event_Observer $observer)
    {
        $isEnabled = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_ACTIVE);
        if($isEnabled){
            $subscriber = $observer->getEvent()->getSubscriber();
            if (TRUE === $subscriber->getIsStatusChanged()) {
                Mage::getModel('mailchimp/api_subscribers')->removeSubscriber($subscriber);
            }
        }
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

    public function customerSaveBefore(Varien_Event_Observer $observer)
    {
        $customer = $observer->getEvent()->getCustomer();

        if($customer->getMailchimpUpdateObserverRan())
        {
            return $observer;
        }else{
            $customer->setMailchimpUpdateObserverRan(true);
        }

        //update mailchimp ecommerce data for that customer
        Mage::getModel('mailchimp/api_customers')->update($customer);
        return $observer;
    }

    public function productSaveBefore(Varien_Event_Observer $observer)
    {
        $product = $observer->getEvent()->getProduct();

        if($product->getMailchimpUpdateObserverRan())
        {
            return $observer;
        }else{
            $product->setMailchimpUpdateObserverRan(true);
        }

        //update mailchimp ecommerce data for that product variant
        Mage::getModel('mailchimp/api_products')->update($product);
        return $observer;
    }

    public function saveCampaignData(Varien_Event_Observer $observer)
    {
        $campaignCookie = $this->_getCampaignCookie();
        if($campaignCookie) {
            $observer->getEvent()->getOrder()->setMailchimpCampaignId($campaignCookie);
        }
    }

    public function removeCampaignData(Varien_Event_Observer $observer)
    {
        if($this->_getCampaignCookie())
        {
            Mage::getModel('core/cookie')->delete('mailchimp_campaign_id');
        }
        return $observer;
    }

    protected function _getCampaignCookie()
    {
        return Mage::getModel('core/cookie')->get('mailchimp_campaign_id');
    }

    public function addAbandonedToSalesOrderGrid($observer) {
        $block = $observer->getEvent()->getBlock();
        if($block instanceof Mage_Adminhtml_Block_Sales_Order_Grid) {
            $block->addColumnAfter('mailchimp_abandonedcart_flag', array(
                    'header' => Mage::helper('mailchimp')->__('Cart Recovered'),
                    'index' => 'mailchimp_abandonedcart_flag',
                    'align' => 'center',
                    'filter' => false,
                    'renderer' => 'mailchimp/adminhtml_sales_order_grid_renderer_abandoned',
                    'sortable' => false,
                    'width' => 170
                )
                , 'created_at');
        }
        return $observer;
    }
}
