<?php

class Ebizmarts_MailChimp_Helper_DataTest extends PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        Mage::app('default');
    }

    public function testGetLastDateOfPurchase()
    {
        $emailAddress = "john@example.com";
        $lastDateOfPurchase = '2018-02-13 15:14:28';

        /**
         * @var \Ebizmarts_MailChimp_Helper_Data $helperMock
         */
        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getOrderCollectionByCustomerEmail'))
            ->getMock();

        $orderCollectionMock = $this->getMockBuilder(Mage_Sales_Model_Resource_Order::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getSize', 'setOrder', 'getFirstItem'))
            ->getMock();

        $orderMock = $this->getMockBuilder(Mage_Sales_Model_Order::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getCreatedAt'))
            ->getMock();

        $helperMock->expects($this->once())->method('getOrderCollectionByCustomerEmail')->with($emailAddress)
            ->willReturn($orderCollectionMock);

        $orderCollectionMock->expects($this->once())->method('getSize')->willReturn(1);
        $orderCollectionMock->expects($this->once())->method('setOrder')->with('created_at', 'DESC')->willReturnSelf();
        $orderCollectionMock->expects($this->once())->method('getFirstItem')->willReturn($orderMock);

        $orderMock->expects($this->once())->method('getCreatedAt')->willReturn($lastDateOfPurchase);

        $result = $helperMock->getLastDateOfPurchase($emailAddress);

        $this->assertEquals($result, $lastDateOfPurchase);
    }

    public function testCustomMergeFieldAlreadyExists()
    {
        /**
         * @var \Ebizmarts_MailChimp_Helper_Data $helperMock
         */
        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getCustomMergeFields'))
            ->getMock();

        $helperMock->expects($this->once())->method('getCustomMergeFields')->with(0, "default")
            ->willReturn(
                array(
                    array(
                        "value" => "FNAME"
                    )
                )
            );

        $this->assertTrue($helperMock->customMergeFieldAlreadyExists("FNAME", 0, "default"));
    }

    public function testIsCheckoutSubscribeEnabled()
    {
        $scopeId = 1;
        $scope = 'stores';
        /**
         * @var \Ebizmarts_MailChimp_Helper_Data $helperMock
         */
        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('isSubscriptionEnabled', 'getCheckoutSubscribeValue'))
            ->getMock();
        $helperMock->expects($this->once())->method('isSubscriptionEnabled')->with($scopeId, $scope)
            ->willReturn(true);

        $helperMock->expects($this->once())->method('getCheckoutSubscribeValue')->with($scopeId, $scope)
            ->willReturn(Ebizmarts_MailChimp_Model_System_Config_Source_Checkoutsubscribe::NOT_CHECKED_BY_DEFAULT);

        $this->assertTrue($helperMock->isCheckoutSubscribeEnabled($scopeId, $scope));
    }

    public function testAddResendFilter()
    {
        $storeId = 1;
        $lastItemSent = 100;
        /**
         * @var \Ebizmarts_MailChimp_Helper_Data $helperMock
         */
        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getResendEnabled', 'getResendTurn', 'getOrderResendLastId'))
            ->getMock();

        $collectionMock = $this->getMockBuilder(Mage_Sales_Model_Resource_Order_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('addFieldToFilter'))
            ->getMock();

        $helperMock->expects($this->once())->method('getResendEnabled')->with($storeId)->willReturn(1);
        $helperMock->expects($this->once())->method('getResendTurn')->with($storeId)->willReturn(1);
        $helperMock->expects($this->once())->method('getOrderResendLastId')->with($storeId)->willReturn($lastItemSent);

        $collectionMock->expects($this->once())->method('addFieldToFilter')->with('entity_id', array('lteq' => $lastItemSent));

        $helperMock->addResendFilter($collectionMock, $storeId, Ebizmarts_MailChimp_Model_Config::IS_ORDER);
    }

    public function testHandleResendFinish()
    {
        $scopeId = 1;
        $scope = 'stores';
        /**
         * @var \Ebizmarts_MailChimp_Helper_Data $helperMock
         */
        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('allResendItemsSent', 'deleteResendConfigValues'))
            ->getMock();

        $helperMock->expects($this->once())->method('allResendItemsSent')->with($scopeId, $scope)->willReturn(1);
        $helperMock->expects($this->once())->method('deleteResendConfigValues')->with($scopeId, $scope);

        $helperMock->handleResendFinish($scopeId, $scope);
    }

    public function testDeleteAllConfiguredMCStoreLocalData()
    {
        $scope = 'stores';
        $scopeId = 1;
        $mailchimpStoreId = 'a1s2d3f4g5h6j7k8l9n0';
        $where = "status = 'pending' AND store_id = $mailchimpStoreId";
        $tableName = 'mailchimp_sync_batches';
        $configValues = array(array(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_ACTIVE, 0));
        $storeIdOne = 1;
        $storeIdTwo = 2;
        $storeIdThree = 3;
        $storeIdsArray = array($storeIdOne, $storeIdTwo, $storeIdThree);

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getConfig', 'getCoreResource', 'saveMailchimpConfig', 'getAllStoresForScope'))
            ->getMock();

        $configMock = $this->getMockBuilder(Mage_Core_Model_Config::class)
            ->disableOriginalConstructor()
            ->setMethods(array('deleteConfig', 'cleanCache'))
            ->getMock();

        $coreResourceMock = $this->getMockBuilder(Mage_Core_Model_Resource::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getConnection', 'getTableName'))
            ->getMock();

        $dbAdapterInterfaceMock = $this->getMockForAbstractClass(Varien_Db_Adapter_Interface::class);


        $helperMock->expects($this->once())->method('saveMailchimpConfig')->with($configValues, $scopeId, $scope, false);
        $helperMock->expects($this->exactly(2))->method('getConfig')->willReturn($configMock);
        $helperMock->expects($this->once())->method('getAllStoresForScope')->with($scopeId, $scope)->willReturn($storeIdsArray);

        $param2 = array(Ebizmarts_MailChimp_Model_Config::GENERAL_MCISSYNCING. "_$mailchimpStoreId", 'stores', $storeIdOne);
        $param3 = array(Ebizmarts_MailChimp_Model_Config::GENERAL_MCISSYNCING. "_$mailchimpStoreId", 'stores', $storeIdTwo);
        $param4 = array(Ebizmarts_MailChimp_Model_Config::GENERAL_MCISSYNCING. "_$mailchimpStoreId", 'stores', $storeIdThree);
        $param5 = array(Ebizmarts_MailChimp_Model_Config::GENERAL_MCISSYNCING. "_$mailchimpStoreId", $scope, $scopeId);
        $param1 = array(Ebizmarts_MailChimp_Model_Config::GENERAL_MCSTOREID, $scope, $scopeId);
        $param6 = array(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_CUSTOMER_LAST_ID, $scope, $scopeId);
        $param7 = array(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_PRODUCT_LAST_ID, $scope, $scopeId);
        $param8 = array(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_ORDER_LAST_ID, $scope, $scopeId);
        $param9 = array(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_CART_LAST_ID, $scope, $scopeId);
        $param10 = array(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_PCD_LAST_ID, $scope, $scopeId);
        $param11 = array(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_RESEND_ENABLED, $scope, $scopeId);
        $param12 = array(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_RESEND_TURN, $scope, $scopeId);

        $configMock->expects($this->exactly(12))->method('deleteConfig')->withConsecutive($param1, $param2, $param3, $param4, $param5, $param6, $param7, $param8, $param9, $param10, $param11, $param12);

        $configMock->expects($this->once())->method('cleanCache');

        $helperMock->expects($this->once())->method('getCoreResource')->willReturn($coreResourceMock);

        $coreResourceMock->expects($this->once())->method('getConnection')->with('core_write')->willReturn($dbAdapterInterfaceMock);

        $coreResourceMock->expects($this->once())->method('getTableName')->willReturn($tableName);

        $dbAdapterInterfaceMock->expects($this->once())->method('quoteInto')->with("status = 'pending' AND store_id = ?", $mailchimpStoreId)->willReturn($where);
        $dbAdapterInterfaceMock->expects($this->once())->method('update')->with($tableName, array('status' => 'canceled'), $where);

        $helperMock->deleteAllConfiguredMCStoreLocalData($mailchimpStoreId, $scopeId, $scope);
    }

    public function testGetDateSyncFinishByStoreId()
    {
        $scope = 'default';
        $scopeId = 0;
        $mailchimpStoreId = 'a1s2d3f4g5h6j7k8l9n0';

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getMCStoreId', 'getConfigValueForScope'))
            ->getMock();

        $helperMock->expects($this->once())->method('getMCStoreId')->with($scopeId, $scope)->willReturn($mailchimpStoreId);
        $helperMock->expects($this->once())->method('getConfigValueForScope')->with(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_SYNC_DATE . "_$mailchimpStoreId", $scopeId, $scope);

        $helperMock->getDateSyncFinishByStoreId($scopeId, $scope);
    }

    public function testHandleResendDataBefore()
    {
        $scopeId = 0;
        $scope = 'default';
        $configMock = $this->getMockBuilder(Mage_Core_Model_Config_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getScope', 'getScopeId'))
            ->getMock();
        $configEntries = array();

        /**
         * @var \Ebizmarts_MailChimp_Helper_Data $helperMock
         */
        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getResendTurnConfigCollection', 'getResendTurn', 'getResendEnabled',
                'setIsSyncingIfFinishedPerScope', 'isEcomSyncDataEnabled'))
            ->getMock();

        $collectionMock = $this->getMockBuilder(Mage_Core_Model_Resource_Config_Data_Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $configMock->expects($this->once())->method('getScope')->willReturn($scope);
        $configMock->expects($this->once())->method('getScopeId')->willReturn($scopeId);
        $configEntries [] = $configMock;
        $collectionMock->expects($this->once())->method("getIterator")->willReturn(new ArrayIterator($configEntries));

        $helperMock->expects($this->once())->method('getResendTurnConfigCollection')->willReturn($collectionMock);
        $helperMock->expects($this->once())->method('getResendTurn')->with($scopeId, $scope)->willReturn(1);
        $helperMock->expects($this->once())->method('getResendEnabled')->with($scopeId, $scope)->willReturn(1);
        $helperMock->expects($this->once())->method('isEcomSyncDataEnabled')->with($scopeId, $scope)->willReturn(1);
        $helperMock->expects($this->once())->method('setIsSyncingIfFinishedPerScope')->with(true, $scopeId, $scope);

        $helperMock->handleResendDataBefore();
    }

    public function testHandleResendDataAfter()
    {
        $scopeId = 0;
        $scope = 'default';
        $configMock = $this->getMockBuilder(Mage_Core_Model_Config_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getScope', 'getScopeId'))
            ->getMock();
        $configEntries = array();

        /**
         * @var \Ebizmarts_MailChimp_Helper_Data $helperMock
         */
        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getResendTurnConfigCollection', 'getResendTurn', 'setIsSyncingIfFinishedPerScope',
                'setResendTurn', 'handleResendFinish', 'isEcomSyncDataEnabled'))
            ->getMock();

        $collectionMock = $this->getMockBuilder(Mage_Core_Model_Resource_Config_Data_Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $configMock->expects($this->once())->method('getScope')->willReturn($scope);
        $configMock->expects($this->once())->method('getScopeId')->willReturn($scopeId);
        $configEntries [] = $configMock;
        $collectionMock->expects($this->once())->method("getIterator")->willReturn(new ArrayIterator($configEntries));
        $helperMock->expects($this->once())->method('getResendTurnConfigCollection')->willReturn($collectionMock);
        $helperMock->expects($this->once())->method('getResendTurn')->with($scopeId, $scope)->willReturn(1);
        $helperMock->expects($this->once())->method('isEcomSyncDataEnabled')->with($scopeId, $scope)->willReturn(true);
        $helperMock->expects($this->once())->method('setIsSyncingIfFinishedPerScope')->with(false, $scopeId, $scope);

        $helperMock->expects($this->once())->method('setResendTurn')->with(0, $scopeId, $scope);
        $helperMock->expects($this->once())->method('handleResendFinish')->with($scopeId, $scope);

        $helperMock->handleResendDataAfter();
    }

    public function testSaveMailChimpConfig()
    {
        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getConfig'))
            ->getMock();

        $configMock = $this->getMockBuilder(Mage_Core_Model_Config_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('saveConfig', 'cleanCache'))
            ->getMock();


        $helperMock->expects($this->exactly(2))->method('getConfig')->willReturn($configMock);
        $configMock->expects($this->once())->method('saveConfig')->with(Ebizmarts_MailChimp_Model_Config::GENERAL_MIGRATE_FROM_116, 1, 'default', 0);
        $configMock->expects($this->once())->method('cleanCache');

        $helperMock->saveMailChimpConfig(array(array(Ebizmarts_MailChimp_Model_Config::GENERAL_MIGRATE_FROM_116, 1)), 0, 'default', true);
    }

    public function testHandleWebhookChange()
    {
        $scopeId = 0;
        $scope = 'default';
        $realScopeArray = array('scope_id' => 0, 'scope' => 'default');
        $listId = 'a1s2d3f4g5';

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getRealScopeForConfig', 'getGeneralList', 'deleteCurrentWebhook',
                'isSubscriptionEnabled', 'createNewWebhook'))
            ->getMock();

        $helperMock->expects($this->once())->method('getRealScopeForConfig')->with(Ebizmarts_MailChimp_Model_Config::GENERAL_LIST, $scopeId, $scope)->willReturn($realScopeArray);
        $helperMock->expects($this->once())->method('getGeneralList')->with($scopeId, $scope)->willReturn($listId);
        $helperMock->expects($this->once())->method('deleteCurrentWebhook')->with($realScopeArray['scope_id'], $realScopeArray['scope'], $listId);
        $helperMock->expects($this->once())->method('isSubscriptionEnabled')->with($scopeId, $scope)->willReturn(1);
        $helperMock->expects($this->once())->method('createNewWebhook')->with($scopeId, $scope, $listId);

        $helperMock->handleWebhookChange($scopeId, $scope);
    }

    /**
     * @param array $data
     * @dataProvider testGetImageUrlByIdDataProvider
     */

    public function testGetReSizedImageUrlById($data)
    {
        $productId = 1;
        $magentoStoreId = 1;
        $defaultStoreId = 0;
        $imageSize = $data['imageSize'];
        $imageUrl = 'http://magento.com/catalog/product/image.jpg';
        $configImageSize = $data['configImageSize'];

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getProductResourceModel', 'getProductModel', 'getImageSize', 'getCurrentStoreId',
                'setCurrentStore', 'getImageUrl', 'getImageUrlForSize'))
            ->getMock();

        $productModelMock = $this->getMockBuilder(Mage_Catalog_Model_Product::class)
            ->disableOriginalConstructor()
            ->setMethods(array('setData', 'getImageUrl', 'getThumbnailUrl', 'getSmallImageUrl'))
            ->getMock();

        $productResourceModelMock = $this->getMockBuilder(Mage_Catalog_Model_Resource_Product::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getAttributeRawValue'))
            ->getMock();

        $imageModelMock = $this->getMockBuilder(Mage_Media_Model_Image::class)
            ->disableOriginalConstructor()
            ->getMock();

        $helperMock->expects($this->once())->method('getProductResourceModel')->willReturn($productResourceModelMock);
        $helperMock->expects($this->once())->method('getProductModel')->willReturn($productModelMock);
        $helperMock->expects($this->once())->method('getImageSize')->with($magentoStoreId)->willReturn($configImageSize);

        $productResourceModelMock->expects($this->once())->method('getAttributeRawValue')->with($productId, $imageSize, $magentoStoreId)->willReturn($imageModelMock);

        $productModelMock->expects($this->once())->method('setData')->with($imageSize, $imageModelMock);

        $helperMock->expects($this->once())->method('getCurrentStoreId')->willReturn($defaultStoreId);
        $helperMock->expects($this->exactly(2))->method('setCurrentStore')->withConsecutive(array($magentoStoreId), array($defaultStoreId));

        $helperMock->expects($this->once())->method('getImageUrlForSize')->with($imageSize, $productModelMock)->willReturn($imageUrl);

        $return = $helperMock->getImageUrlById($productId, $magentoStoreId);

        $this->assertEquals($return, $imageUrl);
    }

    public function testGetImageUrlByIdDataProvider()
    {
        return array(
            array(array('imageSize' => Ebizmarts_MailChimp_Model_Config::IMAGE_SIZE_DEFAULT, 'configImageSize' => Ebizmarts_MailChimp_Helper_Data::DEFAULT_SIZE)),
            array(array('imageSize' => Ebizmarts_MailChimp_Model_Config::IMAGE_SIZE_SMALL, 'configImageSize' => Ebizmarts_MailChimp_Helper_Data::SMALL_SIZE)),
            array(array('imageSize' => Ebizmarts_MailChimp_Model_Config::IMAGE_SIZE_THUMBNAIL, 'configImageSize' => Ebizmarts_MailChimp_Helper_Data::THUMBNAIL_SIZE))
        );
    }

    public function testGetOriginalImageUrlById()
    {
        $productId = 1;
        $magentoStoreId = 1;
        $defaultStoreId = 0;
        $imageSize = Ebizmarts_MailChimp_Model_Config::IMAGE_SIZE_DEFAULT;
        $imageUrl = 'http://magento.com/catalog/product/image.jpg';
        $configImageSize = Ebizmarts_MailChimp_Helper_Data::ORIGINAL_SIZE;

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getProductResourceModel', 'getProductModel', 'getImageSize', 'getCurrentStoreId',
                'setCurrentStore', 'getImageUrl', 'getOriginalPath'))
            ->getMock();

        $productModelMock = $this->getMockBuilder(Mage_Catalog_Model_Product::class)
            ->disableOriginalConstructor()
            ->setMethods(array('setData', 'getImageUrl', 'getThumbnailUrl', 'getSmallImageUrl'))
            ->getMock();

        $productResourceModelMock = $this->getMockBuilder(Mage_Catalog_Model_Resource_Product::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getAttributeRawValue'))
            ->getMock();

        $imageModelMock = $this->getMockBuilder(Mage_Media_Model_Image::class)
            ->disableOriginalConstructor()
            ->getMock();

        $helperMock->expects($this->once())->method('getProductResourceModel')->willReturn($productResourceModelMock);
        $helperMock->expects($this->once())->method('getProductModel')->willReturn($productModelMock);
        $helperMock->expects($this->once())->method('getImageSize')->with($magentoStoreId)->willReturn($configImageSize);

        $productResourceModelMock->expects($this->once())->method('getAttributeRawValue')->with($productId, $imageSize, $magentoStoreId)->willReturn($imageModelMock);

        $productModelMock->expects($this->once())->method('setData')->with($imageSize, $imageModelMock);

        $helperMock->expects($this->once())->method('getCurrentStoreId')->willReturn($defaultStoreId);
        $helperMock->expects($this->exactly(2))->method('setCurrentStore')->withConsecutive(array($magentoStoreId), array($defaultStoreId));

        $helperMock->expects($this->once())->method('getOriginalPath')->with($imageModelMock)->willReturn($imageUrl);

        $return = $helperMock->getImageUrlById($productId, $magentoStoreId);

        $this->assertEquals($return, $imageUrl);
    }

    public function testGetImageFunctionName()
    {
        $imageSize = 'image_size';
        $imageArray = array('image', 'size');
        $upperCaseImage = 'ImageSize';
        $functionName = 'getImageSizeUrl';

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('setImageSizeVarToArray', 'setWordToCamelCase', 'setFunctionName'))
            ->getMock();

        $helperMock->expects($this->once())->method('setImageSizeVarToArray')->with($imageSize)->willReturn($imageArray);
        $helperMock->expects($this->once())->method('setWordToCamelCase')->with($imageArray)->willReturn($upperCaseImage);
        $helperMock->expects($this->once())->method('setFunctionName')->with($upperCaseImage)->willReturn($functionName);

        $result = $helperMock->getImageFunctionName($imageSize);

        $this->assertEquals($result, 'getImageSizeUrl');

    }

    public function testSetImageSizeVarToArray()
    {
        $imageSize = 'image_size';

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('setWordToCamelCase'))
            ->getMock();

        $result = $helperMock->setImageSizeVarToArray($imageSize);
        $this->assertEquals($result, array('image', 'size'));
    }

    public function testSetWordToCamelCase()
    {
        $imageArray = array('image', 'size');

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('setImageSizeVarToArray'))
            ->getMock();

        $result = $helperMock->setWordToCamelCase($imageArray);

        $this->assertEquals($result, 'ImageSize');
    }

    public function testSetFunctionName()
    {
        $upperCaseImage = 'ImageSize';

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('setImageSizeVarToArray'))
            ->getMock();

        $result = $helperMock->setFunctionName($upperCaseImage);

        $this->assertEquals($result, 'getImageSizeUrl');
    }

    public function testRemoveEcommerceSyncDataDeleteAll()
    {
        $scopeId = 0;
        $scope = 'default';
        $deleteErrorsOnly = false;
        $mailchimpStoreId = 'a1s2d3f4g5h6j7k8l9n0';
        $connectionType = 'core_write';
        $mailchimpEcommTableAlias = 'mailchimp/ecommercesyncdata';
        $mailchimpEcommTableName = 'mailchimp_ecommerce_sync_data';
        $where = "mailchimp_store_id = ".$mailchimpStoreId;
        $whereArray = "mailchimp_store_id = ?";

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getMCStoreId', 'getCoreResource'))
            ->getMock();

        $coreResourceMock = $this->getMockBuilder(Mage_Core_Model_Resource::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getConnection', 'getTableName'))
            ->getMock();

        $dbAdapterInterfaceMock = $this->getMockForAbstractClass(Varien_Db_Adapter_Interface::class);

        $helperMock->expects($this->once())->method('getMCStoreId')->with($scopeId, $scope)->WillReturn($mailchimpStoreId);
        $helperMock->expects($this->once())->method('getCoreResource')->WillReturn($coreResourceMock);
        $coreResourceMock->expects($this->once())->method('getConnection')->with($connectionType)->willReturn($dbAdapterInterfaceMock);
        $coreResourceMock->expects($this->once())->method('getTableName')->with($mailchimpEcommTableAlias)->willReturn($mailchimpEcommTableName);

        $dbAdapterInterfaceMock->expects($this->once())->method('quoteInto')->with($whereArray)->willReturn($where);

        $dbAdapterInterfaceMock->expects($this->once())->method('delete')->with($mailchimpEcommTableName, $where);

        $helperMock->removeEcommerceSyncData($scopeId, $scope, $deleteErrorsOnly);
    }

    public function testRemoveEcommerceSyncDataDeleteErrorsOnlyForDefaultScope()
    {
        $scopeId = 0;
        $scope = 'default';
        $deleteErrorsOnly = true;

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('removeAllEcommerceSyncDataErrors'))
            ->getMock();

        $helperMock->expects($this->once())->method('removeAllEcommerceSyncDataErrors');

        $helperMock->removeEcommerceSyncData($scopeId, $scope, $deleteErrorsOnly);
    }

    public function testRemoveEcommerceSyncDataErrorsOnlyForStoreView()
    {
        $scopeId = 1;
        $scope = 'stores';
        $deleteErrorsOnly = true;
        $mailchimpStoreId = 'a1s2d3f4g5h6j7k8l9n0';

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getMCStoreId', 'removeEcommerceSyncDataByMCStore'))
            ->getMock();

        $helperMock->expects($this->once())->method('getMCStoreId')->with($scopeId, $scope)->WillReturn($mailchimpStoreId);

        $helperMock->expects($this->once())->method('removeEcommerceSyncDataByMCStore')->with($mailchimpStoreId, $deleteErrorsOnly);

        $helperMock->removeEcommerceSyncData($scopeId, $scope, $deleteErrorsOnly);
    }

    public function testRemoveAllEcommerceSynddataErrors()
    {
        $tableName = 'mailchimp_ecommerce_sync_data';
        $tableAlias = 'mailchimp/ecommercesyncdata';
        $where = "mailchimp_sync_error != ''";

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getCoreResource', 'removeEcommerceSyncDataByMCStore'))
            ->getMock();

        $coreResourceMock = $this->getMockBuilder(Mage_Core_Model_Resource::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getConnection', 'getTableName'))
            ->getMock();

        $dbAdapterInterfaceMock = $this->getMockForAbstractClass(Varien_Db_Adapter_Interface::class);

        $helperMock->expects($this->once())->method('getCoreResource')->willReturn($coreResourceMock);

        $coreResourceMock->expects($this->once())->method('getConnection')->with('core_write')->willReturn($dbAdapterInterfaceMock);
        $coreResourceMock->expects($this->once())->method('getTableName')->with($tableAlias)->willReturn($tableName);

        $dbAdapterInterfaceMock->expects($this->once())->method('delete')->with($tableName, $where);

        $helperMock->removeAllEcommerceSyncDataErrors();
    }

    public function testRemoveEcommerceSyncDataByMCStore()
    {
        $deleteErrorsOnly = true;
        $mailchimpStoreId = 'a1s2d3f4g5h6j7k8l9p0';
        $tableName = 'mailchimp_ecommerce_sync_data';
        $tableAlias = 'mailchimp/ecommercesyncdata';
        $where = "mailchimp_store_id = $mailchimpStoreId AND mailchimp_sync_error != ''";

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getCoreResource'))
            ->getMock();

        $coreResourceMock = $this->getMockBuilder(Mage_Core_Model_Resource::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getConnection', 'getTableName'))
            ->getMock();

        $dbAdapterInterfaceMock = $this->getMockForAbstractClass(Varien_Db_Adapter_Interface::class);

        $helperMock->expects($this->once())->method('getCoreResource')->willReturn($coreResourceMock);

        $coreResourceMock->expects($this->once())->method('getConnection')->with('core_write')->willReturn($dbAdapterInterfaceMock);
        $coreResourceMock->expects($this->once())->method('getTableName')->with($tableAlias)->willReturn($tableName);

        $dbAdapterInterfaceMock->expects($this->once())->method('quoteInto')->with("mailchimp_store_id = ? AND mailchimp_sync_error != ''", $mailchimpStoreId)->willReturn($where);
        $dbAdapterInterfaceMock->expects($this->once())->method('delete')->with($tableName, $where);

        $helperMock->removeEcommerceSyncDataByMCStore($mailchimpStoreId, $deleteErrorsOnly);
    }

    public function testClearErrorGridExcludeSubscribers()
    {
        $scopeId = 0;
        $scope = 'default';
        $excludeSubscribers = true;
        $mailchimpStoreId = 'a1s2d3f4g5h6j7k8l9n0';
        $connectionType = 'core_write';
        $mailchimpEcommTableAlias = 'mailchimp/mailchimperrors';
        $mailchimpEcommTableName = 'mailchimp_errors';
        $where = array("mailchimp_store_id = ?" => $mailchimpStoreId);

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('handleOldErrors', 'getMCStoreId', 'getCoreResource'))
            ->getMock();

        $coreResourceMock = $this->getMockBuilder(Mage_Core_Model_Resource::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getConnection', 'getTableName'))
            ->getMock();

        $dbAdapterInterfaceMock = $this->getMockForAbstractClass(Varien_Db_Adapter_Interface::class);

        $helperMock->expects($this->once())->method('handleOldErrors');
        $helperMock->expects($this->once())->method('getMCStoreId')->with($scopeId, $scope)->WillReturn($mailchimpStoreId);
        $helperMock->expects($this->once())->method('getCoreResource')->WillReturn($coreResourceMock);
        $coreResourceMock->expects($this->once())->method('getConnection')->with($connectionType)->willReturn($dbAdapterInterfaceMock);
        $coreResourceMock->expects($this->once())->method('getTableName')->with($mailchimpEcommTableAlias)->willReturn($mailchimpEcommTableName);

        $dbAdapterInterfaceMock->expects($this->once())->method('delete')->with($mailchimpEcommTableName, $where);

        $helperMock->clearErrorGrid($scopeId, $scope, $excludeSubscribers);
    }

    public function testClearErrorGridIncludeSubscribersForDefaultScope()
    {
        $scopeId = 0;
        $scope = 'default';
        $excludeSubscribers = false;
        $mailchimpStoreId = 'a1s2d3f4g5h6j7k8l9n0';
        $connectionType = 'core_write';
        $mailchimpEcommTableAlias = 'mailchimp/mailchimperrors';
        $mailchimpEcommTableName = 'mailchimp_errors';
        $where = "";

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('handleOldErrors', 'getMCStoreId', 'getCoreResource'))
            ->getMock();

        $coreResourceMock = $this->getMockBuilder(Mage_Core_Model_Resource::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getConnection', 'getTableName'))
            ->getMock();

        $dbAdapterInterfaceMock = $this->getMockForAbstractClass(Varien_Db_Adapter_Interface::class);

        $helperMock->expects($this->once())->method('handleOldErrors');
        $helperMock->expects($this->once())->method('getMCStoreId')->with($scopeId, $scope)->WillReturn($mailchimpStoreId);
        $helperMock->expects($this->once())->method('getCoreResource')->WillReturn($coreResourceMock);
        $coreResourceMock->expects($this->once())->method('getConnection')->with($connectionType)->willReturn($dbAdapterInterfaceMock);
        $coreResourceMock->expects($this->once())->method('getTableName')->with($mailchimpEcommTableAlias)->willReturn($mailchimpEcommTableName);

        $dbAdapterInterfaceMock->expects($this->once())->method('delete')->with($mailchimpEcommTableName, $where);

        $helperMock->clearErrorGrid($scopeId, $scope, $excludeSubscribers);
    }

    public function testClearErrorGridIncludeSubscribersForStoreView()
    {
        $scopeId = 1;
        $scope = 'stores';
        $excludeSubscribers = false;
        $mailchimpStoreId = 'a1s2d3f4g5h6j7k8l9n0';
        $connectionType = 'core_write';
        $mailchimpEcommTableAlias = 'mailchimp/mailchimperrors';
        $mailchimpEcommTableName = 'mailchimp_errors';
        $where = array("store_id = ?" => $scopeId);

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('handleOldErrors', 'getMCStoreId', 'getCoreResource'))
            ->getMock();

        $coreResourceMock = $this->getMockBuilder(Mage_Core_Model_Resource::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getConnection', 'getTableName'))
            ->getMock();

        $dbAdapterInterfaceMock = $this->getMockForAbstractClass(Varien_Db_Adapter_Interface::class);

        $helperMock->expects($this->once())->method('handleOldErrors');
        $helperMock->expects($this->once())->method('getMCStoreId')->with($scopeId, $scope)->WillReturn($mailchimpStoreId);
        $helperMock->expects($this->once())->method('getCoreResource')->WillReturn($coreResourceMock);
        $coreResourceMock->expects($this->once())->method('getConnection')->with($connectionType)->willReturn($dbAdapterInterfaceMock);
        $coreResourceMock->expects($this->once())->method('getTableName')->with($mailchimpEcommTableAlias)->willReturn($mailchimpEcommTableName);

        $dbAdapterInterfaceMock->expects($this->once())->method('delete')->with($mailchimpEcommTableName, $where);

        $helperMock->clearErrorGrid($scopeId, $scope, $excludeSubscribers);
    }

    public function testIsNewApiKeyForSameAccount()
    {
        $oldApiKey = 'a1s2d3f4g5h6j7k8l9n0';
        $newApiKey = 'z9x8c7v6b5n4m3i2o1p0';
        $accountIdKey = 'account_id';
        $accountIdValue = '123456789';
        $accountIdArray = array($accountIdKey => $accountIdValue);

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getApiByKey'))
            ->getMock();

        $apiMock = $this->getMockBuilder(Ebizmarts_MailChimp::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getRoot'))
            ->getMock();

        $apiRootMock = $this->getMockBuilder(MailChimp_Root::class)
            ->disableOriginalConstructor()
            ->setMethods(array('info'))
            ->getMock();

        $helperMock->expects($this->exactly(2))->method('getApiByKey')->withConsecutive(
            array($oldApiKey),
            array($newApiKey)
        )->willReturnOnConsecutiveCalls(
            $apiMock,
            $apiMock);

        $apiMock->expects($this->exactly(2))->method('getRoot')->willReturn($apiRootMock);

        $apiRootMock->expects($this->exactly(2))->method('info')->with($accountIdKey)->willReturn($accountIdArray);

        $helperMock->isNewApiKeyForSameAccount($oldApiKey, $newApiKey);
    }

    public function testResendSubscribers()
    {
        $scopeId = 1;
        $scope = 'stores';
        $storeArray = array(1);
        $connectionType = 'core_write';
        $subscriberTableAlias = 'newsletter/subscriber';
        $subscriberTableName = 'newsletter_subscriber';
        $where = array("store_id = ?" => $scopeId);
        $setCondition = array('mailchimp_sync_delta' => '0000-00-00 00:00:00', 'mailchimp_sync_error' => '');

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getAllStoresForScope', 'getCoreResource'))
            ->getMock();

        $coreResourceMock = $this->getMockBuilder(Mage_Core_Model_Resource::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getConnection', 'getTableName'))
            ->getMock();

        $dbAdapterInterfaceMock = $this->getMockForAbstractClass(Varien_Db_Adapter_Interface::class);

        $helperMock->expects($this->once())->method('getAllStoresForScope')->with($scopeId, $scope)->willReturn($storeArray);

        $helperMock->expects($this->once())->method('getCoreResource')->WillReturn($coreResourceMock);
        $coreResourceMock->expects($this->once())->method('getConnection')->with($connectionType)->willReturn($dbAdapterInterfaceMock);
        $coreResourceMock->expects($this->once())->method('getTableName')->with($subscriberTableAlias)->willReturn($subscriberTableName);

        $dbAdapterInterfaceMock->expects($this->once())->method('update')->with($subscriberTableName, $setCondition, $where);

        $helperMock->resendSubscribers($scopeId, $scope);
    }

    public function testSaveLastItemsSent()
    {
        $mailchimpStoreId = 'a1s2d3f4g5h6j7k8l9p0';
        $scope = 'stores';
        $scopeId = 1;
        $customerLastId = 10;
        $productLastId = 10;
        $orderLastId = 10;
        $cartLastId = 10;
        $promoCodeLastId = 10;
        $configValues = array(
            array(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_CUSTOMER_LAST_ID, $customerLastId),
            array(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_PRODUCT_LAST_ID, $productLastId),
            array(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_ORDER_LAST_ID, $orderLastId),
            array(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_CART_LAST_ID, $cartLastId),
            array(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_PCD_LAST_ID, $promoCodeLastId),
            array(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_RESEND_ENABLED, 1),
            array(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_RESEND_TURN, 1)
        );

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getMCStoreId', 'getMCIsSyncing', 'getLastCustomerSent', 'getLastProductSent',
                'getLastOrderSent', 'getLastCartSent', 'getLastPromoCodeSent', 'saveMailchimpConfig'))
            ->getMock();

        $helperMock->expects($this->once())->method('getMCStoreId')->with($scopeId, $scope)->willReturn($mailchimpStoreId);
        $helperMock->expects($this->once())->method('getMCIsSyncing')->with($mailchimpStoreId, $scopeId, $scope)->willReturn(false);
        $helperMock->expects($this->once())->method('getLastCustomerSent')->with($scopeId, $scope)->willReturn($customerLastId);
        $helperMock->expects($this->once())->method('getLastProductSent')->with($scopeId, $scope)->willReturn($productLastId);
        $helperMock->expects($this->once())->method('getLastOrderSent')->with($scopeId, $scope)->willReturn($orderLastId);
        $helperMock->expects($this->once())->method('getLastCartSent')->with($scopeId, $scope)->willReturn($cartLastId);
        $helperMock->expects($this->once())->method('getLastPromoCodeSent')->with($scopeId, $scope)->willReturn($promoCodeLastId);
        $helperMock->expects($this->once())->method('saveMailchimpConfig')->with($configValues, $scopeId, $scope);

        $helperMock->saveLastItemsSent($scopeId, $scope);
    }

    /**
     * @param $data
     * @dataProvider scopeDataProvider
     */
    public function testIsEcomSyncDataEnabledWithStoreId($data)
    {
        $scopeId = $data['scopeId'];
        $scope = $data['scope'];
        $isStoreCreation = $data['isStoreCreation'];
        $mcStoreId = 'a1s2d3f4g5h6j7k8l9p0';

        $expectedResult = true;

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('isSubscriptionEnabled', 'isEcommerceEnabled', 'getMCStoreId'))
            ->getMock();

        $helperMock->expects($this->once())->method('isSubscriptionEnabled')->with($scopeId, $scope)->willReturn(true);
        $helperMock->expects($this->once())->method('isEcommerceEnabled')->with($scopeId, $scope)->willReturn(true);
        $helperMock->expects($this->once())->method('getMCStoreId')->with($scopeId, $scope)->willReturn($mcStoreId);

        $result = $helperMock->isEcomSyncDataEnabled($scopeId, $scope, $isStoreCreation);

        $this->assertEquals($expectedResult, $result);
    }

    public function scopeDataProvider()
    {

        return array(
            array(array('scopeId' => 1, 'scope' => 'stores', 'isStoreCreation' => false)),
            array(array('scopeId' => 1, 'scope' => 'stores', 'isStoreCreation' => true)),
            array(array('scopeId' => 1, 'scope' => 'websites', 'isStoreCreation' => false)),
            array(array('scopeId' => 0, 'scope' => 'admin', 'isStoreCreation' => false)),
        );
    }

    public function testIsEcomSyncDataEnabledWithStoreIdNull()
    {
        $scopeId = null;
        $scope = null;
        $isStoreCreation = false;
        $expectedResult = false;

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        $result = $helperMock->isEcomSyncDataEnabled($scopeId, $scope, $isStoreCreation);

        $this->assertEquals($expectedResult, $result);
    }

    public function testGetListInterestCategoriesByKeyAndList()
    {
        $apiKey = 'a1s2d3f4g5h6j7k8l9p0z1x2c3v4b5-us1';
        $listId = 'a1s2d3f4g5';
        $interestCategoryId = 1;
        $categoriesResponse = array('categories' => array(array('id' => $interestCategoryId, 'title' => 'Category Title', 'type' => 'checkbox')));

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getApiByKey'))
            ->getMock();

        $apiMock = $this->getMockBuilder(Ebizmarts_MailChimp::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getLists'))
            ->getMock();

        $apiListsMock = $this->getMockBuilder(Ebizmarts_MailChimp::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getInterestCategory'))
            ->getMock();

        $apiListsInterestCategoryMock = $this->getMockBuilder(MailChimp_ListsInterestCategory::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getAll'))
            ->getMock();

        $helperMock->expects($this->once())->method('getApiByKey')->with($apiKey)->willReturn($apiMock);

        $apiMock->expects($this->once())->method('getLists')->willReturn($apiListsMock);

        $apiListsMock->expects($this->once())->method('getInterestCategory')->willReturn($apiListsInterestCategoryMock);

        $apiListsInterestCategoryMock->expects($this->once())->method('getAll')->with($listId, 'categories')->willReturn($categoriesResponse);

        $helperMock->getListInterestCategoriesByKeyAndList($apiKey, $listId);
    }

    public function testGetListInterestGroups()
    {
        $scopeId = 1;
        $scope = 'stores';
        $listId = 'a1s2d3f4g5';
        $interestCategoryId = 1;
        $categoriesResponse = array('categories' => array(array('id' => $interestCategoryId, 'title' => 'Category Title', 'type' => 'checkbox')));
        $interestsResponse = array('interests' => array(array('id' => 2, 'name' => 'Group Name')));

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getApi', 'getGeneralList'))
            ->getMock();

        $apiMock = $this->getMockBuilder(Ebizmarts_MailChimp::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getLists'))
            ->getMock();

        $apiListsMock = $this->getMockBuilder(Ebizmarts_MailChimp::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getInterestCategory'))
            ->getMock();

        $apiListsInterestCategoryMock = $this->getMockBuilder(MailChimp_ListsInterestCategory::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getAll', 'getInterests'))
            ->getMock();

        $apiListsInterestCategoryInterestsMock = $this->getMockBuilder(MailChimp_ListInterestCategoryInterests::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getAll'))
            ->getMock();

        $helperMock->expects($this->once())->method('getApi')->with($scopeId, $scope)->willReturn($apiMock);
        $helperMock->expects($this->once())->method('getGeneralList')->with($scopeId, $scope)->willReturn($listId);

        $apiMock->expects($this->once())->method('getLists')->willReturn($apiListsMock);

        $apiListsMock->expects($this->once())->method('getInterestCategory')->willReturn($apiListsInterestCategoryMock);

        $apiListsInterestCategoryMock->expects($this->once())->method('getAll')->with($listId, 'categories')->willReturn($categoriesResponse);
        $apiListsInterestCategoryMock->expects($this->once())->method('getInterests')->willReturn($apiListsInterestCategoryInterestsMock);

        $apiListsInterestCategoryInterestsMock->expects($this->once())->method('getAll')->with($listId, $interestCategoryId)->willReturn($interestsResponse);

        $helperMock->getListInterestGroups($scopeId, $scope);
    }

    public function testGetInterest()
    {
        $scopeId = 1;
        $listId = 'a1s2d3f4g5';
        $interestIdOne = 'z0x9c8v7b6';
        $interestNameOne = 'Group One Name';
        $displayOrderOne = 1;
        $interestIdTwo = 'p4o5i6u7y8';
        $interestNameTwo = 'Group Two Name';
        $displayOrderTwo = 2;
        $localGroups = "$interestIdOne,$interestIdTwo";
        $interestCategoryId = 1;
        $categoriesResponse = array('categories' => array(array('id' => $interestCategoryId, 'title' => 'Category Title', 'type' => 'checkbox')));
        $interestsResponseOne = array('interests' => array(array('category_id' => $interestCategoryId, 'id' => $interestIdOne, 'name' => $interestNameOne, 'display_order' => $displayOrderOne)));
        $interestsResponseTwo = array('interests' => array(array('category_id' => $interestCategoryId, 'id' => $interestIdTwo, 'name' => $interestNameTwo, 'display_order' => $displayOrderTwo)));
        $expectedResult = array(1 => array('category' => array($displayOrderOne => array('id' => $interestIdOne, 'name' => $interestNameOne, 'checked' => false), $displayOrderTwo => array('id' => $interestIdTwo, 'name' => $interestNameTwo, 'checked' => false))));

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getApi', 'getGeneralList', 'getLocalInterestCategories'))
            ->getMock();

        $apiMock = $this->getMockBuilder(Ebizmarts_MailChimp::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getLists'))
            ->getMock();

        $apiListsMock = $this->getMockBuilder(Ebizmarts_MailChimp::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getInterestCategory'))
            ->getMock();

        $apiListsInterestCategoryMock = $this->getMockBuilder(MailChimp_ListsInterestCategory::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getAll', 'getInterests'))
            ->getMock();

        $apiListsInterestCategoryInterestsMock = $this->getMockBuilder(MailChimp_ListInterestCategoryInterests::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getAll'))
            ->getMock();

        $helperMock->expects($this->once())->method('getLocalInterestCategories')->with($scopeId)->willReturn($localGroups);
        $helperMock->expects($this->once())->method('getApi')->with($scopeId)->willReturn($apiMock);
        $helperMock->expects($this->once())->method('getGeneralList')->with($scopeId)->willReturn($listId);

        $apiMock->expects($this->once())->method('getLists')->willReturn($apiListsMock);

        $apiListsMock->expects($this->once())->method('getInterestCategory')->willReturn($apiListsInterestCategoryMock);

        $apiListsInterestCategoryMock->expects($this->once())->method('getAll')->with($listId)->willReturn($categoriesResponse);

        $apiListsInterestCategoryMock->expects($this->once())->method('getInterests')->willReturn($apiListsInterestCategoryInterestsMock);

        $apiListsInterestCategoryInterestsMock->expects($this->exactly(2))->method('getAll')->withConsecutive(
            array($listId, $interestIdOne),
            array($listId, $interestIdTwo)
        )->willReturnOnConsecutiveCalls(
            $interestsResponseOne,
            $interestsResponseTwo
        );

        $result = $helperMock->getInterest($scopeId);

        $this->assertEquals($expectedResult, $result);
    }

    public function testGetInterestGroups()
    {
        $customerId = 1;
        $subscriberId = 1;
        $storeId = 1;
        $interestGroupId = 1;
        $interestIdOne = 'z0x9c8v7b6';
        $interestNameOne = 'Group One Name';
        $displayOrderOne = 1;
        $interestIdTwo = 'p4o5i6u7y8';
        $interestNameTwo = 'Group Two Name';
        $displayOrderTwo = 2;
        $interest = array(1 => array('category' => array($displayOrderOne => array('id' => $interestIdOne, 'name' => $interestNameOne, 'checked' => false), $displayOrderTwo => array('id' => $interestIdTwo, 'name' => $interestNameTwo, 'checked' => false))));
        $encodedGroupData = '{"bc15dbe6a5":{"d6b7541ee7":"d6b7541ee7"},"2a2f23d671":"36c250eeff"}';
        $groupData = array(
            'bc15dbe6a5' => array('d6b7541ee7' => 'd6b7541ee7'),
            '2a2f23d671' => '36c250eeff'
        );
        $expectedResult = $interest;

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getInterest', 'getInterestGroupModel', 'getLocalInterestCategories', 'arrayDecode', 'isSubscriptionEnabled'))
            ->getMock();

        $interestGroupMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Interestgroup::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getByRelatedIdStoreId', 'getId', 'getGroupdata'))
            ->getMock();

        $helperMock->expects($this->once())->method('isSubscriptionEnabled')->with($storeId)->willReturn(true);
        $helperMock->expects($this->once())->method('getInterest')->with($storeId)->willReturn($interest);
        $helperMock->expects($this->once())->method('getInterestGroupModel')->willReturn($interestGroupMock);

        $interestGroupMock->expects($this->once())->method('getByRelatedIdStoreId')->with($customerId, $subscriberId, $storeId)->willReturnSelf();
        $interestGroupMock->expects($this->once())->method('getId')->willReturn($interestGroupId);
        $interestGroupMock->expects($this->once())->method('getGroupdata')->willReturn($encodedGroupData);

        $helperMock->expects($this->once())->method('arrayDecode')->with($encodedGroupData)->willReturn($groupData);

        $result = $helperMock->getInterestGroups($customerId, $subscriberId, $storeId);

        $this->assertEquals($expectedResult, $result);
    }

    public function testGetInterestGroupsIsSubscriptionDisabled()
    {
        $customerId = 1;
        $subscriberId = 1;
        $storeId = 1;
        $expectedResult = array();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('isSubscriptionEnabled'))
            ->getMock();

        $helperMock->expects($this->once())->method('isSubscriptionEnabled')->with($storeId)->willReturn(false);

        $result = $helperMock->getInterestGroups($customerId, $subscriberId, $storeId);

        $this->assertEquals($expectedResult, $result);
    }

    public function testSaveInterestGroupData()
    {
        $params = array();
        $customerId = 2;
        $subscriberId = 2;
        $origCustomerId = 1;
        $origSubscriberId = 1;
        $storeId = 1;
        $encodedGroupData = '{"bc15dbe6a5":{"d6b7541ee7":"d6b7541ee7"},"2a2f23d671":"36c250eeff"}';
        $groupData = array(
            'bc15dbe6a5' => array('d6b7541ee7' => 'd6b7541ee7'),
            '2a2f23d671' => '36c250eeff'
        );
        $currentDateTime = '2018-07-26 12:43:40';

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getInterestGroupsIfAvailable', 'isAdmin', 'getCustomerSession', 'getInterestGroupModel',
                'getCurrentDateTime', 'arrayEncode'))
            ->getMock();

        $interestGroupMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Interestgroup::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getByRelatedIdStoreId', 'getSubscriberId', 'getCustomerId', 'setSubscriberId',
                'setCustomerId', 'setGroupdata', 'getGroupdata', 'setStoreId', 'setUpdatedAt', 'save'))
            ->getMock();

        $subscriberMock = $this->getMockBuilder(Mage_Newsletter_Model_Subscriber::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getSubscriberId'))
            ->getMock();

        $customerSessionMock = $this->getMockBuilder(Mage_Customer_Model_Session::class)
            ->disableOriginalConstructor()
            ->setMethods(array('isLoggedIn', 'getCustomer'))
            ->getMock();

        $customerMock = $this->getMockBuilder(Mage_Customer_Model_Customer::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getId'))
            ->getMock();

        $helperMock->expects($this->once())->method('getInterestGroupsIfAvailable')->with($params)->willReturn($groupData);
        $helperMock->expects($this->once())->method('getCustomerSession')->willReturn($customerSessionMock);
        $helperMock->expects($this->once())->method('isAdmin')->willReturn(false);

        $customerSessionMock->expects($this->once())->method('isLoggedIn')->willReturn(true);
        $customerSessionMock->expects($this->once())->method('getCustomer')->willReturn($customerMock);

        $customerMock->expects($this->once())->method('getId')->willReturn($customerId);

        $subscriberMock->expects($this->once())->method('getSubscriberId')->willReturn($subscriberId);

        $helperMock->expects($this->once())->method('getInterestGroupModel')->willReturn($interestGroupMock);

        $interestGroupMock->expects($this->once())->method('getByRelatedIdStoreId')->with($customerId, $subscriberId, $storeId)->willReturnSelf();
        $interestGroupMock->expects($this->once())->method('getSubscriberId')->willReturn($origSubscriberId);
        $interestGroupMock->expects($this->once())->method('getCustomerId')->willReturn($origCustomerId);
        $interestGroupMock->expects($this->once())->method('setSubscriberId')->with($subscriberId)->willReturnSelf();
        $interestGroupMock->expects($this->once())->method('setCustomerId')->with($customerId)->willReturnSelf();

        $helperMock->expects($this->once())->method('arrayEncode')->with($groupData)->willReturn($encodedGroupData);

        $interestGroupMock->expects($this->once())->method('setGroupdata')->with($encodedGroupData)->willReturnSelf();
        $interestGroupMock->expects($this->once())->method('getGroupdata')->willReturn($encodedGroupData);
        $interestGroupMock->expects($this->once())->method('setStoreId')->with($storeId)->willReturnSelf();

        $helperMock->expects($this->once())->method('getCurrentDateTime')->willReturn($currentDateTime);

        $interestGroupMock->expects($this->once())->method('setUpdatedAt')->with($currentDateTime)->willReturnSelf();
        $interestGroupMock->expects($this->once())->method('save')->willReturnSelf();

        $helperMock->saveInterestGroupData($params, $storeId, null, $subscriberMock);
    }

    public function testGetMCJs()
    {
        $storeId = 1;
        $jsUrl = 'https://chimpstatic.com/mcjs-connected/js/users/1647ea7abc3f2f3259e2613f9/dffd1d29fea0323354a9caa32.js';
        $mailchimpStoreId = 'a1s2d3f4g5h6j7k8l9p0';

        $expectedResult = '<script type="text/javascript" src="' . $jsUrl . '" defer></script>';

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getMageApp', 'isEcomSyncDataEnabled', 'getConfigValueForScope',
                'retrieveAndSaveMCJsUrlInConfig', 'getMCStoreId'))
            ->getMock();

        $mageAppMock = $this->getMockBuilder(Mage_Core_Model_App::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getStore'))
            ->getMock();

        $storeMock = $this->getMockBuilder(Mage_Core_Model_Store::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getId'))
            ->getMock();

        $helperMock->expects($this->once())->method('getMageApp')->willReturn($mageAppMock);

        $mageAppMock->expects($this->once())->method('getStore')->willReturn($storeMock);

        $storeMock->expects($this->once())->method('getId')->willReturn($storeId);

        $helperMock->expects($this->once())->method('getMCStoreId')->with($storeId)->willReturn($mailchimpStoreId);
        $helperMock->expects($this->once())->method('isEcomSyncDataEnabled')->with($storeId)->willReturn(true);
        $helperMock->expects($this->once())->method('getConfigValueForScope')->with(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_MC_JS_URL . "_$mailchimpStoreId", 0, 'default')->willReturn(null);
        $helperMock->expects($this->once())->method('retrieveAndSaveMCJsUrlInConfig')->with($storeId)->willReturn($jsUrl);

        $result = $helperMock->getMCJs();

        $this->assertEquals($expectedResult, $result);
    }

    public function testGetAllApiKeys()
    {
        $apiKey = 'a1s2d3f4g5h6j7k8l9-us1';
        $storeIdOne = 1;
        $storeIdTwo = 2;
        $storeIdThree = 3;

        $storeMock = $this->getMockBuilder(Mage_Core_Model_Store::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getId'))
            ->getMock();
        $stores = array($storeIdOne => $storeMock, $storeIdTwo => $storeMock, $storeIdThree => $storeMock);

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getMageApp', 'getApiKey'))
            ->getMock();

        $mageAppMock = $this->getMockBuilder(Mage_Core_Model_App::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getStores'))
            ->getMock();

        $helperMock->expects($this->once())->method('getMageApp')->willReturn($mageAppMock);

        $mageAppMock->expects($this->once())->method('getStores')->willReturn($stores);

        $helperMock->expects($this->exactly(3))->method('getApiKey')->withConsecutive(
            array($storeIdOne),
            array($storeIdTwo),
            array($storeIdThree)
        )->willReturnOnConsecutiveCalls(
            $apiKey,
            $apiKey,
            $apiKey
        );

        $helperMock->getAllApiKeys();
    }

    public function testHandleDeleteMigrationConfigData()
    {
        $arrayMigrationConfigData = array('115' => true, '116' => true, '1164' => true);

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('delete115MigrationConfigData', 'delete116MigrationConfigData', 'delete1164MigrationConfigData', 'getConfig'))
            ->getMock();

        $modelConfigMock = $this->getMockBuilder(Mage_Core_Model_Config::class)
            ->disableOriginalConstructor()
            ->setMethods(array('cleanCache'))
            ->getMock();

        $helperMock->expects($this->once())->method('delete115MigrationConfigData');
        $helperMock->expects($this->once())->method('delete116MigrationConfigData');
        $helperMock->expects($this->once())->method('delete1164MigrationConfigData');
        $helperMock->expects($this->once())->method('getConfig')->willReturn($modelConfigMock);

        $modelConfigMock->expects($this->once())->method('cleanCache');

        $helperMock->handleDeleteMigrationConfigData($arrayMigrationConfigData);

    }
}
