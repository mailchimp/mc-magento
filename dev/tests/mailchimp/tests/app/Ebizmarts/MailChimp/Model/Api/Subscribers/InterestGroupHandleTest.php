<?php

class Ebizmarts_MailChimp_Model_Api_Subscribers_InterestGroupHandleTest extends PHPUnit_Framework_TestCase
{
    const DEFAULT_STORE_ID = 1;

    public function setUp()
    {
        Mage::app('default');
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

        $grouping = array(array(
            'unique_id' => $uniqueId,
            'groups' => 'group1, group2'
        ));

        $interestGroupHandleMock = $this
            ->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Subscribers_InterestGroupHandle::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getHelper', 'getDateHelper', '_getStoreId',
                'getInterestGroupModel', 'getSubscriber'))
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
            ->setMethods(array('getId'))
            ->getMock();

        $subscriberMock = $this->getMockBuilder(Mage_Newsletter_Model_Subscriber::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getSubscriberId', 'getStoreId'))
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

        $interestGroupHandleMock->expects($this->exactly(2))->method('getHelper')->willReturn($helperMock);
        $interestGroupHandleMock->expects($this->once())->method('getDateHelper')->willReturn($dateHelperMock);
        $interestGroupHandleMock->expects($this->once())->method('getSubscriber')->willReturn($subscriberMock);

        $customerMock->expects($this->once())->method('getId')->willReturn($customerId);
        $subscriberMock->expects($this->once())->method('getSubscriberId')->willReturn($subscriberId);
        $subscriberMock->expects($this->once())->method('getStoreId')->willReturn($storeId);

        $helperMock->expects($this->once())->method('getApi')->with($storeId)->willReturn($apiMock);
        $apiMock->expects($this->once())->method('getLists')->willReturn($listsMock);
        $listsMock->expects($this->once())->method('getInterestCategory')->willReturn($interestCategoryMock);
        $interestCategoryMock->expects($this->once())->method('getInterests')->willReturn($interestMock);
        $interestMock->expects($this->once())->method('getAll')->with($listId, $uniqueId)->willReturn($interests);

        $interestGroupHandleMock->expects($this->once())->method('getInterestGroupModel')->willReturn($interestGroupMock);
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

        $interestGroupHandleMock->setGroupings($grouping);
        $interestGroupHandleMock->setCustomer($customerMock);
        $interestGroupHandleMock->setListId($listId);

        $interestGroupHandleMock->processGroupsData();
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

        $grouping = array(array(
            'unique_id' => $uniqueId,
            'groups' => 'group1, group2'
        ));

        $interestGroupHandleMock = $this
            ->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Subscribers_InterestGroupHandle::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getHelper', 'getDateHelper', '_getStoreId',
                'getInterestGroupModel', 'getSubscriberModel'))
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
            ->setMethods(array('getSubscriberId', 'loadByEmail', 'getStoreId', 'getCustomerId'))
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

        $interestGroupHandleMock->expects($this->exactly(2))->method('getHelper')->willReturn($helperMock);
        $interestGroupHandleMock->expects($this->once())->method('getDateHelper')->willReturn($dateHelperMock);

        $subscriberMock->expects($this->once())->method('getCustomerId')->willReturn($customerId);
        $subscriberMock->expects($this->once())->method('getSubscriberId')->willReturn($subscriberId);
        $subscriberMock->expects($this->once())->method('getStoreId')->willReturn($storeId);

        $helperMock->expects($this->once())->method('getApi')->with($storeId)->willReturn($apiMock);
        $apiMock->expects($this->once())->method('getLists')->willReturn($listsMock);
        $listsMock->expects($this->once())->method('getInterestCategory')->willReturn($interestCategoryMock);
        $interestCategoryMock->expects($this->once())->method('getInterests')->willReturn($interestMock);
        $interestMock->expects($this->once())->method('getAll')->with($listId, $uniqueId)->willReturn($interests);

        $interestGroupHandleMock->expects($this->once())->method('getInterestGroupModel')->willReturn($interestGroupMock);
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

        $interestGroupHandleMock->setGroupings($grouping);
        $interestGroupHandleMock->setSubscriber($subscriberMock);
        $interestGroupHandleMock->setListId($listId);

        $interestGroupHandleMock->processGroupsData();
    }
}


