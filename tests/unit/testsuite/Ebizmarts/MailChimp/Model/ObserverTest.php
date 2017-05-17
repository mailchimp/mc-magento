<?php

class Ebizmarts_MailChimp_Model_ObserverTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Mage::app('default');
    }

    public function testProductAttributeUpdateIsUsingCorrectStoreId()
    {
        /** @var \Ebizmarts_MailChimp_Helper_Data $helperMock */
        $modelMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(array('changeStoreName', 'makeHelper', 'makeApiProducts'))
            ->getMock();

        $apiProductsMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Products::class)
            ->disableOriginalConstructor()
            ->setMethods(array('update'))
            ->getMock();
        $apiProductsMock->expects($this->exactly(2))->method('update')->withConsecutive(
            array(12, 0),
            array(34, 0)
        );

        $eventMock = $this->getMockBuilder(Varien_Event::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getProductIds', 'getStoreId'))
            ->getMock();
        $eventMock->expects($this->once())->method('getProductIds')->willReturn(array(12,34));
        $eventMock->expects($this->once())->method('getStoreId')->willReturn(0);

        $eventObserverMock = $this->getMockBuilder(Varien_Event_Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getEvent'))
            ->getMock();
        $eventObserverMock->expects($this->exactly(2))->method('getEvent')->willReturn($eventMock);

        $modelMock->expects($this->never())->method('makeHelper');

        $modelMock->expects($this->exactly(2))->method('makeApiProducts')->willReturn($apiProductsMock);

        $modelMock->productAttributeUpdate($eventObserverMock);
    }
}