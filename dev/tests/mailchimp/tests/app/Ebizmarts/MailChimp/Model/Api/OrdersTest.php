<?php

class Ebizmarts_MailChimp_Model_Api_OrdersTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Ebizmarts_MailChimp_Model_Api_Orders
     */
    protected $_ordersApiMock;

    public function setUp()
    {
        Mage::app('default');

        $this->_ordersApiMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Orders::class);
    }

    public function tearDown()
    {
        $this->_ordersApiMock = null;
    }

    public function testCreateBatchJson()
    {
        $mailchimpStoreId = 'a1s2d3f4g5h6j7k8l9n0';
        $magentoStoreId = 1;
        $batchArray = array();

        $ordersApiMock = $this->_ordersApiMock
            ->setMethods(
                array(
                        'getMailchimpStoreId', 'getMagentoStoreId', 'createEcommerceOrdersCollection',
                        'getHelper', '_getModifiedOrders', '_getNewOrders', 'getDateHelper'
                    )
            )->getMock();

        $ordersCollectionResourceMock =
            $this->getMockBuilder(Ebizmarts_MailChimp_Model_Resource_Ecommercesyncdata_Orders_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('setMailchimpStoreId', 'setStoreId'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getEcommerceFirstDate', 'getResendTurn', 'getCurrentStoreId', 'setCurrentStore'))
            ->getMock();

        $helperDateMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Date::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getDateMicrotime'))
            ->getMock();

        $ordersApiMock->expects($this->once())->method('getMailchimpStoreId')->willReturn($mailchimpStoreId);
        $ordersApiMock->expects($this->once())->method('getMagentoStoreId')->willReturn($magentoStoreId);

        $ordersApiMock->expects($this->once())->method('createEcommerceOrdersCollection')
            ->willReturn($ordersCollectionResourceMock);

        $ordersCollectionResourceMock->expects($this->once())->method('setMailchimpStoreId')->with($mailchimpStoreId);
        $ordersCollectionResourceMock->expects($this->once())->method('setStoreId')->with($magentoStoreId);

        $ordersApiMock->expects($this->once())->method('getHelper')->willReturn($helperMock);
        $ordersApiMock->expects($this->once())->method('getDateHelper')->willReturn($helperDateMock);

        $helperMock->expects($this->once())->method('getCurrentStoreId')->willReturn($magentoStoreId);
        $helperMock->expects($this->exactly(2))->method('setCurrentStore')
            ->withConsecutive(
                array($magentoStoreId),
                array($magentoStoreId)
            );

        $helperMock->expects($this->once())->method('getEcommerceFirstDate')
            ->with($magentoStoreId)->willReturn(null);

        $helperDateMock->expects($this->once())->method('getDateMicrotime')->willReturn('00-00-00 00:00:00');
        $helperMock->expects($this->once())->method('getResendTurn')->with($magentoStoreId)->willReturn(null);

        $ordersApiMock->expects($this->once())->method('_getModifiedOrders')->willReturn($batchArray);
        $ordersApiMock->expects($this->once())->method('_getNewOrders')->willReturn($batchArray);

        $ordersApiMock->createBatchJson();
    }

    /**
     * @dataProvider getPromoDataProvider
     */
    public function testGetPromoData($type)
    {

        if ($type == 'by_percent') {
            $assertType = 'percentage';
        } else {
            $assertType = 'fixed';
        }

        $modelMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Orders::class)
            ->disableOriginalConstructor()
            ->setMethods(array('makeSalesRuleCoupon', 'makeSalesRule', 'getSimpleAction'))
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

    public function testGetSyncedOrder()
    {
        $orderId = 1;
        $mailchimpStoreId = '5axx998994cxxxx47e6b3b5dxxxx26e2';

        $modelMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Orders::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getMailchimpEcommerceSyncDataModel'))
            ->getMock();

        $ecommerceMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Ecommercesyncdata::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getMailchimpSyncedFlag', 'getId', 'getEcommerceSyncDataItem'))
            ->getMock();

        $modelMock->expects($this->once())
            ->method('getMailchimpEcommerceSyncDataModel')
            ->willReturn($ecommerceMock);

        $ecommerceMock->expects($this->once())
            ->method('getEcommerceSyncDataItem')
            ->with($orderId, 'ORD', $mailchimpStoreId)
            ->willReturn($ecommerceMock);

        $ecommerceMock->expects($this->once())->method('getMailchimpSyncedFlag')->willReturn(1);
        $ecommerceMock->expects($this->once())->method('getId')->willReturn(1);

        $result = $modelMock->getSyncedOrder($orderId, $mailchimpStoreId);

        $this->assertEquals($result, array('synced_status' => 1, 'order_id' => 1));
    }

    public function testGeneratePOSTPayload()
    {
        $magentoStoreId = 1;
        $statusArray = array('financial_status' => '', 'fulfillment_status' => '');
        $customerEmail = 'test@ebizmarts.com';
        $customerFirstName = 'testFirstName';
        $billingAddressStreet = array('billingAddress1', 'billingAddress2');
        $currentDate = now();

        $lines['itemsCount'] = 9;
        $lines['lines'][] = array(
            "id" => 1,
            "product_id" => 2,
            "product_variant_id" => 2,
            "quantity" => 2,
            "price" => 200,
            "discount" => 9
        );

        $orderId = 12;
        $data = array();
        $data['id'] = $orderId;
        $data['currency_code'] = "USD";
        $data['order_total'] = 100;
        $data['tax_total'] = 3;
        $data['discount_total'] = 4;
        $data['shipping_total'] = 2;
        $data['promos'] = array();
        $data['financial_status'] = $statusArray['financial_status'];
        $data['fulfillment_status'] = $statusArray['fulfillment_status'];
        $data['processed_at_foreign'] = $currentDate;
        $data['updated_at_foreign'] = $currentDate;
        $data['cancelled_at_foreign'] = $currentDate;
        $data['lines'] = $lines['lines'];
        $data['customer'] = array(
            'id' => '66ceb8736aefb347ac63da0d588b29a6',
            'email_address' => 'test@ebizmarts.com',
            'opt_in_status' => false,
            'first_name' => 'testFirstName'
        );
        $data['order_url'] = 'http://somedomain.com';

        $ordersApiMock = $this->_ordersApiMock
            ->setMethods(
                array(
                    'getMagentoStoreId',
                    '_getPayloadData', '_getPayloadDataLines', '_getPayloadBilling',
                    '_getPayloadShipping', 'getCustomerModel', 'getStoreModelFromMagentoStoreId',
                    'getSubscriberModel'
                )
            )
            ->getMock();

        $subscriberMock = $this->getMockBuilder(Mage_Newsletter_Model_Subscriber::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getOptIn', 'loadByEmail', 'subscribe', 'getSubscriberId'))
            ->getMock();

        $orderMock = $this->getMockBuilder(Mage_Sales_Model_Order::class)
            ->disableOriginalConstructor()
            ->setMethods(
                array(
                    'getCustomerEmail',
                    'getId',
                    'getCustomerFirstname',
                    'getCustomerLastname',
                    'getBillingAddress',
                    'getShippingAddress'
                )
            )
            ->getMock();

        $billingAddressMock = $this->getMockBuilder(Mage_Sales_Model_Order_Address::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getStreet'))
            ->getMock();

        $ordersApiMock->expects($this->once())
            ->method('getMagentoStoreId')
            ->willReturn($magentoStoreId);

        $ordersApiMock->expects($this->once())
            ->method('_getPayloadData')
            ->with($orderMock)
            ->willReturn($data);

        $ordersApiMock->expects($this->once())
            ->method('_getPayloadDataLines')
            ->with($orderMock)
            ->willReturn($lines);

        $orderMock->expects($this->exactly(4))
            ->method('getCustomerEmail')
            ->willReturnOnConsecutiveCalls(
                $customerEmail,
                $customerEmail,
                $customerEmail,
                $customerEmail
            );

        $ordersApiMock->expects($this->once())
            ->method('getSubscriberModel')
            ->willReturn($subscriberMock);

        $subscriberMock->expects($this->once())
            ->method('getOptIn')->with($magentoStoreId)
            ->willReturn(true);

        $subscriberMock->expects($this->once())
            ->method('loadByEmail')->with($customerEmail)
            ->willReturn($subscriberMock);

        $subscriberMock->expects($this->once())
            ->method('getSubscriberId')
            ->willReturn(false);

        $subscriberMock->expects($this->once())
            ->method('subscribe')->with($customerEmail);

        $storeMock = $this->getMockBuilder(Mage_Core_Model_Resource_Store::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getUrl'))
            ->getMock();

        $ordersApiMock->expects($this->once())
            ->method('getStoreModelFromMagentoStoreId')
            ->with($magentoStoreId)
            ->willReturn($storeMock);

        $storeMock->expects($this->once())
            ->method('getUrl')
            ->with(
                'sales/order/view/',
                array(
                    'order_id' => $orderId,
                    '_nosid' => true,
                    '_secure' => true
                )
            )
            ->willReturn('http://somedomain.com');

        $orderMock->expects($this->once())
            ->method('getId')
            ->willReturn($orderId);

        $orderMock->expects($this->exactly(2))
            ->method('getCustomerFirstname')
            ->willReturnOnConsecutiveCalls($customerFirstName, $customerFirstName);

        $orderMock->expects($this->once())
            ->method('getBillingAddress')
            ->willReturn($billingAddressMock);

        $billingAddressMock->expects($this->once())
            ->method('getStreet')
            ->willReturn($billingAddressStreet);

        $ordersApiMock->expects($this->once())
            ->method('_getPayloadBilling')
            ->with($data, $billingAddressMock, $billingAddressStreet);

        $shippingAddressMock = $this->getMockBuilder(Mage_Sales_Model_Order_Address::class)
            ->setMethods(array('getStreet'))
            ->getMock();

        $orderMock->expects($this->once())
            ->method('getShippingAddress')
            ->willReturn($shippingAddressMock);

        $ordersApiMock->expects($this->once())
            ->method('_getPayloadShipping')
            ->with($data, $shippingAddressMock);

        $ordersApiMock->GeneratePOSTPayload($orderMock);
    }

    public function testShouldSendCampaignId()
    {
        $mailchimpCampaignId = 'ddf1830cf9';
        $orderId = 1;
        $magentoStoreId = '1';
        $listId = 'c7ce5a3c4e';
        $apiKey = 'asdasdqweqweqwedasd484848asd15';
        $campaignData = array(
            'recipients' => array(
                'list_id' => $listId,
                'list_is_active' => 1,
                'list_name' => 'test'
            )
        );

        $ordersApiMock = $this->_ordersApiMock
            ->setMethods(array('getHelper', 'getMagentoStoreId'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getGeneralList', 'getApiKey', 'getApi'))
            ->getMock();

        $apiMock = $this->getMockBuilder(Ebizmarts_MailChimp::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getCampaign'))
            ->getMock();

        $campaignMock = $this->getMockBuilder(MailChimp_Campaigns::class)
            ->disableOriginalConstructor()
            ->setMethods(array('get'))
            ->getMock();

        $ordersApiMock->expects($this->once())
            ->method('getMagentoStoreId')
            ->willReturn($magentoStoreId);

        $ordersApiMock->expects($this->once())
            ->method('getHelper')
            ->willReturn($helperMock);

        $helperMock->expects($this->once())
            ->method('getGeneralList')
            ->with($magentoStoreId)
            ->willReturn($listId);

        $helperMock->expects($this->once())
            ->method('getApiKey')
            ->with($magentoStoreId)
            ->willReturn($apiKey);

        $helperMock->expects($this->once())
            ->method('getApi')
            ->with($magentoStoreId)
            ->willReturn($apiMock);

        $apiMock->expects($this->once())
            ->method('getCampaign')
            ->willReturn($campaignMock);

        $campaignMock->expects($this->once())
            ->method('get')
            ->with($mailchimpCampaignId, 'recipients')
            ->willReturn($campaignData);

        $ordersApiMock->shouldSendCampaignId($mailchimpCampaignId, $orderId);
    }
}
