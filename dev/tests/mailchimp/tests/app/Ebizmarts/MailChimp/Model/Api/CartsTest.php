<?php

class Ebizmarts_MailChimp_Model_Api_CartsTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Ebizmarts_MailChimp_Model_Api_Carts
     */
    private $cartsApiMock;
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
        $this->cartsApiMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Carts::class);
    }

    public function tearDown()
    {
        $this->cartsApiMock = null;
    }

    public function testCreateBatchJson()
    {
        $batchArray = array();

        $cartsApiMock = $this->cartsApiMock->setMethods(array(
            'getHelper',
            '_getConvertedQuotes',
            '_getModifiedQuotes',
            '_getNewQuotes',
            'setBatchId'
        ))
            ->getMock();
        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('isAbandonedCartEnabled', 'getAbandonedCartFirstDate', 'getDateMicrotime', 'getResendTurn'))
            ->getMock();

        $cartsApiMock->expects($this->once())->method('setBatchId')->with(self::BATCH_ID);
        $cartsApiMock->expects($this->once())->method('getHelper')->willReturn($helperMock);
        $cartsApiMock->expects($this->once())->method('_getConvertedQuotes')->with(self::MAILCHIMP_STORE_ID, self::MAGENTO_STORE_ID)->willReturn($batchArray);
        $cartsApiMock->expects($this->once())->method('_getModifiedQuotes')->with(self::MAILCHIMP_STORE_ID, self::MAGENTO_STORE_ID)->willReturn($batchArray);
        $cartsApiMock->expects($this->once())->method('_getNewQuotes')->with(self::MAILCHIMP_STORE_ID, self::MAGENTO_STORE_ID)->willReturn($batchArray);
        $helperMock->expects($this->once())->method('isAbandonedCartEnabled')->with(self::MAGENTO_STORE_ID)->willReturn(true);
        $helperMock->expects($this->once())->method('getAbandonedCartFirstDate')->with(self::MAGENTO_STORE_ID)->willReturn('00-00-00 00:00:00');
        $helperMock->expects($this->once())->method('getDateMicrotime')->willReturn(self::DATE);
        $helperMock->expects($this->once())->method('getResendTurn')->with(self::MAGENTO_STORE_ID)->willReturn(null);

        $cartsApiMock->createBatchJson(self::MAILCHIMP_STORE_ID, self::MAGENTO_STORE_ID);
    }

    public function testCreateBatchJsonisAbandonedCartDisabled()
    {
        $cartsApiMock = $this->cartsApiMock
            ->setMethods(array('getHelper'))
            ->getMock();
        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('isAbandonedCartEnabled'))
            ->getMock();

        $cartsApiMock->expects($this->once())->method('getHelper')->willReturn($helperMock);
        $helperMock->expects($this->once())->method('isAbandonedCartEnabled')->with(self::MAGENTO_STORE_ID)->willReturn(false);

        $cartsApiMock->createBatchJson(self::MAILCHIMP_STORE_ID, self::MAGENTO_STORE_ID);
    }

    public function testGetConvertedQuotes()
    {
        $mailchimpTableName = 'mailchimp_ecommerce_sync_data';
        $arrayAddFieldToFilter = array('eq' => 0);
        $arrayAddFieldToFilterStoreId = array('eq' => self::MAGENTO_STORE_ID);
        $where = "m4m.mailchimp_sync_deleted = 0";
        $arrayTableName = array('m4m' => $mailchimpTableName);
        $conditionSelect = "m4m.related_id = main_table.entity_id and m4m.type = '" . Ebizmarts_MailChimp_Model_Config::IS_QUOTE . "'
            AND m4m.mailchimp_store_id = '" . self::MAILCHIMP_STORE_ID . "'";
        $m4m = array('m4m.*');

        $cartsApiMock = $this->cartsApiMock->setMethods(array(
            'getMailchimpEcommerceDataTableName',
            'getQuoteCollection',
            'getBatchLimitFromConfig',
            'getAllCartsByEmail',
            'getCounter',
            'getBatchId',
            '_updateSyncData',
            'setCounter'
        ))
            ->getMock();
        $quoteResoureceCollectionMock = $this
            ->getMockBuilder(Mage_Sales_Model_Resource_Quote_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('addFieldToFilter', 'getSelect', 'getIterator'))
            ->getMock();
        $varienSelectMock = $this
            ->getMockBuilder(Varien_Db_Select::class)
            ->disableOriginalConstructor()
            ->setMethods(array('joinLeft', 'where', 'limit'))
            ->getMock();
        $cartModelMock = $this
            ->getMockBuilder(Mage_Sales_Model_Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getEntityId', 'getCustomerEmail'))
            ->getMock();
        $quoteByEmailResoureceCollectionMock = $this
            ->getMockBuilder(Mage_Sales_Model_Resource_Quote_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('clear', 'getIterator'))
            ->getMock();
        $cartByEmailModelMock = $this
            ->getMockBuilder(Mage_Sales_Model_Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getEntityId'))
            ->getMock();

        $cartsApiMock->expects($this->once())
            ->method('getMailchimpEcommerceDataTableName')
            ->willReturn($mailchimpTableName);
        $cartsApiMock->expects($this->once())
            ->method('getQuoteCollection')
            ->willReturn($quoteResoureceCollectionMock);
        $cartsApiMock->expects($this->once())
            ->method('getBatchLimitFromConfig')
            ->willReturn(self::BATCH_LIMIT_FROM_CONFIG);
        $cartsApiMock->expects($this->once())
            ->method('getAllCartsByEmail')
            ->with(self::CUSTOMER_EMAIL_BY_CART, self::MAILCHIMP_STORE_ID, self::MAGENTO_STORE_ID)
            ->willReturn($quoteByEmailResoureceCollectionMock);
        $cartsApiMock->expects($this->exactly(4))
            ->method('getCounter')
            ->willReturnOnConsecutiveCalls(
                self::COUNTER,
                self::COUNTER,
                self::COUNTER,
                self::COUNTER
            );
        $cartsApiMock->expects($this->once())
            ->method('getBatchId')
            ->willReturn(self::BATCH_ID);
        $cartsApiMock->expects($this->exactly(2))
            ->method('_updateSyncData')
            ->withConsecutive(
                array(self::ALREADY_SENT_CART_ID, self::MAILCHIMP_STORE_ID, null, null, null, null, 1),
                array(self::CART_ID, self::MAILCHIMP_STORE_ID, null, null, null, null, 1)
            );
        $cartsApiMock->expects($this->exactly(2))
            ->method('setCounter')
            ->withConsecutive(
                array(self::COUNTER + 1),
                array(self::COUNTER + 1)
            );
        $quoteResoureceCollectionMock->expects($this->exactly(2))
            ->method('addFieldToFilter')
            ->withConsecutive(
                array(self::STRING_STORE_ID, $arrayAddFieldToFilterStoreId),
                array(self::STRING_IS_ACTIVE, $arrayAddFieldToFilter)
            );
        $quoteResoureceCollectionMock->expects($this->exactly(3))
            ->method('getSelect')
            ->willReturnOnConsecutiveCalls(
                $varienSelectMock,
                $varienSelectMock,
                $varienSelectMock
            );
        $quoteResoureceCollectionMock->expects($this->once())
            ->method('getIterator')
            ->willReturn(new ArrayIterator(array($cartModelMock)));
        $varienSelectMock->expects($this->once())
            ->method('joinLeft')
            ->with($arrayTableName, $conditionSelect, $m4m);
        $varienSelectMock->expects($this->once())
            ->method('where')
            ->with($where);
        $varienSelectMock->expects($this->once())
            ->method('limit')
            ->with(self::BATCH_LIMIT_FROM_CONFIG);
        $cartModelMock->expects($this->once())
            ->method('getCustomerEmail')
            ->willReturnOnConsecutiveCalls(self::CUSTOMER_EMAIL_BY_CART);
        $cartModelMock->expects($this->once())
            ->method('getEntityId')
            ->willReturn(self::CART_ID);
        $quoteByEmailResoureceCollectionMock->expects($this->once())
            ->method('clear');
        $quoteByEmailResoureceCollectionMock->expects($this->once())
            ->method('getIterator')
            ->willReturn(new ArrayIterator(array($cartByEmailModelMock)));
        $cartByEmailModelMock->expects($this->once())
            ->method('getEntityId')
            ->willReturn(self::ALREADY_SENT_CART_ID);

        $cartsApiMock->_getConvertedQuotes(self::MAILCHIMP_STORE_ID, self::MAGENTO_STORE_ID);
    }

    public function testGetModifiedQuotes()
    {
        $mcTableName = 'mailchimp_ecommerce_sync_data';
        $customerEmailAddress = '';
        $cartJson = '{"id":"692","customer":{"id":"GUEST-2018-11-30-20-00-07-96938700","email_address":"test@ebizmarts.com","opt_in_status":false,"first_name":"Lucia","last_name":"en el checkout","address":{"address1":"asdf","city":"asd","postal_code":"212312","country":"Tajikistan","country_code":"TJ"}},"campaign_id":"482d28ee12","checkout_url":"http:\/\/f3364930.ngrok.io\/mailchimp\/cart\/loadquote\?id=692&token=ec4f79b2e4677d2edc5bf78c934e5794","currency_code":"USD","order_total":"1700.0000","tax_total":0,"lines":[{"id":"1","product_id":"425","product_variant_id":"310","quantity":5,"price":"1700.0000"}]}';
        $customerId = 1;
        $arrayAddFieldToFilter = array('eq' => 1);
        $arrayAddFieldToFilterStoreId = array('eq' => self::MAGENTO_STORE_ID);
        $where = "m4m.mailchimp_sync_deleted = 0
        AND m4m.mailchimp_sync_delta < updated_at";
        $arrayTableName = array('m4m' => $mcTableName);
        $conditionSelect = "m4m.related_id = main_table.entity_id and m4m.type = '" . Ebizmarts_MailChimp_Model_Config::IS_QUOTE . "'
            AND m4m.mailchimp_store_id = '" . self::MAILCHIMP_STORE_ID . "'";
        $m4m = array('m4m.*');
        $allCarts = array(array('method' => 'DELETE', 'path' => '/ecommerce/stores/' . self::MAILCHIMP_STORE_ID . '/carts/' . self::ALREADY_SENT_CART_ID, 'operation_id' => self::BATCH_ID . '_' . self::ALREADY_SENT_CART_ID, 'body' => ''));
        $token = 'ec4f79b2e4677d2edc5bf78c934e5794';

        $cartsApiMock = $this->cartsApiMock->setMethods(
            array(
                'setToken',
                'getToken',
                'getBatchId',
                'getMailchimpEcommerceDataTableName',
                'getBatchLimitFromConfig',
                '_updateSyncData',
                'getQuoteCollection',
                'getCustomerModel',
                'getWebSiteIdFromMagentoStoreId',
                'setCounter',
                'getCounter',
                '_makeCart',
                'getAllCartsByEmail',
                'addProductNotSentData'
            )
        )
            ->getMock();
        $cartModelMock = $this
            ->getMockBuilder(Mage_Sales_Model_Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getEntityId', 'getCustomerEmail', 'getCustomerId'))
            ->getMock();
        $quoteResoureceCollectionMock = $this
            ->getMockBuilder(Mage_Sales_Model_Resource_Quote_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('addFieldToFilter', 'getSelect', 'getIterator'))
            ->getMock();
        $varienSelectMock = $this
            ->getMockBuilder(Varien_Db_Select::class)
            ->disableOriginalConstructor()
            ->setMethods(array('joinLeft', 'where', 'limit'))
            ->getMock();
        $customerModelMock = $this
            ->getMockBuilder(Mage_Customer_Model_Customer::class)
            ->disableOriginalConstructor()
            ->setMethods(array('setWebsiteId', 'loadByEmail', 'getEmail'))
            ->getMock();
        $quoteByEmailResoureceCollectionMock = $this
            ->getMockBuilder(Mage_Sales_Model_Resource_Quote_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('clear', 'getIterator'))
            ->getMock();
        $cartByEmailModelMock = $this
            ->getMockBuilder(Mage_Sales_Model_Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getEntityId'))
            ->getMock();

        $cartsApiMock->expects($this->once())
            ->method('getToken')
            ->willReturn($token);
        $cartsApiMock->expects($this->once())
            ->method('setToken')
            ->with(null);
        $cartsApiMock->expects($this->once())
            ->method('getBatchId')
            ->willReturn(self::BATCH_ID);
        $cartsApiMock->expects($this->once())
            ->method('getMailchimpEcommerceDataTableName')
            ->willReturn($mcTableName);
        $cartsApiMock->expects($this->once())
            ->method('getQuoteCollection')
            ->willReturn($quoteResoureceCollectionMock);
        $cartsApiMock->expects($this->once())
            ->method('getBatchLimitFromConfig')
            ->willReturn(self::BATCH_LIMIT_FROM_CONFIG);
        $cartsApiMock->expects($this->once())
            ->method('getCustomerModel')
            ->willReturn($customerModelMock);
        $cartsApiMock->expects($this->once())
            ->method('getWebSiteIdFromMagentoStoreId')
            ->with(self::MAGENTO_STORE_ID)
            ->willReturn(self::WEB_SITE_ID_FROM_MAGENTO_STORE_ID);
        $cartsApiMock->expects($this->once())
            ->method('getAllCartsByEmail')
            ->with(self::CUSTOMER_EMAIL_BY_CART, self::MAILCHIMP_STORE_ID, self::MAGENTO_STORE_ID)
            ->willReturn($quoteByEmailResoureceCollectionMock);
        $cartsApiMock->expects($this->exactly(4))
            ->method('getCounter')
            ->willReturnOnConsecutiveCalls(
                self::COUNTER,
                self::COUNTER,
                self::COUNTER,
                self::COUNTER
            );
        $cartsApiMock->expects($this->exactly(2))
            ->method('_updateSyncData')
            ->withConsecutive(
                array(self::ALREADY_SENT_CART_ID, self::MAILCHIMP_STORE_ID, null, null, null, null, 1),
                array(self::CART_ID, self::MAILCHIMP_STORE_ID, null, null, null, null, null, $token)
            );
        $cartsApiMock->expects($this->exactly(2))
            ->method('setCounter')
            ->withConsecutive(
                array(self::COUNTER + 1),
                array(self::COUNTER + 1)
            );
        $cartsApiMock->expects($this->once())
            ->method('_makeCart')
            ->with($cartModelMock, self::MAILCHIMP_STORE_ID, self::MAGENTO_STORE_ID, true)
            ->willReturn($cartJson);
        $cartsApiMock->expects($this->once())
            ->method('addProductNotSentData')
            ->with(self::MAILCHIMP_STORE_ID, self::MAGENTO_STORE_ID, $cartModelMock, $allCarts)
            ->willReturn($allCarts);
        $cartModelMock->expects($this->exactly(3))
            ->method('getCustomerEmail')
            ->willReturnOnConsecutiveCalls(
                self::CUSTOMER_EMAIL_BY_CART,
                self::CUSTOMER_EMAIL_BY_CART,
                self::CUSTOMER_EMAIL_BY_CART
            );
        $cartModelMock->expects($this->once())
            ->method('getEntityId')
            ->willReturn(self::CART_ID);
        $cartModelMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn($customerId);
        $quoteResoureceCollectionMock->expects($this->exactly(2))
            ->method('addFieldToFilter')
            ->withConsecutive(
                array(self::STRING_IS_ACTIVE, $arrayAddFieldToFilter),
                array(self::STRING_STORE_ID, $arrayAddFieldToFilterStoreId)
            );
        $quoteResoureceCollectionMock->expects($this->exactly(3))
            ->method('getSelect')
            ->willReturnOnConsecutiveCalls(
                $varienSelectMock,
                $varienSelectMock,
                $varienSelectMock
            );
        $quoteResoureceCollectionMock->expects($this->once())
            ->method('getIterator')
            ->willReturn(new ArrayIterator(array($cartModelMock)));
        $varienSelectMock->expects($this->once())
            ->method('joinLeft')
            ->with($arrayTableName, $conditionSelect, $m4m);
        $varienSelectMock->expects($this->once())
            ->method('where')
            ->with($where);
        $varienSelectMock->expects($this->once())
            ->method('limit')
            ->with(self::BATCH_LIMIT_FROM_CONFIG);
        $customerModelMock->expects($this->once())
            ->method('setWebsiteId')
            ->with(self::WEB_SITE_ID_FROM_MAGENTO_STORE_ID);
        $customerModelMock->expects($this->once())
            ->method('loadByEmail')
            ->with(self::CUSTOMER_EMAIL_BY_CART);
        $customerModelMock->expects($this->once())
            ->method('getEmail')
            ->willReturn($customerEmailAddress);
        $quoteByEmailResoureceCollectionMock->expects($this->once())
            ->method('clear');
        $quoteByEmailResoureceCollectionMock->expects($this->once())
            ->method('getIterator')
            ->willReturn(new ArrayIterator(array($cartByEmailModelMock)));
        $cartByEmailModelMock->expects($this->once())
            ->method('getEntityId')
            ->willReturn(self::ALREADY_SENT_CART_ID);

        $cartsApiMock->_getModifiedQuotes(self::MAILCHIMP_STORE_ID, self::MAGENTO_STORE_ID);
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
        $conditionSelect = "m4m.related_id = main_table.entity_id and m4m.type = '" . Ebizmarts_MailChimp_Model_Config::IS_QUOTE . "'
            AND m4m.mailchimp_store_id = '" . self::MAILCHIMP_STORE_ID . "'";
        $m4m = array('m4m.*');

        $cartsApiMock = $this->cartsApiMock->setMethods(
            array(
                'getQuoteCollection',
                'getBatchLimitFromConfig',
                '_updateSyncData',
                'getCustomerModel',
                'getWebSiteIdFromMagentoStoreId',
                'getBatchId',
                'getOrderCollection',
                'getMailchimpEcommerceDataTableName'
            )
        )
            ->getMock();
        $newCartsCollectionMock = $this
            ->getMockBuilder(Mage_Sales_Model_Resource_Quote_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('addFieldToFilter', 'getSelect', 'getIterator'))
            ->getMock();
        $varienSelectMock = $this
            ->getMockBuilder(Varien_Db_Select::class)
            ->disableOriginalConstructor()
            ->setMethods(array('where', 'limit', 'joinLeft'))
            ->getMock();
        $customerModelMock = $this
            ->getMockBuilder(Mage_Customer_Model_Customer::class)
            ->disableOriginalConstructor()
            ->setMethods(array('setWebsiteId', 'loadByEmail', 'getEmail'))
            ->getMock();
        $cartModelMock = $this
            ->getMockBuilder(Mage_Sales_Model_Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getEntityId', 'getCustomerEmail', 'getCustomerId'))
            ->getMock();

        $cartsApiMock->expects($this->once())
            ->method('getQuoteCollection')
            ->willReturn($newCartsCollectionMock);
        $cartsApiMock->expects($this->once())
            ->method('getBatchLimitFromConfig')
            ->willReturn(self::BATCH_LIMIT_FROM_CONFIG);
        $cartsApiMock->expects($this->once())
            ->method('_updateSyncData')
            ->with(self::CART_ID, self::MAILCHIMP_STORE_ID);
        $cartsApiMock->expects($this->once())
            ->method('getCustomerModel')
            ->willReturn($customerModelMock);
        $cartsApiMock->expects($this->once())
            ->method('getWebSiteIdFromMagentoStoreId')
            ->with(self::MAGENTO_STORE_ID)
            ->willReturn(self::WEB_SITE_ID_FROM_MAGENTO_STORE_ID);
        $cartsApiMock->expects($this->once())
            ->method('getBatchId')
            ->willReturn(self::BATCH_ID);
        $cartsApiMock->expects($this->once())
            ->method('getMailchimpEcommerceDataTableName')
            ->willReturn($mcTableName);
        $newCartsCollectionMock->expects($this->exactly(2))
            ->method('addFieldToFilter')
            ->withConsecutive(
                array(self::STRING_IS_ACTIVE, $arrayAddFieldToFilter),
                array($stringStoreId, $arrayAddFieldToFilterStoreId)
            );
        $newCartsCollectionMock->expects($this->exactly(3))
            ->method('getSelect')
            ->willReturnOnConsecutiveCalls(
                $varienSelectMock,
                $varienSelectMock,
                $varienSelectMock
            );
        $newCartsCollectionMock->expects($this->once())
            ->method('getIterator')
            ->willReturn(new ArrayIterator(array($cartModelMock)));
        $varienSelectMock->expects($this->once())
            ->method('where')
            ->with($where);
        $varienSelectMock->expects($this->once())
            ->method('limit')
            ->with(self::BATCH_LIMIT_FROM_CONFIG);
        $varienSelectMock->expects($this->once())
            ->method('joinLeft')
            ->with($arrayTableName, $conditionSelect, $m4m);
        $customerModelMock->expects($this->once())
            ->method('setWebsiteId')
            ->with(self::WEB_SITE_ID_FROM_MAGENTO_STORE_ID);
        $customerModelMock->expects($this->once())
            ->method('loadByEmail')
            ->with(self::CUSTOMER_EMAIL_BY_CART);
        $customerModelMock->expects($this->exactly(2))
            ->method('getEmail')
            ->willReturnOnConsecutiveCalls(
                $customerEmailAddress,
                $customerEmailAddress
            );
        $cartModelMock->expects($this->exactly(3))
            ->method('getCustomerEmail')
            ->willReturnOnConsecutiveCalls(
                self::CUSTOMER_EMAIL_BY_CART,
                self::CUSTOMER_EMAIL_BY_CART,
                self::CUSTOMER_EMAIL_BY_CART
            );
        $cartModelMock->expects($this->once())
            ->method('getEntityId')
            ->willReturn(self::CART_ID);
        $cartModelMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn($customerId);

        $cartsApiMock->_getModifiedQuotes(self::MAILCHIMP_STORE_ID, self::MAGENTO_STORE_ID);
    }

    public function testGetModifiedQuotesEmptyJson()
    {
        $mcTableName = 'mailchimp_ecommerce_sync_data';
        $customerEmailAddress = '';
        $cartJson = '';
        $customerId = 1;
        $arrayAddFieldToFilter = array('eq' => 1);
        $arrayAddFieldToFilterStoreId = array('eq' => self::MAGENTO_STORE_ID);
        $where = "m4m.mailchimp_sync_deleted = 0
        AND m4m.mailchimp_sync_delta < updated_at";
        $arrayTableName = array('m4m' => $mcTableName);
        $conditionSelect = "m4m.related_id = main_table.entity_id and m4m.type = '" . Ebizmarts_MailChimp_Model_Config::IS_QUOTE . "'
            AND m4m.mailchimp_store_id = '" . self::MAILCHIMP_STORE_ID . "'";
        $m4m = array('m4m.*');
        $allCarts = array(array('method' => 'DELETE', 'path' => '/ecommerce/stores/' . self::MAILCHIMP_STORE_ID . '/carts/' . self::ALREADY_SENT_CART_ID, 'operation_id' => self::BATCH_ID . '_' . self::ALREADY_SENT_CART_ID, 'body' => ''));

        $cartsApiMock = $this->cartsApiMock->setMethods(
            array(
                'setToken',
                'getBatchId',
                'getMailchimpEcommerceDataTableName',
                'getBatchLimitFromConfig',
                '_updateSyncData',
                'getQuoteCollection',
                'getCustomerModel',
                'getWebSiteIdFromMagentoStoreId',
                'setCounter',
                'getCounter',
                '_makeCart',
                'getAllCartsByEmail',
                'addProductNotSentData'
            )
        )
            ->getMock();
        $cartModelMock = $this
            ->getMockBuilder(Mage_Sales_Model_Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getEntityId', 'getCustomerEmail', 'getCustomerId'))
            ->getMock();
        $quoteResoureceCollectionMock = $this
            ->getMockBuilder(Mage_Sales_Model_Resource_Quote_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('addFieldToFilter', 'getSelect', 'getIterator'))
            ->getMock();
        $varienSelectMock = $this
            ->getMockBuilder(Varien_Db_Select::class)
            ->disableOriginalConstructor()
            ->setMethods(array('joinLeft', 'where', 'limit'))
            ->getMock();
        $customerModelMock = $this
            ->getMockBuilder(Mage_Customer_Model_Customer::class)
            ->disableOriginalConstructor()
            ->setMethods(array('setWebsiteId', 'loadByEmail', 'getEmail'))
            ->getMock();
        $quoteByEmailResoureceCollectionMock = $this
            ->getMockBuilder(Mage_Sales_Model_Resource_Quote_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('clear', 'getIterator'))
            ->getMock();
        $cartByEmailModelMock = $this
            ->getMockBuilder(Mage_Sales_Model_Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getEntityId'))
            ->getMock();

        $cartsApiMock->expects($this->once())
            ->method('setToken')
            ->with(null);
        $cartsApiMock->expects($this->once())
            ->method('getBatchId')
            ->willReturn(self::BATCH_ID);
        $cartsApiMock->expects($this->once())
            ->method('getMailchimpEcommerceDataTableName')
            ->willReturn($mcTableName);
        $cartsApiMock->expects($this->once())
            ->method('getQuoteCollection')
            ->willReturn($quoteResoureceCollectionMock);
        $cartsApiMock->expects($this->once())
            ->method('getBatchLimitFromConfig')
            ->willReturn(self::BATCH_LIMIT_FROM_CONFIG);
        $cartsApiMock->expects($this->once())
            ->method('getCustomerModel')
            ->willReturn($customerModelMock);
        $cartsApiMock->expects($this->once())
            ->method('getWebSiteIdFromMagentoStoreId')
            ->with(self::MAGENTO_STORE_ID)
            ->willReturn(self::WEB_SITE_ID_FROM_MAGENTO_STORE_ID);
        $cartsApiMock->expects($this->once())
            ->method('getAllCartsByEmail')
            ->with(self::CUSTOMER_EMAIL_BY_CART, self::MAILCHIMP_STORE_ID, self::MAGENTO_STORE_ID)
            ->willReturn($quoteByEmailResoureceCollectionMock);
        $cartsApiMock->expects($this->exactly(2))
            ->method('getCounter')
            ->willReturnOnConsecutiveCalls(
                self::COUNTER,
                self::COUNTER
            );
        $cartsApiMock->expects($this->exactly(2))
            ->method('_updateSyncData')
            ->withConsecutive(
                array(self::ALREADY_SENT_CART_ID, self::MAILCHIMP_STORE_ID, null, null, null, null, 1),
                array(self::CART_ID, self::MAILCHIMP_STORE_ID)
            );
        $cartsApiMock->expects($this->once())
            ->method('setCounter')
            ->with(self::COUNTER + 1);
        $cartsApiMock->expects($this->once())
            ->method('_makeCart')
            ->with($cartModelMock, self::MAILCHIMP_STORE_ID, self::MAGENTO_STORE_ID, true)
            ->willReturn($cartJson);
        $cartsApiMock->expects($this->once())
            ->method('addProductNotSentData')
            ->with(self::MAILCHIMP_STORE_ID, self::MAGENTO_STORE_ID, $cartModelMock, $allCarts)
            ->willReturn($allCarts);
        $cartModelMock->expects($this->exactly(3))
            ->method('getCustomerEmail')
            ->willReturnOnConsecutiveCalls(
                self::CUSTOMER_EMAIL_BY_CART,
                self::CUSTOMER_EMAIL_BY_CART,
                self::CUSTOMER_EMAIL_BY_CART
            );
        $cartModelMock->expects($this->once())
            ->method('getEntityId')
            ->willReturn(self::CART_ID);
        $cartModelMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn($customerId);
        $quoteResoureceCollectionMock->expects($this->exactly(2))
            ->method('addFieldToFilter')
            ->withConsecutive(
                array(self::STRING_IS_ACTIVE, $arrayAddFieldToFilter),
                array(self::STRING_STORE_ID, $arrayAddFieldToFilterStoreId)
            );
        $quoteResoureceCollectionMock->expects($this->exactly(3))
            ->method('getSelect')
            ->willReturnOnConsecutiveCalls(
                $varienSelectMock,
                $varienSelectMock,
                $varienSelectMock
            );
        $quoteResoureceCollectionMock->expects($this->once())
            ->method('getIterator')
            ->willReturn(new ArrayIterator(array($cartModelMock)));
        $varienSelectMock->expects($this->once())
            ->method('joinLeft')
            ->with($arrayTableName, $conditionSelect, $m4m);
        $varienSelectMock->expects($this->once())
            ->method('where')
            ->with($where);
        $varienSelectMock->expects($this->once())
            ->method('limit')
            ->with(self::BATCH_LIMIT_FROM_CONFIG);
        $customerModelMock->expects($this->once())
            ->method('setWebsiteId')
            ->with(self::WEB_SITE_ID_FROM_MAGENTO_STORE_ID);
        $customerModelMock->expects($this->once())
            ->method('loadByEmail')
            ->with(self::CUSTOMER_EMAIL_BY_CART);
        $customerModelMock->expects($this->once())
            ->method('getEmail')
            ->willReturn($customerEmailAddress);
        $quoteByEmailResoureceCollectionMock->expects($this->once())
            ->method('clear');
        $quoteByEmailResoureceCollectionMock->expects($this->once())
            ->method('getIterator')
            ->willReturn(new ArrayIterator(array($cartByEmailModelMock)));
        $cartByEmailModelMock->expects($this->once())
            ->method('getEntityId')
            ->willReturn(self::ALREADY_SENT_CART_ID);

        $cartsApiMock->_getModifiedQuotes(self::MAILCHIMP_STORE_ID, self::MAGENTO_STORE_ID);
    }

    public function testGetNewQuotesNewQuote()
    {
        $existFirstDate = '2018-11-30';
        $customerId = 1;
        $token = 'ec4f79b2e4677d2edc5bf78c934e5794';
        $customerEmailAddress = '';
        $allCarts = array(array('method' => 'DELETE', 'path' => '/ecommerce/stores/' . self::MAILCHIMP_STORE_ID . '/carts/' . self::ALREADY_SENT_CART_ID, 'operation_id' => self::BATCH_ID . '_' . self::ALREADY_SENT_CART_ID, 'body' => ''));
        $cartJson = '{"id":"692","customer":{"id":"GUEST-2018-11-30-20-00-07-96938700","email_address":"test@ebizmarts.com","opt_in_status":false,"first_name":"Lucia","last_name":"en el checkout","address":{"address1":"asdf","city":"asd","postal_code":"212312","country":"Tajikistan","country_code":"TJ"}},"campaign_id":"482d28ee12","checkout_url":"http:\/\/f3364930.ngrok.io\/mailchimp\/cart\/loadquote\?id=692&token=ec4f79b2e4677d2edc5bf78c934e5794","currency_code":"USD","order_total":"1700.0000","tax_total":0,"lines":[{"id":"1","product_id":"425","product_variant_id":"310","quantity":5,"price":"1700.0000"}]}';
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

        $cartsApiMock = $this->cartsApiMock->setMethods(
            array(
                'getHelper',
                'getQuoteCollection',
                'getFirstDate',
                'joinMailchimpSyncDataWithoutWhere',
                'getBatchLimitFromConfig',
                '_updateSyncData',
                'getCustomerModel',
                'getWebSiteIdFromMagentoStoreId',
                'getAllCartsByEmail',
                'getCounter',
                'getBatchId',
                'setCounter',
                'addProductNotSentData',
                '_makeCart',
                'setToken',
                'getToken',
                'getOrderCollection'
            )
        )
            ->getMock();
        $helperMock = $this
            ->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->setMethods(array('addResendFilter'))
            ->getMock();
        $newCartsCollectionMock = $this
            ->getMockBuilder(Mage_Sales_Model_Resource_Quote_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('addFieldToFilter', 'getSelect', 'getIterator'))
            ->getMock();
        $varienSelectMock = $this
            ->getMockBuilder(Varien_Db_Select::class)
            ->disableOriginalConstructor()
            ->setMethods(array('where', 'limit'))
            ->getMock();
        $customerModelMock = $this
            ->getMockBuilder(Mage_Customer_Model_Customer::class)
            ->disableOriginalConstructor()
            ->setMethods(array('setWebsiteId', 'loadByEmail', 'getEmail'))
            ->getMock();
        $quoteByEmailResoureceCollectionMock = $this
            ->getMockBuilder(Mage_Sales_Model_Resource_Quote_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('clear', 'getIterator'))
            ->getMock();
        $cartModelMock = $this
            ->getMockBuilder(Mage_Sales_Model_Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getEntityId', 'getCustomerEmail', 'getCustomerId', 'getAllVisibleItems'))
            ->getMock();
        $cartByEmailModelMock = $this
            ->getMockBuilder(Mage_Sales_Model_Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getEntityId'))
            ->getMock();
        $orderCollectionMock = $this
            ->getMockBuilder(Mage_Sales_Model_Resource_Order_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getSize', 'addFieldToFilter'))
            ->getMock();

        $cartsApiMock->expects($this->once())
            ->method('getHelper')
            ->willReturn($helperMock);
        $cartsApiMock->expects($this->once())
            ->method('getQuoteCollection')
            ->willReturn($newCartsCollectionMock);
        $cartsApiMock->expects($this->exactly(2))
            ->method('getFirstDate')
            ->willReturnOnConsecutiveCalls(
                $existFirstDate,
                $existFirstDate
            );
        $cartsApiMock->expects($this->once())
            ->method('joinMailchimpSyncDataWithoutWhere')
            ->with($newCartsCollectionMock, self::MAILCHIMP_STORE_ID);
        $cartsApiMock->expects($this->once())
            ->method('getBatchLimitFromConfig')
            ->willReturn(self::BATCH_LIMIT_FROM_CONFIG);
        $cartsApiMock->expects($this->exactly(2))
            ->method('_updateSyncData')
            ->withConsecutive(
                array(self::ALREADY_SENT_CART_ID, self::MAILCHIMP_STORE_ID, null, null, null, null, 1),
                array(self::CART_ID, self::MAILCHIMP_STORE_ID, null, null, null, null, null, $token)
            );
        $cartsApiMock->expects($this->once())
            ->method('getCustomerModel')
            ->willReturn($customerModelMock);
        $cartsApiMock->expects($this->once())
            ->method('getWebSiteIdFromMagentoStoreId')
            ->with(self::MAGENTO_STORE_ID)
            ->willReturn(self::WEB_SITE_ID_FROM_MAGENTO_STORE_ID);
        $cartsApiMock->expects($this->once())
            ->method('getAllCartsByEmail')
            ->with(self::CUSTOMER_EMAIL_BY_CART, self::MAILCHIMP_STORE_ID, self::MAGENTO_STORE_ID)
            ->willReturn($quoteByEmailResoureceCollectionMock);
        $cartsApiMock->expects($this->exactly(4))
            ->method('getCounter')
            ->willReturnOnConsecutiveCalls(
                self::COUNTER,
                self::COUNTER,
                self::COUNTER,
                self::COUNTER
            );
        $cartsApiMock->expects($this->once())
            ->method('getBatchId')
            ->willReturn(self::BATCH_ID);
        $cartsApiMock->expects($this->exactly(2))
            ->method('setCounter')
            ->withConsecutive(
                array(self::COUNTER + 1),
                array(self::COUNTER + 1)
            );
        $cartsApiMock->expects($this->once())
            ->method('addProductNotSentData')
            ->with(self::MAILCHIMP_STORE_ID, self::MAGENTO_STORE_ID, $cartModelMock, $allCarts)
            ->willReturn($allCarts);
        $cartsApiMock->expects($this->once())
            ->method('_makeCart')
            ->with($cartModelMock, self::MAILCHIMP_STORE_ID, self::MAGENTO_STORE_ID)
            ->willReturn($cartJson);
        $cartsApiMock->expects($this->once())
            ->method('getToken')
            ->willReturn($token);
        $cartsApiMock->expects($this->once())
            ->method('setToken')
            ->with(null);
        $cartsApiMock->expects($this->once())
            ->method('getOrderCollection')
            ->willReturn($orderCollectionMock);
        $helperMock->expects($this->once())
            ->method('addResendFilter')
            ->with($newCartsCollectionMock, self::MAGENTO_STORE_ID, Ebizmarts_MailChimp_Model_Config::IS_QUOTE);
        $newCartsCollectionMock->expects($this->exactly(5))
            ->method('addFieldToFilter')
            ->withConsecutive(
                array(self::STRING_IS_ACTIVE, $arrayAddFieldToFilter),
                array($stringCustomerEmail, $arrayAddFieldToFilterCustomerEmail),
                array($stringItemsCount, $arrayAddFieldToFilterItemsCount),
                array(self::STRING_STORE_ID, $arrayAddFieldToFilterStoreId),
                array($stringUpdatedAt, $arrayAddFieldToFilterUpdatedAt)
            );
        $newCartsCollectionMock->expects($this->exactly(2))
            ->method('getSelect')
            ->willReturnOnConsecutiveCalls(
                $varienSelectMock,
                $varienSelectMock
            );
        $newCartsCollectionMock->expects($this->once())
            ->method('getIterator')
            ->willReturn(new ArrayIterator(array($cartModelMock)));
        $varienSelectMock->expects($this->once())
            ->method('where')
            ->with($where);
        $varienSelectMock->expects($this->once())
            ->method('limit')
            ->with(self::BATCH_LIMIT_FROM_CONFIG);
        $customerModelMock->expects($this->once())
            ->method('setWebsiteId')
            ->with(self::WEB_SITE_ID_FROM_MAGENTO_STORE_ID);
        $customerModelMock->expects($this->once())
            ->method('loadByEmail')
            ->with(self::CUSTOMER_EMAIL_BY_CART);
        $customerModelMock->expects($this->once())
            ->method('getEmail')
            ->willReturn($customerEmailAddress);
        $quoteByEmailResoureceCollectionMock->expects($this->once())
            ->method('clear');
        $quoteByEmailResoureceCollectionMock->expects($this->once())
            ->method('getIterator')
            ->willReturn(new ArrayIterator(array($cartByEmailModelMock)));
        $cartModelMock->expects($this->exactly(4))
            ->method('getCustomerEmail')
            ->willReturnOnConsecutiveCalls(
                self::CUSTOMER_EMAIL_BY_CART,
                self::CUSTOMER_EMAIL_BY_CART,
                self::CUSTOMER_EMAIL_BY_CART,
                self::CUSTOMER_EMAIL_BY_CART
            );
        $cartModelMock->expects($this->once())
            ->method('getEntityId')
            ->willReturn(self::CART_ID);
        $cartModelMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn($customerId);
        $cartModelMock->expects($this->once())
            ->method('getAllVisibleItems')
            ->willReturn($allVisbleItems);
        $cartByEmailModelMock->expects($this->once())
            ->method('getEntityId')
            ->willReturn(self::ALREADY_SENT_CART_ID);
        $orderCollectionMock->expects($this->once())
            ->method('getSize')
            ->willReturn($sizeOrderCollection);
        $orderCollectionMock->expects($this->exactly(2))
            ->method('addFieldToFilter')
            ->withConsecutive(
                array($stringCustomerEmailMainTable, $addFieldToFilterOrderCollection),
                array($stringUpdated, $addFieldToFilterUpdated)
            );

        $cartsApiMock->_getNewQuotes(self::MAILCHIMP_STORE_ID, self::MAGENTO_STORE_ID);
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
        $addFieldToFilterUpdated = array('from' => '');

        $cartsApiMock = $this->cartsApiMock->setMethods(
            array(
                'getHelper',
                'getQuoteCollection',
                'getFirstDate',
                'joinMailchimpSyncDataWithoutWhere',
                'getBatchLimitFromConfig',
                '_updateSyncData',
                'getOrderCollection'
            )
        )
            ->getMock();
        $helperMock = $this
            ->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->setMethods(array('addResendFilter'))
            ->getMock();
        $newCartsCollectionMock = $this
            ->getMockBuilder(Mage_Sales_Model_Resource_Quote_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('addFieldToFilter', 'getSelect', 'getIterator'))
            ->getMock();
        $varienSelectMock = $this
            ->getMockBuilder(Varien_Db_Select::class)
            ->disableOriginalConstructor()
            ->setMethods(array('where', 'limit'))
            ->getMock();
        $cartModelMock = $this
            ->getMockBuilder(Mage_Sales_Model_Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getEntityId', 'getAllVisibleItems'))
            ->getMock();
        $orderCollectionMock = $this
            ->getMockBuilder(Mage_Sales_Model_Resource_Order_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getSize', 'addFieldToFilter'))
            ->getMock();

        $cartsApiMock->expects($this->once())
            ->method('getHelper')
            ->willReturn($helperMock);
        $cartsApiMock->expects($this->once())
            ->method('getQuoteCollection')
            ->willReturn($newCartsCollectionMock);
        $cartsApiMock->expects($this->exactly(2))
            ->method('getFirstDate')
            ->willReturnOnConsecutiveCalls(
                $existFirstDate,
                $existFirstDate
            );
        $cartsApiMock->expects($this->once())
            ->method('joinMailchimpSyncDataWithoutWhere')
            ->with($newCartsCollectionMock, self::MAILCHIMP_STORE_ID);
        $cartsApiMock->expects($this->once())
            ->method('getBatchLimitFromConfig')
            ->willReturn(self::BATCH_LIMIT_FROM_CONFIG);
        $cartsApiMock->expects($this->once())
            ->method('_updateSyncData')
            ->with(self::CART_ID, self::MAILCHIMP_STORE_ID);
        $cartsApiMock->expects($this->once())
            ->method('getOrderCollection')
            ->willReturn($orderCollectionMock);
        $helperMock->expects($this->once())
            ->method('addResendFilter')
            ->with($newCartsCollectionMock, self::MAGENTO_STORE_ID, Ebizmarts_MailChimp_Model_Config::IS_QUOTE);
        $newCartsCollectionMock->expects($this->exactly(5))
            ->method('addFieldToFilter')
            ->withConsecutive(
                array(self::STRING_IS_ACTIVE, $arrayAddFieldToFilter),
                array($stringCustomerEmail, $arrayAddFieldToFilterCustomerEmail),
                array($stringItemsCount, $arrayAddFieldToFilterItemsCount),
                array(self::STRING_STORE_ID, $arrayAddFieldToFilterStoreId),
                array($stringUpdatedAt, $arrayAddFieldToFilterUpdatedAt)
            );
        $newCartsCollectionMock->expects($this->exactly(2))
            ->method('getSelect')
            ->willReturnOnConsecutiveCalls(
                $varienSelectMock,
                $varienSelectMock
            );
        $newCartsCollectionMock->expects($this->once())
            ->method('getIterator')
            ->willReturn(new ArrayIterator(array($cartModelMock)));
        $varienSelectMock->expects($this->once())
            ->method('where')
            ->with($where);
        $varienSelectMock->expects($this->once())
            ->method('limit')
            ->with(self::BATCH_LIMIT_FROM_CONFIG);
        $cartModelMock->expects($this->once())
            ->method('getEntityId')
            ->willReturn(self::CART_ID);
        $cartModelMock->expects($this->once())
            ->method('getAllVisibleItems')
            ->willReturn($allVisbleItems);
        $orderCollectionMock->expects($this->once())
            ->method('getSize')
            ->willReturn($sizeOrderCollection);
        $orderCollectionMock->expects($this->exactly(2))
            ->method('addFieldToFilter')
            ->withConsecutive(
                array($stringCustomerEmailMainTable, $addFieldToFilterOrderCollection),
                array($stringUpdated, $addFieldToFilterUpdated)
            );

        $cartsApiMock->_getNewQuotes(self::MAILCHIMP_STORE_ID, self::MAGENTO_STORE_ID);
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
        $addFieldToFilterUpdated = array('from' => '');

        $cartsApiMock = $this->cartsApiMock->setMethods(
            array(
                'getHelper',
                'getQuoteCollection',
                'getFirstDate',
                'joinMailchimpSyncDataWithoutWhere',
                'getBatchLimitFromConfig',
                '_updateSyncData',
                'getOrderCollection'
            )
        )
            ->getMock();
        $helperMock = $this
            ->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->setMethods(array('addResendFilter'))
            ->getMock();
        $newCartsCollectionMock = $this
            ->getMockBuilder(Mage_Sales_Model_Resource_Quote_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('addFieldToFilter', 'getSelect', 'getIterator'))
            ->getMock();
        $varienSelectMock = $this
            ->getMockBuilder(Varien_Db_Select::class)
            ->disableOriginalConstructor()
            ->setMethods(array('where', 'limit'))
            ->getMock();
        $cartModelMock = $this
            ->getMockBuilder(Mage_Sales_Model_Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getEntityId', 'getAllVisibleItems'))
            ->getMock();
        $orderCollectionMock = $this
            ->getMockBuilder(Mage_Sales_Model_Resource_Order_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('addFieldToFilter'))
            ->getMock();

        $cartsApiMock->expects($this->once())
            ->method('getHelper')
            ->willReturn($helperMock);
        $cartsApiMock->expects($this->once())
            ->method('getQuoteCollection')
            ->willReturn($newCartsCollectionMock);
        $cartsApiMock->expects($this->exactly(2))
            ->method('getFirstDate')
            ->willReturnOnConsecutiveCalls(
                $existFirstDate,
                $existFirstDate
            );
        $cartsApiMock->expects($this->once())
            ->method('joinMailchimpSyncDataWithoutWhere')
            ->with($newCartsCollectionMock, self::MAILCHIMP_STORE_ID);
        $cartsApiMock->expects($this->once())
            ->method('getBatchLimitFromConfig')
            ->willReturn(self::BATCH_LIMIT_FROM_CONFIG);
        $cartsApiMock->expects($this->once())
            ->method('_updateSyncData')
            ->with(self::CART_ID, self::MAILCHIMP_STORE_ID);
        $cartsApiMock->expects($this->once())
            ->method('getOrderCollection')
            ->willReturn($orderCollectionMock);
        $helperMock->expects($this->once())
            ->method('addResendFilter')
            ->with($newCartsCollectionMock, self::MAGENTO_STORE_ID, Ebizmarts_MailChimp_Model_Config::IS_QUOTE);
        $newCartsCollectionMock->expects($this->exactly(5))
            ->method('addFieldToFilter')
            ->withConsecutive(
                array(self::STRING_IS_ACTIVE, $arrayAddFieldToFilter),
                array($stringCustomerEmail, $arrayAddFieldToFilterCustomerEmail),
                array($stringItemsCount, $arrayAddFieldToFilterItemsCount),
                array(self::STRING_STORE_ID, $arrayAddFieldToFilterStoreId),
                array($stringUpdatedAt, $arrayAddFieldToFilterUpdatedAt)
            );
        $newCartsCollectionMock->expects($this->exactly(2))
            ->method('getSelect')
            ->willReturnOnConsecutiveCalls(
                $varienSelectMock,
                $varienSelectMock
            );
        $newCartsCollectionMock->expects($this->once())
            ->method('getIterator')
            ->willReturn(new ArrayIterator(array($cartModelMock)));
        $varienSelectMock->expects($this->once())
            ->method('where')
            ->with($where);
        $varienSelectMock->expects($this->once())
            ->method('limit')
            ->with(self::BATCH_LIMIT_FROM_CONFIG);
        $cartModelMock->expects($this->once())
            ->method('getEntityId')
            ->willReturn(self::CART_ID);
        $cartModelMock->expects($this->once())
            ->method('getAllVisibleItems')
            ->willReturn($allVisbleItems);
        $orderCollectionMock->expects($this->exactly(2))
            ->method('addFieldToFilter')
            ->withConsecutive(
                array($stringCustomerEmailMainTable, $addFieldToFilterOrderCollection),
                array($stringUpdated, $addFieldToFilterUpdated)
            );

        $cartsApiMock->_getNewQuotes(self::MAILCHIMP_STORE_ID, self::MAGENTO_STORE_ID);
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
        $addFieldToFilterUpdated = array('from' => '');

        $cartsApiMock = $this->cartsApiMock->setMethods(
            array(
                'getHelper',
                'getQuoteCollection',
                'getFirstDate',
                'joinMailchimpSyncDataWithoutWhere',
                'getBatchLimitFromConfig',
                '_updateSyncData',
                'getCustomerModel',
                'getWebSiteIdFromMagentoStoreId',
                'getBatchId',
                'getOrderCollection'
            )
        )
            ->getMock();
        $helperMock = $this
            ->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->setMethods(array('addResendFilter'))
            ->getMock();
        $newCartsCollectionMock = $this
            ->getMockBuilder(Mage_Sales_Model_Resource_Quote_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('addFieldToFilter', 'getSelect', 'getIterator'))
            ->getMock();
        $varienSelectMock = $this
            ->getMockBuilder(Varien_Db_Select::class)
            ->disableOriginalConstructor()
            ->setMethods(array('where', 'limit'))
            ->getMock();
        $customerModelMock = $this
            ->getMockBuilder(Mage_Customer_Model_Customer::class)
            ->disableOriginalConstructor()
            ->setMethods(array('setWebsiteId', 'loadByEmail', 'getEmail'))
            ->getMock();
        $cartModelMock = $this
            ->getMockBuilder(Mage_Sales_Model_Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getEntityId', 'getCustomerEmail', 'getCustomerId', 'getAllVisibleItems'))
            ->getMock();
        $orderCollectionMock = $this
            ->getMockBuilder(Mage_Sales_Model_Resource_Order_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getSize', 'addFieldToFilter'))
            ->getMock();

        $cartsApiMock->expects($this->once())
            ->method('getHelper')
            ->willReturn($helperMock);
        $cartsApiMock->expects($this->once())
            ->method('getQuoteCollection')
            ->willReturn($newCartsCollectionMock);
        $cartsApiMock->expects($this->exactly(2))
            ->method('getFirstDate')
            ->willReturnOnConsecutiveCalls(
                $existFirstDate,
                $existFirstDate
            );
        $cartsApiMock->expects($this->once())
            ->method('joinMailchimpSyncDataWithoutWhere')
            ->with($newCartsCollectionMock, self::MAILCHIMP_STORE_ID);
        $cartsApiMock->expects($this->once())
            ->method('getBatchLimitFromConfig')
            ->willReturn(self::BATCH_LIMIT_FROM_CONFIG);
        $cartsApiMock->expects($this->once())
            ->method('_updateSyncData')
            ->with(self::CART_ID, self::MAILCHIMP_STORE_ID);
        $cartsApiMock->expects($this->once())
            ->method('getCustomerModel')
            ->willReturn($customerModelMock);
        $cartsApiMock->expects($this->once())
            ->method('getWebSiteIdFromMagentoStoreId')
            ->with(self::MAGENTO_STORE_ID)
            ->willReturn(self::WEB_SITE_ID_FROM_MAGENTO_STORE_ID);
        $cartsApiMock->expects($this->once())
            ->method('getBatchId')
            ->willReturn(self::BATCH_ID);
        $cartsApiMock->expects($this->once())
            ->method('getOrderCollection')
            ->willReturn($orderCollectionMock);
        $helperMock->expects($this->once())
            ->method('addResendFilter')
            ->with($newCartsCollectionMock, self::MAGENTO_STORE_ID, Ebizmarts_MailChimp_Model_Config::IS_QUOTE);
        $newCartsCollectionMock->expects($this->exactly(5))
            ->method('addFieldToFilter')
            ->withConsecutive(
                array(self::STRING_IS_ACTIVE, $arrayAddFieldToFilter),
                array($stringCustomerEmail, $arrayAddFieldToFilterCustomerEmail),
                array($stringItemsCount, $arrayAddFieldToFilterItemsCount),
                array(self::STRING_STORE_ID, $arrayAddFieldToFilterStoreId),
                array($stringUpdatedAt, $arrayAddFieldToFilterUpdatedAt)
            );
        $newCartsCollectionMock->expects($this->exactly(2))
            ->method('getSelect')
            ->willReturnOnConsecutiveCalls(
                $varienSelectMock,
                $varienSelectMock
            );
        $newCartsCollectionMock->expects($this->once())
            ->method('getIterator')
            ->willReturn(new ArrayIterator(array($cartModelMock)));
        $varienSelectMock->expects($this->once())
            ->method('where')
            ->with($where);
        $varienSelectMock->expects($this->once())
            ->method('limit')
            ->with(self::BATCH_LIMIT_FROM_CONFIG);
        $customerModelMock->expects($this->once())
            ->method('setWebsiteId')
            ->with(self::WEB_SITE_ID_FROM_MAGENTO_STORE_ID);
        $customerModelMock->expects($this->once())
            ->method('loadByEmail')
            ->with(self::CUSTOMER_EMAIL_BY_CART);
        $customerModelMock->expects($this->exactly(2))
            ->method('getEmail')
            ->willReturnOnConsecutiveCalls(
                $customerEmailAddress,
                $customerEmailAddress
            );
        $cartModelMock->expects($this->exactly(4))
            ->method('getCustomerEmail')
            ->willReturnOnConsecutiveCalls(
                self::CUSTOMER_EMAIL_BY_CART,
                self::CUSTOMER_EMAIL_BY_CART,
                self::CUSTOMER_EMAIL_BY_CART,
                self::CUSTOMER_EMAIL_BY_CART
            );
        $cartModelMock->expects($this->once())
            ->method('getEntityId')
            ->willReturn(self::CART_ID);
        $cartModelMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn($customerId);
        $cartModelMock->expects($this->once())
            ->method('getAllVisibleItems')
            ->willReturn($allVisbleItems);
        $orderCollectionMock->expects($this->once())
            ->method('getSize')
            ->willReturn($sizeOrderCollection);
        $orderCollectionMock->expects($this->exactly(2))
            ->method('addFieldToFilter')
            ->withConsecutive(
                array($stringCustomerEmailMainTable, $addFieldToFilterOrderCollection),
                array($stringUpdated, $addFieldToFilterUpdated)
            );

        $cartsApiMock->_getNewQuotes(self::MAILCHIMP_STORE_ID, self::MAGENTO_STORE_ID);
    }

    public function testGetNewQuotesEmptyJson()
    {
        $existFirstDate = '2018-11-30';
        $customerId = 1;
        $customerEmailAddress = '';
        $allCarts = array(array('method' => 'DELETE', 'path' => '/ecommerce/stores/' . self::MAILCHIMP_STORE_ID . '/carts/' . self::ALREADY_SENT_CART_ID, 'operation_id' => self::BATCH_ID . '_' . self::ALREADY_SENT_CART_ID, 'body' => ''));
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

        $cartsApiMock = $this->cartsApiMock->setMethods(
            array(
                'getHelper',
                'getQuoteCollection',
                'getFirstDate',
                'joinMailchimpSyncDataWithoutWhere',
                'getBatchLimitFromConfig',
                '_updateSyncData',
                'getCustomerModel',
                'getWebSiteIdFromMagentoStoreId',
                'getAllCartsByEmail',
                'getCounter',
                'getBatchId',
                'setCounter',
                'addProductNotSentData',
                '_makeCart',
                'setToken',
                'getOrderCollection'
            )
        )
            ->getMock();
        $helperMock = $this
            ->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->setMethods(array('addResendFilter'))
            ->getMock();
        $newCartsCollectionMock = $this
            ->getMockBuilder(Mage_Sales_Model_Resource_Quote_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('addFieldToFilter', 'getSelect', 'getIterator'))
            ->getMock();
        $varienSelectMock = $this
            ->getMockBuilder(Varien_Db_Select::class)
            ->disableOriginalConstructor()
            ->setMethods(array('where', 'limit'))
            ->getMock();
        $customerModelMock = $this
            ->getMockBuilder(Mage_Customer_Model_Customer::class)
            ->disableOriginalConstructor()
            ->setMethods(array('setWebsiteId', 'loadByEmail', 'getEmail'))
            ->getMock();
        $quoteByEmailResoureceCollectionMock = $this
            ->getMockBuilder(Mage_Sales_Model_Resource_Quote_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('clear', 'getIterator'))
            ->getMock();
        $cartModelMock = $this
            ->getMockBuilder(Mage_Sales_Model_Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getEntityId', 'getCustomerEmail', 'getCustomerId', 'getAllVisibleItems'))
            ->getMock();
        $cartByEmailModelMock = $this
            ->getMockBuilder(Mage_Sales_Model_Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getEntityId'))
            ->getMock();
        $orderCollectionMock = $this
            ->getMockBuilder(Mage_Sales_Model_Resource_Order_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getSize', 'addFieldToFilter'))
            ->getMock();

        $cartsApiMock->expects($this->once())
            ->method('getHelper')
            ->willReturn($helperMock);
        $cartsApiMock->expects($this->once())
            ->method('getQuoteCollection')
            ->willReturn($newCartsCollectionMock);
        $cartsApiMock->expects($this->exactly(2))
            ->method('getFirstDate')
            ->willReturnOnConsecutiveCalls(
                $existFirstDate,
                $existFirstDate
            );
        $cartsApiMock->expects($this->once())
            ->method('joinMailchimpSyncDataWithoutWhere')
            ->with($newCartsCollectionMock, self::MAILCHIMP_STORE_ID);
        $cartsApiMock->expects($this->once())
            ->method('getBatchLimitFromConfig')
            ->willReturn(self::BATCH_LIMIT_FROM_CONFIG);
        $cartsApiMock->expects($this->exactly(2))
            ->method('_updateSyncData')
            ->withConsecutive(
                array(self::ALREADY_SENT_CART_ID, self::MAILCHIMP_STORE_ID, null, null, null, null, 1),
                array(self::CART_ID, self::MAILCHIMP_STORE_ID)
            );
        $cartsApiMock->expects($this->once())
            ->method('getCustomerModel')
            ->willReturn($customerModelMock);
        $cartsApiMock->expects($this->once())
            ->method('getWebSiteIdFromMagentoStoreId')
            ->with(self::MAGENTO_STORE_ID)
            ->willReturn(self::WEB_SITE_ID_FROM_MAGENTO_STORE_ID);
        $cartsApiMock->expects($this->once())
            ->method('getAllCartsByEmail')
            ->with(self::CUSTOMER_EMAIL_BY_CART, self::MAILCHIMP_STORE_ID, self::MAGENTO_STORE_ID)
            ->willReturn($quoteByEmailResoureceCollectionMock);
        $cartsApiMock->expects($this->exactly(2))
            ->method('getCounter')
            ->willReturnOnConsecutiveCalls(
                self::COUNTER,
                self::COUNTER
            );
        $cartsApiMock->expects($this->once())
            ->method('getBatchId')
            ->willReturn(self::BATCH_ID);
        $cartsApiMock->expects($this->once())
            ->method('setCounter')
            ->with(self::COUNTER + 1);
        $cartsApiMock->expects($this->once())
            ->method('addProductNotSentData')
            ->with(self::MAILCHIMP_STORE_ID, self::MAGENTO_STORE_ID, $cartModelMock, $allCarts)
            ->willReturn($allCarts);
        $cartsApiMock->expects($this->once())
            ->method('_makeCart')
            ->with($cartModelMock, self::MAILCHIMP_STORE_ID, self::MAGENTO_STORE_ID)
            ->willReturn($cartJson);
        $cartsApiMock->expects($this->once())
            ->method('setToken')
            ->with(null);
        $cartsApiMock->expects($this->once())
            ->method('getOrderCollection')
            ->willReturn($orderCollectionMock);
        $helperMock->expects($this->once())
            ->method('addResendFilter')
            ->with($newCartsCollectionMock, self::MAGENTO_STORE_ID, Ebizmarts_MailChimp_Model_Config::IS_QUOTE);
        $newCartsCollectionMock->expects($this->exactly(5))
            ->method('addFieldToFilter')
            ->withConsecutive(
                array(self::STRING_IS_ACTIVE, $arrayAddFieldToFilter),
                array($stringCustomerEmail, $arrayAddFieldToFilterCustomerEmail),
                array($stringItemsCount, $arrayAddFieldToFilterItemsCount),
                array(self::STRING_STORE_ID, $arrayAddFieldToFilterStoreId),
                array($stringUpdatedAt, $arrayAddFieldToFilterUpdatedAt)
            );
        $newCartsCollectionMock->expects($this->exactly(2))
            ->method('getSelect')
            ->willReturnOnConsecutiveCalls(
                $varienSelectMock,
                $varienSelectMock
            );
        $newCartsCollectionMock->expects($this->once())
            ->method('getIterator')
            ->willReturn(new ArrayIterator(array($cartModelMock)));
        $varienSelectMock->expects($this->once())
            ->method('where')
            ->with($where);
        $varienSelectMock->expects($this->once())
            ->method('limit')
            ->with(self::BATCH_LIMIT_FROM_CONFIG);
        $customerModelMock->expects($this->once())
            ->method('setWebsiteId')
            ->with(self::WEB_SITE_ID_FROM_MAGENTO_STORE_ID);
        $customerModelMock->expects($this->once())
            ->method('loadByEmail')
            ->with(self::CUSTOMER_EMAIL_BY_CART);
        $customerModelMock->expects($this->once())
            ->method('getEmail')
            ->willReturn($customerEmailAddress);
        $quoteByEmailResoureceCollectionMock->expects($this->once())
            ->method('clear');
        $quoteByEmailResoureceCollectionMock->expects($this->once())
            ->method('getIterator')
            ->willReturn(new ArrayIterator(array($cartByEmailModelMock)));
        $cartModelMock->expects($this->exactly(4))
            ->method('getCustomerEmail')
            ->willReturnOnConsecutiveCalls(
                self::CUSTOMER_EMAIL_BY_CART,
                self::CUSTOMER_EMAIL_BY_CART,
                self::CUSTOMER_EMAIL_BY_CART,
                self::CUSTOMER_EMAIL_BY_CART
            );
        $cartModelMock->expects($this->once())
            ->method('getEntityId')
            ->willReturn(self::CART_ID);
        $cartModelMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn($customerId);
        $cartModelMock->expects($this->once())
            ->method('getAllVisibleItems')
            ->willReturn($allVisbleItems);
        $cartByEmailModelMock->expects($this->once())
            ->method('getEntityId')
            ->willReturn(self::ALREADY_SENT_CART_ID);
        $orderCollectionMock->expects($this->once())
            ->method('getSize')
            ->willReturn($sizeOrderCollection);
        $orderCollectionMock->expects($this->exactly(2))
            ->method('addFieldToFilter')
            ->withConsecutive(
                array($stringCustomerEmailMainTable, $addFieldToFilterOrderCollection),
                array($stringUpdated, $addFieldToFilterUpdated)
            );

        $cartsApiMock->_getNewQuotes(self::MAILCHIMP_STORE_ID, self::MAGENTO_STORE_ID);
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
        $condition = "m4m.related_id = main_table.entity_id and m4m.type = '" . Ebizmarts_MailChimp_Model_Config::IS_QUOTE . "'
            AND m4m.mailchimp_store_id = '" . self::MAILCHIMP_STORE_ID . "'";
        $m4m = array('m4m.*');
        $where = "m4m.mailchimp_sync_deleted = 0 AND m4m.mailchimp_store_id = '" . self::MAILCHIMP_STORE_ID . "'";

        $cartsApiMock = $this->cartsApiMock
            ->setMethods(array('getMailchimpEcommerceDataTableName', 'getQuoteCollection'))
            ->getMock();
        $newCartsCollectionMock = $this
            ->getMockBuilder(Mage_Sales_Model_Resource_Quote_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('addFieldToFilter', 'getSelect'))
            ->getMock();
        $varienSelectMock = $this
            ->getMockBuilder(Varien_Db_Select::class)
            ->disableOriginalConstructor()
            ->setMethods(array('where', 'joinLeft'))
            ->getMock();

        $cartsApiMock->expects($this->once())
            ->method('getMailchimpEcommerceDataTableName')
            ->willReturn($mailchimpTableName);
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
        $newCartsCollectionMock->expects($this->exactly(2))
            ->method('getSelect')
            ->willReturnOnConsecutiveCalls(
                $varienSelectMock,
                $varienSelectMock
            );

        $varienSelectMock->expects($this->once())
            ->method('joinLeft')
            ->with($arrayMailchimpTableName, $condition, $m4m);
        $varienSelectMock->expects($this->once())
            ->method('where')
            ->with($where);

        $cartsApiMock->getAllCartsByEmail(self::CUSTOMER_EMAIL_BY_CART, self::MAILCHIMP_STORE_ID, self::MAGENTO_STORE_ID);
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

        $cartsApiMock = $this->cartsApiMock
            ->setMethods(array('getApiCustomersOptIn', 'getCountryModel'))
            ->getMock();

        $cartModelMock = $this
            ->getMockBuilder(Mage_Sales_Model_Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getCustomerFirstname', 'getCustomerLastname', 'getBillingAddress'))
            ->getMock();

        $billingAddressMock = $this
            ->getMockBuilder(Mage_Sales_Model_Order_Address::class)
            ->setMethods(array('getStreet', 'getCity', 'getRegion', 'getRegionCode', 'getPostcode', 'getCountry', 'getCompany'))
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
            ->willReturnOnConsecutiveCalls(
                $city,
                $city
            );
        $billingAddressMock->expects($this->exactly(2))
            ->method('getRegion')
            ->willReturnOnConsecutiveCalls(
                $region,
                $region
            );
        $billingAddressMock->expects($this->exactly(2))
            ->method('getRegionCode')
            ->willReturnOnConsecutiveCalls(
                $regionCode,
                $regionCode
            );
        $billingAddressMock->expects($this->exactly(2))
            ->method('getPostcode')
            ->willReturnOnConsecutiveCalls(
                $postCode,
                $postCode
            );
        $billingAddressMock->expects($this->exactly(2))
            ->method('getCountry')
            ->willReturnOnConsecutiveCalls(
                $country,
                $country
            );
        $billingAddressMock->expects($this->exactly(2))
            ->method('getCompany')
            ->willReturnOnConsecutiveCalls(
                $company,
                $company
            );

        $cartsApiMock->_getCustomer($cartModelMock, self::MAGENTO_STORE_ID);
    }

    public function testMakeCart()
    {
        $checkoutUrl = 'test';
        $isModified = false;
        $mailchimpCampaignId = 'qwe123erq';
        $quoteCurrencyCode = 'test';
        $grandTotal = 21;
        $simpleProductString = 'simple_product';
        $productId = 1;
        $productQty = 1;
        $productPrice = 210;
        $variantId = 12;
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
        $isProductEnabled = false;
        $mailchimpSyncError = 0;

        $cartsApiMock = $this->cartsApiMock->setMethods(array(
            '_getCheckoutUrl',
            'isProductTypeConfigurable',
            'getApiCustomersOptIn',
            'getCountryModel',
            'getHelper',
            'getApiProducts'
        ))->getMock();

        $helperMock = $this
            ->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->setMethods(array('getEcommerceSyncDataItem'))
            ->getMock();

        $productSyncDataMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Ecommercesyncdata::class)
            ->setMethods(array('getMailchimpSyncError'))
            ->getMock();

        $apiProductsMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Products::class)
            ->disableOriginalConstructor()
            ->setMethods(array('updateDisabledProducts', 'isProductEnabled'))
            ->getMock();

        $cartModelMock = $this
            ->getMockBuilder(Mage_Sales_Model_Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(array(
                'getMailchimpCampaignId',
                'getEntityId',
                'getQuoteCurrencyCode',
                'getGrandTotal',
                'getAllVisibleItems',
                'getCustomerFirstname',
                'getCustomerLastname',
                'getBillingAddress'
            ))
            ->getMock();

        $itemsMockCollection = $this->getMockBuilder(Mage_Sales_Model_Resource_Quote_Item_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getIterator'))
            ->getMock();

        $itemMock = $this->getMockBuilder(Mage_Sales_Model_Quote_Item::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getProductType', 'getOptionByCode', 'getProductId', 'getQty', 'getRowTotal'))
            ->getMock();

        $optionByCodeMock = $this->getMockBuilder(Mage_Sales_Model_Quote_Item_Option::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getProduct'))
            ->getMock();

        $variantMock = $this->getMockBuilder(Mage_Sales_Model_Quote_Item::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getId'))
            ->getMock();

        $billingAddressMock = $this
            ->getMockBuilder(Mage_Sales_Model_Order_Address::class)
            ->setMethods(array('getStreet', 'getCity', 'getRegion', 'getRegionCode', 'getPostcode', 'getCountry', 'getCompany'))
            ->getMock();

        $cartsApiMock->expects($this->once())
            ->method('getApiCustomersOptIn')
            ->with(self::MAGENTO_STORE_ID)
            ->willReturn($optinStatus);
        $cartsApiMock->expects($this->once())
            ->method('getCountryModel')
            ->with($billingAddressMock)
            ->willReturn($country);
        $cartsApiMock->expects($this->once())
            ->method('_getCheckoutUrl')
            ->with($cartModelMock, $isModified)
            ->willReturn($checkoutUrl);
        $cartsApiMock->expects($this->once())
            ->method('isProductTypeConfigurable')
            ->with($itemMock)
            ->willReturn(true);
        $cartsApiMock->expects($this->once())
            ->method('getHelper')
            ->willReturn($helperMock);
        $cartsApiMock->expects($this->once())
            ->method('getApiProducts')
            ->willReturn($apiProductsMock);

        $helperMock->expects($this->once())
            ->method('getEcommerceSyncDataItem')
            ->willReturn($productSyncDataMock);

        $productSyncDataMock->expects($this->once())
            ->method('getMailchimpSyncError')
            ->willReturn($mailchimpSyncError);

        $apiProductsMock->expects($this->once())
            ->method('updateDisabledProducts')
            ->with($productId, self::MAILCHIMP_STORE_ID);
        $apiProductsMock->expects($this->once())
            ->method('isProductEnabled')
            ->with($productId)
            ->willReturn($isProductEnabled);

        $cartModelMock->expects($this->once())
            ->method('getCustomerFirstname')
            ->willReturn($firstName);
        $cartModelMock->expects($this->once())
            ->method('getCustomerLastname')
            ->willReturn($lastName);
        $cartModelMock->expects($this->once())
            ->method('getBillingAddress')
            ->willReturn($billingAddressMock);
        $cartModelMock->expects($this->once())
            ->method('getMailchimpCampaignId')
            ->willReturn($mailchimpCampaignId);
        $cartModelMock->expects($this->once())
            ->method('getEntityId')
            ->willReturn(self::CART_ID);
        $cartModelMock->expects($this->once())
            ->method('getQuoteCurrencyCode')
            ->willReturn($quoteCurrencyCode);
        $cartModelMock->expects($this->once())
            ->method('getGrandTotal')
            ->willReturn($grandTotal);
        $cartModelMock->expects($this->once())
            ->method('getAllVisibleItems')
            ->willReturn($itemsMockCollection);

        $itemsMockCollection->expects($this->once())
            ->method('getIterator')
            ->willReturn(new ArrayIterator(array($itemMock)));

        $itemMock->expects($this->exactly(2))
            ->method('getProductType')
            ->willReturnOnConsecutiveCalls(
                'grouped',
                'bundle'
            );
        $itemMock->expects($this->exactly(2))
            ->method('getOptionByCode')
            ->withConsecutive(
                array($simpleProductString),
                array($simpleProductString)
            )->willReturnOnConsecutiveCalls(
                $optionByCodeMock,
                $optionByCodeMock
            );
        $itemMock->expects($this->once())
            ->method('getProductId')
            ->willReturn($productId);
        $itemMock->expects($this->once())
            ->method('getQty')
            ->willReturn($productQty);
        $itemMock->expects($this->once())
            ->method('getRowTotal')
            ->willReturn($productPrice);

        $optionByCodeMock->expects($this->once())
            ->method('getProduct')
            ->willReturn($variantMock);

        $variantMock->expects($this->once())
            ->method('getId')
            ->willReturn($variantId);

        $billingAddressMock->expects($this->once())
            ->method('getStreet')
            ->willReturn($street);
        $billingAddressMock->expects($this->exactly(2))
            ->method('getCity')
            ->willReturnOnConsecutiveCalls(
                $city,
                $city
            );
        $billingAddressMock->expects($this->exactly(2))
            ->method('getRegion')
            ->willReturnOnConsecutiveCalls(
                $region,
                $region
            );
        $billingAddressMock->expects($this->exactly(2))
            ->method('getRegionCode')
            ->willReturnOnConsecutiveCalls(
                $regionCode,
                $regionCode
            );
        $billingAddressMock->expects($this->exactly(2))
            ->method('getPostcode')
            ->willReturnOnConsecutiveCalls(
                $postCode,
                $postCode
            );
        $billingAddressMock->expects($this->exactly(2))
            ->method('getCountry')
            ->willReturnOnConsecutiveCalls(
                $country,
                $country
            );
        $billingAddressMock->expects($this->exactly(2))
            ->method('getCompany')
            ->willReturnOnConsecutiveCalls(
                $company,
                $company
            );

        $cartsApiMock->_makeCart($cartModelMock, self::MAILCHIMP_STORE_ID, self::MAGENTO_STORE_ID, $isModified);
    }
}
