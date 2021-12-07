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

    /**
     * @var Ebizmarts_MailChimp_Model_Resource_Ecommercesyncdata_Collection
     */
    protected $_ecommerceData;

    public function __construct()
    {
        $this->_helper = Mage::helper('mailchimp');
        $this->_dateHelper = Mage::helper('mailchimp/date');

        $this->_ecommerceData = Mage::getModel('mailchimp/ecommercesyncdata')
            ->getCollection()
            ->addFieldToSelect('related_id')
            ->setPageSize(100);
    }

    /**
     * @return Ebizmarts_MailChimp_Helper_Data|Mage_Core_Helper_Abstract
     */
    protected function getHelper($type='')
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
    public function clearEcommerceData()
    {
        $itemsPRO = $this->getItemsToDelete(Ebizmarts_MailChimp_Model_Config::IS_PRODUCT);
        $itemsCUS = $this->getItemsToDelete(Ebizmarts_MailChimp_Model_Config::IS_CUSTOMER);
        $itemsQUO = $this->getItemsToDelete(Ebizmarts_MailChimp_Model_Config::IS_QUOTE);
        $itemsPRL = $this->getItemsToDelete(Ebizmarts_MailChimp_Model_Config::IS_PROMO_RULE);
        $itemsPCD = $this->getItemsToDelete(Ebizmarts_MailChimp_Model_Config::IS_PROMO_CODE);

        $this->processData($itemsPRO, Ebizmarts_MailChimp_Model_Config::IS_PRODUCT);
        $this->processData($itemsCUS, Ebizmarts_MailChimp_Model_Config::IS_CUSTOMER);
        $this->processData($itemsQUO, Ebizmarts_MailChimp_Model_Config::IS_QUOTE);
        $this->processData($itemsPRL, Ebizmarts_MailChimp_Model_Config::IS_PROMO_RULE);
        $this->processData($itemsPCD, Ebizmarts_MailChimp_Model_Config::IS_PROMO_CODE);
    }

    /**
     * @param $data
     * @param $type
     */
    public function processData($data, $type)
    {
        $ids = array();
        foreach ($data as $item) {
            $ids [] = $item->getId();
        }

        $reverseIds = $this->processDeletedData($type);
        $ids = array_merge($ids, $reverseIds);

        if (!empty($ids)) {
            $this->deleteEcommerceRows($ids, $type);
        } else {
            $this->clearEcommerceCollection();
        }
    }

    /**
     * @param $type
     * @return array
     */
    public function processDeletedData($type)
    {
        $ids = array();
        $eData = $this->getDeletedRows($type);

        foreach ($eData as $eItem) {
            $ids [] = $eItem['related_id'];
        }

        return $ids;
    }

    /**
     * Get the items from eCommerce data that had been disabled.
     *
     * @param $type
     * @param bool $filter
     * @return array
     * @throws Mage_Core_Model_Store_Exception
     */
    protected function getItemsToDelete($type)
    {
        $items = array();
        switch ($type) {
            case Ebizmarts_MailChimp_Model_Config::IS_PRODUCT:
                $items = $this->getProductItems();
                break;
            case Ebizmarts_MailChimp_Model_Config::IS_QUOTE:
                $items = $this->getQuoteItems();
                break;
            case Ebizmarts_MailChimp_Model_Config::IS_CUSTOMER:
                $items = $this->getCustomerItems();
                break;
            case Ebizmarts_MailChimp_Model_Config::IS_PROMO_RULE:
                $items = $this->getPromoRuleItems();
                break;
            case Ebizmarts_MailChimp_Model_Config::IS_PROMO_CODE:
                $items = $this->getPromoCodeItems();
                break;
        }

        return $items;
    }

    /**
     * @param $filter
     * @return array
     */
    protected function getProductItems()
    {
        $collection = Mage::getModel('catalog/product')
            ->getCollection()
            ->setPageSize(100)
            ->setCurPage(1)
            ->addFieldToFilter('status', array('eq' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED));

        return $collection->getItems();
    }

    /**
     * @param $filter
     * @return array
     */
    protected function getQuoteItems()
    {
        $collection = Mage::getModel('sales/quote')
            ->getCollection()
            ->setPageSize(100)
            ->setCurPage(1)
            ->addFieldToFilter('is_active', array('eq' => 0));

        return $collection->getItems();
    }

    /**
     * @param $filter
     * @return array
     */
    protected function getCustomerItems()
    {
        $items = array();
        $collection = Mage::getModel('customer/customer')
            ->getCollection()
            ->setPageSize(100)
            ->setCurPage(1);

        $customers = $collection->getItems();
        foreach ($customers as $item) {
            if ($item->getIsActive() == 0) {
                $items [] = $item;
            }
        }

        return $items;
    }

    /**
     * @param $filter
     * @return array
     */
    protected function getPromoRuleItems()
    {
        $collection = Mage::getModel('salesrule/rule')
            ->getCollection()
            ->setPageSize(100)
            ->setCurPage(1)
            ->addFieldToFilter('is_active', array('eq' => 0));

        return $collection->getItems();
    }

    /**
     * @param $filter
     * @return mixed
     * @throws Mage_Core_Model_Store_Exception
     */
    protected function getPromoCodeItems()
    {
        $collection = Mage::getModel('salesrule/coupon')
            ->getCollection()
            ->setPageSize(100)
            ->setCurPage(1);

        $date = $this->getDateHelper()->formatDate(null, 'Y-m-d H:i:s');
        $collection->addFieldToFilter('expiration_date', array('lteq' => $date));

        return $collection->getItems();
    }

    /**
     * Returns the rows that still exist in eCommerce data but
     * that had been deleted in it respective entity (product,
     * quote, promo code, etc.)
     *
     * @param $type
     * @return array
     */
    protected function getDeletedRows($type)
    {
        switch ($type) {
            case Ebizmarts_MailChimp_Model_Config::IS_PRODUCT:
                $this->addProductDeletedRowsToFilter();
                break;
            case Ebizmarts_MailChimp_Model_Config::IS_QUOTE:
                $this->addQuoteDeletedRowsToFilter();
                break;
            case Ebizmarts_MailChimp_Model_Config::IS_CUSTOMER:
                $this->addCustomerDeletedRowsToFilter();
                break;
            case Ebizmarts_MailChimp_Model_Config::IS_PROMO_RULE:
                $this->addRuleDeletedRowsToFilter();
                break;
            case Ebizmarts_MailChimp_Model_Config::IS_PROMO_CODE:
                $this->addCouponDeletedRowsToFilter();
                break;
        }

        return $this->getData();
    }

    /**
     * Gets the deleted rows from Collection
     *
     * @return array
     */
    protected function getData()
    {
        $data = array();

        foreach ($this->_ecommerceData as $item) {
            $data [] = $item->getData();
        }

        return $data;
    }

    /**
     * @param $ids
     * @param $type
     */
    public function deleteEcommerceRows($ids, $type)
    {
        $ids = array_filter($ids);
        $ids = implode(',' , $ids);
        $where = array(
            "related_id IN ($ids)",
            "type = '$type'"
        );

        $helper = $this->getHelper();
        $resource = $helper->getCoreResource();
        $connection = $resource->getConnection('core_write');
        $tableName = $resource->getTableName('mailchimp/ecommercesyncdata');
        $connection->delete($tableName, $where);

        $this->clearEcommerceCollection();
    }

    /**
     * @return Ebizmarts_MailChimp_Model_Resource_Ecommercesyncdata_Collection
     */
    protected function getEcommerceCollection()
    {
        return $this->_ecommerceData;
    }

    /**
     * Deletes entity type filters and joins
     *
     * @throws Zend_Db_Select_Exception
     */
    protected function clearEcommerceCollection()
    {
        $selectPart = $this->_ecommerceData->getSelect()->getPart(Zend_Db_Select::COLUMNS);

        if (empty($selectPart)) {
            $this->_ecommerceData->addFieldToSelect('related_id');
        }

        $fromPart = $this->_ecommerceData->getSelect()->getPart(Zend_Db_Select::FROM);
        unset($fromPart['ent']);

        $this->_ecommerceData->getSelect()->setPart(Zend_Db_Select::FROM, $fromPart);
        $this->_ecommerceData->getSelect()->setPart(Zend_Db_Select::WHERE, array());
        $this->_ecommerceData->clear();
    }

    /**
     * @param string $entityTable
     * @param string $entity
     * @throws Zend_Db_Select_Exception
     */
    protected function joinMagentoEntityTable($entityTable, $entity = 'entity')
    {
        $cols = '*';
        $selectPart = $this->_ecommerceData->getSelect()->getPart(Zend_Db_Select::COLUMNS);

        foreach ($selectPart as $p) {
            if (in_array('ent', $p)) {
                $cols = null;
            }
        }

        $this->_ecommerceData->getSelect()->joinLeft(
            array('ent' => $entityTable),
            'main_table.related_id = ent.' . $entity . '_id',
            $cols
        );
    }

    /**
     * Adds filter to get only deleted Products
     */
    protected function addProductDeletedRowsToFilter()
    {
        $resource = Mage::getSingleton('core/resource');
        $ecommerceData = $this->getEcommerceCollection();
        $ecommerceData->addFieldToFilter(
            'main_table.type',
            array('eq' => Ebizmarts_MailChimp_Model_Config::IS_PRODUCT)
        );

        $entityTable = $resource->getTableName('catalog/product');
        $ecommerceData->addFieldToFilter('ent.entity_id', array('null' => true));

        $this->joinMagentoEntityTable($entityTable, 'entity');
    }

    /**
     * Adds filter to get only deleted Quotes
     */
    protected function addQuoteDeletedRowsToFilter()
    {
        $resource = Mage::getSingleton('core/resource');
        $ecommerceData = $this->getEcommerceCollection();
        $ecommerceData->addFieldToFilter(
            'main_table.type',
            array('eq' => Ebizmarts_MailChimp_Model_Config::IS_QUOTE)
        );

        $entityTable = $resource->getTableName('sales/quote');
        $ecommerceData->addFieldToFilter('ent.entity_id', array('null' => true));

        $this->joinMagentoEntityTable($entityTable, 'entity');
    }

    /**
     * Adds filter to get only deleted Customers
     */
    protected function addCustomerDeletedRowsToFilter()
    {
        $resource = Mage::getSingleton('core/resource');
        $ecommerceData = $this->getEcommerceCollection();
        $ecommerceData->addFieldToFilter(
            'main_table.type',
            array('eq' => Ebizmarts_MailChimp_Model_Config::IS_CUSTOMER)
        );

        $entityTable = $resource->getTableName('customer/entity');
        $ecommerceData->addFieldToFilter('ent.entity_id', array('null' => true));

        $this->joinMagentoEntityTable($entityTable, 'entity');
    }

    /**
     * Adds filter to get only deleted Rules
     */
    protected function addRuleDeletedRowsToFilter()
    {
        $resource = Mage::getSingleton('core/resource');
        $ecommerceData = $this->getEcommerceCollection();
        $ecommerceData->addFieldToFilter(
            'main_table.type',
            array('eq' => Ebizmarts_MailChimp_Model_Config::IS_PROMO_RULE)
        );

        $entityTable = $resource->getTableName('salesrule/rule');
        $ecommerceData->addFieldToFilter('ent.rule_id', array('null' => true));

        $this->joinMagentoEntityTable($entityTable, 'rule');
    }

    /**
     * Adds filter to get only deleted Coupons
     */
    protected function addCouponDeletedRowsToFilter()
    {
        $resource = Mage::getSingleton('core/resource');
        $ecommerceData = $this->getEcommerceCollection();
        $ecommerceData->addFieldToFilter(
            'main_table.type',
            array('eq' => Ebizmarts_MailChimp_Model_Config::IS_PROMO_CODE)
        );

        $entityTable = $resource->getTableName('salesrule/coupon');
        $ecommerceData->addFieldToFilter('ent.coupon_id', array('null' => true));

        $this->joinMagentoEntityTable($entityTable, 'coupon');
    }
}

