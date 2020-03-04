<?php

class Ebizmarts_MailChimp_Model_Api_ProductsTest extends PHPUnit_Framework_TestCase
{
    protected $_productsApiMock;

    const BATCH_ID = 'storeid-0_PRO_2017-05-18-14-45-54-38849500';

    const PRODUCT_ID = 603;

    public function setUp()
    {
        Mage::app('default');

        /**
         * @var Ebizmarts_MailChimp_Model_Api_Products $apiProductsMock productsApiMock
         */
        $this->_productsApiMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Products::class);
    }

    public function tearDown()
    {
        $this->_productsApiMock = null;
    }

    public function testCreateBatchJson()
    {
        $magentoStoreId = 0;
        $mailchimpStoreId = 'dasds231231312';
        $oldStore = $magentoStoreId;
        $products = array();
        $productId = 15;
        $syncDataItemId = 2;
        $productData = array(
            'method' => 'POST',
            'path' => "/ecommerce/stores/$mailchimpStoreId/products",
            'operation_id' => self::BATCH_ID,
            'body' => '{"id":"906","title":"test Prod","url":"http:\/\/127.0.0.1\/mcmagento-1937'
                . '\/test-prod.html","published_at_foreign":"","description":"Test",'
                . '"type":"Default Category","vendor":"Default Category","handle":"","variants":['
                . '{"id":"906","title":"test Prod","url":"http:\/\/127.0.0.1\/mcmagento-1937'
                . '\/test-prod.html","published_at_foreign":"","sku":"testprod","price":10,'
                . '"inventory_quantity":1000,"backorders":"0","visibility":"Catalog, Search"}]}'
        );

        $productMock = $this->getMockBuilder(Mage_Catalog_Model_Product::class)
            ->disableOriginalConstructor()->setMethods(array('getId'))->getMock();

        $productCollectionResourceMock = $this
            ->getMockBuilder(Ebizmarts_MailChimp_Model_Resource_Ecommercesyncdata_Product_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('setMailchimpStoreId', 'setStoreId'))
            ->getMock();

        $productCollection = $this->getMockBuilder(Mage_Catalog_Model_Resource_Product_Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $productsApiMock = $this->_productsApiMock
            ->setMethods(
                array(
                    'getMailchimpStoreId', 'getMagentoStoreId', 'createEcommerceProductsCollection',
                    'getHelper', 'getDateHelper', 'isProductFlatTableEnabled', '_markSpecialPrices',
                    'makeProductsNotSentCollection', 'joinMailchimpSyncData', 'makeBatchId', 'shouldSendProductUpdate',
                    '_buildUpdateProductRequest', '_getBatchCounter', '_buildNewProductRequest',
                    'getMailchimpEcommerceSyncDataModel', 'addSyncData', 'addSyncDataError'
                )
            )->getMock();

       $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
           ->disableOriginalConstructor()
            ->setMethods(
                array(
                    'getCurrentStoreId', 'setCurrentStore', 'modifyCounterSentPerBatch',
                    'getMageApp'
                )
            )->getMock();

        $syncDataItemMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Ecommercesyncdata::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getId', 'getEcommerceSyncDataItem'))
            ->getMock();

        $productsApiMock->expects($this->once())->method("isProductFlatTableEnabled")->willReturn(false);
        $productsApiMock->expects($this->once())->method("getHelper")->willReturn($helperMock);

        $productsApiMock->expects($this->once())->method("getMailchimpStoreId")->willReturn($mailchimpStoreId);
        $productsApiMock->expects($this->once())->method("getMagentoStoreId")->willReturn($magentoStoreId);
        $productsApiMock->expects($this->once())->method("createEcommerceProductsCollection")
            ->willReturn($productCollectionResourceMock);


        $productCollectionResourceMock->expects($this->once())->method("setMailchimpStoreId")->with($mailchimpStoreId);
        $productCollectionResourceMock->expects($this->once())->method("setStoreId")->with($magentoStoreId);

        $productsApiMock->expects($this->once())->method('_markSpecialPrices');

        $productsApiMock
            ->expects($this->once())
            ->method('makeProductsNotSentCollection')
            ->willReturn($productCollection);

        $productsApiMock
            ->expects($this->once())
            ->method('joinMailchimpSyncData')
            ->with($productCollection);

        $productsApiMock
            ->expects($this->once())
            ->method('makeBatchId')
            ->with($magentoStoreId)
            ->willReturn(self::BATCH_ID);

        $syncDataItemMock->expects($this->once())
            ->method('getEcommerceSyncDataItem')
            ->with(15, Ebizmarts_MailChimp_Model_Config::IS_PRODUCT, $mailchimpStoreId)
            ->willReturnSelf();

        $productMock
            ->expects($this->once())
            ->method('getId')
            ->willReturn($productId);

        $products[] = $productMock;
        $productCollection->expects($this->once())->method("getIterator")->willReturn(new ArrayIterator($products));

        $productsApiMock
            ->expects($this->once())
            ->method('shouldSendProductUpdate')
            ->with($productMock)
            ->willReturn(false);

        $productsApiMock
            ->expects($this->once())
            ->method('_buildNewProductRequest')
            ->with($productMock, self::BATCH_ID)
            ->willReturn($productData);

        $helperMock->expects($this->once())
            ->method('getCurrentStoreId')
            ->willReturn($magentoStoreId);

        $helperMock->expects($this->exactly(2))
            ->method('setCurrentStore')
            ->withConsecutive(
                array($magentoStoreId),
                array($oldStore)
            );

        $productsApiMock
            ->expects($this->once())
            ->method('getMailchimpEcommerceSyncDataModel')
            ->willReturn($syncDataItemMock);

        $helperMock->expects($this->once())
            ->method('modifyCounterSentPerBatch')
            ->with(Ebizmarts_MailChimp_Helper_Data::PRO_MOD);

        $syncDataItemMock->expects($this->once())->method('getId')->willReturn($syncDataItemId);

        $return = $productsApiMock->createBatchJson();

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
        $magentoStoreId = 1;
        $this->_productsApiMock = $this->_productsApiMock->setMethods(
            array(
                'createEcommerceProductsCollection',
                'getMagentoStoreId',
                'getHelper',
                'getProductResourceCollection',
                'getBatchLimitFromConfig',
            )
        )->getMock();

        $productsCollectionMock = $this
            ->getMockBuilder(Mage_Catalog_Model_Resource_Product_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('addFinalPrice', 'addStoreFilter'))
            ->getMock();

        $helperMock = $this
            ->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('addResendFilter'))
            ->getMock();

        $productResourceCollectionMock = $this
            ->getMockBuilder(Ebizmarts_MailChimp_Model_Resource_Ecommercesyncdata_Product_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('joinQtyAndBackorders', 'limitCollection'))
            ->getMock();

        $this->_productsApiMock->expects($this->once())->method('getProductResourceCollection')
            ->willReturn($productsCollectionMock);

        $this->_productsApiMock->expects($this->once())->method('getMagentoStoreId')
            ->willReturn($magentoStoreId);

        $productsCollectionMock->expects($this->once())->method('addFinalPrice');
        $productsCollectionMock->expects($this->once())->method('addStoreFilter')->with($magentoStoreId);

        $this->_productsApiMock->expects($this->once())->method('getHelper')->willReturn($helperMock);

        $helperMock->expects($this->once())->method('addResendFilter')
            ->with($productsCollectionMock, $magentoStoreId, Ebizmarts_MailChimp_Model_Config::IS_PRODUCT);

        $this->_productsApiMock->expects($this->once())->method('createEcommerceProductsCollection')
            ->willReturn($productResourceCollectionMock);

        $productResourceCollectionMock->expects($this->once())->method('joinQtyAndBackorders');
        $this->_productsApiMock->expects($this->once())->method('getBatchLimitFromConfig')->willReturn(100);
        $productResourceCollectionMock->expects($this->once())->method('limitCollection');

        $this->_productsApiMock->makeProductsNotSentCollection(false);
    }

    public function testGetNotVisibleProductUrl()
    {
        $childId = 1;
        $parentId = 2;
        $magentoStoreId = 1;
        $path = 'path';
        $url = 'url/path';

        $productsApiMock = $this->_productsApiMock
            ->setMethods(array('getHelper', 'getParentId', 'getProductWithAttributesById', 'getUrlByPath'))
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

        $productsApiMock->expects($this->once())->method('getHelper')->willReturn($mailChimpHelperMock);
        $productsApiMock->expects($this->once())->method('getParentId')->with($childId)->willReturn($parentId);
        $productsApiMock
            ->expects($this->once())
            ->method('getProductWithAttributesById')
            ->willReturn($productResourceCollectionMock);

        $productsApiMock
            ->expects($this->once())
            ->method('getUrlByPath')
            ->with($path, $magentoStoreId)
            ->willReturn($url);

        $productResourceCollectionMock
            ->expects($this->once())
            ->method("getIterator")
            ->willReturn(new ArrayIterator(array()));

        $mailChimpHelperMock
            ->expects($this->once())
            ->method('getProductResourceModel')
            ->willReturn($productResourceMock);

        $productResourceMock
            ->expects($this->once())
            ->method('getAttributeRawValue')
            ->with($parentId, 'url_path', $magentoStoreId)
            ->willReturn($path);

        $return = $productsApiMock->getNotVisibleProductUrl($childId, $magentoStoreId);

        $this->assertEquals($return, $url);
    }

    public function testGetParentImageUrl()
    {
        $childId = 1;
        $parentId = 2;
        $magentoStoreId = 1;

        $imageUrl = 'imageUrl';

        $productsApiMock = $this->_productsApiMock
            ->setMethods(array('getParentId', 'getHelper'))
            ->getMock();

        $mailChimpHelperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getImageUrlById'))
            ->getMock();

        $productsApiMock->expects($this->once())->method('getParentId')->with($childId)->willReturn($parentId);
        $productsApiMock->expects($this->once())->method('getHelper')->willReturn($mailChimpHelperMock);

        $mailChimpHelperMock
            ->expects($this->once())
            ->method('getImageUrlById')
            ->with($parentId, $magentoStoreId)
            ->willReturn($imageUrl);

        $return = $productsApiMock->getParentImageUrl($childId, $magentoStoreId);

        $this->assertEquals($return, $imageUrl);
    }

    public function testGetProductCategories()
    {
        $catArray = array(13, 14);
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

        $productsApiMock = $this->_productsApiMock
            ->setMethods(array('makeCatalogCategory'))
            ->getMock();

        $categoryMockGeneric = $this->getMockBuilder(Mage_Catalog_Model_Category::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getCollection'))
            ->getMock();

        $categoryCollectionMock = $this->getMockBuilder(Mage_Catalog_Model_Resource_Category_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(
                array(
                    'addAttributeToSelect',
                    'setStoreId',
                    'addAttributeToFilter',
                    'addAttributeToSort',
                    'getIterator'
                )
            )
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
        $categoryCollectionMock
            ->expects($this->once())
            ->method('addAttributeToSelect')
            ->with(array('name'))
            ->willReturnSelf();
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
        $categoryCollectionMock
            ->expects($this->once())
            ->method("getIterator")
            ->willReturn(new ArrayIterator($categories));

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
        $error = "This product type is not supported on MailChimp. (product id: $groupedProductId)";

        $productsApiMock = $this->_productsApiMock
            ->setMethods(
                array(
                        'getMailchimpStoreId', 'getMagentoStoreId',
                        'makeBatchId', 'loadProductById', 'isGroupedProduct', 'getMailchimpEcommerceSyncDataModel',
                        'isBundleProduct', '_buildUpdateProductRequest', '_buildNewProductRequest', 'isProductEnabled',
                        'getDateHelper', 'addSyncData', 'addSyncDataError'
                )
            )
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

        $dateHelperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Date::class)
            ->disableOriginalConstructor()
            ->setMethods(array('formatDate'))
            ->getMock();

        $syncDataItemMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Ecommercesyncdata::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getMailchimpSyncModified', 'getMailchimpSyncDelta', 'getEcommerceSyncDataItem'))
            ->getMock();

        $productsApiMock->expects($this->once())->method('getMailchimpStoreId')->willReturn($mailchimpStoreId);
        $productsApiMock->expects($this->once())->method('getMagentoStoreId')->willReturn($magentoStoreId);
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

        $dateHelperMock->expects($this->once())
            ->method('formatDate')
            ->with(null, 'Y-m-d H:i:s')
            ->willReturn($ecomSyncDateFlag);

        $productsApiMock->expects($this->once())->method('getDateHelper')->willReturn($dateHelperMock);

        $productsApiMock->expects($this->exactly(2))
            ->method('isProductEnabled')
            ->withConsecutive(
                array($oldProductId),
                array($newProductId)
            )->willReturnOnConsecutiveCalls(
                true,
                false
            );
        $productsApiMock->expects($this->exactly(3))
            ->method('getMailchimpEcommerceSyncDataModel')
            ->willReturn($syncDataItemMock);

        $syncDataItemMock->expects($this->exactly(3))->method('getEcommerceSyncDataItem')->withConsecutive(
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

        $syncDataItemMock->expects($this->exactly(2))->method('getMailchimpSyncModified')
            ->willReturnOnConsecutiveCalls(1, 0);

        $syncDataItemMock->expects($this->exactly(2))->method('getMailchimpSyncDelta')
            ->willReturnOnConsecutiveCalls($itemOneSyncDelta, $itemTwoSyncDelta);

        $productsApiMock
            ->expects($this->once())
            ->method('_buildUpdateProductRequest')
            ->with($productMock, $batchId)
            ->willReturn(array());

        $productsApiMock
            ->expects($this->once())
            ->method('_buildNewProductRequest')
            ->with($productMock, $batchId)
            ->willReturn(array());

        $productsApiMock
            ->expects($this->once())
            ->method('addSyncDataError')
            ->with($groupedProductId, $error, null, null, $ecomSyncDateFlag);

        $productsApiMock
            ->expects($this->once())
            ->method('addSyncData')
            ->withConsecutive($newProductId);

        $productsApiMock->sendModifiedProduct($orderMock);
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

        $productsApiMock = $this->_productsApiMock
            ->setMethods(array('getMailChimpProductPrice'))
            ->getMock();

        $productMock = $this->getMockBuilder(Mage_Catalog_Model_Product::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getSku', 'getQty', 'getBackorders'))
            ->getMock();


        $productsApiMock
            ->expects($this->once())
            ->method('getMailChimpProductPrice')
            ->with($productMock)
            ->willReturn($price);

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

        $productsApiMock = $this->_productsApiMock
            ->setMethods(
                array(
                    'getMailchimpStoreId', 'getMagentoStoreId', 'getProductResourceCollection',
                    'getEcommerceProductsCollection', 'getBatchLimitFromConfig', 'makeBatchId',
                    '_buildDeleteProductRequest', 'addSyncDataError'
                )
            )->getMock();

        $productCollectionResourceMock = $this->
        getMockBuilder(Ebizmarts_MailChimp_Model_Resource_Ecommercesyncdata_Product_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('joinMailchimpSyncDataDeleted'))
            ->getMock();

        $productMock = $this->getMockBuilder(Mage_Catalog_Model_Product::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getId'))
            ->getMock();

        $productCollection = $this->getMockBuilder(Mage_Catalog_Model_Resource_Product_Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $productsApiMock
            ->expects($this->once())
            ->method('getMailchimpStoreId')
            ->willReturn($mailchimpStoreId);

        $productsApiMock
            ->expects($this->once())
            ->method('getMagentoStoreId')
            ->willReturn($magentoStoreId);

        $productsApiMock
            ->expects($this->once())
            ->method('getProductResourceCollection')
            ->willReturn($productCollection);

        $productsApiMock
            ->expects($this->once())
            ->method('getEcommerceProductsCollection')
            ->willReturn($productCollectionResourceMock);

        $productsApiMock->expects($this->once())->method('getBatchLimitFromConfig')->willReturn(null);

        $productCollectionResourceMock
            ->expects($this->once())
            ->method('joinMailchimpSyncDataDeleted')
            ->with($productCollection, null);

        $productsApiMock
            ->expects($this->once())
            ->method('makeBatchId')->with($magentoStoreId)
            ->willReturn(self::BATCH_ID);

        $products [] = $productMock;
        $productCollection->expects($this->exactly(1))->method('getIterator')->willReturn(new ArrayIterator($products));

        $productsApiMock
            ->expects($this->once())
            ->method('_buildDeleteProductRequest')
            ->with($productMock, self::BATCH_ID, $mailchimpStoreId)
            ->willReturn($data);

        $productsApiMock->createDeletedProductsBatchJson();
    }

    /**
     * Call protected/private method of a class.
     *
     * @param object    &$object Instantiated object that we will run method on.
     * @param string    $methodName Method name to call
     * @param array     $parameters Array of parameters to pass into method.
     *
     * @return mixed Method return.
     */
    public function invokeMethod(&$object, $methodName, array $parameters = array())
    {
        $propertyName = $parameters[2];
        $propertyValue = $parameters[3];

        $reflection = new ReflectionClass(get_class($object));
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

        $productsApiMock = $this->_productsApiMock
            ->setMethods(array('getAllParentIds', 'markSyncDataAsModified'))
            ->getMock();

        $productIdArrayMock = $this->getMockBuilder(ArrayObject::class)
            ->disableOriginalConstructor()
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
            ->method('markSyncDataAsModified')
            ->withConsecutive(
                array($parentIdArray[0]),
                array($parentIdArray[1]),
                array($parentIdArray[2]),
                array($parentIdArray[3]),
                array($parentIdArray[4]),
                array($parentIdArray[5]),
                array($parentIdArray[6]),
                array($parentIdArray[7]),
                array($productId)
            );

        $productsApiMock->update($productId);
    }

    public function testMarkSpecialPrices()
    {
        $magentoStoreId = 1;
        $entityId = 145;
        $dateToday = '2019-07-25';
        $whereCondition = 'm4m.mailchimp_sync_delta IS NOT NULL '
            . "AND m4m.mailchimp_sync_delta < '$dateToday 00:00:00'";

        $helperDateMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Date::class)
            ->disableOriginalConstructor()
            ->setMethods(array('formatDate'))
            ->getMock();

        $coreResourceMock = $this->getMockBuilder(Mage_Core_Model_Resource::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getConnection'))
            ->getMock();

        $writeAdapterMock = $this->getMockBuilder(Varien_Db_Adapter_Pdo_Mysql::class)
            ->disableOriginalConstructor()
            ->setMethods(array('quoteInto'))
            ->getMock();

        $productsApiMock = $this->_productsApiMock
            ->setMethods(
                array(
                    'getCoreResource', 'getMagentoStoreId', 'getProductResourceCollection',
                    'joinMailchimpSyncDataForSpecialPrices', 'getDateHelper',
                    'update', 'getEcommerceProductsCollection'
                )
            )->getMock();

        $productsCollectionResourceMock = $this->
        getMockBuilder(Ebizmarts_MailChimp_Model_Resource_Ecommercesyncdata_Product_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('addWhere'))
            ->getMock();

        $productsCollectionMock = $this
            ->getMockBuilder(Mage_Catalog_Model_Resource_Product_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('addStoreFilter', 'addAttributeToFilter', 'getIterator'))
            ->getMock();

        $itemMock = $this
            ->getMockBuilder(Mage_Catalog_Model_Resource_Product::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getEntityId'))
            ->getMock();

        $productsApiMock
            ->expects($this->once())
            ->method('getCoreResource')
            ->willReturn($coreResourceMock);

        $coreResourceMock
            ->expects($this->once())
            ->method('getConnection')
            ->with('core_write')
            ->willReturn($writeAdapterMock);

        $productsApiMock->expects($this->once())
            ->method('getMagentoStoreId')
            ->willReturn($magentoStoreId);

        $productsApiMock->expects($this->exactly(2))
            ->method('getProductResourceCollection')
            ->willReturn($productsCollectionMock);

        $productsCollectionMock->expects($this->exactly(2))
            ->method('addStoreFilter')
            ->with($magentoStoreId);

        $productsApiMock->expects($this->exactly(2))
            ->method('joinMailchimpSyncDataForSpecialPrices')
            ->with($productsCollectionMock);

        $productsCollectionMock->expects($this->exactly(6))
            ->method('addAttributeToFilter')
            ->withConsecutive(
                array('special_price', array('gt' => 0), 'left'),
                array('special_from_date',
                    array('lteq' => $dateToday . " 23:59:59"),
                    'left'),
                array('special_from_date', array('gt' => new Zend_Db_Expr('m4m.mailchimp_sync_delta')), 'left'),
                array('special_price', array('gt' => 0), 'left'),
                array('special_to_date',
                    array('lt' => $dateToday . " 00:00:00"),
                    'left'),
                array('special_to_date', array('gt' => new Zend_Db_Expr('m4m.mailchimp_sync_delta')), 'left')
            )->willReturnOnConsecutiveCalls(
                $productsCollectionMock,
                $productsCollectionMock,
                $productsCollectionMock,
                $productsCollectionMock,
                $productsCollectionMock,
                $productsCollectionMock
            );

        $productsApiMock
            ->expects($this->exactly(3))
            ->method('getDateHelper')
            ->willReturn($helperDateMock);

        $helperDateMock
            ->expects($this->exactly(3))
            ->method('formatDate')
            ->willReturn($dateToday);

        $writeAdapterMock
            ->expects($this->once())
            ->method("quoteInto")
            ->with(
                'm4m.mailchimp_sync_delta IS NOT NULL AND m4m.mailchimp_sync_delta < ?',
                $dateToday . " 00:00:00"
            )->willReturn($whereCondition);

        $productsApiMock
            ->expects($this->once())
            ->method('getEcommerceProductsCollection')
            ->willReturn($productsCollectionResourceMock);

        $productsCollectionResourceMock->expects($this->exactly(2))
            ->method('addWhere')
            ->withConsecutive(
                array($productsCollectionMock, $whereCondition),
                array($productsCollectionMock, $whereCondition)
            );

        $productsApiMock->expects($this->exactly(2))
            ->method('update')
            ->with($entityId);

        $productsCollectionMock->expects($this->exactly(2))
            ->method('getIterator')
            ->willReturn(new ArrayIterator(array($itemMock)));

        $itemMock->expects($this->exactly(2))
            ->method('getEntityId')
            ->willReturn($entityId);

        $productsApiMock->_markSpecialPrices();
    }

    public function testJoinMailchimpSyncData()
    {
        $joinCondition = "m4m.related_id = e.entity_id AND m4m.type = '%s' AND m4m.mailchimp_store_id = '%s'";

        $productsApiMock = $this->_productsApiMock
            ->setMethods(array("buildMailchimpDataJoin", "executeMailchimpDataJoin", "buildMailchimpDataWhere"))
            ->getMock();

        $collectionMock = $this->getMockBuilder(Mage_Catalog_Model_Resource_Product_Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $productsApiMock->expects($this->once())
            ->method('buildMailchimpDataJoin')
            ->willReturn($joinCondition);

        $productsApiMock->expects($this->once())
            ->method('executeMailchimpDataJoin')
            ->with($collectionMock, $joinCondition);

        $productsApiMock->expects($this->once())
            ->method('buildMailchimpDataWhere')
            ->with($collectionMock);

        $productsApiMock->joinMailchimpSyncData($collectionMock);
    }


    public function testMakeProductChildrenArray()
    {
        $magentoStoreId = 1;
        $isBuildUpdateProductRequest = false;
        $stringEntity = "entity_id";
        $isParentProduct = true;

        $productsApiMock = $this->_productsApiMock
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
            ->with($isParentProduct)
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
