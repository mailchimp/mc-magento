<?php

class Ebizmarts_MailChimp_Model_Api_Subscribers_MailchimpTagsTest extends PHPUnit_Framework_TestCase
{
    const DEFAULT_STORE_ID = 1;

    protected $_mapFieldsSerialized;
    protected $_mapsFields = array(
        1 => array('mailchimp' => 'WEBSITE', 'magento' => 1),
        2 => array('mailchimp' => 'STOREID', 'magento' => 2),
        3 => array('mailchimp' => 'STORENAME', 'magento' => 3),
        4 => array('mailchimp' => 'PREFIX', 'magento' => 4),
        5 => array('mailchimp' => 'FNAME', 'magento' => 5),
        6 => array('mailchimp' => 'MNAME', 'magento' => 6),
        7 => array('mailchimp' => 'LNAME', 'magento' => 7),
        8 => array('mailchimp' => 'SUFFIX', 'magento' => 8),
        9 => array('mailchimp' => 'EMAIL', 'magento' => 9),
        10 => array('mailchimp' => 'CGROUP', 'magento' => 10),
        11 => array('mailchimp' => 'DOB', 'magento' => 11),
        12 => array('mailchimp' => 'TAX', 'magento' => 15),
        13 => array('mailchimp' => 'CONFIRMED', 'magento' => 16),
        14 => array('mailchimp' => 'CREATEDAT', 'magento' => 17),
        15 => array('mailchimp' => 'GENDER', 'magento' => 18),
        16 => array('mailchimp' => 'DISGRPCHG', 'magento' => 35),
        17 => array('mailchimp' => 'BCOMPANY', 'magento' => 'billing_company'),
        18 => array('mailchimp' => 'BCOUNTRY', 'magento' => 'billing_country'),
        19 => array('mailchimp' => 'BTELEPHONE', 'magento' => 'billing_telephone'),
        20 => array('mailchimp' => 'BZIPCODE', 'magento' => 'billing_zipcode'),
        21 => array('mailchimp' => 'SCOMPANY', 'magento' => 'shipping_company'),
        22 => array('mailchimp' => 'SCOUNTRY', 'magento' => 'shipping_country'),
        23 => array('mailchimp' => 'STELEPHONE', 'magento' => 'shipping_telephone'),
        24 => array('mailchimp' => 'SZIPCODE', 'magento' => 'shipping_zipcode'),
        25 => array('mailchimp' => 'DOP', 'magento' => 'dop')
    );
    protected $_mergeFields = array(
            'email'     => 'email@ebizmarts.com',
            'list_id'   => '1b2c3d4e5f6g7h8'
        );

    public function setUp()
    {
        Mage::app('default');
        $this->_setSerializedMergeFields();
    }


    public function testBuildMailChimpTags()
    {
        $mapFields = $this->_mapFieldsSerialized;
        $maps = $this->_mapsFields;

        $mailchimpTagsApiMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Subscribers_MailchimpTags::class)
            ->disableOriginalConstructor()
            ->setMethods(
                array('getMailchimpHelper',
                    'getStoreId',
                    'unserializeMapFields',
                    'getEntityAttributeCollection',
                    'buildCustomerAttributes',
                    'buildCustomizedAttributes',
                    'dispatchEventMergeVarAfter',
                    'mergeMailchimpTags',
                    'getNewVarienObject'
                )
            )
            ->getMock();

