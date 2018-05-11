<?php

/**
 * Checkout subscribe checkbox block renderer
 *
 * @category   Ebizmarts
 * @package    Ebizmarts_MageMonkey
 * @author     Ebizmarts Team <info@ebizmarts.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 */
class Ebizmarts_MailChimp_Block_Customer_Newsletter_Index extends Mage_Customer_Block_Newsletter
{

    protected $_lists = array();
    protected $_info = array();
    protected $_myLists = array();
    protected $_generalList = array();
    protected $_form;
    protected $_api;
    protected $_template = "ebizmarts/mailchimp/customer/newsletter/index.phtml";
    /**
     * @var Ebizmarts_MailChimp_Helper_Data
     */
    protected $helper;
    protected $storeId;

    public function __construct()
    {
        $this->setTemplate('ebizmarts/mailchimp/customer/newsletter/index.phtml');
        $this->helper = Mage::helper('mailchimp');
        $this->storeId = Mage::app()->getStore()->getId();
    }

    public function getInterest()
    {
        $subscriber = Mage::getModel('newsletter/subscriber');
        $subscriber->loadByEmail($this->_getEmail());
        $interest = $this->helper->getSubscriberInterest($subscriber->getSubscriberId(), $subscriber->getStoreId());
        return $interest;
    }

    /**
     * Retrieve email from Customer object in session
     *
     * @return string Email address
     */
    protected function _getEmail()
    {
        return $this->helper('customer')->getCustomer()->getEmail();
    }

}
