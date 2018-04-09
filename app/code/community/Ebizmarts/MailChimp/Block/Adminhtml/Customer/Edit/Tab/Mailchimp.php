<?php
/**
 * mc-magento Magento Component
 *
 * @category  Ebizmarts
 * @package   mc-magento
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     6/10/16 12:38 AM
 * @file:     Grid.php
 */
class Ebizmarts_MailChimp_Block_Adminhtml_Customer_Edit_Tab_Mailchimp extends Mage_Adminhtml_Block_Widget_Grid
{

    protected $_lists = array();
    protected $_info = array();
    protected $_myLists = array();
    protected $_generalList = array();
    protected $_form;
    protected $_api;
    protected $_customer;
    /**
     * @var Ebizmarts_MailChimp_Helper_Data
     */
    protected $helper;
    protected $storeId;

    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('ebizmarts/mailchimp/customer/tab/mailchimp.phtml');
        $this->helper = Mage::helper('mailchimp');
        $customerId = (int) $this->getRequest()->getParam('id');
        if ($customerId) {
            $this->_customer = Mage::getModel('customer/customer')->load($customerId);
            $this->storeId = $this->_customer->getStoreId();
        }
    }

    public function getInterest()
    {
        $subscriber = Mage::getModel('newsletter/subscriber');
        $subscriber->loadByEmail($this->_customer->getEmail());
        $interest = $this->helper->getSubscriberInterest($subscriber->getSubscriberId(), $this->storeId);
        return $interest;
    }
}
