<?php

class Ebizmarts_MailChimp_Model_Api_OrdersTest extends PHPUnit_Framework_TestCase
{
    /**
 * @var Ebizmarts_MailChimp_Model_Api_Orders
*/
    private $ordersApiMock;

    public function setUp()
    {
        Mage::app('default');

        $this->ordersApiMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Orders::class);
    }

    public function tearDown()
    {
        $this->ordersApiMock = null;
    }

    public function testCreateBatchJson()
    {
        $mailchimpStoreId = 'a1s2d3f4g5h6j7k8l9n0';
        $magentoStoreId = 1;
        $batchArray = array();

        $ordersApiMock = $this->ordersApiMock->setMethods(array('getHelper', '_getModifiedOrders', '_getNewOrders'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getEcommerceFirstDate', 'getResendTurn', 'getDateMicrotime'))
            ->getMock();

        $ordersApiMock->expects($this->once())->method('getHelper')->willReturn($helperMock);
        $ordersApiMock->expects($this->once())->method('_getModifiedOrders')->with($mailchimpStoreId, $magentoStoreId)->willReturn($batchArray);
        $ordersApiMock->expects($this->once())->method('_getNewOrders')->with($mailchimpStoreId, $magentoStoreId)->willReturn($batchArray);

        $helperMock->expects($this->once())->method('getEcommerceFirstDate')->with($magentoStoreId)->willReturn(null);
        $helperMock->expects($this->once())->method('getDateMicrotime')->willReturn('00-00-00 00:00:00');
        $helperMock->expects($this->once())->method('getResendTurn')->with($magentoStoreId)->willReturn(null);

        $ordersApiMock->createBatchJson($mailchimpStoreId, $magentoStoreId);
    }
}