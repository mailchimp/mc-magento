<?php
/**
 * Created by Ebizmarts
 * Date: 1/18/18
 * Time: 3:49 PM
 */

class Ebizmarts_MailChimp_Block_Customer_Newsletter_IndexTest extends PHPUnit_Framework_TestCase
{

    /**
     * @var \Ebizmarts_MailChimp_Block_Customer_Newsletter_Index $_block
     */
    private $_block;

    /**
     * @var \Ebizmarts_MailChimp_Block_Customer_Newsletter_Index
     */
    private $_indexMock;

    public function setUp()
    {
        $app = Mage::app('default');
        $layout = $app->getLayout();
        $this->_block = new Ebizmarts_MailChimp_Block_Customer_Newsletter_Index;
        $this->_indexMock = $this->getMockBuilder(Ebizmarts_MailChimp_Block_Customer_Newsletter_Index::class);

        /* We are required to set layouts before we can do anything with blocks */
        $this->_block->setLayout($layout);
    }

    public function testGetInterest()
    {
        $interest = array();
        $emailAddress = 'address@email.com';
        $subscriberId = 1;
        $customerId = 2;
        $storeId = 1;

        $indexMock = $this->_indexMock
            ->disableOriginalConstructor()
            ->setMethods(array('getSubscriberModel', '_getEmail', 'getMailChimpHelper', 'getCustomerSession'))
            ->getMock();


        $subscriberMock = $this->getMockBuilder(Mage_Newsletter_Model_Subscriber::class)
            ->disableOriginalConstructor()
            ->setMethods(array('loadByEmail', 'getSubscriberId', 'getStoreId'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('isAdmin', 'getInterestGroups'))
            ->getMock();

        $customerSessionMock = $this->getMockBuilder(Mage_Customer_Model_Session::class)
            ->disableOriginalConstructor()
            ->setMethods(array('isLoggedIn', 'getCustomer'))
            ->getMock();

        $customerMock = $this->getMockBuilder(Mage_Customer_Model_Customer::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getId', 'getStoreId'))
            ->getMock();

        $indexMock->expects($this->once())->method('getSubscriberModel')->willReturn($subscriberMock);
        $indexMock->expects($this->once())->method('_getEmail')->willReturn($emailAddress);

        $subscriberMock->expects($this->once())->method('loadByEmail')->with($emailAddress);

        $indexMock->expects($this->once())->method('getMailChimpHelper')->willReturn($helperMock);
        $indexMock->expects($this->once())->method('getCustomerSession')->willReturn($customerSessionMock);

        $helperMock->expects($this->once())->method('isAdmin')->willReturn(false);

        $customerSessionMock->expects($this->once())->method('isLoggedIn')->willReturn(true);
        $customerSessionMock->expects($this->once())->method('getCustomer')->willReturn($customerMock);

        $customerMock->expects($this->once())->method('getId')->willReturn($customerId);

        $subscriberMock->expects($this->once())->method('getStoreId')->willReturn(null);

        $customerMock->expects($this->once())->method('getStoreId')->willReturn($storeId);

        $subscriberMock->expects($this->once())->method('getSubscriberId')->willReturn($subscriberId);

        $helperMock->expects($this->once())->method('getInterestGroups')->with($customerId, $subscriberId, $storeId)->willReturn($interest);

        $indexMock->getInterest();
    }

}
