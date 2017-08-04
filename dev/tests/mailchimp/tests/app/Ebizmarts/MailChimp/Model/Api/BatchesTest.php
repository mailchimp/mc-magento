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

    public function testSendEcommerceBatch()
    {
        $apiBatchesMock = $this->apiBatchesMock->setMethods(
            array('getHelper', 'isMailChimpEnabled', 'isEcomSyncDataEnabled',
            'getApiCustomers', 'getApiProducts', 'getApiCarts', 'getApiOrders', 'deleteUnsentItems', 'markItemsAsSent')
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

        $apiMock = $this->getMockBuilder(Ebizmarts_MailChimp::class)
            ->disableOriginalConstructor()
//                    ->setMethods(array('edit'))
            ->getMock();

        $apiBatchesMock->expects($this->once())->method('getHelper')->willReturn($helperMock);
        $apiBatchesMock->expects($this->once())->method('getApiCustomers')->willReturn($apiCustomersMock);
        $apiBatchesMock->expects($this->once())->method('getApiProducts')->willReturn($apiProductsMock);
        $apiBatchesMock->expects($this->once())->method('getApiCarts')->willReturn($apiCartsMock);
        $apiBatchesMock->expects($this->once())->method('getApiOrders')->willReturn($apiOrdersMock);
        $helperMock->expects($this->once())->method('getMCStoreId')->with(1)->willReturn('b81c3085c51fa593e1d6b0cf59884f3e');
        $helperMock->expects($this->once())->method('isMailChimpEnabled')->with(1)->willReturn(1);
        $helperMock->expects($this->once())->method('isEcomSyncDataEnabled')->with(1)->willReturn(1);
        $helperMock->expects($this->once())->method('getMCIsSyncing')->with(1)->willReturn(0);

        $apiCustomersMock->expects($this->once())->method('createBatchJson')->with('b81c3085c51fa593e1d6b0cf59884f3e', 1);
        $apiProductsMock->expects($this->once())->method('createBatchJson')->with('b81c3085c51fa593e1d6b0cf59884f3e', 1);
        $apiCartsMock->expects($this->once())->method('createBatchJson')->with('b81c3085c51fa593e1d6b0cf59884f3e', 1);
        $apiOrdersMock->expects($this->once())->method('createBatchJson')->with('b81c3085c51fa593e1d6b0cf59884f3e', 1);
        $helperMock->expects($this->once())->method('getApi')->with(1)->willReturn($apiMock);

        $apiBatchesMock->_sendEcommerceBatch(1);
    }

}