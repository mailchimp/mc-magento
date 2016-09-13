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
        $listId = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_LIST);

        if ($generalEnabled && $listId) {
            $this->_createWebhook($listId, $apiKey);
        }

        return $observer;
    }

    protected function _createWebhook($listId, $apiKey)
    {
        $webhooksKey = Mage::helper('mailchimp')->getWebhooksKey();

        //Generating Webhooks URL
        $url = Ebizmarts_MailChimp_Model_ProcessWebhook::WEBHOOKS_PATH;
        $hookUrl = Mage::getModel('core/url')->getUrl($url, array('wkey' => $webhooksKey));

        if (FALSE != strstr($hookUrl, '?', true)) {
            $hookUrl = strstr($hookUrl, '?', true);
        }
        $userAgent = 'Mailchimp4Magento'.(string)Mage::getConfig()->getNode('modules/Ebizmarts_MailChimp/version');
        $api = new Ebizmarts_Mailchimp($apiKey, null, $userAgent);
        if (Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_TWO_WAY_SYNC)) {
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
        } else {
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
            if (isset($response['total_items']) && $response['total_items'] > 0) {
                foreach ($response['webhooks'] as $webhook) {
                    if ($webhook['url'] == $hookUrl) {
                        $createWebhook = false;
                    }
                }
            }
            if ($createWebhook) {
                $api->lists->webhooks->add($listId, $hookUrl, $events, $sources);
            }
        } catch(Mailchimp_Error $e) {
            Mage::helper('mailchimp')->logError($e->getFriendlyMessage());
            Mage::getSingleton('adminhtml/session')->addError($e->getFriendlyMessage());
            $textToCompare = 'The resource submitted could not be validated. For field-specific details, see the \'errors\' array.';
            if ($e->getMailchimpDetails() == $textToCompare) {
                $errorMessage = 'Your store could not be accessed by MailChimp\'s Api. Please confirm the site is accessible externally to allow the webhook creation.';
                Mage::getSingleton('adminhtml/session')->addError($errorMessage);
            }
        }
        catch (Exception $e) {
            Mage::helper('mailchimp')->logError($e->getMessage());
        }
    }

    public function handleSubscriber(Varien_Event_Observer $observer)
    {
        $isEnabled = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_ACTIVE);
        if ($isEnabled) {
            $subscriber = $observer->getEvent()->getSubscriber();
            if (!Mage::getSingleton('customer/session')->isLoggedIn()&&!Mage::app()->getStore()->isAdmin()) {
                Mage::getModel('core/cookie')->set(
                    'email', $subscriber->getSubscriberEmail(), null, null, null, null, false
                );
            }


            if (TRUE === $subscriber->getIsStatusChanged()) {
                Mage::getModel('mailchimp/api_subscribers')->updateSubscriber($subscriber);
            }
        }
    }

    public function handleSubscriberDeletion(Varien_Event_Observer $observer)
    {
        $isEnabled = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_ACTIVE);
        if ($isEnabled) {
            $subscriber = $observer->getEvent()->getSubscriber();
            if (TRUE === $subscriber->getIsStatusChanged()) {
                Mage::getModel('mailchimp/api_subscribers')->deleteSubscriber($subscriber);
            }
        }
    }

    public function alterNewsletterGrid(Varien_Event_Observer $observer)
    {

        $block = $observer->getEvent()->getBlock();
        if (!isset($block)) {
            return $this;
        }
        if ($block instanceof Mage_Adminhtml_Block_Newsletter_Subscriber_Grid) {

            $block->addColumnAfter(
                'firstname', array(
                'header' => Mage::helper('newsletter')->__('Customer First Name'),
                'index' => 'customer_firstname',
                'renderer' => 'mailchimp/adminhtml_newsletter_subscriber_renderer_firstname',
                ), 'type'
            );

            $block->addColumnAfter(
                'lastname', array(
                'header' => Mage::helper('newsletter')->__('Customer Last Name'),
                'index' => 'customer_lastname',
                'renderer' => 'mailchimp/adminhtml_newsletter_subscriber_renderer_lastname'
                ), 'firstname'
            );
        }
        return $observer;
    }

    public function customerSaveBefore(Varien_Event_Observer $observer)
    {
        $customer = $observer->getEvent()->getCustomer();

        if ($customer->getMailchimpUpdateObserverRan()) {
            return $observer;
        } else {
            $customer->setMailchimpUpdateObserverRan(true);
        }

        //update mailchimp ecommerce data for that customer
        Mage::getModel('mailchimp/api_customers')->update($customer);
        return $observer;
    }

    public function productSaveBefore(Varien_Event_Observer $observer)
    {
        $product = $observer->getEvent()->getProduct();

        if ($product->getMailchimpUpdateObserverRan()) {
            return $observer;
        } else {
            $product->setMailchimpUpdateObserverRan(true);
        }
        //update mailchimp ecommerce data for that product variant
        Mage::getModel('mailchimp/api_products')->update($product);
        return $observer;
    }

    public function saveCampaignData(Varien_Event_Observer $observer)
    {
        $campaignCookie = $this->_getCampaignCookie();
        if ($campaignCookie) {
            $observer->getEvent()->getOrder()->setMailchimpCampaignId($campaignCookie);
        }
    }

    public function removeCampaignData(Varien_Event_Observer $observer)
    {
        if ($this->_getCampaignCookie()) {
            Mage::getModel('core/cookie')->delete('mailchimp_campaign_id');
        }
        return $observer;
    }

    protected function _getCampaignCookie()
    {
        $cookie = Mage::getModel('core/cookie')->get('mailchimp_campaign_id');
        if ($cookie&&Mage::getModel('core/cookie')->getLifetime('mailchimp_campaign_id')==3600) {
            return $cookie;
        } else {
            return null;
        }
    }

    public function addAbandonedToSalesOrderGrid($observer) 
    {
        $block = $observer->getEvent()->getBlock();
        if ($block instanceof Mage_Adminhtml_Block_Sales_Order_Grid) {
            $block->addColumnAfter(
                'mailchimp_flag', array(
                    'header' => Mage::helper('mailchimp')->__('MailChimp'),
                    'index' => 'mailchimp_flag',
                    'align' => 'center',
                    'filter' => false,
                    'renderer' => 'mailchimp/adminhtml_sales_order_grid_renderer_mailchimp',
                    'sortable' => false,
                    'width' => 170
                ), 'created_at'
            );
        }
        return $observer;
    }

    protected function _createMailChimpStore()
    {
        try {
            /**
             * CREATE MAILCHIMP STORE
             */
            $mailchimpStore = Mage::getModel('mailchimp/api_stores')->getMailChimpStore();
            if (!$mailchimpStore) {
                Mage::helper('mailchimp')->resetMCEcommerceData();
            }
            if (!Mage::helper('mailchimp')->getMCStoreId()) {
                $warningMessage = 'The MailChimp store was not created properly, please save your configuration to create it.';
                Mage::getSingleton('adminhtml/session')->addWarning($warningMessage);
            }

        } catch (Mailchimp_Error $e) {
            Mage::helper('mailchimp')->logError($e->getFriendlyMessage());
            Mage::getSingleton('adminhtml/session')->addError($e->getFriendlyMessage());

        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
    }
    

    public function loadCustomerToQuote(Varien_Event_Observer $observer)
    {
        $quote = $observer->getEvent()->getQuote();
        if (!Mage::getSingleton('customer/session')->isLoggedIn() &&
            Mage::getStoreConfig(Ebizmarts_MailChimp_Model_Config::ENABLE_POPUP, $quote->getStoreId())) {
            $action = Mage::app()->getRequest()->getActionName();
            $onCheckout = ($action == 'saveOrder' || $action == 'savePayment' ||
                $action == 'saveShippingMethod' || $action == 'saveBilling');
            if (Mage::getModel('core/cookie')->get('email') &&
                Mage::getModel('core/cookie')->get('email')!= 'none' && !$onCheckout) {
                $emailCookie = Mage::getModel('core/cookie')->get('email');
                $emailCookieArr = explode('/', $emailCookie);
                $email = $emailCookieArr[0];
                $email = str_replace(' ', '+', $email);
                if ($quote->getCustomerEmail() != $email) {
                    $quote->setCustomerEmail($email);
                }
            }
        }
        $campaignId = $this->_getCampaignCookie();
        if ($campaignId) {
            $quote->setMailChimpCampaignId($campaignId);
        }
        return $observer;
    }
}
