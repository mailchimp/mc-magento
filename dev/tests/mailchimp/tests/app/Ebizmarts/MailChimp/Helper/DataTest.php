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

    public function testDeleteStore()
    {
        $scopeId = 1;
        $scope = 'stores';
        $mailchimpStoreId = 'a18a1a8a1aa7aja1a';
        $apiKey = '123456789aa123456789bb123456789c-us13';
        $listId = 'listId';

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getMCStoreId', 'getApiStores', 'getGeneralList', 'deleteCurrentWebhook',
                'deleteLocalMCStoreData', 'getApiKey'))
            ->getMock();

        $apiStoresMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Stores::class)
            ->disableOriginalConstructor()
            ->setMethods(array('deleteMailChimpStore'))
            ->getMock();

        $helperMock->expects($this->once())->method('getMCStoreId')->with($scopeId, $scope)->willReturn($mailchimpStoreId);
        $helperMock->expects($this->once())->method('getApiKey')->with($scopeId, $scope)->willReturn($apiKey);
        $helperMock->expects($this->once())->method('getApiStores')->willReturn($apiStoresMock);

        $apiStoresMock->expects($this->once())->method('deleteMailChimpStore')->with($mailchimpStoreId, $scopeId, $scope);

        $helperMock->expects($this->once())->method('getGeneralList')->with($scopeId, $scope)->willReturn($listId);
        $helperMock->expects($this->once())->method('deleteCurrentWebhook')->with($scopeId, $scope, $listId);
        $helperMock->expects($this->once())->method('deleteLocalMCStoreData')->with($mailchimpStoreId, $scopeId, $scope);

        $helperMock->deleteStore($scopeId, $scope);
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

    public function testDeleteLocalMCStoreData()
    {
        $scope = 'default';
        $scopeId = 0;
        $mailchimpStoreId = 'a1s2d3f4g5h6j7k8l9n0';

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getConfig'))
            ->getMock();

        $configMock = $this->getMockBuilder(Mage_Core_Model_Config::class)
            ->disableOriginalConstructor()
            ->setMethods(array('deleteConfig', 'cleanCache'))
            ->getMock();

        $helperMock->expects($this->once())->method('getConfig')->willReturn($configMock);

        $param1 = array(Ebizmarts_MailChimp_Model_Config::GENERAL_MCSTOREID, $scope, $scopeId);
        $param2 = array(Ebizmarts_MailChimp_Model_Config::GENERAL_MCISSYNCING, $scope, $scopeId);
        $param3 = array(Ebizmarts_MailChimp_Model_Config::GENERAL_ECOMMMINSYNCDATEFLAG, $scope, $scopeId);
        $param4 = array(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_MC_JS_URL, $scope, $scopeId);
        $param5 = array(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_CUSTOMER_LAST_ID, $scope, $scopeId);
        $param6 = array(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_PRODUCT_LAST_ID, $scope, $scopeId);
        $param7 = array(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_ORDER_LAST_ID, $scope, $scopeId);
        $param8 = array(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_CART_LAST_ID, $scope, $scopeId);
        $param9 = array(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_PCD_LAST_ID, $scope, $scopeId);
        $param10 = array(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_RESEND_ENABLED, $scope, $scopeId);
        $param11 = array(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_RESEND_TURN, $scope, $scopeId);
        $param12 = array(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_SYNC_DATE . "_$mailchimpStoreId", 'default', 0);

        $configMock->expects($this->exactly(12))->method('deleteConfig')->withConsecutive($param1, $param2, $param3, $param4, $param5, $param6, $param7, $param8, $param9, $param10, $param11, $param12);
        $configMock->expects($this->once())->method('cleanCache');

        $helperMock->deleteLocalMCStoreData($mailchimpStoreId, $scopeId, $scope);
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

    public function testResetMCEcommerceData()
    {
        $scopeId = 0;
        $scope = 'default';
        $deleteDataInMailchimp = true;

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getGeneralList', 'getMCStoreId', 'removeEcommerceSyncData', 'resetCampaign', 'clearErrorGrid', 'deleteStore', 'isEcomSyncDataEnabled'))
            ->getMock();

        $helperMock->expects($this->once())->method('getGeneralList')->with($scopeId, $scope)->willReturn('a1s2d3f4g5');
        $helperMock->expects($this->once())->method('getMCStoreId')->with($scopeId, $scope)->willReturn('q1w2e3r4t5y6u7i8o9p0');
        $helperMock->expects($this->once())->method('removeEcommerceSyncData')->with($scopeId, $scope);
        $helperMock->expects($this->once())->method('resetCampaign')->with($scopeId, $scope);
        $helperMock->expects($this->once())->method('clearErrorGrid')->with($scopeId, $scope, true);
        $helperMock->expects($this->once())->method('deleteStore')->with($scopeId, $scope);

        $helperMock->resetMCEcommerceData($scopeId, $scope, $deleteDataInMailchimp);
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

        $helperMock->saveMailChimpConfig(array(array(Ebizmarts_MailChimp_Model_Config::GENERAL_MIGRATE_FROM_116, 1)), 0, 'default');
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

    public function testCreateWebhookIfRequired()
    {
        $scopeId = 0;
        $scope = 'default';
        $webhookId = null;

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getWebhookId', 'handleWebhookChange', 'isSubscriptionEnabled'))
            ->getMock();

        $helperMock->expects($this->once())->method('getWebhookId')->with($scopeId, $scope)->willReturn($webhookId);
        $helperMock->expects($this->once())->method('isSubscriptionEnabled')->with($scopeId, $scope)->willReturn(1);
        $helperMock->expects($this->once())->method('handleWebhookChange')->with($scopeId, $scope);

        $helperMock->createWebhookIfRequired($scopeId, $scope);
    }

    public function testGetImageUrlById()
    {
        $productId = 1;
        $magentoStoreId = 1;
        $defaultStoreId = 0;
        $imageSize = 'image';
        $upperCaseImage = 'getImageUrl';

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getProductResourceModel', 'getProductModel', 'getImageSize', 'getCurrentStoreId', 'setCurrentStore', 'getImageFunctionName'))
            ->getMock();

        $productModelMock = $this->getMockBuilder(Mage_Catalog_Model_Product::class)
            ->disableOriginalConstructor()
            ->setMethods(array('setData', 'getImageUrl'))
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
        $helperMock->expects($this->once())->method('getImageSize')->with($magentoStoreId)->willReturn($imageSize);

        $productResourceModelMock->expects($this->once())->method('getAttributeRawValue')->with($productId, $imageSize, $magentoStoreId)->willReturn($imageModelMock);

        $productModelMock->expects($this->once())->method('setData')->with($imageSize, $imageModelMock);
        $productModelMock->expects($this->once())->method('getImageUrl')->willReturn('ImageUrl');

        $helperMock->expects($this->once())->method('getCurrentStoreId')->willReturn($defaultStoreId);

        $helperMock->expects($this->exactly(2))->method('setCurrentStore')->withConsecutive(array($magentoStoreId), array($defaultStoreId));

        $helperMock->expects($this->once())->method('getImageFunctionName')->with($imageSize)->willReturn($upperCaseImage);

        $return = $helperMock->getImageUrlById($productId, $magentoStoreId);

        $this->assertEquals($return, 'ImageUrl');
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

    public function testRemoveEcommerceSyncDataDeleteErrorsOnly()
    {
        $scopeId = 0;
        $scope = 'default';
        $deleteErrorsOnly = false;
        $mailchimpStoreId = 'a1s2d3f4g5h6j7k8l9n0';
        $connectionType = 'core_write';
        $mailchimpEcommTableAlias = 'mailchimp/ecommercesyncdata';
        $mailchimpEcommTableName = 'mailchimp_ecommerce_sync_data';
        $where = array("mailchimp_store_id = ?" => $mailchimpStoreId);

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

        $dbAdapterInterfaceMock->expects($this->once())->method('delete')->with($mailchimpEcommTableName, $where);

        $helperMock->removeEcommerceSyncData($scopeId, $scope, $deleteErrorsOnly);
    }

    public function testRemoveEcommerceSyncDataDeleteAllForDefaultScope()
    {
        $scopeId = 0;
        $scope = 'default';
        $deleteErrorsOnly = true;
        $mailchimpStoreId = 'a1s2d3f4g5h6j7k8l9n0';
        $connectionType = 'core_write';
        $mailchimpEcommTableAlias = 'mailchimp/ecommercesyncdata';
        $mailchimpEcommTableName = 'mailchimp_ecommerce_sync_data';
        $where = "mailchimp_sync_error != ''";

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

        $dbAdapterInterfaceMock->expects($this->once())->method('delete')->with($mailchimpEcommTableName, $where);

        $helperMock->removeEcommerceSyncData($scopeId, $scope, $deleteErrorsOnly);
    }

    public function testRemoveEcommerceSyncDataDeleteAllForStoreView()
    {
        $scopeId = 1;
        $scope = 'stores';
        $deleteErrorsOnly = true;
        $mailchimpStoreId = 'a1s2d3f4g5h6j7k8l9n0';
        $connectionType = 'core_write';
        $mailchimpEcommTableAlias = 'mailchimp/ecommercesyncdata';
        $mailchimpEcommTableName = 'mailchimp_ecommerce_sync_data';
        $where = array("mailchimp_store_id = ? and mailchimp_sync_error != ''" => $mailchimpStoreId);

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

        $dbAdapterInterfaceMock->expects($this->once())->method('delete')->with($mailchimpEcommTableName, $where);

        $helperMock->removeEcommerceSyncData($scopeId, $scope, $deleteErrorsOnly);
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

    public function testGetMCStoreNameForStore()
    {
        $scopeId = 1;
        $scope = 'stores';
        $storeGroupName = 'StoreName';
        $storeViewName = 'StoreViewName';

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getConfigValueForScope', 'getMageApp'))
            ->getMock();

        $mageAppMock = $this->getMockBuilder(Mage_Core_Model_App::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getStore'))
            ->getMock();

        $storeMock = $this->getMockBuilder(Mage_Core_Model_Store::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getGroup', 'getName'))
            ->getMock();

        $groupMock = $this->getMockBuilder(Mage_Core_Model_Store_Group::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getName'))
            ->getMock();

        $helperMock->expects($this->once())->method('getConfigValueForScope')->with(Mage_Core_Model_Store::XML_PATH_STORE_STORE_NAME, $scopeId, $scope)->willReturn('');
        $helperMock->expects($this->once())->method('getMageApp')->willReturn($mageAppMock);

        $mageAppMock->expects($this->once())->method('getStore')->with($scopeId)->willReturn($storeMock);

        $storeMock->expects($this->once())->method('getGroup')->willReturn($groupMock);

        $storeMock->expects($this->once())->method('getName')->willReturn($storeViewName);
        $groupMock->expects($this->once())->method('getName')->willReturn($storeGroupName);

        $result = $helperMock->getMCStoreName($scopeId, $scope);

        $this->assertEquals($result, $storeGroupName . ' - ' . $storeViewName);

    }

    public function testGetMCStoreNameForWebsite()
    {
        $scopeId = 1;
        $scope = 'websites';
        $storeName = 'StoreName';

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getConfigValueForScope', 'getMageApp'))
            ->getMock();

        $mageAppMock = $this->getMockBuilder(Mage_Core_Model_App::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getWebsite'))
            ->getMock();

        $websiteMock = $this->getMockBuilder(Mage_Core_Model_Website::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getName'))
            ->getMock();

        $helperMock->expects($this->once())->method('getConfigValueForScope')->with(Mage_Core_Model_Store::XML_PATH_STORE_STORE_NAME, $scopeId, $scope)->willReturn('');
        $helperMock->expects($this->once())->method('getMageApp')->willReturn($mageAppMock);

        $mageAppMock->expects($this->once())->method('getWebsite')->with($scopeId)->willReturn($websiteMock);

        $websiteMock->expects($this->once())->method('getName')->willReturn($storeName);

        $result = $helperMock->getMCStoreName($scopeId, $scope);

        $this->assertEquals($result, $storeName);
    }

    public function testGetMCStoreNameForDefault()
    {
        $scopeId = 0;
        $scope = 'default';
        $storeName = 'StoreName';

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getConfigValueForScope'))
            ->getMock();

        $helperMock->expects($this->exactly(2))->method('getConfigValueForScope')->withConsecutive(
            array(Mage_Core_Model_Store::XML_PATH_STORE_STORE_NAME, $scopeId, $scope),
            array('web/unsecure/base_url', 0)
        )->willReturnOnConsecutiveCalls(
            '',
            $storeName
        );

        $result = $helperMock->getMCStoreName($scopeId, $scope);

        $this->assertEquals($result, $storeName);
    }

    public function testIsUsingConfigStoreName()
    {
        $scopeId = 1;
        $scope = 'stores';
        $storeName = 'StoreName';

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getConfigValueForScope'))
            ->getMock();

        $helperMock->expects($this->once())->method('getConfigValueForScope')->with(Mage_Core_Model_Store::XML_PATH_STORE_STORE_NAME, $scopeId, $scope)->willReturn($storeName);

        $result = $helperMock->isUsingConfigStoreName($scopeId, $scope);

        $this->assertEquals($result, true);
    }

    public function testChangeStoreNameIfRequired()
    {
        $scopeId = 1;
        $scope = 'stores';
        $mailchimpStoreId = 'a1s2d3f4g5h6j7k8l9n0';
        $realScope = array('scope' => $scope, 'scope_id' => $scopeId);
        $configStoreName = '';
        $groupStoreName = 'StoreName';

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getMCStoreId', 'getRealScopeForConfig', 'isEcomSyncDataEnabled',
                'getConfigValueForScope', 'changeName'))
            ->getMock();

        $helperMock->expects($this->once())->method('getMCStoreId')->with($scopeId, $scope)->willReturn($mailchimpStoreId);
        $helperMock->expects($this->once())->method('getRealScopeForConfig')->with(Ebizmarts_MailChimp_Model_Config::GENERAL_MCSTOREID, $scopeId, $scope)->willReturn($realScope);
        $helperMock->expects($this->once())->method('isEcomSyncDataEnabled')->with($scopeId, $scope)->willReturn(true);
        $helperMock->expects($this->once())->method('getConfigValueForScope')->with(Mage_Core_Model_Store::XML_PATH_STORE_STORE_NAME, $realScope['scope_id'], $realScope['scope'])->willReturn($configStoreName);
        $helperMock->expects($this->once())->method('changeName')->with($groupStoreName, $scopeId, $scope)->willReturn(true);

        $helperMock->changeStoreNameIfRequired($groupStoreName, $scopeId, $scope);
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

    public function testResetCampaign()
    {
        $scopeId = 1;
        $scope = 'stores';
        $connectionType = 'core_write';
        $orderTableAlias = 'sales/order';
        $orderTableName = 'sales_flat_order';
        $whereString = "mailchimp_campaign_id IS NOT NULL AND (store_id = 1)";
        $where = array($whereString);
        $setCondition = array('mailchimp_campaign_id' => NULL);

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getCoreResource', 'makeWhereString'))
            ->getMock();

        $coreResourceMock = $this->getMockBuilder(Mage_Core_Model_Resource::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getConnection', 'getTableName'))
            ->getMock();

        $dbAdapterInterfaceMock = $this->getMockForAbstractClass(Varien_Db_Adapter_Interface::class);


        $helperMock->expects($this->once())->method('getCoreResource')->WillReturn($coreResourceMock);

        $coreResourceMock->expects($this->once())->method('getConnection')->with($connectionType)->willReturn($dbAdapterInterfaceMock);

        $helperMock->expects($this->once())->method('makeWhereString')->with($dbAdapterInterfaceMock, $scopeId, $scope)->willReturn($whereString);

        $coreResourceMock->expects($this->once())->method('getTableName')->with($orderTableAlias)->willReturn($orderTableName);

        $dbAdapterInterfaceMock->expects($this->once())->method('update')->with($orderTableName, $setCondition, $where);

        $helperMock->resetCampaign($scopeId, $scope);
    }

    public function testSaveLastItemsSent()
    {
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
            ->setMethods(array('getMCIsSyncing', 'getLastCustomerSent', 'getLastProductSent', 'getLastOrderSent',
                'getLastCartSent', 'getLastPromoCodeSent', 'saveMailchimpConfig'))
            ->getMock();

        $helperMock->expects($this->once())->method('getMCIsSyncing')->with($scopeId, $scope)->willReturn(false);
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
}
