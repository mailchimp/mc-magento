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
        $mailchimpStoreId = 'ef3bf57fb9bd695a02b7f7c7fb0d2db5';
        $magentoStoreId = 1;
        $syncingFlag = '2018-02-01 00:00:00';
        $ecomSyncDateFlag = '2018-02-02 00:00:00';
        $configValue = array(array(Ebizmarts_MailChimp_Model_Config::GENERAL_MCISSYNCING, 1));

        $customerArray = $this->getCustomerArray();

        $productsArray = $this->getProductArray();

        $cartsArray = $this->getCartArray();

        $ordersArray = $this->getOrderArray();

        $promoRulesArray = $this->getPromoRuleArray();

        $promoCodesArray = $this->getPromoCodeArray();

        //merge arrays and encode
        $batchArray = array();
        $batchArray['operations'] = $customerArray;
        $batchArray['operations'] = array_merge($batchArray['operations'], $productsArray);
        $batchArray['operations'] = array_merge($batchArray['operations'], $cartsArray);
        $batchArray['operations'] = array_merge($batchArray['operations'], $ordersArray);
        $batchArray['operations'] = array_merge($batchArray['operations'], $promoRulesArray);
        $batchArray['operations'] = array_merge($batchArray['operations'], $promoCodesArray);

        $batchJson = json_encode($batchArray);

        $batchResponse = $this->getBatchResponse();

        $apiBatchesMock = $this->apiBatchesMock
            ->disableOriginalConstructor()
            ->setMethods(
            array('getHelper', 'getApiCustomers', 'getApiProducts',
                'getApiCarts', 'getApiOrders', 'deleteUnsentItems', 'markItemsAsSent', 'getApiPromoRules',
                'getApiPromoCodes', 'getSyncBatchesModel')
        )
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getMCStoreId', 'getEcommMinSyncDateFlag', 'isEcomSyncDataEnabled', 'getApi', 'getIsReseted',
                'getMCIsSyncing', 'logRequest', 'validateDate', 'saveMailchimpConfig'))
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
            ->setMethods(array('getBatchOperation'))
            ->getMock();

        $apiBatchOperationMock = $this->getMockBuilder(MailChimp_BatchOperations::class)
            ->disableOriginalConstructor()
            ->setMethods(array('add'))
            ->getMock();

        $syncBatchesMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Synchbatches::class)
            ->disableOriginalConstructor()
            ->setMethods(array('setStoreId', 'setBatchId', 'setStatus', 'save'))
            ->getMock();

        $apiBatchesMock->expects($this->once())->method('getHelper')->willReturn($helperMock);
        $helperMock->expects($this->once())->method('getMCStoreId')->with($magentoStoreId)->willReturn($mailchimpStoreId);

        $apiBatchesMock->expects($this->once())->method('deleteUnsentItems');

        $helperMock->expects($this->once())->method('isEcomSyncDataEnabled')->with($magentoStoreId)->willReturn(1);

        $apiBatchesMock->expects($this->once())->method('getApiCustomers')->willReturn($apiCustomersMock);
        $apiCustomersMock->expects($this->once())->method('createBatchJson')->with($mailchimpStoreId, $magentoStoreId)->willReturn($customerArray);

        $apiBatchesMock->expects($this->once())->method('getApiProducts')->willReturn($apiProductsMock);
        $apiProductsMock->expects($this->once())->method('createBatchJson')->with($mailchimpStoreId, $magentoStoreId)->willReturn($productsArray);

        $apiBatchesMock->expects($this->once())->method('getApiCarts')->willReturn($apiCartsMock);
        $apiCartsMock->expects($this->once())->method('createBatchJson')->with($mailchimpStoreId, $magentoStoreId)->willReturn($cartsArray);

        $apiBatchesMock->expects($this->once())->method('getApiOrders')->willReturn($apiOrdersMock);
        $apiOrdersMock->expects($this->once())->method('createBatchJson')->with($mailchimpStoreId, $magentoStoreId)->willReturn($ordersArray);

        $apiBatchesMock->expects($this->once())->method('getApiPromoRules')->willReturn($apiPromoRulesMock);
        $apiPromoRulesMock->expects($this->once())->method('createBatchJson')->with($mailchimpStoreId, $magentoStoreId)->willReturn($promoRulesArray);

        $apiBatchesMock->expects($this->once())->method('getApiPromoCodes')->willReturn($apiPromoCodesMock);
        $apiPromoCodesMock->expects($this->once())->method('createBatchJson')->with($mailchimpStoreId, $magentoStoreId)->willReturn($promoCodesArray);

        $helperMock->expects($this->once())->method('getApi')->with($magentoStoreId)->willReturn($apiMock);

        $helperMock->expects($this->once())->method('getIsReseted')->with($magentoStoreId)->willReturn(false);

        $apiMock->expects($this->once())->method('getBatchOperation')->willReturn($apiBatchOperationMock);
        $apiBatchOperationMock->expects($this->once())->method('add')->with($batchJson)->willReturn($batchResponse);

        $helperMock->expects($this->once())->method('logRequest')->with($batchJson, $magentoStoreId, $batchResponse['id']);

        $apiBatchesMock->expects($this->once())->method('getSyncBatchesModel')->willReturn($syncBatchesMock);
        $syncBatchesMock->expects($this->once())->method('setStoreId')->with($mailchimpStoreId)->willReturnSelf();
        $syncBatchesMock->expects($this->once())->method('setBatchId')->with($batchResponse['id'])->willReturnSelf();
        $syncBatchesMock->expects($this->once())->method('setStatus')->with($batchResponse['status'])->willReturnSelf();
        $syncBatchesMock->expects($this->once())->method('save');

        $apiBatchesMock->expects($this->once())->method('markItemsAsSent')->with($batchResponse['id'], $mailchimpStoreId);

        $helperMock->expects($this->once())->method('getMCIsSyncing')->with($magentoStoreId)->willReturn($syncingFlag);
        $helperMock->expects($this->once())->method('validateDate')->with($syncingFlag)->willReturn(true);
        $helperMock->expects($this->once())->method('getEcommMinSyncDateFlag')->with($magentoStoreId)->willReturn($ecomSyncDateFlag);
        $helperMock->expects($this->once())->method('saveMailchimpConfig')->with($configValue, $magentoStoreId, 'stores');


        $apiBatchesMock->_sendEcommerceBatch($magentoStoreId);
    }

    /**
     * @return array
     */
    protected function getBatchResponse()
    {
        return array(
            'id' => 'cfb450eb11',
            'status' => 'pending',
            'total_operations' => 0,
            'finished_operations' => 0,
            'errored_operations' => 0,
            'submitted_at' => '2018-01-16T15:17:03+00:00',
            'completed_at' => null,
            'response_body_url' => null,
            '_links' => array(
                array(
                    'rel' => 'parent',
                    'href' => 'https://us13.api.mailchimp.com/3.0/batches',
                    'method' => 'GET',
                    'targetSchema' => 'https://us13.api.mailchimp.com/schema/3.0/Definitions/Batches/CollectionResponse.json',
                    'schema' => 'https://us13.api.mailchimp.com/schema/3.0/CollectionLinks/Batches.json'
                ),
                array(
                    'rel' => 'self',
                    'href' => 'https://us13.api.mailchimp.com/3.0/batches/cfb450eb11',
                    'method' => 'GET',
                    'targetSchema' => 'https://us13.api.mailchimp.com/schema/3.0/Definitions/Batches/Response.json'
                ),
                array(
                    'rel' => 'delete',
                    'href' => 'https://us13.api.mailchimp.com/3.0/batches/cfb450eb11',
                    'method' => 'DELETE'
                )
            )
        );
    }

    /**
     * @return array
     */
    protected function getPromoCodeArray()
    {
        return array(
            array(
                'method' => 'POST',
                'path' => '/ecommerce/stores/ef3bf57fb9bd695a02b7f7c7fb0d2db5/promo-rules',
                'operation_id' => 'storeid-1_PCD_2018-01-16-14-28-03-31075100_PRL_44',
                'body' => '{"id":"44","title":"testrule","description":"testrule","amount":0.05,"type":"percentage","target":"total","enabled":true}'
            ),
            array(
                'method' => 'POST',
                'path' => '/ecommerce/stores/ef3bf57fb9bd695a02b7f7c7fb0d2db5/promo-rules/44/promo-codes',
                'operation_id' => 'storeid-1_PCD_2018-01-16-14-28-03-31075100_49',
                'body' => '{"id":"49","code":"testcoupon","redemption_url":"http:\/\/127.0.0.1\/mcmagento-1937\/mailchimp\/cart\/loadcoupon?coupon_id=49&coupon_token=9e0c002f6d4b39039bff794a6f294341"}'
            )
        );
    }

    /**
     * @return array
     */
    protected function getPromoRuleArray()
    {
        return array(
            array(
                'method' => 'DELETE',
                'path' => '/ecommerce/stores/ef3bf57fb9bd695a02b7f7c7fb0d2db5/promo-rules/43',
                'operation_id' => 'storeid-2_PRL_2018-01-16-14-48-03-29881000_43',
                'body' => ''
            )
        );
    }

    /**
     * @return array
     */
    protected function getOrderArray()
    {
        return array(
            array(
                'method' => 'POST',
                'path' => '/ecommerce/stores/ef3bf57fb9bd695a02b7f7c7fb0d2db5/orders',
                'operation_id' => 'storeid-1_ORD_2018-01-16-14-28-02-50334200_195',
                'body' => '{"id":"145000006","landing_site":"http:\/\/127.0.0.1\/mcmagento-1937\/","currency_code":"USD","order_total":"300.0000","tax_total":"0.0000","discount_total":0,"shipping_total":"5.0000","promos":[{"code":null,"amount_discounted":"0.0000","type":"fixed"}],"financial_status":"pending","processed_at_foreign":"2018-01-16 14:26:55","updated_at_foreign":"2018-01-16 14:26:55","lines":[{"id":"1","product_id":"337","product_variant_id":"337","quantity":1,"price":"295.0000","discount":0}],"customer":{"id":"137","email_address":"santiago+testtest@ebizmarts.com","opt_in_status":false,"first_name":"Santiago","last_name":"Paragarino","address":{"address1":"address","city":"city","province":"Alabama","province_code":"AL","postal_code":"123456","country":"United States","country_code":"US"},"orders_count":1,"total_spent":600},"order_url":"http:\/\/127.0.0.1\/mcmagento-1937\/sales\/order\/view\/order_id\/195\/?___store=default","billing_address":{"address1":"address","city":"city","province":"Alabama","province_code":"AL","postal_code":"123456","country":"United States","country_code":"US","name":"Santiago Paragarino"},"shipping_address":{"name":"Santiago Paragarino","address1":"address","city":"city","province":"Alabama","province_code":"AL","postal_code":"123456","country":"United States","country_code":"US"}}'
            )
        );
    }

    /**
     * @return array
     */
    protected function getCartArray()
    {
        return array(
            array(
                'method' => 'POST',
                'path' => '/ecommerce/stores/ef3bf57fb9bd695a02b7f7c7fb0d2db5/carts',
                'operation_id' => 'storeid-1_QUO_2018-01-16-14-28-01-40953100_681',
                'body' => '{"id":"681","customer":{"id":"137","email_address":"santiago+testtest@ebizmarts.com","opt_in_status":false,"first_name":"Santiago","last_name":"Paragarino"},"checkout_url":"http:\/\/127.0.0.1\/mcmagento-1937\/mailchimp\/cart\/loadquote?id=681&token=0eaf8c240502056a62b9194dd2ed0859","currency_code":"USD","order_total":"10.0000","tax_total":0,"lines":[{"id":"1","product_id":"906","product_variant_id":"906","quantity":1,"price":"10.0000"}]}'
            )
        );
    }

    /**
     * @return array
     */
    protected function getProductArray()
    {
        return array(
            array(
                'method' => 'PATCH',
                'path' => '/ecommerce/stores/ef3bf57fb9bd695a02b7f7c7fb0d2db5/products/337',
                'operation_id' => 'storeid-1_PRO_2018-01-16-14-28-01-24778200_337',
                'body' => '{"id":"337","title":"Aviator Sunglasses","url":"http:\/\/127.0.0.1\/mcmagento-1937\/aviator-sunglasses.html","image_url":"http:\/\/127.0.0.1\/mcmagento-1937\/media\/catalog\/product\/cache\/1\/image\/265x\/9df78eab33525d08d6e5fb8d27136e95\/a\/c\/ace000a_1.jpg","published_at_foreign":"","description":"Gunmetal frame with crystal gradient polycarbonate lenses in grey. ","type":"Eyewear","vendor":"Eyewear","handle":"","variants":[{"id":"337","title":"Aviator Sunglasses","url":"http:\/\/127.0.0.1\/mcmagento-1937\/aviator-sunglasses.html","image_url":"http:\/\/127.0.0.1\/mcmagento-1937\/media\/catalog\/product\/cache\/1\/image\/265x\/9df78eab33525d08d6e5fb8d27136e95\/a\/c\/ace000a_1.jpg","published_at_foreign":"","sku":"ace000","price":295,"inventory_quantity":6,"backorders":"0","visibility":"Catalog, Search"}]}'
            ),
            array(
                'method' => 'POST',
                'path' => '/ecommerce/stores/ef3bf57fb9bd695a02b7f7c7fb0d2db5/products',
                'operation_id' => 'storeid-1_PRO_2018-01-16-14-28-01-24778200_906',
                'body' => '{"id":"906","title":"test Prod","url":"http:\/\/127.0.0.1\/mcmagento-1937\/test-prod.html","published_at_foreign":"","description":"Test","type":"Default Category","vendor":"Default Category","handle":"","variants":[{"id":"906","title":"test Prod","url":"http:\/\/127.0.0.1\/mcmagento-1937\/test-prod.html","published_at_foreign":"","sku":"testprod","price":10,"inventory_quantity":1000,"backorders":"0","visibility":"Catalog, Search"}]}'
            )
        );
    }

    /**
     * @return array
     */
    protected function getCustomerArray()
    {
        return array(
            array(
                'method' => 'PUT', 'path' => '/ecommerce/stores/ef3bf57fb9bd695a02b7f7c7fb0d2db5/customers/137',
                'operation_id' => 'storeid-1_CUS_2018-01-16-14-28-01-17117500_137',
                'body' => '{"id":"137","email_address":"santiago+testtest@ebizmarts.com","first_name":"Santiago","last_name":"Paragarino","opt_in_status":false,"orders_count":1,"total_spent":300,"address":{"address1":"address","city":"city","province":"Alabama","province_code":"AL","postal_code":"123456","country":"United States","country_code":"US"}}'
            )
        );
    }

}
