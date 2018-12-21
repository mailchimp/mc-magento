<?php

class Ebizmarts_MailChimp_Model_Api_CartsTest extends PHPUnit_Framework_TestCase
{
    /**
 * @var Ebizmarts_MailChimp_Model_Api_Carts
*/
    private $cartsApiMock;

    const DATE = '2017-05-18-14-45-54-38849500';
    const BATCH_ID = 'storeid-1_QUO_2017-05-18-14-45-54-38849500';

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
        $mailchimpStoreId = 'a1s2d3f4g5h6j7k8l9n0';
        $magentoStoreId = 1;
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
        $cartsApiMock->expects($this->once())->method('_getConvertedQuotes')->with($mailchimpStoreId, $magentoStoreId)->willReturn($batchArray);
        $cartsApiMock->expects($this->once())->method('_getModifiedQuotes')->with($mailchimpStoreId, $magentoStoreId)->willReturn($batchArray);
        $cartsApiMock->expects($this->once())->method('_getNewQuotes')->with($mailchimpStoreId, $magentoStoreId)->willReturn($batchArray);

        $helperMock->expects($this->once())->method('isAbandonedCartEnabled')->with($magentoStoreId)->willReturn(true);
        $helperMock->expects($this->once())->method('getAbandonedCartFirstDate')->with($magentoStoreId)->willReturn('00-00-00 00:00:00');
        $helperMock->expects($this->once())->method('getDateMicrotime')->willReturn(self::DATE);
        $helperMock->expects($this->once())->method('getResendTurn')->with($magentoStoreId)->willReturn(null);

