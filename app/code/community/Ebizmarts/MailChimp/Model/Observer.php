<?php

/**
 * MailChimp For Magento
 *
 * @category  Ebizmarts_MailChimp
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     4/29/16 3:55 PM
 * @file:     Observer.php
 */
class Ebizmarts_MailChimp_Model_Observer
{


    /**
     * @return Mage_Core_Model_Resource
     */
    protected function getCoreResource()
    {
        return Mage::getSingleton('core/resource');
    }

    /**
     * @return Mage_Core_Model_Config
     */
    protected function getConfig()
    {
        return Mage::getConfig();
    }

    /**
     * @return Ebizmarts_MailChimp_Model_Api_Orders
     */
    protected function makeApiOrders()
    {
        return Mage::getModel('mailchimp/api_orders');
    }

    /**
     * @return Ebizmarts_MailChimp_Model_Api_PromoCodes
     */
    protected function getPromoCodesApi()
    {
        return Mage::getModel('mailchimp/api_promoCodes');
    }

    /**
     * @return Ebizmarts_MailChimp_Model_Api_PromoRules
     */
    protected function getPromoRulesApi()
    {
        return Mage::getModel('mailchimp/api_promoRules');
    }

    /**
     * @return Ebizmarts_MailChimp_Helper_Data
     */
    protected function makeHelper()
    {
        return Mage::helper('mailchimp');
    }

    /**
     * @return Ebizmarts_MailChimp_Model_Api_Products
     */
    protected function makeApiProducts()
    {
        return Mage::getModel('mailchimp/api_products');
    }

    /**
     * @return Ebizmarts_MailChimp_Model_Api_Subscribers
     */
    protected function getApiSubscriber()
    {
        return Mage::getModel('mailchimp/api_subscribers');
    }

    /**
     * @return Mage_Newsletter_Model_Subscriber
     */
    protected function getSubscriberModel()
    {
        return Mage::getModel('newsletter/subscriber');
    }

    /**
     * @return Ebizmarts_MailChimp_Model_Api_Customers
     */
    protected function getApiCustomer()
    {
        return Mage::getModel('mailchimp/api_customers');
    }

    /**
     * @return Mage_Customer_Model_Customer
     */
    protected function getCustomerModel()
    {
        return Mage::getModel('customer/customer');
    }

    /**
     * Handle save of System -> Configuration, section <mailchimp>
     *
     * @param  Varien_Event_Observer $observer
     * @return Varien_Event_Observer
     */
    public function saveConfig(Varien_Event_Observer $observer)
    {
        $post = Mage::app()->getRequest()->getPost();
        $helper = $this->makeHelper();
        $scopeArray = $helper->getCurrentScope();

        if (isset($post['groups']['general']['fields']['list']['inherit']) && $this->makeHelper()->getIfConfigExistsForScope(Ebizmarts_MailChimp_Model_Config::GENERAL_MCSTOREID, $scopeArray['scope_id'], $scopeArray['scope'])) {
            $helper->removeEcommerceSyncData($scopeArray['scope_id'], $scopeArray['scope']);
            $helper->resetCampaign($scopeArray['scope_id'], $scopeArray['scope']);
            $helper->clearErrorGrid($scopeArray['scope_id'], $scopeArray['scope'], true);
            $helper->deleteStore($scopeArray['scope_id'], $scopeArray['scope']);
        }

        return $observer;
    }

