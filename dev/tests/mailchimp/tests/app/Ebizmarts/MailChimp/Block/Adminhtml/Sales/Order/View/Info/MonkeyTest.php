<?php

class Ebizmarts_MailChimp_Block_Adminhtml_Sales_Order_View_Info_MonkeyTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var \Ebizmarts_MailChimp_Block_Adminhtml_Sales_Order_View_Info_Monkey $_block
     */
    private $_block;
    /**
     * @var \Mage_Sales_Model_Order $_orderMock
     */
    private $_orderMock;


    public function setUp()
    {
        $app = Mage::app('default');
        $layout = $app->getLayout();
        $this->_block = new Ebizmarts_MailChimp_Block_Adminhtml_Sales_Order_View_Info_Monkey;
        $this->_orderMock = $this->getMockBuilder(Mage_Sales_Model_Order::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getStoreId', 'getMailchimpAbandonedcartFlag', 'getMailchimpCampaignId'))
            ->getMock();
        if (!Mage::registry('current_order')) {
            Mage::register('current_order', $this->_orderMock);
        }
        /* We are required to set layouts before we can do anything with blocks */
        $this->_block->setLayout($layout);
    }

    public function testIsReferred()
    {
        /**
         * @var \Ebizmarts_MailChimp_Block_Adminhtml_Sales_Order_View_Info_Monkey $monkeyBlock
         */
        $monkeyBlockMock = $this->getMockBuilder(Ebizmarts_MailChimp_Block_Adminhtml_Sales_Order_View_Info_Monkey::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getMailChimpHelper', 'getCampaignId', 'getCurrentOrder'))
            ->getMock();
        $orderMock = $this->_orderMock;

        $monkeyBlockMock->expects($this->once())->method('getCurrentOrder')->willReturn($orderMock);
        $orderMock->expects($this->exactly(1))->method('getMailchimpAbandonedcartFlag')->willReturn(false);
        $orderMock->expects($this->exactly(1))->method('getMailchimpCampaignId')->willReturn(true);

        $monkeyBlockMock->isReferred();
    }


    public function testIsDataAvailable()
    {

        $campaignName = 'campaignName';
        /**
         * @var \Ebizmarts_MailChimp_Block_Adminhtml_Sales_Order_View_Info_Monkey $monkeyBlock
         */
        $monkeyBlockMock = $this->getMockBuilder(Ebizmarts_MailChimp_Block_Adminhtml_Sales_Order_View_Info_Monkey::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getCampaignName'))
            ->getMock();

        $monkeyBlockMock->expects($this->once())->method('getCampaignName')->willReturn($campaignName);

        $result = $monkeyBlockMock->isDataAvailable();

        $this->assertEquals($result, true);
    }

    public function testGetCampaignName()
    {
        $campaignId = '1111111';
        $campaignName = 'campaignName';
        $storeId = 1;

        $monkeyBlockMock = $this->getMockBuilder(Ebizmarts_MailChimp_Block_Adminhtml_Sales_Order_View_Info_Monkey::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getCampaignId', 'getCurrentOrder', 'getMailChimpHelper'))
            ->getMock();
        /**
         * @var \Ebizmarts_MailChimp_Helper_Data $helperMock
         */
        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getMailChimpCampaignNameById', 'isEcomSyncDataEnabled'))
            ->getMock();

        $orderMock = $this->_orderMock;

        $monkeyBlockMock->expects($this->once())->method('getCampaignId')->willReturn($campaignId);
        $monkeyBlockMock->expects($this->once())->method('getCurrentOrder')->willReturn($orderMock);

        $orderMock->expects($this->once())->method('getStoreId')->willReturn($storeId);

        $monkeyBlockMock->expects($this->once())->method('getMailChimpHelper')->willReturn($helperMock);

        $helperMock->expects($this->once())->method('isEcomSyncDataEnabled')->with($storeId)->willReturn(true);
        $helperMock->expects($this->once())->method('getMailChimpCampaignNameById')->with($campaignId, $storeId)->willReturn($campaignName);

        $result = $monkeyBlockMock->getCampaignName();

        $this->assertEquals($result, $campaignName);
    }
}
