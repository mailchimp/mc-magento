<?php

class Ebizmarts_MailChimp_Helper_DataTest extends PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        Mage::app('default');
    }

    public function testGetLastDateOfPurchase()
    {
        /**
 * @var \Ebizmarts_MailChimp_Helper_Data $helperMock 
*/
        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getOrderCollectionByCustomerEmail'))
            ->getMock();

        $helperMock->expects($this->once())->method('getOrderCollectionByCustomerEmail')->with("john@example.com")
            ->willReturn(array());

        $this->assertNull($helperMock->getLastDateOfPurchase("john@example.com"));
    }

    public function testCustomMergeFieldAlreadyExists()
    {
        /**
 * @var \Ebizmarts_MailChimp_Helper_Data $helperMock 
*/
        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getCustomMergeFields'))
            ->getMock();

        $helperMock->expects($this->once())->method('getCustomMergeFields')->with(0, "store")
            ->willReturn(
                array(
                array(
                "value" => "FNAME"
                )
                )
            );

        $this->assertTrue($helperMock->customMergeFieldAlreadyExists("FNAME", 0, "store"));
    }

    public function testDeleteStore()
    {
        $scopeId = 1;
        $scope = 'stores';
        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getMCStoreId', 'getApiStores', 'getGeneralList', 'deleteCurrentWebhook', 'deleteLocalMCStoreData', 'logError'))
            ->getMock();
        $apiStoresMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Stores::class)
            ->disableOriginalConstructor()
            ->getMock();

        $helperMock->expects($this->once())->method('getMCStoreId')->with($scopeId, $scope)->willReturn('a18a1a8a1aa7aja1a');
        $helperMock->expects($this->once())->method('getApiStores')->willReturn($apiStoresMock);
        $helperMock->expects($this->once())->method('logError')->with('Error message', $scopeId, $scope);
        $helperMock->expects($this->once())->method('getGeneralList')->with($scopeId, $scope)->willReturn('listId');
        $helperMock->expects($this->once())->method('deleteCurrentWebhook')->with($scopeId, $scope, 'listId');
        $helperMock->expects($this->once())->method('deleteLocalMCStoreData')->with($scopeId, $scope);

        $helperMock->deleteStore($scopeId, $scope);
    }
}