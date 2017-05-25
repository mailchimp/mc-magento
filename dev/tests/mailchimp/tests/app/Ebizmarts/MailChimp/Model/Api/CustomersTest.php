<?php

class Ebizmarts_MailChimp_Model_Api_CustomersTest extends PHPUnit_Framework_TestCase
{
    /** @var Ebizmarts_MailChimp_Model_Api_Customers */
    private $customersApiMock;

    public function setUp()
    {
        Mage::app('default');

        $this->customersApiMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Customers::class);
    }

    public function tearDown()
    {
        $this->customersApiMock = null;
    }

    public function testGetOptInYes()
    {
        $this->customersApiMock = $this->customersApiMock->setMethods(array('isEcommerceCustomerOptInConfigEnabled'))
            ->getMock();

        $this->customersApiMock->expects($this->once())->method('isEcommerceCustomerOptInConfigEnabled')->with(1)->willReturn('1');

        $this->assertTrue($this->customersApiMock->getOptin(1));
    }

    public function testGetOptInNo()
    {
        $this->customersApiMock = $this->customersApiMock->setMethods(array('isEcommerceCustomerOptInConfigEnabled'))
            ->getMock();

        $this->customersApiMock->expects($this->once())->method('isEcommerceCustomerOptInConfigEnabled')->with(1)->willReturn('0');

        $this->assertFalse($this->customersApiMock->getOptin(1));
    }

    public function testGetOptInNoDefaultStore()
    {
        $this->customersApiMock = $this->customersApiMock->setMethods(array('isEcommerceCustomerOptInConfigEnabled'))
            ->getMock();

        $this->customersApiMock->expects($this->once())->method('isEcommerceCustomerOptInConfigEnabled')->with(0)->willReturn('0');

        $this->assertFalse($this->customersApiMock->getOptin(0));

        $this->assertFalse($this->customersApiMock->getOptin(0));
    }

    public function testCreateBatchJson()
    {
        $this->customersApiMock = $this->customersApiMock->setMethods(array('makeBatchId', 'makeCustomersNotSentCollection', 'joinMailchimpSyncData'))
            ->getMock();

        $this->customersApiMock->expects($this->once())->method('makeBatchId')->with(0)
            ->willReturn('storeid-0_CUS_2017-05-18-14-45-54-38849500');
        $this->customersApiMock->expects($this->never())->method('buildProductDataRemoval');
        $this->customersApiMock->expects($this->once())->method('joinMailchimpSyncData');

        $this->customersApiMock->expects($this->once())->method('makeCustomersNotSentCollection')->with(0)
        ->willReturn(new Varien_Object());

        $this->customersApiMock->createBatchJson('dasds231231312', 0);
    }

    public function testMakeProductsNotSentCollection()
    {
        $this->markTestSkipped();
        $this->customersApiMock = $this->customersApiMock->setMethods(
            array(
                'joinQtyAndBackorders',
                'joinCategoryId',
                'joinProductAttributes',
                'getProductResourceCollection',
                'getWebsiteIdForStoreId'
            )
        )
            ->getMock();

        $dbSelectMock = $this->getMockBuilder(Varien_Db_Select::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbSelectMock->expects($this->once())->method('group')->with('e.entity_id');
        $dbSelectMock->expects($this->once())->method('limit')->with(100);

        $productResourceCollectionMock = $this->getMockBuilder(Mage_Catalog_Model_Resource_Product_Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productResourceCollectionMock->expects($this->exactly(2))->method('getSelect')->willReturn($dbSelectMock);
        $productResourceCollectionMock->expects($this->once())->method('addStoreFilter');
        $productResourceCollectionMock->expects($this->once())->method('addPriceData')->with(null, 1);

        $this->customersApiMock->expects($this->once())->method('getProductResourceCollection')
            ->willReturn($productResourceCollectionMock);
        $this->customersApiMock->expects($this->once())->method('joinQtyAndBackorders');
        $this->customersApiMock->expects($this->once())->method('joinCategoryId');
        $this->customersApiMock->expects($this->once())->method('joinProductAttributes');
        $this->customersApiMock->expects($this->once())->method('getProductResourceCollection');
        $this->customersApiMock->expects($this->once())->method('getWebsiteIdForStoreId')->with(0)->willReturn(1);

        $collection = $this->customersApiMock->makeProductsNotSentCollection('dasds123321', 0);

        $this->assertInstanceOf("Mage_Catalog_Model_Resource_Product_Collection", $collection);
    }
}