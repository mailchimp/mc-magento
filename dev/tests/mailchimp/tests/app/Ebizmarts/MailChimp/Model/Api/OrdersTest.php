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

    public function testGetPromoData($type)
    {

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

    public function testGetSyncedOrder()
    {

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

    public function testGeneratePOSTPayload()
    {
        $mailchimpStoreId = '44a100c71040d4ec27fd707d7c667114';
        $magentoStoreId = '1';
        $oldStore = $magentoStoreId;
        $orderIncrementId = 100;
        $campaignId = 'b6asda8q5';
        $mailchimpLandingPage = 'test';
        $currencyCode = 'USD';
        $baseGrandTotal = 200;
        $baseTaxAmount = 0;
        $taxTotal = 0;
        $baseDiscountAmount = 0;
        $baseShippingAmount = 5;
        $shippingTotal = 0;
        $dataPromo = '';
        $statusArray = array('financial_status' => '', 'fulfillment_status' => '');
        $processedAtForeign = '2017-05-18';
        $updatedAtForeign = '2017-05-18';
        $productId = 15;
        $isTypeProduct = true;
        $options = array('simple_sku' => 'sku-test');
        $sku = 'sku-test';
        $variant = 1;
        $mailchimpSyncError = '';
        $customerEmail = 'test@ebizmarts.com';
        $optInStatus = false;
        $orderUrl = 'test';
        $customerFirstName = 'testFirstName';
        $customerLastName = 'testLastName';
        $billingAddressStreet = array('billingAddress1', 'billingAddress2');
        $billingAddressCity = 'billingCity';
        $billingAddressRegion = 'billingRegion';
        $billingAddressRegionCode = 'billingRegionCode';
        $billingAddressPostCode = 'billingPostCode';
        $billingAddressCountry = 'billingCountry';
        $billingAddressName = 'billingName';
        $billingAddressCompany = 'billingCompany';
        $countryName = 'countryName';
        $shippingAddressStreet = array('shippingAddress1', 'shippingAddress2');
        $shippingAddressCity = 'shippingCity';
        $shippingAddressRegion = 'shippingRegion';
        $shippingAddressRegionCode = 'shippingRegionCode';
        $shippingAddressPostCode = 'shippingPostCode';
        $shippingAddressCountry = 'shippingCountry';
        $shippingAddressName = 'shippingName';
        $grandTotal = 12;
        $totalRefund = 0;
        $totalCanceled = 0;
        $state = 'state';
        $arraySate = array(array('neq' => 'canceled'), array('neq' => 'closed'));
        $customerEmailString = 'customer_email';
        $arrayCustomerEmail = array('eq' => $customerEmail);
        $qtyOrdered = 2;
        $price = 210;
        $discountAmount = 0;
        $isProductEnabled = true;

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getEcommerceSyncDataItem','getCurrentStoreId','setCurrentStore'))
            ->getMock();

        $apiProductMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Products::class)
            ->disableOriginalConstructor()
            ->setMethods(array(
                'isProductEnabled'
            ))
            ->getMock();

        $ordersApiMock = $this->ordersApiMock
            ->setMethods(array(
                'returnZeroIfNull',
                'getPromoData',
                '_getMailChimpStatus',
                'isOrderCanceled',
                'isTypeProduct',
                'getHelper',
                'isItemConfigurable',
                'getModelProduct',
                'getCustomerModel',
                'getStoreModelFromMagentoStoreId',
                'getCountryModelNameFromBillingAddress',
                'getCountryModelNameFromShippingAddress',
                'getResourceModelOrderCollection',
                'shouldSendCampaignId',
                'getApiProduct'
            ))
            ->getMock();

        $orderMock = $this->getMockBuilder(Mage_Sales_Model_Order::class)
            ->disableOriginalConstructor()
            ->setMethods(array(
                'getIncrementId',
                'getMailchimpCampaignId',
                'getMailchimpLandingPage',
                'getOrderCurrencyCode',
                'getGrandTotal',
                'getTaxAmount',
                'getDiscountAmount',
                'getShippingAmount',
                'getCreatedAt',
                'getUpdatedAt',
                'getAllVisibleItems',
                'getCustomerEmail',
                'getId',
                'getCustomerFirstname',
                'getCustomerLastname',
                'getBillingAddress',
                'getShippingAddress'
            ))
            ->getMock();

        $itemsOrderCollection = $this->getMockBuilder(Mage_Sales_Model_Resource_Order_Item_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getIterator'))
            ->getMock();

        $itemOrderMock = $this->getMockBuilder(Mage_Sales_Model_Order_Item::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getProductId', 'getProductOptions', 'getQtyOrdered', 'getPrice', 'getDiscountAmount'))
            ->getMock();

        $productModelMock = $this->getMockBuilder(Mage_Catalog_Model_Product::class)
            ->setMethods(array('getIdBySku'))
            ->getMock();

        $productSyncDataMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Synchbatches::class)
            ->setMethods(array('getMailchimpSyncDelta', 'getMailchimpSyncError'))
            ->getMock();

        $customerModelMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Customers::class)
            ->setMethods(array('getOptin'))
            ->getMock();

        $storeMock = $this->getMockBuilder(Mage_Core_Model_Resource_Store::class)
            ->setMethods(array('getUrl'))
            ->getMock();

        $billingAddressMock = $this->getMockBuilder(Mage_Sales_Model_Order_Address::class)
            ->setMethods(array(
                'getStreet',
                'getCity',
                'getRegion',
                'getRegionCode',
                'getPostcode',
                'getCountry',
                'getName',
                'getCompany'
            ))
            ->getMock();

        $shippingAddressMock = $this->getMockBuilder(Mage_Sales_Model_Order_Address::class)
            ->setMethods(array(
                'getStreet',
                'getCity',
                'getRegion',
                'getRegionCode',
                'getPostcode',
                'getCountry',
                'getName',
                'getCompany'
            ))
            ->getMock();

        $orderCollectionMock = $this->getMockBuilder(Mage_Sales_Model_Resource_Order_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getIterator', 'addFieldToFilter', 'addAttributeToFilter'))
            ->getMock();

        $orderModelMock = $this->getMockBuilder(Mage_Sales_Model_Resource_Order::class)
            ->setMethods(array('getGrandTotal', 'getTotalRefunded', 'getTotalCanceled'))
            ->getMock();

        $orderMock->expects($this->once())
            ->method('getIncrementId')
            ->willReturn($orderIncrementId);
        $orderMock->expects($this->once())
            ->method('getMailchimpCampaignId')
            ->willReturn($campaignId);
        $orderMock->expects($this->exactly(2))
            ->method('getMailchimpLandingPage')
            ->willReturnOnConsecutiveCalls(
                $mailchimpLandingPage,
                $mailchimpLandingPage
            );
        $orderMock->expects($this->once())
            ->method('getOrderCurrencyCode')
            ->willReturn($currencyCode);
        $orderMock->expects($this->once())
            ->method('getGrandTotal')
            ->willReturn($baseGrandTotal);
        $orderMock->expects($this->once())
            ->method('getTaxAmount')
            ->willReturn($baseTaxAmount);
        $orderMock->expects($this->once())
            ->method('getDiscountAmount')
            ->willReturn($baseDiscountAmount);
        $orderMock->expects($this->once())
            ->method('getShippingAmount')
            ->willReturn($baseShippingAmount);
        $orderMock->expects($this->once())
            ->method('getCreatedAt')
            ->willReturn($processedAtForeign);
        $orderMock->expects($this->once())
            ->method('getUpdatedAt')
            ->willReturn($updatedAtForeign);
        $orderMock->expects($this->once())
            ->method('getAllVisibleItems')
            ->willReturn($itemsOrderCollection);
        $orderMock->expects($this->exactly(3))
            ->method('getCustomerEmail')
            ->willReturnOnConsecutiveCalls(
                $customerEmail,
                $customerEmail,
                $customerEmail
            );
        $orderMock->expects($this->once())
            ->method('getId')
            ->willReturn($orderIncrementId);
        $orderMock->expects($this->exactly(2))
            ->method('getCustomerFirstname')
            ->willReturnOnConsecutiveCalls(
                $customerFirstName,
                $customerFirstName
            );
        $orderMock->expects($this->exactly(2))
            ->method('getCustomerLastname')
            ->willReturnOnConsecutiveCalls(
                $customerLastName,
                $customerLastName
            );
        $orderMock->expects($this->once())
            ->method('getBillingAddress')
            ->willReturn($billingAddressMock);
        $orderMock->expects($this->once())
            ->method('getShippingAddress')
            ->willReturn($shippingAddressMock);

        $ordersApiMock->expects($this->exactly(2))
            ->method('returnZeroIfNull')
            ->withConsecutive(
                array($baseTaxAmount),
                array($baseShippingAmount)
            )
            ->willReturnOnConsecutiveCalls(
                $taxTotal,
                $shippingTotal
            );
        $ordersApiMock->expects($this->once())
            ->method('getPromoData')
            ->with($orderMock)
            ->willReturn($dataPromo);
        $ordersApiMock->expects($this->once())
            ->method('shouldSendCampaignId')
            ->with($campaignId, $magentoStoreId)
            ->willReturn(true);
        $ordersApiMock->expects($this->once())
            ->method('_getMailChimpStatus')
            ->with($orderMock)
            ->willReturn($statusArray);
        $ordersApiMock->expects($this->once())
            ->method('isOrderCanceled')
            ->willReturn(false);
        $ordersApiMock->expects($this->once())
            ->method('isTypeProduct')
            ->willReturn($isTypeProduct);
        $ordersApiMock->expects($this->once())
            ->method('getHelper')
            ->willReturn($helperMock);
        $ordersApiMock->expects($this->once())
            ->method('isItemConfigurable')
            ->willReturn(true);
        $ordersApiMock->expects($this->once())
            ->method('getModelProduct')
            ->willReturn($productModelMock);
        $ordersApiMock->expects($this->once())
            ->method('getCustomerModel')
            ->willReturn($customerModelMock);
        $ordersApiMock->expects($this->once())
            ->method('getStoreModelFromMagentoStoreId')
            ->with($magentoStoreId)
            ->willReturn($storeMock);
        $ordersApiMock->expects($this->once())
            ->method('getCountryModelNameFromBillingAddress')
            ->with($billingAddressMock)
            ->willReturn($countryName);
        $ordersApiMock->expects($this->once())
            ->method('getCountryModelNameFromShippingAddress')
            ->with($shippingAddressMock)
            ->willReturn($countryName);
        $ordersApiMock->expects($this->once())
            ->method('getResourceModelOrderCollection')
            ->willReturn($orderCollectionMock);
        $ordersApiMock->expects($this->once())
            ->method('getApiProduct')
            ->willReturn($apiProductMock);

        $apiProductMock->expects($this->once())
            ->method('isProductEnabled')
            ->willReturn($isProductEnabled);

        $itemsOrderCollection->expects($this->once())
            ->method('getIterator')
            ->willReturn(new ArrayIterator(array($itemOrderMock)));

        $itemOrderMock->expects($this->once())
            ->method('getProductId')
            ->willReturn($productId);
        $itemOrderMock->expects($this->once())
            ->method('getProductOptions')
            ->willReturn($options);
        $itemOrderMock->expects($this->once())
            ->method('getQtyOrdered')
            ->willReturn($qtyOrdered);
        $itemOrderMock->expects($this->once())
            ->method('getPrice')
            ->willReturn($price);
        $itemOrderMock->expects($this->once())
            ->method('getDiscountAmount')
            ->willReturn($discountAmount);

        $productModelMock->expects($this->once())
            ->method('getIdBySku')
            ->with($sku)
            ->willReturn($variant);

        $helperMock->expects($this->once())
            ->method('getCurrentStoreId')
            ->willReturn($magentoStoreId);

        $helperMock->expects($this->exactly(2))
            ->method('setCurrentStore')
            ->withConsecutive(
                $magentoStoreId,
                $oldStore
            );


        $helperMock->expects($this->once())
            ->method('getEcommerceSyncDataItem')
            ->with($productId, $isTypeProduct, $mailchimpStoreId)
            ->willReturn($productSyncDataMock);

        $productSyncDataMock->expects($this->once())
            ->method('getMailchimpSyncDelta')
            ->willReturn(true);
        $productSyncDataMock->expects($this->once())
            ->method('getMailchimpSyncError')
            ->willReturn($mailchimpSyncError);

        $customerModelMock->expects($this->once())
            ->method('getOptin')
            ->willReturn($optInStatus);

        $storeMock->expects($this->once())
            ->method('getUrl')
            ->willReturn($orderUrl);

        $billingAddressMock->expects($this->once())
            ->method('getStreet')
            ->willReturn($billingAddressStreet);
        $billingAddressMock->expects($this->exactly(2))
            ->method('getCity')
            ->willReturnOnConsecutiveCalls(
                $billingAddressCity,
                $billingAddressCity
            );
        $billingAddressMock->expects($this->exactly(2))
            ->method('getRegion')
            ->willReturnOnConsecutiveCalls(
                $billingAddressRegion,
                $billingAddressRegion
            );
        $billingAddressMock->expects($this->exactly(2))
            ->method('getRegionCode')
            ->willReturnOnConsecutiveCalls(
                $billingAddressRegionCode,
                $billingAddressRegionCode
            );
        $billingAddressMock->expects($this->exactly(2))
            ->method('getPostcode')
            ->willReturnOnConsecutiveCalls(
                $billingAddressPostCode,
                $billingAddressPostCode
            );
        $billingAddressMock->expects($this->exactly(2))
            ->method('getCountry')
            ->willReturnOnConsecutiveCalls(
                $billingAddressCountry,
                $billingAddressCountry
            );
        $billingAddressMock->expects($this->exactly(2))
            ->method('getName')
            ->willReturnOnConsecutiveCalls(
                $billingAddressName,
                $billingAddressName
            );
        $billingAddressMock->expects($this->exactly(2))
            ->method('getCompany')
            ->willReturnOnConsecutiveCalls(
                $billingAddressCompany,
                $billingAddressCompany
            );

        $shippingAddressMock->expects($this->once())
            ->method('getStreet')
            ->willReturn($shippingAddressStreet);
        $shippingAddressMock->expects($this->exactly(2))
            ->method('getName')
            ->willReturnOnConsecutiveCalls(
                $shippingAddressName,
                $shippingAddressName
            );
        $shippingAddressMock->expects($this->exactly(2))
            ->method('getCity')
            ->willReturnOnConsecutiveCalls(
                $shippingAddressCity,
                $shippingAddressCity
            );
        $shippingAddressMock->expects($this->exactly(2))
            ->method('getRegion')
            ->willReturnOnConsecutiveCalls(
                $shippingAddressRegion,
                $shippingAddressRegion
            );
        $shippingAddressMock->expects($this->exactly(2))
            ->method('getRegionCode')
            ->willReturnOnConsecutiveCalls(
                $shippingAddressRegionCode,
                $shippingAddressRegionCode
            );
        $shippingAddressMock->expects($this->exactly(2))
            ->method('getPostcode')
            ->willReturnOnConsecutiveCalls(
                $shippingAddressPostCode,
                $shippingAddressPostCode
            );
        $shippingAddressMock->expects($this->exactly(2))
            ->method('getCountry')
            ->willReturnOnConsecutiveCalls(
                $shippingAddressCountry,
                $shippingAddressCountry
            );


        $orderCollectionMock->expects($this->once())
            ->method('getIterator')
            ->willReturn(new ArrayIterator(array($orderModelMock)));
        $orderCollectionMock->expects($this->once())
            ->method('addFieldToFilter')
            ->with($state, $arraySate)
            ->willReturnSelf();
        $orderCollectionMock->expects($this->once())
            ->method('addAttributeToFilter')
            ->with($customerEmailString, $arrayCustomerEmail)
            ->willReturnSelf();

        $orderModelMock->expects($this->once())
            ->method('getGrandTotal')
            ->willReturn($grandTotal);
        $orderModelMock->expects($this->once())
            ->method('getTotalRefunded')
            ->willReturn($totalRefund);
        $orderModelMock->expects($this->once())
            ->method('getTotalCanceled')
            ->willReturn($totalCanceled);

        $ordersApiMock->GeneratePOSTPayload($orderMock, $mailchimpStoreId, $magentoStoreId);
    }

    public function testShouldSendCampaignId()
    {
        $mailchimpCampaignId = 'ddf1830cf9';
        $magentoStoreId = '1';
        $listId = 'c7ce5a3c4e';
        $apiKey = 'asdasdqweqweqwedasd484848asd15';
        $campaignData = array('recipients' => array('list_id' => $listId, 'list_is_active' => 1, 'list_name' => 'test'));

        $ordersApiMock = $this->ordersApiMock
            ->setMethods(array('getHelper'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array(
                'getGeneralList',
                'getApiKey',
                'getApi'))
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

        $ordersApiMock->shouldSendCampaignId($mailchimpCampaignId, $magentoStoreId);
    }
}
