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
    protected $_account_details = FALSE;

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
        if($configValue){
            $api = new Ebizmarts_Mailchimp($configValue);
        }
        if($api) {
            try {
                $this->_account_details = $api->root->info('account_name,total_subscribers');
                if(Mage::helper('mailchimp')->getMCStoreId()) {
                    $this->_account_details['store_exists'] = true;
                    $totalCustomers = $api->ecommerce->customers->getAll($mcStoreId, 'total_items');
                    $this->_account_details['total_customers'] = $totalCustomers['total_items'];
                    $totalProducts = $api->ecommerce->products->getAll($mcStoreId, 'total_items');
                    $this->_account_details['total_products'] = $totalProducts['total_items'];
                    $totalOrders = $api->ecommerce->orders->getAll($mcStoreId, 'total_items');
                    $this->_account_details['total_orders'] = $totalOrders['total_items'];
                }else{
                    $this->_account_details['store_exists'] = false;
                }
            }catch (Exception $e){
                $this->_account_details = "--- Invalid API Key ---";
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
        if (is_array($this->_account_details)) {
            $returnArray = array(
                array('value' => 0, 'label' => Mage::helper('mailchimp')->__('Username:') . ' ' . $this->_account_details['account_name']),
                array('value' => 1, 'label' => Mage::helper('mailchimp')->__('Data uploaded to MailChimp:')),
                array('value' => 2, 'label' => Mage::helper('mailchimp')->__('  Total Subscribers:') . ' ' . $this->_account_details['total_subscribers'])
            );
            if($this->_account_details['store_exists']){
                $returnArray = array_merge($returnArray,
                array(
                    array('value' => 3, 'label' => Mage::helper('mailchimp')->__('  Total Customers:') . ' ' . $this->_account_details['total_customers']),
                    array('value' => 4, 'label' => Mage::helper('mailchimp')->__('  Total Products:') . ' ' . $this->_account_details['total_products']),
                    array('value' => 5, 'label' => Mage::helper('mailchimp')->__('  Total Orders:') . ' ' . $this->_account_details['total_orders'])
                    ));
            }elseif(Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_ACTIVE)){
                $returnArray = array_merge($returnArray, array(array('value' => 6, 'label' => Mage::helper('mailchimp')->__('Warning: The MailChimp store was not created properly, please save your configuration again.'))));
            }
                return $returnArray;
        } elseif(!$this->_account_details) {
            return array(array('value' => '', 'label' => $helper->__('--- Enter your API KEY first ---')));
        }else{
            return array(array('value' => '', 'label' => $helper->__($this->_account_details)));
        }
    }

}
