<?php

require_once BP . DS . 'app/code/community/Ebizmarts/MailChimp/controllers/Adminhtml/MailchimpstoresController.php';

class Ebizmarts_MailChimp_Adminhtml_MailchimpstoresControllerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Ebizmarts_MailChimp_Adminhtml_MailchimpstoresController $mailchimpstoresController
     */
    private $mailchimpstoresController;

    public function setUp()
    {
        Mage::app('default');
        $this->mailchimpstoresController = $this->getMockBuilder(Ebizmarts_MailChimp_Adminhtml_MailchimpstoresController::class);
    }

    public function tearDown()
    {
        $this->mailchimpstoresController = null;
    }

    public function testIndexAction()
    {
        $mailchimpstoresControllerMock = $this->mailchimpstoresController
            ->disableOriginalConstructor()
            ->setMethods(array('_loadStores', 'loadLayout', '_setActiveMenu', 'renderLayout'))
            ->getMock();

        $mailchimpstoresControllerMock->expects($this->once())->method('_loadStores');
        $mailchimpstoresControllerMock->expects($this->once())->method('loadLayout');
        $mailchimpstoresControllerMock->expects($this->once())->method('_setActiveMenu')->with('newsletter/mailchimp');
        $mailchimpstoresControllerMock->expects($this->once())->method('renderLayout');

        $mailchimpstoresControllerMock->indexAction();
    }

    public function testGridAction()
    {
        $mailchimpstoresControllerMock = $this->mailchimpstoresController
            ->disableOriginalConstructor()
            ->setMethods(array('loadLayout', 'renderLayout'))
            ->getMock();

        $mailchimpstoresControllerMock->expects($this->once())->method('loadLayout')->with(false);
        $mailchimpstoresControllerMock->expects($this->once())->method('renderLayout');

        $mailchimpstoresControllerMock->gridAction();
    }

    public function testEditAction()
    {
        $idParam = 'id';
        $id = 1;
        $urlPath = '*/*/save';
        $url = 'domain.com/mailchimp/mailchimpstores/save';

        $mailchimpstoresControllerMock = $this->mailchimpstoresController
            ->disableOriginalConstructor()
            ->setMethods(array('_title', 'getRequest', 'loadMailchimpStore', 'sessionregisterStore', '_initAction',
                '_addBreadcrumb', 'getLayout', 'getUrl', '_addContent', 'renderLayout'))
            ->getMock();

        $requestMock = $this->getMockBuilder(Mage_Core_Controller_Response_Http::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getParam'))
            ->getMock();

        $mailchimpStoreModelMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Stores::class)
            ->disableOriginalConstructor()
            ->getMock();

        $layoutMock = $this->getMockBuilder(Mage_Core_Model_Layout::class)
            ->disableOriginalConstructor()
            ->setMethods(array('createBlock'))
            ->getMock();

        $blockMock = $this->getMockBuilder(Mage_Core_Block_Abstract::class)
            ->disableOriginalConstructor()
            ->setMethods(array('setData'))
            ->getMock();

        $mailchimpstoresControllerMock->expects($this->exactly(2))->method('_title')->withConsecutive(
            array('Mailchimp'),
            array('Mailchimp Store')
        )->willReturnOnConsecutiveCalls(
            $mailchimpstoresControllerMock,
            $mailchimpstoresControllerMock
        );
        $mailchimpstoresControllerMock->expects($this->once())->method('getRequest')->willReturn($requestMock);

        $requestMock->expects($this->once())->method('getParam')->with($idParam)->willReturn($id);

        $mailchimpstoresControllerMock->expects($this->once())->method('loadMailchimpStore')->with($id)->willReturn($mailchimpStoreModelMock);
        $mailchimpstoresControllerMock->expects($this->once())->method('sessionregisterStore')->with($mailchimpStoreModelMock);
        $mailchimpstoresControllerMock->expects($this->once())->method('_initAction')->willReturnSelf();
        $mailchimpstoresControllerMock->expects($this->once())->method('_addBreadcrumb')->with('Edit Store', 'Edit Store')->willReturnSelf();
        $mailchimpstoresControllerMock->expects($this->once())->method('getLayout')->willReturn($layoutMock);
        $mailchimpstoresControllerMock->expects($this->once())->method('getUrl')->with($urlPath)->willReturn($url);

        $layoutMock->expects($this->once())->method('createBlock')->with('mailchimp/adminhtml_mailchimpstores_edit')->willReturn($blockMock);

        $blockMock->expects($this->once())->method('setData')->with('action', $url)->willReturnSelf();

        $mailchimpstoresControllerMock->expects($this->once())->method('_addContent')->with($blockMock)->willReturnSelf();
        $mailchimpstoresControllerMock->expects($this->once())->method('renderLayout')->willReturnSelf();

        $mailchimpstoresControllerMock->editAction();
    }

    public function testNewAction()
    {
        $mailchimpstoresControllerMock = $this->mailchimpstoresController
            ->disableOriginalConstructor()
            ->setMethods(array('_forward'))
            ->getMock();

        $mailchimpstoresControllerMock->expects($this->once())->method('_forward')->with('edit');

        $mailchimpstoresControllerMock->newAction();
    }

    public function testSaveAction()
    {
        $postData = array('address_address_one' => 'addressOne', 'address_address_two' => 'addressTwo',
            'address_city' => 'city', 'address_postal_code' => 'postCode', 'address_country_code' => 'countryCode',
            'email_address' => 'email@example.com', 'currency_code' => 'USD', 'primary_locale' => 'en_US',
            'phone' => '123456', 'name' => 'name', 'domain' => 'domain.com', 'storeid' => 1, 'apikey' => '');

        $mailchimpstoresControllerMock = $this->mailchimpstoresController
            ->disableOriginalConstructor()
            ->setMethods(array('getRequest', '_updateMailchimp', '_redirect', 'getHelper'))
            ->getMock();

        $requestMock = $this->getMockBuilder(Mage_Core_Controller_Response_Http::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getPost'))
            ->getMock();

        $mailchimpstoresControllerMock->expects($this->once())->method('getRequest')->willReturn($requestMock);

        $requestMock->expects($this->once())->method('getPost')->willReturn($postData);

        $mailchimpstoresControllerMock->expects($this->once())->method('_updateMailchimp')->with($postData);
        $mailchimpstoresControllerMock->expects($this->once())->method('_redirect')->with('*/*/index');

        $mailchimpstoresControllerMock->saveAction();
    }

    public function testGetstoresAction()
    {
        $apiKeyParam = 'api_key';
        $apiKey = 'a1s2d3f4g5h6j7k8l9z1x2c3v4b5-us1';
        $apiKeyEncrypted = '4rGjyBo/uKChzvu0bF3hjaMwfM503N3/+2fdRjdlAGo=';
        $mcLists = array(
            'lists' => array(array(
                'id' => 'a1s2d3f4g5',
                'name' => 'Newsletter',
                'stats' => array(
                    'member_count' => 18
                )
            ))
        );
        $jsonData = '{"a1s2d3f4g5":{"id":"a1s2d3f4g5","name":"Newsletter"}}';

        $mailchimpstoresControllerMock = $this->mailchimpstoresController
            ->disableOriginalConstructor()
            ->setMethods(array('getRequest', 'getMailchimpHelper', 'getResponse'))
            ->getMock();

        $requestMock = $this->getMockBuilder(Mage_Core_Controller_Request_Http::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getParam'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getApiByKey', 'decryptData'))
            ->getMock();

        $apiMock = $this->getMockBuilder(Ebizmarts_MailChimp::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getLists'))
            ->getMock();

        $listsMock = $this->getMockBuilder(MailChimp_Lists::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getLists'))
            ->getMock();

        $responseMock = $this->getMockBuilder(Mage_Core_Controller_Response_Http::class)
            ->disableOriginalConstructor()
            ->setMethods(array('setHeader', 'setBody'))
            ->getMock();

        $mailchimpstoresControllerMock->expects($this->once())->method('getRequest')->willReturn($requestMock);

        $requestMock->expects($this->once())->method('getParam')->with($apiKeyParam)->willReturn($apiKeyEncrypted);

        $mailchimpstoresControllerMock->expects($this->once())->method('getMailchimpHelper')->willReturn($helperMock);

        $helperMock->expects($this->once())->method('decryptData')->with($apiKeyEncrypted)->willReturn($apiKey);
        $helperMock->expects($this->once())->method('getApiByKey')->with($apiKey)->willReturn($apiMock);

        $apiMock->expects($this->once())->method('getLists')->willReturn($listsMock);

        $listsMock->expects($this->once())->method('getLists')->willReturn($mcLists);

        $mailchimpstoresControllerMock->expects($this->once())->method('getResponse')->willReturn($responseMock);

        $responseMock->expects($this->once())->method('setHeader')->with('Content-type', 'application/json');
        $responseMock->expects($this->once())->method('setBody')->with($jsonData);

        $mailchimpstoresControllerMock->getstoresAction();
    }

    public function testDeleteAction()
    {
        $idParam = 'id';
        $tableId = 1;
        $mailchimpStoreId = 'a1s2d3f4g5h6j7k8l9p0';
        $apiKey = 'a1s2d3f4g5h6j7k8l9p0z1x2c3v4b5-us1';
        $apiKeyEncrypted = '4rGjyBo/uKChzvu0bF3hjaMwfM503N3/+2fdRjdlAGo=';

        $mailchimpstoresControllerMock = $this->mailchimpstoresController
            ->disableOriginalConstructor()
            ->setMethods(array('getRequest', 'loadMailchimpStore', 'getMailchimpHelper', '_redirect'))
            ->getMock();

        $requestMock = $this->getMockBuilder(Mage_Core_Controller_Request_Http::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getParam'))
            ->getMock();

        $mailchimpStoreModelMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Stores::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getStoreid', 'getApikey', 'getId'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getApiStores', 'deleteAllMCStoreData', 'decryptData'))
            ->getMock();

        $apiStoresMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Stores::class)
            ->disableOriginalConstructor()
            ->setMethods(array('deleteMailChimpStore'))
            ->getMock();

        $mailchimpstoresControllerMock->expects($this->once())->method('getRequest')->willReturn($requestMock);

        $requestMock->expects($this->once())->method('getParam')->with($idParam)->willReturn($tableId);

        $mailchimpstoresControllerMock->expects($this->once())->method('loadMailchimpStore')->with($tableId)->willReturn($mailchimpStoreModelMock);

        $mailchimpStoreModelMock->expects($this->once())->method('getStoreid')->willReturn($mailchimpStoreId);
        $mailchimpStoreModelMock->expects($this->once())->method('getApikey')->willReturn($apiKeyEncrypted);

        $mailchimpstoresControllerMock->expects($this->once())->method('getMailchimpHelper')->willReturn($helperMock);

        $mailchimpStoreModelMock->expects($this->once())->method('getId')->willReturn($tableId);

        $helperMock->expects($this->once())->method('decryptData')->with($apiKeyEncrypted)->willReturn($apiKey);
        $helperMock->expects($this->once())->method('getApiStores')->willReturn($apiStoresMock);

        $apiStoresMock->expects($this->once())->method('deleteMailChimpStore')->with($mailchimpStoreId, $apiKey);

        $helperMock->expects($this->once())->method('deleteAllMCStoreData')->with($mailchimpStoreId);

        $mailchimpstoresControllerMock->expects($this->once())->method('_redirect')->with('*/*/index');

        $mailchimpstoresControllerMock->deleteAction();
    }
}
