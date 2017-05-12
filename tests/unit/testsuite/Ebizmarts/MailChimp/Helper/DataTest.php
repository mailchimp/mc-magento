<?php

class Ebizmarts_MailChimp_Helper_DataTest extends PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        Mage::app('default');
    }

    public function testGetLastDateOfPurchase()
    {
        /** @var \Ebizmarts_MailChimp_Helper_Data $helperMock */
        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getOrderCollectionByCustomerEmail'))
            ->getMock();

        $helperMock->expects($this->once())->method('getOrderCollectionByCustomerEmail')->with("john@example.com")
        ->willReturn(array());

        $this->assertNull($helperMock->getLastDateOfPurchase("john@example.com"));
    }

}