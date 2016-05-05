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
        $api = null;
        if($configValue){
            $api = new Ebizmarts_Mailchimp($configValue);
        }
        //TODO pedir solo campos necesarios
        if($api) {
            try {
                $this->_account_details = $api->root->info('account_name,pro_enabled,total_subscribers');
            }catch (Exception $e){
                $this->_account_details = "--- Invalid API Key ---";
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
            return array(
                array('value' => 0, 'label' => Mage::helper('mailchimp')->__('Username:') . ' ' . $this->_account_details['account_name']),
                array('value' => 1, 'label' => Mage::helper('mailchimp')->__('Plan type:') . ' ' . ($this->_account_details['pro_enabled'] ? 'Pro' : 'Free')),
                array('value' => 2, 'label' => Mage::helper('mailchimp')->__('Total Subscribers:') . ' ' . $this->_account_details['total_subscribers']),
            );
        } elseif(!$this->_account_details) {
            return array(array('value' => '', 'label' => $helper->__('--- Enter your API KEY first ---')));
        }else{
            return array(array('value' => '', 'label' => $helper->__($this->_account_details)));
        }
    }

}