        $collectionMock = $this->getMockBuilder(Mage_Eav_Model_Resource_Attribute_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(
                array(
                    'setEntityTypeFilter', 'addSetInfo', 'getData',
                    '_getEntityTypeCode', '_getEavWebsiteTable'
                )
            )
            ->getMock();

        $varienObjectMock = $this->getMockBuilder(Varien_Object::class)
            ->disableOriginalConstructor()
            ->setMethods(array('hasData', 'getData'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getMapFields'))
            ->getMock();

        /**
         * helper
         */
        $helperMock->expects($this->once())
            ->method('getMapFields')
            ->with(self::DEFAULT_STORE_ID)
            ->willReturn($mapFields);

        /**
         * Collection
         */
        $collectionMock->expects($this->once())
            ->method('setEntityTypeFilter')
            ->with(1)
            ->willReturnSelf();
        $collectionMock->expects($this->once())
            ->method('addSetInfo')
            ->willReturnSelf();
        $collectionMock->expects($this->exactly(1))
            ->method('getData')
            ->willReturn($collectionMock);

        /**
         * mailchimpTags
         */
        $mailchimpTagsApiMock->expects($this->once())
            ->method('getMailchimpHelper')
            ->willReturn($helperMock);

        $mailchimpTagsApiMock->expects($this->once())
            ->method('getStoreId')
            ->willReturn(self::DEFAULT_STORE_ID);

        $mailchimpTagsApiMock->expects($this->once())
            ->method('unserializeMapFields')
            ->with($mapFields)
            ->willReturn($maps);

        $mailchimpTagsApiMock->expects($this->once())
            ->method('getEntityAttributeCollection')
            ->willReturn($collectionMock);

        $mailchimpTagsApiMock->expects($this->exactly(16))
            ->method('buildCustomerAttributes')
            ->withConsecutive(
                array($collectionMock,$maps[1]['magento'], strtoupper($maps[1]['mailchimp'])),
                array($collectionMock,$maps[2]['magento'], strtoupper($maps[2]['mailchimp'])),
                array($collectionMock,$maps[3]['magento'], strtoupper($maps[3]['mailchimp'])),
                array($collectionMock,$maps[4]['magento'], strtoupper($maps[4]['mailchimp'])),
                array($collectionMock,$maps[5]['magento'], strtoupper($maps[5]['mailchimp'])),
                array($collectionMock,$maps[6]['magento'], strtoupper($maps[6]['mailchimp'])),
                array($collectionMock,$maps[7]['magento'], strtoupper($maps[7]['mailchimp'])),
                array($collectionMock,$maps[8]['magento'], strtoupper($maps[8]['mailchimp'])),
                array($collectionMock,$maps[9]['magento'], strtoupper($maps[9]['mailchimp'])),
                array($collectionMock,$maps[10]['magento'], strtoupper($maps[10]['mailchimp'])),
                array($collectionMock,$maps[11]['magento'], strtoupper($maps[11]['mailchimp'])),
                array($collectionMock,$maps[12]['magento'], strtoupper($maps[12]['mailchimp'])),
                array($collectionMock,$maps[13]['magento'], strtoupper($maps[13]['mailchimp'])),
                array($collectionMock,$maps[14]['magento'], strtoupper($maps[14]['mailchimp'])),
                array($collectionMock,$maps[15]['magento'], strtoupper($maps[15]['mailchimp'])),
                array($collectionMock,$maps[16]['magento'], strtoupper($maps[16]['mailchimp']))
            )
            ->willReturnSelf();

        $mailchimpTagsApiMock->expects($this->exactly(9))
            ->method('buildCustomizedAttributes')
            ->withConsecutive(
                array($maps[17]['magento'], strtoupper($maps[17]['mailchimp'])),
                array($maps[18]['magento'], strtoupper($maps[18]['mailchimp'])),
                array($maps[19]['magento'], strtoupper($maps[19]['mailchimp'])),
                array($maps[20]['magento'], strtoupper($maps[20]['mailchimp'])),
                array($maps[21]['magento'], strtoupper($maps[21]['mailchimp'])),
                array($maps[22]['magento'], strtoupper($maps[22]['mailchimp'])),
                array($maps[23]['magento'], strtoupper($maps[23]['mailchimp'])),
                array($maps[24]['magento'], strtoupper($maps[24]['mailchimp'])),
                array($maps[25]['magento'], strtoupper($maps[25]['mailchimp']))
            )
            ->willReturnSelf();

        $mailchimpTagsApiMock->expects($this->once())
            ->method('getNewVarienObject')
            ->willReturn($varienObjectMock);

        $varienObjectMock->expects($this->once())
            ->method('hasData')
            ->willReturn(true);

        $varienObjectMock->expects($this->once())
            ->method('getData')
            ->willReturnSelf($varienObjectMock);

        $mailchimpTagsApiMock->expects($this->once())
            ->method('mergeMailchimpTags')
            ->willReturnSelf();

        $mailchimpTagsApiMock->buildMailChimpTags();
    }

    public function testProcessMergeFieldsWithCustomer()
    {
        $mapFields = $this->_mapFieldsSerialized;
        $maps = $this->_mapsFields;
        $data = $this->_mergeFields;

        $mailchimpTagsApiMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Subscribers_MailchimpTags::class)
            ->disableOriginalConstructor()
            ->setMethods(
                array('getMailchimpHelper', 'unserializeMapFields',
                    'getInterestGroupHandleModel', 'setCustomer', '_setMailchimpTagsToCustomer'
                )
            )
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(
                array(
                    'getMagentoStoreIdsByListId', 'getMapFields',
                    'loadListCustomer', 'loadListSubscriber'
                )
            )
            ->getMock();

        $customerMock = $this->getMockBuilder(Mage_Customer_Model_Customer::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getFirstName','setFirstName','getLastName','setLastName','save'))
            ->getMock();

        $mailchimpTagsApiMock->expects($this->once())
            ->method('getMailchimpHelper')
            ->willReturn($helperMock);

        $helperMock->expects($this->once())
            ->method('getMagentoStoreIdsByListId')
            ->with($data['list_id'])
            ->willReturn(array(self::DEFAULT_STORE_ID));

        $helperMock->expects($this->once())
            ->method('getMapFields')
            ->with(self::DEFAULT_STORE_ID)
            ->willReturn($mapFields);

        $mailchimpTagsApiMock->expects($this->once())
            ->method('unserializeMapFields')
            ->with($mapFields)
            ->willReturn($maps);

        $helperMock->expects($this->once())
            ->method('loadListCustomer')
            ->with($data['list_id'], $data['email'])
            ->willReturn($customerMock);

        $mailchimpTagsApiMock->expects($this->once())
            ->method('setCustomer')
            ->with($customerMock)
            ->willReturnSelf();

        $mailchimpTagsApiMock->expects($this->once())
            ->method('_setMailchimpTagsToCustomer')
            ->with($data)
            ->willReturnSelf();

        // Merge Fields Section tested in testProcessMergeFieldsInterestGroups().

        $mailchimpTagsApiMock->processMergeFields($data);
    }

    public function testProcessMergeFieldsWithoutCustomer()
    {

        $mapFields = $this->_mapFieldsSerialized;
        $maps = $this->_mapsFields;
        $data = $this->_mergeFields;

        $fname = 'Name';
        $lname = 'Lastname';

        $mailchimpTagsApiMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Subscribers_MailchimpTags::class)
            ->disableOriginalConstructor()
            ->setMethods(
                array('getMailchimpHelper', 'unserializeMapFields', '_getFName', '_getLName',
                    'getInterestGroupHandleModel', 'setSubscriber', '_processMerges'
                )
            )
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(
                array(
                    'getMagentoStoreIdsByListId', 'getMapFields',
                    'loadListCustomer', 'loadListSubscriber'
                )
            )
            ->getMock();

        $subscriberMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Subscribers::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getId', 'setSubscriberFirstname', 'setSubscriberLastname', 'save'))
            ->getMock();

        $mailchimpTagsApiMock->expects($this->once())
            ->method('getMailchimpHelper')
            ->willReturn($helperMock);

        $helperMock->expects($this->once())
            ->method('getMagentoStoreIdsByListId')
            ->with($data['list_id'])
            ->willReturn(array(self::DEFAULT_STORE_ID));

        $helperMock->expects($this->once())
            ->method('getMapFields')
            ->with(self::DEFAULT_STORE_ID)
            ->willReturn($mapFields);

        $mailchimpTagsApiMock->expects($this->once())
            ->method('unserializeMapFields')
            ->with($mapFields)
            ->willReturn($maps);

        $helperMock->expects($this->once())
            ->method('loadListCustomer')
            ->with($data['list_id'], $data['email'])
            ->willReturn(null);

        // Get's into the Subscriber creation/get part.
        $helperMock->expects($this->once())
            ->method('loadListSubscriber')
            ->with($data['list_id'], $data['email'])
            ->willReturn($subscriberMock);

        $mailchimpTagsApiMock->expects($this->once())->method('_getFName')->with($data)->willReturn($fname);
        $mailchimpTagsApiMock->expects($this->once())->method('_getLName')->with($data)->willReturn($lname);

        $subscriberMock->expects($this->once())->method('getId')->willReturn($subscriberMock);
        $subscriberMock->expects($this->once())
            ->method('setSubscriberFirstname')
            ->with($fname)
            ->willReturn($subscriberMock);
        $subscriberMock->expects($this->once())
            ->method('setSubscriberLastname')
            ->with($lname)
            ->willReturn($subscriberMock);

        $subscriberMock->expects($this->once())->method('save')->willReturn($subscriberMock);
        $mailchimpTagsApiMock->expects($this->once())->method('setSubscriber')->with($subscriberMock)->willReturnSelf();

        // Merge Fields Section tested in testProcessMergeFieldsInterestGroups().

        $mailchimpTagsApiMock->processMergeFields($data);
    }

    public function testProcessMergeFieldsWithoutCustomerNoSubscriber()
    {

        $mapFields = $this->_mapFieldsSerialized;
        $maps = $this->_mapsFields;
        $data = $this->_mergeFields;

        $fname = 'Name';
        $lname = 'Lastname';

        $mailchimpTagsApiMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Subscribers_MailchimpTags::class)
            ->disableOriginalConstructor()
            ->setMethods(
                array('getMailchimpHelper', 'unserializeMapFields', '_getFName', '_getLName',
                    'getInterestGroupHandleModel', 'setSubscriber', '_processMerges',
                    '_addSubscriberData'
                )
            )
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(
                array(
                    'getMagentoStoreIdsByListId', 'getMapFields',
                    'loadListCustomer', 'loadListSubscriber'
                )
            )
            ->getMock();

        $subscriberMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Subscribers::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getId', 'save'))
            ->getMock();

        $mailchimpTagsApiMock->expects($this->once())
            ->method('getMailchimpHelper')
            ->willReturn($helperMock);

        $helperMock->expects($this->once())
            ->method('getMagentoStoreIdsByListId')
            ->with($data['list_id'])
            ->willReturn(array(self::DEFAULT_STORE_ID));

        $helperMock->expects($this->once())
            ->method('getMapFields')
            ->with(self::DEFAULT_STORE_ID)
            ->willReturn($mapFields);

        $mailchimpTagsApiMock->expects($this->once())
            ->method('unserializeMapFields')
            ->with($mapFields)
            ->willReturn($maps);

        $helperMock->expects($this->once())
            ->method('loadListCustomer')
            ->with($data['list_id'], $data['email'])
            ->willReturn(null);

        // Get's into the Subscriber creation/get part.
        $helperMock->expects($this->once())
            ->method('loadListSubscriber')
            ->with($data['list_id'], $data['email'])
            ->willReturn($subscriberMock);

        $mailchimpTagsApiMock->expects($this->once())->method('_getFName')->with($data)->willReturn($fname);
        $mailchimpTagsApiMock->expects($this->once())->method('_getLName')->with($data)->willReturn($lname);

        $subscriberMock->expects($this->once())->method('getId')->willReturn(null);
        $mailchimpTagsApiMock->expects($this->once())
            ->method('_addSubscriberData')
            ->with($subscriberMock, $fname, $lname, $data['email'], $data['list_id'])
            ->willReturn($subscriberMock);

        $subscriberMock->expects($this->once())->method('save')->willReturn($subscriberMock);
        $mailchimpTagsApiMock->expects($this->once())->method('setSubscriber')->with($subscriberMock)->willReturnSelf();

        // Merge Fields Section tested in testProcessMergeFieldsInterestGroups().

        $mailchimpTagsApiMock->processMergeFields($data);
    }

