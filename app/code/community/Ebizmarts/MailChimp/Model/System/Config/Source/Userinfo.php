<?php

/**
 * Author : Ebizmarts <info@ebizmarts.com>
 * Date   : 8/6/14
 * Time   : 12:16 AM
 * File   : Userinfo.php
 * Module : Ebizmarts_Mandrill
 */
class Ebizmarts_MailChimp_Model_System_Config_Source_Userinfo
{

    /**
     * Account details storage
     *
     * @access protected
     * @var    bool|array
     */
    protected $_account_details = "--- Enter your API KEY first ---";

    /**
     * Set AccountDetails on class attribute if not already set
     *
     * @return void
     */
    public function __construct()
    {
        $storeId = Mage::app()->getStore()->getId();
        if (Mage::app()->getRequest()->getParam('store')) {
            $stores = Mage::app()->getStores();
            foreach ($stores as $store) {
                if ($store->getCode() == Mage::app()->getRequest()->getParam('store')) {
                    $storeId = $store->getStoreId();
                    break;
                }
            }
        }

        if ((!is_array($this->_account_details) || isset($this->_account_details['status'])) && Mage::getStoreConfig(Ebizmarts_MailChimp_Model_Config::MANDRILL_APIKEY, $storeId)) {
            $api = new Mandrill_Message(Mage::getStoreConfig(Ebizmarts_MailChimp_Model_Config::MANDRILL_APIKEY, $storeId));
            try {
                $this->_account_details = $api->users->info();
            } catch (Exception $e) {
                $this->_account_details = "--- Invalid API key ---";
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
            if (!isset($this->_account_details['status'])) {
                return array(
                    array('value' => 0, 'label' => $helper->__("<strong>Username</strong>: %s %s", $this->_account_details["username"], "<small>used for SMTP authentication</small>")),

                    array('value' => 1, 'label' => $helper->__('<strong>Reputation</strong>: %s %s', $this->_account_details['reputation'], "<small>scale from 0 to 100, with 75 generally being a \"good\" reputation</small>")),

                    array('value' => 2, 'label' => $helper->__('<strong>Hourly Quota</strong>: %s %s', $this->_account_details['hourly_quota'], "<small>the maximum number of emails Mandrill will deliver for this user each hour. Any emails beyond that will be accepted and queued for later delivery. Users with higher reputations will have higher hourly quotas</small>")),

                    array('value' => 3, 'label' => $helper->__('<strong>Backlog</strong>: %s %s', $this->_account_details['backlog'], "<small>the number of emails that are queued for delivery due to exceeding your monthly or hourly quotas</small>"))
                );
            } else {
                return array(array('value' => '', 'label' => $helper->__('--- Invalid API KEY ---')));
            }
        } else {
            return array(array('value' => '', 'label' => $helper->__($this->_account_details)));
        }
    }

}
