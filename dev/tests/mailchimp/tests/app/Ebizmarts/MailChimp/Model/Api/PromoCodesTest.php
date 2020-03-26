<?php

class Ebizmarts_MailChimp_Model_Api_PromoCodesTest extends PHPUnit_Framework_TestCase
{
    protected $_promoCodesApiMock;

    const BATCH_ID = 'storeid-1_PCD_2017-05-18-14-45-54-38849500';

    const PROMOCODE_ID = 603;

    const MC_STORE_ID = 'a1s2d3f4g5h6j7k8l9n0';
    const STORE_ID = '1';

    public function setUp()
    {
        Mage::app('default');

        /**
         * @var Ebizmarts_MailChimp_Model_Api_PromoCodes $apiPromoCodesMock promoCodesApiMock
         */
        $this->_promoCodesApiMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_PromoCodes::class);
    }

    public function tearDown()
    {
        $this->_promoCodesApiMock = null;
    }

    public function testCreateBatchJson()
    {
        $batchArray = array();
        $promoCodesApiMock = $this->_promoCodesApiMock
            ->setMethods(
                array('getMailchimpStoreId', 'getMagentoStoreId', 'createEcommercePromoCodesCollection',
                    'getDateHelper', '_getDeletedPromoCodes', '_getNewPromoCodes')
            )->getMock();

        $promoCollectionResourceMock = $this
            ->getMockBuilder(Ebizmarts_MailChimp_Model_Resource_Ecommercesyncdata_PromoCodes_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('setMailchimpStoreId', 'setStoreId'))->getMock();

        $mailchimpDateHelperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Date::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getDateMicrotime'))->getMock();

        $promoCodesApiMock->expects($this->once())->method('getMailchimpStoreId')->willReturn(self::MC_STORE_ID);
        $promoCodesApiMock->expects($this->once())->method('getMagentoStoreId')->willReturn(self::STORE_ID);
        $promoCodesApiMock->expects($this->once())->method('createEcommercePromoCodesCollection')
            ->willReturn($promoCollectionResourceMock);

        $promoCollectionResourceMock->expects($this->once())->method('setMailchimpStoreId')->with(self::MC_STORE_ID);
        $promoCollectionResourceMock->expects($this->once())->method('setStoreId')->with(self::STORE_ID);

        $promoCodesApiMock->expects($this->once())->method('getDateHelper')->willReturn($mailchimpDateHelperMock);

        $mailchimpDateHelperMock->expects($this->once())->method('getDateMicrotime');

        $promoCodesApiMock->expects($this->once())->method('_getDeletedPromoCodes')->willReturn($batchArray);

        $promoCodesApiMock
            ->expects($this->once())
            ->method('_getNewPromoCodes')
            ->willReturn($batchArray);

        $promoCodesApiMock->createBatchJson();
    }


    public function testMakePromoCodesCollection()
    {
        $magentoStoreId = 0;
        $promoCodesApiMock = $this->_promoCodesApiMock
            ->setMethods(
                array(
                    'getHelper', 'getPromoCodeResourceCollection',
                    'getEcommercePromoCodesCollection')
            )->getMock();

        $promoCodesCollectionMock = $this->getMockBuilder(Mage_SalesRule_Model_Resource_Coupon_Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mailChimpHelperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('addResendFilter'))
            ->getMock();

        $promoCollectionResourceMock = $this
            ->getMockBuilder(Ebizmarts_MailChimp_Model_Resource_Ecommercesyncdata_PromoCodes_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('addWebsiteColumn', 'joinPromoRuleData'))
            ->getMock();

        $promoCodesApiMock->expects($this->once())->method('getHelper')->willReturn($mailChimpHelperMock);
        $promoCodesApiMock->expects($this->once())->method('getPromoCodeResourceCollection')
            ->willReturn($promoCodesCollectionMock);
        $mailChimpHelperMock->expects($this->once())->method('addResendFilter')
            ->with($promoCodesCollectionMock, $magentoStoreId, Ebizmarts_MailChimp_Model_Config::IS_PROMO_CODE);
        $promoCodesApiMock->expects($this->once())->method('getEcommercePromoCodesCollection')
            ->willReturn($promoCollectionResourceMock);

        $promoCollectionResourceMock->expects($this->once())->method('addWebsiteColumn')
            ->with($promoCodesCollectionMock);

        $promoCollectionResourceMock->expects($this->once())->method('joinPromoRuleData')
            ->with($promoCodesCollectionMock);

        $return = $promoCodesApiMock->makePromoCodesCollection($magentoStoreId);

        $this->assertContains(Mage_SalesRule_Model_Resource_Coupon_Collection::class, get_class($return));
    }

    public function testMarkAsDeleted()
    {
        $promoRuleId = 1;
        $promoCodesApiMock = $this->_promoCodesApiMock
            ->setMethods(array('_setDeleted'))
            ->getMock();

        $promoCodesApiMock->expects($this->once())->method('_setDeleted')->with(self::PROMOCODE_ID, $promoRuleId);

        $promoCodesApiMock->markAsDeleted(self::PROMOCODE_ID, $promoRuleId);
    }

    public function testDeletePromoCodesSyncDataByRule()
    {
        $promoRuleId = 1;
        $promoCodesIds = array();
        $syncDataItems = array();

        $promoCodesApiMock = $this->_promoCodesApiMock
            ->setMethods(array('getPromoCodesForRule', 'getMailchimpEcommerceSyncDataModel'))
            ->getMock();

        $promoRuleMock = $this->getMockBuilder(Mage_SalesRule_Model_Rule::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getRelatedId'))
            ->getMock();

        $syncDataItemCollectionMock = $this
            ->getMockBuilder(Ebizmarts_MailChimp_Model_Resource_Ecommercesyncdata_Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $syncDataItemMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Ecommercesyncdata::class)
            ->disableOriginalConstructor()
            ->setMethods(array('delete', 'getAllEcommerceSyncDataItemsPerId'))
            ->getMock();

        $promoRuleMock->expects($this->once())->method('getRelatedId')->willReturn($promoRuleId);

        $promoCodesIds[] = self::PROMOCODE_ID;
        $promoCodesApiMock
            ->expects($this->once())
            ->method('getPromoCodesForRule')
            ->with($promoRuleId)
            ->willReturn($promoCodesIds);
        $promoCodesApiMock->expects($this->once())
            ->method('getMailchimpEcommerceSyncDataModel')
            ->willReturn($syncDataItemMock);

        $syncDataItemMock
            ->expects($this->once())
            ->method('getAllEcommerceSyncDataItemsPerId')
            ->with(self::PROMOCODE_ID, Ebizmarts_MailChimp_Model_Config::IS_PROMO_CODE)
            ->willReturn($syncDataItemCollectionMock);

        $syncDataItems[] = $syncDataItemMock;

        $syncDataItemCollectionMock
            ->expects($this->once())
            ->method("getIterator")
            ->willReturn(new ArrayIterator($syncDataItems));

        $syncDataItemMock->expects($this->once())->method('delete');

        $promoCodesApiMock->deletePromoCodesSyncDataByRule($promoRuleMock);
    }

    public function testDeletePromoCodeSyncData()
    {
        $promoCodesApiMock = $this->_promoCodesApiMock
            ->setMethods(array('getMailchimpEcommerceSyncDataModel'))
            ->getMock();

        $syncDataItemMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Ecommercesyncdata::class)
            ->disableOriginalConstructor()
            ->setMethods(array('delete', 'getEcommerceSyncDataItem'))
            ->getMock();

        $promoCodesApiMock
            ->expects($this->once())
            ->method('getMailchimpEcommerceSyncDataModel')
            ->willReturn($syncDataItemMock);

        $syncDataItemMock
            ->expects($this->once())
            ->method('getEcommerceSyncDataItem')
            ->with(self::PROMOCODE_ID, Ebizmarts_MailChimp_Model_Config::IS_PROMO_CODE)
            ->willReturn($syncDataItemMock);

        $syncDataItemMock->expects($this->once())->method('delete');

        $promoCodesApiMock->deletePromoCodeSyncData(self::PROMOCODE_ID);
    }
}
