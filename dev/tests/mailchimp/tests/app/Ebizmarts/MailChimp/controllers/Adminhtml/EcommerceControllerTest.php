<?php

require_once BP . DS . 'app/code/community/Ebizmarts/MailChimp/controllers/Adminhtml/EcommerceController.php';

class Ebizmarts_MailChimp_Adminhtml_EcommerceControllerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Ebizmarts_MailChimp_Adminhtml_EcommerceController $ecommerceController
     */
    private $ecommerceController;

    public function setUp()
    {
        Mage::app('default');
        $this->ecommerceController = $this->getMockBuilder(Ebizmarts_MailChimp_Adminhtml_EcommerceController::class);
    }

    public function tearDown()
    {
        $this->ecommerceController = null;
    }

    public function testResetLocalErrorsAction()
    {
        $paramScope = 'scope';
        $paramScopeId = 'scope_id';
        $scope = 'default';
        $scopeId = 0;
        $storeId = 1;
        $result = 1;

        $ecommerceControllerMock = $this->ecommerceController
            ->disableOriginalConstructor()
            ->setMethods(array('makeHelper'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getMageApp', 'resetErrors'))
            ->getMock();

        $mageAppMock = $this->getMockBuilder(Mage_Core_Model_App::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getRequest', 'getStores', 'getResponse'))
            ->getMock();

        $requestMock = $this->getMockBuilder(Mage_Core_Controller_Request_Http::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getParam'))
            ->getMock();

        $responseMock = $this->getMockBuilder(Mage_Core_Controller_Response_Http::class)
            ->disableOriginalConstructor()
            ->setMethods(array('setBody'))
            ->getMock();

        $storeCollectionMock = $this->getMockBuilder(Mage_Core_Model_Resource_Store_Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $storeMock = $this->getMockBuilder(Mage_Core_Model_Store::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getId'))
            ->getMock();

        $stores = array();
        $stores[] = $storeMock;

        $ecommerceControllerMock->expects($this->once())->method('makeHelper')->willReturn($helperMock);

        $helperMock->expects($this->once())->method('getMageApp')->willReturn($mageAppMock);

        $mageAppMock->expects($this->once())->method('getRequest')->willReturn($requestMock);

        $requestMock->expects($this->exactly(2))->method('getParam')->withConsecutive(
            array($paramScope),
            array($paramScopeId)
        )
            ->willReturnOnConsecutiveCalls(
                $scope,
                $scopeId
            );

        $mageAppMock->expects($this->once())->method('getStores')->willReturn($storeCollectionMock);

        $storeCollectionMock->expects($this->once())->method('getIterator')->willReturn(new ArrayIterator($stores));

        $storeMock->expects($this->once())->method('getId')->willReturn($storeId);

        $helperMock->expects($this->exactly(2))->method('resetErrors')->withConsecutive(
            array($storeId),
            array($scopeId, $scope)
        );

        $mageAppMock->expects($this->once())->method('getResponse')->willReturn($responseMock);

        $responseMock->expects($this->once())->method('setBody')->with($result);

        $ecommerceControllerMock->resetLocalErrorsAction();
    }

    public function testResendEcommerceDataAction()
    {
        $paramFilters = 'filter';
        $scope = 'stores';
        $scopeId = 1;
        $filter = Ebizmarts_MailChimp_Model_Config::IS_ORDER;

        $result = 'Redirecting... <script type="text/javascript">window.top.location.reload();</script>';

        $ecommerceControllerMock = $this->ecommerceController
            ->disableOriginalConstructor()
            ->setMethods(array('makeHelper', 'getRequest', 'addSuccess'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getMageApp', 'resendMCEcommerceData', 'getCurrentScope'))
            ->getMock();

        $mageAppMock = $this->getMockBuilder(Mage_Core_Model_App::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getResponse'))
            ->getMock();

        $requestMock = $this->getMockBuilder(Mage_Core_Controller_Request_Http::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getParam'))
            ->getMock();

        $responseMock = $this->getMockBuilder(Mage_Core_Controller_Response_Http::class)
            ->disableOriginalConstructor()
            ->setMethods(array('setBody'))
            ->getMock();

        $mageAppMock->expects($this->once())->method('getResponse')->willReturn($responseMock);

        $helperMock->expects($this->once())->method('getMageApp')->willReturn($mageAppMock);
        $helperMock->expects($this->once())->method('getCurrentScope')->willReturn(array('scope_id'=>$scopeId, 'scope'=>$scope));
        $helperMock->expects($this->once())->method('resendMCEcommerceData')->with($scopeId, $scope, $filter)->willReturnSelf();

        $requestMock->expects($this->once())->method('getParam')->with($paramFilters)->willReturn($filter);
        $responseMock->expects($this->once())->method('setBody')->with($result);

        $ecommerceControllerMock->expects($this->once())->method('makeHelper')->willReturn($helperMock);
        $ecommerceControllerMock->expects($this->once())->method('getRequest')->willReturn($requestMock);
        $ecommerceControllerMock->expects($this->once())->method('addSuccess');
        $ecommerceControllerMock->resendEcommerceDataAction();
    }

    public function testCreateMergeFieldsAction()
    {
        $paramScope = 'scope';
        $paramScopeId = 'scope_id';
        $scope = 'stores';
        $scopeId = 1;
        $result = 1;

        $ecommerceControllerMock = $this->ecommerceController
            ->disableOriginalConstructor()
            ->setMethods(array('makeHelper'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getMageApp', 'isSubscriptionEnabled', 'createMergeFields'))
            ->getMock();

        $mageAppMock = $this->getMockBuilder(Mage_Core_Model_App::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getRequest', 'getResponse'))
            ->getMock();

        $requestMock = $this->getMockBuilder(Mage_Core_Controller_Request_Http::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getParam'))
            ->getMock();

        $responseMock = $this->getMockBuilder(Mage_Core_Controller_Response_Http::class)
            ->disableOriginalConstructor()
            ->setMethods(array('setBody'))
            ->getMock();

        $ecommerceControllerMock->expects($this->once())->method('makeHelper')->willReturn($helperMock);

        $helperMock->expects($this->once())->method('getMageApp')->willReturn($mageAppMock);

        $mageAppMock->expects($this->once())->method('getRequest')->willReturn($requestMock);

        $requestMock->expects($this->exactly(2))->method('getParam')->withConsecutive(
            array($paramScope),
            array($paramScopeId)
        )
            ->willReturnOnConsecutiveCalls(
                $scope,
                $scopeId
            );

        $helperMock->expects($this->once())->method('isSubscriptionEnabled')->with($scopeId, $scope)->willReturn(true);
        $helperMock->expects($this->once())->method('createMergeFields')->with($scopeId, $scope);

        $mageAppMock->expects($this->once())->method('getResponse')->willReturn($responseMock);

        $responseMock->expects($this->once())->method('setBody')->with($result);

        $ecommerceControllerMock->createMergeFieldsAction();
    }
}
