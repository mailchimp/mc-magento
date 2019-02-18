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

    const PRODUCT_IS_DISABLED = 2;

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
    protected function makeApiOrder()
    {
        return Mage::getModel('mailchimp/api_orders');
    }

    /**
     * @return Ebizmarts_MailChimp_Model_Api_PromoCodes
     */
    protected function makeApiPromoCode()
    {
        return Mage::getModel('mailchimp/api_promoCodes');
    }

    /**
     * @return Ebizmarts_MailChimp_Model_Api_PromoRules
     */
    protected function makeApiPromoRule()
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
    protected function makeApiProduct()
    {
        return Mage::getModel('mailchimp/api_products');
    }

    /**
     * @return Ebizmarts_MailChimp_Model_Api_Subscribers
     */
    protected function makeApiSubscriber()
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
    protected function makeApiCustomer()
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
        $post = $this->getRequest()->getPost();
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
     * Handle confirmation emails and subscription to Mailchimp
     *
     * @param Varien_Event_Observer $observer
     * @return Varien_Event_Observer
     * @throws Mage_Core_Exception
     */
    public function subscriberSaveBefore(Varien_Event_Observer $observer)
    {
        $subscriber = $observer->getEvent()->getSubscriber();
        $storeId = $subscriber->getStoreId();
        $helper = $this->makeHelper();
        $isEnabled = $helper->isSubscriptionEnabled($storeId);

        if ($isEnabled && $subscriber->getSubscriberSource() != Ebizmarts_MailChimp_Model_Subscriber::SUBSCRIBE_SOURCE) {
            $statusChanged = $subscriber->getIsStatusChanged();

            //Override Magento status to always send double opt-in confirmation.
            if ($statusChanged && $subscriber->getStatus() == Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED && $helper->isSubscriptionConfirmationEnabled($storeId) && !$helper->isUseMagentoEmailsEnabled($storeId)) {
                $subscriber->setStatus(Mage_Newsletter_Model_Subscriber::STATUS_NOT_ACTIVE);
                $this->addSuccessIfRequired($helper);
            }

        }

        return $observer;
    }

    /**
     * Handle interest groups for subscriber and allow Magento email to be sent if configured that way.
     *
     * @param Varien_Event_Observer $observer
     * @return Varien_Event_Observer
     * @throws Mage_Core_Exception
     * @throws Mage_Core_Model_Store_Exception
     */
    public function subscriberSaveAfter(Varien_Event_Observer $observer)
    {
        $subscriber = $observer->getEvent()->getSubscriber();
        $storeId = $subscriber->getStoreId();
        $helper = $this->makeHelper();
        $isEnabled = $helper->isSubscriptionEnabled($storeId);

        if ($isEnabled && $subscriber->getSubscriberSource() != Ebizmarts_MailChimp_Model_Subscriber::SUBSCRIBE_SOURCE) {
            $params = $this->getRequest()->getParams();
            $helper->saveInterestGroupData($params, $storeId, null, $subscriber);

            $this->createEmailCookie($subscriber);

            if ($helper->isUseMagentoEmailsEnabled($storeId) != 1) {
                $apiSubscriber = $this->makeApiSubscriber();
                if ($subscriber->getIsStatusChanged()) {
                    $apiSubscriber->updateSubscriber($subscriber, true);
                } else {
                    $origData = $subscriber->getOrigData();

                    if (is_array($origData) && isset($origData['subscriber_status'])
                        && $origData['subscriber_status'] != $subscriber->getSubscriberStatus()
                    ) {
                        $apiSubscriber->updateSubscriber($subscriber, true);
                    }
                }
            } else {
                $subscriber->setImportMode(false);
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
            $this->makeApiSubscriber()->deleteSubscriber($subscriber);
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
    public function customerSaveAfter(Varien_Event_Observer $observer)
    {
        $customer = $observer->getEvent()->getCustomer();
        $origEmail = $customer->getOrigData('email');
        $customerEmail = $customer->getEmail();
        $storeId = $customer->getStoreId();
        // if customer was created in admin, use store id selected for Mailchimp.
        if (!$storeId) {
            $storeId = $customer->getMailchimpStoreView();
        }
        $helper = $this->makeHelper();
        $isEnabled = $helper->isSubscriptionEnabled($storeId);
        $params = $this->getRequest()->getParams();

        if ($isEnabled) {
            $customerId = $customer->getId();
            $subscriberEmail = ($origEmail) ? $origEmail : $customerEmail;
            $subscriber = $this->handleCustomerGroups($subscriberEmail, $params, $storeId, $customerId);
            $apiSubscriber = $this->makeApiSubscriber();
            if ($origEmail) {
                // check if customer has changed email address
                if ($origEmail != $customerEmail) {
                    if ($subscriber->getId()) {
                        // unsubscribe old email address
                        $apiSubscriber->deleteSubscriber($subscriber);

                        // subscribe new email address
                        $subscriberModel = $this->getSubscriberModel();
                        $subscriber = $subscriberModel->loadByCustomer($customer);
                        $subscriber->setSubscriberEmail($customerEmail); // make sure we set the new email address
                        $subscriber->save();

                    }
                }
            }
            //update subscriber data if a subscriber with the same email address exists and was not affected.
            if (!$origEmail || $origEmail == $customerEmail) {
                $apiSubscriber->update($customerEmail, $storeId);
            }

            if ($helper->isEcomSyncDataEnabled($storeId)) {
                //update mailchimp ecommerce data for that customer
                $this->makeApiCustomer()->update($customerId, $storeId);
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
            $this->makeApiSubscriber()->update($customer->getEmail(), $storeId);
        }

        if ($helper->isEcomSyncDataEnabled($storeId)) {
            //update mailchimp ecommerce data for that customer
            $this->makeApiCustomer()->update($customerId, $storeId);
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
        $subEnabled = $helper->isSubscriptionEnabled($storeId);

        if ($subEnabled) {
            if (isset($post)) {
                $email = $order->getCustomerEmail();
                $subscriber = $helper->loadListSubscriber($post, $email);
                if ($subscriber) {
                    if (!$subscriber->getCustomerId()) {
                        $subscriber->setSubscriberFirstname($order->getCustomerFirstname());
                        $subscriber->setSubscriberLastname($order->getCustomerLastname());
                    }
                    $subscriber->subscribe($email);
                }
            }
        }

        if ($ecommEnabled) {
            $this->removeCampaignData();

            $items = $order->getAllItems();
            foreach ($items as $item) {
                if ($this->isBundleItem($item) || $this->isConfigurableItem($item)) {
                    continue;
                }

                $mailchimpStoreId = $helper->getMCStoreId($storeId);
                $this->makeApiProduct()->update($item->getProductId(), $mailchimpStoreId);
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
        $apiOrder = $this->makeApiOrder();
        $helper = $this->makeHelper();
        $ecommEnabled = $helper->isEcomSyncDataEnabled($storeId);

        if ($ecommEnabled) {
            $apiOrder->update($order->getId(), $storeId);
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
            $ecommEnabled = $helper->isEcomSyncDataEnabled($storeId);

            if ($ecommEnabled) {
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
                    'sortable' => false,
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
            $select = $collection->getSelect();
            $fromClause = $select->getPart(Zend_Db_Select::FROM);
            //check if mc alias is already defined, avoids possible conflicts
            if (array_key_exists('mc', $fromClause)) {
                return;
            }

            $adapter = $this->getCoreResource()->getConnection('core_write');
            $select->joinLeft(array('mc' => $collection->getTable('mailchimp/ecommercesyncdata')), $adapter->quoteInto('mc.related_id=main_table.entity_id AND type = ?', Ebizmarts_MailChimp_Model_Config::IS_ORDER), array('mc.mailchimp_synced_flag', 'mc.id'));
            $select->group("main_table.entity_id");
            $direction = $this->getRegistry();
            if ($direction) {
                $collection->addOrder('mc.id', $direction);
                $this->removeRegistry();
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
        $email = null;

        if (!$this->isCustomerLoggedIn()
            && $isEcomEnabled && $isAbandonedCartEnabled
        ) {
            $action = $this->getRequestActionName();
            $onCheckout = ($action == 'saveOrder' || $action == 'savePayment' ||
                $action == 'saveShippingMethod' || $action == 'saveBilling');
            $emailCookie = $this->getEmailCookie();
            $mcEidCookie = $this->getMcEidCookie();
            if ($emailCookie && $emailCookie != 'none' && !$onCheckout
            ) {
                $email = $this->getEmailFromPopUp($emailCookie);
            } elseif ($mcEidCookie) {
                $email = $this->getEmailFromMcEid($storeId, $mcEidCookie);
            }

            if ($quote->getCustomerEmail() != $email && $email !== null) {
                $quote->setCustomerEmail($email);
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
        $apiProduct = $this->makeApiProduct();
        $apiOrder = $this->makeApiOrder();

        if ($ecomEnabled) {

            $mailchimpStoreId = $helper->getMCStoreId($storeId);

            $items = $creditMemo->getAllItems();

            foreach ($items as $item) {
                if ($this->isBundleItem($item) || $this->isConfigurableItem($item)) {
                    continue;
                }

                $apiProduct->update($item->getProductId(), $mailchimpStoreId);
            }

            $apiOrder->update($order->getEntityId(), $storeId);
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
        $apiProduct = $this->makeApiProduct();
        $apiOrder = $this->makeApiOrder();

        if ($ecomEnabled) {

            $mailchimpStoreId = $helper->getMCStoreId($storeId);

            $items = $creditMemo->getAllItems();
            foreach ($items as $item) {
                if ($this->isBundleItem($item) || $this->isConfigurableItem($item)) {
                    continue;
                }

                $apiProduct->update($item->getProductId(), $mailchimpStoreId);
            }

            $apiOrder->update($order->getEntityId(), $storeId);
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
        $apiProduct = $this->makeApiProduct();

        if ($ecomEnabled) {

            $mailchimpStoreId = $helper->getMCStoreId($storeId);

            if (!$this->isBundleItem($item) && !$this->isConfigurableItem($item)) {
                $apiProduct->update($item->getProductId(), $mailchimpStoreId);
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
        $apiProduct = $this->makeApiProduct();
        $mailchimpStoreIdsArray = $helper->getAllMailChimpStoreIds();

        foreach ($mailchimpStoreIdsArray as $scopeData => $mailchimpStoreId) {

            $scopeArray = $this->getScopeArrayFromString($scopeData);
            $ecommEnabled = $helper->isEcommerceEnabled($scopeArray['scope_id'], $scopeArray['scope']);

            if ($ecommEnabled) {
                if ($product->getStatus() == self::PRODUCT_IS_DISABLED) {
                    $apiProduct->updateDisabledProducts($product->getId(), $mailchimpStoreId);
                } else {
                    $apiProduct->update($product->getId(), $mailchimpStoreId);
                }
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
        $apiProduct = $this->makeApiProduct();
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
     * @param $emailCookie
     * @return mixed
     */
    protected function getEmailFromPopUp($emailCookie)
    {
        $emailCookieArr = explode('/', $emailCookie);
        $email = $emailCookieArr[0];
        $email = str_replace(' ', '+', $email);
        return $email;
    }

    /**
     * @param $helper
     * @param $storeId
     * @param $mcEidCookie
     * @return mixed
     */
    protected function getEmailFromMcEid($storeId, $mcEidCookie)
    {
        $helper = $this->makeHelper();
        $mailchimpApi = $helper->getApi($storeId);
        $listId = $helper->getGeneralList($storeId);
        $listMember = $mailchimpApi->lists->members->getEmailByMcEid($listId, $mcEidCookie);
        $email = $listMember['members'][0]['email_address'];
        return $email;
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
            $this->makeApiOrder()->update($order->getId(), $storeId);
        }
    }

    public function salesruleSaveAfter(Varien_Event_Observer $observer)
    {
        $promoRulesApi = $this->makeApiPromoRule();
        $rule = $observer->getEvent()->getRule();
        $ruleId = $rule->getRuleId();
        $promoRulesApi->update($ruleId);

        return $observer;
    }

    public function salesruleDeleteAfter(Varien_Event_Observer $observer)
    {
        $promoRulesApi = $this->makeApiPromoRule();
        $rule = $observer->getEvent()->getRule();
        $ruleId = $rule->getRuleId();
        $promoRulesApi->markAsDeleted($ruleId);

        return $observer;
    }

    public function secondaryCouponsDelete(Varien_Event_Observer $observer)
    {
        $promoCodesApi = $this->makeApiPromoCode();
        $params = $this->getRequest()->getParams();
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

    /**
     * @return string|null
     */
    protected function getRegistry()
    {
        return Mage::registry('sort_column_dir');
    }

    protected function removeRegistry()
    {
        return Mage::unregister('sort_column_dir');
    }

    /**
     * Add success message if subscribing from customer account.
     *
     * @param $helper
     */
    protected function addSuccessIfRequired($helper)
    {
        $request = Mage::app()->getRequest();
        $module = $request->getControllerModule();
        $module_controller = $request->getControllerName();
        $module_controller_action = $request->getActionName();
        $fullActionName = $module . '_' . $module_controller . '_' . $module_controller_action;
        if (strstr($fullActionName, 'Mage_Newsletter_manage_save')) {
            Mage::getSingleton('customer/session')->addSuccess($helper->__('Confirmation request has been sent.'));
        }
    }

    /**
     * @param $subscriber
     * @throws Mage_Core_Model_Store_Exception
     */
    protected function createEmailCookie($subscriber)
    {
        if (!$this->isCustomerLoggedIn() && !Mage::app()->getStore()->isAdmin()) {
            Mage::getModel('core/cookie')->set(
                'email', $subscriber->getSubscriberEmail(), null, null, null, null, false
            );
        }
    }

    public function addCustomerTab(Varien_Event_Observer $observer)
    {
        $block = $observer->getEvent()->getBlock();
        $helper = $this->makeHelper();
        // add tab in customer edit page
        if ($block instanceof Mage_Adminhtml_Block_Customer_Edit_Tabs) {
            $customerId = (int)$this->getRequest()->getParam('id');
            $customer = Mage::getModel('customer/customer')->load($customerId);
            $storeId = $customer->getStoreId();
            //If the customer was created in the admin panel use the store view selected for MailChimp.
            if (!$storeId) {
                $storeId = $customer->getMailchimpStoreView();
            }
            if ($helper->getLocalInterestCategories($storeId) && ($this->getRequest()->getActionName() == 'edit' || $this->getRequest()->getParam('type'))) {
                $block->addTab('mailchimp', array(
                    'label' => $helper->__('MailChimp'),
                    'url' => $block->getUrl('adminhtml/mailchimp/index', array('_current' => true)),
                    'class' => 'ajax'
                ));

            }
        }
        return $observer;
    }

    protected function getRequest()
    {
        return Mage::app()->getRequest();
    }

    /**
     * Handle frontend customer interest groups only if is not subscribed and all admin customer groups.
     *
     * @param $subscriberEmail
     * @param $params
     * @param $storeId
     * @param null $customerId
     * @return Mage_Newsletter_Model_Subscriber
     * @throws Mage_Core_Model_Store_Exception
     */
    public function handleCustomerGroups($subscriberEmail, $params, $storeId, $customerId = null)
    {
        $helper = $this->makeHelper();
        $subscriberModel = $this->getSubscriberModel();
        $subscriber = $subscriberModel->loadByEmail($subscriberEmail);
        if ($subscriber->getId()) {
            $helper->saveInterestGroupData($params, $storeId, $customerId, $subscriber);
        } elseif (isset($params['customer_id'])) {
            $groups = $helper->getInterestGroupsIfAvailable($params);
            if ($groups) {
                $helper->saveInterestGroupData($params, $storeId, $customerId);
                $this->getWarningMessageAdminHtmlSession($helper);
            }
        } else {
            //save frontend groupdata when customer is not subscribed.
            $helper->saveInterestGroupData($params, $storeId, $customerId);
        }
        return $subscriber;
    }

    /**
     * @return mixed
     */
    protected function getEmailCookie()
    {
        $emailCookie = Mage::getModel('core/cookie')->get('email');
        return $emailCookie;
    }

    /**
     * @return mixed
     */
    protected function getMcEidCookie()
    {
        $mcEidCookie = Mage::getModel('core/cookie')->get('mailchimp_email_id');
        return $mcEidCookie;
    }

    /**
     * @return mixed
     */
    protected function isCustomerLoggedIn()
    {
        return Mage::getSingleton('customer/session')->isLoggedIn();
    }

    /**
     * @return string
     */
    protected function getRequestActionName()
    {
        return $this->getRequest()->getActionName();
    }

    /**
     * @param $helper
     * @return mixed
     */
    protected function getWarningMessageAdminHtmlSession($helper)
    {
        return Mage::getSingleton('adminhtml/session')->addWarning($helper->__('The customer must be subscribed for this change to apply.'));
    }
}
