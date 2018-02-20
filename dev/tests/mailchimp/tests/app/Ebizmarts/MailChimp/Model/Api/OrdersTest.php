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

      /**
     * @dataProvider getPromoDataProvider
     */

    public function testGetPromoData($type){

        if ($type == 'by_percent') {
            $assertType = 'percentage';
        } else {
            $assertType = 'fixed';
        }

        $modelMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Orders::class)
            ->disableOriginalConstructor()
            ->setMethods(array('makeSalesRuleCoupon','makeSalesRule','getSimpleAction'))
            ->getMock();

        $orderMock = $this->getMockBuilder(Mage_Sales_Model_Order::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getCouponCode', 'getBaseDiscountAmount'))
            ->getMock();

        $couponMock = $this->getMockBuilder(Mage_SalesRule_Model_Coupon::class)
            ->disableOriginalConstructor()
            ->setMethods(array('load', 'getRuleId', 'getCouponId'))
            ->getMock();

        $ruleMock = $this->getMockBuilder(Mage_SalesRule_Model_Rule::class)
            ->disableOriginalConstructor()
            ->setMethods(array('load', 'getSimpleAction', 'getRuleId'))
            ->getMock();

        $modelMock->expects($this->once())->method('makeSalesRuleCoupon')->willReturn($couponMock);
        $modelMock->expects($this->once())->method('makeSalesRule')->willReturn($ruleMock);

        $couponMock->expects($this->once())->method('getRuleId')->willReturn(1);
        $couponMock->expects($this->once())->method('getCouponId')->willReturn(1);
        $couponMock->expects($this->once())->method('load')->with('aa12', 'code')->willReturnSelf();

        $ruleMock->expects($this->once())->method('getSimpleAction')->willReturn($type);
        $ruleMock->expects($this->once())->method('getRuleId')->willReturn(1);
        $ruleMock->expects($this->once())->method('load')->with(1)->willReturnSelf();

        $orderMock->expects($this->once())->method('getCouponCode')->willReturn('aa12');
        $orderMock->expects($this->once())->method('getBaseDiscountAmount')->willReturn(10);

        $result = $modelMock->getPromoData($orderMock);

        $this->assertEquals($result, array(array('code' => 'aa12', 'amount_discounted' => 10, 'type' => $assertType)));

    }

    public function getPromoDataProvider()
    {
        return array(
            array('by_percent'),
            array('by_fixed'),
            array('cart_fixed'),
            array('buy_x_get_y')
        );
    }

    public function testGetSyncedOrder(){

        $orderId = 1;
        $mailchimpStoreId = '5axx998994cxxxx47e6b3b5dxxxx26e2';

        $modelMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Orders::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getHelper'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getEcommerceSyncDataItem'))
            ->getMock();

        $ecommerceMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Ecommercesyncdata::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getMailchimpSyncedFlag', 'getId'))
            ->getMock();

        $modelMock->expects($this->once())->method('getHelper')->willReturn($helperMock);

        $helperMock->expects($this->once())->method('getEcommerceSyncDataItem')->with($orderId, 'ORD', $mailchimpStoreId)->willReturn($ecommerceMock);

        $ecommerceMock->expects($this->once())->method('getMailchimpSyncedFlag')->willReturn(1);
        $ecommerceMock->expects($this->once())->method('getId')->willReturn(1);

        $result = $modelMock->getSyncedOrder($orderId, $mailchimpStoreId);

        $this->assertEquals($result, array('synced_status' => 1, 'order_id' => 1));
    }

}