    /**
     * Handle subscription change (subscribe/unsubscribe)
     *
     * @param Varien_Event_Observer $observer
     * @return Varien_Event_Observer
     */
    public function handleSubscriber(Varien_Event_Observer $observer)
    {
        $subscriber = $observer->getEvent()->getSubscriber();
        $helper = $this->makeHelper();
        if ($subscriber->getSubscriberSource() != Ebizmarts_MailChimp_Model_Subscriber::SUBSCRIBE_SOURCE) {
            $isEnabled = $helper->isSubscriptionEnabled($subscriber->getStoreId());
            if ($isEnabled) {
                $apiSubscriber = $this->getApiSubscriber();
                $subscriber->setImportMode(true);
                if (!Mage::getSingleton('customer/session')->isLoggedIn() && !Mage::app()->getStore()->isAdmin()) {
                    Mage::getModel('core/cookie')->set(
                        'email', $subscriber->getSubscriberEmail(), null, null, null, null, false
                    );
                }

                if (true === $subscriber->getIsStatusChanged()) {
                    $apiSubscriber->updateSubscriber($subscriber, true);
                } else {
                    $origData = $subscriber->getOrigData();

                    if (is_array($origData) && isset($origData['subscriber_status'])
                        && $origData['subscriber_status'] != $subscriber->getSubscriberStatus()
                    ) {
                        $apiSubscriber->updateSubscriber($subscriber, true);
                    }
                }
            }
        }

        return $observer;
    }

    /**
     * Handle subscriber deletion from back end.
     *
     * @param Varien_Event_Observer $observer
     * @return Varien_Event_Observer
     */
    public function handleSubscriberDeletion(Varien_Event_Observer $observer)
    {
        $subscriber = $observer->getEvent()->getSubscriber();
        $helper = $this->makeHelper();
        $isEnabled = $helper->isSubscriptionEnabled($subscriber->getStoreId());

        if ($isEnabled) {
            $this->getApiSubscriber()->deleteSubscriber($subscriber);
        }

        return $observer;
    }

    /**
     * Add Subscriber first name and last name to Newsletter Grid.
     *
     * @param  Varien_Event_Observer $observer
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
     * @param  Varien_Event_Observer $observer
     * @return Varien_Event_Observer
     */
    public function customerSaveBefore(Varien_Event_Observer $observer)
    {
        $customer = $observer->getEvent()->getCustomer();
        $storeId = $customer->getStoreId();
        $helper = $this->makeHelper();
        $isEnabled = $helper->isSubscriptionEnabled($storeId);

        if ($isEnabled) {
            $apiSubscriber = $this->getApiSubscriber();
            $origEmail = $customer->getOrigData('email');
            $customerEmail = $customer->getEmail();
            if ($origEmail) {
                // check if customer has changed email address
                if ($origEmail != $customerEmail) {
                    $subscriberModel = $this->getSubscriberModel();
                    $subscriber = $subscriberModel->loadByEmail($origEmail);
                    if ($subscriber->getId()) {
                        // unsubscribe old email address
                        $apiSubscriber->deleteSubscriber($subscriber);
                    }

                    // subscribe new email address
                    $subscriber = $subscriberModel->loadByCustomer($customer);
                    $subscriber->setSubscriberEmail($customerEmail); // make sure we set the new email address

                    $apiSubscriber->updateSubscriber($subscriber, true);
                }
            }
            //update subscriber data if a subscriber with the same email address exists
            $apiSubscriber->update($customerEmail, $storeId);

            if ($helper->isEcomSyncDataEnabled($storeId)) {
                //update mailchimp ecommerce data for that customer
                $this->getApiCustomer()->update($customer->getId(), $storeId);
            }
        }

        return $observer;
    }

    public function customerAddressSaveBefore(Varien_Event_Observer $observer)
    {
        $customerId = $observer->getEvent()->getCustomerAddress()->getCustomerId();
        $customer = $this->getCustomerModel()->load($customerId);
        $storeId = $customer->getStoreId();
        $helper = $this->makeHelper();

        if ($helper->isSubscriptionEnabled($storeId)) {
            //update subscriber data if a subscriber with the same email address exists
            $this->getApiSubscriber()->update($customer->getEmail(), $storeId);
        }

        if ($helper->isEcomSyncDataEnabled($storeId)) {
            //update mailchimp ecommerce data for that customer
            $this->getApiCustomer()->update($customerId, $storeId);
        }

        return $observer;
    }

