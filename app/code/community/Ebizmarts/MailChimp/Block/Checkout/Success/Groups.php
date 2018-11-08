<?php

/**
 * Checkout subscribe checkbox block renderer
 *
 * @category   Ebizmarts
 * @package    Ebizmarts_MailChimp
 * @author     Ebizmarts Team <info@ebizmarts.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 */
class Ebizmarts_MailChimp_Block_Checkout_Success_Groups extends Mage_Core_Block_Template
{
    protected $_currentIntesrest;
    /**
     * @var Ebizmarts_MailChimp_Helper_Data
     */
    protected $helper;
    protected $storeId;

    public function __construct()
    {
        parent::__construct();
        $this->helper = Mage::helper('mailchimp');
        $this->storeId = Mage::app()->getStore()->getId();
    }

    public function getFormUrl()
    {
        return $this->getSuccessInterestUrl();
    }

    public function getSuccessInterestUrl()
    {
        $url = 'mailchimp/group/index';
        return Mage::app()->getStore()->getUrl($url);
    }

    public function getInterest()
    {
        $subscriber = $this->getSubscriberModel();
        $order = $this->getSessionLastRealOrder();
        $subscriber->loadByEmail($order->getCustomerEmail());
        $subscriberId = $subscriber->getSubscriberId();
        $customerId = $order->getCustomerId();
        $helper = $this->getMailChimpHelper();
        $interest = $helper->getInterestGroups($customerId, $subscriberId, $order->getStoreId());
        return $interest;
    }

    public function getMessageBefore()
    {
        $storeId = $this->storeId;
        return $this->getMailChimpHelper()->getCheckoutSuccessHtmlBefore($storeId);
    }

    public function getMessageAfter()
    {
        $storeId = $this->storeId;
        return $this->getMailChimpHelper()->getCheckoutSuccessHtmlAfter($storeId);
    }

    /**
     * @return false|Mage_Core_Model_Abstract
     */
    protected function getSubscriberModel()
    {
        return Mage::getModel('newsletter/subscriber');
    }

    /**
     * @return mixed
     */
    protected function getSessionLastRealOrder()
    {
        return Mage::getSingleton('checkout/session')->getLastRealOrder();
    }

    /**
     * @return Ebizmarts_MailChimp_Helper_Data|Mage_Core_Helper_Abstract
     */
    protected function getMailChimpHelper()
    {
        return $this->helper;
    }

    /**
     * @return mixed
     */
    protected function getCurrentInterest()
    {
        return $this->_currentIntesrest;
    }

    protected function setCurrentInterest($interest)
    {
        $this->_currentIntesrest = $interest;
    }
}