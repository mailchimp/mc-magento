<?php

class Ebizmarts_MailChimp_Model_ClearEcommerce extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Mage::app('default');
    }

    public function testClearEcommerce()
    {
        $items = array(1, 2);
        $itemsDeleted = array(3, 4);
        $itemsDeletedUnprocessed = array(array('related_id' => 3), array('related_id' => 4));

        $tableName = 'mailchimp_ecommerce_sync_data';

        $clearEcommerce = $this->getMockBuilder(Ebizmarts_MailChimp_Model_ClearEcommerce::class)
            ->disableOriginalConstructor()
            ->setMethods(
                array(
                    'getHelper', 'getDateHelper', 'cleanEcommerceData', 'processData', 'processDeletedData',
                    'getItemsToDelete', 'getProductItems', 'getQuoteItems', 'getCustomerItems',
                    'getPromoRuleItems', 'getPromoCodeItems', 'getDeletedRows', 'deleteEcommerceRows'
                )
            )
            ->getMock();
        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getCoreResource'))
            ->getMock();
        $dateHelperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Date::class)
            ->disableOriginalConstructor()
            ->setMethods(array('formatDate'))
            ->getMock();
        $coreResourceMock = $this->getMockBuilder(Mage_Core_Model_Resource::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getConnection'))
            ->getMock();
        $writeAdapterMock = $this->getMockForAbstractClass(Varien_Db_Adapter_Interface::class);

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
            ->with(
                $tableName,
                ""
            );

        $clearEcommerce->expects($this->exactly(5))->method('getHelper')->willReturn($helperMock);
        $clearEcommerce->expects($this->once())->method('getDateHelper')->willReturn($dateHelperMock);

        $clearEcommerce->expects($this->once())->method('cleanEcommerceData');

        $clearEcommerce->expects($this->once())->method('getProductItems')->willReturn($itemsPro);
        $clearEcommerce->expects($this->once())->method('getCustomerItems')->willReturn($itemsCus);
        $clearEcommerce->expects($this->once())->method('getQuoteItems')->willReturn($itemsQuo);
        $clearEcommerce->expects($this->once())->method('getPromoRuleItems')->willReturn($itemsRul);
        $clearEcommerce->expects($this->once())->method('getPromoCodeItems')->willReturn($itemsCod);

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
                array($items),
                array($items),
                array($items),
                array($items),
                array($items)
            );
        $clearEcommerce
            ->expects($this->exactly(5))
            ->method('processData')
            ->withConsecutive(
                array($items, Ebizmarts_MailChimp_Model_Config::IS_PRODUCT),
                array($items, Ebizmarts_MailChimp_Model_Config::IS_CUSTOMER),
                array($items, Ebizmarts_MailChimp_Model_Config::IS_QUOTE),
                array($items, Ebizmarts_MailChimp_Model_Config::IS_PROMO_RULE),
                array($items, Ebizmarts_MailChimp_Model_Config::IS_PROMO_CODE)
            );


        $clearEcommerce
            ->expects($this->exactly(5))
            ->method('processDeletedData')
            ->withConsecutive(
                array(Ebizmarts_MailChimp_Model_Config::IS_PRODUCT),
                array(Ebizmarts_MailChimp_Model_Config::IS_CUSTOMER),
                array(Ebizmarts_MailChimp_Model_Config::IS_QUOTE),
                array(Ebizmarts_MailChimp_Model_Config::IS_PROMO_RULE),
                array(Ebizmarts_MailChimp_Model_Config::IS_PROMO_CODE)
            )
            ->willReturnOnConsecutiveCalls(
                array($itemsDeleted),
                array($itemsDeleted),
                array($itemsDeleted),
                array($itemsDeleted),
                array($itemsDeleted)
            );

        $clearEcommerce
            ->expects($this->exactly(5))
            ->method('getDeletedRows')
            ->withConsecutive(
                array(Ebizmarts_MailChimp_Model_Config::IS_PRODUCT),
                array(Ebizmarts_MailChimp_Model_Config::IS_CUSTOMER),
                array(Ebizmarts_MailChimp_Model_Config::IS_QUOTE),
                array(Ebizmarts_MailChimp_Model_Config::IS_PROMO_RULE),
                array(Ebizmarts_MailChimp_Model_Config::IS_PROMO_CODE)
            )
            ->willReturnOnConsecutiveCalls(
                array($itemsDeletedUnprocessed),
                array($itemsDeletedUnprocessed),
                array($itemsDeletedUnprocessed),
                array($itemsDeletedUnprocessed),
                array($itemsDeletedUnprocessed)
            );

        $clearEcommerce
            ->expects($this->exactly(5))
            ->method('deleteEcommerceRows')
            ->withConsecutive(
                array(array_merge($itemsPro, $itemsDeleted), Ebizmarts_MailChimp_Model_Config::IS_PRODUCT),
                array(array_merge($itemsCus, $itemsDeleted), Ebizmarts_MailChimp_Model_Config::IS_CUSTOMER),
                array(array_merge($itemsQuo, $itemsDeleted), Ebizmarts_MailChimp_Model_Config::IS_QUOTE),
                array(array_merge($itemsRul, $itemsDeleted), Ebizmarts_MailChimp_Model_Config::IS_PROMO_RULE),
                array(array_merge($itemsCod, $itemsDeleted), Ebizmarts_MailChimp_Model_Config::IS_PROMO_CODE)
            );
    }
}
