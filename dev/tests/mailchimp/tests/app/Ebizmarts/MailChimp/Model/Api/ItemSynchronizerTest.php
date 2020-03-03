<?php

class Ebizmarts_MailChimp_Model_Api_ItemSynchronizerTest extends PHPUnit_Framework_TestCase
{
    protected $_syncItemApiMock;

    public function setUp()
    {
        Mage::app('default');

        /**
         * @var Ebizmarts_MailChimp_Model_Api_PromoRules $apiPromoRulesMock promoRulesApiMock
         */
        $this->_syncItemApiMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_ItemSynchronizer::class);
    }

    public function tearDown()
    {
        $this->_syncItemApiMock = null;
    }

    public function testGetSyncDataTableName()
    {
        $_syncItemApiMock = $this->_syncItemApiMock
            ->setMethods(array('getCoreResource'))
            ->getMock();

        $coreResourceMock = $this->getMockBuilder(Mage_Core_Model_Resource::class)
            ->setMethods(array('getTableName'))
            ->getMock();

        $_syncItemApiMock->expects($this->once())->method('getCoreResource')->willReturn($coreResourceMock);

        $coreResourceMock
            ->expects($this->once())
            ->method('getTableName')
            ->with('mailchimp/ecommercesyncdata')
            ->willReturn('mailchimp_ecommerce_sync_data');

        $_syncItemApiMock->getMailchimpEcommerceDataTableName();
    }
}
