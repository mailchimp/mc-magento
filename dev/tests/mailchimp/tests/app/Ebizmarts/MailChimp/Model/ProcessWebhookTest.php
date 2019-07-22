<?php

class Ebizmarts_MailChimp_Model_ProcessWebhookTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Mage::app('default');
    }

    public function testWebhookProfileCustomerExists()
    {
        $data = array();
        $listId = $data['list_id'] = 'a1s2d3f4t5';
        $email  = $data['email'] = 'pepe@ebizmarts.com';
        $fname  = $data['merges']['FNAME'] = 'pepe';
        $lname  = $data['merges']['LNAME'] = 'Perez';

        $processWebhookMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_ProcessWebhook::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getHelper'))
            ->getMock();
        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('loadListCustomer'))
            ->getMock();

        $customerMock = $this->getMockBuilder(Mage_Customer_Model_Customer::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getFirstName','setFirstName','getLastName','setLastName','save'))
            ->getMock();

        $processWebhookMock->expects($this->once())->method('getHelper')->willReturn($helperMock);
        $helperMock->expects($this->once())->method('loadListCustomer')->with($listId, $email)->willReturn($customerMock);

        $customerMock->expects($this->once())->method('setFirstName')->with($fname)->willReturn($customerMock);
        $customerMock->expects($this->once())->method('setLastName')->with($lname)->willReturn($customerMock);
        $customerMock->expects($this->once())->method('getFirstName')->willReturn($customerMock);
        $customerMock->expects($this->once())->method('getLastName')->willReturn($customerMock);
        $customerMock->expects($this->once())->method('save')->willReturn($customerMock);

        $processWebhookMock->_profile($data);
    }

    public function testWebhookProfileSubscriberExists()
    {
        $data = array();
        $listId = $data['list_id'] = 'e4ef38998b';
        $email  = $data['email'] = 'brian+enterprisex1@ebizmarts.com';
        $fname  = $data['merges']['FNAME'] = 'Enterprise1';
        $lname  = $data['merges']['LNAME'] = 'enterprise11';
        $subscribeSource = Ebizmarts_MailChimp_Model_Subscriber::MAILCHIMP_SUBSCRIBE;

        $processWebhookMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_ProcessWebhook::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getHelper'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('loadListCustomer', 'loadListSubscriber'))
            ->getMock();

        $subscriberMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Subscribers::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getId','getSubscriberFirstname','setSubscriberFirstname',
                'getSubscriberLastname','setSubscriberLastname','setSubscriberSource',
                'save'))
            ->getMock();

        $processWebhookMock->expects($this->once())->method('getHelper')->willReturn($helperMock);

        $helperMock->expects($this->once())->method('loadListCustomer')->with($listId, $email)->willReturn(null);
        $helperMock->expects($this->once())->method('loadListSubscriber')->with($listId, $email)->willReturn($subscriberMock);

        $subscriberMock->expects($this->once())->method('getId')->willReturn($subscriberMock);
        $subscriberMock->expects($this->once())->method('getSubscriberLastname')->willReturn($subscriberMock);
        $subscriberMock->expects($this->once())->method('setSubscriberFirstname')->with($fname)->willReturn($subscriberMock);
        $subscriberMock->expects($this->once())->method('setSubscriberLastname')->with($lname)->willReturn($subscriberMock);
        $subscriberMock->expects($this->once())->method('setSubscriberSource')->with($subscribeSource)->willReturn($subscriberMock);
        $subscriberMock->expects($this->once())->method('save')->willReturn($subscriberMock);

        $processWebhookMock->_profile($data);
    }


    public function testWebhookProfileSubscriberNotExistsMemberSubscribed()
    {
        $data = array();
        $listId = $data['list_id'] = 'e4ef38998b';
        $email  = $data['email'] = 'brian+enterprisex1@ebizmarts.com';
        $fname  = $data['merges']['FNAME'] = 'Enterprise1';
        $lname  = $data['merges']['LNAME'] = 'enterprise11';

        $generalList = Ebizmarts_MailChimp_Model_Config::GENERAL_LIST;
        $scope = 'stores';
        $scope_id = 1;
        $member['status'] = 'subscribed';
        $md5HashEmail = md5(strtolower($email));

        $processWebhookMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_ProcessWebhook::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getHelper'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getFirstScopeFromConfig'
                , 'getApi'
                , 'loadListCustomer'
                , 'loadListSubscriber'
                , 'subscribeMember'
            ))
            ->getMock();

        $subscriberMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Subscribers::class)
            ->disableOriginalConstructor()
            ->setMethods(array('setSubscriberFirstname'
                , 'setSubscriberLastname'
                , 'getId'
               ))
            ->getMock();

        $apiMock = $this->getMockBuilder(Ebizmarts_MailChimp::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getLists'))
            ->getMock();

        $listsMock = $this->getMockBuilder(MailChimp_Lists::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getMembers'))
            ->getMock();

        $memberMock = $this->getMockBuilder(MailChimp_ListsMembers::class)
            ->disableOriginalConstructor()
            ->setMethods(array('get'))
            ->getMock();

        $processWebhookMock->expects($this->once())->method('getHelper')->willReturn($helperMock);
        $helperMock->expects($this->once())->method('loadListCustomer')->with($listId, $email)->willReturn(null);
        $helperMock->expects($this->once())->method('loadListSubscriber')->with($listId, $email)->willReturn($subscriberMock);
        $helperMock->expects($this->once())->method('getFirstScopeFromConfig')
            ->with($generalList, $listId)
            ->willReturn(array('scope'=>$scope,'scope_id'=>$scope_id));

        $helperMock->expects($this->once())->method('getApi')
            ->with($scope_id, $scope)
            ->willReturn($apiMock);

        $subscriberMock->expects($this->once())->method('setSubscriberFirstname')->with($fname)->willReturn($subscriberMock);
        $subscriberMock->expects($this->once())->method('setSubscriberLastname')->with($lname)->willReturn($subscriberMock);

        $apiMock->expects($this->once())
            ->method('getLists')
            ->willReturn($listsMock);

        $listsMock->expects($this->once())
            ->method('getMembers')
            ->willReturn($memberMock);

        $memberMock->expects($this->once())
            ->method('get')
            ->with($listId, $md5HashEmail, null, null)
            ->willReturn($member);

        $helperMock->expects($this->once())->method('subscribeMember')
            ->with($subscriberMock)
            ->willReturn(null);

        //$subscriberMock->expects($this->once())->method('getStoreId')->willReturn($subscriberMock);
        $processWebhookMock->_profile($data);
    }

    public function testWebhookProfileSubscriberNotExistsMemberUnsubscribed()
    {
        $data = array();
        $listId = $data['list_id'] = 'e4ef38998b';
        $email  = $data['email'] = 'brian+enterprisex1@ebizmarts.com';
        $fname  = $data['merges']['FNAME'] = 'Enterprise1';
        $lname  = $data['merges']['LNAME'] = 'enterprise11';
        $generalList = Ebizmarts_MailChimp_Model_Config::GENERAL_LIST;
        $scope = 'stores';
        $scope_id = 1;
        $member['status'] = 'unsubscribed';
        $md5HashEmail = md5(strtolower($email));
        $webhookDeleteActionReturn = 0;

        $processWebhookMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_ProcessWebhook::class)
            ->disableOriginalConstructor()->setMethods(array('getHelper'))->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getFirstScopeFromConfig'
            , 'getApi'
            , 'loadListCustomer'
            , 'loadListSubscriber'
            , 'unsubscribeMember'
            , 'getWebhookDeleteAction'
            ))->getMock();

        $subscriberMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Subscribers::class)
            ->disableOriginalConstructor()
            ->setMethods(array('setSubscriberFirstname'
            , 'setSubscriberLastname'
            , 'getStoreId'
            , 'getId'
            ))->getMock();

        $apiMock = $this->getMockBuilder(Ebizmarts_MailChimp::class)->disableOriginalConstructor()
            ->setMethods(array('getLists'))->getMock();

        $listsMock = $this->getMockBuilder(MailChimp_Lists::class)->disableOriginalConstructor()
            ->setMethods(array('getMembers'))->getMock();

        $memberMock = $this->getMockBuilder(MailChimp_ListsMembers::class)
            ->disableOriginalConstructor()
            ->setMethods(array('get'))
            ->getMock();

        $processWebhookMock->expects($this->once())->method('getHelper')->willReturn($helperMock);
        $helperMock->expects($this->once())->method('loadListCustomer')->with($listId, $email)->willReturn(null);
        $helperMock->expects($this->once())->method('loadListSubscriber')->with($listId, $email)
            ->willReturn($subscriberMock);
        $helperMock->expects($this->once())->method('getFirstScopeFromConfig')
            ->with($generalList, $listId)
            ->willReturn(array('scope'=>$scope,'scope_id'=>$scope_id));

        $helperMock->expects($this->once())->method('getApi')
            ->with($scope_id, $scope)
            ->willReturn($apiMock);

        $subscriberMock->expects($this->once())->method('setSubscriberFirstname')->with($fname)
            ->willReturn($subscriberMock);
        $subscriberMock->expects($this->once())->method('setSubscriberLastname')->with($lname)
            ->willReturn($subscriberMock);

        $apiMock->expects($this->once())->method('getLists')->willReturn($listsMock);
        $listsMock->expects($this->once())->method('getMembers')->willReturn($memberMock);
        $memberMock->expects($this->once())->method('get')->with($listId, $md5HashEmail, null, null)->willReturn($member);

        $helperMock->expects($this->once())->method('unsubscribeMember')->with($subscriberMock)->willReturn(null);
        $subscriberMock->expects($this->once())->method('getStoreId')->willReturn($scope_id);

        $helperMock->expects($this->once())->method('getWebhookDeleteAction')->with($scope_id)
            ->willReturn($webhookDeleteActionReturn);

        $processWebhookMock->_profile($data);
    }
}
