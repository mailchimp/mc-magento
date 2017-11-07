<?php

class Ebizmarts_MailChimp_Model_Api_ProductsTest extends PHPUnit_Framework_TestCase
{
    private $productsApiMock;

    const BATCH_ID = 'storeid-0_PRO_2017-05-18-14-45-54-38849500';

    const PRODUCT_ID = 603;

    public function setUp()
    {
        Mage::app('default');

        /** @var Ebizmarts_MailChimp_Model_Api_Products $apiProductsMock productsApiMock */
        $this->productsApiMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Products::class);
    }

    public function tearDown()
    {
        $this->productsApiMock = null;
    }

    public function testCreateBatchJson()
    {
        $this->productsApiMock = $this->productsApiMock->setMethods(
            array(
                'makeBatchId',
                'makeProductsNotSentCollection',
                'joinMailchimpSyncData',
                'shouldSendProductUpdate',
                'getChildrenIdsForConfigurable',
                'makeProductChildrenCollection',
                "getMailChimpHelper"
            )
        )
        ->getMock();

        $mailChimpHelperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mailChimpHelperMock->method("getMailChimpProductImageUrl")->willReturn("product_image_url");
        $this->productsApiMock->expects($this->any())->method("getMailChimpHelper")
            ->willReturn($mailChimpHelperMock);
        $this->productsApiMock->expects($this->once())->method('makeBatchId')->with(0)
            ->willReturn(self::BATCH_ID);
        $this->productsApiMock->expects($this->never())->method('buildProductDataRemoval');

        $this->productsApiMock->expects($this->once())->method('joinMailchimpSyncData');
        $this->productsApiMock->expects($this->once())->method('shouldSendProductUpdate')->willReturn(false);

        $this->productsApiMock->expects($this->once())->method('makeProductsNotSentCollection')->with(0)
            ->willReturn($this->productCollection());

        $this->productsApiMock->expects($this->once())->method("makeProductChildrenCollection")
            ->willReturn($this->configurableChildrenCollection());
        $this->productsApiMock->expects($this->once())->method("getChildrenIdsForConfigurable")
            ->willReturn($this->configurableNoChildren());

        $return = $this->productsApiMock->createBatchJson('dasds231231312', 0);

        $this->assertEquals(1, count($return));
        $this->assertArrayHasKey("method", $return[0]);
        $this->assertArrayHasKey("path", $return[0]);
        $this->assertArrayHasKey("operation_id", $return[0]);
        $this->assertArrayHasKey("body", $return[0]);
        $this->assertEquals("POST", $return[0]["method"]);
        $this->assertRegExp("/\/ecommerce\/stores\/(.*)\/products/", $return[0]["path"]);
        $this->assertEquals(self::BATCH_ID . "_" . self::PRODUCT_ID, $return[0]["operation_id"]);
    }

    private function configurableChildrenCollection()
    {
        $collectionMock = $this->getMockBuilder(Mage_Catalog_Model_Resource_Product_Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $collectionMock->expects($this->once())->method('addAttributeToFilter')->with("entity_id", array("in" => array()));
        $collectionMock->expects($this->once())->method("getIterator")->willReturn(new ArrayIterator(array()));

        return $collectionMock;
    }

    private function productCollection()
    {
        $products = array();

        $productMock = $this->getMockBuilder(Mage_Catalog_Model_Product::class)
            ->disableOriginalConstructor()
            ->setMethods(
                array(
                    "getMailchimpSyncDeleted",
                    "_getResource",
                    "getId",
                    "getTypeId",
                    "getName",
                    "getDefaultName",
                    "getProductUrl",
                    "getDescription",
                    "getDefaultDescription",
                    "getCategoryIds"
                )
            )
            ->getMock();
        $productMock->method('_getResource')->willReturn(new Varien_Object());
        $productMock->expects($this->once())->method('getMailchimpSyncDeleted')->willReturn(null);
        $productMock->expects($this->any())->method('getId')->willReturn(self::PRODUCT_ID);
        $productMock->expects($this->exactly(4))->method('getTypeId')->willReturn("configurable");
        $productMock->expects($this->exactly(2))->method('getName')->willReturn(null);
        $productMock->expects($this->exactly(2))->method('getDefaultName')->willReturn("Lorem ipsum dolor sit amet 445452340");
        $productMock->expects($this->exactly(2))->method('getProductUrl')->willReturn("http://a.example");
        $productMock->expects($this->exactly(2))->method('getDescription')->willReturn(null);
        $productMock->expects($this->exactly(2))->method('getDefaultDescription')->willReturn("Lorem ipsum dolor sit amet. LONG");
        $productMock->expects($this->once())->method('getCategoryIds')->willReturn(null);

        $products []= $productMock;

        $collectionMock = $this->getMockBuilder(Mage_Catalog_Model_Resource_Product_Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $collectionMock->expects($this->once())->method("getIterator")->willReturn(new ArrayIterator($products));

        return $collectionMock;
    }

    public function testMakeProductsNotSentCollection()
    {
        $this->productsApiMock = $this->productsApiMock->setMethods(
            array(
                'joinQtyAndBackorders',
                'joinCategoryId',
                'joinProductAttributes',
                'getProductResourceCollection',
                'getBatchLimitFromConfig'
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

        $this->productsApiMock->expects($this->once())->method('getProductResourceCollection')
            ->willReturn($productResourceCollectionMock);
        $this->productsApiMock->expects($this->once())->method('joinQtyAndBackorders');
        $this->productsApiMock->expects($this->once())->method('joinCategoryId');
        $this->productsApiMock->expects($this->once())->method('joinProductAttributes');
        $this->productsApiMock->expects($this->once())->method('getProductResourceCollection');
        $this->productsApiMock->expects($this->once())->method('getBatchLimitFromConfig')->willReturn(100);

        $collection = $this->productsApiMock->makeProductsNotSentCollection(0);

        $this->assertInstanceOf("Mage_Catalog_Model_Resource_Product_Collection", $collection);
    }

    /**
     * @see \Mage_Catalog_Model_Resource_Product_Type_Configurable::getChildrenIds
     * @return array
     */
    private function configurableNoChildren()
    {
        return \Ebizmarts_MailChimp_Model_Api_Products::$noChildrenIds;
    }

    public function testGetNotVisibleProductUrl()
    {
        $childId = 1;
        $parentId = 2;
        $magentoStoreId = 1;
        $path = 'path';
        $url = 'url/path';

        $productsApiMock = $this->productsApiMock
            ->setMethods(array('getMailChimpHelper', 'getParentId', 'getProductWithAttributesById', 'getUrlByPath'))
            ->getMock();

        $mailChimpHelperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getProductResourceModel'))
            ->getMock();

        $productResourceMock = $this->getMockBuilder(Mage_Catalog_Model_Resource_Product::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getAttributeRawValue'))
            ->getMock();

        $productResourceCollectionMock = $this->getMockBuilder(Mage_Catalog_Model_Resource_Product_Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $productsApiMock->expects($this->once())->method('getMailChimpHelper')->willReturn($mailChimpHelperMock);
        $productsApiMock->expects($this->once())->method('getParentId')->with($childId)->willReturn($parentId);
        $productsApiMock->expects($this->once())->method('getProductWithAttributesById')->willReturn($productResourceCollectionMock);
        $productsApiMock->expects($this->once())->method('getUrlByPath')->with($path, $magentoStoreId)->willReturn($url);

        $productResourceCollectionMock->expects($this->once())->method("getIterator")->willReturn(new ArrayIterator(array()));

        $mailChimpHelperMock->expects($this->once())->method('getProductResourceModel')->willReturn($productResourceMock);

        $productResourceMock->expects($this->once())->method('getAttributeRawValue')->with($parentId, 'url_path', $magentoStoreId)->willReturn($path);

        $return = $productsApiMock->getNotVisibleProductUrl($childId, $magentoStoreId);

        $this->assertEquals($return, $url);
    }

    public function testGetParentImageUrl()
    {
        $childId = 1;
        $parentId = 2;
        $magentoStoreId = 1;

        $imageUrl = 'imageUrl';

        $productsApiMock = $this->productsApiMock
            ->setMethods(array('getMailChimpHelper', 'getParentId'))
            ->getMock();

        $mailChimpHelperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getImageUrlById'))
            ->getMock();

        $productsApiMock->expects($this->once())->method('getParentId')->with($childId)->willReturn($parentId);
        $productsApiMock->expects($this->once())->method('getMailChimpHelper')->willReturn($mailChimpHelperMock);

        $mailChimpHelperMock->expects($this->once())->method('getImageUrlById')->with($parentId, $magentoStoreId)->willReturn($imageUrl);

        $return = $productsApiMock->getParentImageUrl($childId, $magentoStoreId);

        $this->assertEquals($return, $imageUrl);
    }
}
