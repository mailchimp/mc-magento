<?php

class Ebizmarts_MailChimp_Model_Api_ProductsTest extends PHPUnit_Framework_TestCase
{
    private $productsApiMock;

    public function setUp()
    {
        Mage::app('default');

        /** @var Ebizmarts_MailChimp_Model_Api_Products $apiProductsMock */
        $this->productsApiMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Products::class);
    }

    public function testCreateBatchJson()
    {
        $this->productsApiMock = $this->productsApiMock->setMethods(array('makeBatchId'))
            ->getMock();

        $this->productsApiMock->expects($this->once())->method('makeBatchId')->with(0)
            ->willReturn('storeid-0_PRO_2017-05-18-14-45-54-38849500');
        $this->productsApiMock->expects($this->never())->method('buildProductDataRemoval');

//        $this->productsApiMock->expects($this->once())->method('makeProductsNotSentCollection')->with('dasds231231312', 0)
//        ->willReturn(new Varien_Object());

        $this->productsApiMock->createBatchJson('dasds231231312', 0);
    }

    public function testMakeProductsNotSentCollection()
    {
        $this->productsApiMock = $this->productsApiMock->setMethods(array('sendModifiedProduct', 'generateBatchId'))
            ->getMock();

//        $product->getProductUrl()

        $collection = $this->productsApiMock->makeProductsNotSentCollection('dasds123321', 0);

        $this->assertEquals("select", (string)$collection->getSelect());
    }
}