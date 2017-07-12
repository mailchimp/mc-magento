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
        $this->customersApiMock = $this->customersApiMock->setMethods(
            array(
                'makeBatchId',
                'joinMailchimpSyncData',
                'makeCustomersNotSentCollection',
                'getOptin'
            )
        )
            ->getMock();

        $this->customersApiMock->expects($this->once())->method('makeBatchId')->willReturn('storeid-0_CUS_2017-05-18-14-45-54-38849500');
        $this->customersApiMock->expects($this->never())->method('buildProductDataRemoval');
        $this->customersApiMock->expects($this->once())->method('joinMailchimpSyncData');
        $this->customersApiMock->expects($this->once())->method('makeBatchId');
        $this->customersApiMock->expects($this->once())->method('getOptin')->with(1);

        $this->customersApiMock->expects($this->once())->method('makeCustomersNotSentCollection')
            ->willReturn(new Varien_Object());

        $batchArray = $this->customersApiMock->createBatchJson('dasds231231312', 1);
        $this->assertEquals(array(), $batchArray);
    }

    public function testMakeCustomersNotSentCollection()
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
        $dbSelectMock->expects($this->once())->method('group')->with('e.entity_id');
        $dbSelectMock->expects($this->once())->method('limit')->with(100);

        $customersResourceCollectionMock = $this->getMockBuilder(Mage_Customer_Model_Resource_Customer_Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $customersResourceCollectionMock->expects($this->exactly(2))->method('getSelect')->willReturn($dbSelectMock);
        $customersResourceCollectionMock->expects($this->once())->method('addNameToSelect');

        $this->customersApiMock->expects($this->once())->method('getCustomerResourceCollection')
            ->willReturn($customersResourceCollectionMock);
        $this->customersApiMock->expects($this->once())->method('getBatchMagentoStoreId')->willReturn(1);
        $this->customersApiMock->expects($this->once())->method('joinDefaultBillingAddress');
        $this->customersApiMock->expects($this->once())->method('joinSalesData');
        $this->customersApiMock->expects($this->once())->method('getBatchLimitFromConfig')->willReturn(100);

        $collection = $this->customersApiMock->makeCustomersNotSentCollection();

        $this->assertInstanceOf("Mage_Customer_Model_Resource_Customer_Collection", $collection);
    }
}