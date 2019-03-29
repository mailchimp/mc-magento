<?php

class Ebizmarts_MailChimp_Model_ObserverTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Mage::app('default');
    }

    public function testProductAttributeUpdateIsUsingCorrectStoreId()
    {
        $scopeId = 1;
        $scope = 'stores';
        $mailchimpStoreId = 'a1s2d3f4g5h6j7k8l9n0';
        $mailchimpStoreIdsArray = array('stores_1' => $mailchimpStoreId);
        /**
         * @var \Ebizmarts_MailChimp_Model_Observer $modelMock
         */
        $modelMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(array('makeHelper', 'makeApiProduct'))
            ->getMock();

        $apiProductsMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Products::class)
            ->disableOriginalConstructor()
            ->setMethods(array('update'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getAllMailChimpStoreIds', 'isEcommerceEnabled'))
            ->getMock();

        $eventMock = $this->getMockBuilder(Varien_Event::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getProductIds'))
            ->getMock();

        $eventMock->expects($this->once())->method('getProductIds')->willReturn(array(12, 34));

        $modelMock->expects($this->once())->method('makeHelper')->willReturn($helperMock);
        $modelMock->expects($this->once())->method('makeApiProduct')->willReturn($apiProductsMock);

        $helperMock->expects($this->once())->method('getAllMailChimpStoreIds')->willReturn($mailchimpStoreIdsArray);
        $helperMock->expects($this->once())->method('isEcommerceEnabled')->with($scopeId, $scope)->willReturn(true);

        $apiProductsMock->expects($this->exactly(2))->method('update')->withConsecutive(
            array(12, $mailchimpStoreId),
            array(34, $mailchimpStoreId)
        );

        $eventObserverMock = $this->makeEventObserverMock($eventMock, 1);
        $modelMock->productAttributeUpdate($eventObserverMock);
    }

    public function testSaveCampaignDataCallsCorrectFunctions()
    {
        /**
         * @var \Ebizmarts_MailChimp_Model_Observer $modelMock
         */
        $modelMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(array("_getCampaignCookie", "_getLandingCookie"))
            ->getMock();

        $modelMock->expects($this->once())->method("_getCampaignCookie")->willReturn("abcd123");
        $modelMock->expects($this->once())->method("_getLandingCookie")->willReturn("abcd");

        $orderMock = $this->getMockBuilder(Mage_Sales_Model_Order::class)
            ->setMethods(array("setMailchimpCampaignId", "getMailchimpLandingPage", "setMailchimpLandingPage"))
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->expects($this->once())->method("setMailchimpCampaignId")->with("abcd123");
        $orderMock->expects($this->once())->method("getMailchimpLandingPage")->willReturn(null);
        $orderMock->expects($this->once())->method("setMailchimpLandingPage")->with("abcd");

        $eventMock = $this->getMockBuilder(Varien_Event::class)
            ->disableOriginalConstructor()
            ->setMethods(array("getOrder"))
            ->getMock();
        $eventMock->expects($this->once())->method("getOrder")->willReturn($orderMock);

        $eventObserverMock = $this->makeEventObserverMock($eventMock, 1);

        $modelMock->saveCampaignData($eventObserverMock);
    }

    /**
     * @param $eventMock
     * @param $callCount
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    private function makeEventObserverMock($eventMock, $callCount)
    {
        $eventObserverMock = $this->getMockBuilder(Varien_Event_Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getEvent'))
            ->getMock();

        $eventObserverMock->expects($this->exactly($callCount))->method('getEvent')->willReturn($eventMock);

        return $eventObserverMock;
    }

    public function testChangeStoreGroupName()
    {
        $storeId = 1;

        $eventObserverMock = $this->getMockBuilder(Varien_Event_Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getGroup'))
            ->getMock();

        $observerMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(array('changeStoreNameIfModuleEnabled'))
            ->getMock();

        $storeMock = $this->getMockBuilder(Mage_Core_Model_Store::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getId'))
            ->getMock();

        $groupMock = $this->getMockBuilder(Mage_Core_Model_Store_Group::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getStores'))
            ->getMock();

        $storeCollectionMock = $this->getMockBuilder(Mage_Core_Model_Resource_Store_Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $stores = array();
        $stores[] = $storeMock;

        $eventObserverMock->expects($this->once())->method('getGroup')->willReturn($groupMock);

        $groupMock->expects($this->once())->method('getStores')->willReturn($storeCollectionMock);

        $storeCollectionMock->expects($this->once())->method('getIterator')->willReturn(new ArrayIterator($stores));

        $storeMock->expects($this->once())->method('getId')->willReturn($storeId);

        $observerMock->expects($this->once())->method('changeStoreNameIfModuleEnabled')->with($storeId);

        $observerMock->changeStoreGroupName($eventObserverMock);
    }

    public function testChangeStoreName()
    {
        $storeId = 1;

        $eventObserverMock = $this->getMockBuilder(Varien_Event_Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getStore'))
            ->getMock();

        $observerMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(array('changeStoreNameIfModuleEnabled'))
            ->getMock();

        $storeMock = $this->getMockBuilder(Mage_Core_Model_Store::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getId'))
            ->getMock();

        $stores = array();
        $stores[] = $storeMock;

        $eventObserverMock->expects($this->once())->method('getStore')->willReturn($storeMock);

        $storeMock->expects($this->once())->method('getId')->willReturn($storeId);

        $observerMock->expects($this->once())->method('changeStoreNameIfModuleEnabled')->with($storeId);

        $observerMock->changeStoreName($eventObserverMock);
    }

    public function testChangeStoreNameIfModuleEnabled()
    {
        $mailChimpStoreId = 'a1s2d3f4g5h6j7k8l9n0';
        $scopeId = 1;
        $scope = 'stores';
        $realScope = array('scope_id' => $scopeId, 'scope' => $scope);
        $storeName = 'storeName';

        $observerMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(array('makeHelper'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getMCStoreId', 'getRealScopeForConfig', 'isEcomSyncDataEnabled',
                'isUsingConfigStoreName', 'getMCStoreName', 'changeName'))
            ->getMock();

        $observerMock->expects($this->once())->method('makeHelper')->willReturn($helperMock);

        $helperMock->expects($this->once())->method('getMCStoreId')->with($scopeId)->willReturn($mailChimpStoreId);
        $helperMock->expects($this->once())->method('getRealScopeForConfig')->with(Ebizmarts_MailChimp_Model_Config::GENERAL_MCSTOREID, $scopeId)->willReturn($realScope);
        $helperMock->expects($this->once())->method('isEcomSyncDataEnabled')->with($realScope['scope_id'], $realScope['scope'])->willReturn(true);
        $helperMock->expects($this->once())->method('isUsingConfigStoreName')->with($realScope['scope_id'], $realScope['scope'])->willReturn(false);
        $helperMock->expects($this->once())->method('getMCStoreName')->with($realScope['scope_id'], $realScope['scope'])->willReturn($storeName);
        $helperMock->expects($this->once())->method('changeName')->with($storeName, $realScope['scope_id'], $realScope['scope']);

        $observerMock->changeStoreNameIfModuleEnabled($scopeId);
    }

    public function testHandleSubscriberDeletion()
    {
        $storeId = 1;

        $eventObserverMock = $this->getMockBuilder(Varien_Event_Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getEvent'))
            ->getMock();

        $eventMock = $this->getMockBuilder(Varien_Event::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getSubscriber'))
            ->getMock();

        $observerMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(array('makeHelper', 'makeApiSubscriber'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('isSubscriptionEnabled'))
            ->getMock();

        $subscriberMock = $this->getMockBuilder(Mage_Newsletter_Model_Subscriber::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getStoreId'))
            ->getMock();

        $apiSubscriberMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Subscribers::class)
            ->disableOriginalConstructor()
            ->setMethods(array('deleteSubscriber'))
            ->getMock();

        $eventObserverMock->expects($this->once())->method('getEvent')->willReturn($eventMock);

        $eventMock->expects($this->once())->method('getSubscriber')->willReturn($subscriberMock);

        $subscriberMock->expects($this->once())->method('getStoreId')->willReturn($storeId);

        $observerMock->expects($this->once())->method('makeHelper')->willReturn($helperMock);

        $helperMock->expects($this->once())->method('isSubscriptionEnabled')->with($storeId)->willReturn(true);

        $observerMock->expects($this->once())->method('makeApiSubscriber')->willReturn($apiSubscriberMock);

        $apiSubscriberMock->expects($this->once())->method('deleteSubscriber')->with($subscriberMock);

        $observerMock->handleSubscriberDeletion($eventObserverMock);
    }

    public function testCustomerSaveAfter()
    {
        $adminStoreId = 0;
        $storeId = 1;
        $oldEmailAddress = 'oldEmail@example.com';
        $newEmailAddress = 'newEmail@example.com';
        $subscriberEmail = ($oldEmailAddress) ? $oldEmailAddress : $newEmailAddress;
        $subscriberId = 1;
        $customerId = 1;
        $params = array();

        $customerMock = $this->getMockBuilder(Mage_Customer_Model_Customer::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getId', 'getOrigData', 'getEmail', 'getStoreId', 'getMailchimpStoreView'))
            ->getMock();

        $eventObserverMock = $this->getMockBuilder(Varien_Event_Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getEvent'))
            ->getMock();

        $eventMock = $this->getMockBuilder(Varien_Event::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getCustomer'))
            ->getMock();

        $requestMock = $this->getMockBuilder(Mage_Core_Controller_Request_Http::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getParams'))
            ->getMock();

        $observerMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(array('makeHelper', 'makeApiSubscriber', 'getSubscriberModel', 'makeApiCustomer', 'getRequest', 'handleCustomerGroups'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('isSubscriptionEnabled', 'isEcomSyncDataEnabled'))
            ->getMock();

        $apiSubscriberMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Subscribers::class)
            ->disableOriginalConstructor()
            ->setMethods(array('deleteSubscriber', 'updateSubscriber', 'update'))
            ->getMock();

        $subscriberMock = $this->getMockBuilder(Mage_Newsletter_Model_Subscriber::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getId'))
            ->getMock();

        $subscriberMockTwo = $this->getMockBuilder(Mage_Newsletter_Model_Subscriber::class)
            ->disableOriginalConstructor()
            ->setMethods(array('loadByCustomer', 'setSubscriberEmail', 'save'))
            ->getMock();

        $apiCustomerMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Customers::class)
            ->disableOriginalConstructor()
            ->setMethods(array('update'))
            ->getMock();

        $eventObserverMock->expects($this->once())->method('getEvent')->willReturn($eventMock);

        $eventMock->expects($this->once())->method('getCustomer')->willReturn($customerMock);

        $customerMock->expects($this->once())->method('getOrigData')->with('email')->willReturn($oldEmailAddress);
        $customerMock->expects($this->once())->method('getEmail')->willReturn($newEmailAddress);
        $customerMock->expects($this->once())->method('getStoreId')->willReturn($adminStoreId);
        $customerMock->expects($this->once())->method('getMailchimpStoreView')->willReturn($storeId);

        $observerMock->expects($this->once())->method('makeHelper')->willReturn($helperMock);

        $helperMock->expects($this->once())->method('isSubscriptionEnabled')->with($storeId)->willReturn(true);

        $observerMock->expects($this->once())->method('getRequest')->willReturn($requestMock);

        $requestMock->expects($this->once())->method('getParams')->willReturn($params);

        $customerMock->expects($this->once())->method('getId')->willReturn($customerId);

        $observerMock->expects($this->once())->method('handleCustomerGroups')->with($subscriberEmail, $params, $storeId, $customerId)->willReturn($subscriberMock);
        $observerMock->expects($this->once())->method('makeApiSubscriber')->willReturn($apiSubscriberMock);

        $subscriberMock->expects($this->once())->method('getId')->willReturn($subscriberId);

        $apiSubscriberMock->expects($this->once())->method('deleteSubscriber')->with($subscriberMock);

        $observerMock->expects($this->once())->method('getSubscriberModel')->willReturn($subscriberMockTwo);

        $subscriberMockTwo->expects($this->once())->method('loadByCustomer')->with($customerMock)->willReturnSelf();
        $subscriberMockTwo->expects($this->once())->method('setSubscriberEmail')->with($newEmailAddress);
        $subscriberMockTwo->expects($this->once())->method('save')->willReturnSelf();

        $helperMock->expects($this->once())->method('isEcomSyncDataEnabled')->with($storeId)->willReturn(true);

        $observerMock->expects($this->once())->method('makeApiCustomer')->willReturn($apiCustomerMock);

        $apiCustomerMock->expects($this->once())->method('update')->with($customerId, $storeId);

        $observerMock->customerSaveAfter($eventObserverMock);
    }

    public function testCustomerAddressSaveBefore()
    {
        $storeId = 1;
        $customerId = 1;
        $customerEmail = 'customer@email.com';

        $eventObserverMock = $this->getMockBuilder(Varien_Event_Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getEvent'))
            ->getMock();

        $eventMock = $this->getMockBuilder(Varien_Event::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getCustomerAddress'))
            ->getMock();

        $observerMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(array('makeHelper', 'getCustomerModel', 'makeApiSubscriber', 'makeApiCustomer'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('isSubscriptionEnabled', 'isEcomSyncDataEnabled'))
            ->getMock();

        $customerMock = $this->getMockBuilder(Mage_Customer_Model_Customer::class)
            ->disableOriginalConstructor()
            ->setMethods(array('load', 'getStoreId', 'getEmail'))
            ->getMock();

        $customerAddressMock = $this->getMockBuilder(Mage_Customer_Model_Address::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getCustomerId'))
            ->getMock();

        $apiCustomerMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Customers::class)
            ->disableOriginalConstructor()
            ->setMethods(array('update'))
            ->getMock();

        $apiSubscriberMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Subscribers::class)
            ->disableOriginalConstructor()
            ->setMethods(array('update'))
            ->getMock();

        $eventObserverMock->expects($this->once())->method('getEvent')->willReturn($eventMock);

        $eventMock->expects($this->once())->method('getCustomerAddress')->willReturn($customerAddressMock);

        $customerAddressMock->expects($this->once())->method('getCustomerId')->willReturn($customerId);

        $observerMock->expects($this->once())->method('getCustomerModel')->willReturn($customerMock);

        $customerMock->expects($this->once())->method('load')->with($customerId)->willReturn($customerMock);
        $customerMock->expects($this->once())->method('getStoreId')->willReturn($customerId);

        $observerMock->expects($this->once())->method('makeHelper')->willReturn($helperMock);

        $helperMock->expects($this->once())->method('isSubscriptionEnabled')->with($storeId)->willReturn(true);

        $customerMock->expects($this->once())->method('getEmail')->willReturn($customerEmail);

        $observerMock->expects($this->once())->method('makeApiSubscriber')->willReturn($apiSubscriberMock);

        $apiSubscriberMock->expects($this->once())->method('update')->with($customerEmail, $storeId);

        $helperMock->expects($this->once())->method('isEcomSyncDataEnabled')->with($storeId)->willReturn(true);

        $observerMock->expects($this->once())->method('makeApiCustomer')->willReturn($apiCustomerMock);

        $apiCustomerMock->expects($this->once())->method('update')->with($customerId, $storeId);

        $observerMock->customerAddressSaveBefore($eventObserverMock);
    }

    public function testNewOrder()
    {
        $storeId = 1;
        $post = 1;
        $productId = 1;
        $mailchimpStoreId = 'a1s2d3f4g5h6j7k8l9n0';
        $customerEmail = 'email@example.com';
        $customerFirstname = 'John';
        $customerLastname = 'Smith';
        $isMarkedAsDeleted = 0;
        $type = Ebizmarts_MailChimp_Model_Config::IS_PRODUCT;


        $itemMock = $this->getMockBuilder(Mage_Sales_Model_Order_Item::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getProductType', 'getProductId'))
            ->getMock();

        $orderMock = $this->getMockBuilder(Mage_Sales_Model_Order::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getStoreId', 'getCustomerEmail', 'getCustomerFirstname', 'getCustomerLastname', 'getAllItems'))
            ->getMock();

        $eventObserverMock = $this->getMockBuilder(Varien_Event_Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getEvent'))
            ->getMock();

        $eventMock = $this->getMockBuilder(Varien_Event::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getOrder'))
            ->getMock();

        $observerMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(array('makeHelper', 'removeCampaignData', 'isBundleItem', 'isConfigurableItem', 'makeApiProduct'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getMageApp', 'isEcomSyncDataEnabled', 'isSubscriptionEnabled', 'loadListSubscriber', 'saveEcommerceSyncData', 'getMCStoreId', 'getEcommerceSyncDataItem'))
            ->getMock();

        $mageAppMock = $this->getMockBuilder(Mage_Core_Model_App::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getRequest'))
            ->getMock();

        $requestMock = $this->getMockBuilder(Mage_Core_Controller_Request_Http::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getPost'))
            ->getMock();

        $subscriberMock = $this->getMockBuilder(Mage_Newsletter_Model_Subscriber::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getCustomerId', 'setSubscriberFirstname', 'setSubscriberLastname', 'subscribe'))
            ->getMock();

        $apiProductsMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Products::class)
            ->disableOriginalConstructor()
            ->setMethods(array('update'))
            ->getMock();

        $dataProductMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Ecommercesyncdata::class)
            ->setMethods(array('getMailchimpSyncDeleted'))
            ->getMock();

        $observerMock->expects($this->once())->method('makeHelper')->willReturn($helperMock);

        $helperMock->expects($this->once())->method('getMageApp')->willReturn($mageAppMock);
        $mageAppMock->expects($this->once())->method('getRequest')->willReturn($requestMock);

        $requestMock->expects($this->once())->method('getPost')->with('mailchimp_subscribe')->willReturn($post);

        $eventObserverMock->expects($this->once())->method('getEvent')->willReturn($eventMock);

        $eventMock->expects($this->once())->method('getOrder')->willReturn($orderMock);

        $orderMock->expects($this->once())->method('getStoreId')->willReturn($storeId);

        $helperMock->expects($this->once())->method('isEcomSyncDataEnabled')->with($storeId)->willReturn(true);

        $helperMock->expects($this->once())->method('isSubscriptionEnabled')->with($storeId)->willReturn(true);

        $orderMock->expects($this->once())->method('getCustomerEmail')->willReturn($customerEmail);

        $helperMock->expects($this->once())->method('loadListSubscriber')->with($post, $customerEmail)->willReturn($subscriberMock);

        $subscriberMock->expects($this->once())->method('getCustomerId')->willReturn(false);

        $orderMock->expects($this->once())->method('getCustomerFirstname')->willReturn($customerFirstname);

        $subscriberMock->expects($this->once())->method('setSubscriberFirstname')->with($customerFirstname);

        $orderMock->expects($this->once())->method('getCustomerLastname')->willReturn($customerLastname);

        $subscriberMock->expects($this->once())->method('setSubscriberLastname')->with($customerLastname);

        $subscriberMock->expects($this->once())->method('subscribe')->with($customerEmail);

        $observerMock->expects($this->once())->method('removeCampaignData');

        $orderMock->expects($this->once())->method('getAllItems')->willReturn(array($itemMock));

        $observerMock->expects($this->once())->method('isBundleItem')->with($itemMock)->willReturn(false);
        $observerMock->expects($this->once())->method('isConfigurableItem')->with($itemMock)->willReturn(false);

        $itemMock->expects($this->once())->method('getProductId')->willReturn($productId);

        $helperMock->expects($this->once())->method('getMCStoreId')->with($storeId)->willReturn($mailchimpStoreId);

        $observerMock->expects($this->once())->method('makeApiProduct')->willReturn($apiProductsMock);

        $apiProductsMock->expects($this->once())->method('update')->with($productId, $mailchimpStoreId);

        $dataProductMock->expects($this->once())
            ->method('getMailchimpSyncDeleted')
            ->willReturn($isMarkedAsDeleted);


        $helperMock->expects($this->once())
            ->method('getEcommerceSyncDataItem')
            ->with($productId, $type, $mailchimpStoreId)
            ->willReturn($dataProductMock);

        $observerMock->newOrder($eventObserverMock);
    }

    public function testAddColumnToSalesOrderGridCollection()
    {
        $addColumnConfig = 1;
        $scopeId = 0;
        $fromCond = array(
            'main_table' => array(
                'joinType' => 'from',
                'schema' => '',
                'tableName' => 'sales_flat_order_grid',
                'joinCondition' => ''
            )
        );
        $mcTableName = 'mailchimp_ecommerce_sync_data';
        $condition = 'mc.related_id=main_table.entity_id AND type = '.Ebizmarts_MailChimp_Model_Config::IS_ORDER;
        $direction = 'ASC';

        $eventObserverMock = $this->getMockBuilder(Varien_Event_Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getOrderGridCollection'))
            ->getMock();

        $observerMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(array('makeHelper', 'getCoreResource', 'getRegistry', 'removeRegistry'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getMonkeyInGrid', 'isEcomSyncDataEnabledInAnyScope'))
            ->getMock();

        $orderGridCollectionMock = $this->getMockBuilder(Mage_Sales_Model_Resource_Order_Grid_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getSelect', 'getTable', 'addOrder'))
            ->getMock();

        $selectMock = $this->getMockBuilder(Varien_Db_Select::class)
            ->disableOriginalConstructor()
            ->setMethods(array('joinLeft', 'group', 'getPart'))
            ->getMock();

        $coreResourceMock = $this->getMockBuilder(Mage_Core_Model_Resource::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getConnection'))
            ->getMock();

        $writeAdapterMock = $this->getMockBuilder(Varien_Db_Adapter_Pdo_Mysql::class)
            ->disableOriginalConstructor()
            ->setMethods(array('quoteInto'))
            ->getMock();

        $observerMock->expects($this->once())->method('makeHelper')->willReturn($helperMock);

        $helperMock->expects($this->once())->method('getMonkeyInGrid')->with($scopeId)->willReturn($addColumnConfig);
        $helperMock->expects($this->once())->method('isEcomSyncDataEnabledInAnyScope')->willReturn(true);

        $eventObserverMock->expects($this->once())->method('getOrderGridCollection')->willReturn($orderGridCollectionMock);

        $orderGridCollectionMock->expects($this->once())->method('getSelect')->willReturn($selectMock);

        $selectMock->expects($this->once())->method('getPart')->with(Zend_Db_Select::FROM)->willReturn($fromCond);

        $orderGridCollectionMock->expects($this->once())->method('getTable')->with('mailchimp/ecommercesyncdata')->willReturn($mcTableName);

        $selectMock->expects($this->once())->method('joinLeft')->with(array('mc' => $mcTableName), $condition, array('mc.mailchimp_synced_flag', 'mc.id'));

        $observerMock->expects($this->once())->method('getCoreResource')->willReturn($coreResourceMock);

        $coreResourceMock->expects($this->once())->method('getConnection')->with('core_write')->willReturn($writeAdapterMock);

        $writeAdapterMock->expects($this->once())->method('quoteInto')->with('mc.related_id=main_table.entity_id AND type = ?', Ebizmarts_MailChimp_Model_Config::IS_ORDER)->willReturn($condition);

        $selectMock->expects($this->once())->method('group');

        $observerMock->expects($this->once())->method('getRegistry')->willReturn($direction);

        $orderGridCollectionMock->expects($this->once())->method('addOrder')->with('mc.id', $direction);

        $observerMock->expects($this->once())->method('removeRegistry');

        $observerMock->addColumnToSalesOrderGridCollection($eventObserverMock);
    }


    /**
     * @param array $data
     * @dataProvider subscriberSaveBeforeDataProvider
     */
    public function testSubscriberSaveBefore($data)
    {
        $getStoreId = $data['getStoreId'];
        $storeId = 1;

        $eventObserverMock = $this->getMockBuilder(Varien_Event_Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getEvent'))
            ->getMock();

        $observerMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(array('makeHelper', 'addSuccessIfRequired'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('isSubscriptionEnabled', 'isEcomSyncDataEnabledInAnyScope',
                'isSubscriptionConfirmationEnabled', 'getStoreId', 'isUseMagentoEmailsEnabled'))
            ->getMock();

        $eventMock = $this->getMockBuilder(Varien_Event::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getSubscriber'))
            ->getMock();

        $subscriberMock = $this->getMockBuilder(Mage_Newsletter_Model_Subscriber::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getSubscriberSource', 'getIsStatusChanged', 'getStatus', 'setStatus', 'getStoreId'))
            ->getMock();

        $eventObserverMock->expects($this->once())->method('getEvent')->willReturn($eventMock);

        $eventMock->expects($this->once())->method('getSubscriber')->willReturn($subscriberMock);

        $subscriberMock->expects($this->once())->method('getStoreId')->willReturn($storeId);

        $observerMock->expects($this->once())->method('makeHelper')->willReturn($helperMock);

        $helperMock->expects($this->once())->method('isSubscriptionEnabled')->with($storeId)->willReturn(true);

        $subscriberMock->expects($this->once())->method('getStatus')->willReturn(Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED);

        $subscriberMock->expects($this->once())->method('getIsStatusChanged')->willReturn(true);

        $helperMock->expects($this->once())->method('isSubscriptionConfirmationEnabled')->with($storeId)->willReturn(true);

        $helperMock->expects($this->once())->method('isUseMagentoEmailsEnabled')->with($storeId)->willReturn(false);

        $subscriberMock->expects($this->once())->method('setStatus')->with(Mage_Newsletter_Model_Subscriber::STATUS_NOT_ACTIVE);

        $observerMock->expects($this->once())->method('addSuccessIfRequired')->with($helperMock);

        $subscriberMock->expects($this->once())->method('getSubscriberSource')->willReturn(null);

        $subscriberMock->expects($this->exactly($getStoreId))->method('getStoreId')->willReturn($storeId);


        $observerMock->subscriberSaveBefore($eventObserverMock);
    }

    public function subscriberSaveBeforeDataProvider()
    {
        return array(
            array(array('magentoMail' => 0, 'subscriberUpdatedAmount' => 1, 'getStoreId' => 1)),
            array(array('magentoMail' => 1, 'subscriberUpdatedAmount' => 0, 'getStoreId' => 1))
        );
    }

    public function testSubscriberSaveAfter()
    {
        $storeId = 1;
        $params = array();

        $eventObserverMock = $this->getMockBuilder(Varien_Event_Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getEvent'))
            ->getMock();

        $observerMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(array('makeHelper', 'getRequest', 'createEmailCookie', 'makeApiSubscriber'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('isSubscriptionEnabled', 'saveInterestGroupData', 'isUseMagentoEmailsEnabled'))
            ->getMock();

        $eventMock = $this->getMockBuilder(Varien_Event::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getSubscriber'))
            ->getMock();

        $subscriberMock = $this->getMockBuilder(Mage_Newsletter_Model_Subscriber::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getSubscriberSource', 'getStoreId', 'getIsStatusChanged'))
            ->getMock();

        $requestMock = $this->getMockBuilder(Mage_Core_Controller_Request_Http::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getParams'))
            ->getMock();

        $apiSubscriberMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Subscribers::class)
            ->disableOriginalConstructor()
            ->setMethods(array('updateSubscriber'))
            ->getMock();

        $eventObserverMock->expects($this->once())->method('getEvent')->willReturn($eventMock);

        $eventMock->expects($this->once())->method('getSubscriber')->willReturn($subscriberMock);

        $subscriberMock->expects($this->once())->method('getStoreId')->willReturn($storeId);

        $observerMock->expects($this->once())->method('makeHelper')->willReturn($helperMock);

        $subscriberMock->expects($this->once())->method('getSubscriberSource')->willReturn(null);

        $helperMock->expects($this->once())->method('isSubscriptionEnabled')->with($storeId)->willReturn(true);

        $observerMock->expects($this->once())->method('getRequest')->willReturn($requestMock);

        $requestMock->expects($this->once())->method('getParams')->willReturn($params);

        $helperMock->expects($this->once())->method('saveInterestGroupData')->with($params, $storeId, null, $subscriberMock);

        $observerMock->expects($this->once())->method('createEmailCookie')->with($subscriberMock);

        $helperMock->expects($this->once())->method('isUseMagentoEmailsEnabled')->with($storeId)->willReturn(false);

        $subscriberMock->expects($this->once())->method('getIsStatusChanged')->willReturn(true);

        $observerMock->expects($this->once())->method('makeApiSubscriber')->willReturn($apiSubscriberMock);

        $apiSubscriberMock->expects($this->once())->method('updateSubscriber')->with($subscriberMock, true);

        $observerMock->subscriberSaveAfter($eventObserverMock);
    }

    /**
     * @param array $cookieData
     * @dataProvider loadCustomerToQuoteDataProvider
     */

    public function testLoadCustomerToQuote($cookieData){

        $storeId = 1;
        $ecomSyncEnabled = 1;
        $abandonedCartEnabled = 1;
        $isLoggedIn = 0;
        $actionName = $cookieData['actionName'];
        $emailCookie = 'keller%2Bpopup%40ebizmarts.com';
        $mcEidCookie = 'f7e95531fb';
        $customerEmail = 'customer@ebizmarts.com';
        $email = 'keller@ebizmarts.com';
        $campaignId = 'gf45f4gg';
        $landingCookie = 'http%3A//127.0.0.1/MASTER1939m4m/%3Fmc_cid%3Dgf45f4gg%26mc_eid%3D7dgasydg';

        $eventObserverMock = $this->getMockBuilder(Varien_Event_Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getEvent'))
            ->getMock();

        $eventMock = $this->getMockBuilder(Varien_Event::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getQuote'))
            ->getMock();

        $observerMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(array('makeHelper', 'isCustomerLoggedIn', 'getRequestActionName', 'getEmailFromPopUp', 'getEmailFromMcEid', 'getEmailCookie', 'getMcEidCookie', '_getCampaignCookie',
                '_getLandingCookie'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('isEcomSyncDataEnabled', 'isAbandonedCartEnabled'))
            ->getMock();

        $quoteMock = $this->getMockBuilder(Mage_Sales_Model_Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getStoreId', 'getCustomerEmail', 'setCustomerEmail', 'setMailchimpCampaignId', 'setMailchimpLandingPage'))
            ->getMock();

        $eventObserverMock->expects($this->once())->method('getEvent')->willReturn($eventMock);
        $eventMock->expects($this->once())->method('getQuote')->willReturn($quoteMock);
        $quoteMock->expects($this->once())->method('getStoreId')->willReturn($storeId);
        $observerMock->expects($this->once())->method('makeHelper')->willReturn($helperMock);
        $helperMock->expects($this->once())->method('isEcomSyncDataEnabled')->with($storeId)->willReturn($ecomSyncEnabled);
        $helperMock->expects($this->once())->method('isAbandonedCartEnabled')->with($storeId)->willReturn($abandonedCartEnabled);
        $observerMock->expects($this->once())->method('isCustomerLoggedIn')->willReturn($isLoggedIn);
        $observerMock->expects($this->once())->method('getRequestActionName')->willReturn($actionName);
        $observerMock->expects($this->once())->method('getEmailCookie')->willReturn($emailCookie);
        $observerMock->expects($this->once())->method('getMcEidCookie')->willReturn($mcEidCookie);
        $observerMock->expects($this->any())->method('getEmailFromPopUp')->with($emailCookie)->willReturn($email);
        $observerMock->expects($this->any())->method('getEmailFromMcEid')->with($storeId, $mcEidCookie)->willReturn($email);
        $quoteMock->expects($this->once())->method('getCustomerEmail')->willReturn($customerEmail);
        $quoteMock->expects($this->once())->method('setCustomerEmail')->with($email);
        $observerMock->expects($this->once())->method('_getCampaignCookie')->willReturn($campaignId);
        $quoteMock->expects($this->once())->method('setMailchimpCampaignId')->with($campaignId);
        $observerMock->expects($this->once())->method('_getLandingCookie')->willReturn($landingCookie);
        $quoteMock->expects($this->once())->method('setMailchimpLandingPage')->with($landingCookie);

        $observerMock->loadCustomerToQuote($eventObserverMock);

    }

    public function loadCustomerToQuoteDataProvider()
    {

        return array(
            array(array('actionName' => 'saveOrder')),
            array(array('actionName' => 'savePayment')),
            array(array('actionName' => 'saveShippingMethod')),
            array(array('actionName' => 'saveBilling')),
            array(array('actionName' => null))
        );
    }

    public function testItemCancel()
    {
        $isBundle = false;
        $isConf = false;
        $isEcomEnabled = 1;
        $storeId = 1;
        $productId = 1;
        $mailchimpStoreId = '6167259961c475fef8523e39ef1784e8';

        $observerMock = $this->getMockBuilder(Varien_Event_Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getEvent'))
            ->getMock();

        $eventObserverMock = $this->getMockBuilder(Varien_Event::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getItem'))
            ->getMock();

        $mailchimpObserverMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(array('makeHelper', 'makeApiProduct', 'isBundleItem', 'isConfigurableItem'))
            ->getMock();

        $itemMock = $this->getMockBuilder(Mage_Sales_Model_Order_Item::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getStoreId', 'getProductId'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('isEcomSyncDataEnabled', 'getMCStoreId'))
            ->getMock();

        $apiProductsMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Products::class)
            ->disableOriginalConstructor()
            ->setMethods(array('update'))
            ->getMock();

        $observerMock->expects($this->once())->method('getEvent')->willReturn($eventObserverMock);
        $eventObserverMock->expects($this->once())->method('getItem')->willReturn($itemMock);
        $mailchimpObserverMock->expects($this->once())->method('makeHelper')->willReturn($helperMock);
        $itemMock->expects($this->once())->method('getStoreId')->willReturn($storeId);
        $helperMock->expects($this->once())->method('isEcomSyncDataEnabled')->with($storeId)->willReturn($isEcomEnabled);
        $mailchimpObserverMock->expects($this->once())->method('makeApiProduct')->willReturn($apiProductsMock);
        $mailchimpObserverMock->expects($this->once())->method('isBundleItem')->with($itemMock)->willReturn($isBundle);
        $mailchimpObserverMock->expects($this->once())->method('isConfigurableItem')->with($itemMock)->willReturn($isConf);
        $itemMock->expects($this->once())->method('getProductId')->willReturn($productId);
        $helperMock->expects($this->once())->method('getMCStoreId')->with($storeId)->willReturn($mailchimpStoreId);
        $apiProductsMock->expects($this->once())->method('update')->with($productId, $mailchimpStoreId);

        $mailchimpObserverMock->itemCancel($observerMock);
    }

    public function testHandleCustomerGroupsIsSubscribed()
    {
        $params = array();
        $storeId = 1;
        $customerId = 15;
        $subscriberEmail = 'luciaines+testHandelCustomerGroups@ebizmarts.com';

        $mailchimpObserverMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(array('makeHelper', 'getSubscriberModel'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('saveInterestGroupData'))
            ->getMock();

        $subbscriberModelMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Subscriber::class)
            ->setMethods(array('loadByEmail'))
            ->getMock();

        $subscriberMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Subscriber::class)
            ->setMethods(array('getId'))
            ->getMock();

        $mailchimpObserverMock->expects($this->once())->method('makeHelper')->willReturn($helperMock);
        $mailchimpObserverMock->expects($this->once())->method('getSubscriberModel')->willReturn($subbscriberModelMock);

        $helperMock->expects($this->once())->method('saveInterestGroupData')->with($params, $storeId, $customerId, $subscriberMock);

        $subbscriberModelMock->expects($this->once())->method('loadByEmail')->with($subscriberEmail)->willReturn($subscriberMock);

        $subscriberMock->expects($this->once())->method('getId')->willReturn(true);

        $mailchimpObserverMock->handleCustomerGroups($subscriberEmail, $params, $storeId, $customerId);
    }

    public function testHandleCustomerGroupsIsNotSubcribedFromAdmin()
    {
        $customerId = 15;
        $subscriberEmail = 'luciaines+testHandelCustomerGroups@ebizmarts.com';
        $params = array('form_key' => 'Pm5pxh17N9Z9AINN', 'customer_id' => $customerId, 'group' => array('e939299a7d' => 'e939299a7d', '3dd23446e4' => '3dd23446e4', 'a6c3c332bf' => 'a6c3c332bf'));
        $storeId = 1;
        $groups = array('d46296f47c' => array ('3dd23446e4' => '3dd23446e4', 'a6c3c332bf' => 'a6c3c332bf'));

        $mailchimpObserverMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(array('makeHelper', 'getSubscriberModel', 'getWarningMessageAdminHtmlSession'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('saveInterestGroupData', 'getInterestGroupsIfAvailable'))
            ->getMock();

        $subbscriberModelMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Subscriber::class)
            ->setMethods(array('loadByEmail'))
            ->getMock();

        $subscriberMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Subscriber::class)
            ->setMethods(array('getId'))
            ->getMock();

        $mailchimpObserverMock->expects($this->once())->method('makeHelper')->willReturn($helperMock);
        $mailchimpObserverMock->expects($this->once())->method('getSubscriberModel')->willReturn($subbscriberModelMock);
        $mailchimpObserverMock->expects($this->once())->method('getWarningMessageAdminHtmlSession')->with($helperMock);

        $helperMock->expects($this->once())->method('saveInterestGroupData')->with($params, $storeId, $customerId);
        $helperMock->expects($this->once())->method('getInterestGroupsIfAvailable')->with($params)->willReturn($groups);

        $subbscriberModelMock->expects($this->once())->method('loadByEmail')->with($subscriberEmail)->willReturn($subscriberMock);

        $subscriberMock->expects($this->once())->method('getId')->willReturn(false);

        $mailchimpObserverMock->handleCustomerGroups($subscriberEmail, $params, $storeId, $customerId);
    }

    public function testHandleCustomerGroupsIsNotSubcribedFromFrontEnd()
    {
        $params = array();
        $storeId = 1;
        $customerId = 15;
        $subscriberEmail = 'luciaines+testHandelCustomerGroups@ebizmarts.com';

        $mailchimpObserverMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(array('makeHelper', 'getSubscriberModel'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('saveInterestGroupData'))
            ->getMock();

        $subbscriberModelMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Subscriber::class)
            ->setMethods(array('loadByEmail'))
            ->getMock();

        $subscriberMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Subscriber::class)
            ->setMethods(array('getId'))
            ->getMock();

        $mailchimpObserverMock->expects($this->once())->method('makeHelper')->willReturn($helperMock);
        $mailchimpObserverMock->expects($this->once())->method('getSubscriberModel')->willReturn($subbscriberModelMock);

        $helperMock->expects($this->once())->method('saveInterestGroupData')->with($params, $storeId, $customerId);

        $subbscriberModelMock->expects($this->once())->method('loadByEmail')->with($subscriberEmail)->willReturn($subscriberMock);

        $subscriberMock->expects($this->once())->method('getId')->willReturn(false);

        $mailchimpObserverMock->handleCustomerGroups($subscriberEmail, $params, $storeId, $customerId);
    }

    public function testAddOrderViewMonkey()
    {
        $html = '';
        $storeId = 1;

        $mailchimpObserverMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Observer::class)
            ->setMethods(array('makeHelper'))
            -> getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->setMethods(array('isEcomSyncDataEnabled'))
            ->getMock();

        $observerMock = $this->getMockBuilder(Varien_Event_Observer::class)
            ->setMethods(array('getBlock', 'getTransport'))
            ->getMock();

        $blockMock = $this->getMockBuilder(Mage_Core_Block_Abstract::class)
            ->setMethods(array('getNameInLayout', 'getOrder', 'getChild'))
            ->getMock();

        $transportMock = $this->getMockBuilder(Varien_Event::class)
            ->setMethods(array('getHtml', 'setHtml'))
            ->getMock();

        $orderMock = $this->getMockBuilder(Mage_Sales_Model_Order::class)
            ->setMethods(array('getStoreId'))
            ->getMock();

        $childMock = $this->getMockBuilder(Mage_Core_Helper_String::class)
            ->setMethods(array('toHtml'))
            ->getMock();

        $mailchimpObserverMock->expects($this->once())->method('makeHelper')->willReturn($helperMock);

        $helperMock->expects($this->once())->method('isEcomSyncDataEnabled')->with($storeId)->willReturn(true);

        $observerMock->expects($this->once())->method('getBlock')->willReturn($blockMock);
        $observerMock->expects($this->once())->method('getTransport')->willReturn($transportMock);

        $blockMock->expects($this->once())->method('getNameInLayout')->willReturn('order_info');
        $blockMock->expects($this->once())->method('getOrder')->willReturn($orderMock);
        $blockMock->expects($this->once())->method('getChild')->with('mailchimp.order.info.monkey.block')->willReturn($childMock);

        $transportMock->expects($this->once())->method('getHtml')->willReturn($html);
        $transportMock->expects($this->once())->method('setHtml')->with($html);

        $orderMock->expects($this->once())->method('getStoreId')->willReturn($storeId);

        $childMock->expects($this->once())->method('toHtml')->willReturn($html);

        $mailchimpObserverMock->addOrderViewMonkey($observerMock);
    }

    public function testCleanProductImagesCacheAfter()
    {
        $message = 'Image cache has been flushed please resend the products in order to update image URL.';
        $configValues = array(array(Ebizmarts_MailChimp_Model_Config::PRODUCT_IMAGE_CACHE_FLUSH, 1));
        $default = 'default';

        $mailchimpObserverMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Observer::class)
            ->setMethods(array('makeHelper'))
            -> getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->setMethods(array('saveMailchimpConfig', 'addAdminWarning'))
            ->getMock();

        $observerMock = $this->getMockBuilder(Varien_Event_Observer::class)
            ->setMethods(array())
            ->getMock();


        $mailchimpObserverMock->expects($this->once())->method('makeHelper')->willReturn($helperMock);

        $helperMock->expects($this->once())->method('saveMailchimpConfig')->with($configValues, 0, $default);
        $helperMock->expects($this->once())->method('addAdminWarning')->with($message);

        $mailchimpObserverMock->cleanProductImagesCacheAfter($observerMock);

    }

    public function testProductSaveAfter()
    {
        $productId = 907;
        $type = Ebizmarts_MailChimp_Model_Config::IS_PRODUCT;
        $mailchimpStoreId = '19d457ff95f1f1e710b502f35041e05f';
        $ecommEnabled = true;
        $storeId = 1;
        $isMarkedAsDeleted = 1;
        $storesIds = array(1);
        $status = array($productId => 1);

        $mailchimpObserverMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Observer::class)
            ->setMethods(array('makeHelper', 'makeApiProduct', 'getCatalogProductStatusModel'))
            -> getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->setMethods(array('getMageApp', 'isEcommerceEnabled', 'getEcommerceSyncDataItem', 'getMCStoreId'))
            ->getMock();

        $productMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Products::class)
            ->setMethods(array('getId'))
            ->getMock();

        $productApiMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Products::class)
            ->setMethods(array())
            ->getMock();

        $mageCoreModelAppMock = $this->getMockBuilder(Mage_Core_Model_App::class)
            ->setMethods(array('getStores'))
            ->getMock();

        $dataProductMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Ecommercesyncdata::class)
            ->setMethods(array('delete', 'getMailchimpSyncDeleted'))
            ->getMock();

        $productStatusMock = $this->getMockBuilder(Mage_Catalog_Model_Resource_Product_Status::class)
            ->setMethods(array('getProductStatus'))
            ->getMock();

        $observerMock = $this->getMockBuilder(Varien_Event_Observer::class)
            ->setMethods(array('getEvent'))
            ->getMock();

        $storesMock = $this->getMockBuilder(ArrayObject::class)
            ->setMethods(array('getIterator'))
            ->getMock();

        $eventObserverMock = $this->getMockBuilder(Varien_Event::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getProduct'))
            ->getMock();

        $mailchimpObserverMock->expects($this->once())
            ->method('makeHelper')
            ->willReturn($helperMock);
        $mailchimpObserverMock->expects($this->once())
            ->method('makeApiProduct')
            ->willReturn($productApiMock);
        $mailchimpObserverMock->expects($this->once())
            ->method('getCatalogProductStatusModel')
            ->willReturn($productStatusMock);

        $observerMock->expects($this->once())
            ->method('getEvent')
            ->willReturn($eventObserverMock);

        $eventObserverMock->expects($this->once())
            ->method('getProduct')
            ->willReturn($productMock);

        $productStatusMock->expects($this->once())
            ->method('getProductStatus')
            ->with($productId, $storeId)
            ->willReturn($status);

        $helperMock->expects($this->once())
            ->method('getMageApp')
            ->willReturn($mageCoreModelAppMock);
        $helperMock->expects($this->once())
            ->method('isEcommerceEnabled')
            ->willReturn($ecommEnabled);
        $helperMock->expects($this->once())
            ->method('getEcommerceSyncDataItem')
            ->with($productId, $type, $mailchimpStoreId)
            ->willReturn($dataProductMock);
        $helperMock->expects($this->once())
            ->method('getMCStoreId')
            ->with($storeId)
            ->willReturn($mailchimpStoreId);

        $dataProductMock->expects($this->once())
            ->method('getMailchimpSyncDeleted')
            ->willReturn($isMarkedAsDeleted);
        $dataProductMock->expects($this->once())
            ->method('delete');

        $mageCoreModelAppMock->expects($this->once())
            ->method('getStores')
            ->willReturn($storesMock);

        $storesMock->expects($this->once())
            ->method('getIterator')
            ->willReturn(new ArrayIterator($storesIds));

        $productMock->expects($this->exactly(3))
            ->method('getId')
            ->willReturnOnConsecutiveCalls(
                $productId,
                $productId,
                $productId);

        $mailchimpObserverMock->productSaveAfter($observerMock);
    }
}

