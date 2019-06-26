<?php
/**
 * Created by Ebizmarts
 * Date: 1/18/18
 * Time: 3:49 PM
 */

class Ebizmarts_MailChimp_Block_Adminhtml_Sales_Order_Grid_Renderer_MailchimpOrderTest extends PHPUnit_Framework_TestCase
{

    /**
     * @var \Ebizmarts_MailChimp_Block_Adminhtml_Sales_Order_Grid_Renderer_MailchimpOrder $_block
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
        $this->_block = new Ebizmarts_MailChimp_Block_Adminhtml_Sales_Order_Grid_Renderer_MailchimpOrder;
        $this->_orderMock = $this->getMockBuilder(Mage_Sales_Model_Order::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getStoreId', 'getEntityId', 'getCreatedAt'))
            ->getMock();

        /* We are required to set layouts before we can do anything with blocks */
        $this->_block->setLayout($layout);
    }

    /**
     * @param array $syncedData
     * @dataProvider renderDataProvider
     */

    public function testRender($syncedData)
    {
        $orderId = $syncedData['order_id'];
        $mailchimpStoreId = '5axx998994cxxxx47e6b3b5dxxxx26e2';
        $storeId = 1;
        $status = $syncedData['synced_status'];
        $orderDate = $syncedData['order_date'];
        $firstDate = '2018-09-26 00:00:00';

        if ($status === 1) {
            $assertStatus = '<div style ="color:green">Yes</div>';
        } elseif ($orderId && $status === null) {
            $assertStatus = '<div style ="color:#ed6502">Processing</div>';
        } elseif ($status === null && $orderDate > $firstDate) {
            $assertStatus = '<div style ="color:mediumblue">In queue</div>';
        } else {
            $assertStatus = '<div style ="color:red">No</div>';
        }

        $orderMock = $this->_orderMock;

        $blockMock = $this->getMockBuilder(Ebizmarts_MailChimp_Block_Adminhtml_Sales_Order_Grid_Renderer_MailchimpOrder::class)
            ->disableOriginalConstructor()
            ->setMethods(array('makeHelper', 'makeApiOrders'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getMCStoreId', 'isEcomSyncDataEnabled', 'getEcommerceFirstDate'))
            ->getMock();

        $modelMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Orders::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getSyncedOrder'))
            ->getMock();

        $blockMock->expects($this->once())->method('makeHelper')->willReturn($helperMock);
        $blockMock->expects($this->once())->method('makeApiOrders')->willReturn($modelMock);

        $orderMock->expects($this->once())->method('getStoreId')->willReturn($storeId);
        $orderMock->expects($this->once())->method('getEntityId')->willReturn($orderId);
        $orderMock->expects($this->once())->method('getCreatedAt')->willReturn($orderDate);

        $helperMock->expects($this->once())->method('getMCStoreId')->with($storeId)->willReturn($mailchimpStoreId);
        $helperMock->expects($this->once())->method('isEcomSyncDataEnabled')->with($storeId)->willReturn(true);
        $helperMock->expects($this->any())->method('getEcommerceFirstDate')->with($storeId)->willReturn($firstDate);

        $modelMock->expects($this->once())->method('getSyncedOrder')->with($orderId, $mailchimpStoreId)->willReturn($syncedData);

        $result = $blockMock->render($orderMock);

        $this->assertEquals($assertStatus, $result);
    }

    public function renderDataProvider()
    {

        return array(
            array(array('synced_status' => 1, 'order_id' => 1, 'order_date' => '2018-09-28 18:52:38')),
            array(array('synced_status' => null, 'order_id' => 1, 'order_date' => '2018-09-26 18:52:38')),
            array(array('synced_status' => null, 'order_id' => null, 'order_date' => '2018-09-21 18:52:38')),
            array(array('synced_status' => null, 'order_id' => null, 'order_date' => '2018-09-27 18:52:38')),
            array(array('synced_status' => 0, 'order_id' => 1, 'order_date' => '2018-09-28 18:52:38'))
        );
    }
}