    /**
     * Set the products included the order to be updated on MailChimp on the next cron job run.
     *
     * @param  Varien_Event_Observer $observer
     * @return Varien_Event_Observer
     * @throws Exception
     */
    public function newOrder(Varien_Event_Observer $observer)
    {
        $helper = $this->makeHelper();
        $post = $helper->getMageApp()->getRequest()->getPost('mailchimp_subscribe');
        $order = $observer->getEvent()->getOrder();
        $storeId = $order->getStoreId();
        $ecommEnabled = $helper->isEcomSyncDataEnabled($storeId);

        if ($ecommEnabled) {
            if (isset($post)) {
                $email = $order->getCustomerEmail();
                $subscriber = $helper->loadListSubscriber($post, $email);
                if ($subscriber) {
                    $helper->subscribeMember($subscriber, true);
                }
            }

            $this->removeCampaignData();

            $items = $order->getAllItems();
            foreach ($items as $item) {
                if ($this->isBundleItem($item) || $this->isConfigurableItem($item)) {
                    continue;
                }

                $mailchimpStoreId = $helper->getMCStoreId($storeId);
                $helper->saveEcommerceSyncData($item->getProductId(), Ebizmarts_MailChimp_Model_Config::IS_PRODUCT, $mailchimpStoreId, null, null, 1, null, null, null, true);
            }
        }

        return $observer;
    }

    /**
     * Catch order save before event, mark it as modified and associate the landing page to the order data.
     *
     * @param  Varien_Event_Observer $observer
     * @return Varien_Event_Observer
     */
    public function orderSaveBefore(Varien_Event_Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $storeId = $order->getStoreId();
        $apiProduct = $this->makeApiOrders();
        $helper = $this->makeHelper();
        $ecommEnabled = $helper->isEcomSyncDataEnabled($storeId);

        if ($ecommEnabled) {
            $apiProduct->update($order->getId(), $storeId);
        }

        return $observer;
    }

    /**
     * When Order object is saved add the campaign id if available in the cookies.
     *
     * @param Varien_Event_Observer $observer
     * @return Varien_Event_Observer
     */
    public function saveCampaignData(Varien_Event_Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $campaignCookie = $this->_getCampaignCookie();
        if ($campaignCookie) {
            $order->setMailchimpCampaignId($campaignCookie);
        }

        $landingCookie = $this->_getLandingCookie();
        if ($landingCookie && !$order->getMailchimpLandingPage()) {
            $order->setMailchimpLandingPage($landingCookie);
        }

        return $observer;
    }

    /**
     * Delete campaign and landing cookies.
     *
     * @return Varien_Event_Observer
     * @throws Exception
     */
    public function removeCampaignData()
    {
        if ($this->_getCampaignCookie()) {
            Mage::getModel('core/cookie')->delete('mailchimp_campaign_id');
        }

        if ($this->_getLandingCookie()) {
            Mage::getModel('core/cookie')->delete('mailchimp_landing_page');
        }
    }

