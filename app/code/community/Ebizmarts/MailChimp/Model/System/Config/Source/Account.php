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
     *
     * @return void
     */
    public function __construct()
    {
        $configValue = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_APIKEY);
        $mcStoreId = (Mage::helper('mailchimp')->getMCStoreId()) ? Mage::helper('mailchimp')->getMCStoreId() : null;
        $api = null;
        if ($configValue) {
            $api = new Ebizmarts_Mailchimp($configValue, null, 'Mailchimp4Magento'.(string)Mage::getConfig()->getNode('modules/Ebizmarts_MailChimp/version'));
        }
        if ($api) {
            try {
                $this->_accountDetails = $api->root->info('account_name,total_subscribers');
                if (Mage::helper('mailchimp')->getMCStoreId()) {
                    $this->_accountDetails['store_exists'] = true;
                    $totalCustomers = $api->ecommerce->customers->getAll($mcStoreId, 'total_items');
                    $this->_accountDetails['total_customers'] = $totalCustomers['total_items'];
                    $totalProducts = $api->ecommerce->products->getAll($mcStoreId, 'total_items');
                    $this->_accountDetails['total_products'] = $totalProducts['total_items'];
                    $totalOrders = $api->ecommerce->orders->getAll($mcStoreId, 'total_items');
                    $this->_accountDetails['total_orders'] = $totalOrders['total_items'];
                    $totalCarts = $api->ecommerce->carts->getAll($mcStoreId, 'total_items');
                    $this->_accountDetails['total_carts'] = $totalCarts['total_items'];
                } else {
                    $this->_accountDetails['store_exists'] = false;
                }
            } catch (Exception $e) {
                $this->_accountDetails = "--- Invalid API Key ---";
                Mage::helper('mailchimp')->logError($e->getMessage());
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
        $helper = Mage::helper('mailchimp');
        if (is_array($this->_accountDetails)) {
            $username = Mage::helper('mailchimp')->__('Username:') . ' ' . $this->_accountDetails['account_name'];
            $title = Mage::helper('mailchimp')->__('Data uploaded to MailChimp:');
            $totalSubscribersText = Mage::helper('mailchimp')->__('  Total Subscribers:');
            $totalSubscribers = $totalSubscribersText . ' ' . $this->_accountDetails['total_subscribers'];
            $returnArray = array(
                array('value' => 0, 'label' => $username),
                array('value' => 1, 'label' => $title),
                array('value' => 2, 'label' => $totalSubscribers)
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
                $returnArray = array_merge(
                    $returnArray,
                    array(
                    array('value' => 3, 'label' => $totalCustomers),
                    array('value' => 4, 'label' => $totalProducts),
                    array('value' => 5, 'label' => $totalOrders),
                    array('value' => 6, 'label' => $totalCarts)
                    )
                );
            } elseif (Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_ACTIVE)) {
                $text = 'Warning: The MailChimp store was not created properly, please save your configuration again.';
                $label = Mage::helper('mailchimp')->__($text);
                $returnArray = array_merge($returnArray, array(array('value' => 7, 'label' => $label)));
            }
                return $returnArray;
        } elseif (!$this->_accountDetails) {
            return array(array('value' => '', 'label' => $helper->__('--- Enter your API KEY first ---')));
        } else {
            return array(array('value' => '', 'label' => $helper->__($this->_accountDetails)));
        }
    }

}
