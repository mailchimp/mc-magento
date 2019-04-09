<?php

class Ebizmarts_MailChimp_Model_Api_StoresTest extends PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        Mage::app('default');
    }

    public function testCreateMailChimpStore()
    {
        $storeName = 'Madison Island - English';
        $date = '2017-10-23-19-34-31-92333600';
        $mailchimpStoreId = md5($storeName . '_' . $date);
        $apiKey = 'z1x2c3v4b5n6m7k8l9p0-us1';
        $listId = 'a1s2d3f4g5';
        $currencyCode = 'USD';
        $primaryLocale = 'en_US';
        $timeZone = 'America/Los_Angeles';
        $storePhone = '123456789';
        $currencySymbol = '$';
        $isSyncing = true;
        $storeDomain = 'https:://localhost.com';
        $storeEmail = 'store@email.com';
        $address = 'address';
        $successMessage = "The Mailchimp store was successfully created.";
        $response = array(
            'id' => $mailchimpStoreId,
            'list_id' => $listId,
            'name' => $storeName,
            'platform' => 'Magento',
            'domain' => $storeDomain,
            'is_syncing' => $isSyncing,
            'email_address' => $storeEmail,
            'currency_code' => $currencyCode,
            'connected_site' => array(
                'site_foreign_id'  => 'a1s2d3f4g5h6j7k8l9p0',
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
        $configValues = array(
            array(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_MC_JS_URL . "_$mailchimpStoreId", $response['connected_site']['site_script']['url'])
        );

        $apiStoresMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Stores::class)
                ->disableOriginalConstructor()
                ->setMethods(array('makeHelper', 'getAdminSession', 'addStore'))
                ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getDateMicrotime', 'getApiByKey', 'getMageApp', 'saveMailchimpConfig'))
            ->getMock();

        $apiMock = $this->getMockBuilder(Ebizmarts_MailChimp::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mageAppMock = $this->getMockBuilder(Mage_Core_Model_App::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getLocale'))
            ->getMock();

        $localeMock = $this->getMockBuilder(Mage_Core_Model_Locale::class)
            ->disableOriginalConstructor()
            ->setMethods(array('currency', 'getSymbol'))
            ->getMock();

        $adminSessionMock = $this->getMockBuilder(Mage_Adminhtml_Model_Session::class)
            ->disableOriginalConstructor()
            ->setMethods(array('addSuccess'))
            ->getMock();

        $apiStoresMock->expects($this->once())->method('makeHelper')->willReturn($helperMock);

        $helperMock->expects($this->once())->method('getDateMicrotime')->willReturn($date);
        $helperMock->expects($this->once())->method('getApiByKey')->with($apiKey)->willReturn($apiMock);
        $helperMock->expects($this->once())->method('getMageApp')->willReturn($mageAppMock);

        $mageAppMock->expects($this->once())->method('getLocale')->willReturn($localeMock);

        $localeMock->expects($this->once())->method('currency')->with($currencyCode)->willReturnSelf();
        $localeMock->expects($this->once())->method('getSymbol')->willReturn($currencySymbol)->willReturn($currencySymbol);

        $apiStoresMock->expects($this->once())->method('addStore')->with($apiMock, $mailchimpStoreId, $listId, $storeName, $currencyCode, $isSyncing, $storeDomain, $storeEmail, $currencySymbol, $primaryLocale, $timeZone, $storePhone, $address)->willReturn($response);

        $helperMock->expects($this->once())->method('saveMailchimpConfig')->with($configValues, 0, 'default');

        $apiStoresMock->expects($this->once())->method('getAdminSession')->willReturn($adminSessionMock);

        $adminSessionMock->expects($this->once())->method('addSuccess')->with($successMessage);

        $apiStoresMock->createMailChimpStore($apiKey, $listId, $storeName, $currencyCode, $storeDomain, $storeEmail, $primaryLocale, $timeZone, $storePhone, $address);
    }

    public function testDeleteMailChimpStore()
    {
        $apiKey = 'z1x2c3v4b5n6m7k8l9p0-us1';
        $mailChimpStoreId = 'a1s2d3f4g5h6j7k8l9n0';
        $successMessage = "The Mailchimp store was successfully deleted.";

        $apiStoresMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Stores::class)
                ->disableOriginalConstructor()
                ->setMethods(array('makeHelper', 'getAdminSession'))
                ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getApiByKey'))
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

        $adminSessionMock = $this->getMockBuilder(Mage_Adminhtml_Model_Session::class)
            ->disableOriginalConstructor()
            ->setMethods(array('addSuccess'))
            ->getMock();


        $apiStoresMock->expects($this->once())->method('makeHelper')->willReturn($helperMock);

        $helperMock->expects($this->once())->method('getApiByKey')->with($apiKey)->willReturn($apiMock);

        $apiMock->expects($this->once())->method('getEcommerce')->willReturn($ecommerceMock);

        $ecommerceMock->expects($this->once())->method('getStores')->willReturn($ecommerceStoresMock);

        $ecommerceStoresMock->expects($this->once())->method('delete')->with($mailChimpStoreId);

        $apiStoresMock->expects($this->once())->method('getAdminSession')->willReturn($adminSessionMock);

        $adminSessionMock->expects($this->once())->method('addSuccess')->with($successMessage);

        $apiStoresMock->deleteMailChimpStore($mailChimpStoreId, $apiKey);
    }
}
