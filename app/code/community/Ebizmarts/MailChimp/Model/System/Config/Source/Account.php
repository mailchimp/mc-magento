<?php

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
//        if (!$this->_account_details) {
//            $this->_account_details = Mage::getSingleton('mailchimp/api')
//                ->getAccountDetails();
//        }
        $this->_account_details = array('username' => 'Username', 'plan_type' => 'plan_type', 'is_trial' => 'is_trial');
    }

    /**
     * Return data if API key is entered
     *
     * @return array
     */
    public function toOptionArray()
    {
        if (is_array($this->_account_details)) {
            return array(
                array('value' => 0, 'label' => Mage::helper('mailchimp')->__('Username:') . ' ' . $this->_account_details['username']),
                array('value' => 1, 'label' => Mage::helper('mailchimp')->__('Plan type:') . ' ' . $this->_account_details['plan_type']),
                array('value' => 2, 'label' => Mage::helper('mailchimp')->__('Is in trial mode?:') . ' ' . ($this->_account_details['is_trial'] ? Mage::helper('mailchimp')->__('Yes') : Mage::helper('mailchimp')->__('No')))
            );
        } else {
            return array(array('value' => '', 'label' => Mage::helper('mailchimp')->__('--- Enter your API KEY first ---')));
        }
    }

}
