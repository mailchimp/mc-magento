<?php

class Ebizmarts_MailChimp_Model_Api_CustomersTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Ebizmarts_MailChimp_Model_Api_Customers
     */
    protected $_customersApiMock;

    public function setUp()
    {
        Mage::app('default');

        $this->_customersApiMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Customers::class);
    }

    public function tearDown()
    {
        $this->_customersApiMock = null;
    }

    public function testGetOptInYes()
    {
        $this->_customersApiMock = $this->_customersApiMock
            ->setMethods(array('isEcommerceCustomerOptInConfigEnabled'))
            ->getMock();

        $this->_customersApiMock
            ->expects($this->once())
            ->method('isEcommerceCustomerOptInConfigEnabled')
            ->with(1)
            ->willReturn('1');

        $this->assertTrue($this->_customersApiMock->getOptIn(1));
    }

    public function testGetOptInNo()
    {
        $this->_customersApiMock = $this->_customersApiMock
            ->setMethods(array('isEcommerceCustomerOptInConfigEnabled'))
            ->getMock();

        $this->_customersApiMock
            ->expects($this->once())
            ->method('isEcommerceCustomerOptInConfigEnabled')
            ->with(1)
            ->willReturn('0');

        $this->assertFalse($this->_customersApiMock->getOptIn(1));
    }

    public function testGetOptInNoDefaultStore()
    {
        $this->_customersApiMock = $this->_customersApiMock->setMethods(array('isEcommerceCustomerOptInConfigEnabled'))
            ->getMock();

        $this->_customersApiMock
            ->expects($this->once())
            ->method('isEcommerceCustomerOptInConfigEnabled')
            ->with(0)
            ->willReturn('0');

        $this->assertFalse($this->_customersApiMock->getOptIn(0));

        $this->assertFalse($this->_customersApiMock->getOptIn(0));
    }

    public function testCreateBatchJsonOptInFalseCustomerNotSubscribed()
    {
        $mailchimpStoreId = 'a1s2d3f4g5h6j7k8l9n0';
        $customerEmail = 'brian@ebizmarts.com';
        $subscriberId = 1;
        $storeId = 1;
        $optInStatus = false;
        $customerId = 142;
        $customerIds = array($customerId);
        $listId = "e4ef38998b";

        $patchBatchData = array (
            'method' => 'PATCH',
            'path' => '/lists/e4ef38998b/members/45c3ddfc868517aefd34ba8f122ad600',
            'operation_id' => '_SUB_45c3ddfc868517aefd34ba8f122ad600',
            'body' => '{"merge_fields":{"WEBSITE":"1","STOREID":"1","STORENAME":"Default Store View","FNAME":"test",
            "LNAME":"test","CGROUP":"General","CREATEDAT":"2019-07-25T07:05:34+13:00","GENDER":"Male",
            "CUSBRAND":"brand pref."}}'
        );

        $mergeFields = array (
            'WEBSITE' => '1',
            'STOREID' => '1',
            'STORENAME' => 'Default Store View',
            'FNAME' => 'test',
            'LNAME' => 'test',
            'CGROUP' => 'General',
            'CREATEDAT' => '2019-07-25T07:05:34+13:00',
            'GENDER' => 'Male',
            'CUSBRAND' => 'brand pref.',
        );
        $mergeFieldsArray["merge_fields"] = $mergeFields;
        $customerData = array(
            'id' => '142',
            'email_address' => 'newcusto@ebizmarts.com',
            'first_name' => 'FirstName',
            'last_name' => 'LastName',
            'opt_in_status' => false,
            'orders_count' => 0,
            'total_spent' => 0
        );
        $customerJson = '{"id":"142","email_address":"newcusto@ebizmarts.com","first_name":"FirstName",'
            . '"last_name":"LastName","opt_in_status":false,"orders_count":0,"total_spent":0}';
        $operationData = array(
            'method' => 'PUT',
            'path' => '/ecommerce/stores/00ee7808cc513ee772f209d63c034f1f/customers/142',
            'operation_id' => 'storeid-1_CUS_2018-03-14-20-15-03-44361800_142',
            'body' => '{"id":"142","email_address":"newcusto@ebizmarts.com","first_name":"FirstName",'
                . '"last_name":"LastName","opt_in_status":false,"orders_count":0,"total_spent":0}'
        );

        $this->_customersApiMock = $this->_customersApiMock->setMethods(
            array('getCustomersToSync', 'makeBatchId', 'makeCustomersNotSentCollection', 'setOptInStatusForStore',
                'getOptIn',
                'getOptInStatusForStore', 'getBatchMagentoStoreId', '_buildCustomerData', 'makePutBatchStructure',
                '_updateSyncData', 'setMailchimpStoreId', 'setMagentoStoreId',
                'getCustomerResourceCollection', 'getSubscriberModel', 'getMailChimpHelper',
                'isSubscribed', 'makePatchBatchStructure', '_buildMailchimpTags'
            )
        )
            ->getMock();

        $mailchimpTagsApiMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Subscribers_MailchimpTags::class)
            ->disableOriginalConstructor()
            ->setMethods(
                array('getMailchimpTags'
                )
            )
            ->getMock();

        $customerCollectionMock = $this->getMockBuilder(Mage_Customer_Model_Resource_Customer_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getIterator'))
            ->getMock();

        $customerMock = $this->getMockBuilder(Mage_Customer_Model_Customer::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getId', 'getEmail'))
            ->getMock();

        $subscriberMock = $this->getMockBuilder(Mage_Newsletter_Model_Subscriber::class)
            ->disableOriginalConstructor()
            ->setMethods(array('loadByEmail'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getEcommerceSyncDataItem', 'modifyCounterSentPerBatch', 'getGeneralList'))
            ->getMock();

        $syncDataItemMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Ecommercesyncdata::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getId'))
            ->getMock();

        $customerArray = array($customerMock);

        $this->_customersApiMock->expects($this->once())->method('setMailchimpStoreId')->with($mailchimpStoreId);
        $this->_customersApiMock->expects($this->once())->method('setMagentoStoreId')->with($storeId);
        $this->_customersApiMock->expects($this->once())
            ->method('getCustomersToSync')
            ->willReturn($customerIds);
        $this->_customersApiMock->expects($this->once())
            ->method('makeCustomersNotSentCollection')
            ->with($customerIds)
            ->willReturn($customerCollectionMock);
        $this->_customersApiMock->expects($this->once())->method('makeBatchId');
        $this->_customersApiMock->expects($this->once())->method('getBatchMagentoStoreId')->willReturn($storeId);
        $this->_customersApiMock->expects($this->once())->method('getOptin')->with($storeId)->willReturn($optInStatus);
        $this->_customersApiMock->expects($this->once())->method('setOptInStatusForStore')->with($optInStatus);
        $this->_customersApiMock->expects($this->once())->method('getOptInStatusForStore')->willReturn($optInStatus);
        $this->_customersApiMock->expects($this->once())->method('getSubscriberModel')->willReturn($subscriberMock);

        $customerCollectionMock
            ->expects($this->once())
            ->method('getIterator')
            ->willReturn(new ArrayIterator($customerArray));
        $this->_customersApiMock
            ->expects($this->once())
            ->method('_buildCustomerData')
            ->with($customerMock)
            ->willReturn($customerData);
        $this->_customersApiMock
            ->expects($this->once())
            ->method('isSubscribed')
            ->with($subscriberMock, $customerMock)
            ->willReturn(false);
        $this->_customersApiMock
            ->expects($this->once())
            ->method('makePutBatchStructure')
            ->with($customerJson)
            ->willReturn($operationData);
        $this->_customersApiMock->expects($this->once())->method('getMailChimpHelper')->willReturn($helperMock);
        $customerMock->expects($this->exactly(3))
            ->method('getId')
            ->willReturnOnConsecutiveCalls(
                $customerId,
                $customerId
            );
        $customerMock->expects($this->any())->method('getEmail')->willReturn($customerEmail);
        $this->_customersApiMock
            ->expects($this->once())
            ->method('_updateSyncData')
            ->with($customerId, $mailchimpStoreId);

        $helperMock->expects($this->once())
            ->method('getEcommerceSyncDataItem')
            ->with($customerId, Ebizmarts_MailChimp_Model_Config::IS_CUSTOMER, $mailchimpStoreId)
            ->willReturn($syncDataItemMock);
        $helperMock->expects($this->once())
            ->method('modifyCounterSentPerBatch')
            ->with(Ebizmarts_MailChimp_Helper_Data::CUS_MOD);

        $helperMock
            ->expects($this->once())
            ->method('getGeneralList')
            ->with($storeId)
            ->willReturn($listId);

        $syncDataItemMock->expects($this->once())
            ->method('getId')
            ->willReturn($customerId);

        $this->_customersApiMock
            ->expects($this->once())
            ->method('_buildMailchimpTags')
            ->with($subscriberMock, $storeId)
            ->willReturn($mailchimpTagsApiMock);

        $mailchimpTagsApiMock
            ->expects($this->once())
            ->method('getMailchimpTags')
            ->willReturn($mergeFields);

        $this->_customersApiMock
            ->expects($this->once())
            ->method('makePatchBatchStructure')
            ->with($customerMock, $listId, $mergeFieldsArray)
            ->willReturn($patchBatchData);


        $return = $this->_customersApiMock->createBatchJson($mailchimpStoreId, $storeId);
        $this->assertEquals($operationData['method'], $return[0]['method']);
        $this->assertEquals($operationData['path'], $return[0]['path']);
        $this->assertEquals($operationData['operation_id'], $return[0]['operation_id']);
        $this->assertEquals($operationData['body'], $return[0]['body']);
    }

    public function testCreateBatchJsonOptInTrueCustomerNotSubscribed()
    {
        $mailchimpStoreId = 'a1s2d3f4g5h6j7k8l9n0';
        $customerEmail = 'brian@ebizmarts.com';
        $subscriberId = 1;
        $storeId = 1;
        $optInStatus = true;
        $customerId = 142;
        $customerIds = array($customerId);
        $listId = "765c43c40d";

        $customerData = array(
            'id' => '142',
            'email_address' => 'newcusto@ebizmarts.com',
            'first_name' => 'FirstName',
            'last_name' => 'LastName',
            'opt_in_status' => false,
            'orders_count' => 0,
            'total_spent' => 0
        );
        $customerJson = '{"id":"142","email_address":"newcusto@ebizmarts.com","first_name":"FirstName",'
            . '"last_name":"LastName","opt_in_status":false,"orders_count":0,"total_spent":0}';
        $operationData = array(
            'method' => 'PUT',
            'path' => '/ecommerce/stores/00ee7808cc513ee772f209d63c034f1f/customers/142',
            'operation_id' => 'storeid-1_CUS_2018-03-14-20-15-03-44361800_142',
            'body' => '{"id":"142","email_address":"newcusto@ebizmarts.com","first_name":"FirstName",'
                . '"last_name":"LastName","opt_in_status":false,"orders_count":0,"total_spent":0}'
        );

        $this->_customersApiMock = $this->_customersApiMock->setMethods(
            array('getCustomersToSync', 'makeBatchId', 'makeCustomersNotSentCollection', 'getOptin',
                'getBatchMagentoStoreId', '_buildCustomerData', 'makePutBatchStructure',
                '_updateSyncData', 'setMailchimpStoreId', 'setMagentoStoreId',
                'getCustomerResourceCollection', 'getSubscriberModel', 'getMailChimpHelper',
                'isSubscribed','getOptInStatusForStore'
            )
        )
            ->getMock();

        $customerCollectionMock = $this->getMockBuilder(Mage_Customer_Model_Resource_Customer_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getIterator'))
            ->getMock();

        $customerMock = $this->getMockBuilder(Mage_Customer_Model_Customer::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getId', 'getEmail'))
            ->getMock();

        $subscriberMock = $this->getMockBuilder(Mage_Newsletter_Model_Subscriber::class)
            ->disableOriginalConstructor()
            ->setMethods(array('subscribe'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getEcommerceSyncDataItem', 'modifyCounterSentPerBatch', 'getGeneralList'))
            ->getMock();

        $syncDataItemMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Ecommercesyncdata::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getId'))
            ->getMock();

        $customerArray = array($customerMock);

        $this->_customersApiMock->expects($this->once())->method('setMailchimpStoreId')->with($mailchimpStoreId);
        $this->_customersApiMock->expects($this->once())->method('setMagentoStoreId')->with($storeId);
        $this->_customersApiMock->expects($this->once())
            ->method('getCustomersToSync')
            ->willReturn($customerIds);
        $this->_customersApiMock->expects($this->once())
            ->method('makeCustomersNotSentCollection')
            ->with($customerIds)
            ->willReturn($customerCollectionMock);
        $this->_customersApiMock->expects($this->once())->method('makeBatchId');
        $this->_customersApiMock->expects($this->once())->method('getBatchMagentoStoreId')->willReturn($storeId);
        $this->_customersApiMock->expects($this->once())->method('getOptIn')->with($storeId)->willReturn($optInStatus);
        $this->_customersApiMock->expects($this->once())->method('getOptInStatusForStore')->willReturn($optInStatus);
        $this->_customersApiMock->expects($this->once())->method('getSubscriberModel')->willReturn($subscriberMock);

        $subscriberMock
            ->expects($this->once())
            ->method('subscribe')
            ->with($customerEmail)
            ->willReturnSelf();

        $customerCollectionMock
            ->expects($this->once())
            ->method('getIterator')
            ->willReturn(new ArrayIterator($customerArray));
        $this->_customersApiMock
            ->expects($this->once())
            ->method('_buildCustomerData')
            ->with($customerMock)
            ->willReturn($customerData);
        $this->_customersApiMock
            ->expects($this->once())
            ->method('isSubscribed')
            ->with($subscriberMock, $customerMock)
            ->willReturn(false);
        $this->_customersApiMock
            ->expects($this->once())
            ->method('makePutBatchStructure')
            ->with($customerJson)
            ->willReturn($operationData);
        $this->_customersApiMock->expects($this->once())->method('getMailChimpHelper')->willReturn($helperMock);
        $customerMock->expects($this->exactly(2))
            ->method('getId')
            ->willReturnOnConsecutiveCalls(
                $customerId,
                $customerId
            );
        $customerMock->expects($this->any())->method('getEmail')->willReturn($customerEmail);
        $this->_customersApiMock
            ->expects($this->once())
            ->method('_updateSyncData')
            ->with($customerId, $mailchimpStoreId);

        $helperMock->expects($this->once())
            ->method('getEcommerceSyncDataItem')
            ->with($customerId, Ebizmarts_MailChimp_Model_Config::IS_CUSTOMER, $mailchimpStoreId)
            ->willReturn($syncDataItemMock);
        $helperMock->expects($this->once())
            ->method('modifyCounterSentPerBatch')
            ->with(Ebizmarts_MailChimp_Helper_Data::CUS_MOD);

        $helperMock
            ->expects($this->once())
            ->method('getGeneralList')
            ->with($storeId)
            ->willReturn($listId);

        $syncDataItemMock->expects($this->once())
            ->method('getId')
            ->willReturn($customerId);

        $return = $this->_customersApiMock->createBatchJson($mailchimpStoreId, $storeId);
        $this->assertEquals($operationData['method'], $return[0]['method']);
        $this->assertEquals($operationData['path'], $return[0]['path']);
        $this->assertEquals($operationData['operation_id'], $return[0]['operation_id']);
        $this->assertEquals($operationData['body'], $return[0]['body']);
    }

    public function testMakeCustomersNotSentCollectionTotal()
    {
        $customerId = 142;
        $customerIds = array($customerId);

        $this->_customersApiMock = $this->_customersApiMock->setMethods(
            array(
                'getCustomerResourceCollection',
                'joinDefaultBillingAddress',
                'joinSalesData'
            )
        )
            ->getMock();

        $dbSelectMock = $this->getMockBuilder(Varien_Db_Select::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbSelectMock->expects($this->once())->method('group')->with('e.entity_id');

        $customersResourceCollectionMock = $this
            ->getMockBuilder(Mage_Customer_Model_Resource_Customer_Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $customersResourceCollectionMock->expects($this->once())->method('getSelect')->willReturn($dbSelectMock);
        $customersResourceCollectionMock->expects($this->exactly(1))->method('addNameToSelect');

        $this->_customersApiMock->expects($this->once())->method('getCustomerResourceCollection')
            ->willReturn($customersResourceCollectionMock);
        $this->_customersApiMock->expects($this->once())->method('joinDefaultBillingAddress');
        $this->_customersApiMock->expects($this->once())->method('joinSalesData');

        $collectionFrontEnd = $this->_customersApiMock->makeCustomersNotSentCollection($customerIds);

        $this->assertInstanceOf("Mage_Customer_Model_Resource_Customer_Collection", $collectionFrontEnd);
    }
}
