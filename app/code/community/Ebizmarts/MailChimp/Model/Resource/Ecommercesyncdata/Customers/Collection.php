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
    public function joinLeftEcommerceSyncData($preFilteredCustomersCollection)
    {
        $mailchimpTableName = $this->getMailchimpEcommerceDataTableName();
        $joinCondition      = "m4m.related_id = e.entity_id AND m4m.type = '%s' AND m4m.mailchimp_store_id = '%s'";
        $preFilteredCustomersCollection->getSelect()->joinLeft(
            array("m4m" => $mailchimpTableName),
            sprintf($joinCondition, Ebizmarts_MailChimp_Model_Config::IS_CUSTOMER, $this->getMailchimpStoreId())
        );
    }
}
