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
class Ebizmarts_MailChimp_Model_Resource_Ecommercesyncdata_Product_Collection extends
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
     * @param Mage_Sales_Model_Resource_Quote_Collection $preFilteredQuotesCollection
     */
    public function joinLeftEcommerceSyncData($preFilteredQuotesCollection)
    {
        $mailchimpTableName = $this->getMailchimpEcommerceDataTableName();
        $preFilteredQuotesCollection->getSelect()->joinLeft(
            array('m4m' => $mailchimpTableName),
            "m4m.related_id = main_table.entity_id AND m4m.type = '" . Ebizmarts_MailChimp_Model_Config::IS_PRODUCT
            . "' AND m4m.mailchimp_store_id = '" . $this->getMailchimpStoreId() . "'",
            array('m4m.*')
        );
    }

    /**
     * @param Mage_Catalog_Model_Resource_Product_Collection $preFilteredQuotesCollection
     * @param $where
     * @param int null $limit
     */
    public function addWhere($preFilteredQuotesCollection, $where = null, $limit = null)
    {
        if (isset($where)) {
            $preFilteredQuotesCollection->getSelect()->where($where);
        }

        if (isset($limit)) {
            $preFilteredQuotesCollection->getSelect()->limit($limit);
        }
    }

    /**
     * @param $collection
     * @param $joinCondition
     */
    public function executeMailchimpDataJoin($collection, $joinCondition)
    {
        $mailchimpTableName = $this->getMailchimpEcommerceDataTableName();
        $collection->getSelect()->joinLeft(
            array("m4m" => $mailchimpTableName),
            sprintf($joinCondition, Ebizmarts_MailChimp_Model_Config::IS_PRODUCT, $this->getMailchimpStoreId()),
            array(
                "m4m.related_id",
                "m4m.type",
                "m4m.mailchimp_store_id",
                "m4m.mailchimp_sync_delta",
                "m4m.mailchimp_sync_modified",
                "m4m.mailchimp_synced_flag"
            )
        );
    }

    /**
     * @param $deletedProducts
     */
    public function joinMailchimpSyncDataDeleted($deletedProducts)
    {
        $this->joinLeftEcommerceSyncData($deletedProducts);
        $deletedProducts->getSelect()->where("m4m.mailchimp_sync_deleted = 1 AND m4m.mailchimp_sync_error = ''");
        $deletedProducts->getSelect()->limit($this->getBatchLimitFromConfig());
    }

    public function addJoinLeft($collection, array $array, $colString)
    {
        $collection->getSelect()->joinLeft($array, $colString);
    }

    public function resetColumns($collection, $reset, $columns)
    {
        $collection->getSelect()->reset($reset)->columns($columns);
    }

}
