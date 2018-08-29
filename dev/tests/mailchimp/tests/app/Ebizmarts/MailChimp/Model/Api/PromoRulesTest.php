<?php

class Ebizmarts_MailChimp_Model_Api_PromoRulesTest extends PHPUnit_Framework_TestCase
{
    private $promoRulesApiMock;

    const BATCH_ID = 'storeid-1_PRL_2017-05-18-14-45-54-38849500';

    const PROMORULE_ID = 603;

    public function setUp()
    {
        Mage::app('default');

        /** @var Ebizmarts_MailChimp_Model_Api_PromoRules $apiPromoRulesMock promoRulesApiMock */
        $this->promoRulesApiMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_PromoRules::class);
    }

    public function tearDown()
    {
        $this->promoRulesApiMock = null;
    }

    public function testCreateBatchJson()
    {
        $mailchimpStoreId = 'a1s2d3f4g5h6j7k8l9n0';
        $magentoStoreId = 1;
        $promoRulesArray = array(
            array(
                'method' => 'DELETE',
                'path' => '/ecommerce/stores/ef3bf57fb9bd695a02b7f7c7fb0d2db5/promo-rules/43',
                'operation_id' => 'storeid-2_PRL_2018-01-16-14-48-03-29881000_43',
                'body' => ''
            )
        );

        $promoRulesApiMock = $this->promoRulesApiMock
            ->setMethods(array('getMailChimpHelper', '_getModifiedAndDeletedPromoRules'))
            ->getMock();

        $mailChimpHelperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->setMethods(array('getDateMicrotime'))
            ->disableOriginalConstructor()
            ->getMock();

        $promoRulesApiMock->expects($this->once())->method('getMailChimpHelper')->willReturn($mailChimpHelperMock);
        $mailChimpHelperMock->expects($this->once())->method('getDateMicrotime')->willReturn('2017-10-23-19-34-31-92333600');
        $promoRulesApiMock->expects($this->once())->method('_getModifiedAndDeletedPromoRules')->with($mailchimpStoreId)->willReturn($promoRulesArray);

        $promoRulesApiMock->createBatchJson($mailchimpStoreId, $magentoStoreId);
    }


    /**
     * @param array $promoRuleData
     * @dataProvider getNewPromoRuleWithOutErrorDataProvider
     */

    public function testGetNewPromoRuleWithOutError($promoRuleData)
    {
        $mailchimpStoreId = 'a1s2d3f4g5h6j7k8l9n0';
        $magentoStoreId = 1;
        $ruleName = $promoRuleData['title'];
        $ruleSimpleAction = 'by_percent';
        $ruleIsActive = true;

        $promoRulesApiMock = $this->promoRulesApiMock
            ->disableOriginalConstructor()
            ->setMethods(array('getPromoRule', '_updateSyncData', 'getMailChimpHelper', 'getMailChimpDiscountAmount', 'getMailChimpType', 'getMailChimpTarget', 'ruleIsNotCompatible', 'ruleHasMissingInformation'))
            ->getMock();

        $promoRuleMock = $this->getMockBuilder(Mage_SalesRule_Model_Rule::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getRuleId', 'getName', 'getDescription', 'getFromDate', 'getToDate', 'getSimpleAction', 'getIsActive', 'setMailchimpSyncError'))
            ->getMock();

        $mailChimpHelperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->setMethods(array('getDateMicrotime'))
            ->disableOriginalConstructor()
            ->getMock();

        $mailChimpHelperMock->expects($this->once())->method('getDateMicrotime')->willReturn('2017-05-18-14-45-54-38849500');

        $promoRulesApiMock->expects($this->once())->method('getMailChimpHelper')->willReturn($mailChimpHelperMock);
        $promoRulesApiMock->expects($this->once())->method('getPromoRule')->with(self::PROMORULE_ID)->willReturn($promoRuleMock);
        $promoRulesApiMock->expects($this->once())->method('getMailChimpDiscountAmount')->with($promoRuleMock)->willReturn($promoRuleData['amount']);
        $promoRulesApiMock->expects($this->once())->method('getMailChimpType')->with($ruleSimpleAction)->willReturn($promoRuleData['type']);
        $promoRulesApiMock->expects($this->once())->method('getMailChimpTarget')->with($ruleSimpleAction)->willReturn($promoRuleData['target']);
        $promoRulesApiMock->expects($this->once())->method('ruleIsNotCompatible')->with($promoRuleData['ruleNotCompatibleParamCheck'])->willReturn(false);
        $promoRulesApiMock->expects($this->once())->method('ruleHasMissingInformation')->with($promoRuleData['ruleHasMissingParamCheck'])->willReturn(false);

        $promoRuleMock->expects($this->once())->method('getRuleId')->willReturn(self::PROMORULE_ID);
        $promoRuleMock->expects($this->exactly($promoRuleData['countName']))->method('getName')->willReturn($ruleName);
        $promoRuleMock->expects($this->exactly($promoRuleData['countDesc']))->method('getDescription')->willReturn($promoRuleData['description']);
        $promoRuleMock->expects($this->once())->method('getFromDate')->willReturn($promoRuleData['starts_at']);
        $promoRuleMock->expects($this->once())->method('getToDate')->willReturn($promoRuleData['ends_at']);
        $promoRuleMock->expects($this->once())->method('getSimpleAction')->willReturn($ruleSimpleAction);
        $promoRuleMock->expects($this->once())->method('getIsActive')->willReturn($ruleIsActive);

        $return = $promoRulesApiMock->getNewPromoRule(self::PROMORULE_ID, self::BATCH_ID, $mailchimpStoreId, $magentoStoreId);

        $this->assertEquals(4, count($return));
        $this->assertArrayHasKey("method", $return);
        $this->assertArrayHasKey("path", $return);
        $this->assertArrayHasKey("operation_id", $return);
        $this->assertArrayHasKey("body", $return);
        $this->assertEquals("POST", $return["method"]);
        $this->assertRegExp("/\/ecommerce\/stores\/(.*)\/promo-rules/", $return["path"]);
        $this->assertEquals(self::BATCH_ID . '_' . self::PROMORULE_ID, $return["operation_id"]);
    }

    public function getNewPromoRuleWithOutErrorDataProvider()
    {
        return array(
            'description exists' => array(
                array(
                    'id' => self::PROMORULE_ID,
                    'countDesc' => 2,
                    'countName' => 1,
                    'amount' => 0,
                    'title' => 'test promo',
                    'description' => 'testdesc',
                    'starts_at' => '2018-08-08',
                    'ends_at' => '2018-08-15',
                    'type' => 'percentage',
                    'target' => 'total',
                    'enabled' => true,
                    'ruleHasMissingParamCheck' => array(
                        'id' => self::PROMORULE_ID,
                        'amount' => 0,
                        'title' => 'test promo',
                        'description' => 'testdesc',
                        'starts_at' => '2018-08-08',
                        'ends_at' => '2018-08-15',
                        'type' => 'percentage',
                        'target' => 'total',
                        'enabled' => true,
                    ),
                    'ruleNotCompatibleParamCheck' => array(
                        'id' => self::PROMORULE_ID,
                        'amount' => 0,
                        'title' => 'test promo',
                        'description' => 'testdesc',
                        'starts_at' => '2018-08-08',
                        'ends_at' => '2018-08-15',
                        'type' => 'percentage',
                        'target' => 'total',
                        'enabled' => true,
                    )
                )),
            'no date' =>
                array(array(
                    'id' => self::PROMORULE_ID,
                    'amount' => 1,
                    'title' => 'test promo',
                    'countDesc' => 2,
                    'countName' => 1,
                    'description' => 'testdesc',
                    'type' => 'fixed',
                    'target' => 'total',
                    'enabled' => true,
                    'ruleHasMissingParamCheck' => array(
                        'id' => self::PROMORULE_ID,
                        'amount' => 1,
                        'title' => 'test promo',
                        'description' => 'testdesc',
                        'type' => 'fixed',
                        'target' => 'total',
                        'enabled' => true,
                    ),
                    'ruleNotCompatibleParamCheck' => array(
                        'id' => self::PROMORULE_ID,
                        'amount' => 1,
                        'title' => 'test promo',
                        'description' => 'testdesc',
                        'type' => 'fixed',
                        'target' => 'total',
                        'enabled' => true,
                    )
                )
                ),
            'description empty' => array(
                array(
                    'id' => self::PROMORULE_ID,
                    'amount' => 1.25,
                    'title' => 'test promo',
                    'countDesc' => 1,
                    'countName' => 2,
                    'description' => null,
                    'type' => 'percentage',
                    'target' => 'total',
                    'enabled' => true,
                    'ruleHasMissingParamCheck' => array(
                        'id' => self::PROMORULE_ID,
                        'amount' => 1.25,
                        'title' => 'test promo',
                        'description' => 'test promo',
                        'type' => 'percentage',
                        'target' => 'total',
                        'enabled' => true,
                    ),
                    'ruleNotCompatibleParamCheck' => array(
                        'id' => self::PROMORULE_ID,
                        'amount' => 1.25,
                        'title' => 'test promo',
                        'description' => 'test promo',
                        'type' => 'percentage',
                        'target' => 'total',
                        'enabled' => true,
                    )
                )
            )
        );
    }


    /**
     * @param array $promoRuleData
     * @dataProvider getNewPromoRuleWithErrorDataProvider
     */

    public function testGetNewPromoRuleWithError($promoRuleData)
    {
        return;
        $mailchimpStoreId = 'a1s2d3f4g5h6j7k8l9n0';
        $magentoStoreId = 1;
        $ruleName = $promoRuleData['title'];
        $ruleSimpleAction = 'by_percent';
        $ruleIsActive = true;
        $error = $promoRuleData['error'];

        $data = $promoRuleData;
        $data['description'] = ($promoRuleData['description'] ? $promoRuleData['description'] : $promoRuleData['title']);
        unset($data['countDesc']);
        unset($data['countName']);
        unset($data['error']);
        Mage::log($data, null, 'testPRL4.log', true);

        $promoRulesApiMock = $this->promoRulesApiMock
            ->disableOriginalConstructor()
            ->setMethods(array('getPromoRule', '_updateSyncData', 'getMailChimpHelper', 'getMailChimpDiscountAmount', 'getMailChimpType', 'getMailChimpTarget', 'ruleIsNotCompatible', 'ruleHasMissingInformation'))
            ->getMock();

        $promoRuleMock = $this->getMockBuilder(Mage_SalesRule_Model_Rule::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getRuleId', 'getName', 'getDescription', 'getFromDate', 'getToDate', 'getSimpleAction', 'getIsActive', 'setMailchimpSyncError'))
            ->getMock();

        $mailChimpHelperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->setMethods(array('getDateMicrotime'))
            ->disableOriginalConstructor()
            ->getMock();

        $promoRulesApiMock->expects($this->once())->method('getMailChimpHelper')->willReturn($mailChimpHelperMock);
        $promoRulesApiMock->expects($this->once())->method('getPromoRule')->with(self::PROMORULE_ID)->willReturn($promoRuleMock);
        $promoRulesApiMock->expects($this->once())->method('getMailChimpDiscountAmount')->with($promoRuleMock)->willReturn($promoRuleData['amount']);
        $promoRulesApiMock->expects($this->once())->method('getMailChimpType')->with($ruleSimpleAction)->willReturn($promoRuleData['type']);
        $promoRulesApiMock->expects($this->once())->method('getMailChimpTarget')->with($ruleSimpleAction)->willReturn($promoRuleData['target']);
        $promoRulesApiMock->expects($this->once())->method('ruleIsNotCompatible')->with($data)->willReturn(true);
        $promoRulesApiMock->expects($this->exactly($promoRuleData['countMissing']))->method('ruleHasMissingInformation')->with($data)->willReturn(true);

        $promoRuleMock->expects($this->once())->method('getRuleId')->willReturn(self::PROMORULE_ID);
        $promoRuleMock->expects($this->exactly($promoRuleData['countName']))->method('getName')->willReturn($ruleName);
        $promoRuleMock->expects($this->exactly($promoRuleData['countDesc']))->method('getDescription')->willReturn($promoRuleData['description']);
        $promoRuleMock->expects($this->once())->method('getFromDate')->willReturn($promoRuleData['starts_at']);
        $promoRuleMock->expects($this->once())->method('getToDate')->willReturn($promoRuleData['ends_at']);
        $promoRuleMock->expects($this->once())->method('getSimpleAction')->willReturn($ruleSimpleAction);
        $promoRuleMock->expects($this->once())->method('getIsActive')->willReturn($ruleIsActive);
        $promoRuleMock->expects($this->once())->method('setMailchimpSyncError')->with($error);

        $return = $promoRulesApiMock->getNewPromoRule(self::PROMORULE_ID, self::BATCH_ID, $mailchimpStoreId, $magentoStoreId);

        $this->assertEquals(0, count($return));
    }

//    public function getNewPromoRuleWithErrorDataProvider()
//    {
//        return array(
//            array(array('amount' => 0, 'type' => null, 'target' => 'per_item', 'error' => 'The rule type is not supported by the MailChimp schema.', 'isNotCompatible' => true)),
//            array(array('amount' => 1, 'type' => 'fixed', 'target' => null, 'error' => 'The rule type is not supported by the MailChimp schema.', 'isNotCompatible' => true)),
//            array(array('amount' => null, 'type' => 'percentage', 'target' => 'total', 'error' => 'There is required information by the MailChimp schema missing.', 'isNotCompatible' => false))
//        );
//
//    }

    public function getNewPromoRuleWithErrorDataProvider()
    {
        return array(
            array(
            'no type' =>
                    array(
                        'id' => self::PROMORULE_ID,
                        'countDesc' => 2,
                        'countName' => 1,
                        'countMissing' => 0,
                        'amount' => 0,
                        'title' => 'test promo',
                        'description' => 'testdesc',
                        'starts_at' => '2018-08-08',
                        'ends_at' => '2018-08-15',
                        'type' => null,
                        'target' => 'total',
                        'enabled' => true,
                        'error' => 'The rule type is not supported by the MailChimp schema.'
                    )),
             array(
             'no target' =>
                    array(
                    'id' => self::PROMORULE_ID,
                    'amount' => 1,
                    'title' => 'test promo',
                    'countDesc' => 2,
                    'countName' => 1,
                    'countMissing' => 0,
                    'description' => 'testdesc',
                    'type' => 'fixed',
                    'target' => null,
                    'enabled' => true,
                    'error' => 'The rule type is not supported by the MailChimp schema.'
                 )
                 ),
             array(
             'description empty' =>
                    array(
                        'id' => self::PROMORULE_ID,
                        'amount' => null,
                        'title' => 'test promo',
                        'countDesc' => 1,
                        'countName' => 2,
                        'countMissing' => 1,
                        'description' => null,
                        'type' => 'percentage',
                        'target' => 'total',
                        'enabled' => true,
                        'error' => 'There is required information by the MailChimp schema missing.'
                        )
                          )
                    );

    }

    public function testMakePromoRulesCollection()
    {
        $magentoStoreId = 1;
        $websiteId = 1;

        $promoRulesApiMock = $this->promoRulesApiMock
            ->setMethods(array('getPromoRuleResourceCollection', 'getWebsiteIdByStoreId'))
            ->getMock();

        $promoRulesCollectionMock = $this->getMockBuilder(Mage_SalesRule_Model_Resource_Rule_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('addWebsiteFilter'))
            ->getMock();

        $promoRulesApiMock->expects($this->once())->method('getPromoRuleResourceCollection')->willReturn($promoRulesCollectionMock);
        $promoRulesApiMock->expects($this->once())->method('getWebsiteIdByStoreId')->with($magentoStoreId)->willReturn($websiteId);

        $promoRulesCollectionMock->expects($this->once())->method('addWebsiteFilter')->with($websiteId);

        $return = $promoRulesApiMock->makePromoRulesCollection($magentoStoreId);

        $this->assertContains(Mage_SalesRule_Model_Resource_Rule_Collection::class, get_class($return));
    }

    public function testGetSyncDataTableName()
    {
        $promoRulesApiMock = $this->promoRulesApiMock
            ->setMethods(array('getCoreResource'))
            ->getMock();

        $coreResourceMock = $this->getMockBuilder(Mage_Core_Model_Resource::class)
            ->setMethods(array('getTableName'))
            ->getMock();

        $promoRulesApiMock->expects($this->once())->method('getCoreResource')->willReturn($coreResourceMock);

        $coreResourceMock->expects($this->once())->method('getTableName')->with('mailchimp/ecommercesyncdata')->willReturn('mailchimp_ecommerce_sync_data');

        $promoRulesApiMock->getSyncDataTableName();
    }

    public function testUpdate()
    {
        $promoRulesApiMock = $this->promoRulesApiMock
            ->setMethods(array('_setModified'))
            ->getMock();

        $promoRulesApiMock->expects($this->once())->method('_setModified')->with(self::PROMORULE_ID);

        $promoRulesApiMock->update(self::PROMORULE_ID);
    }

    public function testMarkAsDeleted()
    {
        $promoRulesApiMock = $this->promoRulesApiMock
            ->setMethods(array('_setDeleted'))
            ->getMock();

        $promoRulesApiMock->expects($this->once())->method('_setDeleted')->with(self::PROMORULE_ID);

        $promoRulesApiMock->markAsDeleted(self::PROMORULE_ID);
    }

}
