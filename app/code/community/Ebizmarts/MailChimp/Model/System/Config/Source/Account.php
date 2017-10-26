<?php

/**
 * MailChimp For Magento
 *
 * @category  Ebizmarts_MailChimp
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     4/29/16 3:55 PM
 * @file:     Account.php
 */
class Ebizmarts_MailChimp_Model_System_Config_Source_Account
{

    /**
     * Account details storage
     *
     * @access protected
     * @var    bool|array
     */
    protected $_accountDetails = false;
    protected $helper;
    const USERNAME_KEY = 0;
    const TOTAL_ACCOUNT_SUB_KEY = 1;
    const TOTAL_LIST_SUB_KEY = 2;
    const STORENAME_KEY = 10;
    const SYNC_LABEL_KEY = 11;
    const TOTAL_CUS_KEY = 12;
    const TOTAL_PRO_KEY = 13;
    const TOTAL_ORD_KEY = 14;
    const TOTAL_QUO_KEY = 15;
    const NO_STORE_TEXT_KEY = 20;
    const NEW_STORE_TEXT_KEY = 21;
    const STORE_MIGRATION_TEXT_KEY = 30;

    /**
     * Set AccountDetails on class property if not already set
     */
    public function __construct()
    {
        $helper = $this->helper = $this->getHelper();
        $scopeArray = explode('-', $helper->getScopeString());

        $mcStoreId = ($helper->getMCStoreId($scopeArray[1], $scopeArray[0])) ? $helper->getMCStoreId($scopeArray[1], $scopeArray[0]) : null;
        $listId = $helper->getGeneralList($scopeArray[1], $scopeArray[0]);
        $api = $helper->getApi($scopeArray[1], $scopeArray[0]);
        if ($api) {
            try {
                $this->_accountDetails = $api->root->info('account_name,total_subscribers');
                if ($listId) {
                    $listData = $api->lists->getLists($listId, 'stats');
                    $this->_accountDetails['list_subscribers'] = $listData['stats']['member_count'];
                }
                if ($mcStoreId && $helper->getIfConfigExistsForScope(Ebizmarts_MailChimp_Model_Config::GENERAL_MCSTOREID, $scopeArray[1], $scopeArray[0])) {
                    try {
                        $storeData = $api->ecommerce->stores->get($mcStoreId, 'name,is_syncing');
                        $this->_accountDetails['store_exists'] = true;
                        $this->_accountDetails['store_name'] = $storeData['name'];
                        //Keep both values for backward compatibility
                        $this->_accountDetails['store_sync_flag'] = $storeData['is_syncing'];
                        $this->_accountDetails['store_sync_date'] = $this->getDateSync($mcStoreId);
                        $totalCustomers = $api->ecommerce->customers->getAll($mcStoreId, 'total_items');
                        $this->_accountDetails['total_customers'] = $totalCustomers['total_items'];
                        $totalProducts = $api->ecommerce->products->getAll($mcStoreId, 'total_items');
                        $this->_accountDetails['total_products'] = $totalProducts['total_items'];
                        $totalOrders = $api->ecommerce->orders->getAll($mcStoreId, 'total_items');
                        $this->_accountDetails['total_orders'] = $totalOrders['total_items'];
                        $totalCarts = $api->ecommerce->carts->getAll($mcStoreId, 'total_items');
                        $this->_accountDetails['total_carts'] = $totalCarts['total_items'];
                    } catch (MailChimp_Error $e) {
                        $helper->deleteLocalMCStoreData($mcStoreId, $scopeArray[1], $scopeArray[0]);
                        if ($listId) {
                            $helper->createStore($listId, $scopeArray[1], $scopeArray[0]);
                            $message = $helper->__('Looks like your MailChimp store was deleted. A new one has been created.');
                            Mage::getSingleton('adminhtml/session')->addWarning($message);
                        }

                        $this->_accountDetails['store_exists'] = false;
                    }
                } else {
                    $this->_accountDetails['store_exists'] = false;
                }
            } catch (Exception $e) {
                $this->_accountDetails = "--- Invalid API Key ---";
                $helper->logError($e->getMessage(), $scopeArray[1]);
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
        $helper = $this->helper;
        $scopeArray = explode('-', $helper->getScopeString());
        if (is_array($this->_accountDetails)) {
            $totalAccountSubscribersText = $helper->__('Total Account Subscribers:');
            $totalAccountSubscribers = $totalAccountSubscribersText . ' ' . $this->_accountDetails['total_subscribers'];
            $totalListSubscribers = null;
            if (isset($this->_accountDetails['list_subscribers'])) {
                $totalListSubscribersText = $helper->__('Total List Subscribers:');
                $totalListSubscribers = $totalListSubscribersText . ' ' . $this->_accountDetails['list_subscribers'];
            }
            $username = $helper->__('Username:') . ' ' . $this->_accountDetails['account_name'];
            $returnArray = array(
                array('value' => self::USERNAME_KEY, 'label' => $username),
                array('value' => self::TOTAL_ACCOUNT_SUB_KEY, 'label' => $totalAccountSubscribers)
            );
            if ($totalListSubscribers) {
                $returnArray[] = array('value' => self::TOTAL_LIST_SUB_KEY, 'label' => $totalListSubscribers);
            }
            if ($this->_accountDetails['store_exists']) {
                $totalCustomersText = $helper->__('  Total Customers:');
                $totalCustomers = $totalCustomersText . ' ' . $this->_accountDetails['total_customers'];
                $totalProductsText = $helper->__('  Total Products:');
                $totalProducts = $totalProductsText . ' ' . $this->_accountDetails['total_products'];
                $totalOrdersText = $helper->__('  Total Orders:');
                $totalOrders = $totalOrdersText . ' ' . $this->_accountDetails['total_orders'];
                $totalCartsText = $helper->__('  Total Carts:');
                $totalCarts = $totalCartsText . ' ' . $this->_accountDetails['total_carts'];
                $title = $helper->__('Ecommerce Data uploaded to MailChimp store ' . $this->_accountDetails['store_name'] . ':');
                if ($this->_accountDetails['store_sync_flag'] && !$this->_accountDetails['store_sync_date'] && !$helper->getResendEnabled($scopeArray[1], $scopeArray[0])) {
                    $syncValue = 'In Progress';
                } else {
                    $syncData = $this->_accountDetails['store_sync_date'];
                    if ($helper->validateDate($syncData)) {
                        $syncValue = $syncData;
                    } else {
                        $syncValue = 'Finished';
                    }
                }
                $syncLabel = $helper->__('Initial sync: ' . $syncValue);
                $returnArray = array_merge(
                    $returnArray,
                    array(
                        array('value' => self::STORENAME_KEY, 'label' => $title),
                        array('value' => self::SYNC_LABEL_KEY, 'label' => $syncLabel),
                        array('value' => self::TOTAL_CUS_KEY, 'label' => $totalCustomers),
                        array('value' => self::TOTAL_PRO_KEY, 'label' => $totalProducts),
                        array('value' => self::TOTAL_ORD_KEY, 'label' => $totalOrders),
                        array('value' => self::TOTAL_QUO_KEY, 'label' => $totalCarts)
                    )
                );
            } elseif ($helper->isEcomSyncDataEnabled($scopeArray[1], $scopeArray[0], true)) {
                $noStoreText = $helper->__('No MailChimp store was created for this scope, parent scopes might be sending data for this store anyways.');
                $newStoreText = $helper->__('You can create a new MailChimp store for this scope by configuring a new list for this scope.');
                $returnArray = array_merge(
                    $returnArray,
                    array(
                        array('value' => self::NO_STORE_TEXT_KEY, 'label' => $noStoreText),
                        array('value' => self::NEW_STORE_TEXT_KEY, 'label' => $newStoreText)
                    )
                );
            }

            if (!$helper->migrationFinished() && $helper->isEcommerceEnabled($scopeArray[1], $scopeArray[0])) {
                $storeMigrationText = $helper->__('The store data is currently being migrated to the new version. This process might take a while depending on the amount of data in Magento.');
                $returnArray = array_merge(
                    $returnArray,
                    array(
                        array('value' => self::STORE_MIGRATION_TEXT_KEY, 'label' => $storeMigrationText)
                    )
                );
            }

            return $returnArray;
        } elseif (!$this->_accountDetails) {
            return array(array('value' => '', 'label' => $helper->__('--- Enter your API KEY first ---')));
        } else {
            return array(array('value' => '', 'label' => $helper->__($this->_accountDetails)));
        }
    }

    protected function getHelper()
    {
        return Mage::helper('mailchimp');
    }

    protected function getDateSync($mailchimpStoreId)
    {
        return $this->getHelper()->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_SYNC_DATE."_$mailchimpStoreId", 0, 'default');
    }

}
