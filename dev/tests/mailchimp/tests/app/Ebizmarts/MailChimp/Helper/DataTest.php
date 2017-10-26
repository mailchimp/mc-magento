<?php

class Ebizmarts_MailChimp_Helper_DataTest extends PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        Mage::app('default');
    }

    public function testGetLastDateOfPurchase()
    {
        /**
         * @var \Ebizmarts_MailChimp_Helper_Data $helperMock
         */
        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getOrderCollectionByCustomerEmail'))
            ->getMock();

        $helperMock->expects($this->once())->method('getOrderCollectionByCustomerEmail')->with("john@example.com")
            ->willReturn(array());

        $this->assertNull($helperMock->getLastDateOfPurchase("john@example.com"));
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
        /**
         * @var \Ebizmarts_MailChimp_Helper_Data $helperMock
         */
        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('isMailChimpEnabled', 'getCheckoutSubscribeValue'))
            ->getMock();
        $helperMock->expects($this->once())->method('isMailChimpEnabled')->with(1, 'stores')
            ->willReturn(true);

        $helperMock->expects($this->once())->method('getCheckoutSubscribeValue')->with(1, 'stores')
            ->willReturn(Ebizmarts_MailChimp_Model_System_Config_Source_Checkoutsubscribe::NOT_CHECKED_BY_DEFAULT);

        $this->assertTrue($helperMock->isCheckoutSubscribeEnabled(1, "stores"));
    }

    public function testDeleteStore()
    {
        $scopeId = 1;
        $scope = 'stores';
        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getMCStoreId', 'getApiStores', 'getGeneralList', 'deleteCurrentWebhook', 'deleteLocalMCStoreData'))
            ->getMock();
        $apiStoresMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Stores::class)
            ->disableOriginalConstructor()
            ->getMock();

        $helperMock->expects($this->once())->method('getMCStoreId')->with($scopeId, $scope)->willReturn('a18a1a8a1aa7aja1a');
        $helperMock->expects($this->once())->method('getApiStores')->willReturn($apiStoresMock);
        $helperMock->expects($this->once())->method('getGeneralList')->with($scopeId, $scope)->willReturn('listId');
        $helperMock->expects($this->once())->method('deleteCurrentWebhook')->with($scopeId, $scope, 'listId');
        $helperMock->expects($this->once())->method('deleteLocalMCStoreData')->with($scopeId, $scope);

        $helperMock->deleteStore($scopeId, $scope);
    }

    public function testAddResendFilter()
    {
        $storeId = 1;
        /**
         * @var \Ebizmarts_MailChimp_Helper_Data $helperMock
         */
        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getResendEnabled', 'getResendTurn', 'getOrderResendLastId'))
            ->getMock();

        $orderCollectionMock = $this->getMockBuilder(Mage_Sales_Model_Resource_Order_Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $helperMock->expects($this->once())->method('getResendEnabled')->with($storeId)->willReturn(1);
        $helperMock->expects($this->once())->method('getResendTurn')->with($storeId)->willReturn(1);
        $helperMock->expects($this->once())->method('getOrderResendLastId')->with($storeId);

        $helperMock->addResendFilter($orderCollectionMock, $storeId);
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

//    public function testHandleResendDataBefore()
//    {
//        $scopeId = 0;
//        $scope = 'default';
//        $configMock = $this->getMockBuilder(Mage_Core_Model_Config_Data::class)
//            ->disableOriginalConstructor()
//            ->setMethods(array('getScope', 'getScopeId'))
//            ->getMock();
//        $configEntries = array();
//
//        /**
//         * @var \Ebizmarts_MailChimp_Helper_Data $helperMock
//         */
//        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
//            ->disableOriginalConstructor()
//            ->setMethods(array('getResendTurnConfigCollection', 'getResendTurn', 'setIsSyncingIfFinishedPerScope'))
//            ->getMock();
//
//        $collectionMock = $this->getMockBuilder(Mage_Core_Model_Resource_Config_Data_Collection::class)
//            ->disableOriginalConstructor()
//            ->getMock();
//
//        $configMock->expects($this->once())->method('getScope')->willReturn($scope);
//        $configMock->expects($this->once())->method('getScopeId')->willReturn($scopeId);
//        $configEntries [] = $configMock;
//        $collectionMock->expects($this->once())->method("getIterator")->willReturn(new ArrayIterator($configEntries));
//        $helperMock->expects($this->once())->method('getResendTurnConfigCollection')->willReturn($collectionMock);
//        $helperMock->expects($this->once())->method('getResendTurn')->with($scopeId, $scope)->willReturn(1);
//        $helperMock->expects($this->once())->method('setIsSyncingIfFinishedPerScope')->with(true, $scopeId, $scope);
//
//        $helperMock->handleResendDataBefore();
//    }

//    public function testHandleResendDataAfter()
//    {
//        $scopeId = 0;
//        $scope = 'default';
//        $configMock = $this->getMockBuilder(Mage_Core_Model_Config_Data::class)
//            ->disableOriginalConstructor()
//            ->setMethods(array('getScope', 'getScopeId'))
//            ->getMock();
//        $configEntries = array();
//
//        /**
//         * @var \Ebizmarts_MailChimp_Helper_Data $helperMock
//         */
//        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
//            ->disableOriginalConstructor()
//            ->setMethods(array('getResendTurnConfigCollection', 'getResendTurn', 'setIsSyncingIfFinishedPerScope'))
//            ->getMock();
//
//        $collectionMock = $this->getMockBuilder(Mage_Core_Model_Resource_Config_Data_Collection::class)
//            ->disableOriginalConstructor()
//            ->getMock();
//
//        $configMock->expects($this->once())->method('getScope')->willReturn($scope);
//        $configMock->expects($this->once())->method('getScopeId')->willReturn($scopeId);
//        $configEntries [] = $configMock;
//        $collectionMock->expects($this->once())->method("getIterator")->willReturn(new ArrayIterator($configEntries));
//        $helperMock->expects($this->once())->method('getResendTurnConfigCollection')->willReturn($collectionMock);
//        $helperMock->expects($this->once())->method('getResendTurn')->with($scopeId, $scope)->willReturn(1);
//        $helperMock->expects($this->once())->method('setIsSyncingIfFinishedPerScope')->with(false, $scopeId, $scope);
//
//        $helperMock->handleResendDataAfter();
//    }

//    public function testResetMCEcommerceData()
//    {
//        $scopeId = 0;
//        $scope = 'default';
//        $deleteDataInMailchimp = true;
//
//        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
//            ->disableOriginalConstructor()
//            ->setMethods(array('getGeneralList', 'getMCStoreId', 'removeEcommerceSyncData', 'resetCampaign', 'clearErrorGrid', 'deleteStore', 'isEcomSyncDataEnabled'))
//            ->getMock();
//
//        $helperMock->expects($this->once())->method('getGeneralList')->with($scopeId, $scope)->willReturn('a1s2d3f4g5');
//        $helperMock->expects($this->once())->method('getMCStoreId')->with($scopeId, $scope)->willReturn('q1w2e3r4t5y6u7i8o9p0');
//        $helperMock->expects($this->once())->method('removeEcommerceSyncData')->with($scopeId, $scope);
//        $helperMock->expects($this->once())->method('resetCampaign')->with($scopeId, $scope);
//        $helperMock->expects($this->once())->method('clearErrorGrid')->with($scopeId, $scope, true);
//        $helperMock->expects($this->once())->method('deleteStore')->with($scopeId, $scope);
//
//        $helperMock->resetMCEcommerceData($scopeId, $scope, $deleteDataInMailchimp);
//    }

//    public function testSaveMailChimpConfig()
//    {
//        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
//            ->disableOriginalConstructor()
//            ->setMethods(array('getConfig'))
//            ->getMock();
//
//        $configMock = $this->getMockBuilder(Mage_Core_Model_Config_Data::class)
//            ->disableOriginalConstructor()
//            ->setMethods(array('saveConfig', 'cleanCache'))
//            ->getMock();
//
//
//        $helperMock->expects($this->exactly(2))->method('getConfig')->willReturn($configMock);
//        $configMock->expects($this->once())->method('saveConfig')->with(Ebizmarts_MailChimp_Model_Config::GENERAL_MIGRATE_FROM_116, 1, 'default', 0);
//        $configMock->expects($this->once())->method('cleanCache');
//
//        $helperMock->saveMailChimpConfig(array(array(Ebizmarts_MailChimp_Model_Config::GENERAL_MIGRATE_FROM_116, 1)), 0, 'default');
//    }
}