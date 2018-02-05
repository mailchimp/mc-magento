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
        $mailchimpStoreIdsArray = array($scope.'_'.$scopeId => $mailchimpStoreId);
        /**
         * @var \Ebizmarts_MailChimp_Model_Observer $modelMock
         */
        $modelMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(array('makeHelper', 'makeApiProducts'))
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
        $modelMock->expects($this->once())->method('makeApiProducts')->willReturn($apiProductsMock);

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

    public function testFrontInitBefore()
    {
        $modelMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(array('makeHelper', 'markProductsAsModified', 'getConfig'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('wasProductImageCacheFlushed'))
            ->getMock();

        $configMock = $this->getMockBuilder(Mage_Core_Model_Config::class)
            ->disableOriginalConstructor()
            ->setMethods(array('deleteConfig', 'cleanCache'))
            ->getMock();

        $eventObserverMock = $this->getMockBuilder(Varien_Event_Observer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $modelMock->expects($this->once())->method('makeHelper')->willReturn($helperMock);
        $modelMock->expects($this->once())->method('markProductsAsModified');
        $modelMock->expects($this->once())->method('getConfig')->willReturn($configMock);

        $helperMock->expects($this->once())->method('wasProductImageCacheFlushed')->willReturn(1);

        $configMock->expects($this->once())->method('deleteConfig')->with(Ebizmarts_MailChimp_Model_Config::PRODUCT_IMAGE_CACHE_FLUSH, 'default', 0);
        $configMock->expects($this->once())->method('cleanCache');

        $modelMock->frontInitBefore($eventObserverMock);
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

        $storeArrayMock = $this->getMockBuilder(Mage_Core_Model_Resource_Store_Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $stores = array();
        $stores[] = $storeMock;

        $eventObserverMock->expects($this->once())->method('getGroup')->willReturn($groupMock);

        $groupMock->expects($this->once())->method('getStores')->willReturn($storeArrayMock);

        $storeArrayMock->expects($this->once())->method('getIterator')->willReturn(new ArrayIterator($stores));

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

}
