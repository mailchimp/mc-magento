<?php

class Ebizmarts_MailChimp_Model_ClearEcommerceTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Mage::app('default');
    }

    public function testClearEcommerce()
    {
        $ids = array(1, 3, 4);

        $clearEcommerce = $this->getMockBuilder(Ebizmarts_MailChimp_Model_ClearEcommerce::class)
            ->disableOriginalConstructor()
            ->setMethods(
                array('processData', 'getItemsToDelete')
            )
            ->getMock();

        $itemMock = $this
            ->getMockBuilder(Mage_Catalog_Model_Resource_Product::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getId'))
            ->getMock();

        $clearEcommerce
            ->expects($this->exactly(5))
            ->method('getItemsToDelete')
            ->withConsecutive(
                array(Ebizmarts_MailChimp_Model_Config::IS_PRODUCT),
                array(Ebizmarts_MailChimp_Model_Config::IS_CUSTOMER),
                array(Ebizmarts_MailChimp_Model_Config::IS_QUOTE),
                array(Ebizmarts_MailChimp_Model_Config::IS_PROMO_RULE),
                array(Ebizmarts_MailChimp_Model_Config::IS_PROMO_CODE)
            )
            ->willReturnOnConsecutiveCalls(
                array($itemMock),
                array($itemMock),
                array($itemMock),
                array($itemMock),
                array($itemMock)
            );

        $clearEcommerce
            ->expects($this->exactly(5))
            ->method('processData')
            ->withConsecutive(
                array(array($itemMock), Ebizmarts_MailChimp_Model_Config::IS_PRODUCT),
                array(array($itemMock), Ebizmarts_MailChimp_Model_Config::IS_CUSTOMER),
                array(array($itemMock), Ebizmarts_MailChimp_Model_Config::IS_QUOTE),
                array(array($itemMock), Ebizmarts_MailChimp_Model_Config::IS_PROMO_RULE),
                array(array($itemMock), Ebizmarts_MailChimp_Model_Config::IS_PROMO_CODE)
            )
            ->willReturnOnConsecutiveCalls(
                $ids,
                $ids,
                $ids,
                $ids,
                $ids
            );

        $clearEcommerce->clearEcommerceData();
    }

    public function testClearEcommerceProcessData()
    {
        $ids = array(1, 3, 4);
        $itemsDeleted = array(3, 4);

        $clearEcommerce = $this->getMockBuilder(Ebizmarts_MailChimp_Model_ClearEcommerce::class)
            ->disableOriginalConstructor()
            ->setMethods(
                array('processDeletedData', 'deleteEcommerceRows')
            )
            ->getMock();

        $itemMock = $this
            ->getMockBuilder(Mage_Catalog_Model_Resource_Product::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getId'))
            ->getMock();

        $itemMock->expects($this->once())->method('getId')->willReturn(1);

        $clearEcommerce
            ->expects($this->once())
            ->method('processDeletedData')
            ->with(Ebizmarts_MailChimp_Model_Config::IS_PRODUCT)
            ->willReturn($itemsDeleted);

        $clearEcommerce
            ->expects($this->once())
            ->method('deleteEcommerceRows')
            ->with($ids, Ebizmarts_MailChimp_Model_Config::IS_PRODUCT);

        $clearEcommerce->processData(array($itemMock), Ebizmarts_MailChimp_Model_Config::IS_PRODUCT);
    }

    public function testClearEcommerceProcessDataEmpty()
    {
        $itemsDeleted = array();

        $clearEcommerce = $this->getMockBuilder(Ebizmarts_MailChimp_Model_ClearEcommerce::class)
            ->disableOriginalConstructor()
            ->setMethods(
                array('processDeletedData', 'deleteEcommerceRows', 'clearEcommerceCollection')
            )
            ->getMock();

        $clearEcommerce
            ->expects($this->once())
            ->method('processDeletedData')
            ->with(Ebizmarts_MailChimp_Model_Config::IS_PRODUCT)
            ->willReturn($itemsDeleted);

        $clearEcommerce
            ->expects($this->never())
            ->method('deleteEcommerceRows');

        $clearEcommerce
            ->expects($this->once())
            ->method('clearEcommerceCollection')
            ->willReturnSelf();

        $clearEcommerce->processData(array(), Ebizmarts_MailChimp_Model_Config::IS_PRODUCT);
    }

    public function testClearEcommerceDeteleRows()
    {
        $idsArray = array(1, 3, 4);
        $ids = implode($idsArray, ', ');
        $where = array("related_id IN ($ids)", "type = '" . Ebizmarts_MailChimp_Model_Config::IS_PRODUCT . "'");
        $tableName = 'mailchimp_ecommerce_sync_data';

        $clearEcommerce = $this->getMockBuilder(Ebizmarts_MailChimp_Model_ClearEcommerce::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getHelper', 'clearEcommerceCollection'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getCoreResource'))
            ->getMock();
        $coreResourceMock = $this->getMockBuilder(Mage_Core_Model_Resource::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getConnection', 'getTableName'))
            ->getMock();
        $writeAdapterMock = $this->getMockForAbstractClass(Varien_Db_Adapter_Interface::class);

        $clearEcommerce->expects($this->once())->method('getHelper')->willReturn($helperMock);
        $helperMock->expects($this->once())->method('getCoreResource')->willReturn($coreResourceMock);

        $coreResourceMock
            ->expects($this->once())
            ->method('getConnection')
            ->with('core_write')
            ->willReturn($writeAdapterMock);

        $coreResourceMock
            ->expects($this->once())
            ->method('getTableName')
            ->with('mailchimp/ecommercesyncdata')
            ->willReturn($tableName);

        $writeAdapterMock
            ->expects($this->once())
            ->method("delete")
            ->with($tableName, $where);

        $clearEcommerce
            ->expects($this->once())
            ->method('clearEcommerceCollection')
            ->willReturnSelf();

        $clearEcommerce->deleteEcommerceRows($idsArray, Ebizmarts_MailChimp_Model_Config::IS_PRODUCT);
    }
}
