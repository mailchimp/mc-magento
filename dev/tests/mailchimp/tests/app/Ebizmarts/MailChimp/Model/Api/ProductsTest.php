<?php

class Ebizmarts_MailChimp_Model_Api_ProductsTest extends PHPUnit_Framework_TestCase
{
    private $productsApiMock;

    public function setUp()
    {
        Mage::app('default');

        /**
 * @var Ebizmarts_MailChimp_Model_Api_Products $apiProductsMock 
*/
        $this->productsApiMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Products::class);
    }

    public function tearDown()
    {
        $this->productsApiMock = null;
    }

    public function testCreateBatchJson()
    {
        $this->productsApiMock = $this->productsApiMock->setMethods(array('makeBatchId', 'makeProductsNotSentCollection', 'joinMailchimpSyncData'))
            ->getMock();

        $this->productsApiMock->expects($this->once())->method('makeBatchId')->with(0)
            ->willReturn('storeid-0_PRO_2017-05-18-14-45-54-38849500');
        $this->productsApiMock->expects($this->never())->method('buildProductDataRemoval');
        $this->productsApiMock->expects($this->once())->method('makeProductsNotSentCollection');
        $this->productsApiMock->expects($this->once())->method('joinMailchimpSyncData');

        $this->productsApiMock->expects($this->once())->method('makeProductsNotSentCollection')->with('dasds231231312', 0)
            ->willReturn(new Varien_Object());

        $this->productsApiMock->createBatchJson('dasds231231312', 0);
    }

    public function testMakeProductsNotSentCollection()
    {
        $this->productsApiMock = $this->productsApiMock->setMethods(
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

        $this->productsApiMock->expects($this->once())->method('getProductResourceCollection')
            ->willReturn($productResourceCollectionMock);
        $this->productsApiMock->expects($this->once())->method('joinQtyAndBackorders');
        $this->productsApiMock->expects($this->once())->method('joinCategoryId');
        $this->productsApiMock->expects($this->once())->method('joinProductAttributes');
        $this->productsApiMock->expects($this->once())->method('getProductResourceCollection');
        $this->productsApiMock->expects($this->once())->method('getWebsiteIdForStoreId')->with(0)->willReturn(1);

        $collection = $this->productsApiMock->makeProductsNotSentCollection('dasds123321', 0);

        $this->assertInstanceOf("Mage_Catalog_Model_Resource_Product_Collection", $collection);
    }
}