<?php

/**
 * Checkout subscribe checkbox block renderer
 *
 * @category   Ebizmarts
 * @package    Ebizmarts_MageMonkey
 * @author     Ebizmarts Team <info@ebizmarts.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 */
class Ebizmarts_MailChimp_Block_Checkout_Success_Groups extends Mage_Core_Block_Template
{

    protected $_lists = array();
    protected $_info = array();
    protected $_myLists = array();
    protected $_generalList = array();
    protected $_form;
    protected $_api;
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

    /**
     * Get list data from MC
     *
     * @return array
     */
    public function getGeneralList()
    {
        $storeId = $this->storeId;
        $helper = $this->helper;
        $listId = $helper->getGeneralList($storeId);

        return $listId;
    }

    public function getListInterestGroups()
    {
        $storeId = $this->storeId;
        $helper = $this->helper;
        $return = $helper->getListInterestGroups($storeId);
        return $return;
    }

    public function getCategoryTitle($category)
    {
        return $category['title'];
    }

    public function renderGroups($category)
    {
        $object = $this->createObject($category);

        $this->addGroupOptions($category, $object);

        $html = $this->getElementHtml($category, $object);

        return $html;
    }

    public function getGroupClass($type)
    {
        switch ($type) {
            case 'radio':
                $class = 'Varien_Data_Form_Element_Radios';
                break;
            case 'checkboxes':
                $class = 'Varien_Data_Form_Element_Checkboxes';
                break;
            case 'dropdown':
                $class = 'Varien_Data_Form_Element_Select';
                break;
            default:
                $class = 'Varien_Data_Form_Element_Text';
                break;
        }

        return $class;
    }

    public function htmlGroupName($category)
    {
        $storeId = $this->storeId;
        $helper = $this->helper;
        $listId = $helper->getGeneralList($storeId);
        $htmlName = "list[{$listId}]";
        $htmlName .= "[{$category['id']}]";

        if ($category['type'] == 'checkboxes') {
            $htmlName .= '[]';
        }

        return $htmlName;
    }

    /**
     * Form getter/instantiation
     *
     * @return Varien_Data_Form
     */
    public function getForm()
    {
        if ($this->_form instanceof Varien_Data_Form) {
            return $this->_form;
        }
        $form = new Varien_Data_Form();
        return $form;
    }

    /**
     * @param $category
     * @param $object
     */
    protected function addGroupOptions($category, $object)
    {
        $type = $category['type'];

        if ($type == 'checkboxes' || $type == 'dropdown') {
            $options = $this->createOptionArray($category);

            if (isset($category['groups'])) {
                foreach ($category['groups'] as $key => $group) {
                    $options[$key] = $group;
                }

                $object->setValues($options);
            }
        }
    }

    /**
     * @param $category
     * @return mixed
     */
    protected function createOptionArray($category)
    {
        $options = array();
        $type = $category['type'];

        if ($type == 'dropdown') {
            $options[''] = '-- Select Group --';
        }
        return $options;
    }

    /**
     * @param $category
     * @param $html
     * @return string
     */
    protected function addGroupContainer($category, $html)
    {
        $type = $category['type'];
        if ($type != 'checkboxes') {
            $html = "<div class=\"groups-list\">{$html}</div>";
        }
        return $html;
    }

    /**
     * @param $category
     * @return mixed
     */
    protected function createObject($category)
    {
        $type = $category['type'];
        $class = $this->getGroupClass($type);
        $object = new $class;
        $object->setForm($this->getForm());
        $object->setName($this->htmlGroupName($category));
        $object->setHtmlId('interest-group');

        return $object;
    }

    /**
     * @param $category
     * @param $object
     * @return string
     */
    protected function getElementHtml($category, $object)
    {
        $html = $object->getElementHtml();
        $html = $this->addGroupContainer($category, $html);

        return $html;
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
        $subscriber = Mage::getModel('newsletter/subscriber');
        $order = Mage::getSingleton('checkout/session')->getLastRealOrder();
        $subscriber->loadByEmail($order->getCustomerEmail());
        $interest = $this->helper->getSubscriberInterest($subscriber->getSubscriberId(),$order->getStoreId());
        return $interest;
    }

    public function getMessageBefore()
    {
        $storeId = $this->storeId;
        return $this->helper->getCheckoutSuccessHtmlBefore($storeId);
    }

    public function getMessageAfter()
    {
        $storeId = $this->storeId;
        return $this->helper->getCheckoutSuccessHtmlAfter($storeId);
    }

}