    public function testProcessMergeFieldsInterestGroups()
    {
        $mapFields = $this->_mapFieldsSerialized;
        $maps = $this->_mapsFields;
        $data = $this->_mergeFields;
        $data ['merges'] = array(
                'GROUPINGS' => array(
                    'id' => '5605',
                    'unique_id' => '625420971c',
                    'name' => 'InterestingGroups',
                    'groups' => 'Group1, Group2, Group3'
                )
            );

        $mailchimpTagsApiMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Subscribers_MailchimpTags::class)
            ->disableOriginalConstructor()
            ->setMethods(
                array('getMailchimpHelper', 'unserializeMapFields',
                    '_getInterestGroupHandleModel', 'setCustomer', '_setMailchimpTagsToCustomer',
                    'getSubscriber', 'getCustomer'
                )
            )
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(
                array(
                    'getMagentoStoreIdsByListId', 'getMapFields',
                    'loadListCustomer', 'loadListSubscriber'
                )
            )
            ->getMock();

        $customerMock = $this->getMockBuilder(Mage_Customer_Model_Customer::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getFirstName','setFirstName','getLastName','setLastName','save'))
            ->getMock();

        $interestGroupHandleMock = $this
            ->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Subscribers_InterestGroupHandle::class)
            ->disableOriginalConstructor()
            ->setMethods(array('setGroupings', 'setListId', 'processGroupsData', 'setCustomer'))
            ->getMock();

