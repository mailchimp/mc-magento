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
        $post = Mage::app()->getRequest()->getPost();
        $scopeArray = explode('-', Mage::helper('mailchimp')->getScopeString());
        $generalEnabled = Mage::helper('mailchimp')->isMailChimpEnabled($scopeArray[1], $scopeArray[0]);
        $listId = Mage::helper('mailchimp')->getGeneralList($scopeArray[1], $scopeArray[0]);

        if ($generalEnabled && $listId) {
            $this->_createWebhook($listId, $scopeArray[1], $scopeArray[0]);
        }

        if (isset($post['groups']['general']['fields']['list']['inherit']) && Mage::helper('mailchimp')->getIfMCStoreIdExistsForScope($scopeArray[1], $scopeArray[0])) {
            Mage::helper('mailchimp')->removeEcommerceSyncData($scopeArray[1], $scopeArray[0]);
            Mage::helper('mailchimp')->resetCampaign($scopeArray[1], $scopeArray[0]);
            Mage::helper('mailchimp')->clearErrorGrid($scopeArray[1], $scopeArray[0], true);
            Mage::helper('mailchimp')->deleteStore($scopeArray[1], $scopeArray[0]);
        }

        if (isset($post['groups']['general']['fields']['list']['value']) && !Mage::helper('mailchimp')->getIfMCStoreIdExistsForScope($scopeArray[1], $scopeArray[0]) && Mage::helper('mailchimp')->isEcomSyncDataEnabled($scopeArray[1], $scopeArray[0], true)) {
            Mage::helper('mailchimp')->createStore($post['groups']['general']['fields']['list']['value'], $scopeArray[1], $scopeArray[0]);
        }

        return $observer;
    }

    /**
     * Create MailChimp webhook based on the Two Way Sync field. If disabled the webhook is created only for subsciption confirmation when opt-in enabled.
     * 
     * @param $listId
     * @param $scopeId
     * @param $scope
     */
    protected function _createWebhook($listId, $scopeId, $scope)
    {
        $store = Mage::app()->getDefaultStoreView();
        $webhooksKey = Mage::helper('mailchimp')->getWebhooksKey();
        //Generating Webhooks URL
        $url = Ebizmarts_MailChimp_Model_ProcessWebhook::WEBHOOKS_PATH;
        $hookUrl = $store->getUrl(
            $url, array(
            'wkey' => $webhooksKey,
            '_nosid' => true,
            '_secure' => true,
            )
        );

        if (FALSE != strstr($hookUrl, '?', true)) {
            $hookUrl = strstr($hookUrl, '?', true);
        }

        $api = Mage::helper('mailchimp')->getApi($scopeId, $scope);
        if (Mage::helper('mailchimp')->getTwoWaySyncEnabled($scopeId, $scope)) {
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
        } catch (Mailchimp_Error $e) {
            Mage::helper('mailchimp')->logError($e->getFriendlyMessage(), $scopeId, $scope);
            Mage::getSingleton('adminhtml/session')->addError($e->getFriendlyMessage());
            $textToCompare = 'The resource submitted could not be validated. For field-specific details, see the \'errors\' array.';
            if ($e->getMailchimpDetails() == $textToCompare) {
                $errorMessage = 'Your store could not be accessed by MailChimp\'s Api. Please confirm the URL: '. $hookUrl .' is accessible externally to allow the webhook creation.';
                Mage::getSingleton('adminhtml/session')->addError($errorMessage);
            }
        } catch (Exception $e) {
            Mage::helper('mailchimp')->logError($e->getMessage(), $scopeId, $scope);
        }
    }

    /**
     * Handle subscription change (subscribe/unsubscribe)
     * 
     * @param Varien_Event_Observer $observer
     */
    public function handleSubscriber(Varien_Event_Observer $observer)
    {
        $subscriber = $observer->getEvent()->getSubscriber();
        $isEnabled = Mage::helper('mailchimp')->isMailChimpEnabled($subscriber->getStoreId());
        if ($isEnabled) {
            $subscriber->setImportMode(true);
            if (!Mage::getSingleton('customer/session')->isLoggedIn() && !Mage::app()->getStore()->isAdmin()) {
                Mage::getModel('core/cookie')->set(
                    'email', $subscriber->getSubscriberEmail(), null, null, null, null, false
                );
            }

            if (TRUE === $subscriber->getIsStatusChanged()) {
                Mage::getModel('mailchimp/api_subscribers')->updateSubscriber($subscriber, true);
            } else {
                $origData = $subscriber->getOrigData();

                if (is_array($origData) && isset($origData['subscriber_status']) &&
                    $origData['subscriber_status'] != $subscriber->getSubscriberStatus()
                ) {
                    Mage::getModel('mailchimp/api_subscribers')->updateSubscriber($subscriber, true);
                }
            }
        }
    }

    /**
     * Handle subscriber deletion from back end.
     * 
     * @param Varien_Event_Observer $observer
     */
    public function handleSubscriberDeletion(Varien_Event_Observer $observer)
    {
        $isEnabled = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_ACTIVE);
        if ($isEnabled) {
            $subscriber = $observer->getEvent()->getSubscriber();

            Mage::getModel('mailchimp/api_subscribers')->deleteSubscriber($subscriber);
        }
    }

    /**
     * Add Subscriber first name and last name to Newsletter Grid.
     * 
     * @param Varien_Event_Observer $observer
     * @return $this|Varien_Event_Observer
     */
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

    /**
     * When Customer object is saved set it to be updated on MailChimp if getMailchimpUpdateObserverRan() is false.
     * 
     * @param Varien_Event_Observer $observer
     * @return Varien_Event_Observer
     */
    public function customerSaveBefore(Varien_Event_Observer $observer)
    {
        $customer = $observer->getEvent()->getCustomer();
        $storeId = Mage::app()->getStore()->getStoreId();

        //update mailchimp ecommerce data for that customer
        Mage::getModel('mailchimp/api_customers')->update($customer->getId(), $storeId);
        
        //update subscriber data if a subscriber with the same email address exists
        Mage::getModel('mailchimp/api_subscribers')->update($customer->getEmail(), $storeId);
        return $observer;
    }

    /**
     * When Product object is saved set it to be updated on MailChimp if getMailchimpUpdateObserverRan() is false.
     * 
     * @param Varien_Event_Observer $observer
     * @return Varien_Event_Observer
     */
    public function productSaveBefore(Varien_Event_Observer $observer)
    {
        $product = $observer->getEvent()->getProduct();
        $storeId = Mage::app()->getStore()->getStoreId();
        //update mailchimp ecommerce data for that product variant
        Mage::getModel('mailchimp/api_products')->update($product->getId(), $storeId);
        return $observer;
    }

    /**
     * When Order object is saved add the campaign id if available in the cookies.
     * 
     * @param Varien_Event_Observer $observer
     */
    public function saveCampaignData(Varien_Event_Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $campaignCookie = $this->_getCampaignCookie();
        if ($campaignCookie) {
            $order->setMailchimpCampaignId($campaignCookie);
        }
    }

    /**
     * Catch order save before event, mark it as modified and associate the landing page to the order data.
     * 
     * @param Varien_Event_Observer $observer
     * @return Varien_Event_Observer
     */
    public function orderSaveBefore(Varien_Event_Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $storeId = Mage::app()->getStore()->getStoreId();

        //update mailchimp ecommerce data for that product variant
        Mage::getModel('mailchimp/api_orders')->update($order->getId(), $storeId);
        $landingCookie = $this->_getLandingCookie();
        if ($landingCookie && !$order->getMailchimpLandingPage()) {
            $order->setMailchimpLandingPage($landingCookie);
        }
    }

    /**
     * Delete campaign cookie after it was added to the order object.
     *
     * @param Varien_Event_Observer $observer
     * @return Varien_Event_Observer
     * @throws Exception
     */
    public function removeCampaignData(Varien_Event_Observer $observer)
    {
        if ($this->_getCampaignCookie()) {
            Mage::getModel('core/cookie')->delete('mailchimp_campaign_id');
        }

        if ($this->_getLandingCookie()) {
            Mage::getModel('core/cookie')->delete('mailchimp_landing_page');
        }

        return $observer;
    }

    /**
     * Get campaign cookie if available.
     *
     * @return mixed
     */
    protected function _getCampaignCookie()
    {
        $landingCookie = $this->_getLandingCookie();
        if (preg_match("/utm_source=mailchimp/", $landingCookie)) {
            return false;
        }

        return Mage::getModel('core/cookie')->get('mailchimp_campaign_id');
    }

    /**
     * Get landing_page cookie if exists.
     * 
     * @return null
     */
    protected function _getLandingCookie()
    {
        return Mage::getModel('core/cookie')->get('mailchimp_landing_page');
    }

    /**
     * Add column to associate orders gained from MailChimp campaigns and automations.
     *
     * @param $observer
     * @return mixed
     */
    public function addColumnToSalesOrderGrid($observer)
    {
        $scopeArray = explode('-', Mage::helper('mailchimp')->getScopeString());
        $block = $observer->getEvent()->getBlock();
        if ($block instanceof Mage_Adminhtml_Block_Sales_Order_Grid
            && Mage::helper('mailchimp')->getMonkeyInGrid($scopeArray[1], $scopeArray[0])
            && (
                Mage::helper('mailchimp')->isAbandonedCartEnabled($scopeArray[1], $scopeArray[0])
                || Mage::helper('mailchimp')->isMailChimpEnabled($scopeArray[1], $scopeArray[0])
            )) {
            $block->addColumnAfter(
                'mailchimp_flag', array(
                'header' => Mage::helper('mailchimp')->__('MailChimp'),
                'index' => 'mailchimp_flag',
                'align' => 'center',
                'filter' => false,
                'renderer' => 'mailchimp/adminhtml_sales_order_grid_renderer_mailchimp',
                'sortable' => false,
                'width' => 70
                ), 'created_at'
            );
        }

        return $observer;
    }

    /**
     * Add customer to the cart if it placed the email address in the popup or footer subscription form.
     *
     * @param Varien_Event_Observer $observer
     * @return Varien_Event_Observer
     */
    public function loadCustomerToQuote(Varien_Event_Observer $observer)
    {
        $quote = $observer->getEvent()->getQuote();
        if (!Mage::getSingleton('customer/session')->isLoggedIn() &&
            Mage::helper('mailchimp')->isEmailCatcherEnabled($quote->getStoreId())
        ) {
            $action = Mage::app()->getRequest()->getActionName();
            $onCheckout = ($action == 'saveOrder' || $action == 'savePayment' ||
                $action == 'saveShippingMethod' || $action == 'saveBilling');
            if (Mage::getModel('core/cookie')->get('email') &&
                Mage::getModel('core/cookie')->get('email') != 'none' && !$onCheckout
            ) {
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
            $quote->setMailchimpCampaignId($campaignId);
        }

        $landingCookie = $this->_getLandingCookie();
        if ($landingCookie) {
            $quote->setMailchimpLandingPage($landingCookie);
        }

        return $observer;
    }

    /**
     * Set the products included the order to be updated on MailChimp on the next cron job run.
     *
     * @param Varien_Event_Observer $observer
     * @return Varien_Event_Observer
     * @throws Exception
     */
    public function newOrder(Varien_Event_Observer $observer)
    {
        if(($this->_getLandingCookie())) {
            Mage::getModel('core/cookie')->delete('mailchimp_landing_page');
        }

        if ($this->_getCampaignCookie()) {
            Mage::getModel('core/cookie')->delete('mailchimp_campaign_id');
        }

        $order = $observer->getEvent()->getOrder();
        $items = $order->getAllItems();
        foreach ($items as $item)
        {
            if ($item->getProductType()=='bundle' || $item->getProductType()=='configurable') {
                continue;
            }

            $mailchimpStoreId = Mage::helper('mailchimp')->getMCStoreId($order->getStoreId());
            Mage::helper('mailchimp')->saveEcommerceSyncData($item->getProductId(), Ebizmarts_MailChimp_Model_Config::IS_PRODUCT, $mailchimpStoreId, null, null, 1, null, null, true);
        }

        return $observer;
    }

    /**
     * Set the products included in the credit memo to be updated on MailChimp on the next cron job run.
     *
     * @param Varien_Event_Observer $observer
     * @return Varien_Event_Observer
     * @throws Exception
     */
    public function newCreditMemo(Varien_Event_Observer $observer)
    {
        $creditMemo = $observer->getEvent()->getCreditmemo();
        $order = $creditMemo->getOrder();
        $mailchimpStoreId = Mage::helper('mailchimp')->getMCStoreId($order->getStoreId());
        $items = $creditMemo->getAllItems();
        foreach ($items as $item)
        {
            if ($item->getProductType()=='bundle' || $item->getProductType()=='configurable') {
                continue;
            }

            Mage::helper('mailchimp')->saveEcommerceSyncData($item->getProductId(), Ebizmarts_MailChimp_Model_Config::IS_PRODUCT, $mailchimpStoreId, null, null, 1, null, null, true);
        }

        Mage::helper('mailchimp')->saveEcommerceSyncData($order->getEntityId(), Ebizmarts_MailChimp_Model_Config::IS_ORDER, $mailchimpStoreId, null, null, 1, null, null, true);
        return $observer;
    }

    /**
     * Set the products included in the credit memo to be updated on MailChimp on the next cron job run.
     *
     * @param Varien_Event_Observer $observer
     * @return Varien_Event_Observer
     * @throws Exception
     */
    public function cancelCreditMemo(Varien_Event_Observer $observer)
    {
        $creditMemo = $observer->getEvent()->getCreditmemo();
        $order = $creditMemo->getOrder();
        $mailchimpStoreId = Mage::helper('mailchimp')->getMCStoreId($order->getStoreId());
        $items = $creditMemo->getAllItems();
        foreach ($items as $item)
        {
            if ($item->getProductType()=='bundle' || $item->getProductType()=='configurable') {
                continue;
            }

            Mage::helper('mailchimp')->saveEcommerceSyncData($item->getProductId(), Ebizmarts_MailChimp_Model_Config::IS_PRODUCT, $mailchimpStoreId, null, null, 1, null, null, true);
        }

        Mage::helper('mailchimp')->saveEcommerceSyncData($order->getEntityId(), Ebizmarts_MailChimp_Model_Config::IS_ORDER, $mailchimpStoreId, null, null, 1, null, null, true);
        return $observer;
    }

    /**
     * Set the products canceled to be updated on MailChimp on the next cron job run.
     *
     * @param Varien_Event_Observer $observer
     * @return Varien_Event_Observer
     * @throws Exception
     */
    public function itemCancel(Varien_Event_Observer$observer)
    {
        $item = $observer->getEvent()->getItem();
        $mailchimpStoreId = Mage::helper('mailchimp')->getMCStoreId($item->getStoreId());
        if ($item->getProductType()!='bundle' && $item->getProductType()!='configurable') {
            Mage::helper('mailchimp')->saveEcommerceSyncData($item->getProductId(), Ebizmarts_MailChimp_Model_Config::IS_PRODUCT, $mailchimpStoreId, null, null, 1, null, null, true);
        }

        return $observer;
    }

    /**
     * Add column to order grid to identify orders gained by MailChimp campaigns and automations.
     *
     * @param Varien_Event_Observer $observer
     */
    public function addOrderViewMonkey(Varien_Event_Observer $observer)
    {
        $block = $observer->getBlock();
        if(($block->getNameInLayout() == 'order_info') && ($child = $block->getChild('mailchimp.order.info.monkey.block'))){
            $transport = $observer->getTransport();
            if($transport){
                $html = $transport->getHtml();
                $html .= $child->toHtml();
                $transport->setHtml($html);
            }
        }

    }

    /**
     * Catch Magento store name change event and call changeName function to check if MailChimp store name must be changed as well.
     *
     * @param Varien_Event_Observer $observer
     */
    public function changeStoreName(Varien_Event_Observer $observer)
    {
        $scopeArray = explode('-', Mage::helper('mailchimp')->getScopeString());
        $group = $observer->getGroup();
        $storeName = Mage::getStoreConfig('general/store_information/name');
        if ($storeName == '') {
            Mage::helper('mailchimp')->changeName($group->getName(), $scopeArray[1], $scopeArray[0]);
        }
    }

    public function productAttributeUpdate(Varien_Event_Observer $observer)
    {
        Mage::log(__METHOD__, null, 'observer.log', true);
        $productIds = $observer->getEvent()->getProductIds();
        $storeId = $observer->getEvent()->getStoreId();
        $scope = 'stores';
        if ($storeId === 0) {
            $scope = 'default';
        }

        $mailchimpStoreId = Mage::helper('mailchimp')->getMCStoreId($storeId, $scope);
        Mage::log($storeId, null, 'observer.log', true);
        foreach ($productIds as $productId) {
            Mage::getModel('mailchimp/api_products')->update($productId, $mailchimpStoreId, null, null, 1, null, true);
        }
    }
    public function productWebsiteUpdate(Varien_Event_Observer $observer)
    {
        $productIds = $observer->getEvent()->getProductIds();
        $websiteIds = $observer->getEvent()->getWebsiteIds();
        $action = $observer->getEvent()->getAction();
//        foreach ($websiteIds as $websiteId) {
//            $stores = Mage::app()->getWebsite($websiteId)->getStores();
//            foreach ($stores as $storeId => $store) {
//                $mailchimpStoreId = Mage::helper('mailchimp')->getMCStoreId($storeId);
//                foreach ($productIds as $productId) {
//                    $productSyncData = Mage::helper('mailchimp')->getEcommerceSyncDataItem($productId, Ebizmarts_MailChimp_Model_Config::IS_PRODUCT, $mailchimpStoreId);
//                    if ($productSyncData->getMailchimpSyncDelta() && $productSyncData->getMailchimpSyncDelta() > Mage::helper('mailchimp')->getMCMinSyncDateFlag($storeId)) {
//                        if ($action == 'remove' && !$productSyncData->getMailchimpSyncDeleted()) {
//                            $productSyncData->setData("mailchimp_sync_modified", 0)
//                                ->setData("mailchimp_sync_deleted", 1)
//                                ->save();
//                        } elseif (!$productSyncData->getMailchimpSyncModified()) {
//                            $productSyncData->setData("mailchimp_sync_modified", 1)
//                                ->setData("mailchimp_sync_deleted", 0)
//                                ->save();
//                        }
//                    }
//                }
//            }
//        }
    }
}
