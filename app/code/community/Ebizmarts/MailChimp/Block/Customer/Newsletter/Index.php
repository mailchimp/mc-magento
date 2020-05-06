<?php

/**
 * Customer account subscribe interest groups block renderer
 *
 * @category Ebizmarts
 * @package  Ebizmarts_MageMonkey
 * @author   Ebizmarts Team <info@ebizmarts.com>
 * @license  http://opensource.org/licenses/osl-3.0.php
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
    protected $_helper;
    protected $_storeId;

    public function __construct()
    {
        $this->setTemplate('ebizmarts/mailchimp/customer/newsletter/index.phtml');
        $this->_helper = Mage::helper('mailchimp');
        $this->_storeId = Mage::app()->getStore()->getId();
    }

    /**
     * @return array|null
     * @throws Mage_Core_Exception
     * @throws Mage_Core_Model_Store_Exception
     * @throws MailChimp_Error
     */
    public function getInterest()
    {
        $subscriber = $this->getSubscriberModel();
        $subscriber->loadByEmail($this->_getEmail());
        $helper = $this->getMailChimpHelper();
        $customerSession = $this->getCustomerSession();
        if (!$helper->isAdmin() && $customerSession->isLoggedIn()) {
            $customer = $customerSession->getCustomer();
            $customerId = $customer->getId();
            $storeId = ($subscriber->getStoreId()) ? $subscriber->getStoreId() : $customer->getStoreId();
        } else {
            $customerId = null;
            $storeId = $subscriber->getStoreId();
        }

        $interest = $helper->getInterestGroups($customerId, $subscriber->getSubscriberId(), $storeId);
        return $interest;
    }

    /**
     * @param $data
     * @return string
     */
    public function escapeQuote($data)
    {
        return $this->getMailChimpHelper()->mcEscapeQuote($data);
    }

    /**
     * @return Ebizmarts_MailChimp_Helper_Data
     */
    public function getMailChimpHelper()
    {
        return $this->_helper;
    }

    /**
     * Retrieve email from Customer object in session
     *
     * @return string Email address
     */
    protected function _getEmail()
    {
        return $this->getCustomerSession()->getCustomer()->getEmail();
    }

    /**
     * @return Mage_Customer_Model_Session
     */
    protected function getCustomerSession()
    {
        return Mage::getSingleton('customer/session');
    }

    /**
     * @return Mage_Newsletter_Model_Subscriber
     */
    protected function getSubscriberModel()
    {
        return Mage::getModel('newsletter/subscriber');
    }
}
