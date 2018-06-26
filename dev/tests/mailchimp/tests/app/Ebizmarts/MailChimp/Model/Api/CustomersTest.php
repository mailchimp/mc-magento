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
        $storeId = 1;
        $optInStatus = true;
        $customerId = 142;
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

        $collectionIdsFrontEnd = $array = array (7, 5, 4);
        $collectionIdsAdmin = $array = array (7, 9, 8);
        $collectionIds = $array = array (
            0 => 7,
            1 => 5,
            2 => 4,
            4 => 9,
            5 => 8);

        $this->customersApiMock = $this->customersApiMock->setMethods(
            array('makeBatchId', 'joinMailchimpSyncData', 'makeCustomersNotSentCollection', 'makeAdminCustomersNotSentCollection', 'getOptin',
                'getBatchMagentoStoreId', '_buildCustomerData', 'makePutBatchStructure',
                '_updateSyncData', 'setMailchimpStoreId', 'setMagentoStoreId', 'getCustomerResourceCollection'
            )
        )
            ->getMock();

        $customerFrontEndCollectionMock = $this->getMockBuilder(Mage_Customer_Model_Resource_Customer_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getAllIds'))
            ->getMock();

        $customerAdminCollectionMock = $this->getMockBuilder(Mage_Customer_Model_Resource_Customer_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getAllIds'))
            ->getMock();

        $customerFinalCollectionMock = $this->getMockBuilder(Mage_Customer_Model_Resource_Customer_Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $customerMock = $this->getMockBuilder(Mage_Customer_Model_Customer::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getId'))
            ->getMock();

        $customersResourceCollectionMock = $this->getMockBuilder(Mage_Customer_Model_Resource_Customer_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('addAttributeToSelect', 'addFieldToFilter', 'getSelect'))
            ->getMock();

        $customerArray = array($customerMock);

        $this->customersApiMock->expects($this->once())->method('setMailchimpStoreId')->with($mailchimpStoreId);
        $this->customersApiMock->expects($this->once())->method('setMagentoStoreId')->with($storeId);

        $this->customersApiMock->expects($this->once())->method('makeCustomersNotSentCollection')
            ->willReturn($customerFrontEndCollectionMock);

        $this->customersApiMock->expects($this->once())->method('makeAdminCustomersNotSentCollection')
            ->willReturn($customerAdminCollectionMock);

        $customerFrontEndCollectionMock->expects($this->once())->method('getAllIds')
            ->willReturn($collectionIdsFrontEnd);

        $customerAdminCollectionMock->expects($this->once())->method('getAllIds')
            ->willReturn($collectionIdsAdmin);

        $this->customersApiMock->expects($this->once())->method('getCustomerResourceCollection')
            ->willReturn($customersResourceCollectionMock);

        $customersResourceCollectionMock->expects($this->once())->method('addFieldToFilter')->with('entity_id', array('in' => $collectionIds))->willReturnSelf();
        $customersResourceCollectionMock->expects($this->once())->method('addAttributeToSelect')->with('*')->willReturn($customerFinalCollectionMock);

        $this->customersApiMock->expects($this->once())->method('makeBatchId');

        $this->customersApiMock->expects($this->once())->method('getBatchMagentoStoreId')->willReturn($storeId);
        $this->customersApiMock->expects($this->once())->method('getOptin')->with($storeId)->willReturn($optInStatus);

        $this->customersApiMock->expects($this->once())->method('joinMailchimpSyncData');

        $customerFinalCollectionMock->expects($this->once())->method('getIterator')->willReturn(new ArrayIterator($customerArray));

        $this->customersApiMock->expects($this->once())->method('_buildCustomerData')->with($customerMock)->willReturn($customerData);
        $this->customersApiMock->expects($this->once())->method('makePutBatchStructure')->with($customerJson)->willReturn($operationData);

        $customerMock->expects($this->once())->method('getId')->willReturn($customerId);

        $this->customersApiMock->expects($this->once())->method('_updateSyncData')->with($customerId, $mailchimpStoreId);


        $return = $this->customersApiMock->createBatchJson($mailchimpStoreId, $storeId);
        $this->assertEquals($operationData['method'], $return[0]['method']);
        $this->assertEquals($operationData['path'], $return[0]['path']);
        $this->assertEquals($operationData['operation_id'], $return[0]['operation_id']);
        $this->assertEquals($operationData['body'], $return[0]['body']);
    }

    public function testMakeCustomersNotSentCollectionTotal()
    {
        $this->customersApiMock = $this->customersApiMock->setMethods(
            array(
                'joinDefaultBillingAddress',
                'joinSalesData',
                'getBatchLimitFromConfig',
                'getBatchMagentoStoreId',
                'getCustomerResourceCollection'
            )
        )
            ->getMock();

        $dbSelectMock = $this->getMockBuilder(Varien_Db_Select::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbSelectMock->expects($this->exactly(2))->method('group')->with('e.entity_id');
        $dbSelectMock->expects($this->exactly(2))->method('limit')->with(100);

        $customersResourceCollectionMock = $this->getMockBuilder(Mage_Customer_Model_Resource_Customer_Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $customersResourceCollectionMock->expects($this->exactly(4))->method('getSelect')->willReturn($dbSelectMock);
        $customersResourceCollectionMock->expects($this->exactly(2))->method('addNameToSelect');

        $this->customersApiMock->expects($this->exactly(2))->method('getCustomerResourceCollection')
            ->willReturn($customersResourceCollectionMock);
        $this->customersApiMock->expects($this->exactly(2))->method('getBatchMagentoStoreId')->willReturn(1);
        $this->customersApiMock->expects($this->exactly(2))->method('joinDefaultBillingAddress');
        $this->customersApiMock->expects($this->exactly(2))->method('joinSalesData');
        $this->customersApiMock->expects($this->exactly(2))->method('getBatchLimitFromConfig')->willReturn(100);

        $collectionFrontEnd = $this->customersApiMock->makeCustomersNotSentCollection();
        $collectionAdmin = $this->customersApiMock->makeAdminCustomersNotSentCollection();

        $this->assertInstanceOf("Mage_Customer_Model_Resource_Customer_Collection", $collectionFrontEnd);
        $this->assertInstanceOf("Mage_Customer_Model_Resource_Customer_Collection", $collectionAdmin);
    }
}
