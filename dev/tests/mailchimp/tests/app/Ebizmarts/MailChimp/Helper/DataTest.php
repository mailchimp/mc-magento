<?php

class Ebizmarts_MailChimp_Helper_DataTest extends PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        Mage::app('default');
    }

    public function testCustomMergeFieldAlreadyExists()
    {
        /**
         * @var Ebizmarts_MailChimp_Helper_Data $helperMock
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
         * @var Ebizmarts_MailChimp_Helper_Data $helperMock
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
         * @var Ebizmarts_MailChimp_Helper_Data $helperMock
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

        $collectionMock
            ->expects($this->once())
            ->method('addFieldToFilter')
            ->with('entity_id', array('lteq' => $lastItemSent));

        $helperMock->addResendFilter($collectionMock, $storeId, Ebizmarts_MailChimp_Model_Config::IS_ORDER);
    }

    public function testHandleResendFinish()
    {
        $scopeId = 1;
        $scope = 'stores';
        /**
         * @var Ebizmarts_MailChimp_Helper_Data $helperMock
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


        $helperMock
            ->expects($this->once())
            ->method('saveMailchimpConfig')
            ->with($configValues, $scopeId, $scope, false);
        $helperMock->expects($this->exactly(2))->method('getConfig')->willReturn($configMock);
        $helperMock
            ->expects($this->once())
            ->method('getAllStoresForScope')
            ->with($scopeId, $scope)
            ->willReturn($storeIdsArray);

        $params = array();
        $params ['param2'] = array(Ebizmarts_MailChimp_Model_Config::GENERAL_MCISSYNCING
            . "_$mailchimpStoreId", 'stores', $storeIdOne);
        $params ['param3'] = array(Ebizmarts_MailChimp_Model_Config::GENERAL_MCISSYNCING
            . "_$mailchimpStoreId", 'stores', $storeIdTwo);
        $params ['param4'] = array(Ebizmarts_MailChimp_Model_Config::GENERAL_MCISSYNCING
            . "_$mailchimpStoreId", 'stores', $storeIdThree);
        $params ['param5'] = array(Ebizmarts_MailChimp_Model_Config::GENERAL_MCISSYNCING
            . "_$mailchimpStoreId", $scope, $scopeId);
        $params ['param1'] = array(Ebizmarts_MailChimp_Model_Config::GENERAL_MCSTOREID, $scope, $scopeId);
        $params ['param6'] = array(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_CUSTOMER_LAST_ID, $scope, $scopeId);
        $params ['param7'] = array(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_PRODUCT_LAST_ID, $scope, $scopeId);
        $params ['param8'] = array(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_ORDER_LAST_ID, $scope, $scopeId);
        $params ['param9'] = array(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_CART_LAST_ID, $scope, $scopeId);
        $params ['param10'] = array(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_PCD_LAST_ID, $scope, $scopeId);
        $params ['param11'] = array(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_RESEND_ENABLED, $scope, $scopeId);
        $params ['param12'] = array(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_RESEND_TURN, $scope, $scopeId);

        $configMock
            ->expects($this->exactly(12))
            ->method('deleteConfig')
            ->withConsecutive(
                $params ['param1'], $params ['param2'], $params ['param3'], $params ['param4'], $params ['param5'],
                $params ['param6'], $params ['param7'], $params ['param8'], $params ['param9'], $params ['param10'],
                $params ['param11'], $params ['param12']
            );

        $configMock->expects($this->once())->method('cleanCache');

        $helperMock->expects($this->once())->method('getCoreResource')->willReturn($coreResourceMock);

        $coreResourceMock
            ->expects($this->once())
            ->method('getConnection')
            ->with('core_write')
            ->willReturn($dbAdapterInterfaceMock);

        $coreResourceMock->expects($this->once())->method('getTableName')->willReturn($tableName);

        $dbAdapterInterfaceMock
            ->expects($this->once())
            ->method('quoteInto')
            ->with("status = 'pending' AND store_id = ?", $mailchimpStoreId)
            ->willReturn($where);
        $dbAdapterInterfaceMock
            ->expects($this->once())
            ->method('update')
            ->with($tableName, array('status' => 'canceled'), $where);

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

        $helperMock
            ->expects($this->once())
            ->method('getMCStoreId')
            ->with($scopeId, $scope)
            ->willReturn($mailchimpStoreId);
        $helperMock
            ->expects($this->once())
            ->method('getConfigValueForScope')
            ->with(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_SYNC_DATE . "_$mailchimpStoreId", $scopeId, $scope);

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
         * @var Ebizmarts_MailChimp_Helper_Data $helperMock
         */
        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(
                array('getResendTurnConfigCollection', 'getResendTurn', 'getResendEnabled',
                    'setIsSyncingIfFinishedPerScope', 'isEcomSyncDataEnabled')
            )
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
         * @var Ebizmarts_MailChimp_Helper_Data $helperMock
         */
        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(
                array('getResendTurnConfigCollection', 'getResendTurn', 'setIsSyncingIfFinishedPerScope',
                    'setResendTurn', 'handleResendFinish', 'isEcomSyncDataEnabled')
            )
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
        $configMock
            ->expects($this->once())
            ->method('saveConfig')
            ->with(Ebizmarts_MailChimp_Model_Config::GENERAL_MIGRATE_FROM_116, 1, 'default', 0);
        $configMock->expects($this->once())->method('cleanCache');

        $helperMock
            ->saveMailChimpConfig(
                array(
                    array(
                        Ebizmarts_MailChimp_Model_Config::GENERAL_MIGRATE_FROM_116,
                        1
                    )
                ),
                0,
                'default',
                true
            );
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
            ->setMethods(
                array('getProductResourceModel', 'getProductModel', 'getImageSize', 'getCurrentStoreId',
                    'setCurrentStore', 'getImageUrl', 'getImageUrlForSize')
            )
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
        $helperMock
            ->expects($this->once())
            ->method('getImageSize')
            ->with($magentoStoreId)
            ->willReturn($configImageSize);

        $productResourceModelMock
            ->expects($this->once())
            ->method('getAttributeRawValue')
            ->with($productId, $imageSize, $magentoStoreId)
            ->willReturn($imageModelMock);

        $productModelMock->expects($this->once())->method('setData')->with($imageSize, $imageModelMock);

        $helperMock->expects($this->once())->method('getCurrentStoreId')->willReturn($defaultStoreId);
        $helperMock
            ->expects($this->exactly(2))
            ->method('setCurrentStore')
            ->withConsecutive(array($magentoStoreId), array($defaultStoreId));

        $helperMock
            ->expects($this->once())
            ->method('getImageUrlForSize')
            ->with($imageSize, $productModelMock)
            ->willReturn($imageUrl);

        $return = $helperMock->getImageUrlById($productId, $magentoStoreId);

        $this->assertEquals($return, $imageUrl);
    }

    public function testGetImageUrlByIdDataProvider()
    {
        return array(
            array(
                array(
                    'imageSize' => Ebizmarts_MailChimp_Model_Config::IMAGE_SIZE_DEFAULT,
                    'configImageSize' => Ebizmarts_MailChimp_Helper_Data::DEFAULT_SIZE
                )
            ),
            array(
                array(
                    'imageSize' => Ebizmarts_MailChimp_Model_Config::IMAGE_SIZE_SMALL,
                    'configImageSize' => Ebizmarts_MailChimp_Helper_Data::SMALL_SIZE
                )
            ),
            array(
                array(
                    'imageSize' => Ebizmarts_MailChimp_Model_Config::IMAGE_SIZE_THUMBNAIL,
                    'configImageSize' => Ebizmarts_MailChimp_Helper_Data::THUMBNAIL_SIZE
                )
            )
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
            ->setMethods(
                array('getProductResourceModel', 'getProductModel', 'getImageSize', 'getCurrentStoreId',
                    'setCurrentStore', 'getImageUrl', 'getOriginalPath')
            )
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
        $helperMock
            ->expects($this->once())
            ->method('getImageSize')
            ->with($magentoStoreId)
            ->willReturn($configImageSize);

        $productResourceModelMock
            ->expects($this->once())
            ->method('getAttributeRawValue')
            ->with($productId, $imageSize, $magentoStoreId)
            ->willReturn($imageModelMock);

        $productModelMock->expects($this->once())->method('setData')->with($imageSize, $imageModelMock);

        $helperMock->expects($this->once())->method('getCurrentStoreId')->willReturn($defaultStoreId);
        $helperMock
            ->expects($this->exactly(2))
            ->method('setCurrentStore')
            ->withConsecutive(array($magentoStoreId), array($defaultStoreId));

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

        $helperMock
            ->expects($this->once())
            ->method('setImageSizeVarToArray')
            ->with($imageSize)
            ->willReturn($imageArray);
        $helperMock
            ->expects($this->once())
            ->method('setWordToCamelCase')
            ->with($imageArray)
            ->willReturn($upperCaseImage);
        $helperMock
            ->expects($this->once())
            ->method('setFunctionName')
            ->with($upperCaseImage)
            ->willReturn($functionName);

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
        $filters = Ebizmarts_MailChimp_Model_Config::IS_ORDER;
        $deleteErrorsOnly = false;

        $connectionType = 'core_write';
        $mailchimpEcommTableAlias = 'mailchimp/ecommercesyncdata';
        $mailchimpEcommTableName = 'mailchimp_ecommerce_sync_data';

        $mailchimpStoreId = 'a1s2d3f4g5h6j7k8l9n0';
        $storeReturnWhere = "mailchimp_store_id = " . $mailchimpStoreId;
        $storeWhere = "mailchimp_store_id = ?";

        $mailchimpFilters = Ebizmarts_MailChimp_Model_Config::IS_ORDER;
        $filterReturnWhere = "type IN (" . $mailchimpFilters . ')';
        $filterWhere = "type IN (?)";

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getMCStoreId', 'getCoreResource'))
            ->getMock();

        $coreResourceMock = $this->getMockBuilder(Mage_Core_Model_Resource::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getConnection', 'getTableName'))
            ->getMock();

        $dbAdapterInterfaceMock = $this->getMockForAbstractClass(Varien_Db_Adapter_Interface::class);

        $helperMock->expects($this->once())->method('getCoreResource')->WillReturn($coreResourceMock);
        $helperMock
            ->expects($this->once())
            ->method('getMCStoreId')
            ->with($scopeId, $scope)
            ->WillReturn($mailchimpStoreId);

        $coreResourceMock
            ->expects($this->once())
            ->method('getConnection')
            ->with($connectionType)
            ->willReturn($dbAdapterInterfaceMock);
        $coreResourceMock
            ->expects($this->once())
            ->method('getTableName')
            ->with($mailchimpEcommTableAlias)
            ->willReturn($mailchimpEcommTableName);

        $dbAdapterInterfaceMock->expects($this->exactly(2))->method('quoteInto')
            ->withConsecutive(array($storeWhere, $mailchimpStoreId), array($filterWhere, $mailchimpFilters))
            ->willReturnOnConsecutiveCalls($storeReturnWhere, $filterReturnWhere);

        $dbAdapterInterfaceMock->expects($this->once())->method('delete')
            ->with($mailchimpEcommTableName, array($storeReturnWhere, $filterReturnWhere));

        $helperMock->removeEcommerceSyncData($scopeId, $scope, $deleteErrorsOnly, $filters);
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

        $helperMock
            ->expects($this->once())
            ->method('getMCStoreId')
            ->with($scopeId, $scope)
            ->WillReturn($mailchimpStoreId);

        $helperMock
            ->expects($this->once())
            ->method('removeEcommerceSyncDataByMCStore')
            ->with($mailchimpStoreId, $deleteErrorsOnly);

        $helperMock->removeEcommerceSyncData($scopeId, $scope, $deleteErrorsOnly);
    }

    public function testRemoveAllEcommerceSynddataErrors()
    {
        $mailchimpFilters = Ebizmarts_MailChimp_Model_Config::IS_ORDER;
        $filterReturnWhere = "type IN (" . $mailchimpFilters . ')';
        $filterWhere = "type IN (?)";

        $tableName = 'mailchimp_ecommerce_sync_data';
        $tableAlias = 'mailchimp/ecommercesyncdata';
        $errorSyncWhere = "mailchimp_sync_error != ''";

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

        $coreResourceMock
            ->expects($this->once())
            ->method('getConnection')
            ->with('core_write')
            ->willReturn($dbAdapterInterfaceMock);
        $coreResourceMock
            ->expects($this->once())
            ->method('getTableName')
            ->with($tableAlias)
            ->willReturn($tableName);

        $dbAdapterInterfaceMock
            ->expects($this->once())
            ->method('quoteInto')
            ->with($filterWhere, $mailchimpFilters)
            ->willReturn($filterReturnWhere);
        $dbAdapterInterfaceMock
            ->expects($this->once())
            ->method('delete')
            ->with($tableName, array($errorSyncWhere, $filterReturnWhere));

        $helperMock->removeAllEcommerceSyncDataErrors($mailchimpFilters);
    }

    public function testRemoveEcommerceSyncDataByMCStore()
    {
        $deleteErrorsOnly = true;
        $mailchimpStoreId = 'a1s2d3f4g5h6j7k8l9p0';
        $tableName = 'mailchimp_ecommerce_sync_data';
        $tableAlias = 'mailchimp/ecommercesyncdata';
        $storeReturnWhere = "mailchimp_store_id = $mailchimpStoreId AND mailchimp_sync_error != ''";

        $mailchimpFilters = Ebizmarts_MailChimp_Model_Config::IS_ORDER;
        $filterReturnWhere = "type IN (" . $mailchimpFilters . ')';
        $filterWhere = "type IN (?)";

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

        $coreResourceMock
            ->expects($this->once())
            ->method('getConnection')
            ->with('core_write')
            ->willReturn($dbAdapterInterfaceMock);
        $coreResourceMock->expects($this->once())->method('getTableName')->with($tableAlias)->willReturn($tableName);

        $dbAdapterInterfaceMock->expects($this->exactly(2))->method('quoteInto')
            ->withConsecutive(
                array(
                    "mailchimp_store_id = ? AND mailchimp_sync_error != ''", $mailchimpStoreId
                ),
                array(
                    $filterWhere, $mailchimpFilters
                )
            )
            ->willReturnOnConsecutiveCalls($storeReturnWhere, $filterReturnWhere);
        $dbAdapterInterfaceMock
            ->expects($this->once())
            ->method('delete')
            ->with($tableName, array($storeReturnWhere, $filterReturnWhere));

        $helperMock->removeEcommerceSyncDataByMCStore($mailchimpStoreId, $deleteErrorsOnly, $mailchimpFilters);
    }

    public function testClearErrorGridExcludeSubscribers()
    {
        $scopeId = 0;
        $scope = 'default';
        $excludeSubscribers = true;
        $connectionType = 'core_write';
        $mailchimpEcommTableAlias = 'mailchimp/mailchimperrors';
        $mailchimpEcommTableName = 'mailchimp_errors';

        $mailchimpStoreId = 'a1s2d3f4g5h6j7k8l9n0';
        $storeReturnWhere = "mailchimp_store_id = " . $mailchimpStoreId;
        $storeWhere = "mailchimp_store_id = ?";

        $mailchimpFilters = Ebizmarts_MailChimp_Model_Config::IS_ORDER;
        $filterReturnWhere = "regtype IN (" . $mailchimpFilters . ')';
        $filterWhere = "regtype IN (?)";

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
        $helperMock
            ->expects($this->once())
            ->method('getMCStoreId')
            ->with($scopeId, $scope)
            ->WillReturn($mailchimpStoreId);
        $helperMock->expects($this->once())->method('getCoreResource')->WillReturn($coreResourceMock);
        $coreResourceMock
            ->expects($this->once())
            ->method('getConnection')
            ->with($connectionType)
            ->willReturn($dbAdapterInterfaceMock);
        $coreResourceMock
            ->expects($this->once())
            ->method('getTableName')
            ->with($mailchimpEcommTableAlias)
            ->willReturn($mailchimpEcommTableName);

        $dbAdapterInterfaceMock->expects($this->exactly(2))->method('quoteInto')
            ->withConsecutive(array($storeWhere, $mailchimpStoreId), array($filterWhere, $mailchimpFilters))
            ->willReturnOnConsecutiveCalls($storeReturnWhere, $filterReturnWhere);
        $dbAdapterInterfaceMock
            ->expects($this->once())
            ->method('delete')
            ->with($mailchimpEcommTableName, array($storeReturnWhere, $filterReturnWhere));

        $helperMock->clearErrorGrid($scopeId, $scope, $excludeSubscribers, $mailchimpFilters);
    }

    public function testClearErrorGridIncludeSubscribersForDefaultScope()
    {
        $scopeId = 0;
        $scope = 'default';
        $excludeSubscribers = false;
        $connectionType = 'core_write';
        $mailchimpEcommTableAlias = 'mailchimp/mailchimperrors';
        $mailchimpEcommTableName = 'mailchimp_errors';
        $mailchimpStoreId = 'a1s2d3f4g5h6j7k8l9n0';
        $mailchimpFilters = Ebizmarts_MailChimp_Model_Config::IS_ORDER;
        $filterReturnWhere = "type IN (" . $mailchimpFilters . ')';
        $filterWhere = "type IN (?)";

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
        $helperMock
            ->expects($this->once())
            ->method('getMCStoreId')
            ->with($scopeId, $scope)
            ->WillReturn($mailchimpStoreId);
        $helperMock->expects($this->once())->method('getCoreResource')->WillReturn($coreResourceMock);
        $coreResourceMock
            ->expects($this->once())
            ->method('getConnection')
            ->with($connectionType)
            ->willReturn($dbAdapterInterfaceMock);
        $coreResourceMock
            ->expects($this->once())
            ->method('getTableName')
            ->with($mailchimpEcommTableAlias)
            ->willReturn($mailchimpEcommTableName);

        $dbAdapterInterfaceMock->expects($this->once())->method('quoteInto')
            ->with($filterWhere, $mailchimpFilters)
            ->willReturn($filterReturnWhere);
        $dbAdapterInterfaceMock
            ->expects($this->once())
            ->method('delete')
            ->with($mailchimpEcommTableName, array($filterReturnWhere));

        $helperMock->clearErrorGrid($scopeId, $scope, $excludeSubscribers, $mailchimpFilters);
    }

    public function testClearErrorGridIncludeSubscribersForStoreView()
    {
        $scopeId = 1;
        $scope = 'stores';
        $excludeSubscribers = false;
        $connectionType = 'core_write';
        $mailchimpEcommTableAlias = 'mailchimp/mailchimperrors';
        $mailchimpEcommTableName = 'mailchimp_errors';

        $mailchimpStoreId = 'a1s2d3f4g5h6j7k8l9n0';
        $storeReturnWhere = "store_id = " . $scopeId;
        $storeWhere = "store_id = ?";

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
        $helperMock
            ->expects($this->once())
            ->method('getMCStoreId')
            ->with($scopeId, $scope)
            ->WillReturn($mailchimpStoreId);
        $helperMock->expects($this->once())->method('getCoreResource')->WillReturn($coreResourceMock);
        $coreResourceMock
            ->expects($this->once())
            ->method('getConnection')
            ->with($connectionType)
            ->willReturn($dbAdapterInterfaceMock);
        $coreResourceMock
            ->expects($this->once())
            ->method('getTableName')
            ->with($mailchimpEcommTableAlias)
            ->willReturn($mailchimpEcommTableName);

        $dbAdapterInterfaceMock->expects($this->once())->method('quoteInto')
            ->withConsecutive(array($storeWhere, $scopeId))
            ->willReturnOnConsecutiveCalls($storeReturnWhere);
        $dbAdapterInterfaceMock
            ->expects($this->once())
            ->method('delete')
            ->with($mailchimpEcommTableName, array($storeReturnWhere));

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
            $apiMock
        );

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

        $helperMock
            ->expects($this->once())
            ->method('getAllStoresForScope')
            ->with($scopeId, $scope)
            ->willReturn($storeArray);

        $helperMock->expects($this->once())->method('getCoreResource')->WillReturn($coreResourceMock);
        $coreResourceMock
            ->expects($this->once())
            ->method('getConnection')
            ->with($connectionType)
            ->willReturn($dbAdapterInterfaceMock);
        $coreResourceMock
            ->expects($this->once())
            ->method('getTableName')
            ->with($subscriberTableAlias)
            ->willReturn($subscriberTableName);

        $dbAdapterInterfaceMock
            ->expects($this->once())
            ->method('update')
            ->with($subscriberTableName, $setCondition, $where);

        $helperMock->resendSubscribers($scopeId, $scope);
    }

    public function testSaveLastItemsSent()
    {
        $mailchimpStoreId = 'a1s2d3f4g5h6j7k8l9p0';
        $scope = 'stores';
        $scopeId = 1;

        $filters = array(
            Ebizmarts_MailChimp_Model_Config::IS_PRODUCT,
            Ebizmarts_MailChimp_Model_Config::IS_CUSTOMER,
            Ebizmarts_MailChimp_Model_Config::IS_ORDER,
            Ebizmarts_MailChimp_Model_Config::IS_QUOTE,
            Ebizmarts_MailChimp_Model_Config::IS_PROMO_CODE . ', ' . Ebizmarts_MailChimp_Model_Config::IS_PROMO_RULE);

        $resendEnabled = 1;
        $resendTurn = 1;
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
            array(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_RESEND_ENABLED, $resendEnabled),
            array(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_RESEND_TURN, $resendTurn)
        );

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(
                array(
                    'getMCStoreId', 'getMCIsSyncing', 'getLastCustomerSent', 'getLastProductSent',
                    'getLastOrderSent', 'getLastCartSent', 'getLastPromoCodeSent', 'saveMailchimpConfig',
                    'getCustomerResendLastId', 'getProductResendLastId', 'getOrderResendLastId',
                    'getCartResendLastId', 'getPromoCodeResendLastId'
                )
            )
            ->getMock();

        $helperMock
            ->expects($this->once())
            ->method('getMCStoreId')
            ->with($scopeId, $scope)
            ->willReturn($mailchimpStoreId);
        $helperMock
            ->expects($this->once())
            ->method('getMCIsSyncing')
            ->with($mailchimpStoreId, $scopeId, $scope)
            ->willReturn(false);

        $helperMock->expects($this->once())->method('getCustomerResendLastId')->willReturn(null);
        $helperMock->expects($this->once())->method('getProductResendLastId')->willReturn(null);
        $helperMock->expects($this->once())->method('getOrderResendLastId')->willReturn(null);
        $helperMock->expects($this->once())->method('getCartResendLastId')->willReturn(null);
        $helperMock->expects($this->once())->method('getPromoCodeResendLastId')->willReturn(null);

        $helperMock
            ->expects($this->once())
            ->method('getLastCustomerSent')
            ->with($scopeId, $scope)
            ->willReturn($customerLastId);
        $helperMock
            ->expects($this->once())
            ->method('getLastProductSent')
            ->with($scopeId, $scope)
            ->willReturn($productLastId);
        $helperMock
            ->expects($this->once())
            ->method('getLastOrderSent')
            ->with($scopeId, $scope)
            ->willReturn($orderLastId);
        $helperMock->expects($this->once())->method('getLastCartSent')->with($scopeId, $scope)->willReturn($cartLastId);
        $helperMock
            ->expects($this->once())
            ->method('getLastPromoCodeSent')
            ->with($scopeId, $scope)
            ->willReturn($promoCodeLastId);

        $helperMock->expects($this->once())->method('saveMailchimpConfig')->with($configValues, $scopeId, $scope);

        $helperMock->saveLastItemsSent($scopeId, $scope, $filters);
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
        $categoriesResponse = array(
            'categories' => array(
                array(
                    'id' => $interestCategoryId,
                    'title' => 'Category Title',
                    'type' => 'checkbox'
                )
            )
        );

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

        $apiListsInterestCategoryMock
            ->expects($this->once())
            ->method('getAll')
            ->with($listId, 'categories')
            ->willReturn($categoriesResponse);

        $helperMock->getListInterestCategoriesByKeyAndList($apiKey, $listId);
    }

    public function testGetListInterestGroups()
    {
        $scopeId = 1;
        $scope = 'stores';
        $listId = 'a1s2d3f4g5';
        $interestCategoryId = 1;
        $categoriesResponse = array(
            'categories' => array(
                array(
                    'id' => $interestCategoryId,
                    'title' => 'Category Title',
                    'type' => 'checkbox'
                )
            )
        );
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

        $apiListsInterestCategoryMock
            ->expects($this->once())
            ->method('getAll')
            ->with($listId, 'categories')
            ->willReturn($categoriesResponse);
        $apiListsInterestCategoryMock
            ->expects($this->once())
            ->method('getInterests')
            ->willReturn($apiListsInterestCategoryInterestsMock);

        $apiListsInterestCategoryInterestsMock
            ->expects($this->once())
            ->method('getAll')
            ->with($listId, $interestCategoryId)
            ->willReturn($interestsResponse);

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
        $categoriesResponse = array(
            'categories' => array(
                array(
                    'id' => $interestCategoryId,
                    'title' => 'Category Title',
                    'type' => 'checkbox'
                )
            )
        );
        $interestsResponseOne = array(
            'interests' => array(
                array(
                    'category_id' => $interestCategoryId,
                    'id' => $interestIdOne,
                    'name' => $interestNameOne,
                    'display_order' => $displayOrderOne
                )
            )
        );
        $interestsResponseTwo = array(
            'interests' => array(
                array(
                    'category_id' => $interestCategoryId,
                    'id' => $interestIdTwo,
                    'name' => $interestNameTwo,
                    'display_order' => $displayOrderTwo
                )
            )
        );
        $expectedResult = array(
            1 => array(
                'category' => array(
                    $displayOrderOne => array(
                        'id' => $interestIdOne,
                        'name' => $interestNameOne,
                        'checked' => false
                    ),
                    $displayOrderTwo => array(
                        'id' => $interestIdTwo,
                        'name' => $interestNameTwo,
                        'checked' => false
                    )
                )
            )
        );

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

        $apiListsInterestCategoryInterestsMock = $this->getMockBuilder(
            MailChimp_ListInterestCategoryInterests::class
        )
            ->disableOriginalConstructor()
            ->setMethods(array('getAll'))
            ->getMock();

        $helperMock
            ->expects($this->once())
            ->method('getLocalInterestCategories')
            ->with($scopeId)
            ->willReturn($localGroups);
        $helperMock->expects($this->once())->method('getApi')->with($scopeId)->willReturn($apiMock);
        $helperMock->expects($this->once())->method('getGeneralList')->with($scopeId)->willReturn($listId);

        $apiMock->expects($this->once())->method('getLists')->willReturn($apiListsMock);

        $apiListsMock->expects($this->once())->method('getInterestCategory')->willReturn($apiListsInterestCategoryMock);

        $apiListsInterestCategoryMock
            ->expects($this->once())
            ->method('getAll')
            ->with($listId)
            ->willReturn($categoriesResponse);

        $apiListsInterestCategoryMock
            ->expects($this->once())
            ->method('getInterests')
            ->willReturn($apiListsInterestCategoryInterestsMock);

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
        $interest = array(
            1 => array(
                'category' => array(
                    $displayOrderOne => array(
                        'id' => $interestIdOne,
                        'name' => $interestNameOne,
                        'checked' => false
                    ),
                    $displayOrderTwo => array(
                        'id' => $interestIdTwo,
                        'name' => $interestNameTwo,
                        'checked' => false
                    )
                )
            )
        );
        $encodedGroupData = '{"bc15dbe6a5":{"d6b7541ee7":"d6b7541ee7"},"2a2f23d671":"36c250eeff"}';
        $groupData = array(
            'bc15dbe6a5' => array('d6b7541ee7' => 'd6b7541ee7'),
            '2a2f23d671' => '36c250eeff'
        );
        $expectedResult = $interest;

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(
                array(
                    'getInterest', 'getInterestGroupModel', 'getLocalInterestCategories',
                    'arrayDecode', 'isSubscriptionEnabled'
                )
            )
            ->getMock();

        $interestGroupMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Interestgroup::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getByRelatedIdStoreId', 'getId', 'getGroupdata'))
            ->getMock();

        $helperMock->expects($this->once())->method('isSubscriptionEnabled')->with($storeId)->willReturn(true);
        $helperMock->expects($this->once())->method('getInterest')->with($storeId)->willReturn($interest);
        $helperMock->expects($this->once())->method('getInterestGroupModel')->willReturn($interestGroupMock);

        $interestGroupMock
            ->expects($this->once())
            ->method('getByRelatedIdStoreId')
            ->with($customerId, $subscriberId, $storeId)
            ->willReturnSelf();
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
            ->setMethods(
                array('getInterestGroupsIfAvailable', 'isAdmin', 'getCustomerSession',
                    'getInterestGroupModel', 'arrayEncode', 'getDateHelper')
            )
            ->getMock();

        $helperDateMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Date::class)
            ->disableOriginalConstructor()
            ->setMethods(
                array('formatDate')
            )
            ->getMock();

        $interestGroupMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Interestgroup::class)
            ->disableOriginalConstructor()
            ->setMethods(
                array('getByRelatedIdStoreId', 'getSubscriberId', 'getCustomerId', 'setSubscriberId',
                    'setCustomerId', 'setGroupdata', 'getGroupdata', 'setStoreId', 'setUpdatedAt', 'save')
            )
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

        $helperMock
            ->expects($this->once())
            ->method('getInterestGroupsIfAvailable')
            ->with($params)
            ->willReturn($groupData);
        $helperMock->expects($this->once())->method('getCustomerSession')->willReturn($customerSessionMock);
        $helperMock->expects($this->once())->method('isAdmin')->willReturn(false);

        $helperMock->expects($this->once())->method('getDateHelper')->willReturn($helperDateMock);
        $helperDateMock
            ->expects($this->once())
            ->method('formatDate')
            ->with(null, 'Y-m-d H:i:s')
            ->willReturn($currentDateTime);

        $customerSessionMock->expects($this->once())->method('isLoggedIn')->willReturn(true);
        $customerSessionMock->expects($this->once())->method('getCustomer')->willReturn($customerMock);

        $customerMock->expects($this->once())->method('getId')->willReturn($customerId);

        $subscriberMock->expects($this->once())->method('getSubscriberId')->willReturn($subscriberId);

        $helperMock->expects($this->once())->method('getInterestGroupModel')->willReturn($interestGroupMock);

        $interestGroupMock
            ->expects($this->once())
            ->method('getByRelatedIdStoreId')
            ->with($customerId, $subscriberId, $storeId)
            ->willReturnSelf();
        $interestGroupMock->expects($this->once())->method('getSubscriberId')->willReturn($origSubscriberId);
        $interestGroupMock->expects($this->once())->method('getCustomerId')->willReturn($origCustomerId);
        $interestGroupMock->expects($this->once())->method('setSubscriberId')->with($subscriberId)->willReturnSelf();
        $interestGroupMock->expects($this->once())->method('setCustomerId')->with($customerId)->willReturnSelf();

        $helperMock->expects($this->once())->method('arrayEncode')->with($groupData)->willReturn($encodedGroupData);

        $interestGroupMock->expects($this->once())->method('setGroupdata')->with($encodedGroupData)->willReturnSelf();
        $interestGroupMock->expects($this->once())->method('getGroupdata')->willReturn($encodedGroupData);
        $interestGroupMock->expects($this->once())->method('setStoreId')->with($storeId)->willReturnSelf();

        $interestGroupMock->expects($this->once())->method('setUpdatedAt')->with($currentDateTime)->willReturnSelf();
        $interestGroupMock->expects($this->once())->method('save')->willReturnSelf();

        $helperMock->saveInterestGroupData($params, $storeId, null, $subscriberMock);
    }

    public function testGetMCJs()
    {
        $storeId = 1;
        $jsUrl = 'https://chimpstatic.com/mcjs-connected/js/users/1647ea7abc3f2f3259e2613f9'
            . '/dffd1d29fea0323354a9caa32.js';
        $mailchimpStoreId = 'a1s2d3f4g5h6j7k8l9p0';

        $expectedResult = '<script type="text/javascript" src="' . $jsUrl . '" defer></script>';

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(
                array('getMageApp', 'isEcomSyncDataEnabled', 'getConfigValueForScope',
                    'retrieveAndSaveMCJsUrlInConfig', 'getMCStoreId')
            )
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

        $helperMock
            ->expects($this->once())
            ->method('getMCStoreId')
            ->with($storeId)
            ->willReturn($mailchimpStoreId);
        $helperMock
            ->expects($this->once())
            ->method('isEcomSyncDataEnabled')
            ->with($storeId)
            ->willReturn(true);
        $helperMock
            ->expects($this->once())
            ->method('getConfigValueForScope')
            ->with(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_MC_JS_URL . "_$mailchimpStoreId", 0, 'default')
            ->willReturn(null);
        $helperMock
            ->expects($this->once())
            ->method('retrieveAndSaveMCJsUrlInConfig')
            ->with($storeId)
            ->willReturn($jsUrl);

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

    public function testGetSessionLastRealOrderM19()
    {
        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getCheckOutSession'))
            ->getMock();

        $checkoutSessionMock = $this->getMockBuilder(Mage_Checkout_Model_Session::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getLastRealOrder'))
            ->getMock();

        $orderMock = $this->getMockBuilder(Mage_Sales_Model_Order::class)
            ->disableOriginalConstructor()
            ->getMock();

        $helperMock->expects($this->once())->method('getCheckOutSession')->willReturn($checkoutSessionMock);

        $checkoutSessionMock->expects($this->once())->method('getLastRealOrder')->willReturn($orderMock);

        $helperMock->getSessionLastRealOrder();
    }

    public function testGetSessionLastRealOrderM17()
    {
        $orderId = 4;
        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getCheckOutSession', 'getSalesOrderModel'))
            ->getMock();

        $checkoutSessionMock = $this->getMockBuilder(Mage_Checkout_Model_Session::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getLastRealOrder', 'getLastOrderId'))
            ->getMock();

        $orderMock = $this->getMockBuilder(Mage_Sales_Model_Order::class)
            ->disableOriginalConstructor()
            ->setMethods(array('load'))
            ->getMock();

        $helperMock->expects($this->once())->method('getCheckOutSession')->willReturn($checkoutSessionMock);

        $checkoutSessionMock->expects($this->once())->method('getLastRealOrder')->willReturn(null);
        $checkoutSessionMock->expects($this->once())->method('getLastOrderId')->willReturn($orderId);
        $helperMock->expects($this->once())->method('getSalesOrderModel')->willReturn($orderMock);
        $orderMock->expects($this->once())->method('load')->with($orderId)->willReturnSelf();

        $helperMock->getSessionLastRealOrder();
    }

    public function testSetCurrentStore()
    {
        $storeId = 1;
        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getMageApp'))
            ->getMock();

        $mageAppMock = $this->getMockBuilder(Mage_Core_Model_App::class)
            ->disableOriginalConstructor()
            ->setMethods(array('setCurrentStore'))
            ->getMock();

        $helperMock
            ->expects($this->once())
            ->method('getMageApp')
            ->willReturn($mageAppMock);

        $mageAppMock
            ->expects($this->once())
            ->method('setCurrentStore')
            ->with($storeId)
            ->willReturnSelf();

        $helperMock->setCurrentStore($storeId);
    }

    public function testGetCurrentStoreId()
    {
        $storeId = 1;
        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getMageApp'))
            ->getMock();

        $mageAppMock = $this->getMockBuilder(Mage_Core_Model_App::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getStore'))
            ->getMock();

        $mageAppMockStore = $this->getMockBuilder(Mage_Core_Model_Store::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getId'))
            ->getMock();

        $helperMock
            ->expects($this->once())
            ->method('getMageApp')
            ->willReturn($mageAppMock);

        $mageAppMock
            ->expects($this->once())
            ->method('getStore')
            ->willReturn($mageAppMockStore);

        $mageAppMockStore
            ->expects($this->once())
            ->method('getId')->willReturn($storeId);

        $return = $helperMock->getCurrentStoreId();

        $this->assertInternalType('int', $return);
    }

    public function testResetErrors()
    {
        $scopeId = 0;
        $scope = 'stores';

        $helperDataMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('removeErrorsFromSubscribers', 'removeEcommerceSyncData', 'clearErrorGrid'))
            ->getMock();

        $helperDataMock
            ->expects($this->once())
            ->method('removeErrorsFromSubscribers')
            ->with($scopeId, $scope);

        $helperDataMock
            ->expects($this->once())
            ->method('removeEcommerceSyncData')
            ->with($scopeId, $scope);

        $helperDataMock
            ->expects($this->once())
            ->method('clearErrorGrid')
            ->with($scopeId, $scope);

        $helperDataMock->resetErrors($scopeId, $scope);
    }

    public function testGetMagentoStoresForMCStoreIdByScope()
    {
        $scopeId = 0;
        $scope = 'stores';
        $storeRelation = array(
            'a1s2d3f4g5h6j7k8l9p0' => 1,
            'a1s2d3f5h6h6j7k8l9p0' => 2
        );

        $helperDataMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getStoreRelation', 'getMCStoreId'))
            ->getMock();

        $helperDataMock
            ->expects($this->once())
            ->method('getStoreRelation')
            ->willReturn($storeRelation);

        $helperDataMock
            ->expects($this->once())
            ->method('getMCStoreId')
            ->with($scopeId, $scope)
            ->willReturn('a1s2d3f4g5h6j7k8l9p0');

        $helperDataMock->getMagentoStoresForMCStoreIdByScope($scopeId, $scope);
    }

    public function testCreateMergeFields()
    {
        $scopeId = 0;
        $scope = 'stores';
        $listId = "b514eebd1a";
        $mapFieldsSerialized = 'a:10:{s:18:"_1468601283719_719";a:2:{s:9:"mailchimp";s:7:"WEBSITE";' .
            's:7:"magento";s:1:"1";}s:18:"_1468609069544_544";a:2:{s:9:"mailchimp";s:7:"STOREID";' .
            's:7:"magento";s:1:"2";}s:18:"_1469026825907_907";a:2:{s:9:"mailchimp";s:9:"STORENAME";' .
            's:7:"magento";s:1:"3";}s:18:"_1469027411717_717";a:2:{s:9:"mailchimp";s:6:"PREFIX";' .
            's:7:"magento";s:1:"4";}s:18:"_1469027418285_285";a:2:{s:9:"mailchimp";s:5:"FNAME";' .
            's:7:"magento";s:1:"5";}}';

        $mapFieldsUnserialized = array(
            "_1468601283719_719" => array("mailchimp" => "WEBSITE", "magento" => "1"),
            "_1468609069544_544" => array("mailchimp" => "STOREID", "magento"=>  "2"),
            "_1469026825907_907" => array("mailchimp" => "STORENAME", "magento" => "3"),
            "_1469027411717_717" => array("mailchimp" => "PREFIX","magento" => "4"),
            "_1469027418285_285" => array("mailchimp" => "FNAME", "magento" => "5")
        );

        $arrayMergeFieldsGetAll = array
        (
            "merge_fields" => array
            (
                0 => array
                (
                    "merge_id" => 3,
                    "tag" => "ADDRESS",
                    "name" => "Address",
                    "type" => "address",
                    "required" => "",
                    "default_value" => "",
                    "public" => "",
                    "display_order" => 4,
                    "options" => array("default_country" => 164),
                    "help_text" => "",
                    "list_id" => "b514eebd1a",
                    "_links" => array
                    (
                        0 => array
                        (
                            "rel" => "self",
                            "href" => "https://us20.api.mailchimp.com/3.0/lists/b514eebd1a/merge-fields/3",
                            "method" => "GET",
                            "targetSchema" => "https://us20.api.mailchimp.com"
                                . "/schema/3.0/Definitions/Lists/MergeFields/Response.json",
                        ),

                        1 => array
                        (
                            "rel" => "parent",
                            "href" => "https://us20.api.mailchimp.com/3.0/lists/b514eebd1a/merge-fields",
                            "method" => "GET",
                            "targetSchema" => "https://us20.api.mailchimp.com"
                                . "/schema/3.0/Definitions/Lists/MergeFields/CollectionResponse.json",
                            "schema" => "https://us20.api.mailchimp.com"
                                . "/schema/3.0/CollectionLinks/Lists/MergeFields.json",
                        ),

                        2 => array
                        (
                            "rel" => "update",
                            "href" => "https://us20.api.mailchimp.com/3.0/lists/b514eebd1a/merge-fields/3",
                            "method" => "PATCH",
                            "targetSchema" => "https://us20.api.mailchimp.com"
                                . "/schema/3.0/Definitions/Lists/MergeFields/Response.json",
                            "schema" => "https://us20.api.mailchimp.com"
                                . "/schema/3.0/Definitions/Lists/MergeFields/PATCH.json",
                        ),

                        3 => array
                        (
                            "rel" => "delete",
                            "href" => "https://us20.api.mailchimp.com/3.0/lists/b514eebd1a/merge-fields/3",
                            "method" => "DELETE",
                        )

                    )

                )
            )
        );

        $ebizmartsMailchimpMock = $this->getMockBuilder(Ebizmarts_MailChimp::class)
            ->disableOriginalConstructor()->setMethods(array('getLists'))
            ->getMock();

        $mailchimpListsMock = $this->getMockBuilder(MailChimp_Lists::class)
            ->disableOriginalConstructor()->setMethods(array('getMergeFields'))
            ->getMock();

        $mailchimpListsMergeFieldsMock = $this->getMockBuilder(MailChimp_ListsMergeFields::class)
            ->disableOriginalConstructor()->setMethods(array('getAll'))
            ->getMock();

        $helperDataMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(
                array(
                    'getGeneralList', 'getMapFields', 'unserialize', 'getCustomMergeFieldsSerialized',
                    'getApi', '_createCustomFieldTypes',
                )
            )
            ->getMock();

        $helperDataMock->expects($this->once())->method('getGeneralList')->with($scopeId, $scope)->willReturn($listId);

        $helperDataMock->expects($this->once())->method('getMapFields')
            ->with($scopeId, $scope)->willReturn($mapFieldsSerialized);

        $helperDataMock->expects($this->exactly(2))->method('unserialize')
            ->withConsecutive(array($mapFieldsSerialized), array($mapFieldsSerialized))
            ->willReturnOnConsecutiveCalls($mapFieldsUnserialized, $mapFieldsUnserialized);

        $helperDataMock->expects($this->once())->method('getCustomMergeFieldsSerialized')
            ->with($scopeId, $scope)->willReturn($mapFieldsSerialized);

        $helperDataMock->expects($this->once())->method('getApi')->with($scopeId, $scope)
            ->willReturn($ebizmartsMailchimpMock);

        $ebizmartsMailchimpMock->expects($this->once())->method('getLists')
            ->willReturn($mailchimpListsMock);

        $mailchimpListsMock->expects($this->once())->method('getMergeFields')
            ->willReturn($mailchimpListsMergeFieldsMock);

        $mailchimpListsMergeFieldsMock->expects($this->once())->method('getAll')
            ->with($listId, null, null, 50)
            ->willReturn($arrayMergeFieldsGetAll);

        $times = 5;

        $helperDataMock->expects($this->exactly($times))->method('_createCustomFieldTypes')
            ->withConsecutive(
                array($mapFieldsUnserialized),
                array($mapFieldsUnserialized),
                array($mapFieldsUnserialized),
                array($mapFieldsUnserialized),
                array($mapFieldsUnserialized)
            );

        $helperDataMock->createMergeFields($scopeId, $scope);
    }

    public function testGetCustomMergeFields()
    {
        $scopeId = 0;
        $scope = 'stores';
        $mapFieldsSerialized = 'a:10:{s:18:"_1468601283719_719";a:2:{s:9:"mailchimp";s:7:"WEBSITE";' .
            's:7:"magento";s:1:"1";}s:18:"_1468609069544_544";a:2:{s:9:"mailchimp";s:7:"STOREID";' .
            's:7:"magento";s:1:"2";}s:18:"_1469026825907_907";a:2:{s:9:"mailchimp";s:9:"STORENAME";' .
            's:7:"magento";s:1:"3";}s:18:"_1469027411717_717";a:2:{s:9:"mailchimp";s:6:"PREFIX";' .
            's:7:"magento";s:1:"4";}s:18:"_1469027418285_285";a:2:{s:9:"mailchimp";s:5:"FNAME";' .
            's:7:"magento";s:1:"5";}}';

        $mapFieldsUnserialized = array(
            "_1468601283719_719", array("mailchimp", "WEBSITE", "magento", "1"), "_1468609069544_544",
            array("mailchimp", "STOREID", "magento", "2"), "_1469026825907_907",
            array("mailchimp", "STORENAME", "magento", "3"),
            "_1469027411717_717", array("mailchimp", "PREFIX", "magento", "4"), "_1469027418285_285",
            array("mailchimp", "FNAME", "magento", "5")
        );

        $helperDataMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('unserialize', 'getCustomMergeFieldsSerialized'))
            ->getMock();

        $helperDataMock->expects($this->once())->method('getCustomMergeFieldsSerialized')
            ->with($scopeId, $scope)->willReturn($mapFieldsSerialized);

        $helperDataMock->expects($this->once())->method('unserialize')
            ->with($mapFieldsSerialized)
            ->willReturn($mapFieldsUnserialized);

        $helperDataMock->getCustomMergeFields($scopeId, $scope);
    }

    public function testping()
    {
        $storeId = 6;

        $helperDataMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getApi'))
            ->getMock();

        $apiMock = $this->getMockBuilder(Ebizmarts_MailChimp::class)
            ->disableOriginalConstructor()->setMethods(array('getRoot'))
            ->getMock();

        $helperDataMock->expects($this->once())->method('getApi')->with($storeId)
            ->willReturn($apiMock);

        $apiRootMock = $this->getMockBuilder(MailChimp_Root::class)
            ->disableOriginalConstructor()
            ->setMethods(array('info'))
            ->getMock();

        $apiMock->expects($this->once())->method('getRoot')->willReturn($apiRootMock);

        $apiRootMock->expects($this->once())->method('info');

        $helperDataMock->ping($storeId);
    }

    public function testmodifyCounterDataSentToMailchimp()
    {
        $index = 0;
        $statusChanged = "SENT";

        $helperDataMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getCountersDataSentToMailchimp', 'setCountersDataSentToMailchimp'))
            ->getMock();

        $helperDataMock->expects($this->once())->method('getCountersDataSentToMailchimp');
        $helperDataMock->expects($this->once())->method('setCountersDataSentToMailchimp')
            ->with($index, $statusChanged, 1);

        $helperDataMock->modifyCounterDataSentToMailchimp($index);
    }

    public function testmodifyCounterDataNotSentToMailchimp()
    {
        $index = 0;
        $statusChanged = "NOT SENT";

        $helperDataMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getCountersDataSentToMailchimp', 'setCountersDataSentToMailchimp'))
            ->getMock();

        $helperDataMock->expects($this->once())->method('getCountersDataSentToMailchimp');
        $helperDataMock->expects($this->once())->method('setCountersDataSentToMailchimp')
            ->with($index, $statusChanged, 1);

        $helperDataMock->modifyCounterDataSentToMailchimp($index, true);
    }

    public function testSerialize()
    {
        $data = array('data1' => 'value1', 'data2' => 'value2');
        $serializedData = 'a:2:{s:5:"data1";s:6:"value1";s:5:"data2";s:6:"value2";}';

        // Sets any methods so the mock initializes the required method
        $helperDataMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('unserialize'))
            ->getMock();

        $testData = $helperDataMock->serialize($data);

        $this->assertEquals($testData, $serializedData);
    }

    public function testUnserialize()
    {
        $unserializedData = array('data1' => 'value1', 'data2' => 'value2');
        $serializedData = 'a:2:{s:5:"data1";s:6:"value1";s:5:"data2";s:6:"value2";}';


        // Sets any methods so the mock initializes the required method
        $helperDataMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('serialize'))
            ->getMock();

        $testData = $helperDataMock->unserialize($serializedData);

        $this->assertEquals($testData, $unserializedData);
    }
}
