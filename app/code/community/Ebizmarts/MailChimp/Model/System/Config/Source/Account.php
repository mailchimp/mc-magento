<?php

/**
 * MailChimp For Magento
 *
 * @category Ebizmarts_MailChimp
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 4/29/16 3:55 PM
 * @file: Account.php
 */
class Ebizmarts_MailChimp_Model_System_Config_Source_Account
{

    /**
     * Account details storage
     *
     * @access protected
     * @var bool|array
     */
    protected $_accountDetails = FALSE;

    /**
     * Set AccountDetails on class property if not already set
     */
    public function __construct()
    {
        $scopeArray = explode('-', Mage::helper('mailchimp')->getScopeString());

        $mcStoreId = (Mage::helper('mailchimp')->getMCStoreId($scopeArray[1], $scopeArray[0])) ? Mage::helper('mailchimp')->getMCStoreId($scopeArray[1], $scopeArray[0]) : null;
        $listId = Mage::helper('mailchimp')->getGeneralList($scopeArray[1], $scopeArray[0]);
        $api = Mage::helper('mailchimp')->getApi($scopeArray[1], $scopeArray[0]);
        if ($api) {
            try {
                $this->_accountDetails = $api->root->info('account_name,total_subscribers');
                if ($mcStoreId && Mage::helper('mailchimp')->getIfMCStoreIdExistsForScope($scopeArray[1], $scopeArray[0])) {
                    try {
                        $storeName = $api->ecommerce->stores->get($mcStoreId, 'name');
                        $this->_accountDetails['store_exists'] = true;
                        $this->_accountDetails['store_name'] = $storeName['name'];
                        $totalCustomers = $api->ecommerce->customers->getAll($mcStoreId, 'total_items');
                        $this->_accountDetails['total_customers'] = $totalCustomers['total_items'];
                        $totalProducts = $api->ecommerce->products->getAll($mcStoreId, 'total_items');
                        $this->_accountDetails['total_products'] = $totalProducts['total_items'];
                        $totalOrders = $api->ecommerce->orders->getAll($mcStoreId, 'total_items');
                        $this->_accountDetails['total_orders'] = $totalOrders['total_items'];
                        $totalCarts = $api->ecommerce->carts->getAll($mcStoreId, 'total_items');
                        $this->_accountDetails['total_carts'] = $totalCarts['total_items'];
                    } catch (Mailchimp_Error $e) {
                        Mage::helper('mailchimp')->deleteLocalMCStoreData($scopeArray[1], $scopeArray[0]);
                        if ($listId) {
                            Mage::helper('mailchimp')->createStore($listId, $scopeArray[1], $scopeArray[0]);
                            $message = Mage::helper('mailchimp')->__('Looks like your MailChimp store was deleted. A new one has been created.');
                            Mage::getSingleton('adminhtml/session')->addWarning($message);
                        }

                        $this->_accountDetails['store_exists'] = false;
                    }
                } else {
                    $this->_accountDetails['store_exists'] = false;
                }
            } catch (Exception $e) {
                $this->_accountDetails = "--- Invalid API Key ---";
                Mage::helper('mailchimp')->logError($e->getMessage(), $scopeArray[1]);
            }
        }
    }

    /**
     * Return data if API key is entered
     *
     * @return array
     */
    public function toOptionArray()
    {
        $scopeArray = explode('-', Mage::helper('mailchimp')->getScopeString());
        if (is_array($this->_accountDetails)) {
            $totalSubscribersText = Mage::helper('mailchimp')->__('Total subscribers:');
            $totalSubscribers = $totalSubscribersText . ' ' . $this->_accountDetails['total_subscribers'];
            $username = Mage::helper('mailchimp')->__('Username:') . ' ' . $this->_accountDetails['account_name'];
            $returnArray = array(
                array('value' => 0, 'label' => $username),
                array('value' => 1, 'label' => $totalSubscribers)
            );
            if ($this->_accountDetails['store_exists']) {
                $totalCustomersText = Mage::helper('mailchimp')->__('  Total Customers:');
                $totalCustomers = $totalCustomersText . ' ' . $this->_accountDetails['total_customers'];
                $totalProductsText = Mage::helper('mailchimp')->__('  Total Products:');
                $totalProducts = $totalProductsText . ' ' . $this->_accountDetails['total_products'];
                $totalOrdersText = Mage::helper('mailchimp')->__('  Total Orders:');
                $totalOrders = $totalOrdersText . ' ' . $this->_accountDetails['total_orders'];
                $totalCartsText = Mage::helper('mailchimp')->__('  Total Carts:');
                $totalCarts = $totalCartsText . ' ' . $this->_accountDetails['total_carts'];
                $title = Mage::helper('mailchimp')->__('Ecommerce Data uploaded to MailChimp store ' . $this->_accountDetails['store_name'] . ':');
                $returnArray = array_merge(
                    $returnArray,
                    array(
                        array('value' => 2, 'label' => $title),
                        array('value' => 3, 'label' => $totalCustomers),
                        array('value' => 4, 'label' => $totalProducts),
                        array('value' => 5, 'label' => $totalOrders),
                        array('value' => 6, 'label' => $totalCarts)
                    )
                );
            } elseif (Mage::helper('mailchimp')->isEcomSyncDataEnabled($scopeArray[1], $scopeArray[0], true)) {
                $noStoreText = Mage::helper('mailchimp')->__('No MailChimp store was created for this scope, parent scopes might be sending data for this store anyways.');
                $newStoreText = Mage::helper('mailchimp')->__('You can create a new MailChimp store for this scope by configuring a new list for this scope.');
                $returnArray = array_merge(
                    $returnArray,
                    array(
                        array('value' => 7, 'label' => $noStoreText),
                        array('value' => 8, 'label' => $newStoreText)
                    )
                );
            }

            return $returnArray;
        } elseif (!$this->_accountDetails) {
            return array(array('value' => '', 'label' => Mage::helper('mailchimp')->__('--- Enter your API KEY first ---')));
        } else {
            return array(array('value' => '', 'label' => Mage::helper('mailchimp')->__($this->_accountDetails)));
        }
    }

}
