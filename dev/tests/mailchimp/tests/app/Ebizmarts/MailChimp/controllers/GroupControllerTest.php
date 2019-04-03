<?php

require_once BP . DS . 'app/code/community/Ebizmarts/MailChimp/controllers/GroupController.php';

class Ebizmarts_MailChimp_GroupControllerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Ebizmarts_MailChimp_GroupController $groupController
     */
    private $groupController;

    public function setUp()
    {
        Mage::app('default');
        $this->groupController = $this->getMockBuilder(Ebizmarts_MailChimp_GroupController::class);
    }

    public function tearDown()
    {
        $this->groupController = null;
    }

    public function testIndexAction()
    {
        $storeId = 1;
        $customerId = 1;
        $subscriberId = 1;
        $customerEmail = 'customer@email.com';
        $customerFirstName = "First Name";
        $customerLastName = "Last Name";
        $encodedGroupData = '{"bc15dbe6a5":{"d6b7541ee7":"d6b7541ee7"},"2a2f23d671":"36c250eeff"}';
        $params = array(
            'bc15dbe6a5' => array('d6b7541ee7' => 'd6b7541ee7'),
            '2a2f23d671' => '36c250eeff'
        );
        $currentDateTime = '2018-07-26 12:43:40';
        $successMessage = 'Thanks for sharing your interest with us.';

        $groupControllerMock = $this->groupController
            ->disableOriginalConstructor()
            ->setMethods(array('getSessionLastRealOrder', 'getCoreSession', 'getHelper', 'getRequest',
                'getInterestGroupModel', 'getSubscriberModel', 'getApiSubscriber', '_redirect',
                'getCurrentDateTime','__'))
            ->getMock();

        $requestMock = $this->getMockBuilder(Mage_Core_Controller_Request_Http::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getParams'))
            ->getMock();

        $orderMock = $this->getMockBuilder(Mage_Sales_Model_Order::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getStoreId', 'getCustomerEmail', 'getCustomerId', 'getCustomerFirstname', 'getCustomerLastname'))
            ->getMock();

        $subscriberMock = $this->getMockBuilder(Mage_Newsletter_Model_Subscriber::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getSubscriberId', 'setSubscriberEmail', 'setSubscriberFirstname',
                'setSubscriberLastname', 'subscribe', 'getSubscriberEmail', 'loadByEmail'))
            ->getMock();

        $coreSessionMock = $this->getMockBuilder(Mage_Core_Model_Session::class)
            ->disableOriginalConstructor()
            ->setMethods(array('addSuccess'))
            ->getMock();

        $interestGroupMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Interestgroup::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getByRelatedIdStoreId', 'setGroupdata', 'setSubscriberId', 'setCustomerId',
                'setStoreId', 'setUpdatedAt', 'save'))
            ->getMock();

        $apiSubscriberMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Subscribers::class)
            ->disableOriginalConstructor()
            ->setMethods(array('update'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('arrayEncode'))
            ->getMock();

        $groupControllerMock->expects($this->once())->method('getSessionLastRealOrder')->willReturn($orderMock);
        $groupControllerMock->expects($this->once())->method('getCoreSession')->willReturn($coreSessionMock);
        $groupControllerMock->expects($this->once())->method('getInterestGroupModel')->willReturn($interestGroupMock);
        $groupControllerMock->expects($this->once())->method('getRequest')->willReturn($requestMock);
        $groupControllerMock->expects($this->once())->method('getHelper')->willReturn($helperMock);

        $requestMock->expects($this->once())->method('getParams')->willReturn($params);

        $orderMock->expects($this->once())->method('getStoreId')->willReturn($storeId);
        $orderMock->expects($this->once())->method('getCustomerEmail')->willReturn($customerEmail);
        $orderMock->expects($this->once())->method('getCustomerId')->willReturn($customerId);
        $orderMock->expects($this->once())->method('getCustomerFirstname')->willReturn($customerFirstName);
        $orderMock->expects($this->once())->method('getCustomerLastname')->willReturn($customerLastName);

        $groupControllerMock->expects($this->once())->method('getSubscriberModel')->willReturn($subscriberMock);
        $groupControllerMock->expects($this->once())->method('getCurrentDateTime')->willReturn($currentDateTime);

        $subscriberMock->expects($this->once())->method('loadByEmail')->with($customerEmail)->willReturnSelf();
        $subscriberMock->expects($this->exactly(2))->method('getSubscriberId')
            ->willReturnOnConsecutiveCalls(null, $subscriberId);
        $subscriberMock->expects($this->once())->method('setSubscriberEmail')->with($customerEmail)->willReturnSelf();
        $subscriberMock->expects($this->once())->method('setSubscriberFirstname')->with($customerFirstName)->willReturnSelf();
        $subscriberMock->expects($this->once())->method('setSubscriberLastname')->with($customerLastName)->willReturnSelf();
        $subscriberMock->expects($this->once())->method('subscribe')->willReturnSelf();

        $interestGroupMock->expects($this->once())->method('getByRelatedIdStoreId')->with($customerId, $subscriberId, $storeId)->willReturnSelf();

        $helperMock->expects($this->once())->method('arrayEncode')->with($params)->willReturn($encodedGroupData);

        $interestGroupMock->expects($this->once())->method('setGroupdata')->with($encodedGroupData)->willReturnSelf();
        $interestGroupMock->expects($this->once())->method('setSubscriberId')->with($subscriberId)->willReturnSelf();
        $interestGroupMock->expects($this->once())->method('setCustomerId')->with($customerId)->willReturnSelf();
        $interestGroupMock->expects($this->once())->method('setStoreId')->with($storeId)->willReturnSelf();
        $interestGroupMock->expects($this->once())->method('setUpdatedAt')->with($currentDateTime)->willReturnSelf();
        $interestGroupMock->expects($this->once())->method('save')->willReturnSelf();

        $groupControllerMock->expects($this->once())->method('getApiSubscriber')->willReturn($apiSubscriberMock);

        $subscriberMock->expects($this->once())->method('getSubscriberEmail')->willReturn($customerEmail);

        $apiSubscriberMock->expects($this->once())->method('update')->with($customerEmail, $storeId, '', 1);

        $groupControllerMock->expects($this->once())->method('__')->with($successMessage)->willReturn($successMessage);

        $coreSessionMock->expects($this->once())->method('addSuccess')->with($successMessage);

        $groupControllerMock->expects($this->once())->method('_redirect')->with('/');
        $groupControllerMock->indexAction();
    }
}