    /**
     * Get campaign cookie if available.
     *
     * @return mixed
     */
    protected function _getCampaignCookie()
    {
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
     * Add section in order view with MailChimp campaign data if available.
     *
     * @param Varien_Event_Observer $observer
     * @return Varien_Event_Observer
     */
    public function addOrderViewMonkey(Varien_Event_Observer $observer)
    {
        $block = $observer->getBlock();
        if (($block->getNameInLayout() == 'order_info') && ($child = $block->getChild('mailchimp.order.info.monkey.block'))) {
            $order = $block->getOrder();
            $storeId = $order->getStoreId();
            $helper = $this->makeHelper();
            $addColumnConfig = $helper->getMonkeyInGrid($storeId);
            $ecommEnabled = $helper->isEcomSyncDataEnabled($storeId);

            if ($ecommEnabled && $addColumnConfig) {
                $transport = $observer->getTransport();
                if ($transport) {
                    $html = $transport->getHtml();
                    $html .= $child->toHtml();
                    $transport->setHtml($html);
                }
            }
        }

        return $observer;
    }

    /**
     * Add column to associate orders in grid gained from MailChimp campaigns and automations.
     *
     * @param  $observer
     * @return mixed
     */
    public function addColumnToSalesOrderGrid(Varien_Event_Observer $observer)
    {
        $helper = $this->makeHelper();
        $addColumnConfig = $helper->getMonkeyInGrid(0);
        $ecommEnabledAnyScope = $helper->isEcomSyncDataEnabledInAnyScope();
        $block = $observer->getEvent()->getBlock();
        if ($block instanceof Mage_Adminhtml_Block_Sales_Order_Grid
            && $ecommEnabledAnyScope && $addColumnConfig
        ) {
            if ($addColumnConfig == Ebizmarts_MailChimp_Model_Config::ADD_MAILCHIMP_LOGO_TO_GRID || $addColumnConfig == Ebizmarts_MailChimp_Model_Config::ADD_BOTH_TO_GRID) {
                $block->addColumnAfter(
                    'mailchimp_campaign_id', array(
                    'header' => $helper->__('MailChimp'),
                    'index' => 'mailchimp_campaign_id',
                    'align' => 'center',
                    'filter' => false,
                    'renderer' => 'mailchimp/adminhtml_sales_order_grid_renderer_mailchimp',
                    'sortable' => true,
                    'width' => 70
                ), 'created_at'
                );
            }
            if ($addColumnConfig == Ebizmarts_MailChimp_Model_Config::ADD_SYNC_STATUS_TO_GRID || $addColumnConfig == Ebizmarts_MailChimp_Model_Config::ADD_BOTH_TO_GRID) {
                $block->addColumnAfter(
                    'mailchimp_synced_flag', array(
                    'header' => $helper->__('Synced to MailChimp'),
                    'index' => 'mailchimp_synced_flag',
                    'align' => 'center',
                    'filter' => false,
                    'renderer' => 'mailchimp/adminhtml_sales_order_grid_renderer_mailchimpOrder',
                    'sortable' => true,
                    'width' => 70
                ), 'created_at'
                );

                $columnId = $block->getParam($block->getVarNameSort());
                $direction = $block->getParam($block->getVarNameDir());
                if ($columnId == 'mailchimp_synced_flag') {
                    Mage::register('sort_column_dir', $direction);
                }
            }
        }

        return $observer;
    }



    public function addColumnToSalesOrderGridCollection(Varien_Event_Observer $observer)
    {

        $helper = $this->makeHelper();
        $addColumnConfig = $helper->getMonkeyInGrid(0);
        $ecommEnabledAnyScope = $helper->isEcomSyncDataEnabledInAnyScope();
        if ($ecommEnabledAnyScope && $addColumnConfig) {
            $collection = $observer->getOrderGridCollection();
            $collection->addFilterToMap('store_id', 'main_table.store_id');
            $select = $collection->getSelect();
            $select->joinLeft(array('oe' => $collection->getTable('sales/order')), 'oe.entity_id=main_table.entity_id', array('oe.mailchimp_campaign_id'));
            $adapter = $this->getCoreResource()->getConnection('core_write');
            $select->joinLeft(array('mc' => $collection->getTable('mailchimp/ecommercesyncdata')), $adapter->quoteInto('mc.related_id=main_table.entity_id AND type = ?', Ebizmarts_MailChimp_Model_Config::IS_ORDER), array('mc.mailchimp_synced_flag', 'mc.id'));
            $select->group("main_table.entity_id");
            $direction = Mage::registry('sort_column_dir');
            if ($direction) {
                $collection->addOrder('mc.id', $direction);
                Mage::unregister('sort_column_dir');
            }
        }
    }

    /**
     * Add customer to the cart if it placed the email address in the popup or footer subscription form.
     *
     * @param  Varien_Event_Observer $observer
     * @return Varien_Event_Observer
     */
    public function loadCustomerToQuote(Varien_Event_Observer $observer)
    {
        $quote = $observer->getEvent()->getQuote();
        $storeId = $quote->getStoreId();
        $helper = $this->makeHelper();
        $isEcomEnabled = $helper->isEcomSyncDataEnabled($storeId);
        $isAbandonedCartEnabled = $helper->isAbandonedCartEnabled($storeId);

        if (!Mage::getSingleton('customer/session')->isLoggedIn()
            && $isEcomEnabled && $isAbandonedCartEnabled
        ) {
            $action = Mage::app()->getRequest()->getActionName();
            $onCheckout = ($action == 'saveOrder' || $action == 'savePayment' ||
                $action == 'saveShippingMethod' || $action == 'saveBilling');
            if (Mage::getModel('core/cookie')->get('email')
                && Mage::getModel('core/cookie')->get('email') != 'none' && !$onCheckout
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
     * Set the products included in the credit memo to be updated on MailChimp on the next cron job run.
     *
     * @param Varien_Event_Observer $observer
     * @return Varien_Event_Observer
     */
    public function newCreditMemo(Varien_Event_Observer $observer)
    {
        $creditMemo = $observer->getEvent()->getCreditmemo();
        $order = $creditMemo->getOrder();
        $storeId = $order->getStoreId();
        $helper = $this->makeHelper();
        $ecomEnabled = $helper->isEcomSyncDataEnabled($storeId);

        if ($ecomEnabled) {

            $mailchimpStoreId = $helper->getMCStoreId($storeId);

            $items = $creditMemo->getAllItems();

            foreach ($items as $item) {
                if ($this->isBundleItem($item) || $this->isConfigurableItem($item)) {
                    continue;
                }

                $helper->saveEcommerceSyncData($item->getProductId(), Ebizmarts_MailChimp_Model_Config::IS_PRODUCT, $mailchimpStoreId, null, null, 1, null, null, null, true);
            }

            $helper->saveEcommerceSyncData($order->getEntityId(), Ebizmarts_MailChimp_Model_Config::IS_ORDER, $mailchimpStoreId, null, null, 1, null, null, null, true);
        }
        return $observer;
    }

    /**
     * Set the products included in the credit memo to be updated on MailChimp on the next cron job run.
     *
     * @param Varien_Event_Observer $observer
     * @return Varien_Event_Observer
     */
    public function cancelCreditMemo(Varien_Event_Observer $observer)
    {
        $creditMemo = $observer->getEvent()->getCreditmemo();
        $order = $creditMemo->getOrder();
        $storeId = $order->getStoreId();
        $helper = $this->makeHelper();
        $ecomEnabled = $helper->isEcomSyncDataEnabled($storeId);

        if ($ecomEnabled) {

            $mailchimpStoreId = $helper->getMCStoreId($storeId);

            $items = $creditMemo->getAllItems();
            foreach ($items as $item) {
                if ($this->isBundleItem($item) || $this->isConfigurableItem($item)) {
                    continue;
                }

                $helper->saveEcommerceSyncData($item->getProductId(), Ebizmarts_MailChimp_Model_Config::IS_PRODUCT, $mailchimpStoreId, null, null, 1, null, null, null, true);
            }

            $helper->saveEcommerceSyncData($order->getEntityId(), Ebizmarts_MailChimp_Model_Config::IS_ORDER, $mailchimpStoreId, null, null, 1, null, null, null, true);
        }
        return $observer;
    }

    /**
     * Set the products canceled to be updated on MailChimp on the next cron job run.
     *
     * @param  Varien_Event_Observer $observer
     * @return Varien_Event_Observer
     * @throws Exception
     */
    public function itemCancel(Varien_Event_Observer $observer)
    {
        $item = $observer->getEvent()->getItem();
        $helper = $this->makeHelper();
        $storeId = $item->getStoreId();
        $ecomEnabled = $helper->isEcomSyncDataEnabled($storeId);

        if ($ecomEnabled) {

            $mailchimpStoreId = $helper->getMCStoreId($storeId);

            if (!$this->isBundleItem($item) && !$this->isConfigurableItem($item)) {
                $helper->saveEcommerceSyncData($item->getProductId(), Ebizmarts_MailChimp_Model_Config::IS_PRODUCT, $mailchimpStoreId, null, null, 1, null, null, null, true);
            }
        }

        return $observer;
    }

    /**
     * When Product object is saved set it to be updated on MailChimp if getMailchimpUpdateObserverRan() is false.
     *
     * @param  Varien_Event_Observer $observer
     * @return Varien_Event_Observer
     */
    public function productSaveBefore(Varien_Event_Observer $observer)
    {
        $product = $observer->getEvent()->getProduct();
        $helper = $this->makeHelper();
        $apiProduct = $this->makeApiProducts();
        $mailchimpStoreIdsArray = $helper->getAllMailChimpStoreIds();

        foreach ($mailchimpStoreIdsArray as $scopeData => $mailchimpStoreId) {

            $scopeArray = $this->getScopeArrayFromString($scopeData);
            $ecommEnabled = $helper->isEcommerceEnabled($scopeArray['scope_id'], $scopeArray['scope']);

            if ($ecommEnabled) {
                $apiProduct->update($product->getId(), $mailchimpStoreId);
            }
        }

        return $observer;
    }

    /**
     * Catch Magento store group change event and call changeName function for the relevant stores.
     *
     * @param Varien_Event_Observer $observer
     * @return Varien_Event_Observer
     */
    public function changeStoreGroupName(Varien_Event_Observer $observer)
    {
        $stores = $observer->getGroup()->getStores();

        foreach ($stores as $store) {
            $storeId = $store->getId();
            $this->changeStoreNameIfModuleEnabled($storeId);
        }

        return $observer;
    }

    /**
     * @param Varien_Event_Observer $observer
     * @return Varien_Event_Observer
     */
    public function changeStoreName(Varien_Event_Observer $observer)
    {
        $storeId = $observer->getStore()->getId();

        $this->changeStoreNameIfModuleEnabled($storeId);

        return $observer;
    }

    /**
     * @param $storeId
     */
    public function changeStoreNameIfModuleEnabled($storeId)
    {
        $helper = $this->makeHelper();
        $mailchimpStoreId = $helper->getMCStoreId($storeId);

        if ($mailchimpStoreId) {
            $realScope = $helper->getRealScopeForConfig(Ebizmarts_MailChimp_Model_Config::GENERAL_MCSTOREID, $storeId);
            if ($realScope['scope_id'] == $storeId && $realScope['scope'] == 'stores') {
                $ecomEnabled = $helper->isEcomSyncDataEnabled($realScope['scope_id'], $realScope['scope']);
                if ($ecomEnabled) {
                    if (!$helper->isUsingConfigStoreName($realScope['scope_id'], $realScope['scope'])) {
                        $storeName = $helper->getMCStoreName($realScope['scope_id'], $realScope['scope']);
                        $helper->changeName($storeName, $realScope['scope_id'], $realScope['scope']);
                    }
                }
            }
        }
    }

    /**
     * @param Varien_Event_Observer $observer
     * @return Varien_Event_Observer
     */
    public function productAttributeUpdate(Varien_Event_Observer $observer)
    {
        $productIds = $observer->getEvent()->getProductIds();
        $helper = $this->makeHelper();
        $apiProduct = $this->makeApiProducts();
        $mailchimpStoreIdsArray = $helper->getAllMailChimpStoreIds();

        foreach ($mailchimpStoreIdsArray as $scopeData => $mailchimpStoreId) {

            $scopeArray = $this->getScopeArrayFromString($scopeData);
            $ecommEnabled = $helper->isEcommerceEnabled($scopeArray['scope_id'], $scopeArray['scope']);

            if ($ecommEnabled) {
                foreach ($productIds as $productId) {
                    $apiProduct->update($productId, $mailchimpStoreId);
                }
            }
        }

        return $observer;
    }

    /**
     * @param $order
     */
    protected function handleOrderUpdate($order)
    {
        $storeId = Mage::app()->getStore()->getStoreId();

        if ($storeId == 0) {
            $this->handleAdminOrderUpdate($order);
        } else {
            $this->makeApiOrders()->update($order->getId(), $storeId);
        }
    }

    public function salesruleSaveAfter(Varien_Event_Observer $observer)
    {
        $promoRulesApi = $this->getPromoRulesApi();
        $rule = $observer->getEvent()->getRule();
        $ruleId = $rule->getRuleId();
        $promoRulesApi->update($ruleId);

        return $observer;
    }

    public function salesruleDeleteAfter(Varien_Event_Observer $observer)
    {
        $promoRulesApi = $this->getPromoRulesApi();
        $rule = $observer->getEvent()->getRule();
        $ruleId = $rule->getRuleId();
        $promoRulesApi->markAsDeleted($ruleId);

        return $observer;
    }

    public function secondaryCouponsDelete(Varien_Event_Observer $observer)
    {
        $promoCodesApi = $this->getPromoCodesApi();
        $params = Mage::app()->getRequest()->getParams();
        if (isset($params['ids']) && isset($params['id'])) {
            $promoRuleId = $params['id'];
            $promoCodeIds = $params['ids'];
            foreach ($promoCodeIds as $promoCodeId) {
                $promoCodesApi->markAsDeleted($promoCodeId, $promoRuleId);
            }
        }

        return $observer;

    }

    public function cleanProductImagesCacheAfter(Varien_Event_Observer $observer)
    {
        $configValues = array(array(Ebizmarts_MailChimp_Model_Config::PRODUCT_IMAGE_CACHE_FLUSH, 1));
        $this->makeHelper()->saveMailchimpConfig($configValues, 0, 'default');

        return $observer;
    }

    public function frontInitBefore(Varien_Event_Observer $observer)
    {
        $helper = $this->makeHelper();
        if ($helper->wasProductImageCacheFlushed()) {
            try {
                $this->markProductsAsModified();
            } catch (Exception $e) {
                $helper->logError($e->getMessage());
            }
            $config = $this->getConfig();
            $config->deleteConfig(Ebizmarts_MailChimp_Model_Config::PRODUCT_IMAGE_CACHE_FLUSH, 'default', 0);
            $config->cleanCache();
        }

        return $observer;
    }

    protected function markProductsAsModified()
    {
        $tableName = $mailchimpTableName = $this->getCoreResource()->getTableName('mailchimp/ecommercesyncdata');
        $sqlQuery = "UPDATE " . $tableName . " SET mailchimp_sync_modified = 1 WHERE type = '" . Ebizmarts_MailChimp_Model_Config::IS_PRODUCT . "';";
        $connection = $this->getCoreResource()->getConnection('core_write');
        $connection->query($sqlQuery);
    }

    /**
     * @param $item
     * @return bool
     */
    protected function isBundleItem($item)
    {
        return $item->getProductType() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE;
    }

    /**
     * @param $item
     * @return bool
     */
    protected function isConfigurableItem($item)
    {
        return $item->getProductType() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE;
    }

    /**
     * Transform array in format scope_scopeId to array.
     *
     * @param $scopeData
     * @return array
     */
    protected function getScopeArrayFromString($scopeData)
    {
        $scopeArray = explode('_', $scopeData);
        return array('scope' => $scopeArray[0], 'scope_id' => $scopeArray[1]);
    }
}
