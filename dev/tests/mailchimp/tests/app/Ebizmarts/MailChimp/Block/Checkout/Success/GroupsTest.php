<?php
/**
 * Created by Ebizmarts
 * Date: 1/18/18
 * Time: 3:49 PM
 */

class Ebizmarts_MailChimp_Block_Checkout_Success_GroupsTest extends PHPUnit_Framework_TestCase
{

    /**
     * @var \Ebizmarts_MailChimp_Block_Checkout_Success_GroupsTest $_block
     */
    private $_block;

    /**
     * @var \Ebizmarts_MailChimp_Block_Checkout_Success_Groups
     */
    private $_groupsMock;

    public function setUp()
    {
        $app = Mage::app('default');
        $layout = $app->getLayout();
        $this->_block = new Ebizmarts_MailChimp_Block_Checkout_Success_Groups;
        $this->_groupsMock = $this->getMockBuilder(Ebizmarts_MailChimp_Block_Checkout_Success_Groups::class);

        /* We are required to set layouts before we can do anything with blocks */
        $this->_block->setLayout($layout);
    }

    public function testGetInterest()
    {
        $interest = array();
        $customerEmail = 'customer@email.com';
        $subscriberId = 1;
        $customerId = 2;
        $storeId = 1;

        $groupsMock = $this->_groupsMock
            ->disableOriginalConstructor()
            ->setMethods(array('getSubscriberModel', 'getSessionLastRealOrder', 'getElementHtml', 'getMailChimpHelper'))
            ->getMock();

        $orderMock = $this->getMockBuilder(Mage_Sales_Model_Order::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getCustomerEmail', 'getCustomerId', 'getStoreId'))
            ->getMock();

        $subscriberMock = $this->getMockBuilder(Mage_Newsletter_Model_Subscriber::class)
            ->disableOriginalConstructor()
            ->setMethods(array('loadByEmail', 'getSubscriberId'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getInterestGroups'))
            ->getMock();


        $groupsMock->expects($this->once())->method('getSubscriberModel')->willReturn($subscriberMock);
        $groupsMock->expects($this->once())->method('getSessionLastRealOrder')->willReturn($orderMock);

        $orderMock->expects($this->once())->method('getCustomerEmail')->willReturn($customerEmail);

        $subscriberMock->expects($this->once())->method('loadByEmail')->with($customerEmail);
        $subscriberMock->expects($this->once())->method('getSubscriberId')->willReturn($subscriberId);

        $orderMock->expects($this->once())->method('getCustomerId')->willReturn($customerId);
        $orderMock->expects($this->once())->method('getStoreId')->willReturn($storeId);

        $groupsMock->expects($this->once())->method('getMailChimpHelper')->willReturn($helperMock);

        $orderMock->expects($this->once())->method('getStoreId')->willReturn($storeId);

        $helperMock->expects($this->once())->method('getInterestGroups')->with($customerId, $subscriberId, $storeId)->willReturn($interest);

        $groupsMock->getInterest();
    }

}
