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
        $magentoStoreId = 0;
        $mailchimpStoreId = 'dasds231231312';
        $products = array();
        $productData = array(
            'method' => 'POST',
            'path' => "/ecommerce/stores/$mailchimpStoreId/products",
            'operation_id' => self::BATCH_ID,
            'body' => '{"id":"906","title":"test Prod","url":"http:\/\/127.0.0.1\/mcmagento-1937\/test-prod.html","published_at_foreign":"","description":"Test","type":"Default Category","vendor":"Default Category","handle":"","variants":[{"id":"906","title":"test Prod","url":"http:\/\/127.0.0.1\/mcmagento-1937\/test-prod.html","published_at_foreign":"","sku":"testprod","price":10,"inventory_quantity":1000,"backorders":"0","visibility":"Catalog, Search"}]}'
        );


        $productMock = $this->getMockBuilder(Mage_Catalog_Model_Product::class)
            ->disableOriginalConstructor()
            ->setMethods(
                array(
                    "getMailchimpSyncDeleted",
                    "getId"
                )
            )
            ->getMock();

        $productCollection = $this->getMockBuilder(Mage_Catalog_Model_Resource_Product_Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $productsApiMock = $this->productsApiMock
            ->setMethods(array('makeBatchId', 'makeProductsNotSentCollection', 'joinMailchimpSyncData',
                'shouldSendProductUpdate', 'getChildrenIdsForConfigurable',
                'getMailChimpHelper', 'isProductFlatTableEnabled', '_buildNewProductRequest', '_updateSyncData', '_markSpecialPrices'))
            ->getMock();

        $productsApiMock->expects($this->once())->method("isProductFlatTableEnabled")->willReturn(false);

        $productsApiMock->expects($this->once())->method('_markSpecialPrices')->with($mailchimpStoreId, $magentoStoreId);
        $productsApiMock->expects($this->once())->method('makeProductsNotSentCollection')->with($magentoStoreId)->willReturn($productCollection);
        $productsApiMock->expects($this->once())->method('joinMailchimpSyncData')->with($productCollection, $mailchimpStoreId);

        $productsApiMock->expects($this->once())->method('makeBatchId')->with($magentoStoreId)->willReturn(self::BATCH_ID);

        $products [] = $productMock;
        $productCollection->expects($this->once())->method("getIterator")->willReturn(new ArrayIterator($products));

        $productsApiMock->expects($this->once())->method('shouldSendProductUpdate')->with($mailchimpStoreId, $magentoStoreId, $productMock)->willReturn(false);
        $productsApiMock->expects($this->once())->method('_buildNewProductRequest')->with($productMock, self::BATCH_ID, $mailchimpStoreId, $magentoStoreId)->willReturn($productData);
        $productsApiMock->expects($this->once())->method('_updateSyncData')->with($productMock->getId(), $mailchimpStoreId);


        $return = $productsApiMock->createBatchJson($mailchimpStoreId, $magentoStoreId);

        $this->assertEquals(1, count($return));
        $this->assertArrayHasKey("method", $return[0]);
        $this->assertArrayHasKey("path", $return[0]);
        $this->assertArrayHasKey("operation_id", $return[0]);
        $this->assertArrayHasKey("body", $return[0]);
        $this->assertEquals("POST", $return[0]["method"]);
        $this->assertRegExp("/\/ecommerce\/stores\/(.*)\/products/", $return[0]["path"]);
        $this->assertEquals(self::BATCH_ID, $return[0]["operation_id"]);
    }

    public function testMakeProductsNotSentCollection()
    {
        $this->productsApiMock = $this->productsApiMock->setMethods(
            array(
                'joinQtyAndBackorders',
                'getProductResourceCollection',
                'getBatchLimitFromConfig'
            )
        )
            ->getMock();

        $dbSelectMock = $this->getMockBuilder(Varien_Db_Select::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbSelectMock->expects($this->once())->method('limit')->with(100);

        $productResourceCollectionMock = $this->getMockBuilder(Mage_Catalog_Model_Resource_Product_Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productResourceCollectionMock->expects($this->once())->method('getSelect')->willReturn($dbSelectMock);
        $productResourceCollectionMock->expects($this->once())->method('addStoreFilter')->willReturnSelf();
        $productResourceCollectionMock->expects($this->once())->method('addFinalPrice')->willReturnSelf();

        $this->productsApiMock->expects($this->once())->method('getProductResourceCollection')
            ->willReturn($productResourceCollectionMock);
        $this->productsApiMock->expects($this->once())->method('joinQtyAndBackorders');
        $this->productsApiMock->expects($this->once())->method('getProductResourceCollection');
        $this->productsApiMock->expects($this->once())->method('getBatchLimitFromConfig')->willReturn(100);

        $collection = $this->productsApiMock->makeProductsNotSentCollection(0);

        $this->assertInstanceOf("Mage_Catalog_Model_Resource_Product_Collection", $collection);
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

    public function testGetProductCategories()
    {
        $catArray = array(13,14);
        $magentoStoreId = '1';
        $result = 'catO - catR';
        $categories = array();

        $productMock = $this->getMockBuilder(Mage_Catalog_Model_Product::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getResource'))
            ->getMock();

        $productResourceMock = $this->getMockBuilder(Mage_Catalog_Model_Resource_Product::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getCategoryIds'))
            ->getMock();

        $productsApiMock = $this->productsApiMock
            ->setMethods(array('makeCatalogCategory'))
            ->getMock();

        $categoryMockGeneric = $this->getMockBuilder(Mage_Catalog_Model_Category::class)
          ->disableOriginalConstructor()
            ->setMethods(array('getCollection'))
            ->getMock();

        $categoryCollectionMock = $this->getMockBuilder(Mage_Catalog_Model_Resource_Category_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('addAttributeToSelect', 'setStoreId', 'addAttributeToFilter', 'addAttributeToSort', 'getIterator'))
            ->getMock();

        $categoryMockOne = $this->getMockBuilder(Mage_Catalog_Model_Category::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getName'))
            ->getMock();

        $categoryMockTwo = $this->getMockBuilder(Mage_Catalog_Model_Category::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getName'))
            ->getMock();

        $productMock->expects($this->once())->method('getResource')->willReturn($productResourceMock);

        $productResourceMock->expects($this->once())->method('getCategoryIds')->willReturn($catArray);

        $productsApiMock->expects($this->once())->method('makeCatalogCategory')->willReturn($categoryMockGeneric);

        $categoryMockGeneric->expects($this->once())->method('getCollection')->willReturn($categoryCollectionMock);

        $categoryCollectionMock->expects($this->once())->method('setStoreId')->with($magentoStoreId)->willReturnSelf();
        $categoryCollectionMock->expects($this->once())->method('addAttributeToSelect')->with(array('name'))->willReturnSelf();
        $categoryCollectionMock->expects($this->exactly(2))->method('addAttributeToFilter')->withConsecutive(
            array('is_active', array('eq' => '1')),
            array('entity_id', array('in' => $catArray))
        )->willReturnOnConsecutiveCalls(
            $categoryCollectionMock,
            $categoryCollectionMock
        );
        $categoryCollectionMock->expects($this->exactly(2))->method('addAttributeToSort')->withConsecutive(
            array('level', 'asc'),
            array('name', 'asc')
        )->willReturnOnConsecutiveCalls(
            $categoryCollectionMock,
            $categoryCollectionMock
        );

        $categories[] = $categoryMockOne;
        $categories[] = $categoryMockTwo;
        $categoryCollectionMock->expects($this->once())->method("getIterator")->willReturn(new ArrayIterator($categories));

        $categoryMockOne->expects($this->once())->method('getName')->willReturn('catO');
        $categoryMockTwo->expects($this->once())->method('getName')->willReturn('catR');

        $return = $productsApiMock->getProductCategories($productMock, $magentoStoreId);

        $this->assertEquals($result, $return);
    }

    public function testSendModifiedProduct()
    {
        $magentoStoreId = 1;
        $mailchimpStoreId = 'a1s2d3f4g5h6j7k8l9n0';
        $groupedProductId = 1;
        $oldProductId = 2;
        $newProductId = 3;
        $batchId = 'storeid-1_PRO_2018-03-15-19-16-36-84319400_';
        $ecomSyncDateFlag = '2018-03-14 15:03:36';
        $itemOneSyncDelta = '2018-03-14 15:03:37';
        $itemTwoSyncDelta = '2018-03-14 15:03:35';
        $isProductEnabled = false;

        $productsApiMock = $this->productsApiMock
            ->setMethods(array('makeBatchId', '_updateSyncData', 'loadProductById', 'getMailChimpHelper',
                'isGroupedProduct', 'isBundleProduct', '_buildUpdateProductRequest', '_buildNewProductRequest', 'isProductEnabled'))
            ->getMock();

        $orderMock = $this->getMockBuilder(Mage_Sales_Model_Order::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getAllVisibleItems'))
            ->getMock();

        $itemMock = $this->getMockBuilder(Mage_Sales_Model_Order_Item::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getProductId'))
            ->getMock();

        $itemCollectionMock = $this->getMockBuilder(Mage_Sales_Model_Resource_Order_Item_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getIterator'))
            ->getMock();

        $items = array();
        $items[] = $itemMock;
        $items[] = $itemMock;
        $items[] = $itemMock;

        $productMock = $this->getMockBuilder(Mage_Catalog_Model_Product::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getId'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getEcommerceSyncDataItem', 'getEcommMinSyncDateFlag'))
            ->getMock();

        $syncDataItemMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Ecommercesyncdata::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getMailchimpSyncModified', 'getMailchimpSyncDelta'))
            ->getMock();

        $productsApiMock->expects($this->once())->method('makeBatchId')->with($magentoStoreId)->willReturn($batchId);

        $orderMock->expects($this->once())->method('getAllVisibleItems')->willReturn($itemCollectionMock);

        $itemCollectionMock->expects($this->once())->method('getIterator')->willReturn(new ArrayIterator($items));

        $itemMock->expects($this->exactly(3))->method('getProductId')->willReturnOnConsecutiveCalls(
            $groupedProductId,
            $oldProductId,
            $newProductId
            );

        $productsApiMock->expects($this->exactly(3))->method('loadProductById')->withConsecutive(
            array($groupedProductId),
            array($oldProductId),
            array($newProductId)
        )->willReturnOnConsecutiveCalls(
            $productMock,
            $productMock,
            $productMock
        );

        $productMock->expects($this->exactly(3))->method('getId')->willReturnOnConsecutiveCalls(
            $groupedProductId,
            $oldProductId,
            $newProductId
        );

        $productsApiMock->expects($this->once())->method('getMailChimpHelper')->willReturn($helperMock);

        $productsApiMock->expects($this->exactly(2))
            ->method('isProductEnabled')
            ->withConsecutive(
                array($oldProductId),
                array($newProductId)
            )->willReturnOnConsecutiveCalls(
                true,
                true
            );

        $helperMock->expects($this->once())->method('getEcommMinSyncDateFlag')->with($mailchimpStoreId, $magentoStoreId)->willReturn($ecomSyncDateFlag);
        $helperMock->expects($this->exactly(3))->method('getEcommerceSyncDataItem')->withConsecutive(
            array($groupedProductId, Ebizmarts_MailChimp_Model_Config::IS_PRODUCT, $mailchimpStoreId),
            array($oldProductId, Ebizmarts_MailChimp_Model_Config::IS_PRODUCT, $mailchimpStoreId),
            array($newProductId, Ebizmarts_MailChimp_Model_Config::IS_PRODUCT, $mailchimpStoreId)
        )->willReturnOnConsecutiveCalls(
            $syncDataItemMock,
            $syncDataItemMock,
            $syncDataItemMock
        );

        $productsApiMock->expects($this->exactly(3))->method('isBundleProduct')->withConsecutive(
            array($productMock),
            array($productMock),
            array($productMock)
        )->willReturnOnConsecutiveCalls(
            false,
            false,
            false
        );

        $productsApiMock->expects($this->exactly(3))->method('isGroupedProduct')->withConsecutive(
            array($productMock),
            array($productMock),
            array($productMock)
        )->willReturnOnConsecutiveCalls(
            true,
            false,
            false
        );

        $syncDataItemMock->expects($this->exactly(2))->method('getMailchimpSyncModified')->willReturnOnConsecutiveCalls(
            1,
            0
        );

        $syncDataItemMock->expects($this->exactly(2))->method('getMailchimpSyncDelta')->willReturnOnConsecutiveCalls(
            $itemOneSyncDelta,
            $itemTwoSyncDelta
        );

        $productsApiMock->expects($this->once())->method('_buildUpdateProductRequest')->with($productMock, $batchId, $mailchimpStoreId, $magentoStoreId)->willReturn(array());

        $productsApiMock->expects($this->once())->method('_buildNewProductRequest')->with($productMock, $batchId, $mailchimpStoreId, $magentoStoreId)->willReturn(array());

        $productsApiMock->expects($this->exactly(3))->method('_updateSyncData')->withConsecutive(
            array($groupedProductId, $mailchimpStoreId),
            array($oldProductId, $mailchimpStoreId),
            array($newProductId, $mailchimpStoreId)
        );

        $return = $productsApiMock->sendModifiedProduct($orderMock, $mailchimpStoreId, $magentoStoreId);

    }

    /**
     * @param array $data
     * @dataProvider getProductVariantDataDataProvider
     */

    public function testGetProductVariantData($data)
    {
        $magentoStoreId = 1;
        $sku = $data['sku'];
        $price = 100;
        $qty = 500;
        $backOrders = 0;
        $propertyVisibilityName = '_visibility';
        $propertyVisibilityValue = $data['propertyVisibilityValue'];
        $visibilityOptions = $data['visibilityOptions'];
        $finalSku = $data['finalSku'];

        $productsApiMock = $this->productsApiMock
            ->setMethods(array('getMailChimpProductPrice'))
            ->getMock();

        $productMock = $this->getMockBuilder(Mage_Catalog_Model_Product::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getSku', 'getQty', 'getBackorders'))
            ->getMock();


        $productsApiMock->expects($this->once())->method('getMailChimpProductPrice')->with($productMock, $magentoStoreId)->willReturn($price);

        $productMock->expects($this->once())->method('getSku')->willReturn($sku);
        $productMock->expects($this->once())->method('getQty')->willReturn($qty);
        $productMock->expects($this->once())->method('getBackorders')->willReturn($backOrders);

        $return = $this->invokeMethod(
            $productsApiMock,
            'getProductVariantData',
            array($productMock, $magentoStoreId, $propertyVisibilityName, $propertyVisibilityValue)
        );

        $this->assertEquals($visibilityOptions, $return['visibility']);
        $this->assertSame($finalSku, $return['sku']);
    }

    public function getProductVariantDataDataProvider()
    {
        return array(
            'Not Visible Individually' => array(
                array(
                    'sku' => 'PAK001',
                    'finalSku' => 'PAK001',
                    'propertyVisibilityValue' => 1,
                    'visibilityOptions' => 'Not Visible Individually'
                )),

            'Catalog' => array(
                array(
                    'sku' => null,
                    'finalSku' => '',
                    'propertyVisibilityValue' => 2,
                    'visibilityOptions' => 'Catalog'
                )),

            'Search' => array(
                array(
                    'sku' => 'PAK002',
                    'finalSku' => 'PAK002',
                    'propertyVisibilityValue' => 3,
                    'visibilityOptions' => 'Search'
                )),

            'Catalog Search' => array(
                array(
                    'sku' => null,
                    'finalSku' => '',
                    'propertyVisibilityValue' => 4,
                    'visibilityOptions' => 'Catalog, Search'
                ))
        );
    }

    public function testCreateDeletedProductsBatchJson()
    {
        $magentoStoreId = 0;
        $mailchimpStoreId = 'dasds231231312';
        $products = array();
        $data = array(456, 789, 123);

        $productsApiMock = $this->productsApiMock
            ->setMethods(array('getProductResourceCollection', 'joinMailchimpSyncDataDeleted',
                'makeBatchId', '_updateSyncData', '_buildDeleteProductRequest'))
            ->getMock();

        $productMock = $this->getMockBuilder(Mage_Catalog_Model_Product::class)
            ->disableOriginalConstructor()
            ->setMethods(
                array(
                    'getId'
                )
            )
            ->getMock();

        $productCollection = $this->getMockBuilder(Mage_Catalog_Model_Resource_Product_Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $productsApiMock->expects($this->once())->method('getProductResourceCollection')->willReturn($productCollection);
        $productsApiMock->expects($this->once())->method('joinMailchimpSyncDataDeleted')->with($mailchimpStoreId, $productCollection);
        $productsApiMock->expects($this->once())->method('makeBatchId')->with($magentoStoreId)->willReturn(self::BATCH_ID);
        $productsApiMock->expects($this->once())->method('_buildDeleteProductRequest')->with($productMock, self::BATCH_ID, $mailchimpStoreId, $magentoStoreId)->willReturn($data);

        $products [] = $productMock;
        $productCollection->expects($this->exactly(1))->method('getIterator')->willReturn(new ArrayIterator($products));

        $productsApiMock->expects($this->once())->method('_updateSyncData')->with($productMock->getId(), $mailchimpStoreId, null, 'This product was deleted because it is disabled in Magento.', null, null, 0);

        $productsApiMock->createDeletedProductsBatchJson($mailchimpStoreId, $magentoStoreId);

    }

    /**
     * Call protected/private method of a class.
     *
     * @param object &$object    Instantiated object that we will run method on.
     * @param string $methodName Method name to call
     * @param array  $parameters Array of parameters to pass into method.
     *
     * @return mixed Method return.
     */
    public function invokeMethod(&$object, $methodName, array $parameters = array())
    {
        $propertyName = $parameters[2];
        $propertyValue = $parameters[3];

        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        $reflectionProperty = $reflection->getParentClass()->getProperty($propertyName);
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($object, $propertyValue);

        return $method->invokeArgs($object, $parameters);
    }

    public function testUpdate()
    {
        $parentIdArray = array(282, 283, 284, 510, 511, 878, 880, 881);
        $productId = 877;
        $mailchimpStoreId = '3ade9d9e52e35e9b18d95bdd4d9e9a44';

        $productsApiMock = $this->productsApiMock
            ->setMethods(array('getAllParentIds', '_updateSyncData'))
            ->getMock();

        $productIdArrayMock = $this->getMockBuilder(ArrayObject::class)
            ->setMethods(array('getIterator'))
            ->getMock();

        $productsApiMock->expects($this->once())
            ->method('getAllParentIds')
            ->with($productId)
            ->willReturn($productIdArrayMock);


        $productIdArrayMock->expects($this->once())
            ->method('getIterator')
            ->willReturn(new ArrayIterator($parentIdArray));

        $productsApiMock->expects($this->exactly(9))
            ->method('_updateSyncData')
            ->withConsecutive(
                array($parentIdArray[0], $mailchimpStoreId, null, null, 1, null, null, true, false),
                array($parentIdArray[1], $mailchimpStoreId, null, null, 1, null, null, true, false),
                array($parentIdArray[2], $mailchimpStoreId, null, null, 1, null, null, true, false),
                array($parentIdArray[3], $mailchimpStoreId, null, null, 1, null, null, true, false),
                array($parentIdArray[4], $mailchimpStoreId, null, null, 1, null, null, true, false),
                array($parentIdArray[5], $mailchimpStoreId, null, null, 1, null, null, true, false),
                array($parentIdArray[6], $mailchimpStoreId, null, null, 1, null, null, true, false),
                array($parentIdArray[7], $mailchimpStoreId, null, null, 1, null, null, true, false),
                array($productId, $mailchimpStoreId, null, null, 1, null, null, true, false)
            );

        $productsApiMock->update($productId, $mailchimpStoreId);
    }

    public function testMarkSpecialPrices()
    {
        $mailchimpStoreId = '3ade9d9e52e35e9b18d95bdd4d9e9a44';
        $magentoStoreId = 1;
        $entityId = 145;
        $stringSpecialPrice = 'special_price';
        $arraySpecialPrice = array('notnull' => true);
        $joinTypeSpecialPrice = 'left';
        $stringSpecialFromDate = 'special_from_date';
        $arraySpecialFromDate = array('notnull' => true);
        $joinTypeSpecialFromDate = 'left';
        $stringSpecialToDate = 'special_to_date';
        $joinTypeSpecialToDate = 'left';

        $productsApiMock = $this->productsApiMock
            ->setMethods(array('getProductResourceCollection', 'update', 'joinMailchimpSyncData'))
            ->getMock();

        $collectiontMock = $this
            ->getMockBuilder(Mage_Catalog_Model_Resource_Product_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('addStoreFilter', 'addAttributeToFilter', 'getIterator', 'addAttributeToSelect'))
            ->getMock();

        $itemMock = $this
            ->getMockBuilder(Mage_Catalog_Model_Resource_Product::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getEntityId'))
            ->getMock();

        $productsApiMock->expects($this->once())
            ->method('getProductResourceCollection')
            ->willReturn($collectiontMock);
        $productsApiMock->expects($this->once())
            ->method('update')
            ->with($entityId, $mailchimpStoreId);
        $productsApiMock->expects($this->once())
            ->method('joinMailchimpSyncData')
            ->with($collectiontMock, $mailchimpStoreId, true);

        $collectiontMock->expects($this->once())
            ->method('addStoreFilter')
            ->with($magentoStoreId)
            ->willReturnSelf();
        $collectiontMock->expects($this->exactly(2))
            ->method('addAttributeToFilter')
            ->withConsecutive(
                array($stringSpecialPrice, $arraySpecialPrice, $joinTypeSpecialPrice),
                array($stringSpecialFromDate, $arraySpecialFromDate, $joinTypeSpecialFromDate)
            )->willReturnOnConsecutiveCalls(
                $collectiontMock,
                $collectiontMock
            );
        $collectiontMock->expects($this->once())
            ->method('addAttributeToSelect')
            ->with($stringSpecialToDate, $joinTypeSpecialToDate)
            ->willReturnSelf();
        $collectiontMock->expects($this->once())
            ->method('getIterator')
            ->willReturn(new ArrayIterator(array($itemMock)));

        $itemMock->expects($this->once())
            ->method('getEntityId')
            ->willReturn($entityId);

        $productsApiMock->_markSpecialPrices($mailchimpStoreId, $magentoStoreId);
    }

    public function testJoinMailchimpSyncDataMarkSpecialPrice()
    {
        $mailchimpStoreId = '';
        $isForSpecialPrice = true;
        $whereMarkSpecialPrice = "((IF(at_special_from_date.value_id > 0, at_special_from_date.value, at_special_from_date_default.value) <= '" . date('Y-m-d', time()) . " 23:59:59' AND m4m.mailchimp_sync_delta <  IF(at_special_from_date.value_id > 0, at_special_from_date.value, at_special_from_date_default.value)) OR (IF(at_special_to_date.value_id > 0, at_special_to_date.value, at_special_to_date_default.value) < '" . date('Y-m-d', time()) . "  00:00:00' AND m4m.mailchimp_sync_delta <  IF(at_special_to_date.value_id > 0, at_special_to_date.value, at_special_to_date_default.value)) AND mailchimp_sync_delta IS NOT NULL)";

        $productsApiMock = $this->productsApiMock
            ->setMethods(array('joinMailchimpSyncDataWithoutWhere'))
            ->getMock();

        $collectiontMock = $this
            ->getMockBuilder(Mage_Catalog_Model_Resource_Product_Collection::class)
            ->disableOriginalConstructor(array('getSelect'))
            ->getMock();

        $varienSelectMock = $this
            ->getMockBuilder(Varien_Db_Select::class)
            ->disableOriginalConstructor()
            ->setMethods(array('where'))
            ->getMock();

        $productsApiMock->expects($this->once())
            ->method('joinMailchimpSyncDataWithoutWhere')
            ->with($collectiontMock, $mailchimpStoreId, $isForSpecialPrice);

        $collectiontMock->expects($this->once())
            ->method('getSelect')
            ->willReturn($varienSelectMock);

        $varienSelectMock->expects($this->once())
            ->method('where')
            ->with($whereMarkSpecialPrice);

        $productsApiMock->joinMailchimpSyncData($collectiontMock, $mailchimpStoreId, $isForSpecialPrice);
    }

    public function testJoinMailchimpSyncDataCreateBatchJson()
    {
        $mailchimpStoreId = '';
        $isForSpecialPrice = false;
        $whereCreateBatchJson = "m4m.mailchimp_sync_delta IS null OR m4m.mailchimp_sync_modified = 1";

        $productsApiMock = $this->productsApiMock
            ->setMethods(array('joinMailchimpSyncDataWithoutWhere'))
            ->getMock();

        $collectiontMock = $this
            ->getMockBuilder(Mage_Catalog_Model_Resource_Product_Collection::class)
            ->disableOriginalConstructor(array('getSelect'))
            ->getMock();

        $varienSelectMock = $this
            ->getMockBuilder(Varien_Db_Select::class)
            ->disableOriginalConstructor()
            ->setMethods(array('where'))
            ->getMock();

        $productsApiMock->expects($this->once())
            ->method('joinMailchimpSyncDataWithoutWhere')
            ->with($collectiontMock, $mailchimpStoreId, $isForSpecialPrice);

        $collectiontMock->expects($this->once())
            ->method('getSelect')
            ->willReturn($varienSelectMock);

        $varienSelectMock->expects($this->once())
            ->method('where')
            ->with($whereCreateBatchJson);

        $productsApiMock->joinMailchimpSyncData($collectiontMock, $mailchimpStoreId, $isForSpecialPrice);
    }

    public function testMakeProductChildrenArray()
    {
        $magentoStoreId = 1;
        $isBuildUpdateProductRequest = false;
        $stringEntity = "entity_id";
        $isParentProduct = true;

        $productsApiMock = $this->productsApiMock
            ->setMethods(array('getConfigurableChildrenIds', 'makeProductsNotSentCollection'))
            ->getMock();

        $productMock = $this->getMockBuilder(Mage_Catalog_Model_Product::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getId'))
            ->getMock();

        $collectionMock = $this->getMockBuilder(Mage_Catalog_Model_Resource_Product_Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $childrenProductCollection = $this->getMockBuilder(Mage_Catalog_Model_Resource_Product_Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $productsApiMock->expects($this->once())
            ->method('getConfigurableChildrenIds')
            ->with($productMock)
            ->willReturn($childrenProductCollection);
        $productsApiMock->expects($this->once())
            ->method('makeProductsNotSentCollection')
            ->with($magentoStoreId, $isParentProduct)
            ->willReturn($collectionMock);

        $arrayEntity = array("in" => $childrenProductCollection);
        $collectionMock->expects($this->once())
            ->method('addAttributeToFilter')
            ->with($stringEntity, $arrayEntity)
            ->willReturnSelf();

        $collectionMock->expects($this->once())
            ->method('getIterator')
            ->willReturn(new ArrayIterator($childrenProductCollection));

        $variantProducts[] = $productMock;

        $productsApiMock->makeProductChildrenArray($productMock, $magentoStoreId, $isBuildUpdateProductRequest);
    }
}
