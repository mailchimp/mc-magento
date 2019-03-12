<?php

/**
 * mc-magento Magento Component
 *
 * @category  Ebizmarts
 * @package   mc-magento
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     5/27/16 1:50 PM
 * @file:     EcommerceController.php
 */
class Ebizmarts_MailChimp_Adminhtml_MailchimpController extends Mage_Adminhtml_Controller_Action
{
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

    public function indexAction()
    {
        $customerId = (int)$this->getRequest()->getParam('id');
        if ($customerId) {
            $block = $this->getLayout()
                ->createBlock('mailchimp/adminhtml_customer_edit_tab_mailchimp', 'admin.customer.mailchimp')
                ->setCustomerId($customerId)
                ->setUseAjax(true);
            $html = $this->getHtml($block);
            $this->getResponse()->setBody($html);
        }
    }

    public function resendSubscribersAction()
    {
        $helper = $this->makeHelper();
        $mageApp = $helper->getMageApp();
        $request = $mageApp->getRequest();
        $scope = $request->getParam('scope');
        $scopeId = $request->getParam('scope_id');
        $success = 1;

        try {
            $helper->resendSubscribers($scopeId, $scope);
        } catch (Exception $e) {
            $success = 0;
        }

        $mageApp->getResponse()->setBody($success);
    }

    public function createWebhookAction()
    {
        $helper = $this->makeHelper();
        $mageApp = $helper->getMageApp();
        $request = $mageApp->getRequest();
        $scope = $request->getParam('scope');
        $scopeId = $request->getParam('scope_id');
        $listId = $helper->getGeneralList($scopeId);

        $message = $helper->createNewWebhook($scopeId, $scope, $listId);

        $mageApp->getResponse()->setBody($message);
    }

    public function getStoresAction()
    {
        $apiKey = $this->getRequest()->getParam('apikey');
        $helper = $this->makeHelper();
        $data = array();

        try {
            $api = $helper->getApiByKey($apiKey);
            $stores = $api->getEcommerce()->getStores()->get(null, null, null, 100);
            $data[] = array('id' => '', 'name' => '--- Select a MailChimp Store ---');
            foreach ($stores['stores'] as $store) {
                if ($store['platform'] == 'Magento') {
                    if ($store['list_id'] == '') {
                        continue;
                    }
                    if (isset($store['connected_site'])) {
                        $label = $store['name'];
                    } else {
                        $label = $store['name'] . ' (Warning: not connected)';
                    }

                    $data[] = array('id' => $store['id'], 'name' => $label);
                }
            }

        } catch (Ebizmarts_MailChimp_Helper_Data_ApiKeyException $e) {
            $data = array('error' => 1, 'message' => $e->getMessage());
            $helper->logError($e->getMessage());
        } catch (MailChimp_Error $e) {
            $data = array('error' => 1, 'message' => $e->getFriendlyMessage());
            $helper->logError($e->getFriendlyMessage());
        } catch (Exception $e) {
            $data = array('error' => 1, 'message' => $e->getMessage());
            $helper->logError($e->getMessage());
        }

        $jsonData = json_encode($data);
        $response = $this->getResponse();
        $response->setHeader('Content-type', 'application/json');
        $response->setBody($jsonData);
    }

    public function getListAction()
    {
        $request = $this->getRequest();
        $apiKey = $request->getParam('apikey');
        $storeId = $request->getParam('storeid');
        $helper = $this->makeHelper();

        try {
            $api = $helper->getApiByKey($apiKey);
            $store = $api->getEcommerce()->getStores()->get($storeId);
            $listId = $store['list_id'];
            $list = $api->getLists()->getLists($listId);
            $data = array('id' => $list['id'], 'name' => $list['name']);
        } catch (Ebizmarts_MailChimp_Helper_Data_ApiKeyException $e) {
            $data = array('error' => 1, 'message' => $e->getMessage());
            $helper->logError($e->getMessage());
        } catch (MailChimp_Error $e) {
            $data = array('error' => 1, 'message' => $e->getFriendlyMessage());
            $helper->logError($e->getFriendlyMessage());
        } catch (Exception $e) {
            $data = array('error' => 1, 'message' => $e->getMessage());
            $helper->logError($e->getMessage());
        }

        $jsonData = json_encode($data);
        $response = $this->getResponse();
        $response->setHeader('Content-type', 'application/json');
        $response->setBody($jsonData);
    }

