<?php

class Ebizmarts_MailChimp_Model_Api_CustomersTest extends PHPUnit_Framework_TestCase
{
    /**
 * @var Ebizmarts_MailChimp_Model_Api_Customers
*/
    private $customersApiMock;

    public function setUp()
    {
        Mage::app('default');

        $this->customersApiMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Customers::class);
    }

    public function tearDown()
    {
        $this->customersApiMock = null;
    }

    public function testGetOptInYes()
    {
        $this->customersApiMock = $this->customersApiMock->setMethods(array('isEcommerceCustomerOptInConfigEnabled'))
            ->getMock();

        $this->customersApiMock->expects($this->once())->method('isEcommerceCustomerOptInConfigEnabled')->with(1)->willReturn('1');

        $this->assertTrue($this->customersApiMock->getOptin(1));
    }

    public function testGetOptInNo()
    {
        $this->customersApiMock = $this->customersApiMock->setMethods(array('isEcommerceCustomerOptInConfigEnabled'))
            ->getMock();

        $this->customersApiMock->expects($this->once())->method('isEcommerceCustomerOptInConfigEnabled')->with(1)->willReturn('0');

        $this->assertFalse($this->customersApiMock->getOptin(1));
    }

    public function testGetOptInNoDefaultStore()
    {
        $this->customersApiMock = $this->customersApiMock->setMethods(array('isEcommerceCustomerOptInConfigEnabled'))
            ->getMock();

        $this->customersApiMock->expects($this->once())->method('isEcommerceCustomerOptInConfigEnabled')->with(0)->willReturn('0');

        $this->assertFalse($this->customersApiMock->getOptin(0));

        $this->assertFalse($this->customersApiMock->getOptin(0));
    }

    public function testCreateBatchJson()
    {
        $mailchimpStoreId = 'a1s2d3f4g5h6j7k8l9n0';
        $customerEmail = 'keller@ebizmarts.com';
        $subscriberId = 1;
        $storeId = 1;
        $optInStatus = true;
        $customerId = 142;
        $customerIds = array($customerId);

        $customerData = array(
            'id' => '142',
            'email_address' => 'newcusto@ebizmarts.com',
            'first_name' => 'FirstName',
            'last_name' => 'LastName',
            'opt_in_status' => false,
            'orders_count' => 0,
            'total_spent' => 0
        );
        $customerJson = '{"id":"142","email_address":"newcusto@ebizmarts.com","first_name":"FirstName","last_name":"LastName","opt_in_status":false,"orders_count":0,"total_spent":0}';
        $operationData = array(
            'method' => 'PUT',
            'path' => '/ecommerce/stores/00ee7808cc513ee772f209d63c034f1f/customers/142',
            'operation_id' => 'storeid-1_CUS_2018-03-14-20-15-03-44361800_142',
            'body' => '{"id":"142","email_address":"newcusto@ebizmarts.com","first_name":"FirstName","last_name":"LastName","opt_in_status":false,"orders_count":0,"total_spent":0}'
        );

        $this->customersApiMock = $this->customersApiMock->setMethods(
            array('getCustomersToSync', 'makeBatchId', 'makeCustomersNotSentCollection', 'getOptin',
                'getBatchMagentoStoreId', '_buildCustomerData', 'makePutBatchStructure',
                '_updateSyncData', 'setMailchimpStoreId', 'setMagentoStoreId', 'getCustomerResourceCollection', 'getSubscriberModel'
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

        $subscribersMailchimpMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Subscriber::class)
                ->disableOriginalConstructor()
                ->setMethods(array('getSubscriberId'))
                ->getMock();

        $customerArray = array($customerMock);

        $this->customersApiMock->expects($this->once())->method('setMailchimpStoreId')->with($mailchimpStoreId);
        $this->customersApiMock->expects($this->once())->method('setMagentoStoreId')->with($storeId);
        $this->customersApiMock->expects($this->once())->method('getCustomersToSync')
            ->willReturn($customerIds);
        $this->customersApiMock->expects($this->once())->method('makeCustomersNotSentCollection')
            ->with($customerIds)
            ->willReturn($customerCollectionMock);
        $this->customersApiMock->expects($this->once())->method('makeBatchId');
        $this->customersApiMock->expects($this->once())->method('getBatchMagentoStoreId')->willReturn($storeId);
        $this->customersApiMock->expects($this->once())->method('getOptin')->with($storeId)->willReturn($optInStatus);
        $this->customersApiMock->expects($this->once())->method('getSubscriberModel')->willReturn($subscriberMock);
        $customerCollectionMock->expects($this->once())->method('getIterator')->willReturn(new ArrayIterator($customerArray));
        $this->customersApiMock->expects($this->once())->method('_buildCustomerData')->with($customerMock)->willReturn($customerData);
        $this->customersApiMock->expects($this->once())->method('makePutBatchStructure')->with($customerJson)->willReturn($operationData);
        $subscriberMock->expects($this->once())->method('loadByEmail')->with($customerEmail)->willReturn($subscribersMailchimpMock);
        $subscribersMailchimpMock->expects($this->once())->method('getSubscriberId')->willReturn($subscriberId);
        $customerMock->expects($this->once())->method('getId')->willReturn($customerId);
        $customerMock->expects($this->any())->method('getEmail')->willReturn($customerEmail);
        $this->customersApiMock->expects($this->once())->method('_updateSyncData')->with($customerId, $mailchimpStoreId);

        $return = $this->customersApiMock->createBatchJson($mailchimpStoreId, $storeId);
        $this->assertEquals($operationData['method'], $return[0]['method']);
        $this->assertEquals($operationData['path'], $return[0]['path']);
        $this->assertEquals($operationData['operation_id'], $return[0]['operation_id']);
        $this->assertEquals($operationData['body'], $return[0]['body']);
    }

    public function testMakeCustomersNotSentCollectionTotal()
    {
        $customerId = 142;
        $customerIds = array($customerId);

        $this->customersApiMock = $this->customersApiMock->setMethods(
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

        $customersResourceCollectionMock = $this->getMockBuilder(Mage_Customer_Model_Resource_Customer_Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $customersResourceCollectionMock->expects($this->once())->method('getSelect')->willReturn($dbSelectMock);
        $customersResourceCollectionMock->expects($this->exactly(1))->method('addNameToSelect');

        $this->customersApiMock->expects($this->once())->method('getCustomerResourceCollection')
            ->willReturn($customersResourceCollectionMock);
        $this->customersApiMock->expects($this->once())->method('joinDefaultBillingAddress');
        $this->customersApiMock->expects($this->once())->method('joinSalesData');

        $collectionFrontEnd = $this->customersApiMock->makeCustomersNotSentCollection($customerIds);

        $this->assertInstanceOf("Mage_Customer_Model_Resource_Customer_Collection", $collectionFrontEnd);
    }
}
