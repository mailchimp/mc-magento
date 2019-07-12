<?php

class Ebizmarts_MailChimp_Model_Api_PromoCodesTest extends PHPUnit_Framework_TestCase
{
    private $promoCodesApiMock;

    const BATCH_ID = 'storeid-1_PCD_2017-05-18-14-45-54-38849500';

    const PROMOCODE_ID = 603;

    const MC_STORE_ID = 'a1s2d3f4g5h6j7k8l9n0';

    public function setUp()
    {
        Mage::app('default');

        /** @var Ebizmarts_MailChimp_Model_Api_PromoCodes $apiPromoCodesMock promoCodesApiMock */
        $this->promoCodesApiMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_PromoCodes::class);
    }

    public function tearDown()
    {
        $this->promoCodesApiMock = null;
    }

    public function testCreateBatchJson()
    {
        $magentoStoreId = 1;
        $batchArray = array();
        $promoCodesApiMock = $this->promoCodesApiMock
            ->setMethods(array('_getDeletedPromoCodes', '_getNewPromoCodes'))
            ->getMock();

        $promoCodesApiMock->expects($this->once())->method('_getDeletedPromoCodes')->with(self::MC_STORE_ID)->willReturn($batchArray);
        $promoCodesApiMock->expects($this->once())->method('_getNewPromoCodes')->with(self::MC_STORE_ID, $magentoStoreId)->willReturn($batchArray);

        $promoCodesApiMock->createBatchJson(self::MC_STORE_ID, $magentoStoreId);
    }


    public function testMakePromoCodesCollection()
    {
        $magentoStoreId = 0;

        $promoCodesApiMock = $this->promoCodesApiMock
            ->setMethods(array('getPromoCodeResourceCollection', 'addWebsiteColumn', 'joinPromoRuleData', 'getMailChimpHelper'))
            ->getMock();

        $promoCodesCollectionMock = $this->getMockBuilder(Mage_SalesRule_Model_Resource_Coupon_Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mailChimpHelperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('addResendFilter'))
            ->getMock();

        $promoCodesApiMock->expects($this->once())->method('getMailChimpHelper')->willReturn($mailChimpHelperMock);

        $promoCodesApiMock->expects($this->once())->method('getPromoCodeResourceCollection')->willReturn($promoCodesCollectionMock);

        $mailChimpHelperMock->expects($this->once())->method('addResendFilter')->with($promoCodesCollectionMock, $magentoStoreId, Ebizmarts_MailChimp_Model_Config::IS_PROMO_CODE);

        $promoCodesApiMock->expects($this->once())->method('addWebsiteColumn')->with($promoCodesCollectionMock);
        $promoCodesApiMock->expects($this->once())->method('joinPromoRuleData')->with($promoCodesCollectionMock);

        $return = $promoCodesApiMock->makePromoCodesCollection($magentoStoreId);

        $this->assertContains(Mage_SalesRule_Model_Resource_Coupon_Collection::class, get_class($return));
    }

    public function testGetSyncDataTableName()
    {
        $promoCodesApiMock = $this->promoCodesApiMock
            ->setMethods(array('getCoreResource'))
            ->getMock();

        $coreResourceMock = $this->getMockBuilder(Mage_Core_Model_Resource::class)
            ->setMethods(array('getTableName'))
            ->getMock();

        $promoCodesApiMock->expects($this->once())->method('getCoreResource')->willReturn($coreResourceMock);

        $coreResourceMock->expects($this->once())->method('getTableName')->with('mailchimp/ecommercesyncdata')->willReturn('mailchimp_ecommerce_sync_data');

        $promoCodesApiMock->getSyncDataTableName();
    }

    public function testMarkAsDeleted()
    {
        $promoRuleId = 1;
        $promoCodesApiMock = $this->promoCodesApiMock
            ->setMethods(array('_setDeleted'))
            ->getMock();

        $promoCodesApiMock->expects($this->once())->method('_setDeleted')->with(self::PROMOCODE_ID, $promoRuleId);

        $promoCodesApiMock->markAsDeleted(self::PROMOCODE_ID, $promoRuleId);
    }

    public function testDeletePromoCodeSyncData()
    {
        $promoCodesApiMock = $this->promoCodesApiMock
            ->setMethods(array('getMailChimpHelper'))
            ->getMock();

        $mailChimpHelperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getEcommerceSyncDataItem'))
            ->getMock();

        $syncDataItemMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Ecommercesyncdata::class)
            ->disableOriginalConstructor()
            ->setMethods(array('delete'))
            ->getMock();

        $promoCodesApiMock->expects($this->once())->method('getMailChimpHelper')->willReturn($mailChimpHelperMock);

        $mailChimpHelperMock->expects($this->once())->method('getEcommerceSyncDataItem')->with(self::PROMOCODE_ID, Ebizmarts_MailChimp_Model_Config::IS_PROMO_CODE, self::MC_STORE_ID)->willReturn($syncDataItemMock);

        $syncDataItemMock->expects($this->once())->method('delete');

        $promoCodesApiMock->deletePromoCodeSyncData(self::PROMOCODE_ID, self::MC_STORE_ID);
    }
}
