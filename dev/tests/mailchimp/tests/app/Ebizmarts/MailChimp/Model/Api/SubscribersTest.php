<?php

class Ebizmarts_MailChimp_Model_Api_SubscribersTest extends PHPUnit_Framework_TestCase
{
    const DEFAULT_STORE_ID = 1;

    public function setUp()
    {
        Mage::app('default');
    }

    /**
     * @param $magentoStatus
     * @param $expected
     * @dataProvider magentoSubscriberStatus
     */
    public function testMailchimpStatus($magentoStatus, $expected)
    {
        $subscribersApiMock =
            $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Subscribers::class)
                ->disableOriginalConstructor()
                ->setMethods(array('magentoConfigNeedsConfirmation'))
                ->getMock();

        $return = $subscribersApiMock->translateMagentoStatusToMailchimpStatus($magentoStatus);

        $this->assertEquals($expected, $return);
    }

    /**
     * @return array(subscriber_status, magento_store_id, subscriber_status_string)
     */
    public function magentoSubscriberStatus()
    {
        return array(
            array(Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED, "subscribed"),
            array(Mage_Newsletter_Model_Subscriber::STATUS_NOT_ACTIVE, "pending"),
            array(Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED, "unsubscribed"),
            array(Mage_Newsletter_Model_Subscriber::STATUS_UNCONFIRMED, "pending"),
        );
    }

    public function testGetMergeVars()
    {
        $websiteId = 1;
        $storeId = 2;
        $mergeVars = array();
        $customAtt = $map['magento'];
        $lastOrder = array();
        $key = 'KEY';
        $email = 'test@ebizmarts.com';
        $eventValue = null;
        $mapFields = array();
        $customerId = 10;

        $subscribersApiMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Subscribers::class)
            ->disableOriginalConstructor()
            ->setMethods(array(
                    'getWebSiteByStoreId',
                    'getEntityAttributeCollection',
                    'getCustomerModel',
                    'customizedAttributes',
                    'dispatchEventValue',
                    'dispatchEventMergeVars',
                    'getMailchimpHelper')
            )->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getMapFields', 'getLastOrderByEmail'))
            ->getMock();

        $collectionMock = $this->getMockBuilder(Mage_Eav_Model_Resource_Entity_Attribute_Collection::class)
            ->setMethods(array('setEntityTypeFilter', 'addSetInfo', 'getData'))
            ->getMock();

        $customerMock = $this->getMockBuilder(Mage_Customer_Model_Customer::class)
            ->setMethods(array('setWebsiteId', 'load'))
            ->getMock();

        $subscriberMock = $this->getMockBuilder(Mage_Customer_Model_Customer::class)
            ->setMethods(array('getStoreId', 'getSubscriberEmail', 'getCustomerId'))
            ->getMock();


        $subscribersApiMock->expects($this->once())
            ->method('getMailchimpHelper')
            ->willReturn($helperMock);
        $subscribersApiMock->expects($this->once())
            ->method('getWebSiteByStoreId')
            ->with($storeId)
            ->willReturn($websiteId);
        $subscribersApiMock->expects($this->once())
            ->method('getEntityAttributeCollection')
            ->willReturn($collectionMock);
        $subscribersApiMock->expects($this->once())
            ->method('getCustomerModel')
            ->willReturn($customerMock);
//        $subscribersApiMock->expects($this->once())
//            ->method('customizedAttributes')
//            ->with($customAtt, $customerMock, $lastOrder, $mergeVars, $key, $helperMock, $email, $storeId)
//            ->willReturn($mergeVars);
//        $subscribersApiMock->expects($this->once())
//            ->method('dispatchEventValue')
//            ->with($customerMock, $email, $customAtt, $eventValue);
        $newVars = new Varien_Object;
        $subscribersApiMock->expects($this->once())
            ->method('dispatchEventMergeVars')
            ->with($subscriberMock, $mergeVars, $newVars);

        $helperMock->expects($this->once())
            ->method('getMapFields')
            ->with($storeId)
            ->willReturn($mapFields);
        $helperMock->expects($this->once())
            ->method('getLastOrderByEmail')
            ->with($email)
            ->willReturn($lastOrder);

        $collectionMock->expects($this->once())
            ->method('setEntityTypeFilter')
            ->with(1)
            ->willReturnSelf();
        $collectionMock->expects($this->once())
            ->method('addSetInfo')
            ->willReturnSelf();
        $collectionMock->expects($this->once())
            ->method('getData')
            ->willReturnSelf();

        $customerMock->expects($this->once())
            ->method('setWebsiteId')
            ->with($websiteId)
            ->willReturnSelf();
        $customerMock->expects($this->once())
            ->method('load')
            ->with($customerId)
            ->willReturnSelf();

        $subscriberMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn($customerId);
        $subscriberMock->expects($this->once())
            ->method('getSubscriberEmail')
            ->willReturn($email);
        $subscriberMock->expects($this->once())
            ->method('getStoreId')
            ->willReturn($storeId);

        $subscribersApiMock->getMergeVars($subscriberMock);
    }
}
