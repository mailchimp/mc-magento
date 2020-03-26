<?php

class Ebizmarts_MailChimp_Model_Api_CartsTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Ebizmarts_MailChimp_Model_Api_Carts
     */
    protected $_cartsApiMock;
    const DATE = '2017-05-18-14-45-54-38849500';
    const BATCH_ID = 'storeid-1_QUO_2017-05-18-14-45-54-38849500';
    const MAILCHIMP_STORE_ID = '3ade9d9e52e35e9b18d95bdd4d9e9a44';
    const BATCH_LIMIT_FROM_CONFIG = 100;
    const MAGENTO_STORE_ID = 1;
    const ALREADY_SENT_CART_ID = 2;
    const WEB_SITE_ID_FROM_MAGENTO_STORE_ID = 0;
    const CUSTOMER_EMAIL_BY_CART = 'test@ebizmarts.com';
    const CART_ID = 1;
    const COUNTER = 0;
    const STRING_IS_ACTIVE = 'is_active';
    const STRING_STORE_ID = 'store_id';

    public function setUp()
    {
        Mage::app('default');
        $this->_cartsApiMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Carts::class)
            ->disableOriginalConstructor();
    }

    public function tearDown()
    {
        $this->_cartsApiMock = null;
    }

    public function testCreateBatchJson()
    {
        $batchArray = array();

        $cartsApiMock = $this->_cartsApiMock->setMethods(
            array(
                'getMailchimpStoreId',
                'getMagentoStoreId',
                'createEcommerceQuoteCollection',
                'getHelper',
                'getDateHelper',
                '_getConvertedQuotes',
                '_getModifiedQuotes',
                '_getNewQuotes',
                'setBatchId',
                'setCounter',
            )
        )->getMock();

        $cartsCollectionResourceMock = $this
            ->getMockBuilder(Ebizmarts_MailChimp_Model_Resource_Ecommercesyncdata_Quote_Collection::class)
            ->disableOriginalConstructor()->setMethods(array('setMailchimpStoreId', 'setStoreId'))
            ->getMock();

        $helperDateMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Date::class)
            ->disableOriginalConstructor()->setMethods(array('getDateMicrotime'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(
                array('getCurrentStoreId', 'setCurrentStore', 'isAbandonedCartEnabled',
                    'getAbandonedCartFirstDate', 'getResendTurn')
            )->getMock();

        $cartsApiMock->expects($this->once())->method('getMailchimpStoreId')->willReturn(self::MAILCHIMP_STORE_ID);
        $cartsApiMock->expects($this->once())->method('getMagentoStoreId')->willReturn(self::MAGENTO_STORE_ID);
        $cartsApiMock->expects($this->once())->method('createEcommerceQuoteCollection')
            ->willReturn($cartsCollectionResourceMock);

        $cartsCollectionResourceMock->expects($this->once())->method('setMailchimpStoreId')
            ->with(self::MAILCHIMP_STORE_ID);
        $cartsCollectionResourceMock->expects($this->once())->method('setStoreId')->with(self::MAGENTO_STORE_ID);
        $cartsApiMock->expects($this->once())->method('getHelper')->willReturn($helperMock);
        $helperMock->expects($this->once())->method('getCurrentStoreId')->willReturn(self::MAGENTO_STORE_ID);
        $helperMock->expects($this->exactly(2))->method('setCurrentStore')
            ->withConsecutive(array(self::MAGENTO_STORE_ID), array(self::MAGENTO_STORE_ID));

        $helperMock->expects($this->once())->method('isAbandonedCartEnabled')->with(self::MAGENTO_STORE_ID)
            ->willReturn(true);
        $cartsApiMock->expects($this->once())->method('getDateHelper')->willReturn($helperDateMock);
        $helperMock->expects($this->once())->method('getAbandonedCartFirstDate')->with(self::MAGENTO_STORE_ID)
        ->willReturn(self::DATE);
        $cartsApiMock->expects($this->once())->method('setCounter')->with(0);
        $helperDateMock->expects($this->once())->method('getDateMicrotime')->willReturn(self::DATE);
        $cartsApiMock->expects($this->once())->method('setBatchId')->with(self::BATCH_ID);
        $helperMock->expects($this->once())->method('getResendTurn')->with(self::MAGENTO_STORE_ID)->willReturn(null);
        $cartsApiMock->expects($this->once())->method('_getConvertedQuotes')->willReturn($batchArray);
        $cartsApiMock->expects($this->once())->method('_getModifiedQuotes')->willReturn($batchArray);
        $cartsApiMock->expects($this->once())->method('_getNewQuotes')->willReturn($batchArray);

        $helperMock->expects($this->once())->method('getAbandonedCartFirstDate')->with(self::MAGENTO_STORE_ID)
            ->willReturn('00-00-00 00:00:00');

        $cartsApiMock->createBatchJson();
    }

    public function testCreateBatchJsonisAbandonedCartDisabled()
    {
        $cartsApiMock = $this->_cartsApiMock
            ->setMethods(
                array(
                    'getMailchimpStoreId', 'getMagentoStoreId', 'createEcommerceQuoteCollection',
                    'getHelper', 'getDateHelper', 'setBatchId',
                    '_getConvertedQuotes', '_getModifiedQuotes', '_getNewQuotes'
                )
            )->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('isAbandonedCartEnabled'))
            ->getMock();

        $quotesCollectionResource = $this
            ->getMockBuilder(Ebizmarts_MailChimp_Model_Resource_Ecommercesyncdata_Quote_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('setStoreId', 'setMailchimpStoreId'))
            ->getMock();

        $cartsApiMock->expects($this->once())->method('getMailchimpStoreId')->willReturn(self::MAILCHIMP_STORE_ID);
        $cartsApiMock->expects($this->once())->method('getMagentoStoreId')->willReturn(self::MAGENTO_STORE_ID);
        $cartsApiMock->expects($this->once())->method('createEcommerceQuoteCollection')
            ->willReturn($quotesCollectionResource);

        $quotesCollectionResource->expects($this->once())
            ->method('setStoreId')->willReturn(self::MAGENTO_STORE_ID);

        $quotesCollectionResource->expects($this->once())
            ->method('setMailchimpStoreId')->willReturn(self::MAILCHIMP_STORE_ID);

        $cartsApiMock->expects($this->once())->method('getHelper')->willReturn($helperMock);

        $helperMock
            ->expects($this->once())
            ->method('isAbandonedCartEnabled')
            ->with(self::MAGENTO_STORE_ID)
            ->willReturn(false);

        $cartsApiMock->createBatchJson();
    }

    public function testGetConvertedQuotes()
    {
        $arrayAddFieldToFilter = array('eq' => 0);
        $arrayAddFieldToFilterStoreId = array('eq' => self::MAGENTO_STORE_ID);
        $where = "m4m.mailchimp_sync_deleted = 0";
        $conditionSelect = "m4m.related_id = main_table.entity_id AND m4m.type = '"
            . Ebizmarts_MailChimp_Model_Config::IS_QUOTE
            . "' AND m4m.mailchimp_store_id = '" . self::MAILCHIMP_STORE_ID . "'";
        $cartsApiMock = $this->_cartsApiMock->setMethods(
            array(
                'getMailchimpStoreId',
                'getMagentoStoreId',
                'getQuoteCollection',
                'joinLeftEcommerceSyncData',
                'getEcommerceQuoteCollection',
                'getBatchLimitFromConfig',
                'getAllCartsByEmail',
                'getCounter',
                'getBatchId',
                'markSyncDataAsDeleted',
                'setCounter'
            )
        )->getMock();

        $quoteResourceCollectionMock = $this
            ->getMockBuilder(Ebizmarts_MailChimp_Model_Resource_Ecommercesyncdata_Quote_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('addWhere'))
            ->getMock();

        $cartsCollectionMock = $this
        ->getMockBuilder(Mage_Sales_Model_Resource_Quote_Collection::class)
        ->disableOriginalConstructor()
        ->setMethods(array('addFieldToFilter', 'getIterator'))
        ->getMock();

        $cartsByEmailCollectionMock = $this
            ->getMockBuilder(Mage_Sales_Model_Resource_Quote_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('clear', 'getIterator'))
            ->getMock();

        $cartModelMock = $this
            ->getMockBuilder(Mage_Sales_Model_Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getEntityId', 'getCustomerEmail'))
            ->getMock();

        $cartByEmailModelMock = $this
            ->getMockBuilder(Mage_Sales_Model_Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getIterator', 'getEntityId'))
            ->getMock();

        $cartsApiMock->expects($this->once())->method('getMailchimpStoreId')->willReturn(self::MAILCHIMP_STORE_ID);
        $cartsApiMock->expects($this->once())->method('getMagentoStoreId')->willReturn(self::MAGENTO_STORE_ID);
        $cartsApiMock->expects($this->once())->method('getBatchId')->willReturn(self::BATCH_ID);
        $cartsApiMock->expects($this->once())->method('getQuoteCollection')->willReturn($cartsCollectionMock);

        $cartsCollectionMock->expects($this->exactly(2))
            ->method('addFieldToFilter')
            ->withConsecutive(
                array('store_id', $arrayAddFieldToFilterStoreId),
                array('is_active', $arrayAddFieldToFilter)
            );

        $cartsApiMock->expects($this->once())->method('joinLeftEcommerceSyncData')->with($cartsCollectionMock);

        $cartsApiMock->expects($this->once())
            ->method('getEcommerceQuoteCollection')->willReturn($quoteResourceCollectionMock);

        $cartsApiMock->expects($this->once())
            ->method('getBatchLimitFromConfig')->willReturn(self::BATCH_LIMIT_FROM_CONFIG);

        $quoteResourceCollectionMock->expects($this->once())
            ->method('addWhere')->with($cartsCollectionMock, $where, self::BATCH_LIMIT_FROM_CONFIG);

        $cartsCollectionMock->expects($this->once())
            ->method('getIterator')->willReturn(new ArrayIterator(array($cartModelMock)));

        $cartModelMock->expects($this->once())
            ->method('getEntityId')->willReturn(self::CART_ID);

        $cartsApiMock->expects($this->once())->method('getAllCartsByEmail')
            ->with(self::CUSTOMER_EMAIL_BY_CART)->willReturn($cartsByEmailCollectionMock);

        $cartModelMock->expects($this->once())->method('getCustomerEmail')->willReturn(self::CUSTOMER_EMAIL_BY_CART);

        $cartsApiMock->expects($this->once())->method('getAllCartsByEmail')
            ->with(self::CUSTOMER_EMAIL_BY_CART)->willReturn($cartsByEmailCollectionMock);

        $cartsByEmailCollectionMock->expects($this->once())
            ->method('getIterator')->willReturn(new ArrayIterator(array($cartByEmailModelMock)));

        $cartByEmailModelMock->expects($this->once())->method('getEntityId')->willReturn(self::ALREADY_SENT_CART_ID);

        $cartsApiMock->expects($this->exactly(4))->method('getCounter')
            ->willReturnOnConsecutiveCalls(self::COUNTER, self::COUNTER, self::COUNTER, self::COUNTER);

        $cartsApiMock->expects($this->exactly(2))->method('markSyncDataAsDeleted')
            ->withConsecutive(
                array(self::ALREADY_SENT_CART_ID),
                array(self::CART_ID)
            );

        $cartsApiMock->expects($this->exactly(2))->method('setCounter')
            ->willReturnOnConsecutiveCalls(self::COUNTER + 1, self::COUNTER + 1);

        $cartsByEmailCollectionMock->expects($this->once())->method('clear');

        $cartsApiMock->_getConvertedQuotes();
    }

    public function testGetModifiedQuotes()
    {
        $customerEmailAddress = '';
        $cartJson = '{"id":"692","customer":{"id":"GUEST-2018-11-30-20-00-07-96938700",'
            . '"email_address":"test@ebizmarts.com","opt_in_status":false,"first_name":"Lucia",'
            . '"last_name":"en el checkout","address":{"address1":"asdf","city":"asd",'
            . '"postal_code":"212312","country":"Tajikistan","country_code":"TJ"}},'
            . '"campaign_id":"482d28ee12","checkout_url":"http:\/\/f3364930.ngrok.io\/mailchimp\/cart'
            . '\/loadquote\?id=692&token=ec4f79b2e4677d2edc5bf78c934e5794","currency_code":"USD",'
            . '"order_total":"1700.0000","tax_total":0,"lines":[{"id":"1","product_id":"425",'
            . '"product_variant_id":"310","quantity":5,"price":"1700.0000"}]}';
        $customerId = 1;
        $arrayAddFieldToFilter = array('eq' => 1);
        $arrayAddFieldToFilterStoreId = array('eq' => self::MAGENTO_STORE_ID);
        $where = "m4m.mailchimp_sync_deleted = 0
        AND m4m.mailchimp_sync_delta < updated_at";

        $allCarts = array(
            array(
                'method' => 'DELETE',
                'path' => '/ecommerce/stores/' . self::MAILCHIMP_STORE_ID . '/carts/' . self::ALREADY_SENT_CART_ID,
                'operation_id' => self::BATCH_ID . '_' . self::ALREADY_SENT_CART_ID,
                'body' => ''
            )
        );
        $token = 'ec4f79b2e4677d2edc5bf78c934e5794';

        $cartsApiMock = $this->_cartsApiMock->setMethods(
            array(
                'getMailchimpStoreId',
                'getMagentoStoreId',
                'getBatchId',
                'getQuoteCollection',
                'joinLeftEcommerceSyncData',
                'getEcommerceQuoteCollection',
                'getAllCartsByEmail',
                'setCounter',
                'getCounter',
                'markSyncDataAsDeleted',
                'getHelper',

                'setToken',
                'getToken',
                'getBatchLimitFromConfig',
                'getCustomerModel',
                'getWebSiteIdFromMagentoStoreId',
                'makeCart',
                'addProductNotSentData',
                'addSyncDataToken',
            )
        )->getMock();

        $cartModelMock = $this->getMockBuilder(Mage_Sales_Model_Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getEntityId', 'getCustomerEmail', 'getCustomerId'))
            ->getMock();

        $cartByEmailModelMock = $this->getMockBuilder(Mage_Sales_Model_Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getEntityId'))
            ->getMock();

        $cartsCollectionMock = $this
            ->getMockBuilder(Mage_Sales_Model_Resource_Quote_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('addFieldToFilter', 'getIterator'))
            ->getMock();

        $customerModelMock = $this
            ->getMockBuilder(Mage_Customer_Model_Customer::class)
            ->disableOriginalConstructor()
            ->setMethods(array('setWebsiteId', 'loadByEmail', 'getEmail'))
            ->getMock();

        $cartsByEmailCollectionMock = $this
            ->getMockBuilder(Mage_Sales_Model_Resource_Quote_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('clear', 'getIterator'))
            ->getMock();

        $cartsResourceCollectionModelMock = $this->
        getMockBuilder(Ebizmarts_MailChimp_Model_Resource_Ecommercesyncdata_Quote_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('addWhere'))
            ->getMock();

        $mailchimpHelperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('modifyCounterSentPerBatch', 'getEntityId'))
            ->getMock();
        //----------------------------
        $cartsApiMock->expects($this->once())->method('getMailchimpStoreId')->willReturn(self::MAILCHIMP_STORE_ID);
        $cartsApiMock->expects($this->once())->method('getMagentoStoreId')->willReturn(self::MAGENTO_STORE_ID);
        $cartsApiMock->expects($this->once())->method('getHelper')->willReturn($mailchimpHelperMock);
        $cartsApiMock->expects($this->once())->method('getBatchId')->willReturn(self::BATCH_ID);
        $cartsApiMock->expects($this->once())->method('getQuoteCollection')->willReturn($cartsCollectionMock);

        $cartsCollectionMock->expects($this->exactly(2))->method('addFieldToFilter')
            ->withConsecutive(
                array(self::STRING_IS_ACTIVE, $arrayAddFieldToFilter),
                array(self::STRING_STORE_ID, $arrayAddFieldToFilterStoreId)
            );

        $cartsApiMock->expects($this->once())->method('joinLeftEcommerceSyncData')->with($cartsCollectionMock);
        $cartsApiMock->expects($this->once())->method('getEcommerceQuoteCollection')
            ->willReturn($cartsResourceCollectionModelMock);

        $cartsApiMock->expects($this->once())->method('getBatchLimitFromConfig')
            ->willReturn(self::BATCH_LIMIT_FROM_CONFIG);

        $cartsResourceCollectionModelMock->expects($this->once())->method('addWhere')
            ->with(
                $cartsCollectionMock,
                "m4m.mailchimp_sync_deleted = 0 AND m4m.mailchimp_sync_delta < updated_at",
                self::BATCH_LIMIT_FROM_CONFIG
            );

        $cartsCollectionMock->expects($this->once())->method('getIterator')
            ->willReturn(new ArrayIterator(array($cartModelMock)));

        $cartModelMock->expects($this->once())->method('getEntityId')->willReturn(self::CART_ID);
        $cartsApiMock->expects($this->once())->method('getCustomerModel')->willReturn($customerModelMock);

        $cartsApiMock->expects($this->once())->method('getWebSiteIdFromMagentoStoreId')
            ->with(self::MAGENTO_STORE_ID)->willReturn(self::WEB_SITE_ID_FROM_MAGENTO_STORE_ID);

        $customerModelMock->expects($this->once())->method('setWebsiteId')
            ->with(self::WEB_SITE_ID_FROM_MAGENTO_STORE_ID);

        $customerModelMock->expects($this->once())->method('loadByEmail')->with(self::CUSTOMER_EMAIL_BY_CART);

        $cartModelMock->expects($this->once())->method('getCustomerEmail')
            ->willReturnOnConsecutiveCalls(
                self::CUSTOMER_EMAIL_BY_CART, self::CUSTOMER_EMAIL_BY_CART, self::CUSTOMER_EMAIL_BY_CART
            );

        $customerModelMock->expects($this->once())->method('getEmail')->willReturn($customerEmailAddress);
        $cartsApiMock->expects($this->once())->method('getAllCartsByEmail')->with(self::CUSTOMER_EMAIL_BY_CART)
            ->willReturn($cartsByEmailCollectionMock);

        $cartsByEmailCollectionMock->expects($this->once())->method('getIterator')
            ->willReturn(new ArrayIterator(array($cartByEmailModelMock)));

        $cartByEmailModelMock->expects($this->once())->method('getEntityId')->willReturn(self::ALREADY_SENT_CART_ID);

        $cartsApiMock->expects($this->exactly(3))->method('getCounter')
            ->willReturnOnConsecutiveCalls(self::COUNTER, self::COUNTER, self::COUNTER, self::COUNTER);

        $cartsApiMock->expects($this->once())->method('markSyncDataAsDeleted')->with(self::CART_ID);
        $cartsApiMock->expects($this->exactly(2))->method('setCounter')
            ->withConsecutive(array(self::COUNTER + 1), array(self::COUNTER + 1));

        $cartsByEmailCollectionMock->expects($this->once())->method('clear');
        $cartModelMock->expects($this->once())->method('getCustomerId')->willReturn($customerId);

        $cartsApiMock->expects($this->once())->method('getToken')->willReturn($token);
        $cartsApiMock->expects($this->once())->method('addSyncDataToken')->with(self::CART_ID, $token);
        $cartsApiMock->expects($this->once())->method('addProductNotSentData')->with($cartModelMock, $allCarts)
            ->willReturn($allCarts);
        $cartsApiMock->expects($this->once())->method('makeCart')->with($cartModelMock, true)->willReturn($cartJson);
        $cartsApiMock->expects($this->once())->method('getHelper')->willReturn($mailchimpHelperMock);
        $mailchimpHelperMock->expects($this->once())->method('modifyCounterSentPerBatch')
            ->with(Ebizmarts_MailChimp_Helper_Data::QUO_MOD);

        $cartsApiMock->expects($this->once())->method('setToken')->with(null);

        $cartsApiMock->_getModifiedQuotes();
    }

    public function testGetModifiedQuotesGuestCustomer()
    {
        $mcTableName = 'mailchimp_ecommerce_sync_data';
        $customerId = '';
        $customerEmailAddress = 'test@ebizmarts.com';
        $stringStoreId = 'store_id';
        $arrayAddFieldToFilter = array('eq' => 1);
        $arrayAddFieldToFilterStoreId = array('eq' => self::MAGENTO_STORE_ID);
        $where = "m4m.mailchimp_sync_deleted = 0
        AND m4m.mailchimp_sync_delta < updated_at";
        $arrayTableName = array('m4m' => $mcTableName);
        $conditionSelect = "m4m.related_id = main_table.entity_id AND m4m.type = '"
            . Ebizmarts_MailChimp_Model_Config::IS_QUOTE
            . "' AND m4m.mailchimp_store_id = '" . self::MAILCHIMP_STORE_ID . "'";
        $mailchimpSelect = array('m4m.*');

        $cartsApiMock = $this->_cartsApiMock->setMethods(
            array(
                'getMailchimpStoreId',
                'getMagentoStoreId',
                'getBatchId',
                'getQuoteCollection',
                'joinLeftEcommerceSyncData',
                'getEcommerceQuoteCollection',
                'setCounter',
                'getCounter',
                'getHelper',

                'setToken',
                'getToken',
                'getBatchLimitFromConfig',
                'getCustomerModel',
                'getWebSiteIdFromMagentoStoreId',
                'makeCart',
                'addProductNotSentData',
                'addSyncDataToken',
                'addSyncData',
            )
        )->getMock();

        $cartModelMock = $this->getMockBuilder(Mage_Sales_Model_Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getEntityId', 'getCustomerEmail', 'getCustomerId'))
            ->getMock();

        $cartsCollectionMock = $this
            ->getMockBuilder(Mage_Sales_Model_Resource_Quote_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('addFieldToFilter', 'getIterator'))
            ->getMock();

        $customerModelMock = $this
            ->getMockBuilder(Mage_Customer_Model_Customer::class)
            ->disableOriginalConstructor()
            ->setMethods(array('setWebsiteId', 'loadByEmail', 'getEmail'))
            ->getMock();

        $cartsResourceCollectionModelMock = $this->
        getMockBuilder(Ebizmarts_MailChimp_Model_Resource_Ecommercesyncdata_Quote_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('addWhere'))
            ->getMock();

        $mailchimpHelperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('modifyCounterSentPerBatch', 'getEntityId'))
            ->getMock();

        $cartsApiMock->expects($this->once())->method('getMailchimpStoreId')->willReturn(self::MAILCHIMP_STORE_ID);
        $cartsApiMock->expects($this->once())->method('getMagentoStoreId')->willReturn(self::MAGENTO_STORE_ID);
        $cartsApiMock->expects($this->once())->method('getHelper')->willReturn($mailchimpHelperMock);
        $cartsApiMock->expects($this->once())->method('getBatchId')->willReturn(self::BATCH_ID);
        $cartsApiMock->expects($this->once())->method('getQuoteCollection')->willReturn($cartsCollectionMock);

        $cartsCollectionMock->expects($this->exactly(2))->method('addFieldToFilter')
            ->withConsecutive(
                array(self::STRING_IS_ACTIVE, $arrayAddFieldToFilter),
                array(self::STRING_STORE_ID, $arrayAddFieldToFilterStoreId)
            );

        $cartsApiMock->expects($this->once())->method('joinLeftEcommerceSyncData')->with($cartsCollectionMock);
        $cartsApiMock->expects($this->once())->method('getEcommerceQuoteCollection')
            ->willReturn($cartsResourceCollectionModelMock);

        $cartsApiMock->expects($this->once())->method('getBatchLimitFromConfig')
            ->willReturn(self::BATCH_LIMIT_FROM_CONFIG);

        $cartsResourceCollectionModelMock->expects($this->once())->method('addWhere')
            ->with(
                $cartsCollectionMock,
                "m4m.mailchimp_sync_deleted = 0 AND m4m.mailchimp_sync_delta < updated_at",
                self::BATCH_LIMIT_FROM_CONFIG
            );

        $cartsCollectionMock->expects($this->once())->method('getIterator')
            ->willReturn(new ArrayIterator(array($cartModelMock)));

        $cartModelMock->expects($this->once())->method('getEntityId')->willReturn(self::CART_ID);
        $cartsApiMock->expects($this->once())->method('getCustomerModel')->willReturn($customerModelMock);

        $cartsApiMock->expects($this->once())->method('getWebSiteIdFromMagentoStoreId')
            ->with(self::MAGENTO_STORE_ID)->willReturn(self::WEB_SITE_ID_FROM_MAGENTO_STORE_ID);

        $customerModelMock->expects($this->once())->method('setWebsiteId')
            ->with(self::WEB_SITE_ID_FROM_MAGENTO_STORE_ID);

        $customerModelMock->expects($this->once())->method('loadByEmail')->with(self::CUSTOMER_EMAIL_BY_CART);

        $cartModelMock->expects($this->once())->method('getCustomerEmail')
            ->willReturnOnConsecutiveCalls(
                self::CUSTOMER_EMAIL_BY_CART, self::CUSTOMER_EMAIL_BY_CART, self::CUSTOMER_EMAIL_BY_CART
            );

        $customerModelMock->expects($this->once())->method('getEmail')
            ->willReturnOnConsecutiveCalls($customerEmailAddress, $customerEmailAddress);

        $cartModelMock->expects($this->once())->method('getCustomerId')->willReturn($customerId);

        $cartsApiMock->_getModifiedQuotes();
    }

    public function testGetModifiedQuotesEmptyJson()
    {
        $customerEmailAddress = '';
        $cartJson = '';
        $customerId = 1;
        $arrayAddFieldToFilter = array('eq' => 1);
        $arrayAddFieldToFilterStoreId = array('eq' => self::MAGENTO_STORE_ID);
        $where = "m4m.mailchimp_sync_deleted = 0
        AND m4m.mailchimp_sync_delta < updated_at";
        $conditionSelect = "m4m.related_id = main_table.entity_id AND m4m.type = '"
            . Ebizmarts_MailChimp_Model_Config::IS_QUOTE
            . "' AND m4m.mailchimp_store_id = '" . self::MAILCHIMP_STORE_ID . "'";
        $allCarts = array(
            array(
                'method' => 'DELETE',
                'path' => '/ecommerce/stores/' . self::MAILCHIMP_STORE_ID . '/carts/' . self::ALREADY_SENT_CART_ID,
                'operation_id' => self::BATCH_ID . '_' . self::ALREADY_SENT_CART_ID,
                'body' => ''
            )
        );

        $cartsApiMock = $this->_cartsApiMock->setMethods(
            array(
                'getMailchimpStoreId',
                'getMagentoStoreId',
                'getBatchId',
                'getQuoteCollection',
                'joinLeftEcommerceSyncData',
                'getEcommerceQuoteCollection',
                'getAllCartsByEmail',
                'setCounter',
                'getCounter',
                'markSyncDataAsDeleted',
                'getHelper',

                'setToken',
                'getToken',
                'getBatchLimitFromConfig',
                'getCustomerModel',
                'getWebSiteIdFromMagentoStoreId',
                'makeCart',
                'addProductNotSentData',
                'addSyncDataToken',
                'addSyncDataError',
            )
        )->getMock();

        $cartModelMock = $this->getMockBuilder(Mage_Sales_Model_Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getEntityId', 'getCustomerEmail', 'getCustomerId'))
            ->getMock();

        $cartByEmailModelMock = $this->getMockBuilder(Mage_Sales_Model_Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getEntityId'))
            ->getMock();

        $cartsCollectionMock = $this
            ->getMockBuilder(Mage_Sales_Model_Resource_Quote_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('addFieldToFilter', 'getIterator'))
            ->getMock();

        $customerModelMock = $this
            ->getMockBuilder(Mage_Customer_Model_Customer::class)
            ->disableOriginalConstructor()
            ->setMethods(array('setWebsiteId', 'loadByEmail', 'getEmail'))
            ->getMock();

        $cartsByEmailCollectionMock = $this
            ->getMockBuilder(Mage_Sales_Model_Resource_Quote_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('clear', 'getIterator'))
            ->getMock();

        $cartsResourceCollectionModelMock = $this->
        getMockBuilder(Ebizmarts_MailChimp_Model_Resource_Ecommercesyncdata_Quote_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('addWhere'))
            ->getMock();

        $mailchimpHelperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('modifyCounterSentPerBatch', 'getEntityId'))
            ->getMock();

        $cartsApiMock->expects($this->once())->method('getMailchimpStoreId')->willReturn(self::MAILCHIMP_STORE_ID);
        $cartsApiMock->expects($this->once())->method('getMagentoStoreId')->willReturn(self::MAGENTO_STORE_ID);
        $cartsApiMock->expects($this->once())->method('getHelper')->willReturn($mailchimpHelperMock);
        $cartsApiMock->expects($this->once())->method('getBatchId')->willReturn(self::BATCH_ID);
        $cartsApiMock->expects($this->once())->method('getQuoteCollection')->willReturn($cartsCollectionMock);

        $cartsCollectionMock->expects($this->exactly(2))->method('addFieldToFilter')
            ->withConsecutive(
                array(self::STRING_IS_ACTIVE, $arrayAddFieldToFilter),
                array(self::STRING_STORE_ID, $arrayAddFieldToFilterStoreId)
            );

        $cartsApiMock->expects($this->once())->method('joinLeftEcommerceSyncData')->with($cartsCollectionMock);
        $cartsApiMock->expects($this->once())->method('getEcommerceQuoteCollection')
            ->willReturn($cartsResourceCollectionModelMock);

        $cartsApiMock->expects($this->once())->method('getBatchLimitFromConfig')
            ->willReturn(self::BATCH_LIMIT_FROM_CONFIG);

        $cartsResourceCollectionModelMock->expects($this->once())->method('addWhere')
            ->with(
                $cartsCollectionMock,
                "m4m.mailchimp_sync_deleted = 0 AND m4m.mailchimp_sync_delta < updated_at",
                self::BATCH_LIMIT_FROM_CONFIG
            );

        $cartsCollectionMock->expects($this->once())->method('getIterator')
            ->willReturn(new ArrayIterator(array($cartModelMock)));

        $cartModelMock->expects($this->once())->method('getEntityId')->willReturn(self::CART_ID);
        $cartsApiMock->expects($this->once())->method('getCustomerModel')->willReturn($customerModelMock);

        $cartsApiMock->expects($this->once())->method('getWebSiteIdFromMagentoStoreId')
            ->with(self::MAGENTO_STORE_ID)->willReturn(self::WEB_SITE_ID_FROM_MAGENTO_STORE_ID);

        $customerModelMock->expects($this->once())->method('setWebsiteId')
            ->with(self::WEB_SITE_ID_FROM_MAGENTO_STORE_ID);

        $customerModelMock->expects($this->once())->method('loadByEmail')->with(self::CUSTOMER_EMAIL_BY_CART);

        $cartModelMock->expects($this->once())->method('getCustomerEmail')
            ->willReturnOnConsecutiveCalls(
                self::CUSTOMER_EMAIL_BY_CART, self::CUSTOMER_EMAIL_BY_CART, self::CUSTOMER_EMAIL_BY_CART
            );

        $customerModelMock->expects($this->once())->method('getEmail')->willReturn($customerEmailAddress);
        $cartsApiMock->expects($this->once())->method('getAllCartsByEmail')->with(self::CUSTOMER_EMAIL_BY_CART)
            ->willReturn($cartsByEmailCollectionMock);

        $cartsByEmailCollectionMock->expects($this->once())->method('getIterator')
            ->willReturn(new ArrayIterator(array($cartByEmailModelMock)));

        $cartByEmailModelMock->expects($this->once())->method('getEntityId')->willReturn(self::ALREADY_SENT_CART_ID);

        $cartsApiMock->expects($this->once())->method('getCounter')
            //->willReturnOnConsecutiveCalls(self::COUNTER, self::COUNTER, self::COUNTER, self::COUNTER);
            ->willReturn(self::COUNTER);

        $cartsApiMock->expects($this->once())->method('markSyncDataAsDeleted')->with(self::CART_ID);
        $cartsApiMock->expects($this->once())->method('setCounter')
            ->withConsecutive(array(self::COUNTER + 1), array(self::COUNTER + 1));

        $cartsByEmailCollectionMock->expects($this->once())->method('clear');
        $cartModelMock->expects($this->once())->method('getCustomerId')->willReturn($customerId);

        $cartsApiMock->expects($this->once())->method('addProductNotSentData')->with($cartModelMock, $allCarts)
            ->willReturn($allCarts);
        $cartsApiMock->expects($this->once())->method('makeCart')->with($cartModelMock, true)->willReturn($cartJson);
        $cartsApiMock->expects($this->once())->method('addSyncDataError')
            ->with(self::CART_ID, "There is not supported products in this cart.", null);

        $cartsApiMock->expects($this->once())->method('setToken')->with(null);

        $cartsApiMock->_getModifiedQuotes();
    }

    public function testGetNewQuotesNewQuote()
    {
        $existFirstDate = '2018-11-30';
        $customerId = 1;
        $token = 'ec4f79b2e4677d2edc5bf78c934e5794';
        $customerEmailAddress = '';
        $allCarts = array(
            array(
                'method' => 'DELETE',
                'path' => '/ecommerce/stores/' . self::MAILCHIMP_STORE_ID . '/carts/' . self::ALREADY_SENT_CART_ID,
                'operation_id' => self::BATCH_ID . '_' . self::ALREADY_SENT_CART_ID,
                'body' => ''
            )
        );
        $cartJson = '{"id":"692","customer":{"id":"GUEST-2018-11-30-20-00-07-96938700",'
            . '"email_address":"test@ebizmarts.com","opt_in_status":false,"first_name":"Lucia",'
            . '"last_name":"en el checkout","address":{"address1":"asdf","city":"asd","postal_code":"212312",'
            . '"country":"Tajikistan","country_code":"TJ"}},"campaign_id":"482d28ee12",'
            . '"checkout_url":"http:\/\/f3364930.ngrok.io\/mailchimp\/cart\/loadquote\?'
            . 'id=692&token=ec4f79b2e4677d2edc5bf78c934e5794","currency_code":"USD","order_total":"1700.0000",'
            . '"tax_total":0,"lines":[{"id":"1","product_id":"425","product_variant_id":"310","quantity":5,'
            . '"price":"1700.0000"}]}';
        $stringCustomerEmail = 'customer_email';
        $stringItemsCount = 'items_count';
        $stringUpdatedAt = 'updated_at';
        $arrayAddFieldToFilterUpdatedAt = array('gt' => $existFirstDate);
        $arrayAddFieldToFilterItemsCount = array('gt' => 0);
        $arrayAddFieldToFilterCustomerEmail = array('notnull' => true);
        $arrayAddFieldToFilter = array('eq' => 1);
        $arrayAddFieldToFilterStoreId = array('eq' => self::MAGENTO_STORE_ID);
        $where = "m4m.mailchimp_sync_delta IS NULL";
        $allVisbleItems = array('item');
        $sizeOrderCollection = 0;
        $addFieldToFilterOrderCollection = array('eq' => self::CUSTOMER_EMAIL_BY_CART);
        $stringCustomerEmailMainTable = 'main_table.customer_email';
        $stringUpdated = 'main_table.updated_at';
        $updatedFromDate = array('from' => self::DATE);

        $cartsApiMock = $this->_cartsApiMock->setMethods(
            array(
                'getMailchimpStoreId',
                'getMagentoStoreId',
                'getHelper',
                'getDateHelper',
                'getBatchId',
                'getQuoteCollection',
                'getFirstDate',
                'joinLeftEcommerceSyncData',
                'getBatchLimitFromConfig',
                'getEcommerceQuoteCollection',
                'getOrderCollection',
                'getCustomerModel',
                'getWebSiteIdFromMagentoStoreId',
                'getAllCartsByEmail',
                'getCounter',
                'markSyncDataAsDeleted',
                'addProductNotSentData',
                'makeCart',
                'addSyncDataToken',
                'getToken'
            )
        )->getMock();

        $cartsCollectionResourceMock = $this
            ->getMockBuilder(Ebizmarts_MailChimp_Model_Resource_Ecommercesyncdata_Quote_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('addResendFilter', 'modifyCounterSentPerBatch', 'addWhere'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('addResendFilter', 'modifyCounterSentPerBatch'))
            ->getMock();

        $dateHelperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Date::class)
            ->disableOriginalConstructor()
            ->getMock();

        $newCartsCollectionMock = $this->getMockBuilder(Mage_Sales_Model_Resource_Quote_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('addFieldToFilter', 'getSelect', 'getIterator'))
            ->getMock();

        $customerModelMock = $this->getMockBuilder(Mage_Customer_Model_Customer::class)
            ->disableOriginalConstructor()
            ->setMethods(
                array('getEntityId', 'getCustomerEmail', 'getAllVisibleItems',
                    'getEmail', 'setWebsiteId', 'loadByEmail')
            )->getMock();

        $cartByEmailCollectionMock = $this->getMockBuilder(Mage_Sales_Model_Resource_Quote_Collection::class)
            ->disableOriginalConstructor()->setMethods(array('clear', 'getIterator'))
            ->getMock();

        $cartModelMock = $this->getMockBuilder(Mage_Sales_Model_Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(
                array('getEntityId', 'getCustomerEmail', 'getCustomerId', 'getUpdatedAt', 'getAllVisibleItems')
            )->getMock();

        $cartByEmailModelMock = $this->getMockBuilder(Mage_Sales_Model_Quote::class)
            ->disableOriginalConstructor()->setMethods(array('getEntityId'))
            ->getMock();

        $ordersCollectionMock = $this->getMockBuilder(Mage_Sales_Model_Resource_Order_Collection::class)
            ->disableOriginalConstructor()->setMethods(array('getSize', 'addFieldToFilter'))
            ->getMock();

        $cartsApiMock->expects($this->once())->method('getMailchimpStoreId')->willReturn(self::MAILCHIMP_STORE_ID);
        $cartsApiMock->expects($this->once())->method('getMagentoStoreId')->willReturn(self::MAGENTO_STORE_ID);
        $cartsApiMock->expects($this->once())->method('getHelper')->willReturn($helperMock);
        $cartsApiMock->expects($this->once())->method('getDateHelper')->willReturn($dateHelperMock);
        $cartsApiMock->expects($this->once())->method('getBatchId')->willReturn(self::BATCH_ID);
        $cartsApiMock->expects($this->once())->method('getQuoteCollection')->willReturn($newCartsCollectionMock);

        $newCartsCollectionMock->expects($this->exactly(5))->method('addFieldToFilter')
            ->withConsecutive(
                array(self::STRING_IS_ACTIVE, $arrayAddFieldToFilter),
                array($stringCustomerEmail, $arrayAddFieldToFilterCustomerEmail),
                array($stringItemsCount, $arrayAddFieldToFilterItemsCount),
                array(self::STRING_STORE_ID, $arrayAddFieldToFilterStoreId),
                array($stringUpdatedAt, $arrayAddFieldToFilterUpdatedAt)
            );

        $helperMock->expects($this->once())->method('addResendFilter')
            ->with($newCartsCollectionMock, self::MAGENTO_STORE_ID, Ebizmarts_MailChimp_Model_Config::IS_QUOTE);

        $cartsApiMock->expects($this->exactly(2))->method('getFirstDate')
            ->willReturnOnConsecutiveCalls($existFirstDate, $existFirstDate);

        $cartsApiMock->expects($this->once())->method('joinLeftEcommerceSyncData')->with($newCartsCollectionMock);

        $cartsApiMock->expects($this->once())->method('getEcommerceQuoteCollection')
            ->willReturn($cartsCollectionResourceMock);

        $cartsApiMock->expects($this->once())->method('getBatchLimitFromConfig')
            ->willReturn(self::BATCH_LIMIT_FROM_CONFIG);

        $cartsCollectionResourceMock->expects($this->once())->method('addWhere')
            ->with($newCartsCollectionMock, $where, self::BATCH_LIMIT_FROM_CONFIG);

        $newCartsCollectionMock->expects($this->once())->method('getIterator')
            ->willReturn(new ArrayIterator(array($cartModelMock)));

        $cartModelMock->expects($this->once())->method('getEntityId')->willReturn(self::CART_ID);

        $cartsApiMock->expects($this->once())->method('getOrderCollection')->willReturn($ordersCollectionMock);

        $cartModelMock->expects($this->once(4))->method('getCustomerEmail')
            ->willReturn(self::CUSTOMER_EMAIL_BY_CART);
            /*->willReturnOnConsecutiveCalls(
                self::CUSTOMER_EMAIL_BY_CART,
                self::CUSTOMER_EMAIL_BY_CART,
                self::CUSTOMER_EMAIL_BY_CART,
                self::CUSTOMER_EMAIL_BY_CART
            );*/

        $cartsApiMock->expects($this->once())->method('getCustomerModel')->willReturn($customerModelMock);
        $ordersCollectionMock->expects($this->exactly(2))->method('addFieldToFilter')
            ->withConsecutive(
                array($stringCustomerEmailMainTable, $addFieldToFilterOrderCollection),
                array($stringUpdated, $updatedFromDate)//$addFieldToFilterUpdated $arrayAddFieldToFilterUpdatedAt
            );

        $cartModelMock->expects($this->once())->method('getUpdatedAt')->willReturn(self::DATE);
        $cartModelMock->expects($this->once())->method('getAllVisibleItems')->willReturn($allVisbleItems);
        $ordersCollectionMock->expects($this->once())->method('getSize')->willReturn($sizeOrderCollection);

        $cartsApiMock->expects($this->once())->method('getWebSiteIdFromMagentoStoreId')
            ->with(self::MAGENTO_STORE_ID)->willReturn(self::WEB_SITE_ID_FROM_MAGENTO_STORE_ID);

        $customerModelMock->expects($this->once())->method('setWebsiteId')
            ->with(self::WEB_SITE_ID_FROM_MAGENTO_STORE_ID);

        $customerModelMock->expects($this->once())->method('loadByEmail')->with(self::CUSTOMER_EMAIL_BY_CART);
        $customerModelMock->expects($this->once())->method('getEmail')->willReturn($customerEmailAddress);

        $cartsApiMock->expects($this->once())->method('getAllCartsByEmail')->with(self::CUSTOMER_EMAIL_BY_CART)
            ->willReturn($cartByEmailCollectionMock);

        $cartByEmailCollectionMock->expects($this->once())->method('getIterator')
            ->willReturn(new ArrayIterator(array($cartByEmailModelMock)));

        $cartsApiMock->expects($this->exactly(3))->method('getCounter')
            ->willReturnOnConsecutiveCalls(
                self::COUNTER,
                self::COUNTER,
                self::COUNTER
            );

        $cartByEmailModelMock->expects($this->once())->method('getEntityId')->willReturn(self::ALREADY_SENT_CART_ID);
        $cartsApiMock->expects($this->once())->method('markSyncDataAsDeleted')->with(self::ALREADY_SENT_CART_ID);
        /*$cartsApiMock->expects($this->exactly(2))->method('setCounter')
            ->withConsecutive(
                array(self::COUNTER + 1),
                array(self::COUNTER + 1)
            );*/
        $cartByEmailCollectionMock->expects($this->once())->method('clear');
        $cartModelMock->expects($this->once())->method('getCustomerId')->willReturn($customerId);
        $cartsApiMock->expects($this->once())->method('addProductNotSentData')->with($cartModelMock, $allCarts)
            ->willReturn($allCarts);

        $cartsApiMock->expects($this->once())->method('makeCart')->with($cartModelMock)->willReturn($cartJson);

        $helperMock->expects($this->once())->method('modifyCounterSentPerBatch')
            ->with(Ebizmarts_MailChimp_Helper_Data::QUO_NEW);

        $cartsApiMock->expects($this->once())->method('getToken')->willReturn($token);
        $cartsApiMock->expects($this->once())->method('addSyncDataToken')->with(self::CART_ID, $token);

        $cartsApiMock->_getNewQuotes();
    }

    public function testGetNewQuotesIsOrder()
    {
        $existFirstDate = '2018-11-30';
        $stringCustomerEmail = 'customer_email';
        $stringItemsCount = 'items_count';
        $stringUpdatedAt = 'updated_at';
        $arrayAddFieldToFilterUpdatedAt = array('gt' => $existFirstDate);
        $arrayAddFieldToFilterItemsCount = array('gt' => 0);
        $arrayAddFieldToFilterCustomerEmail = array('notnull' => true);
        $arrayAddFieldToFilter = array('eq' => 1);
        $arrayAddFieldToFilterStoreId = array('eq' => self::MAGENTO_STORE_ID);
        $where = "m4m.mailchimp_sync_delta IS NULL";
        $allVisbleItems = array('item');
        $sizeOrderCollection = 1;
        $addFieldToFilterOrderCollection = array('eq' => '');
        $stringCustomerEmailMainTable = 'main_table.customer_email';
        $stringUpdated = 'main_table.updated_at';
        $updatedFromDate = array('from' => self::DATE);

        $cartsApiMock = $this->_cartsApiMock->setMethods(
            array(
                'getMailchimpStoreId',
                'getMagentoStoreId',
                'getHelper',
                'getDateHelper',
                'getBatchId',
                'getQuoteCollection',
                'getFirstDate',
                'joinLeftEcommerceSyncData',
                'getBatchLimitFromConfig',
                'getEcommerceQuoteCollection',
                'getOrderCollection',
                'getWebSiteIdFromMagentoStoreId',
                'addProductNotSentData',
                'makeCart',
                'addSyncDataToken',
                'getToken',
                'addSyncData'
            )
        )->getMock();

        $cartsCollectionResourceMock = $this
            ->getMockBuilder(Ebizmarts_MailChimp_Model_Resource_Ecommercesyncdata_Quote_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('addResendFilter', 'modifyCounterSentPerBatch', 'addWhere'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('addResendFilter', 'modifyCounterSentPerBatch'))
            ->getMock();

        $dateHelperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Date::class)
            ->disableOriginalConstructor()
            ->getMock();

        $newCartsCollectionMock = $this->getMockBuilder(Mage_Sales_Model_Resource_Quote_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('addFieldToFilter', 'getSelect', 'getIterator'))
            ->getMock();

        $cartModelMock = $this->getMockBuilder(Mage_Sales_Model_Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(
                array('getEntityId', 'getCustomerEmail', 'getCustomerId', 'getUpdatedAt', 'getAllVisibleItems')
            )->getMock();

        $ordersCollectionMock = $this->getMockBuilder(Mage_Sales_Model_Resource_Order_Collection::class)
            ->disableOriginalConstructor()->setMethods(array('getSize', 'addFieldToFilter'))
            ->getMock();

        $cartsApiMock->expects($this->once())->method('getMailchimpStoreId')->willReturn(self::MAILCHIMP_STORE_ID);
        $cartsApiMock->expects($this->once())->method('getMagentoStoreId')->willReturn(self::MAGENTO_STORE_ID);
        $cartsApiMock->expects($this->once())->method('getHelper')->willReturn($helperMock);
        $cartsApiMock->expects($this->once())->method('getDateHelper')->willReturn($dateHelperMock);
        $cartsApiMock->expects($this->once())->method('getBatchId')->willReturn(self::BATCH_ID);
        $cartsApiMock->expects($this->once())->method('getQuoteCollection')->willReturn($newCartsCollectionMock);

        $newCartsCollectionMock->expects($this->exactly(5))->method('addFieldToFilter')
            ->withConsecutive(
                array(self::STRING_IS_ACTIVE, $arrayAddFieldToFilter),
                array($stringCustomerEmail, $arrayAddFieldToFilterCustomerEmail),
                array($stringItemsCount, $arrayAddFieldToFilterItemsCount),
                array(self::STRING_STORE_ID, $arrayAddFieldToFilterStoreId),
                array($stringUpdatedAt, $arrayAddFieldToFilterUpdatedAt)
            );

        $helperMock->expects($this->once())->method('addResendFilter')
            ->with($newCartsCollectionMock, self::MAGENTO_STORE_ID, Ebizmarts_MailChimp_Model_Config::IS_QUOTE);

        $cartsApiMock->expects($this->exactly(2))->method('getFirstDate')
            ->willReturnOnConsecutiveCalls($existFirstDate, $existFirstDate);

        $cartsApiMock->expects($this->once())->method('joinLeftEcommerceSyncData')->with($newCartsCollectionMock);

        $cartsApiMock->expects($this->once())->method('getEcommerceQuoteCollection')
            ->willReturn($cartsCollectionResourceMock);

        $cartsApiMock->expects($this->once())->method('getBatchLimitFromConfig')
            ->willReturn(self::BATCH_LIMIT_FROM_CONFIG);

        $cartsCollectionResourceMock->expects($this->once())->method('addWhere')
            ->with($newCartsCollectionMock, $where, self::BATCH_LIMIT_FROM_CONFIG);

        $newCartsCollectionMock->expects($this->once())->method('getIterator')
            ->willReturn(new ArrayIterator(array($cartModelMock)));

        $cartModelMock->expects($this->once())->method('getEntityId')->willReturn(self::CART_ID);

        $cartsApiMock->expects($this->once())->method('getOrderCollection')->willReturn($ordersCollectionMock);

        $cartModelMock->expects($this->once(4))->method('getCustomerEmail')
            ->willReturn(self::CUSTOMER_EMAIL_BY_CART);

        $ordersCollectionMock->expects($this->exactly(2))->method('addFieldToFilter')
            ->withConsecutive(
                array($stringCustomerEmailMainTable, array('eq' => 'test@ebizmarts.com')),
                array($stringUpdated, $updatedFromDate)
            );

        $cartModelMock->expects($this->once())->method('getUpdatedAt')->willReturn(self::DATE);
        $cartModelMock->expects($this->once())->method('getAllVisibleItems')->willReturn($allVisbleItems);
        $ordersCollectionMock->expects($this->once())->method('getSize')->willReturn($sizeOrderCollection);
        $cartsApiMock->expects($this->once())->method('addSyncData')->with(self::CART_ID);

        $cartsApiMock->_getNewQuotes();
    }

    public function testGetNewQuotesEmpty()
    {
        $existFirstDate = '2018-11-30';
        $stringCustomerEmail = 'customer_email';
        $stringItemsCount = 'items_count';
        $stringUpdatedAt = 'updated_at';
        $arrayAddFieldToFilterUpdatedAt = array('gt' => $existFirstDate);
        $arrayAddFieldToFilterItemsCount = array('gt' => 0);
        $arrayAddFieldToFilterCustomerEmail = array('notnull' => true);
        $arrayAddFieldToFilter = array('eq' => 1);
        $arrayAddFieldToFilterStoreId = array('eq' => self::MAGENTO_STORE_ID);
        $where = "m4m.mailchimp_sync_delta IS NULL";
        $allVisbleItems = array();
        $addFieldToFilterOrderCollection = array('eq' => '');
        $stringCustomerEmailMainTable = 'main_table.customer_email';
        $stringUpdated = 'main_table.updated_at';
        $updatedFromDate = array('from' => self::DATE);

        $cartsApiMock = $this->_cartsApiMock->setMethods(
            array(
                'getMailchimpStoreId',
                'getMagentoStoreId',
                'getHelper',
                'getDateHelper',
                'getBatchId',
                'getQuoteCollection',
                'getFirstDate',
                'joinLeftEcommerceSyncData',
                'getBatchLimitFromConfig',
                'getEcommerceQuoteCollection',
                'getOrderCollection',
                'getWebSiteIdFromMagentoStoreId',
                'addProductNotSentData',
                'makeCart',
                'addSyncDataToken',
                'getToken',
                'addSyncData'
            )
        )->getMock();

        $cartsCollectionResourceMock = $this
            ->getMockBuilder(Ebizmarts_MailChimp_Model_Resource_Ecommercesyncdata_Quote_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('addResendFilter', 'modifyCounterSentPerBatch', 'addWhere'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('addResendFilter', 'modifyCounterSentPerBatch'))
            ->getMock();

        $dateHelperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Date::class)
            ->disableOriginalConstructor()
            ->getMock();

        $newCartsCollectionMock = $this->getMockBuilder(Mage_Sales_Model_Resource_Quote_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('addFieldToFilter', 'getSelect', 'getIterator'))
            ->getMock();

        $cartModelMock = $this->getMockBuilder(Mage_Sales_Model_Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(
                array('getEntityId', 'getCustomerEmail', 'getCustomerId', 'getUpdatedAt', 'getAllVisibleItems')
            )->getMock();

        $ordersCollectionMock = $this->getMockBuilder(Mage_Sales_Model_Resource_Order_Collection::class)
            ->disableOriginalConstructor()->setMethods(array('getSize', 'addFieldToFilter'))
            ->getMock();

        $cartsApiMock->expects($this->once())->method('getMailchimpStoreId')->willReturn(self::MAILCHIMP_STORE_ID);
        $cartsApiMock->expects($this->once())->method('getMagentoStoreId')->willReturn(self::MAGENTO_STORE_ID);
        $cartsApiMock->expects($this->once())->method('getHelper')->willReturn($helperMock);
        $cartsApiMock->expects($this->once())->method('getDateHelper')->willReturn($dateHelperMock);
        $cartsApiMock->expects($this->once())->method('getBatchId')->willReturn(self::BATCH_ID);
        $cartsApiMock->expects($this->once())->method('getQuoteCollection')->willReturn($newCartsCollectionMock);

        $newCartsCollectionMock->expects($this->exactly(5))->method('addFieldToFilter')
            ->withConsecutive(
                array(self::STRING_IS_ACTIVE, $arrayAddFieldToFilter),
                array($stringCustomerEmail, $arrayAddFieldToFilterCustomerEmail),
                array($stringItemsCount, $arrayAddFieldToFilterItemsCount),
                array(self::STRING_STORE_ID, $arrayAddFieldToFilterStoreId),
                array($stringUpdatedAt, $arrayAddFieldToFilterUpdatedAt)
            );

        $helperMock->expects($this->once())->method('addResendFilter')
            ->with($newCartsCollectionMock, self::MAGENTO_STORE_ID, Ebizmarts_MailChimp_Model_Config::IS_QUOTE);

        $cartsApiMock->expects($this->exactly(2))->method('getFirstDate')
            ->willReturnOnConsecutiveCalls($existFirstDate, $existFirstDate);

        $cartsApiMock->expects($this->once())->method('joinLeftEcommerceSyncData')->with($newCartsCollectionMock);

        $cartsApiMock->expects($this->once())->method('getEcommerceQuoteCollection')
            ->willReturn($cartsCollectionResourceMock);

        $cartsApiMock->expects($this->once())->method('getBatchLimitFromConfig')
            ->willReturn(self::BATCH_LIMIT_FROM_CONFIG);

        $cartsCollectionResourceMock->expects($this->once())->method('addWhere')
            ->with($newCartsCollectionMock, $where, self::BATCH_LIMIT_FROM_CONFIG);

        $newCartsCollectionMock->expects($this->once())->method('getIterator')
            ->willReturn(new ArrayIterator(array($cartModelMock)));

        $cartModelMock->expects($this->once())->method('getEntityId')->willReturn(self::CART_ID);

        $cartsApiMock->expects($this->once())->method('getOrderCollection')->willReturn($ordersCollectionMock);

        $cartModelMock->expects($this->once(4))->method('getCustomerEmail')
            ->willReturn(self::CUSTOMER_EMAIL_BY_CART);

        $ordersCollectionMock->expects($this->exactly(2))->method('addFieldToFilter')
            ->withConsecutive(
                array($stringCustomerEmailMainTable, array('eq' => 'test@ebizmarts.com')),
                array($stringUpdated, $updatedFromDate)
            );

        $cartModelMock->expects($this->once())->method('getUpdatedAt')->willReturn(self::DATE);
        $cartModelMock->expects($this->once())->method('getAllVisibleItems')->willReturn($allVisbleItems);
        $cartsApiMock->expects($this->once())->method('addSyncData')->with(self::CART_ID);

        $cartsApiMock->_getNewQuotes();
    }

    public function testGetNewQuotesGuestCustomer()
    {
        $existFirstDate = '2018-11-30';
        $customerId = '';
        $customerEmailAddress = 'test@ebizmarts.com';
        $stringCustomerEmail = 'customer_email';
        $stringItemsCount = 'items_count';
        $stringUpdatedAt = 'updated_at';
        $arrayAddFieldToFilterUpdatedAt = array('gt' => $existFirstDate);
        $arrayAddFieldToFilterItemsCount = array('gt' => 0);
        $arrayAddFieldToFilterCustomerEmail = array('notnull' => true);
        $arrayAddFieldToFilter = array('eq' => 1);
        $arrayAddFieldToFilterStoreId = array('eq' => self::MAGENTO_STORE_ID);
        $where = "m4m.mailchimp_sync_delta IS NULL";
        $allVisbleItems = array('item');
        $sizeOrderCollection = 0;
        $addFieldToFilterOrderCollection = array('eq' => self::CUSTOMER_EMAIL_BY_CART);
        $stringCustomerEmailMainTable = 'main_table.customer_email';
        $stringUpdated = 'main_table.updated_at';
        $updatedFromDate = array('from' => self::DATE);

        $cartsApiMock = $this->_cartsApiMock->setMethods(
            array(
                'getMailchimpStoreId',
                'getMagentoStoreId',
                'getHelper',
                'getDateHelper',
                'getBatchId',
                'getQuoteCollection',
                'getFirstDate',
                'joinLeftEcommerceSyncData',
                'getBatchLimitFromConfig',
                'getEcommerceQuoteCollection',
                'getOrderCollection',
                'getCustomerModel',
                'getWebSiteIdFromMagentoStoreId',
                'addProductNotSentData',
                'makeCart',
                'addSyncDataToken',
                'getToken',
                'addSyncData'
            )
        )->getMock();

        $cartsCollectionResourceMock = $this
            ->getMockBuilder(Ebizmarts_MailChimp_Model_Resource_Ecommercesyncdata_Quote_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('addResendFilter', 'addWhere'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('addResendFilter'))
            ->getMock();

        $dateHelperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Date::class)
            ->disableOriginalConstructor()
            ->getMock();

        $newCartsCollectionMock = $this->getMockBuilder(Mage_Sales_Model_Resource_Quote_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('addFieldToFilter', 'getSelect', 'getIterator'))
            ->getMock();

        $customerModelMock = $this->getMockBuilder(Mage_Customer_Model_Customer::class)
            ->disableOriginalConstructor()
            ->setMethods(
                array('getEntityId', 'getCustomerEmail', 'getAllVisibleItems',
                    'getEmail', 'setWebsiteId', 'loadByEmail')
            )->getMock();

        $cartModelMock = $this->getMockBuilder(Mage_Sales_Model_Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(
                array('getEntityId', 'getCustomerEmail', 'getCustomerId', 'getUpdatedAt', 'getAllVisibleItems')
            )->getMock();

        $ordersCollectionMock = $this->getMockBuilder(Mage_Sales_Model_Resource_Order_Collection::class)
            ->disableOriginalConstructor()->setMethods(array('getSize', 'addFieldToFilter'))
            ->getMock();

        $cartsApiMock->expects($this->once())->method('getMailchimpStoreId')->willReturn(self::MAILCHIMP_STORE_ID);
        $cartsApiMock->expects($this->once())->method('getMagentoStoreId')->willReturn(self::MAGENTO_STORE_ID);
        $cartsApiMock->expects($this->once())->method('getHelper')->willReturn($helperMock);
        $cartsApiMock->expects($this->once())->method('getDateHelper')->willReturn($dateHelperMock);
        $cartsApiMock->expects($this->once())->method('getBatchId')->willReturn(self::BATCH_ID);
        $cartsApiMock->expects($this->once())->method('getQuoteCollection')->willReturn($newCartsCollectionMock);

        $newCartsCollectionMock->expects($this->exactly(5))->method('addFieldToFilter')
            ->withConsecutive(
                array(self::STRING_IS_ACTIVE, $arrayAddFieldToFilter),
                array($stringCustomerEmail, $arrayAddFieldToFilterCustomerEmail),
                array($stringItemsCount, $arrayAddFieldToFilterItemsCount),
                array(self::STRING_STORE_ID, $arrayAddFieldToFilterStoreId),
                array($stringUpdatedAt, $arrayAddFieldToFilterUpdatedAt)
            );

        $helperMock->expects($this->once())->method('addResendFilter')
            ->with($newCartsCollectionMock, self::MAGENTO_STORE_ID, Ebizmarts_MailChimp_Model_Config::IS_QUOTE);

        $cartsApiMock->expects($this->exactly(2))->method('getFirstDate')
            ->willReturnOnConsecutiveCalls($existFirstDate, $existFirstDate);

        $cartsApiMock->expects($this->once())->method('joinLeftEcommerceSyncData')->with($newCartsCollectionMock);

        $cartsApiMock->expects($this->once())->method('getEcommerceQuoteCollection')
            ->willReturn($cartsCollectionResourceMock);

        $cartsApiMock->expects($this->once())->method('getBatchLimitFromConfig')
            ->willReturn(self::BATCH_LIMIT_FROM_CONFIG);

        $cartsCollectionResourceMock->expects($this->once())->method('addWhere')
            ->with($newCartsCollectionMock, $where, self::BATCH_LIMIT_FROM_CONFIG);

        $newCartsCollectionMock->expects($this->once())->method('getIterator')
            ->willReturn(new ArrayIterator(array($cartModelMock)));

        $cartModelMock->expects($this->once())->method('getEntityId')->willReturn(self::CART_ID);

        $cartsApiMock->expects($this->once())->method('getOrderCollection')->willReturn($ordersCollectionMock);

        $cartModelMock->expects($this->once(4))->method('getCustomerEmail')
            ->willReturn(self::CUSTOMER_EMAIL_BY_CART);


        $cartsApiMock->expects($this->once())->method('getCustomerModel')->willReturn($customerModelMock);
        $ordersCollectionMock->expects($this->exactly(2))->method('addFieldToFilter')
            ->withConsecutive(
                array($stringCustomerEmailMainTable, $addFieldToFilterOrderCollection),
                array($stringUpdated, $updatedFromDate)
            );

        $cartModelMock->expects($this->once())->method('getUpdatedAt')->willReturn(self::DATE);
        $cartModelMock->expects($this->once())->method('getAllVisibleItems')->willReturn($allVisbleItems);
        $ordersCollectionMock->expects($this->once())->method('getSize')->willReturn($sizeOrderCollection);

        $cartsApiMock->expects($this->once())->method('getWebSiteIdFromMagentoStoreId')
            ->with(self::MAGENTO_STORE_ID)->willReturn(self::WEB_SITE_ID_FROM_MAGENTO_STORE_ID);

        $customerModelMock->expects($this->once())->method('setWebsiteId')
            ->with(self::WEB_SITE_ID_FROM_MAGENTO_STORE_ID);

        $customerModelMock->expects($this->once())->method('loadByEmail')->with(self::CUSTOMER_EMAIL_BY_CART);
        $customerModelMock->expects($this->once())->method('getEmail')->willReturn($customerEmailAddress);

        $cartModelMock->expects($this->once())->method('getCustomerId')->willReturn($customerId);

        $cartsApiMock->_getNewQuotes();
    }

    public function testGetNewQuotesEmptyJson()
    {
        $existFirstDate = '2018-11-30';
        $customerId = 1;
        $customerEmailAddress = '';
        $allCarts = array(
            array(
                'method' => 'DELETE',
                'path' => '/ecommerce/stores/' . self::MAILCHIMP_STORE_ID . '/carts/' . self::ALREADY_SENT_CART_ID,
                'operation_id' => self::BATCH_ID . '_' . self::ALREADY_SENT_CART_ID, 'body' => ''
            )
        );
        $cartJson = '';
        $stringCustomerEmail = 'customer_email';
        $stringItemsCount = 'items_count';
        $stringUpdatedAt = 'updated_at';
        $arrayAddFieldToFilterUpdatedAt = array('gt' => $existFirstDate);
        $arrayAddFieldToFilterItemsCount = array('gt' => 0);
        $arrayAddFieldToFilterCustomerEmail = array('notnull' => true);
        $arrayAddFieldToFilter = array('eq' => 1);
        $arrayAddFieldToFilterStoreId = array('eq' => self::MAGENTO_STORE_ID);
        $where = "m4m.mailchimp_sync_delta IS NULL";
        $allVisbleItems = array('item');
        $sizeOrderCollection = 0;
        $addFieldToFilterOrderCollection = array('eq' => self::CUSTOMER_EMAIL_BY_CART);
        $stringCustomerEmailMainTable = 'main_table.customer_email';
        $stringUpdated = 'main_table.updated_at';
        $addFieldToFilterUpdated = array('from' => '');

        $cartsApiMock = $this->_cartsApiMock->setMethods(
            array(
                'getMailchimpStoreId',
                'getMagentoStoreId',
                'getHelper',
                'getDateHelper',
                'getBatchId',
                'getQuoteCollection',
                'getFirstDate',
                'joinLeftEcommerceSyncData',
                'getBatchLimitFromConfig',
                'getEcommerceQuoteCollection',
                'getOrderCollection',
                'getCustomerModel',
                'getWebSiteIdFromMagentoStoreId',
                'getAllCartsByEmail',
                'getCounter',
                'markSyncDataAsDeleted',
                'addProductNotSentData',
                'makeCart',
                'addSyncDataToken',
                'addSyncDataError',
                'getToken'
            )
        )->getMock();

        $cartsCollectionResourceMock = $this
            ->getMockBuilder(Ebizmarts_MailChimp_Model_Resource_Ecommercesyncdata_Quote_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('addResendFilter', 'modifyCounterSentPerBatch', 'addWhere'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('addResendFilter', 'modifyCounterSentPerBatch'))
            ->getMock();

        $dateHelperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Date::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getCurrentDateTime'))
            ->getMock();

        $newCartsCollectionMock = $this->getMockBuilder(Mage_Sales_Model_Resource_Quote_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('addFieldToFilter', 'getSelect', 'getIterator'))
            ->getMock();

        $customerModelMock = $this->getMockBuilder(Mage_Customer_Model_Customer::class)
            ->disableOriginalConstructor()
            ->setMethods(
                array('getEntityId', 'getCustomerEmail', 'getAllVisibleItems',
                    'getEmail', 'setWebsiteId', 'loadByEmail')
            )->getMock();

        $cartByEmailCollectionMock = $this->getMockBuilder(Mage_Sales_Model_Resource_Quote_Collection::class)
            ->disableOriginalConstructor()->setMethods(array('clear', 'getIterator'))
            ->getMock();

        $cartModelMock = $this->getMockBuilder(Mage_Sales_Model_Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(
                array('getEntityId', 'getCustomerEmail', 'getCustomerId', 'getUpdatedAt', 'getAllVisibleItems')
            )->getMock();

        $cartByEmailModelMock = $this->getMockBuilder(Mage_Sales_Model_Quote::class)
            ->disableOriginalConstructor()->setMethods(array('getEntityId'))
            ->getMock();

        $ordersCollectionMock = $this->getMockBuilder(Mage_Sales_Model_Resource_Order_Collection::class)
            ->disableOriginalConstructor()->setMethods(array('getSize', 'addFieldToFilter'))
            ->getMock();

        $cartsApiMock->expects($this->once())->method('getMailchimpStoreId')->willReturn(self::MAILCHIMP_STORE_ID);
        $cartsApiMock->expects($this->once())->method('getMagentoStoreId')->willReturn(self::MAGENTO_STORE_ID);
        $cartsApiMock->expects($this->once())->method('getHelper')->willReturn($helperMock);
        $cartsApiMock->expects($this->once())->method('getDateHelper')->willReturn($dateHelperMock);
        $cartsApiMock->expects($this->once())->method('getBatchId')->willReturn(self::BATCH_ID);
        $cartsApiMock->expects($this->once())->method('getQuoteCollection')->willReturn($newCartsCollectionMock);

        $newCartsCollectionMock->expects($this->exactly(5))->method('addFieldToFilter')
            ->withConsecutive(
                array(self::STRING_IS_ACTIVE, $arrayAddFieldToFilter),
                array($stringCustomerEmail, $arrayAddFieldToFilterCustomerEmail),
                array($stringItemsCount, $arrayAddFieldToFilterItemsCount),
                array(self::STRING_STORE_ID, $arrayAddFieldToFilterStoreId),
                array($stringUpdatedAt, $arrayAddFieldToFilterUpdatedAt)
            );

        $helperMock->expects($this->once())->method('addResendFilter')
            ->with($newCartsCollectionMock, self::MAGENTO_STORE_ID, Ebizmarts_MailChimp_Model_Config::IS_QUOTE);

        $cartsApiMock->expects($this->exactly(2))->method('getFirstDate')
            ->willReturnOnConsecutiveCalls($existFirstDate, $existFirstDate);

        $cartsApiMock->expects($this->once())->method('joinLeftEcommerceSyncData')->with($newCartsCollectionMock);

        $cartsApiMock->expects($this->once())->method('getEcommerceQuoteCollection')
            ->willReturn($cartsCollectionResourceMock);

        $cartsApiMock->expects($this->once())->method('getBatchLimitFromConfig')
            ->willReturn(self::BATCH_LIMIT_FROM_CONFIG);

        $cartsCollectionResourceMock->expects($this->once())->method('addWhere')
            ->with($newCartsCollectionMock, $where, self::BATCH_LIMIT_FROM_CONFIG);

        $newCartsCollectionMock->expects($this->once())->method('getIterator')
            ->willReturn(new ArrayIterator(array($cartModelMock)));

        $cartModelMock->expects($this->once())->method('getEntityId')->willReturn(self::CART_ID);

        $cartsApiMock->expects($this->once())->method('getOrderCollection')->willReturn($ordersCollectionMock);

        $cartModelMock->expects($this->once(4))->method('getCustomerEmail')
            ->willReturn(self::CUSTOMER_EMAIL_BY_CART);

        $cartsApiMock->expects($this->once())->method('getCustomerModel')->willReturn($customerModelMock);
        /*$ordersCollectionMock->expects($this->exactly(2))->method('addFieldToFilter')
            ->withConsecutive(
                array($stringCustomerEmailMainTable, $addFieldToFilterOrderCollection),
                array($stringUpdated, $updatedFromDate)//$addFieldToFilterUpdated $arrayAddFieldToFilterUpdatedAt
            );*/

        $cartModelMock->expects($this->once())->method('getUpdatedAt')->willReturn(self::DATE);
        $cartModelMock->expects($this->once())->method('getAllVisibleItems')->willReturn($allVisbleItems);
        $ordersCollectionMock->expects($this->once())->method('getSize')->willReturn($sizeOrderCollection);

        $cartsApiMock->expects($this->once())->method('getWebSiteIdFromMagentoStoreId')
            ->with(self::MAGENTO_STORE_ID)->willReturn(self::WEB_SITE_ID_FROM_MAGENTO_STORE_ID);

        $customerModelMock->expects($this->once())->method('setWebsiteId')
            ->with(self::WEB_SITE_ID_FROM_MAGENTO_STORE_ID);

        $customerModelMock->expects($this->once())->method('loadByEmail')->with(self::CUSTOMER_EMAIL_BY_CART);
        $customerModelMock->expects($this->once())->method('getEmail')->willReturn($customerEmailAddress);

        $cartsApiMock->expects($this->once())->method('getAllCartsByEmail')->with(self::CUSTOMER_EMAIL_BY_CART)
            ->willReturn($cartByEmailCollectionMock);

        $cartByEmailCollectionMock->expects($this->once())->method('getIterator')
            ->willReturn(new ArrayIterator(array($cartByEmailModelMock)));

        $cartsApiMock->expects($this->once(2))->method('getCounter')->willReturn(self::COUNTER);

        $cartByEmailModelMock->expects($this->once())->method('getEntityId')->willReturn(self::ALREADY_SENT_CART_ID);
        $cartsApiMock->expects($this->once())->method('markSyncDataAsDeleted')->with(self::ALREADY_SENT_CART_ID);

        $cartByEmailCollectionMock->expects($this->once())->method('clear');
        $cartModelMock->expects($this->once())->method('getCustomerId')->willReturn($customerId);
        $cartsApiMock->expects($this->once())->method('addProductNotSentData')->with($cartModelMock, $allCarts)
            ->willReturn($allCarts);

        $cartsApiMock->expects($this->once())->method('makeCart')->with($cartModelMock)->willReturn($cartJson);
        $dateHelperMock->expects($this->once())->method('getCurrentDateTime')->willReturn(self::DATE);
        $cartsApiMock->expects($this->once())->method('addSyncDataError')
            ->with(
                self::CART_ID, 'There is not supported products in this cart.', null, false, self::DATE
            );

        $cartsApiMock->_getNewQuotes();
    }

    public function testGetAllCartsByEmail()
    {
        $mailchimpTableName = 'm4m';
        $stringIsActive = 'is_active';
        $arrayAddToFilterIsActive = array('eq' => 1);
        $stringStoreId = 'store_id';
        $arrayAddToFilterStoreId = array('eq' => self::MAGENTO_STORE_ID);
        $stringCustomerId = 'customer_email';
        $arrayAddToFilterCustomerId = array('eq' => self::CUSTOMER_EMAIL_BY_CART);
        $arrayMailchimpTableName = array('m4m' => $mailchimpTableName);
        $condition = "m4m.related_id = main_table.entity_id AND m4m.type = '"
            . Ebizmarts_MailChimp_Model_Config::IS_QUOTE
            . "' AND m4m.mailchimp_store_id = '" . self::MAILCHIMP_STORE_ID . "'";
        $mailchimpSelect = array('m4m.*');
        $where = "m4m.mailchimp_sync_deleted = 0 AND m4m.mailchimp_store_id = '" . self::MAILCHIMP_STORE_ID . "'";

        $cartsApiMock = $this->_cartsApiMock
            ->setMethods(
                array(
                        'getQuoteCollection', 'getMagentoStoreId', 'joinLeftEcommerceSyncData',
                        'getMailchimpStoreId', 'getEcommerceQuoteCollection')
            )->getMock();

        $newCartsCollectionMock = $this
            ->getMockBuilder(Mage_Sales_Model_Resource_Quote_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('addFieldToFilter'))
            ->getMock();

            $cartsCollectionResourceMock = $this
            ->getMockBuilder(Ebizmarts_MailChimp_Model_Resource_Ecommercesyncdata_Quote_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('addWhere'))
            ->getMock();

        $cartsApiMock->expects($this->once())
            ->method('getQuoteCollection')
            ->willReturn($newCartsCollectionMock);

        $newCartsCollectionMock->expects($this->exactly(3))
            ->method('addFieldToFilter')
            ->withConsecutive(
                array($stringIsActive, $arrayAddToFilterIsActive),
                array($stringStoreId, $arrayAddToFilterStoreId),
                array($stringCustomerId, $arrayAddToFilterCustomerId)
            );

        $cartsApiMock->expects($this->once())->method('getMagentoStoreId')->willReturn(self::MAGENTO_STORE_ID);
        $cartsApiMock->expects($this->once())->method('joinLeftEcommerceSyncData')->willReturn($newCartsCollectionMock);
        $cartsApiMock->expects($this->once())->method('getMailchimpStoreId')->willReturn(self::MAILCHIMP_STORE_ID);
        $cartsApiMock->expects($this->once())->method('getEcommerceQuoteCollection')
            ->willReturn($cartsCollectionResourceMock);
        $cartsCollectionResourceMock->expects($this->once())->method('addWhere')
            ->with($newCartsCollectionMock, $where);

        $cartsApiMock->getAllCartsByEmail(self::CUSTOMER_EMAIL_BY_CART);
    }

    public function testGetCustomer()
    {
        $firstName = 'firstname';
        $lastName = 'lastname';
        $street = array(
            'address',
            'address2'
        );
        $city = 'city';
        $region = 'region';
        $optinStatus = false;
        $company = 'company';
        $regionCode = 'regionCode';
        $postCode = 'postCode';
        $country = 'country';

        $cartsApiMock = $this->_cartsApiMock
            ->setMethods(array('getApiCustomersOptIn', 'getCountryModel'))
            ->getMock();

        $cartModelMock = $this
            ->getMockBuilder(Mage_Sales_Model_Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getCustomerFirstname', 'getCustomerLastname', 'getBillingAddress'))
            ->getMock();

        $billingAddressMock = $this
            ->getMockBuilder(Mage_Sales_Model_Order_Address::class)
            ->setMethods(
                array(
                    'getStreet', 'getCity', 'getRegion', 'getRegionCode',
                    'getPostcode', 'getCountry', 'getCompany'
                )
            )
            ->getMock();

        $cartsApiMock->expects($this->once())
            ->method('getApiCustomersOptIn')
            ->with(self::MAGENTO_STORE_ID)
            ->willReturn($optinStatus);

        $cartsApiMock->expects($this->once())
            ->method('getCountryModel')
            ->with($billingAddressMock)
            ->willReturn($country);

        $cartModelMock->expects($this->once())
            ->method('getCustomerFirstname')
            ->willReturn($firstName);

        $cartModelMock->expects($this->once())
            ->method('getCustomerLastname')
            ->willReturn($lastName);

        $cartModelMock->expects($this->once())
            ->method('getBillingAddress')
            ->willReturn($billingAddressMock);

        $billingAddressMock->expects($this->once())
            ->method('getStreet')
            ->willReturn($street);

        $billingAddressMock->expects($this->exactly(2))
            ->method('getCity')
            ->willReturnOnConsecutiveCalls($city, $city);

        $billingAddressMock->expects($this->exactly(2))
            ->method('getRegion')
            ->willReturnOnConsecutiveCalls($region, $region);

        $billingAddressMock->expects($this->exactly(2))
            ->method('getRegionCode')
            ->willReturnOnConsecutiveCalls($regionCode, $regionCode);

        $billingAddressMock->expects($this->exactly(2))
            ->method('getPostcode')
            ->willReturnOnConsecutiveCalls($postCode, $postCode);

        $billingAddressMock->expects($this->exactly(2))
            ->method('getCountry')
            ->willReturnOnConsecutiveCalls($country, $country);

        $billingAddressMock->expects($this->exactly(2))
            ->method('getCompany')
            ->willReturnOnConsecutiveCalls($company, $company);

        $cartsApiMock->_getCustomer($cartModelMock, self::MAGENTO_STORE_ID);
    }

    public function testMakeCart()
    {
        $checkoutUrl = 'test';
        $isModified = false;
        $mailchimpCampaignId = 'qwe123erq';

        $cartsApiMock = $this->_cartsApiMock->setMethods(
            array(
                'getMagentoStoreId',
                'getApiProducts',
                '_getCustomer',
                '_getCheckoutUrl',
                '_processCartLines',
            )
        )->getMock();

        $apiProductsMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Products::class)
            ->disableOriginalConstructor()
            ->setMethods(
                array(
                    'setMagentoStoreId', 'updateDisabledProducts', 'isProductEnabled'
                )
            )->getMock();

        $cartModelMock = $this
            ->getMockBuilder(Mage_Sales_Model_Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(
                array(
                        'getMailchimpCampaignId',
                        'getEntityId',
                        'getQuoteCurrencyCode',
                        'getGrandTotal',
                        'getAllVisibleItems',
                        'getCustomerFirstname',
                        'getCustomerLastname',
                        'getBillingAddress'
                )
            )->getMock();

        $cartsApiMock->expects($this->once())
            ->method('getMagentoStoreId')
            ->willReturn(self::MAGENTO_STORE_ID);

        $cartsApiMock->expects($this->once())
            ->method('getApiProducts')
            ->willReturn($apiProductsMock);

        $apiProductsMock->expects($this->once())
            ->method('setMagentoStoreId')
            ->with(self::MAGENTO_STORE_ID);

        $cartModelMock->expects($this->once())
            ->method('getMailchimpCampaignId')
            ->willReturn($mailchimpCampaignId);

        $cartModelMock->expects($this->once())
            ->method('getEntityId')
            ->willReturn(00000);

        $cartsApiMock->expects($this->once())
            ->method('_getCustomer')
            ->with($cartModelMock, self::MAGENTO_STORE_ID)
            ->willReturn(array('Customer'));

        $cartsApiMock->expects($this->once())
            ->method('_getCheckoutUrl')
            ->with($cartModelMock, $isModified)
            ->willReturn($checkoutUrl);

        $cartModelMock->expects($this->once())
            ->method('getQuoteCurrencyCode')
            ->willReturn('USD');

        $cartModelMock->expects($this->once())
            ->method('getGrandTotal')
            ->willReturn(1000);

        $cartsApiMock->expects($this->once())
            ->method('_processCartLines')
            ->with(array(), $apiProductsMock)
            ->willReturn(array());

        $cartModelMock->expects($this->once())
            ->method('getAllVisibleItems')
            ->willReturn(array());

        $cartsApiMock->makeCart($cartModelMock, $isModified);
    }
}
