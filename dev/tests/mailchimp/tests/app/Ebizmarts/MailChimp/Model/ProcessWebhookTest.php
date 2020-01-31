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
        $helperMock
            ->expects($this->once())
            ->method('loadListCustomer')
            ->with($listId, $email)
            ->willReturn($customerMock);

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
            ->setMethods(
                array('getId','getSubscriberFirstname','setSubscriberFirstname',
                'getSubscriberLastname','setSubscriberLastname','setSubscriberSource',
                'save')
            )
            ->getMock();

        $processWebhookMock->expects($this->once())->method('getHelper')->willReturn($helperMock);

        $helperMock->expects($this->once())->method('loadListCustomer')->with($listId, $email)->willReturn(null);
        $helperMock
            ->expects($this->once())
            ->method('loadListSubscriber')
            ->with($listId, $email)
            ->willReturn($subscriberMock);

        $subscriberMock
            ->expects($this->once())
            ->method('getId')
            ->willReturn($subscriberMock);
        $subscriberMock
            ->expects($this->once())
            ->method('getSubscriberLastname')
            ->willReturn($subscriberMock);
        $subscriberMock
            ->expects($this->once())
            ->method('setSubscriberFirstname')
            ->with($fname)
            ->willReturn($subscriberMock);
        $subscriberMock
            ->expects($this->once())
            ->method('setSubscriberLastname')
            ->with($lname)
            ->willReturn($subscriberMock);
        $subscriberMock
            ->expects($this->once())
            ->method('setSubscriberSource')
            ->with($subscribeSource)
            ->willReturn($subscriberMock);
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
        $scopeId = 1;
        $member['status'] = 'subscribed';
        $cryptHashEmail = hash('md5', strtolower($email));

        $processWebhookMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_ProcessWebhook::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getHelper'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(
                array('getFirstScopeFromConfig'
                , 'getApi'
                , 'loadListCustomer'
                , 'loadListSubscriber'
                , 'subscribeMember'
                )
            )
            ->getMock();

        $subscriberMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Subscribers::class)
            ->disableOriginalConstructor()
            ->setMethods(
                array('setSubscriberFirstname'
                , 'setSubscriberLastname'
                , 'getId'
                )
            )
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

        $processWebhookMock->expects($this->exactly(2))->method('getHelper')->willReturn($helperMock);
        $helperMock->expects($this->once())->method('loadListCustomer')->with($listId, $email)->willReturn(null);
        $helperMock
            ->expects($this->once())
            ->method('loadListSubscriber')
            ->with($listId, $email)
            ->willReturn($subscriberMock);
        $helperMock->expects($this->once())->method('getFirstScopeFromConfig')
            ->with($generalList, $listId)
            ->willReturn(array('scope'=>$scope,'scope_id'=>$scopeId));

        $helperMock->expects($this->once())->method('getApi')
            ->with($scopeId, $scope)
            ->willReturn($apiMock);

        $subscriberMock
            ->expects($this->once())
            ->method('setSubscriberFirstname')
            ->with($fname)
            ->willReturn($subscriberMock);
        $subscriberMock
            ->expects($this->once())
            ->method('setSubscriberLastname')
            ->with($lname)
            ->willReturn($subscriberMock);

        $apiMock->expects($this->once())
            ->method('getLists')
            ->willReturn($listsMock);

        $listsMock->expects($this->once())
            ->method('getMembers')
            ->willReturn($memberMock);

        $memberMock->expects($this->once())
            ->method('get')
            ->with($listId, $cryptHashEmail, null, null)
            ->willReturn($member);

        $helperMock->expects($this->once())->method('subscribeMember')
            ->with($subscriberMock)
            ->willReturn(null);

        $processWebhookMock->_profile($data);
    }

    public function testProcessGroupDataNoSubscriber()
    {
        $storeId = 1;

        $customerId = 1;
        $customerMail = '';
        $subscriberId = 1;

        $currentDateTime = '2020-01-30 10:24:42';

        $listId = 'a1b2c3d4';
        $uniqueId = 'q1w2e3r4';
        $interests = array(
            'interests' => array(
                array('name' => 'group1', 'id' => 'groupId1'),
                array('name' => 'group2', 'id' => 'groupId2')
            )
        );

        $groups = array(
            $uniqueId => array(
                'groupId1' => 'groupId1',
                'groupId2' => 'groupId2'
            )
        );
        $groupsEncoded = json_encode($groups);

        $grouping = array(
            'unique_id' => $uniqueId,
            'groups' => 'group1, group2'
        );

        $processWebhookMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_ProcessWebhook::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getHelper', 'getDateHelper', '_getStoreId',
                'getInterestGroupModel', 'getSubscriberModel')) //agregar mock de load by email y eso.
            ->getMock();
        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('arrayEncode', 'getApi'))
            ->getMock();

        $dateHelperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Date::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getCurrentDateTime'))
            ->getMock();

        $customerMock = $this->getMockBuilder(Mage_Customer_Model_Customer::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getEmail','getId'))
            ->getMock();

        $subscriberMock = $this->getMockBuilder(Mage_Newsletter_Model_Subscriber::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getSubscriberId', 'loadByEmail', 'getStoreId'))
            ->getMock();

        $interestGroupMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Interestgroup::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getByRelatedIdStoreId', 'setGroupdata', 'setSubscriberId',
                'setCustomerId', 'setStoreId', 'setUpdatedAt', 'save'))
            ->getMock();

        $apiMock = $this->getMockBuilder(Ebizmarts_MailChimp::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getLists'))
            ->getMock();

        $listsMock = $this->getMockBuilder(MailChimp_Lists::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getInterestCategory'))
            ->getMock();

        $interestCategoryMock = $this->getMockBuilder(MailChimp_ListsInterestCategory::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getInterests'))
            ->getMock();

        $interestMock = $this->getMockBuilder(MailChimp_ListInterestCategoryInterests::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getAll'))
            ->getMock();

        $processWebhookMock->expects($this->once())->method('getHelper')->willReturn($helperMock);
        $processWebhookMock->expects($this->once())->method('getDateHelper')->willReturn($dateHelperMock);
        $processWebhookMock->expects($this->once())->method('getSubscriberModel')->willReturn($subscriberMock);

        $subscriberMock->expects($this->once())->method('loadByEmail')->with($customerMail)->willReturnSelf();

        $customerMock->expects($this->once())->method('getEmail')->willReturn($customerMail);
        $customerMock->expects($this->once())->method('getId')->willReturn($customerId);
        $subscriberMock->expects($this->once())->method('getSubscriberId')->willReturn($subscriberId);
        $subscriberMock->expects($this->once())->method('getStoreId')->willReturn($storeId);

        $helperMock->expects($this->once())->method('getApi')->with($storeId)->willReturn($apiMock);
        $apiMock->expects($this->once())->method('getLists')->willReturn($listsMock);
        $listsMock->expects($this->once())->method('getInterestCategory')->willReturn($interestCategoryMock);
        $interestCategoryMock->expects($this->once())->method('getInterests')->willReturn($interestMock);
        $interestMock->expects($this->once())->method('getAll')->with($listId, $uniqueId)->willReturn($interests);

        $processWebhookMock->expects($this->once())->method('getInterestGroupModel')->willReturn($interestGroupMock);
        $helperMock->expects($this->once())->method('arrayEncode')->with($groups)->willReturn($groupsEncoded);
        $dateHelperMock->expects($this->once())->method('getCurrentDateTime')->with()->willReturn($currentDateTime);

        $interestGroupMock
            ->expects($this->once())
            ->method('getByRelatedIdStoreId')
            ->with($customerId, $subscriberId, $storeId)
            ->willReturnSelf();
        $interestGroupMock->expects($this->once())->method('setGroupdata')->with($groupsEncoded)->willReturnSelf();
        $interestGroupMock->expects($this->once())->method('setSubscriberId')->with($subscriberId)->willReturnSelf();
        $interestGroupMock->expects($this->once())->method('setCustomerId')->with($customerId)->willReturnSelf();
        $interestGroupMock->expects($this->once())->method('setStoreId')->with($storeId)->willReturnSelf();
        $interestGroupMock->expects($this->once())->method('setUpdatedAt')->with($currentDateTime)->willReturnSelf();
        $interestGroupMock->expects($this->once())->method('save')->willReturnSelf();

        $processWebhookMock->_processGroupsData($grouping, $customerMock, $listId, false);
    }

    public function testProcessGroupDataSubscriber()
    {
        $storeId = 1;

        $customerId = 1;
        $customerMail = '';
        $subscriberId = 1;

        $currentDateTime = '2020-01-30 10:24:42';

        $listId = 'a1b2c3d4';
        $uniqueId = 'q1w2e3r4';
        $interests = array(
            'interests' => array(
                array('name' => 'group1', 'id' => 'groupId1'),
                array('name' => 'group2', 'id' => 'groupId2')
            )
        );

        $groups = array(
            $uniqueId => array(
                'groupId1' => 'groupId1',
                'groupId2' => 'groupId2'
            )
        );
        $groupsEncoded = json_encode($groups);

        $grouping = array(
            'unique_id' => $uniqueId,
            'groups' => 'group1, group2'
        );

        $processWebhookMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_ProcessWebhook::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getHelper', 'getDateHelper', '_getStoreId',
                'getInterestGroupModel', 'getSubscriberModel')) //agregar mock de load by email y eso.
            ->getMock();
        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('arrayEncode', 'getApi'))
            ->getMock();

        $dateHelperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Date::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getCurrentDateTime'))
            ->getMock();

        $customerMock = $this->getMockBuilder(Mage_Customer_Model_Customer::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getEmail','getCustomerId'))
            ->getMock();

        $subscriberMock = $this->getMockBuilder(Mage_Newsletter_Model_Subscriber::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getSubscriberId', 'loadByEmail', 'getStoreId'))
            ->getMock();

        $interestGroupMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Interestgroup::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getByRelatedIdStoreId', 'setGroupdata', 'setSubscriberId',
                'setCustomerId', 'setStoreId', 'setUpdatedAt', 'save'))
            ->getMock();

        $apiMock = $this->getMockBuilder(Ebizmarts_MailChimp::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getLists'))
            ->getMock();

        $listsMock = $this->getMockBuilder(MailChimp_Lists::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getInterestCategory'))
            ->getMock();

        $interestCategoryMock = $this->getMockBuilder(MailChimp_ListsInterestCategory::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getInterests'))
            ->getMock();

        $interestMock = $this->getMockBuilder(MailChimp_ListInterestCategoryInterests::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getAll'))
            ->getMock();

        $processWebhookMock->expects($this->once())->method('getHelper')->willReturn($helperMock);
        $processWebhookMock->expects($this->once())->method('getDateHelper')->willReturn($dateHelperMock);
        $processWebhookMock->expects($this->once())->method('getSubscriberModel')->willReturn($subscriberMock);

        $subscriberMock->expects($this->once())->method('loadByEmail')->with($customerMail)->willReturnSelf();

        $customerMock->expects($this->once())->method('getEmail')->willReturn($customerMail);
        $customerMock->expects($this->once())->method('getCustomerId')->willReturn($customerId);
        $subscriberMock->expects($this->once())->method('getSubscriberId')->willReturn($subscriberId);
        $subscriberMock->expects($this->once())->method('getStoreId')->willReturn($storeId);

        $helperMock->expects($this->once())->method('getApi')->with($storeId)->willReturn($apiMock);
        $apiMock->expects($this->once())->method('getLists')->willReturn($listsMock);
        $listsMock->expects($this->once())->method('getInterestCategory')->willReturn($interestCategoryMock);
        $interestCategoryMock->expects($this->once())->method('getInterests')->willReturn($interestMock);
        $interestMock->expects($this->once())->method('getAll')->with($listId, $uniqueId)->willReturn($interests);

        $processWebhookMock->expects($this->once())->method('getInterestGroupModel')->willReturn($interestGroupMock);
        $helperMock->expects($this->once())->method('arrayEncode')->with($groups)->willReturn($groupsEncoded);
        $dateHelperMock->expects($this->once())->method('getCurrentDateTime')->with()->willReturn($currentDateTime);

        $interestGroupMock
            ->expects($this->once())
            ->method('getByRelatedIdStoreId')
            ->with($customerId, $subscriberId, $storeId)
            ->willReturnSelf();
        $interestGroupMock->expects($this->once())->method('setGroupdata')->with($groupsEncoded)->willReturnSelf();
        $interestGroupMock->expects($this->once())->method('setSubscriberId')->with($subscriberId)->willReturnSelf();
        $interestGroupMock->expects($this->once())->method('setCustomerId')->with($customerId)->willReturnSelf();
        $interestGroupMock->expects($this->once())->method('setStoreId')->with($storeId)->willReturnSelf();
        $interestGroupMock->expects($this->once())->method('setUpdatedAt')->with($currentDateTime)->willReturnSelf();
        $interestGroupMock->expects($this->once())->method('save')->willReturnSelf();

        $processWebhookMock->_processGroupsData($grouping, $customerMock, $listId, true);
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
        $scopeId = 1;
        $member['status'] = 'unsubscribed';
        $cryptHashEmail = hash('md5', strtolower($email));
        $webhookDeleteActionReturn = 0;

        $processWebhookMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_ProcessWebhook::class)
            ->disableOriginalConstructor()->setMethods(array('getHelper'))->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(
                array('getFirstScopeFromConfig'
                , 'getApi'
                , 'loadListCustomer'
                , 'loadListSubscriber'
                , 'unsubscribeMember'
                , 'getWebhookDeleteAction'
                )
            )->getMock();

        $subscriberMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Subscribers::class)
            ->disableOriginalConstructor()
            ->setMethods(
                array('setSubscriberFirstname'
                , 'setSubscriberLastname'
                , 'getStoreId'
                , 'getId'
                )
            )->getMock();

        $apiMock = $this->getMockBuilder(Ebizmarts_MailChimp::class)->disableOriginalConstructor()
            ->setMethods(array('getLists'))->getMock();

        $listsMock = $this->getMockBuilder(MailChimp_Lists::class)->disableOriginalConstructor()
            ->setMethods(array('getMembers'))->getMock();

        $memberMock = $this->getMockBuilder(MailChimp_ListsMembers::class)
            ->disableOriginalConstructor()
            ->setMethods(array('get'))
            ->getMock();

        $processWebhookMock->expects($this->exactly(2))->method('getHelper')->willReturn($helperMock);
        $helperMock->expects($this->once())->method('loadListCustomer')->with($listId, $email)->willReturn(null);
        $helperMock->expects($this->once())->method('loadListSubscriber')->with($listId, $email)
            ->willReturn($subscriberMock);
        $helperMock->expects($this->once())->method('getFirstScopeFromConfig')
            ->with($generalList, $listId)
            ->willReturn(array('scope'=>$scope,'scope_id'=>$scopeId));

        $helperMock->expects($this->once())->method('getApi')
            ->with($scopeId, $scope)
            ->willReturn($apiMock);

        $subscriberMock->expects($this->once())->method('setSubscriberFirstname')->with($fname)
            ->willReturn($subscriberMock);
        $subscriberMock->expects($this->once())->method('setSubscriberLastname')->with($lname)
            ->willReturn($subscriberMock);

        $apiMock->expects($this->once())->method('getLists')->willReturn($listsMock);
        $listsMock->expects($this->once())->method('getMembers')->willReturn($memberMock);
        $memberMock
            ->expects($this->once())
            ->method('get')
            ->with($listId, $cryptHashEmail, null, null)
            ->willReturn($member);

        $helperMock->expects($this->once())->method('unsubscribeMember')->with($subscriberMock)->willReturn(null);
        $subscriberMock->expects($this->once())->method('getStoreId')->willReturn($scopeId);

        $helperMock->expects($this->once())->method('getWebhookDeleteAction')->with($scopeId)
            ->willReturn($webhookDeleteActionReturn);

        $processWebhookMock->_profile($data);
    }
}
