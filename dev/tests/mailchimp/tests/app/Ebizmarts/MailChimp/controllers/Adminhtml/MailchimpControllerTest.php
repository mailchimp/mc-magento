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
            ->setMethods(array('getHelper'))
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

        $mailchimpControllerMock->expects($this->once())->method('getHelper')->willReturn($helperMock);

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
            ->setMethods(array('getHelper'))
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

        $mailchimpControllerMock->expects($this->once())->method('getHelper')->willReturn($helperMock);

        $helperMock->expects($this->once())->method('getGeneralList')->with($scopeId)->willReturn($listId);
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

        $helperMock->expects($this->once())->method('createNewWebhook')->with($scopeId, $scope, $listId)->willReturn($message);
        $mageAppMock->expects($this->once())->method('getResponse')->willReturn($responseMock);

        $responseMock->expects($this->once())->method('setBody')->with($message);

        $mailchimpControllerMock->createWebhookAction();
    }

    public function testGetStoresAction()
    {
        $apiKeyParam = 'api_key';
        $apiKey = 'a1s2d3f4g5h6j7k8l9z1x2c3v4v4-us1';

        $data = array(array('id' => '', 'name' => '--- Select a Mailchimp Store ---'), array('id' => 'a1s2d3f4g5h6j7k8l9p0', 'name' => 'Madison Island - English'));
        $jsonData = '[{"id":"","name":"--- Select a Mailchimp Store ---"},{"id":"a1s2d3f4g5h6j7k8l9p0","name":"Madison Island - English"}]';

        $mailchimpControllerMock = $this->mailchimpController
            ->disableOriginalConstructor()
            ->setMethods(array('getRequest', 'getResponse', 'getSourceStoreOptions', 'getHelper'))
            ->getMock();

        $requestMock = $this->getMockBuilder(Mage_Core_Controller_Request_Http::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getParam'))
            ->getMock();

        $responseMock = $this->getMockBuilder(Mage_Core_Controller_Response_Http::class)
            ->disableOriginalConstructor()
            ->setMethods(array('setHeader', 'setBody'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('isApiKeyObscure'))
            ->getMock();

        $mailchimpControllerMock->expects($this->once())->method('getHelper')->willReturn($helperMock);
        $mailchimpControllerMock->expects($this->once())->method('getRequest')->willReturn($requestMock);

        $requestMock->expects($this->once())->method('getParam')->with($apiKeyParam)->willReturn($apiKey);
        $helperMock->expects($this->once())->method('isApiKeyObscure')->with($apiKey)->willReturn(false);

        $mailchimpControllerMock->expects($this->once())->method('getSourceStoreOptions')->with($apiKey)->willReturn($data);
        $mailchimpControllerMock->expects($this->once())->method('getResponse')->willReturn($responseMock);

        $responseMock->expects($this->once())->method('setHeader')->with('Content-type', 'application/json');
        $responseMock->expects($this->once())->method('setBody')->with($jsonData);

        $mailchimpControllerMock->getStoresAction();
    }

    public function testGetInfoAction()
    {
        $apiKeyParam = 'api_key';
        $apiKey = 'a1s2d3f4g5h6j7k8l9z1x2c3v4v4-us1';
        $storeIdParam = 'mailchimp_store_id';
        $mcStoreId = 'q1w2e3r4t5y6u7i8o9p0';
        $syncDate = "2019-02-01 20:00:05";
        $optionSyncFlag = array(
            'value' => Ebizmarts_MailChimp_Model_System_Config_Source_Account::SYNC_LABEL_KEY,
            'label' => 'Initial sync: '.$syncDate
        );
        $liElement = "<li>Initial sync: <span style='color: forestgreen;font-weight: bold;'>$syncDate</span></li>";
        $liElementEscaped = "<li>Initial sync: <span style='color: forestgreen;font-weight: bold;'>$syncDate<\/span><\/li>";
        $data = array(
            array(
                'value' => Ebizmarts_MailChimp_Model_System_Config_Source_Account::USERNAME_KEY,
                'label' => 'Username: Ebizmarts Corp.'
            ), array(
                'value' => Ebizmarts_MailChimp_Model_System_Config_Source_Account::TOTAL_ACCOUNT_SUB_KEY,
                'label' => 'Total Account Subscribers: 104'
            ), array(
                'value' => Ebizmarts_MailChimp_Model_System_Config_Source_Account::TOTAL_LIST_SUB_KEY,
                'label' => 'Total List Subscribers: 18'
            ), array(
                'value' => Ebizmarts_MailChimp_Model_System_Config_Source_Account::STORENAME_KEY,
                'label' => 'Ecommerce Data uploaded to MailChimp store Madison Island - English:'
            ),
            $optionSyncFlag,
            array(
                'value' => Ebizmarts_MailChimp_Model_System_Config_Source_Account::TOTAL_CUS_KEY,
                'label' => '  Total Customers: 10'
            ), array(
                'value' => Ebizmarts_MailChimp_Model_System_Config_Source_Account::TOTAL_PRO_KEY,
                'label' => '  Total Products: 10'
            ), array(
                'value' => Ebizmarts_MailChimp_Model_System_Config_Source_Account::TOTAL_ORD_KEY,
                'label' => '  Total Orders: 10'
            ), array(
                'value' => Ebizmarts_MailChimp_Model_System_Config_Source_Account::TOTAL_QUO_KEY,
                'label' => '  Total Carts: 10'
            )
        );
        $jsonData = '[{"value":'.Ebizmarts_MailChimp_Model_System_Config_Source_Account::USERNAME_KEY.',"label":"Username: Ebizmarts Corp."},'.
            '{"value":'.Ebizmarts_MailChimp_Model_System_Config_Source_Account::TOTAL_ACCOUNT_SUB_KEY.',"label":"Total Account Subscribers: 104"},'.
            '{"value":'.Ebizmarts_MailChimp_Model_System_Config_Source_Account::TOTAL_LIST_SUB_KEY.',"label":"Total List Subscribers: 18"},'.
            '{"value":'.Ebizmarts_MailChimp_Model_System_Config_Source_Account::STORENAME_KEY.',"label":"Ecommerce Data uploaded to MailChimp store Madison Island - English:"},'.
            '{"value":'.Ebizmarts_MailChimp_Model_System_Config_Source_Account::SYNC_LABEL_KEY.',"label":"'.$liElementEscaped.'"},'.
            '{"value":'.Ebizmarts_MailChimp_Model_System_Config_Source_Account::TOTAL_CUS_KEY.',"label":"  Total Customers: 10"},'.
            '{"value":'.Ebizmarts_MailChimp_Model_System_Config_Source_Account::TOTAL_PRO_KEY.',"label":"  Total Products: 10"},'.
            '{"value":'.Ebizmarts_MailChimp_Model_System_Config_Source_Account::TOTAL_ORD_KEY.',"label":"  Total Orders: 10"},'.
            '{"value":'.Ebizmarts_MailChimp_Model_System_Config_Source_Account::TOTAL_QUO_KEY.',"label":"  Total Carts: 10"}]';

        $mailchimpControllerMock = $this->mailchimpController
            ->disableOriginalConstructor()
            ->setMethods(array('getHelper', 'getRequest', 'getSourceAccountInfoOptions', 'getResponse'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getSyncFlagDataHtml'))
            ->getMock();

        $requestMock = $this->getMockBuilder(Mage_Core_Controller_Request_Http::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getParam'))
            ->getMock();

        $responseMock = $this->getMockBuilder(Mage_Core_Controller_Response_Http::class)
            ->disableOriginalConstructor()
            ->setMethods(array('setHeader', 'setBody'))
            ->getMock();

        $mailchimpControllerMock->expects($this->once())->method('getHelper')->willReturn($helperMock);
        $mailchimpControllerMock->expects($this->once())->method('getRequest')->willReturn($requestMock);

        $requestMock->expects($this->exactly(2))->method('getParam')->withConsecutive(
            array($storeIdParam),
            array($apiKeyParam)
        )->willReturnOnConsecutiveCalls(
            $mcStoreId,
            $apiKey
        );

        $mailchimpControllerMock->expects($this->once())->method('getSourceAccountInfoOptions')->with($apiKey, $mcStoreId)->willReturn($data);

        $helperMock->expects($this->once())->method('getSyncFlagDataHtml')->with($optionSyncFlag, "")->willReturn($liElement);

        $mailchimpControllerMock->expects($this->once())->method('getResponse')->willReturn($responseMock);

        $responseMock->expects($this->once())->method('setHeader')->with('Content-type', 'application/json');
        $responseMock->expects($this->once())->method('setBody')->with($jsonData);

        $mailchimpControllerMock->getInfoAction();
    }

    public function testGetListAction()
    {
        $apiKeyParam = 'api_key';
        $apiKey = 'a1s2d3f4g5h6j7k8l9z1x2c3v4v4-us1';
        $storeIdParam = 'mailchimp_store_id';
        $mcStoreId = 'q1w2e3r4t5y6u7i8o9p0';
        $listId = 'a1s2d3f4g5';

        $data = array(array('id' => $listId, 'name' => 'Newsletter'));
        $jsonData = '[{"id":"'.$listId.'","name":"Newsletter"}]';

        $mailchimpControllerMock = $this->mailchimpController
            ->disableOriginalConstructor()
            ->setMethods(array('getRequest', 'getSourceListOptions', 'getResponse', 'getHelper'))
            ->getMock();

        $requestMock = $this->getMockBuilder(Mage_Core_Controller_Request_Http::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getParam'))
            ->getMock();

        $responseMock = $this->getMockBuilder(Mage_Core_Controller_Response_Http::class)
            ->disableOriginalConstructor()
            ->setMethods(array('setHeader', 'setBody'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('isApiKeyObscure'))
            ->getMock();

        $mailchimpControllerMock->expects($this->once())->method('getHelper')->willReturn($helperMock);
        $mailchimpControllerMock->expects($this->once())->method('getRequest')->willReturn($requestMock);

        $requestMock->expects($this->exactly(2))->method('getParam')->withConsecutive(
            array($apiKeyParam),
            array($storeIdParam)
        )->willReturnOnConsecutiveCalls(
            $apiKey,
            $mcStoreId
        );

        $helperMock->expects($this->once())->method('isApiKeyObscure')->with($apiKey)->willReturn(false);

        $mailchimpControllerMock->expects($this->once())->method('getSourceListOptions')->with($apiKey, $mcStoreId)->willReturn($data);
        $mailchimpControllerMock->expects($this->once())->method('getResponse')->willReturn($responseMock);

        $responseMock->expects($this->once())->method('setHeader')->with('Content-type', 'application/json');
        $responseMock->expects($this->once())->method('setBody')->with($jsonData);

        $mailchimpControllerMock->getListAction();
    }

    public function testGetInterestAction()
    {
        $apiKeyParam = 'api_key';
        $apiKey = 'a1s2d3f4g5h6j7k8l9z1x2c3v4v4-us1';
        $listIdParam = 'list_id';
        $listId = 'a1s2d3f4g5';

        $data = array(
            array('value' => 'bc15dbe6a5', 'label' => 'Checkboxes'),
            array('value' => '2a2f23d671', 'label' => 'DropDown')
        );
        $jsonData = '[{"value":"bc15dbe6a5","label":"Checkboxes"},{"value":"2a2f23d671","label":"DropDown"}]';

        $mailchimpControllerMock = $this->mailchimpController
            ->disableOriginalConstructor()
            ->setMethods(array('getRequest', 'getSourceInterestOptions', 'getResponse', 'getHelper'))
            ->getMock();

        $requestMock = $this->getMockBuilder(Mage_Core_Controller_Request_Http::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getParam'))
            ->getMock();

        $responseMock = $this->getMockBuilder(Mage_Core_Controller_Response_Http::class)
            ->disableOriginalConstructor()
            ->setMethods(array('setHeader', 'setBody'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('isApiKeyObscure'))
            ->getMock();

        $mailchimpControllerMock->expects($this->once())->method('getHelper')->willReturn($helperMock);
        $mailchimpControllerMock->expects($this->once())->method('getRequest')->willReturn($requestMock);

        $requestMock->expects($this->exactly(2))->method('getParam')->withConsecutive(
            array($apiKeyParam),
            array($listIdParam)
        )->willReturnOnConsecutiveCalls(
            $apiKey,
            $listId
        );

        $helperMock->expects($this->once())->method('isApiKeyObscure')->with($apiKey)->willReturn(false);

        $mailchimpControllerMock->expects($this->once())->method('getSourceInterestOptions')->with($apiKey, $listId)->willReturn($data);
        $mailchimpControllerMock->expects($this->once())->method('getResponse')->willReturn($responseMock);

        $responseMock->expects($this->once())->method('setHeader')->with('Content-type', 'application/json');
        $responseMock->expects($this->once())->method('setBody')->with($jsonData);

        $mailchimpControllerMock->getInterestAction();
    }
}