        $mailchimpTagsApiMock->expects($this->once())
            ->method('getMailchimpHelper')
            ->willReturn($helperMock);

        $helperMock->expects($this->once())
            ->method('getMagentoStoreIdsByListId')
            ->with($data['list_id'])
            ->willReturn(array(self::DEFAULT_STORE_ID));

        $helperMock->expects($this->once())
            ->method('getMapFields')
            ->with(self::DEFAULT_STORE_ID)
            ->willReturn($mapFields);

        $mailchimpTagsApiMock->expects($this->once())
            ->method('unserializeMapFields')
            ->with($mapFields)
            ->willReturn($maps);

        $helperMock->expects($this->once())
            ->method('loadListCustomer')
            ->with($data['list_id'], $data['email'])
            ->willReturn($customerMock);

        $mailchimpTagsApiMock->expects($this->once())
            ->method('setCustomer')
            ->with($customerMock)
            ->willReturnSelf();

        $mailchimpTagsApiMock->expects($this->once())
            ->method('_setMailchimpTagsToCustomer')
            ->with($data)
            ->willReturnSelf();

        // Merge Fields Section.
        $mailchimpTagsApiMock->expects($this->once())
            ->method('_getInterestGroupHandleModel')
            ->willReturn($interestGroupHandleMock);