        $cartsApiMock->createBatchJson($mailchimpStoreId, $magentoStoreId);
    }

    public function testGetModifiedQuotes()
    {
        $mcTableName = 'mailchimp_ecommerce_sync_data';
        $batchLimitFromConfig = 100;
        $magentoStoreId = 0;
        $webSiteIdFromMagentoStoreId = 0;
        $mailchimpStoreId = '3ade9d9e52e35e9b18d95bdd4d9e9a44';
        $customerEmailAddressByCart = 'luciaines@ebizmarts.com';
        $customerEmailAddress = '';
        $counter = 0;
        $alreadySentCartId = 2;
        $cartJson = '{"id":"692","customer":{"id":"GUEST-2018-11-30-20-00-07-96938700","email_address":"luciaines@ebizmarts.com","opt_in_status":false,"first_name":"Lucia","last_name":"en el checkout","address":{"address1":"asdf","city":"asd","postal_code":"212312","country":"Tajikistan","country_code":"TJ"}},"campaign_id":"482d28ee12","checkout_url":"http:\/\/f3364930.ngrok.io\/mailchimp\/cart\/loadquote\?id=692&token=ec4f79b2e4677d2edc5bf78c934e5794","currency_code":"USD","order_total":"1700.0000","tax_total":0,"lines":[{"id":"1","product_id":"425","product_variant_id":"310","quantity":5,"price":"1700.0000"}]}';
        $cartId = 1;
        $customerId = 1;
        $stringIsActive = 'is_active';
        $stringStoreId = 'store_id';
        $stringCustomerEmail = 'customer_email';
        $arrayAddFieldToFilterCustomerEmail = array('eq' => $customerEmailAddressByCart);
        $arrayAddFieldToFilter = array('eq' => 1);
        $arrayAddFieldToFilterStoreId = array('eq' => $magentoStoreId);
        $where = "m4m.mailchimp_sync_deleted = 0
        AND m4m.mailchimp_sync_delta < updated_at";
        $whereGetAllCartsByEmail = "m4m.mailchimp_sync_deleted = 0 AND m4m.mailchimp_store_id = '" . $mailchimpStoreId . "'";
        $arrayTableName = array('m4m' => $mcTableName);
        $conditionSelect = "m4m.related_id = main_table.entity_id and m4m.type = '" . Ebizmarts_MailChimp_Model_Config::IS_QUOTE . "'
            AND m4m.mailchimp_store_id = '" . $mailchimpStoreId . "'";
        $m4m = array('m4m.*');
        $allCarts = array(array('method' => 'DELETE', 'path' => '/ecommerce/stores/'.$mailchimpStoreId.'/carts/'. $alreadySentCartId, 'operation_id' => self::BATCH_ID . '_' . $alreadySentCartId, 'body' => ''));
        $token = 'ec4f79b2e4677d2edc5bf78c934e5794';

        $cartsApiMock = $this->cartsApiMock->setMethods(
            array('setToken', 'getToken', 'getBatchId', 'getMailchimpEcommerceDataTableName', 'getBatchLimitFromConfig', '_updateSyncData', 'getQuoteCollection', 'getCustomerModel', 'getWebSiteIdFromMagentoStoreId', 'setCounter',  'getCounter', '_makeCart', '_getAllCartsByEmail', 'addProductNotSentData'))
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
            ->willReturn($batchLimitFromConfig);
        $cartsApiMock->expects($this->once())
            ->method('getCustomerModel')
            ->willReturn($customerModelMock);
        $cartsApiMock->expects($this->once())
            ->method('getWebSiteIdFromMagentoStoreId')
            ->with($magentoStoreId)
            ->willReturn($webSiteIdFromMagentoStoreId);
        $cartsApiMock->expects($this->once())
            ->method('_getAllCartsByEmail')
            ->with($customerEmailAddressByCart,$mailchimpStoreId, $magentoStoreId)
            ->willReturn($quoteByEmailResoureceCollectionMock);
        $cartsApiMock->expects($this->exactly(4))
            ->method('getCounter')
            ->willReturnOnConsecutiveCalls(
                $counter,
                $counter,
                $counter,
                $counter
            );
        $cartsApiMock->expects($this->exactly(2))
            ->method('_updateSyncData')
            ->withConsecutive(
            array($alreadySentCartId, $mailchimpStoreId, null, null, null, null, 1),
            array($cartId, $mailchimpStoreId,  null, null, null, null, null, $token)
            );
        $cartsApiMock->expects($this->exactly(2))
            ->method('setCounter')
            ->withConsecutive(
                array($counter + 1),
                array($counter + 1)
            );
        $cartsApiMock->expects($this->once())
            ->method('_makeCart')
            ->with($cartModelMock, $mailchimpStoreId, $magentoStoreId, true)
            ->willReturn($cartJson);
        $cartsApiMock->expects($this->once())
            ->method('addProductNotSentData')
            ->with($mailchimpStoreId, $magentoStoreId, $cartModelMock, $allCarts)
            ->willReturn($allCarts);

        $cartModelMock->expects($this->exactly(3))
            ->method('getCustomerEmail')
            ->willReturnOnConsecutiveCalls(
                $customerEmailAddressByCart,
                $customerEmailAddressByCart,
                $customerEmailAddressByCart
            );
        $cartModelMock->expects($this->once())
            ->method('getEntityId')
            ->willReturn($cartId);
        $cartModelMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn($customerId);

        $quoteResoureceCollectionMock->expects($this->exactly(2))
            ->method('addFieldToFilter')
            ->withConsecutive(
                array($stringIsActive, $arrayAddFieldToFilter),
                array($stringStoreId, $arrayAddFieldToFilterStoreId)
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
            ->with($batchLimitFromConfig);

        $customerModelMock->expects($this->once())
            ->method('setWebsiteId')
            ->with($webSiteIdFromMagentoStoreId);
        $customerModelMock->expects($this->once())
            ->method('loadByEmail')
            ->with($customerEmailAddressByCart);
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
            ->willReturn($alreadySentCartId);

        $cartsApiMock->_getModifiedQuotes($mailchimpStoreId, $magentoStoreId);
    }
}
