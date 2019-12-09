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
        $storeId = 1;
        $optInStatus = false;
        $customerId = 142;
        $customerIds = array($customerId);
        $listId = "e4ef38998b";
        $isSubscribed = false;

        $patchBatchData = array(
            'method' => 'PATCH',
            'path' => '/lists/e4ef38998b/members/45c3ddfc868517aefd34ba8f122ad600',
            'operation_id' => '_SUB_45c3ddfc868517aefd34ba8f122ad600',
            'body' => '{"merge_fields":{"WEBSITE":"1","STOREID":"1","STORENAME":"Default Store View","FNAME":"test",
            "LNAME":"test","CGROUP":"General","CREATEDAT":"2019-07-25T07:05:34+13:00","GENDER":"Male",
            "CUSBRAND":"brand pref."}}'
        );

        $mergeFields = array(
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
            'opt_in_status' => false
        );
        $customerJson = '{"id":"142","email_address":"newcusto@ebizmarts.com","first_name":"FirstName",'
            . '"last_name":"LastName","opt_in_status":false}';
        $operationData = array(
            'method' => 'PUT',
            'path' => '/ecommerce/stores/00ee7808cc513ee772f209d63c034f1f/customers/142',
            'operation_id' => 'storeid-1_CUS_2018-03-14-20-15-03-44361800_142',
            'body' => '{"id":"142","email_address":"newcusto@ebizmarts.com","first_name":"FirstName",'
                . '"last_name":"LastName","opt_in_status":false}'
        );

        $this->_customersApiMock = $this->_customersApiMock->setMethods(
            array(
                'getMailchimpStoreId', 'getMagentoStoreId', 'createEcommerceCustomersCollection', 'addSyncData',

                'getCustomersToSync', 'makeBatchId', 'makeCustomersNotSentCollection', 'setOptInStatusForStore',
                'getOptIn', 'getMailchimpEcommerceSyncDataModel', 'getOptInStatusForStore', 'getBatchMagentoStoreId',
                '_buildCustomerData', 'makePutBatchStructure', 'setMailchimpStoreId',
                'setMagentoStoreId', 'getCustomerResourceCollection', 'getSubscriberModel', 'getHelper',
                'isSubscribed', 'makePatchBatchStructure', 'incrementCounterSentPerBatch','sendMailchimpTags'
            )
        )->getMock();

        $customerCollectionResourceMock = $this
            ->getMockBuilder(Ebizmarts_MailChimp_Model_Resource_Ecommercesyncdata_Customers_Collection::class)
            ->disableOriginalConstructor()->setMethods(
                array('setMailchimpStoreId', 'setStoreId')
            )->getMock();

        $customerCollectionMock = $this->getMockBuilder(Mage_Customer_Model_Resource_Customer_Collection::class)
            ->disableOriginalConstructor()->setMethods(array('getIterator'))->getMock();

        $customerMock = $this->getMockBuilder(Mage_Customer_Model_Customer::class)
            ->disableOriginalConstructor()->setMethods(array('getId', 'getEmail'))->getMock();

        $subscriberMock = $this->getMockBuilder(Mage_Newsletter_Model_Subscriber::class)
            ->disableOriginalConstructor()->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()->setMethods(array('getGeneralList'))->getMock();

        $syncDataItemMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Ecommercesyncdata::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getId', 'getMailchimpSyncedFlag', 'getEcommerceSyncDataItem'))
            ->getMock();

        $customerArray = array($customerMock);

        $this->_customersApiMock->expects($this->once())->method('getMailchimpStoreId')->willReturn($mailchimpStoreId);
        $this->_customersApiMock->expects($this->once())->method('getMagentoStoreId')->willReturn($storeId);
        $this->_customersApiMock->expects($this->once())->method('createEcommerceCustomersCollection')
            ->willReturn($customerCollectionResourceMock);

        $customerCollectionResourceMock->expects($this->once())->method('setMailchimpStoreId')->with($mailchimpStoreId);
        $customerCollectionResourceMock->expects($this->once())->method('setStoreId')->with($storeId);

        $this->_customersApiMock->expects($this->once())->method('setMailchimpStoreId')->with($mailchimpStoreId);
        $this->_customersApiMock->expects($this->once())->method('setMagentoStoreId')->with($storeId);
        $this->_customersApiMock->expects($this->once())->method('getHelper')->willReturn($helperMock);

        $this->_customersApiMock->expects($this->once())->method('getCustomersToSync')->willReturn($customerIds);
        $this->_customersApiMock->expects($this->once())->method('makeCustomersNotSentCollection')
            ->with($customerIds)->willReturn($customerCollectionMock);
        $this->_customersApiMock->expects($this->once())->method('makeBatchId');
        $this->_customersApiMock->expects($this->once())->method('getBatchMagentoStoreId')->willReturn($storeId);
        $this->_customersApiMock->expects($this->once())->method('setOptInStatusForStore')->with($optInStatus);
        $this->_customersApiMock->expects($this->once())->method('getOptin')->with($storeId)->willReturn($optInStatus);

        $this->_customersApiMock->expects($this->once())->method('getSubscriberModel')->willReturn($subscriberMock);
        $helperMock->expects($this->once())->method('getGeneralList')->with($storeId)->willReturn($listId);

        $customerCollectionMock->expects($this->once())->method('getIterator')
            ->willReturn(new ArrayIterator($customerArray));

        $this->_customersApiMock->expects($this->once())->method('_buildCustomerData')->with($customerMock)
            ->willReturn($customerData);

        $this->_customersApiMock->expects($this->once())->method('isSubscribed')
            ->with($subscriberMock, $customerMock)->willReturn($isSubscribed);

        $this->_customersApiMock->expects($this->once())->method('getMailchimpEcommerceSyncDataModel')
            ->willReturn($syncDataItemMock);

        $customerMock->expects($this->exactly(2))->method('getId')
            ->willReturnOnConsecutiveCalls($customerId, $customerId);

        $syncDataItemMock->expects($this->once())->method('getEcommerceSyncDataItem')
            ->with($customerId, Ebizmarts_MailChimp_Model_Config::IS_CUSTOMER, $mailchimpStoreId)
            ->willReturnSelf();

        $this->_customersApiMock->expects($this->once())->method('incrementCounterSentPerBatch')
            ->with($syncDataItemMock, $helperMock)->willReturnSelf();

        $this->_customersApiMock->expects($this->once())->method('makePutBatchStructure')
            ->with($customerJson, $customerMock)->willReturn($operationData);

        $this->_customersApiMock->expects($this->once())->method('addSyncData')
            ->with($customerId);

        $this->_customersApiMock->expects($this->once())->method('getOptInStatusForStore')->willReturn($optInStatus);

        $customerMock->expects($this->any())->method('getEmail')->willReturn($customerEmail);



        $rtnArray[0] = $operationData;
        $rtnArray[1] = $patchBatchData;

        $this->_customersApiMock
            ->expects($this->once())
            ->method('sendMailchimpTags')
            ->with(
                $storeId,
                $syncDataItemMock,
                $subscriberMock,
                $customerMock,
                $listId,
                1,
                array(0 => $operationData)
            )
            ->willReturn(array(0 => $rtnArray, 1 => 2));

        $return = $this->_customersApiMock->createBatchJson();

        $this->assertEquals($operationData['method'], $return[0]['method']);
        $this->assertEquals($operationData['path'], $return[0]['path']);
        $this->assertEquals($operationData['operation_id'], $return[0]['operation_id']);
        $this->assertEquals($operationData['body'], $return[0]['body']);
    }

    public function testCreateBatchJsonOptInTrueCustomerNotSubscribed()
    {
        $mailchimpStoreId = 'a1s2d3f4g5h6j7k8l9n0';
        $customerEmail = 'brian@ebizmarts.com';
        $storeId = 1;
        $optInStatus = true;
        $customerId = 142;
        $customerIds = array($customerId);
        $listId = "e4ef38998b";
        $isSubscribed = false;

        $patchBatchData = array(
            'method' => 'PATCH',
            'path' => '/lists/e4ef38998b/members/45c3ddfc868517aefd34ba8f122ad600',
            'operation_id' => '_SUB_45c3ddfc868517aefd34ba8f122ad600',
            'body' => '{"merge_fields":{"WEBSITE":"1","STOREID":"1","STORENAME":"Default Store View","FNAME":"test",
            "LNAME":"test","CGROUP":"General","CREATEDAT":"2019-07-25T07:05:34+13:00","GENDER":"Male",
            "CUSBRAND":"brand pref."}}'
        );

        $mergeFields = array(
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
            'opt_in_status' => false
        );
        $customerJson = '{"id":"142","email_address":"newcusto@ebizmarts.com","first_name":"FirstName",'
            . '"last_name":"LastName","opt_in_status":false}';
        $operationData = array(
            'method' => 'PUT',
            'path' => '/ecommerce/stores/00ee7808cc513ee772f209d63c034f1f/customers/142',
            'operation_id' => 'storeid-1_CUS_2018-03-14-20-15-03-44361800_142',
            'body' => '{"id":"142","email_address":"newcusto@ebizmarts.com","first_name":"FirstName",'
                . '"last_name":"LastName","opt_in_status":false}'
        );

        $this->_customersApiMock = $this->_customersApiMock->setMethods(
            array(
                'getMailchimpStoreId', 'getMagentoStoreId', 'createEcommerceCustomersCollection','addSyncData',
                'getCustomersToSync', 'makeBatchId', 'makeCustomersNotSentCollection', 'setOptInStatusForStore',
                'getOptIn', 'getOptInStatusForStore', 'getBatchMagentoStoreId', '_buildCustomerData',
                'makePutBatchStructure', 'setMailchimpStoreId', 'setMagentoStoreId',
                'getCustomerResourceCollection', 'getSubscriberModel', 'getHelper',
                'isSubscribed', 'makePatchBatchStructure', 'incrementCounterSentPerBatch',
                'makeMailchimpTagsBatchStructure', 'getMailchimpEcommerceSyncDataModel'
            )
        )->getMock();

        $customerCollectionResourceMock = $this
            ->getMockBuilder(Ebizmarts_MailChimp_Model_Resource_Ecommercesyncdata_Customers_Collection::class)
            ->disableOriginalConstructor()->setMethods(
                array('setMailchimpStoreId', 'setStoreId')
            )->getMock();

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
            ->setMethods(array('getGeneralList'))
            ->getMock();

        $syncDataItemMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Ecommercesyncdata::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getId', 'getMailchimpSyncedFlag', 'getEcommerceSyncDataItem'))
            ->getMock();

        $customerArray = array($customerMock);

        $this->_customersApiMock->expects($this->once())->method('getMailchimpStoreId')->willReturn($mailchimpStoreId);
        $this->_customersApiMock->expects($this->once())->method('getMagentoStoreId')->willReturn($storeId);
        $this->_customersApiMock->expects($this->once())->method('createEcommerceCustomersCollection')
            ->willReturn($customerCollectionResourceMock);

        $customerCollectionResourceMock->expects($this->once())->method('setMailchimpStoreId')->with($mailchimpStoreId);
        $customerCollectionResourceMock->expects($this->once())->method('setStoreId')->with($storeId);

        $this->_customersApiMock->expects($this->once())->method('setMailchimpStoreId')->with($mailchimpStoreId);
        $this->_customersApiMock->expects($this->once())->method('setMagentoStoreId')->with($storeId);
        $this->_customersApiMock->expects($this->once())->method('getHelper')->willReturn($helperMock);
        $this->_customersApiMock->expects($this->once())
            ->method('getCustomersToSync')
            ->willReturn($customerIds);
        $this->_customersApiMock->expects($this->once())
            ->method('makeCustomersNotSentCollection')
            ->with($customerIds)
            ->willReturn($customerCollectionMock);
        $this->_customersApiMock->expects($this->once())->method('makeBatchId');
        $this->_customersApiMock->expects($this->once())->method('getBatchMagentoStoreId')->willReturn($storeId);
        $this->_customersApiMock->expects($this->once())->method('setOptInStatusForStore')->with($optInStatus);
        $this->_customersApiMock->expects($this->once())->method('getOptin')->with($storeId)->willReturn($optInStatus);

        $this->_customersApiMock->expects($this->once())->method('getSubscriberModel')->willReturn($subscriberMock);

        $helperMock
            ->expects($this->once())
            ->method('getGeneralList')
            ->with($storeId)
            ->willReturn($listId);

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
            ->willReturn($isSubscribed);
        $this->_customersApiMock
            ->expects($this->once())
            ->method('getMailchimpEcommerceSyncDataModel')
            ->willReturn($syncDataItemMock);
        $this->_customersApiMock
            ->expects($this->once())
            ->method('incrementCounterSentPerBatch')
            ->with($syncDataItemMock, $helperMock)
            ->willReturnSelf();

        $this->_customersApiMock
            ->expects($this->once())
            ->method('getMailchimpEcommerceSyncDataModel')
            ->willReturn($syncDataItemMock);

        $this->_customersApiMock
            ->expects($this->once())
            ->method('addSyncData')
            ->with($customerId);

        $this->_customersApiMock
            ->expects($this->once())
            ->method('getOptInStatusForStore')
            ->willReturn($optInStatus);

        $customerMock->expects($this->exactly(2))
            ->method('getId')
            ->willReturnOnConsecutiveCalls(
                $customerId,
                $customerId
            );

        $customerMock->expects($this->once())->method('getEmail')->willReturn($customerEmail);

        $syncDataItemMock->expects($this->once())
            ->method('getEcommerceSyncDataItem')
            ->with($customerId, Ebizmarts_MailChimp_Model_Config::IS_CUSTOMER, $mailchimpStoreId)
            ->willReturn($syncDataItemMock);

        $subscriberMock
            ->expects($this->once())
            ->method('subscribe')
            ->with($customerEmail)
            ->willReturnSelf();

        $return = $this->_customersApiMock->createBatchJson();

        /*$this->assertEquals($operationData['method'], $return[0]['method']);
        $this->assertEquals($operationData['path'], $return[0]['path']);
        $this->assertEquals($operationData['operation_id'], $return[0]['operation_id']);
        $this->assertEquals($operationData['body'], $return[0]['body']);*/
    }

    public function testMakeCustomersNotSentCollectionTotal()
    {
        $customerId = 142;
        $customerIds = array($customerId);

        $this->_customersApiMock = $this->_customersApiMock->setMethods(
            array(
                'getCustomerResourceCollection',
                'joinDefaultBillingAddress'
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

        $collectionFrontEnd = $this->_customersApiMock->makeCustomersNotSentCollection($customerIds);

        $this->assertInstanceOf("Mage_Customer_Model_Resource_Customer_Collection", $collectionFrontEnd);
    }
}
