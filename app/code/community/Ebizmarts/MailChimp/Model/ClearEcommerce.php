<?php

/**
 * MailChimp For Magento
 *
 * @category  Ebizmarts_MailChimp
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     7/24/19 2:47 PM
 * @file:     ClearEcommerce.php
 */
class Ebizmarts_MailChimp_Model_ClearEcommerce
{
    /**
     * @var Ebizmarts_MailChimp_Helper_Data
     */
    protected $_helper;

    /**
     * @var Ebizmarts_MailChimp_Helper_Date
     */
    protected $_dateHelper;

    public function __construct()
    {
        $this->_helper = Mage::helper('mailchimp');
        $this->_dateHelper = Mage::helper('mailchimp/date');
    }

    /**
     * @return Ebizmarts_MailChimp_Helper_Data|Mage_Core_Helper_Abstract
     */
    protected function getHelper()
    {
        return $this->_helper;
    }

    /**
     * @return Ebizmarts_MailChimp_Helper_Date|Mage_Core_Helper_Abstract
     */
    protected function getDateHelper()
    {
        return $this->_dateHelper;
    }

    /**
     * Process all types of data from eCommerce data to delete
     * non active products, quotes, customers, etc. from the table.
     */
    public function cleanEcommerceData()
    {
        $this->processData($this->getData('QUO'), 'QUO');
        $this->processData($this->getData('PRO'), 'PRO');
        $this->processData($this->getData('CUS'), 'CUS');
        $this->processData($this->getData('PRL'), 'PRL');
        $this->processData($this->getData('PCD'), 'PCD');
    }

    /**
     * @param $data
     * @param $type
     */
    protected function processData($data, $type)
    {
        $ids = array();
        foreach ($data as $item) {
            $ids []= $item->getId();
        }

        $reverseIds = $this->processReverseData($type);
        $ids = array_merge($ids, $reverseIds);

        if (!empty($ids)) {
            $this->deleteEcommerceRows($ids, $type);
        }
    }

    protected function processReverseData($type)
    {
        $ids = array();
        $eData = $this->getEcommerceRows($type);

        foreach ($eData as $eItem) {
            $ids []= $eItem['related_id'];
        }

        return $ids;
    }

    /**
     * @param $type
     * @param bool $filter
     * @return array
     * @throws Mage_Core_Model_Store_Exception
     */
    protected function getData($type, $filter = true)
    {
        $items = array();
        switch ($type) {
            case 'PRO':
                $items = $this->getPROItems($filter);
                break;
            case 'QUO':
                $items = $this->getQUOItems($filter);
                break;
            case 'CUS':
                $items = $this->getCUSItems($filter);
                break;
            case 'PRL':
                $items = $this->getPRLItems($filter);
                break;
            case 'PCD':
                $items = $this->getPCDItems($filter);
                break;
        }

        return $items;
    }

    protected function getPROItems($filter)
    {
        $collection = Mage::getModel('catalog/product')->getCollection();
        if ($filter) {
            $collection->addFieldToFilter('status', array('eq' => 2));
        }

        return $collection->getItems();
    }

    protected function getQUOItems($filter)
    {
        $collection = Mage::getModel('sales/quote')->getCollection();
        if ($filter) {
            $collection->addFieldToFilter('is_active', array('eq' => 0));
        }

        return $collection->getItems();
    }

    protected function getCUSItems($filter)
    {
        $items = array();
        $collection = Mage::getModel('customer/customer')->getCollection();
        if ($filter) {
            $customers = $collection->getItems();
            foreach ($customers as $item) {
                if ($item->getIsActive() == 0) {
                    $items [] = $item;
                }
            }
        }

        return $items;
    }

    protected function getPRLItems($filter)
    {
        $collection = Mage::getModel('salesrule/rule')->getCollection();
        if ($filter) {
            $collection->addFieldToFilter('is_active', array('eq' => 0));
        }

        return $collection->getItems();
    }

    protected function getPCDItems($filter)
    {
        $collection = Mage::getModel('salesrule/coupon')->getCollection();
        if ($filter) {
            $date = $this->getDateHelper()->formatDate(null, 'YYYY-mm-dd H:i:s');
            $collection->addFieldToFilter('expiration_date', array('lteq' => $date));
        }

        return $collection->getItems();
    }

    /**
     * @param $type
     * @return array
     */
    protected function getEcommerceRows($type)
    {
        $ecommerceData = Mage::getModel('mailchimp/ecommercesyncdata')
            ->getCollection()
            ->addFieldToSelect('related_id');
        $ecommerceData->addFieldToFilter('type', array('eq' => $type));
        $ecommerceData->addFieldToFilter('product.entity_id', array('null' => true));
        $ecommerceData->getSelect()->joinLeft(
            array('product' => 'catalog_product_entity'),
            'main_table.related_id = product.entity_id'
        );

        return $ecommerceData->getData();
    }

    /**
     * @param $ids
     * @param $type
     */
    protected function deleteEcommerceRows($ids, $type)
    {
        $ids = implode($ids, ', ');
        $where = array(
            "related_id IN ($ids)",
            "type = '$type'"
        );

        $helper = $this->getHelper();
        $resource = $helper->getCoreResource();
        $connection = $resource->getConnection('core_write');
        $tableName = $resource->getTableName('mailchimp/ecommercesyncdata');
        $connection->delete($tableName, $where);
    }
}

