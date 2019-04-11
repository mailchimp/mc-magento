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
        $lastOrder = array();
        $email = 'test@ebizmarts.com';
        $eventValue = null;
        $mapFields = 'a:25:{s:18:"_1468601283719_719";a:2:{s:9:"mailchimp";s:7:"WEBSITE";s:7:"magento";s:1:"1";}s:18:"_1468609069544_544";a:2:{s:9:"mailchimp";s:7:"STOREID";s:7:"magento";s:1:"2";}s:18:"_1469026825907_907";a:2:{s:9:"mailchimp";s:9:"STORENAME";s:7:"magento";s:1:"3";}s:18:"_1469027411717_717";a:2:{s:9:"mailchimp";s:6:"PREFIX";s:7:"magento";s:1:"4";}s:18:"_1469027418285_285";a:2:{s:9:"mailchimp";s:5:"FNAME";s:7:"magento";s:1:"5";}s:18:"_1469027422918_918";a:2:{s:9:"mailchimp";s:5:"MNAME";s:7:"magento";s:1:"6";}s:18:"_1469027429502_502";a:2:{s:9:"mailchimp";s:5:"LNAME";s:7:"magento";s:1:"7";}s:18:"_1469027434574_574";a:2:{s:9:"mailchimp";s:6:"SUFFIX";s:7:"magento";s:1:"8";}s:18:"_1469027444231_231";a:2:{s:9:"mailchimp";s:5:"EMAIL";s:7:"magento";s:1:"9";}s:18:"_1469027453439_439";a:2:{s:9:"mailchimp";s:6:"CGROUP";s:7:"magento";s:2:"10";}s:18:"_1469027462887_887";a:2:{s:9:"mailchimp";s:3:"DOB";s:7:"magento";s:2:"11";}s:18:"_1469027480560_560";a:2:{s:9:"mailchimp";s:3:"TAX";s:7:"magento";s:2:"15";}s:18:"_1469027486920_920";a:2:{s:9:"mailchimp";s:9:"CONFIRMED";s:7:"magento";s:2:"16";}s:18:"_1469027496512_512";a:2:{s:9:"mailchimp";s:9:"CREATEDAT";s:7:"magento";s:2:"17";}s:18:"_1469027502720_720";a:2:{s:9:"mailchimp";s:6:"GENDER";s:7:"magento";s:2:"18";}s:18:"_1469027508616_616";a:2:{s:9:"mailchimp";s:9:"DISGRPCHG";s:7:"magento";s:2:"35";}s:18:"_1472845935735_735";a:2:{s:9:"mailchimp";s:8:"BCOMPANY";s:7:"magento";s:15:"billing_company";}s:18:"_1472846546252_252";a:2:{s:9:"mailchimp";s:8:"BCOUNTRY";s:7:"magento";s:15:"billing_country";}s:18:"_1472846569989_989";a:2:{s:9:"mailchimp";s:10:"BTELEPHONE";s:7:"magento";s:17:"billing_telephone";}s:18:"_1472846572949_949";a:2:{s:9:"mailchimp";s:8:"BZIPCODE";s:7:"magento";s:15:"billing_zipcode";}s:18:"_1472846578861_861";a:2:{s:9:"mailchimp";s:8:"SCOMPANY";s:7:"magento";s:16:"shipping_company";}s:17:"_1472846584014_14";a:2:{s:9:"mailchimp";s:8:"SCOUNTRY";s:7:"magento";s:16:"shipping_country";}s:18:"_1472846587534_534";a:2:{s:9:"mailchimp";s:10:"STELEPHONE";s:7:"magento";s:18:"shipping_telephone";}s:18:"_1472846591374_374";a:2:{s:9:"mailchimp";s:8:"SZIPCODE";s:7:"magento";s:16:"shipping_zipcode";}s:18:"_1490127043147_147";a:2:{s:9:"mailchimp";s:3:"DOP";s:7:"magento";s:3:"dop";}}';
        $customerId = 10;
        $maps = array(
            '_1468601283719_719' => array(
                'mailchimp' => 'WEBSITE',
                'magento' => 1),
            '_1468609069544_544' => array(
                'mailchimp' => 'STOREID',
                'magento' => 2),
            '_1469026825907_907' => array(
                'mailchimp' => 'STORENAME',
                'magento' => 3),
            '_1469027411717_717' => array(
                'mailchimp' => 'PREFIX',
                'magento' => 4),
            '_1469027418285_285' => array(
                'mailchimp' => 'FNAME',
                'magento' => 5),
            '_1469027422918_918' => array(
                'mailchimp' => 'MNAME',
                'magento' => 6),
            '_1469027429502_502' => array(
                'mailchimp' => 'LNAME',
                'magento' => 7),
            '_1469027434574_574' => array(
                'mailchimp' => 'SUFFIX',
                'magento' => 8),
            '_1469027444231_231' => array(
                'mailchimp' => 'EMAIL',
                'magento' => 9),
            '_1469027453439_439' => array(
                'mailchimp' => 'CGROUP',
                'magento' => 10),
            '_1469027462887_887' => array(
                'mailchimp' => 'DOB',
                'magento' => 11),
            '_1469027480560_560' => array(
                'mailchimp' => 'TAX',
                'magento' => 15),
            '_1469027486920_920' => array(
                'mailchimp' => 'CONFIRMED',
                'magento' => 16),
            '_1469027496512_512' => array(
                'mailchimp' => 'CREATEDAT',
                'magento' => 17),
            '_1469027502720_720' => array(
                'mailchimp' => 'GENDER',
                'magento' => 18),
            '_1469027508616_616' => array(
                'mailchimp' => 'DISGRPCHG',
                'magento' => 35),
            '_1472845935735_735' => array(
                'mailchimp' => 'BCOMPANY',
                'magento' => 'billing_company'),
            '_1472846546252_252' => array(
                'mailchimp' => 'BCOUNTRY',
                'magento' => 'billing_country'),
            '_1472846569989_989' => array(
                'mailchimp' => 'BTELEPHONE',
                'magento' => 'billing_telephone'),
            '_1472846572949_949' => array(
                'mailchimp' => 'BZIPCODE',
                'magento' => 'billing_zipcode'),
            '_1472846578861_861' => array(
                'mailchimp' => 'SCOMPANY',
                'magento' => 'shipping_company'),
            '_1472846584014_14' => array(
                'mailchimp' => 'SCOUNTRY',
                'magento' => 'shipping_country'),
            '_1472846587534_534' => array(
                'mailchimp' => 'STELEPHONE',
                'magento' => 'shipping_telephone'),
            '_1472846591374_374' => array(
                'mailchimp' => 'SZIPCODE',
                'magento' => 'shipping_zipcode'),
            '_1490127043147_147' => array(
                'mailchimp' => 'DOP',
                'magento' => 'dop')
        );

        $subscribersApiMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Subscribers::class)
            ->disableOriginalConstructor()
            ->setMethods(array(
                    'getWebSiteByStoreId',
                    'getEntityAttributeCollection',
                    'getCustomerModel',
                    'dispatchEventMergeVars',
                    'getMailchimpHelper',
                    'unserilizeMapFields',
                    'customizedAttributes',
                    'dispatchEventValue')
            )->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getMapFields', 'getLastOrderByEmail'))
            ->getMock();

        $collectionMock = $this->getMockBuilder(Mage_Eav_Model_Resource_Attribute_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('setEntityTypeFilter', 'addSetInfo', 'getData', '_getEntityTypeCode', '_getEavWebsiteTable'))
            ->getMock();

        $attributeMock = $this->getMockBuilder(Mage_Eav_Model_Resource_Attribute::class)
            ->disableOriginalConstructor()
            ->setMethods(array())
            ->getMock();

        $customerMock = $this->getMockBuilder(Mage_Customer_Model_Customer::class)
            ->setMethods(array('setWebsiteId', 'load'))
            ->getMock();

        $subscriberMock = $this->getMockBuilder(Mage_Newsletter_Model_Subscriber::class)
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
        $newVars = new Varien_Object;
        $subscribersApiMock->expects($this->once())
            ->method('dispatchEventMergeVars')
            ->with($subscriberMock, $mergeVars, $newVars);
        $subscribersApiMock->expects($this->once())
            ->method('unserilizeMapFields')
            ->with($mapFields)
            ->willReturn($maps);

        $subscribersApiMock->expects($this->exactly(9))
            ->method('customizedAttributes')
            ->withConsecutive(
                array($maps['_1472845935735_735']['magento'], $customerMock, $lastOrder, $mergeVars, $maps['_1472845935735_735']['mailchimp'], $helperMock, $email, $storeId),
                array($maps['_1472846546252_252']['magento'], $customerMock, $lastOrder, $mergeVars, $maps['_1472846546252_252']['mailchimp'], $helperMock, $email, $storeId),
                array($maps['_1472846569989_989']['magento'], $customerMock, $lastOrder, $mergeVars, $maps['_1472846569989_989']['mailchimp'], $helperMock, $email, $storeId),
                array($maps['_1472846572949_949']['magento'], $customerMock, $lastOrder, $mergeVars, $maps['_1472846572949_949']['mailchimp'], $helperMock, $email, $storeId),
                array($maps['_1472846578861_861']['magento'], $customerMock, $lastOrder, $mergeVars, $maps['_1472846578861_861']['mailchimp'], $helperMock, $email, $storeId),
                array($maps['_1472846584014_14']['magento'], $customerMock, $lastOrder, $mergeVars, $maps['_1472846584014_14']['mailchimp'], $helperMock, $email, $storeId),
                array($maps['_1472846587534_534']['magento'], $customerMock, $lastOrder, $mergeVars, $maps['_1472846587534_534']['mailchimp'], $helperMock, $email, $storeId),
                array($maps['_1472846591374_374']['magento'], $customerMock, $lastOrder, $mergeVars, $maps['_1472846591374_374']['mailchimp'], $helperMock, $email, $storeId),
                array($maps['_1490127043147_147']['magento'], $customerMock, $lastOrder, $mergeVars, $maps['_1490127043147_147']['mailchimp'], $helperMock, $email, $storeId)
            )
            ->willReturnOnConsecutiveCalls(
                $mergeVars,
                $mergeVars,
                $mergeVars,
                $mergeVars,
                $mergeVars,
                $mergeVars,
                $mergeVars,
                $mergeVars,
                $mergeVars
            );

            $subscribersApiMock->expects($this->exactly(9))
            ->method('dispatchEventValue')
            ->withConsecutive(
                array($customerMock, $email, $maps['_1472845935735_735']['magento'], $eventValue),
                array($customerMock, $email, $maps['_1472846546252_252']['magento'], $eventValue),
                array($customerMock, $email, $maps['_1472846569989_989']['magento'], $eventValue),
                array($customerMock, $email, $maps['_1472846572949_949']['magento'], $eventValue),
                array($customerMock, $email, $maps['_1472846578861_861']['magento'], $eventValue),
                array($customerMock, $email, $maps['_1472846584014_14']['magento'], $eventValue),
                array($customerMock, $email, $maps['_1472846587534_534']['magento'], $eventValue),
                array($customerMock, $email, $maps['_1472846591374_374']['magento'], $eventValue),
                array($customerMock, $email, $maps['_1490127043147_147']['magento'], $eventValue)
            );

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
        $collectionMock->expects($this->exactly(2))
            ->method('getData')
            ->willReturnOnConsecutiveCalls(
                $collectionMock,
                $collectionMock
            );

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
