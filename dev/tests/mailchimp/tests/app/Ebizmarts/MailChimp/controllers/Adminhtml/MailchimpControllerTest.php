<?php

require_once BP . DS . 'app/code/community/Ebizmarts/MailChimp/controllers/Adminhtml/MailchimpController.php';

class Ebizmarts_MailChimp_Adminhtml_MailchimpControllerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Ebizmarts_MailChimp_Adminhtml_MailchimpController $mailchimpController
     */
    private $mailchimpController;

    public function setUp()
    {
        Mage::app('default');
        $this->mailchimpController = $this->getMockBuilder(Ebizmarts_MailChimp_Adminhtml_MailchimpController::class);
    }

    public function tearDown()
    {
        $this->mailchimpController = null;
    }

    public function testIndexAction()
    {
        $customerId = 1;
        $type = 'mailchimp/adminhtml_customer_edit_tab_mailchimp';
        $name = 'admin.customer.mailchimp';
        $result = '<body></body>';

        $mailchimpControllerMock = $this->mailchimpController
            ->disableOriginalConstructor()
            ->setMethods(array('getRequest', 'getResponse', 'getLayout', 'getHtml'))
            ->getMock();

        $requestMock = $this->getMockBuilder(Mage_Core_Controller_Request_Http::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getParam'))
            ->getMock();

        $responseMock = $this->getMockBuilder(Mage_Core_Controller_Response_Http::class)
            ->disableOriginalConstructor()
            ->setMethods(array('setBody'))
            ->getMock();

        $layoutMock = $this->getMockBuilder(Mage_Core_Model_Layout::class)
            ->disableOriginalConstructor()
            ->setMethods(array('createBlock'))
            ->getMock();

        $blockMock = $this->getMockBuilder(Ebizmarts_MailChimp_Block_Adminhtml_Customer_Edit_Tab_Mailchimp::class)
            ->disableOriginalConstructor()
            ->setMethods(array('setCustomerId', 'setUseAjax', 'toHtml'))
            ->getMock();

        $mailchimpControllerMock->expects($this->once())->method('getRequest')->willReturn($requestMock);

        $requestMock->expects($this->once())->method('getParam')->with('id')->willReturn($customerId);

        $mailchimpControllerMock->expects($this->once())->method('getLayout')->willReturn($layoutMock);

        $layoutMock->expects($this->once())->method('createBlock')->with($type, $name)->willReturn($blockMock);

        $blockMock->expects($this->once())->method('setCustomerId')->with($customerId)->willReturnSelf();
        $blockMock->expects($this->once())->method('setUseAjax')->with(true)->willReturnSelf();

        $mailchimpControllerMock->expects($this->once())->method('getHtml')->with($blockMock)->willReturn($result);

        $mailchimpControllerMock->expects($this->once())->method('getResponse')->willReturn($responseMock);
        $responseMock->expects($this->once())->method('setBody')->with($result);

        $mailchimpControllerMock->indexAction();
    }

    public function testResendSubscribersAction()
    {
        $paramScope = 'scope';
        $paramScopeId = 'scope_id';
        $scope = 'stores';
        $scopeId = 1;
        $result = 1;

        $mailchimpControllerMock = $this->mailchimpController
            ->disableOriginalConstructor()
            ->setMethods(array('makeHelper'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getMageApp', 'resendSubscribers'))
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

        $mailchimpControllerMock->expects($this->once())->method('makeHelper')->willReturn($helperMock);

        $helperMock->expects($this->once())->method('getMageApp')->willReturn($mageAppMock);

        $mageAppMock->expects($this->once())->method('getRequest')->willReturn($requestMock);

        $requestMock->expects($this->exactly(2))->method('getParam')->withConsecutive(
            array($paramScope),
            array($paramScopeId))
            ->willReturnOnConsecutiveCalls(
                $scope,
                $scopeId
            );

        $helperMock->expects($this->once())->method('resendSubscribers')->with($scopeId, $scope);
        $mageAppMock->expects($this->once())->method('getResponse')->willReturn($responseMock);

        $responseMock->expects($this->once())->method('setBody')->with($result);

        $mailchimpControllerMock->resendSubscribersAction();
    }

    public function testCreateWebhookAction()
    {
        $paramScope = 'scope';
        $paramScopeId = 'scope_id';
        $scope = 'stores';
        $scopeId = 1;
        $listId = 'ca841a1103';
        $message = 1;

        $mailchimpControllerMock = $this->mailchimpController
            ->disableOriginalConstructor()
            ->setMethods(array('makeHelper'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getMageApp', 'createNewWebhook', 'getGeneralList'))
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

        $mailchimpControllerMock->expects($this->once())->method('makeHelper')->willReturn($helperMock);

        $helperMock->expects($this->once())->method('getGeneralList')->with($scopeId)->willReturn($listId);
        $helperMock->expects($this->once())->method('getMageApp')->willReturn($mageAppMock);

        $mageAppMock->expects($this->once())->method('getRequest')->willReturn($requestMock);

        $requestMock->expects($this->exactly(2))->method('getParam')->withConsecutive(
            array($paramScope),
            array($paramScopeId))
            ->willReturnOnConsecutiveCalls(
                $scope,
                $scopeId
            );

        $helperMock->expects($this->once())->method('createNewWebhook')->with($scopeId, $scope, $listId)->willReturn($message);
        $mageAppMock->expects($this->once())->method('getResponse')->willReturn($responseMock);

        $responseMock->expects($this->once())->method('setBody')->with($message);

        $mailchimpControllerMock->createWebhookAction();
    }
}
