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
class Ebizmarts_MailChimp_Model_Resource_Ecommercesyncdata_Customers_Collection extends
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
     * @param Mage_Customer_Model_Resource_Customer_Collection $preFilteredCustomersCollection
     */
    public function joinLeftEcommerceSyncData($preFilteredCustomersCollection, $columns = array('m4m.*'))
    {
        $mailchimpTableName = $this->getMailchimpEcommerceDataTableName();
        $preFilteredCustomersCollection->getSelect()->joinLeft(
            array('m4m' => $mailchimpTableName),
            "m4m.related_id = main_table.entity_id AND m4m.type = '" . Ebizmarts_MailChimp_Model_Config::IS_CUSTOMER
            . "' AND m4m.mailchimp_store_id = '" . $this->getMailchimpStoreId() . "'",
            $columns
        );
    }
}
