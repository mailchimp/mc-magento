<?php

/**
 * mc-magento Magento Component
 *
 * @category  Ebizmarts
 * @package   mc-magento
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     2019-11-04 17:32
 */
class Ebizmarts_MailChimp_Model_Resource_Ecommercesyncdata_PromoCodes_Collection extends
    Ebizmarts_MailChimp_Model_Resource_Ecommercesyncdata_Collection
{

    /**
     * Set resource type
     *
     * @return void
     */
    public function _construct()
    {
        parent::_construct();
    }

    /**
     * @param Mage_SalesRule_Model_Resource_Coupon_Collection $preFilteredPromoCodesCollection
     */
    public function joinLeftEcommerceSyncData($preFilteredPromoCodesCollection, $columns = array('m4m.*'))
    {
        $joinCondition = "m4m.related_id = main_table.coupon_id AND m4m.type = '%s' AND m4m.mailchimp_store_id = '%s'";
        $mailchimpTableName = $this->getMailchimpEcommerceDataTableName();
        $preFilteredPromoCodesCollection->getSelect()->joinLeft(
            array("m4m" => $mailchimpTableName),
            sprintf($joinCondition, Ebizmarts_MailChimp_Model_Config::IS_PROMO_CODE, $this->getMailchimpStoreId()),
            $columns
        );
    }

    /**
     * @param $collection Mage_SalesRule_Model_Resource_Coupon_Collection
     */
    public function addWebsiteColumn($collection)
    {
        $websiteTableName = $this->getCoreResource()->getTableName('salesrule/website');
        $collection->getSelect()->joinLeft(
            array('website' => $websiteTableName),
            'main_table.rule_id=website.rule_id',
            array('*')
        );
    }

    /**
     * @param $collection Mage_SalesRule_Model_Resource_Coupon_Collection
     */
    public function joinPromoRuleData($collection)
    {
        $salesRuleName = $this->getCoreResource()->getTableName('salesrule/rule');
        $conditions = 'main_table.rule_id=salesrule.rule_id';
        $collection->getSelect()->joinLeft(
            array('salesrule' => $salesRuleName),
            $conditions,
            array('use_auto_generation' => 'use_auto_generation')
        );
    }
}
