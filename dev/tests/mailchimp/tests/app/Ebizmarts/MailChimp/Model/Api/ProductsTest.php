<?php

class Ebizmarts_MailChimp_Model_Api_ProductsTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Mage::app('default');
    }

    public function testCreateBatchJson()
    {
        /** @var Ebizmarts_MailChimp_Model_Api_Products $apiProductsMock */
        $apiProductsMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Products::class)
            ->disableOriginalConstructor()
            ->setMethods(array('sendModifiedProduct', 'generateBatchId', 'makeProductsNotSentCollection'))
            ->getMock();

        $apiProductsMock->expects($this->once())->method('generateBatchId')->with(0)
            ->willReturn('storeid-0_PRO_2017-05-18-14-45-54-38849500');
        $apiProductsMock->expects($this->never())->method('buildProductDataRemoval');

        $apiProductsMock->expects($this->once())->method('makeProductsNotSentCollection')->with('dasds231231312', 0)
        ->willReturn(new Varien_Object());

        $apiProductsMock->createBatchJson('dasds231231312', 0);
    }
}