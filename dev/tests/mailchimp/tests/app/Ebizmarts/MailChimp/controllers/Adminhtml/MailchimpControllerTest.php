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

    public function testGetStoresAction()
    {
        $apiKeyParam = 'apikey';
        $apiKey = 'a1s2d3f4g5h6j7k8l9z1x2c3v4v4-us1';
        $mcStores = array(
            'stores' => array(
                array(
                    'id' => 'a1s2d3f4g5h6j7k8l9p0',
                    'list_id' => 'a1s2d3f4g5',
                    'name' => 'Madison Island - English',
                    'platform' => 'Magento',
                    'domain' => 'domain.com',
                    'is_syncing' => false,
                    'email_address' => 'email@example.com',
                    'currency_code' => 'USD',
                    'connected_site' => array(
                        'site_foreign_id' => 'a1s2d3f4g5h6j7k8l9p0',
                        'site_script' => array(
                            'url' => 'https://chimpstatic.com/mcjs-connected/js/users/1647ea7abc3f2f3259e2613f9/a946187aed2d57d15cdac9987.js',
                            'fragment' => '<script id="mcjs">!function(c,h,i,m,p){m=c.createElement(h),p=c.getElementsByTagName(h)[0],m.async=1,m.src=i,p.parentNode.insertBefore(m,p)}(document,"script","https://chimpstatic.com/mcjs-connected/js/users/1647ea7abc3f2f3259e2613f9/a946187aed2d57d15cdac9987.js");</script>'
                        ),
                    ),
                    'automations' => array(
                        'abandoned_cart' => array(
                            'is_supported' => 1
                        ),
                        'abandoned_browse' => array(
                            'is_supported' => 1
                        )
                    ),
                    'list_is_active' => 1,
                    'created_at' => '2016-05-26T18:30:55+00:00',
                    'updated_at' => '2019-03-04T19:53:57+00:00'
                )
            )
        );
        $jsonData = '[{"id":"","name":"--- Select a MailChimp Store ---"},{"id":"a1s2d3f4g5h6j7k8l9p0","name":"Madison Island - English"}]';

        $mailchimpControllerMock = $this->mailchimpController
            ->disableOriginalConstructor()
            ->setMethods(array('getRequest', 'makeHelper', 'getResponse'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getApiByKey'))
            ->getMock();

        $requestMock = $this->getMockBuilder(Mage_Core_Controller_Request_Http::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getParam'))
            ->getMock();

        $responseMock = $this->getMockBuilder(Mage_Core_Controller_Response_Http::class)
            ->disableOriginalConstructor()
            ->setMethods(array('setHeader', 'setBody'))
            ->getMock();

        $apiMock = $this->getMockBuilder(Ebizmarts_MailChimp::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getEcommerce'))
            ->getMock();

        $ecommerceMock = $this->getMockBuilder(MailChimp_Ecommerce::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getStores'))
            ->getMock();

        $storesMock = $this->getMockBuilder(MailChimp_EcommerceStore::class)
            ->disableOriginalConstructor()
            ->setMethods(array('get'))
            ->getMock();

        $mailchimpControllerMock->expects($this->once())->method('getRequest')->willReturn($requestMock);

        $requestMock->expects($this->once())->method('getParam')->with($apiKeyParam)->willReturn($apiKey);

        $mailchimpControllerMock->expects($this->once())->method('makeHelper')->willReturn($helperMock);

        $helperMock->expects($this->once())->method('getApiByKey')->with($apiKey)->willReturn($apiMock);

        $apiMock->expects($this->once())->method('getEcommerce')->willReturn($ecommerceMock);

        $ecommerceMock->expects($this->once())->method('getStores')->willReturn($storesMock);

        $storesMock->expects($this->once())->method('get')->with(null, null, null, 100)->willReturn($mcStores);

        $mailchimpControllerMock->expects($this->once())->method('getResponse')->willReturn($responseMock);

        $responseMock->expects($this->once())->method('setHeader')->with('Content-type', 'application/json');
        $responseMock->expects($this->once())->method('setBody')->with($jsonData);

        $mailchimpControllerMock->getStoresAction();
    }

    public function testGetListAction()
    {
        $apiKeyParam = 'apikey';
        $apiKey = 'a1s2d3f4g5h6j7k8l9z1x2c3v4v4-us1';
        $storeIdParam = 'storeid';
        $storeId = 1;
        $listId = 'a1s2d3f4g5';

        $mcStore = array(
            'id' => 'a1s2d3f4g5h6j7k8l9p0',
            'list_id' => $listId,
            'name' => 'Madison Island - English',
            'platform' => 'Magento',
            'domain' => 'domain.com',
            'is_syncing' => false,
            'email_address' => 'email@example.com',
            'currency_code' => 'USD',
            'connected_site' => array(
                'site_foreign_id' => 'a1s2d3f4g5h6j7k8l9p0',
                'site_script' => array(
                    'url' => 'https://chimpstatic.com/mcjs-connected/js/users/1647ea7abc3f2f3259e2613f9/a946187aed2d57d15cdac9987.js',
                    'fragment' => '<script id="mcjs">!function(c,h,i,m,p){m=c.createElement(h),p=c.getElementsByTagName(h)[0],m.async=1,m.src=i,p.parentNode.insertBefore(m,p)}(document,"script","https://chimpstatic.com/mcjs-connected/js/users/1647ea7abc3f2f3259e2613f9/a946187aed2d57d15cdac9987.js");</script>'
                ),
            ),
            'automations' => array(
                'abandoned_cart' => array(
                    'is_supported' => 1
                ),
                'abandoned_browse' => array(
                    'is_supported' => 1
                )
            ),
            'list_is_active' => 1,
            'created_at' => '2016-05-26T18:30:55+00:00',
            'updated_at' => '2019-03-04T19:53:57+00:00'
        );

        $mcList = array(
            'id' => $listId,
            'name' => 'Newsletter',
            'stats' => array(
                'member_count' => 18
            )
        );

        $jsonData = '{"id":"'.$listId.'","name":"Newsletter"}';

        $mailchimpControllerMock = $this->mailchimpController
            ->disableOriginalConstructor()
            ->setMethods(array('getRequest', 'makeHelper', 'getResponse'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getApiByKey'))
            ->getMock();

        $requestMock = $this->getMockBuilder(Mage_Core_Controller_Request_Http::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getParam'))
            ->getMock();

        $responseMock = $this->getMockBuilder(Mage_Core_Controller_Response_Http::class)
            ->disableOriginalConstructor()
            ->setMethods(array('setHeader', 'setBody'))
            ->getMock();

        $apiMock = $this->getMockBuilder(Ebizmarts_MailChimp::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getEcommerce', 'getLists'))
            ->getMock();

        $ecommerceMock = $this->getMockBuilder(MailChimp_Ecommerce::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getStores'))
            ->getMock();

        $storesMock = $this->getMockBuilder(MailChimp_EcommerceStore::class)
            ->disableOriginalConstructor()
            ->setMethods(array('get'))
            ->getMock();

        $listsMock = $this->getMockBuilder(MailChimp_Lists::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getLists'))
            ->getMock();

        $mailchimpControllerMock->expects($this->once())->method('getRequest')->willReturn($requestMock);

        $requestMock->expects($this->exactly(2))->method('getParam')->withConsecutive(
            array($apiKeyParam),
            array($storeIdParam)
            )->willReturnOnConsecutiveCalls(
                $apiKey,
                $storeId
        );

        $mailchimpControllerMock->expects($this->once())->method('makeHelper')->willReturn($helperMock);

        $helperMock->expects($this->once())->method('getApiByKey')->with($apiKey)->willReturn($apiMock);

        $apiMock->expects($this->once())->method('getEcommerce')->willReturn($ecommerceMock);

        $ecommerceMock->expects($this->once())->method('getStores')->willReturn($storesMock);

        $storesMock->expects($this->once())->method('get')->with($storeId)->willReturn($mcStore);

        $apiMock->expects($this->once())->method('getLists')->willReturn($listsMock);

        $listsMock->expects($this->once())->method('getLists')->with($listId)->willReturn($mcList);

        $mailchimpControllerMock->expects($this->once())->method('getResponse')->willReturn($responseMock);

        $responseMock->expects($this->once())->method('setHeader')->with('Content-type', 'application/json');
        $responseMock->expects($this->once())->method('setBody')->with($jsonData);

        $mailchimpControllerMock->getListAction();
    }

    public function testGetInfoAction()
    {
        $apiKeyParam = 'apikey';
        $apiKey = 'a1s2d3f4g5h6j7k8l9z1x2c3v4v4-us1';
        $storeIdParam = 'storeid';
        $storeId = 1;
        $listId = 'a1s2d3f4g5';

        $mcStore = array(
            'id' => 'a1s2d3f4g5h6j7k8l9p0',
            'list_id' => $listId,
            'name' => 'Madison Island - English',
            'platform' => 'Magento',
            'domain' => 'domain.com',
            'is_syncing' => false,
            'email_address' => 'email@example.com',
            'currency_code' => 'USD',
            'connected_site' => array(
                'site_foreign_id' => 'a1s2d3f4g5h6j7k8l9p0',
                'site_script' => array(
                    'url' => 'https://chimpstatic.com/mcjs-connected/js/users/1647ea7abc3f2f3259e2613f9/a946187aed2d57d15cdac9987.js',
                    'fragment' => '<script id="mcjs">!function(c,h,i,m,p){m=c.createElement(h),p=c.getElementsByTagName(h)[0],m.async=1,m.src=i,p.parentNode.insertBefore(m,p)}(document,"script","https://chimpstatic.com/mcjs-connected/js/users/1647ea7abc3f2f3259e2613f9/a946187aed2d57d15cdac9987.js");</script>'
                ),
            ),
            'automations' => array(
                'abandoned_cart' => array(
                    'is_supported' => 1
                ),
                'abandoned_browse' => array(
                    'is_supported' => 1
                )
            ),
            'list_is_active' => 1,
            'created_at' => '2016-05-26T18:30:55+00:00',
            'updated_at' => '2019-03-04T19:53:57+00:00'
        );

        $mcList = array(
            'id' => $listId,
            'name' => 'Newsletter',
            'stats' => array(
                'member_count' => 18
            )
        );

        $amounts = array('total_items' => 10);
        $info = array('account_name' => 'Ebizmarts Corp.','total_subscribers' => 104);
        $syncDate = "2019-02-01 20:00:05";
        $jsonData = '[{"value":0,"label":"Username: Ebizmarts Corp."},{"value":1,"label":"Total Account Subscribers: 104"},{"value":2,"label":"Total List Subscribers: 18"},{"value":10,"label":"Ecommerce Data uploaded to MailChimp store Madison Island - English:"},{"value":11,"label":"Initial sync: '.$syncDate.'"},{"value":12,"label":"  Total Customers: 10"},{"value":13,"label":"  Total Products: 10"},{"value":14,"label":"  Total Orders: 10"},{"value":15,"label":"  Total Carts: 10"}]';

        $mailchimpControllerMock = $this->mailchimpController
            ->disableOriginalConstructor()
            ->setMethods(array('getRequest', 'makeHelper', 'getResponse', '_getDateSync'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getApiByKey'))
            ->getMock();

        $requestMock = $this->getMockBuilder(Mage_Core_Controller_Request_Http::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getParam'))
            ->getMock();

        $responseMock = $this->getMockBuilder(Mage_Core_Controller_Response_Http::class)
            ->disableOriginalConstructor()
            ->setMethods(array('setHeader', 'setBody'))
            ->getMock();

        $apiMock = $this->getMockBuilder(Ebizmarts_MailChimp::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getEcommerce', 'getLists', 'getRoot'))
            ->getMock();

        $rootMock = $this->getMockBuilder(MailChimp_Root::class)
            ->disableOriginalConstructor()
            ->setMethods(array('info'))
            ->getMock();

        $ecommerceMock = $this->getMockBuilder(MailChimp_Ecommerce::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getStores', 'getCustomers', 'getProducts', 'getOrders', 'getCarts'))
            ->getMock();

        $storesMock = $this->getMockBuilder(MailChimp_EcommerceStore::class)
            ->disableOriginalConstructor()
            ->setMethods(array('get'))
            ->getMock();

        $listsMock = $this->getMockBuilder(MailChimp_Lists::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getLists'))
            ->getMock();

        $customersMock = $this->getMockBuilder(MailChimp_EcommerceCustomers::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getAll'))
            ->getMock();

        $productsMock = $this->getMockBuilder(MailChimp_EcommerceProducts::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getAll'))
            ->getMock();

        $ordersMock = $this->getMockBuilder(MailChimp_EcommerceOrders::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getAll'))
            ->getMock();

        $cartsMock = $this->getMockBuilder(MailChimp_EcommerceCarts::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getAll'))
            ->getMock();

        $mailchimpControllerMock->expects($this->once())->method('getRequest')->willReturn($requestMock);

        $requestMock->expects($this->exactly(2))->method('getParam')->withConsecutive(
            array($apiKeyParam),
            array($storeIdParam)
        )->willReturnOnConsecutiveCalls(
            $apiKey,
            $storeId
        );

        $mailchimpControllerMock->expects($this->once())->method('makeHelper')->willReturn($helperMock);

        $helperMock->expects($this->once())->method('getApiByKey')->with($apiKey)->willReturn($apiMock);

        $apiMock->expects($this->once())->method('getRoot')->willReturn($rootMock);

        $rootMock->expects($this->once())->method('info')->with('account_name,total_subscribers')->willReturn($info);

        $apiMock->expects($this->once())->method('getEcommerce')->willReturn($ecommerceMock);

        $ecommerceMock->expects($this->once())->method('getStores')->willReturn($storesMock);

        $storesMock->expects($this->once())->method('get')->with($storeId)->willReturn($mcStore);

        $apiMock->expects($this->once())->method('getLists')->willReturn($listsMock);

        $listsMock->expects($this->once())->method('getLists')->with($listId)->willReturn($mcList);

        $mailchimpControllerMock->expects($this->once())->method('_getDateSync')->with($storeId)->willReturn($syncDate);

        $ecommerceMock->expects($this->once())->method('getCustomers')->willReturn($customersMock);

        $customersMock->expects($this->once())->method('getAll')->with($storeId, 'total_items')->willReturn($amounts);

        $ecommerceMock->expects($this->once())->method('getProducts')->willReturn($productsMock);

        $productsMock->expects($this->once())->method('getAll')->with($storeId, 'total_items')->willReturn($amounts);

        $ecommerceMock->expects($this->once())->method('getOrders')->willReturn($ordersMock);

        $ordersMock->expects($this->once())->method('getAll')->with($storeId, 'total_items')->willReturn($amounts);

        $ecommerceMock->expects($this->once())->method('getCarts')->willReturn($cartsMock);

        $cartsMock->expects($this->once())->method('getAll')->with($storeId, 'total_items')->willReturn($amounts);

        $mailchimpControllerMock->expects($this->once())->method('getResponse')->willReturn($responseMock);

        $responseMock->expects($this->once())->method('setHeader')->with('Content-type', 'application/json');
        $responseMock->expects($this->once())->method('setBody')->with($jsonData);

        $mailchimpControllerMock->getInfoAction();
    }
}
