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
        $whereGetAllCartsByEmail = "m4m.mailchimp_sync_deleted = 0 AND m4m.mailchimp_store_id = '" . self::MAILCHIMP_STORE_ID . "'";
        $arrayTableName = array('m4m' => $mcTableName);
        $conditionSelect = "m4m.related_id = main_table.entity_id and m4m.type = '" . Ebizmarts_MailChimp_Model_Config::IS_QUOTE . "'
            AND m4m.mailchimp_store_id = '" . self::MAILCHIMP_STORE_ID . "'";
        $m4m = array('m4m.*');
        $allCarts = array(array('method' => 'DELETE', 'path' => '/ecommerce/stores/'.self::MAILCHIMP_STORE_ID.'/carts/'. self::ALREADY_SENT_CART_ID, 'operation_id' => self::BATCH_ID . '_' . self::ALREADY_SENT_CART_ID, 'body' => ''));
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
                '_getAllCartsByEmail',
                'addProductNotSentData'
            ))
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
            ->method('_getAllCartsByEmail')
            ->with(self::CUSTOMER_EMAIL_BY_CART,self::MAILCHIMP_STORE_ID, self::MAGENTO_STORE_ID)
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
            array(self::CART_ID, self::MAILCHIMP_STORE_ID,  null, null, null, null, null, $token)
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

    public function testGetNewQuotes()
    {
        $existFirstDate = '2018-11-30';
        $customerId = 1;
        $token = 'ec4f79b2e4677d2edc5bf78c934e5794';
        $customerEmailAddress = '';
        $allCarts = array(array('method' => 'DELETE', 'path' => '/ecommerce/stores/'.self::MAILCHIMP_STORE_ID.'/carts/'. self::ALREADY_SENT_CART_ID, 'operation_id' => self::BATCH_ID . '_' . self::ALREADY_SENT_CART_ID, 'body' => ''));
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
                '_getAllCartsByEmail',
                'getCounter',
                'getBatchId',
                'setCounter',
                'addProductNotSentData',
                '_makeCart',
                'setToken',
                'getToken',
                'getOrderCollection'
            ))
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
                array(self::CART_ID, self::MAILCHIMP_STORE_ID,  null, null, null, null, null, $token)
            );
        $cartsApiMock->expects($this->once())
            ->method('getCustomerModel')
            ->willReturn($customerModelMock);
        $cartsApiMock->expects($this->once())
            ->method('getWebSiteIdFromMagentoStoreId')
            ->with(self::MAGENTO_STORE_ID)
            ->willReturn(self::WEB_SITE_ID_FROM_MAGENTO_STORE_ID);
        $cartsApiMock->expects($this->once())
            ->method('_getAllCartsByEmail')
            ->with(self::CUSTOMER_EMAIL_BY_CART,self::MAILCHIMP_STORE_ID, self::MAGENTO_STORE_ID)
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
}
