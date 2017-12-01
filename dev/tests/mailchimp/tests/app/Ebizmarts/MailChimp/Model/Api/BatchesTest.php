<?php

class Ebizmarts_MailChimp_Model_Api_BatchesTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Ebizmarts_MailChimp_Model_Api_Products $apiBatchesMock
     */
    private $apiBatchesMock;

    public function setUp()
    {
        Mage::app('default');
        $this->apiBatchesMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Batches::class);
    }

    public function tearDown()
    {
        $this->apiBatchesMock = null;
    }

    public function testHandleEcommerceBatches()
    {
        $storeId = 1;
        $apiBatchesMock = $this->apiBatchesMock
            ->disableOriginalConstructor()
            ->setMethods(array('getHelper', '_getResults', '_sendEcommerceBatch', 'handleResetIfNecessary', 'addSyncValueToArray', 'handleSyncingValue', 'getStores'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('handleResendDataBefore', 'handleResendDataAfter', 'isEcomSyncDataEnabled'))
            ->getMock();

        $storeArrayMock = $this->getMockBuilder(Mage_Core_Model_Resource_Store_Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $storeMock = $this->getMockBuilder(Mage_Core_Model_Store::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getId'))
            ->getMock();

        $stores = array();
        $stores[] = $storeMock;
        $syncedArray = array();
        $storeMock->expects($this->exactly(2))->method('getId')->willReturn($storeId);
        $storeArrayMock->expects($this->exactly(2))->method("getIterator")->willReturn(new ArrayIterator($stores));

        $helperMock->expects($this->once())->method('handleResendDataBefore');
        $helperMock->expects($this->once())->method('handleResendDataAfter');
        $helperMock->expects($this->once())->method('isEcomSyncDataEnabled')->with($storeId)->willReturn(true);

        $apiBatchesMock->expects($this->once())->method('getHelper')->willReturn($helperMock);
        $apiBatchesMock->expects($this->once())->method('getStores')->willReturn($storeArrayMock);
        $apiBatchesMock->expects($this->once())->method('_getResults')->with($storeId);
        $apiBatchesMock->expects($this->once())->method('_sendEcommerceBatch')->with($storeId);
        $apiBatchesMock->expects($this->once())->method('handleResetIfNecessary')->with($storeId);
        $apiBatchesMock->expects($this->once())->method('addSyncValueToArray')->with($storeId, $syncedArray)->willReturn($syncedArray);

        $apiBatchesMock->handleEcommerceBatches();
    }

    public function testSendEcommerceBatch()
    {
        $apiBatchesMock = $this->apiBatchesMock->setMethods(
            array('getHelper', 'isMailChimpEnabled', 'isEcomSyncDataEnabled', 'getApiCustomers', 'getApiProducts',
                'getApiCarts', 'getApiOrders', 'deleteUnsentItems', 'markItemsAsSent', 'getApiPromoRules', 'getApiPromoCodes')
        )
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getMCStoreId', 'isMailChimpEnabled', 'isEcomSyncDataEnabled', 'getApi', 'getIsReseted', 'getMCIsSyncing'))
            ->getMock();

        $apiCustomersMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Customers::class)
            ->disableOriginalConstructor()
            ->setMethods(array('createBatchJson'))
            ->getMock();


        $apiProductsMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Products::class)
            ->disableOriginalConstructor()
            ->setMethods(array('createBatchJson'))
            ->getMock();


        $apiCartsMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Carts::class)
            ->disableOriginalConstructor()
            ->setMethods(array('createBatchJson'))
            ->getMock();

        $apiOrdersMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Orders::class)
            ->disableOriginalConstructor()
            ->setMethods(array('createBatchJson'))
            ->getMock();

        $apiPromoRulesMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_PromoRules::class)
            ->disableOriginalConstructor()
            ->setMethods(array('createBatchJson'))
            ->getMock();

        $apiPromoCodesMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_PromoCodes::class)
            ->disableOriginalConstructor()
            ->setMethods(array('createBatchJson'))
            ->getMock();

        $apiMock = $this->getMockBuilder(Ebizmarts_MailChimp::class)
            ->disableOriginalConstructor()
            ->getMock();

        $apiBatchesMock->expects($this->once())->method('getHelper')->willReturn($helperMock);
        $apiBatchesMock->expects($this->once())->method('getApiCustomers')->willReturn($apiCustomersMock);
        $apiBatchesMock->expects($this->once())->method('getApiProducts')->willReturn($apiProductsMock);
        $apiBatchesMock->expects($this->once())->method('getApiCarts')->willReturn($apiCartsMock);
        $apiBatchesMock->expects($this->once())->method('getApiOrders')->willReturn($apiOrdersMock);
        $apiBatchesMock->expects($this->once())->method('getApiPromoRules')->willReturn($apiPromoRulesMock);
        $apiBatchesMock->expects($this->once())->method('getApiPromoCodes')->willReturn($apiPromoCodesMock);
        $helperMock->expects($this->once())->method('getMCStoreId')->with(1)->willReturn('b81c3085c51fa593e1d6b0cf59884f3e');
        $helperMock->expects($this->once())->method('isMailChimpEnabled')->with(1)->willReturn(1);
        $helperMock->expects($this->once())->method('isEcomSyncDataEnabled')->with(1)->willReturn(1);
        $helperMock->expects($this->once())->method('getMCIsSyncing')->with(1)->willReturn(0);

        $apiCustomersMock->expects($this->once())->method('createBatchJson')->with('b81c3085c51fa593e1d6b0cf59884f3e', 1);
        $apiProductsMock->expects($this->once())->method('createBatchJson')->with('b81c3085c51fa593e1d6b0cf59884f3e', 1);
        $apiCartsMock->expects($this->once())->method('createBatchJson')->with('b81c3085c51fa593e1d6b0cf59884f3e', 1);
        $apiOrdersMock->expects($this->once())->method('createBatchJson')->with('b81c3085c51fa593e1d6b0cf59884f3e', 1);
        $apiOrdersMock->expects($this->once())->method('createBatchJson')->with('b81c3085c51fa593e1d6b0cf59884f3e', 1);
        $apiOrdersMock->expects($this->once())->method('createBatchJson')->with('b81c3085c51fa593e1d6b0cf59884f3e', 1);
        $apiPromoRulesMock->expects($this->once())->method('createBatchJson')->with('b81c3085c51fa593e1d6b0cf59884f3e', 1);
        $apiPromoCodesMock->expects($this->once())->method('createBatchJson')->with('b81c3085c51fa593e1d6b0cf59884f3e', 1);
        $helperMock->expects($this->once())->method('getApi')->with(1)->willReturn($apiMock);

        $apiBatchesMock->_sendEcommerceBatch(1);
    }

}