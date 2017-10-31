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
     * Handle save of System -> Configuration, section <mailchimp>
     *
     * @param  Varien_Event_Observer $observer
     * @return void|Varien_Event_Observer
     */
    public function saveConfig(Varien_Event_Observer $observer)
    {
        $post = Mage::app()->getRequest()->getPost();
        $scopeArray = explode('-', $this->makeHelper()->getScopeString());

        if (isset($post['groups']['general']['fields']['list']['inherit']) && $this->makeHelper()->getIfConfigExistsForScope(Ebizmarts_MailChimp_Model_Config::GENERAL_MCSTOREID, $scopeArray[1], $scopeArray[0])) {
            $this->makeHelper()->removeEcommerceSyncData($scopeArray[1], $scopeArray[0]);
            $this->makeHelper()->resetCampaign($scopeArray[1], $scopeArray[0]);
            $this->makeHelper()->clearErrorGrid($scopeArray[1], $scopeArray[0], true);
            $this->makeHelper()->deleteStore($scopeArray[1], $scopeArray[0]);
        }

        return $observer;
    }

    /**
     * Handle subscription change (subscribe/unsubscribe)
     *
     * @param Varien_Event_Observer $observer
     */
    public function handleSubscriber(Varien_Event_Observer $observer)
    {
        $subscriber = $observer->getEvent()->getSubscriber();
        if ($subscriber->getSubscriberSource() != Ebizmarts_MailChimp_Model_Subscriber::SUBSCRIBE_SOURCE) {
            $isEnabled = $this->makeHelper()->isMailChimpEnabled($subscriber->getStoreId());
            if ($isEnabled) {
                $subscriber->setImportMode(true);
                if (!Mage::getSingleton('customer/session')->isLoggedIn() && !Mage::app()->getStore()->isAdmin()) {
                    Mage::getModel('core/cookie')->set(
                        'email', $subscriber->getSubscriberEmail(), null, null, null, null, false
                    );
                }

                if (true === $subscriber->getIsStatusChanged()) {
                    Mage::getModel('mailchimp/api_subscribers')->updateSubscriber($subscriber, true);
                } else {
                    $origData = $subscriber->getOrigData();

                    if (is_array($origData) && isset($origData['subscriber_status'])
                        && $origData['subscriber_status'] != $subscriber->getSubscriberStatus()
                    ) {
                        Mage::getModel('mailchimp/api_subscribers')->updateSubscriber($subscriber, true);
                    }
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
        $subscriber = $observer->getEvent()->getSubscriber();
        $isEnabled = $this->makeHelper()->isMailChimpEnabled($subscriber->getStoreId());

        if ($isEnabled) {
            Mage::getModel('mailchimp/api_subscribers')->deleteSubscriber($subscriber);
        }
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

        if ($customer->getOrigData('email')) {
            // check if customer has changed email address
            if ($customer->getOrigData('email') != $customer->getEmail()) {

                // unsubscribe old email address
                $subscriber = Mage::getModel('newsletter/subscriber');
                $subscriber
                    ->setSubscriberEmail($customer->getOrigData('email'))
                    ->setStoreId($storeId);

                Mage::getModel('mailchimp/api_subscribers')->deleteSubscriber($subscriber);

                // subscribe new email address
                $subscriber = Mage::getModel('newsletter/subscriber')->loadByCustomer($customer);
                $subscriber->setSubscriberEmail($customer->getEmail()); // make sure we set the new email address

                Mage::getModel('mailchimp/api_subscribers')->updateSubscriber($subscriber, true);
            }
        }

        //update mailchimp ecommerce data for that customer
        Mage::getModel('mailchimp/api_customers')->update($customer->getId(), $storeId);
        //update subscriber data if a subscriber with the same email address exists
        Mage::getModel('mailchimp/api_subscribers')->update($customer->getEmail(), $storeId);
        return $observer;
    }

    public function customerAddressSaveBefore(Varien_Event_Observer $observer)
    {
        $customerId = $observer->getEvent()->getCustomerAddress()->getCustomerId();
        $customer = Mage::getModel('customer/customer')->load($customerId);
        $storeId = $customer->getStoreId();

        //update mailchimp ecommerce data for that customer
        Mage::getModel('mailchimp/api_customers')->update($customerId, $storeId);
        //update subscriber data if a subscriber with the same email address exists
        Mage::getModel('mailchimp/api_subscribers')->update($customer->getEmail(), $storeId);
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
        $storeId = Mage::app()->getStore()->getStoreId();
        //update mailchimp ecommerce data for that product variant
        $this->makeApiProducts()->update($product->getId(), $storeId);
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

        $landingCookie = $this->_getLandingCookie();
        if ($landingCookie && !$order->getMailchimpLandingPage()) {
            $order->setMailchimpLandingPage($landingCookie);
        }
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
        $this->handleOrderUpdate($order);
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
     * Add column to associate orders gained from MailChimp campaigns and automations.
     *
     * @param  $observer
     * @return mixed
     */
    public function addColumnToSalesOrderGrid($observer)
    {
        $scopeArray = explode('-', $this->makeHelper()->getScopeString());
        $block = $observer->getEvent()->getBlock();
        if ($block instanceof Mage_Adminhtml_Block_Sales_Order_Grid
            && $this->makeHelper()->getMonkeyInGrid($scopeArray[1], $scopeArray[0])
            && ($this->makeHelper()->isAbandonedCartEnabled($scopeArray[1], $scopeArray[0])
                || $this->makeHelper()->isMailChimpEnabled($scopeArray[1], $scopeArray[0]))
        ) {
            $block->addColumnAfter(
                'mailchimp_flag', array(
                'header' => $this->makeHelper()->__('MailChimp'),
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
     * @param  Varien_Event_Observer $observer
     * @return Varien_Event_Observer
     */
    public function loadCustomerToQuote(Varien_Event_Observer $observer)
    {
        $quote = $observer->getEvent()->getQuote();
        if (!Mage::getSingleton('customer/session')->isLoggedIn()
            && $this->makeHelper()->isEmailCatcherEnabled($quote->getStoreId())
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
     * Set the products included the order to be updated on MailChimp on the next cron job run.
     *
     * @param  Varien_Event_Observer $observer
     * @return Varien_Event_Observer
     * @throws Exception
     */
    public function newOrder(Varien_Event_Observer $observer)
    {
        $post = Mage::app()->getRequest()->getPost('mailchimp_subscribe');
        $helper = $this->makeHelper();
        if (isset($post)) {
            $order = $observer->getEvent()->getOrder();
            $email = $order->getCustomerEmail();
            $subscriber = $helper->loadListSubscriber($post, $email);
            if ($subscriber) {
                $helper->subscribeMember($subscriber, true);
            }
        }

        $this->removeCampaignData();

        $order = $observer->getEvent()->getOrder();
        $items = $order->getAllItems();
        foreach ($items as $item) {
            if ($item->getProductType() == 'bundle' || $item->getProductType() == 'configurable') {
                continue;
            }

            $mailchimpStoreId = $this->makeHelper()->getMCStoreId($order->getStoreId());
            $this->makeHelper()->saveEcommerceSyncData($item->getProductId(), Ebizmarts_MailChimp_Model_Config::IS_PRODUCT, $mailchimpStoreId, null, null, 1, null, null, true);
        }

        return $observer;
    }

    /**
     * Set the products included in the credit memo to be updated on MailChimp on the next cron job run.
     *
     * @param  Varien_Event_Observer $observer
     * @return Varien_Event_Observer
     * @throws Exception
     */
    public function newCreditMemo(Varien_Event_Observer $observer)
    {
        $creditMemo = $observer->getEvent()->getCreditmemo();
        $order = $creditMemo->getOrder();
        $mailchimpStoreId = $this->makeHelper()->getMCStoreId($order->getStoreId());
        $items = $creditMemo->getAllItems();
        foreach ($items as $item) {
            if ($item->getProductType() == 'bundle' || $item->getProductType() == 'configurable') {
                continue;
            }

            $this->makeHelper()->saveEcommerceSyncData($item->getProductId(), Ebizmarts_MailChimp_Model_Config::IS_PRODUCT, $mailchimpStoreId, null, null, 1, null, null, true);
        }

        $this->makeHelper()->saveEcommerceSyncData($order->getEntityId(), Ebizmarts_MailChimp_Model_Config::IS_ORDER, $mailchimpStoreId, null, null, 1, null, null, true);
        return $observer;
    }

    /**
     * Set the products included in the credit memo to be updated on MailChimp on the next cron job run.
     *
     * @param  Varien_Event_Observer $observer
     * @return Varien_Event_Observer
     * @throws Exception
     */
    public function cancelCreditMemo(Varien_Event_Observer $observer)
    {
        $creditMemo = $observer->getEvent()->getCreditmemo();
        $order = $creditMemo->getOrder();
        $mailchimpStoreId = $this->makeHelper()->getMCStoreId($order->getStoreId());
        $items = $creditMemo->getAllItems();
        foreach ($items as $item) {
            if ($item->getProductType() == 'bundle' || $item->getProductType() == 'configurable') {
                continue;
            }

            $this->makeHelper()->saveEcommerceSyncData($item->getProductId(), Ebizmarts_MailChimp_Model_Config::IS_PRODUCT, $mailchimpStoreId, null, null, 1, null, null, true);
        }

        $this->makeHelper()->saveEcommerceSyncData($order->getEntityId(), Ebizmarts_MailChimp_Model_Config::IS_ORDER, $mailchimpStoreId, null, null, 1, null, null, true);
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
        $mailchimpStoreId = $this->makeHelper()->getMCStoreId($item->getStoreId());
        if ($item->getProductType() != 'bundle' && $item->getProductType() != 'configurable') {
            $this->makeHelper()->saveEcommerceSyncData($item->getProductId(), Ebizmarts_MailChimp_Model_Config::IS_PRODUCT, $mailchimpStoreId, null, null, 1, null, null, true);
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
        if (($block->getNameInLayout() == 'order_info') && ($child = $block->getChild('mailchimp.order.info.monkey.block'))) {
            $transport = $observer->getTransport();
            if ($transport) {
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
        $scopeArray = explode('-', $this->makeHelper()->getScopeString());
        $group = $observer->getGroup();
        $storeName = Mage::getStoreConfig('general/store_information/name');
        if ($storeName == '') {
            $this->makeHelper()->changeName($group->getName(), $scopeArray[1], $scopeArray[0]);
        }
    }

    public function productAttributeUpdate(Varien_Event_Observer $observer)
    {
        $productIds = $observer->getEvent()->getProductIds();
        $storeId = $observer->getEvent()->getStoreId();

        foreach ($productIds as $productId) {
            $this->makeApiProducts()->update($productId, $storeId);
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
        //                    if ($productSyncData->getMailchimpSyncDelta() && $productSyncData->getMailchimpSyncDelta() > Mage::helper('mailchimp')->getEcommMinSyncDateFlag($storeId)) {
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
     * @param $order
     */
    protected function handleAdminOrderUpdate($order)
    {
        $mailchimpStoreIdsArray = $this->makeHelper()->getAllMailChimpStoreIds();
        foreach ($mailchimpStoreIdsArray as $scopeData => $mailchimpStoreId) {
            $scopeArray = explode('_', $scopeData);
            if ($scopeArray[0] != 'websites') {
                Mage::getModel('mailchimp/api_orders')->update($order->getId(), $scopeArray[1]);
            } else {
                $website = Mage::getModel('core/website')->load($scopeArray[1]);
                $storeIds = $website->getStoreIds();
                foreach ($storeIds as $storeId) {
                    Mage::getModel('mailchimp/api_orders')->update($order->getId(), $storeId);
                }
            }
        }
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
            Mage::getModel('mailchimp/api_orders')->update($order->getId(), $storeId);
        }
    }

    public function salesruleSaveAfter(Varien_Event_Observer $observer)
    {
        $promoRulesApi = $this->getPromoRulesApi();
        $rule = $observer->getEvent()->getRule();
        $ruleId = $rule->getRuleId();
        $promoRulesApi->update($ruleId);
    }

    public function salesruleDeleteAfter(Varien_Event_Observer $observer)
    {
        $promoRulesApi = $this->getPromoRulesApi();
        $rule = $observer->getEvent()->getRule();
        $ruleId = $rule->getRuleId();
        $promoRulesApi->markAsDeleted($ruleId);
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

    }

    /**
     * @return Ebizmarts_MailChimp_Model_Api_PromoCodes
     */
    protected function getPromoCodesApi()
    {
        $promoCodesApi = Mage::getModel('mailchimp/api_promoCodes');
        return $promoCodesApi;
    }

    /**
     * @return Ebizmarts_MailChimp_Model_Api_PromoRules
     */
    protected function getPromoRulesApi()
    {
        $promoRulesApi = Mage::getModel('mailchimp/api_promoRules');
        return $promoRulesApi;
    }

    public function cleanProductImagesCacheAfter(Varien_Event_Observer $observer)
    {
        $configValues = array(array(Ebizmarts_MailChimp_Model_Config::PRODUCT_IMAGE_CACHE_FLUSH, 1));
        $this->makeHelper()->saveMailchimpConfig($configValues, 0, 'default');
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

    }

    /**
     * @return Mage_Core_Model_Abstract
     */
    protected function getCoreResource()
    {
        return Mage::getSingleton('core/resource');
    }

    protected function markProductsAsModified()
    {
        $tableName = $mailchimpTableName = $this->getCoreResource()->getTableName('mailchimp/ecommercesyncdata');
        $sqlQuery = "UPDATE " . $tableName . " SET mailchimp_sync_modified = 1 WHERE type = '" . Ebizmarts_MailChimp_Model_Config::IS_PRODUCT . "';";
        $connection = $this->getCoreResource()->getConnection('core_write');
        $connection->query($sqlQuery);
    }

    /**
     * @return Mage_Core_Model_Config
     */
    protected function getConfig()
    {
        $config = Mage::getConfig();
        return $config;
    }
}