        $mailchimpTagsApiMock->expects($this->once())
            ->method('getSubscriber')
            ->willReturn(null);

        $mailchimpTagsApiMock->expects($this->once())
            ->method('getCustomer')
            ->willReturn($customerMock);

        $interestGroupHandleMock->expects($this->once())
            ->method('setCustomer')
            ->with($customerMock)
            ->willReturnSelf();

        $interestGroupHandleMock->expects($this->once())
            ->method('setGroupings')
            ->with($data['merges']['GROUPINGS'])
            ->willReturnSelf();

        $interestGroupHandleMock->expects($this->once())
            ->method('setListId')
            ->with($data['list_id'])
            ->willReturnSelf();

        $interestGroupHandleMock->expects($this->once())
            ->method('processGroupsData')
            ->willReturnSelf();

        $mailchimpTagsApiMock->processMergeFields($data);
    }

    public function testProcessMergeFieldsSubscribe()
    {

        $mapFields = $this->_mapFieldsSerialized;
        $maps = $this->_mapsFields;
        $data = $this->_mergeFields;

        $fname = 'Name';
        $lname = 'Lastname';

        $mailchimpTagsApiMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Subscribers_MailchimpTags::class)
            ->disableOriginalConstructor()
            ->setMethods(
                array('getMailchimpHelper', 'unserializeMapFields', '_getFName', '_getLName',
                    'getInterestGroupHandleModel', 'setSubscriber', '_processMerges',
                    '_addSubscriberData'
                )
            )
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(
                array(
                    'getMagentoStoreIdsByListId', 'getMapFields',
                    'loadListCustomer', 'loadListSubscriber', 'subscribeMember'
                )
            )
            ->getMock();

        $subscriberMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Subscribers::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getId', 'save'))
            ->getMock();

        $mailchimpTagsApiMock->expects($this->once())
            ->method('getMailchimpHelper')
            ->willReturn($helperMock);

        $helperMock->expects($this->once())
            ->method('getMagentoStoreIdsByListId')
            ->with($data['list_id'])
            ->willReturn(array(self::DEFAULT_STORE_ID));

        $helperMock->expects($this->once())
            ->method('getMapFields')
            ->with(self::DEFAULT_STORE_ID)
            ->willReturn($mapFields);

        $mailchimpTagsApiMock->expects($this->once())
            ->method('unserializeMapFields')
            ->with($mapFields)
            ->willReturn($maps);

        $helperMock->expects($this->once())
            ->method('loadListCustomer')
            ->with($data['list_id'], $data['email'])
            ->willReturn(null);

        // Get's into the Subscriber creation/get part.
        $helperMock->expects($this->once())
            ->method('loadListSubscriber')
            ->with($data['list_id'], $data['email'])
            ->willReturn($subscriberMock);

        $mailchimpTagsApiMock->expects($this->once())->method('_getFName')->with($data)->willReturn($fname);
        $mailchimpTagsApiMock->expects($this->once())->method('_getLName')->with($data)->willReturn($lname);

        $subscriberMock->expects($this->once())->method('getId')->willReturn(null);
        $mailchimpTagsApiMock->expects($this->once())
            ->method('_addSubscriberData')
            ->with($subscriberMock, $fname, $lname, $data['email'], $data['list_id'])
            ->willReturn($subscriberMock);

        $helperMock->expects($this->once())->method('subscribeMember')->with($subscriberMock);
        $subscriberMock->expects($this->once())->method('save')->willReturn($subscriberMock);
        $mailchimpTagsApiMock->expects($this->once())->method('setSubscriber')->with($subscriberMock)->willReturnSelf();

        // Merge Fields Section tested in testProcessMergeFieldsInterestGroups().

        $mailchimpTagsApiMock->processMergeFields($data, true);
    }

    protected function _setSerializedMergeFields()
    {
        $this->_mapFieldsSerialized =
            'a:25:{s:18:"_1468601283719_719";a:2:{s:9:"mailchimp";s:7:"WEBSITE";s:7:"magento";s:1:"1";}'
            . 's:18:"_1468609069544_544";a:2:{s:9:"mailchimp";s:7:"STOREID";s:7:"magento";s:1:"2";}'
            . 's:18:"_1469026825907_907";a:2:{s:9:"mailchimp";s:9:"STORENAME";s:7:"magento";s:1:"3";}'
            . 's:18:"_1469027411717_717";a:2:{s:9:"mailchimp";s:6:"PREFIX";s:7:"magento";s:1:"4";}'
            . 's:18:"_1469027418285_285";a:2:{s:9:"mailchimp";s:5:"FNAME";s:7:"magento";s:1:"5";}'
            . 's:18:"_1469027422918_918";a:2:{s:9:"mailchimp";s:5:"MNAME";s:7:"magento";s:1:"6";}'
            . 's:18:"_1469027429502_502";a:2:{s:9:"mailchimp";s:5:"LNAME";s:7:"magento";s:1:"7";}'
            . 's:18:"_1469027434574_574";a:2:{s:9:"mailchimp";s:6:"SUFFIX";s:7:"magento";s:1:"8";}'
            . 's:18:"_1469027444231_231";a:2:{s:9:"mailchimp";s:5:"EMAIL";s:7:"magento";s:1:"9";}'
            . 's:18:"_1469027453439_439";a:2:{s:9:"mailchimp";s:6:"CGROUP";s:7:"magento";s:2:"10";}'
            . 's:18:"_1469027462887_887";a:2:{s:9:"mailchimp";s:3:"DOB";s:7:"magento";s:2:"11";}'
            . 's:18:"_1469027480560_560";a:2:{s:9:"mailchimp";s:3:"TAX";s:7:"magento";s:2:"15";}'
            . 's:18:"_1469027486920_920";a:2:{s:9:"mailchimp";s:9:"CONFIRMED";s:7:"magento";s:2:"16";}'
            . 's:18:"_1469027496512_512";a:2:{s:9:"mailchimp";s:9:"CREATEDAT";s:7:"magento";s:2:"17";}'
            . 's:18:"_1469027502720_720";a:2:{s:9:"mailchimp";s:6:"GENDER";s:7:"magento";s:2:"18";}'
            . 's:18:"_1469027508616_616";a:2:{s:9:"mailchimp";s:9:"DISGRPCHG";s:7:"magento";s:2:"35";}'
            . 's:18:"_1472845935735_735";a:2:{s:9:"mailchimp";s:8:"BCOMPANY";s:7:"magento";s:15:"billing_company";}'
            . 's:18:"_1472846546252_252";a:2:{s:9:"mailchimp";s:8:"BCOUNTRY";s:7:"magento";s:15:"billing_country";}'
            . 's:18:"_1472846569989_989";a:2:'
            . '{s:9:"mailchimp";s:10:"BTELEPHONE";s:7:"magento";s:17:"billing_telephone";}'
            . 's:18:"_1472846572949_949";a:2:{s:9:"mailchimp";s:8:"BZIPCODE";s:7:"magento";s:15:"billing_zipcode";}'
            . 's:18:"_1472846578861_861";a:2:{s:9:"mailchimp";s:8:"SCOMPANY";s:7:"magento";s:16:"shipping_company";}'
            . 's:17:"_1472846584014_14";a:2:{s:9:"mailchimp";s:8:"SCOUNTRY";s:7:"magento";s:16:"shipping_country";}'
            . 's:18:"_1472846587534_534";a:2:'
            . '{s:9:"mailchimp";s:10:"STELEPHONE";s:7:"magento";s:18:"shipping_telephone";}'
            . 's:18:"_1472846591374_374";a:2:{s:9:"mailchimp";s:8:"SZIPCODE";s:7:"magento";s:16:"shipping_zipcode";}'
            . 's:18:"_1490127043147_147";a:2:{s:9:"mailchimp";s:3:"DOP";s:7:"magento";s:3:"dop";}}';
    }
}