    public function getInfoAction()
    {
        $request = $this->getRequest();
        $apiKey = $request->getParam('apikey');
        $storeId = $request->getParam('storeid');
        $helper = $this->makeHelper();

        try {
            $api = $helper->getApiByKey($apiKey);
            $data = $api->getRoot()->info('account_name,total_subscribers');

            $apiEcommerce = $api->getEcommerce();
            $storeData = $apiEcommerce->getStores()->get($storeId, 'name,is_syncing,list_id');

            $listData = $api->getLists()->getLists($storeData['list_id'], 'stats');

            $data['list_subscribers'] = $listData['stats']['member_count'];

            $data['store_exists'] = true;
            $data['store_name'] = $storeData['name'];
            //Keep both values for backward compatibility
            $data['store_sync_flag'] = $storeData['is_syncing'];
            $data['store_sync_date'] = $this->_getDateSync($storeId);
            $totalCustomers = $apiEcommerce->getCustomers()->getAll($storeId, 'total_items');
            $data['total_customers'] = $totalCustomers['total_items'];
            $totalProducts = $apiEcommerce->getProducts()->getAll($storeId, 'total_items');
            $data['total_products'] = $totalProducts['total_items'];
            $totalOrders = $apiEcommerce->getOrders()->getAll($storeId, 'total_items');
            $data['total_orders'] = $totalOrders['total_items'];
            $totalCarts = $apiEcommerce->getCarts()->getAll($storeId, 'total_items');
            $data['total_carts'] = $totalCarts['total_items'];

        } catch (Ebizmarts_MailChimp_Helper_Data_ApiKeyException $e) {
            $data = array('error' => 1, 'message' => $e->getMessage());
            $helper->logError($e->getMessage());
        } catch (MailChimp_Error $e) {
            $data = array('error' => 1, 'message' => $e->getFriendlyMessage());
            $helper->logError($e->getFriendlyMessage());
        } catch (Exception $e) {
            $data = array('error' => 1, 'message' => $e->getMessage());
            $helper->logError($e->getMessage());
        }

        $jsonData = json_encode($this->_makeValues($data, $helper));
        $response = $this->getResponse();
        $response->setHeader('Content-type', 'application/json');
        $response->setBody($jsonData);

    }

    protected function _makeValues($data, $helper)
    {
        $totalAccountSubscribersText = $helper->__('Total Account Subscribers:');
        $totalAccountSubscribers = $totalAccountSubscribersText . ' ' . $data['total_subscribers'];
        $totalListSubscribers = null;
        if (isset($data['list_subscribers'])) {
            $totalListSubscribersText = $helper->__('Total List Subscribers:');
            $totalListSubscribers = $totalListSubscribersText . ' ' . $data['list_subscribers'];
        }
        $username = $helper->__('Username:') . ' ' . $data['account_name'];
        $returnArray = array(
            array('value' => self::USERNAME_KEY, 'label' => $username),
            array('value' => self::TOTAL_ACCOUNT_SUB_KEY, 'label' => $totalAccountSubscribers)
        );
        if ($totalListSubscribers) {
            $returnArray[] = array('value' => self::TOTAL_LIST_SUB_KEY, 'label' => $totalListSubscribers);
        }
        if ($data['store_exists']) {
            $totalCustomersText = $helper->__('  Total Customers:');
            $totalCustomers = $totalCustomersText . ' ' . $data['total_customers'];
            $totalProductsText = $helper->__('  Total Products:');
            $totalProducts = $totalProductsText . ' ' . $data['total_products'];
            $totalOrdersText = $helper->__('  Total Orders:');
            $totalOrders = $totalOrdersText . ' ' . $data['total_orders'];
            $totalCartsText = $helper->__('  Total Carts:');
            $totalCarts = $totalCartsText . ' ' . $data['total_carts'];
            $title = $helper->__('Ecommerce Data uploaded to MailChimp store ' . $data['store_name'] . ':');
            if ($data['store_sync_flag'] && !$data['store_sync_date']) {
                $syncValue = 'In Progress';
            } else {
                $syncData = $data['store_sync_date'];
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

        }
        return $returnArray;
    }

    protected function _getDateSync($mailchimpStoreId)
    {
        return $this->makeHelper()->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_SYNC_DATE . "_$mailchimpStoreId", 0, 'default');
    }

    protected function _isAllowed()
    {
        $acl = null;
        switch ($this->getRequest()->getActionName()) {
            case 'index':
            case 'resendSubscribers':
            case 'createWebhook':
            case 'getStores':
            case 'getList':
            case 'getInfo':
                $acl = 'system/config/mailchimp';
                break;
        }

        return Mage::getSingleton('admin/session')->isAllowed($acl);
    }

    /**
     * @return Ebizmarts_MailChimp_Helper_Data
     */
    protected function makeHelper()
    {
        return Mage::helper('mailchimp');
    }

    /**
     * @param $block
     * @return mixed
     */
    protected function getHtml($block)
    {
        return $block->toHtml();
    }
}
