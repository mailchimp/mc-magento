<?php

class Ebizmarts_MailChimp_Model_Api_StoresTest extends PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        Mage::app('default');
    }

    public function testCreateMailChimpStore()
    {
        $mailChimpStoreId = 'a1s2d3f4g5h6j7k8l9n0';
        $scopeId = 1;
        $scope = 'stores';
        $listId = 'listId';
        $storeName = 'Madison Island - English';
        $storeEmailAddress = 'store@email.com';
        $currencyCode = 'USD';
        $primaryLocale = 'en_US';
        $timeZone = 'America/Los_Angeles';
        $storePhone = '123456789';
        $currencySymbol = '$';
        $response = '';
        $isSyncing = true;
        $storeDomain = 'https:://localhost.com';

        $apiStoresMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Stores::class)
                ->disableOriginalConstructor()
                ->setMethods(array('makeHelper'))
                ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getApi', 'getGeneralList', 'getMCStoreName', 'getConfigValueForScope', 'getStoreDomain',
                'getStoreLanguageCode', 'getStoreTimeZone', 'getStorePhone', 'getMageApp'))
            ->getMock();

        $apiMock = $this->getMockBuilder(Ebizmarts_MailChimp::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getEcommerce'))
            ->getMock();

        $ecommerceMock = $this->getMockBuilder(MailChimp_Ecommerce::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getStores'))
            ->getMock();

        $ecommerceStoresMock = $this->getMockBuilder(MailChimp_EcommerceStore::class)
            ->disableOriginalConstructor()
            ->setMethods(array('add'))
            ->getMock();

        $mageAppMock = $this->getMockBuilder(Mage_Core_Model_App::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getLocale'))
            ->getMock();

        $localeMock = $this->getMockBuilder(Mage_Core_Model_Locale::class)
            ->disableOriginalConstructor()
            ->setMethods(array('currency', 'getSymbol'))
            ->getMock();

        $apiStoresMock->expects($this->once())->method('makeHelper')->willReturn($helperMock);

        $helperMock->expects($this->once())->method('getApi')->with($scopeId, $scope)->willReturn($apiMock);
        $helperMock->expects($this->once())->method('getGeneralList')->with($scopeId, $scope)->willReturn($listId);
        $helperMock->expects($this->once())->method('getMCStoreName')->with($scopeId, $scope)->willReturn($storeName);

        $helperMock->expects($this->exactly(2))->method('getConfigValueForScope')->withConsecutive(
            array('trans_email/ident_general/email', $scopeId, $scope),
            array(Mage_Directory_Model_Currency::XML_PATH_CURRENCY_DEFAULT, $scopeId, $scope)
        )->willReturnOnConsecutiveCalls(
            $storeEmailAddress,
            $currencyCode
        );

        $helperMock->expects($this->once())->method('getStoreDomain')->with($scopeId, $scope)->willReturn($storeDomain);
        $helperMock->expects($this->once())->method('getStoreLanguageCode')->with($scopeId, $scope)->willReturn($primaryLocale);
        $helperMock->expects($this->once())->method('getStoreTimeZone')->with($scopeId, $scope)->willReturn($timeZone);
        $helperMock->expects($this->once())->method('getStorePhone')->with($scopeId, $scope)->willReturn($storePhone);
        $helperMock->expects($this->once())->method('getMageApp')->willReturn($mageAppMock);

        $mageAppMock->expects($this->once())->method('getLocale')->willReturn($localeMock);

        $localeMock->expects($this->once())->method('currency')->with($currencyCode)->willReturnSelf();
        $localeMock->expects($this->once())->method('getSymbol')->willReturn($currencySymbol)->willReturn($currencySymbol);

        $apiMock->expects($this->once())->method('getEcommerce')->willReturn($ecommerceMock);

        $ecommerceMock->expects($this->once())->method('getStores')->willReturn($ecommerceStoresMock);

        $ecommerceStoresMock->expects($this->once())->method('add')->with($mailChimpStoreId, $listId, $storeName, $currencyCode, $isSyncing, 'Magento', $storeDomain, $storeEmailAddress, $currencySymbol, $primaryLocale, $timeZone, $storePhone)->willReturn($response);

        $apiStoresMock->createMailChimpStore($mailChimpStoreId, null, $scopeId, $scope);
    }

    public function testDeleteMailChimpStore()
    {
        $mailChimpStoreId = 'a1s2d3f4g5h6j7k8l9n0';
        $scopeId = 1;
        $scope = 'stores';
        $connectionType = 'core_write';
        $setCondition = array('status' => 'canceled');
        $whereCondition = "status = 'pending'";
        $tableName = 'mailchimp_sync_batches';

        $apiStoresMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Stores::class)
                ->disableOriginalConstructor()
                ->setMethods(array('makeHelper', 'getSyncBatchesResource'))
                ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getApi', 'getCoreResource'))
            ->getMock();

        $apiMock = $this->getMockBuilder(Ebizmarts_MailChimp::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getEcommerce'))
            ->getMock();

        $ecommerceMock = $this->getMockBuilder(MailChimp_Ecommerce::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getStores'))
            ->getMock();

        $ecommerceStoresMock = $this->getMockBuilder(MailChimp_EcommerceStore::class)
            ->disableOriginalConstructor()
            ->setMethods(array('delete'))
            ->getMock();

        $coreResourceMock = $this->getMockBuilder(Mage_Core_Model_Resource::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getConnection'))
            ->getMock();

        $dbAdapterInterfaceMock = $this->getMockForAbstractClass(Varien_Db_Adapter_Interface::class);

        $syncBatchesResourceMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Mysql4_SynchBatches::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getMainTable'))
            ->getMock();


        $apiStoresMock->expects($this->once())->method('makeHelper')->willReturn($helperMock);

        $helperMock->expects($this->once())->method('getApi')->with($scopeId, $scope)->willReturn($apiMock);
        $apiMock->expects($this->once())->method('getEcommerce')->willReturn($ecommerceMock);
        $ecommerceMock->expects($this->once())->method('getStores')->willReturn($ecommerceStoresMock);
        $ecommerceStoresMock->expects($this->once())->method('delete')->with($mailChimpStoreId);

        $helperMock->expects($this->once())->method('getCoreResource')->WillReturn($coreResourceMock);
        $coreResourceMock->expects($this->once())->method('getConnection')->with($connectionType)->willReturn($dbAdapterInterfaceMock);

        $apiStoresMock->expects($this->once())->method('getSyncBatchesResource')->willReturn($syncBatchesResourceMock);
        $syncBatchesResourceMock->expects($this->once())->method('getMainTable')->willReturn($tableName);

        $dbAdapterInterfaceMock->expects($this->once())->method('update')->with($tableName, $setCondition, $whereCondition);

        $apiStoresMock->deleteMailChimpStore($mailChimpStoreId, $scopeId, $scope);
    }

    public function testModifyName()
    {
        $mailChimpStoreId = 'a1s2d3f4g5h6j7k8l9n0';
        $scopeId = 1;
        $scope = 'stores';
        $name = 'New Name';

        $apiStoresMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Stores::class)
            ->disableOriginalConstructor()
            ->setMethods(array('makeHelper'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getApi', 'getMCStoreId', 'getRealScopeForConfig'))
            ->getMock();

        $apiMock = $this->getMockBuilder(Ebizmarts_MailChimp::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getEcommerce'))
            ->getMock();

        $ecommerceMock = $this->getMockBuilder(MailChimp_Ecommerce::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getStores'))
            ->getMock();

        $ecommerceStoresMock = $this->getMockBuilder(MailChimp_EcommerceStore::class)
            ->disableOriginalConstructor()
            ->setMethods(array('edit'))
            ->getMock();

        $apiStoresMock->expects($this->once())->method('makeHelper')->willReturn($helperMock);

        $helperMock->expects($this->once())->method('getApi')->with($scopeId, $scope)->willReturn($apiMock);
        $helperMock->expects($this->once())->method('getMCStoreId')->with($scopeId, $scope)->willReturn($mailChimpStoreId);

        $apiMock->expects($this->once())->method('getEcommerce')->willReturn($ecommerceMock);
        $ecommerceMock->expects($this->once())->method('getStores')->willReturn($ecommerceStoresMock);
        $ecommerceStoresMock->expects($this->once())->method('edit')->with($mailChimpStoreId, $name);

        $apiStoresMock->modifyName($name, $scopeId, $scope);
    }

    public function testGetMCJsUrl()
    {
        $mailChimpStoreId = 'a1s2d3f4g5h6j7k8l9n0';
        $scopeId = 1;
        $scope = 'stores';
        $MCJsUrl = 'http://mailchimpUrl.com/mc.js';
        $response = array('connected_site' => array('site_script' => array('url' => $MCJsUrl)));
        $configValues = array(array(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_MC_JS_URL, $MCJsUrl));
        $realScope = array('scope_id' => $scopeId, 'scope' => $scope);

        $apiStoresMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Stores::class)
            ->disableOriginalConstructor()
            ->setMethods(array('makeHelper', 'getStoreConnectedSiteData'))
            ->getMock();

        $apiMock = $this->getMockBuilder(Ebizmarts_MailChimp::class)
            ->disableOriginalConstructor()
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getApi', 'getMCStoreId', 'saveMailchimpConfig', 'getRealScopeForConfig'))
            ->getMock();

        $apiStoresMock->expects($this->once())->method('makeHelper')->willReturn($helperMock);

        $helperMock->expects($this->once())->method('getApi')->with($scopeId, $scope)->willReturn($apiMock);
        $helperMock->expects($this->once())->method('getMCStoreId')->with($scopeId, $scope)->willReturn($mailChimpStoreId);

        $apiStoresMock->expects($this->once())->method('getStoreConnectedSiteData')->with($apiMock, $mailChimpStoreId)->willReturn($response);

        $helperMock->expects($this->once())->method('getRealScopeForConfig')->with(Ebizmarts_MailChimp_Model_Config::GENERAL_LIST, $scopeId, $scope)->willReturn($realScope);
        $helperMock->expects($this->once())->method('saveMailchimpConfig')->with($configValues, $realScope['scope_id'], $realScope['scope']);

        $return = $apiStoresMock->getMCJsUrl($scopeId, $scope);

        $this->assertEquals($MCJsUrl, $return);
    }
}
