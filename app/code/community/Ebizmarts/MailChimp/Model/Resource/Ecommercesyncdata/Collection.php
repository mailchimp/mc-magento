<?php

/**
 * mc-magento Magento Component
 *
 * @category  Ebizmarts
 * @package   mc-magento
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     2019-10-02 15:57
 */
class Ebizmarts_MailChimp_Model_Resource_Ecommercesyncdata_Collection extends
    Mage_Core_Model_Resource_Db_Collection_Abstract
{
    /**
     * @var int
     */
    protected $_storeId;

    /**
     * @var string
     */
    protected $_mailchimpStoreId;

    /**
     * Set resource type
     *
     * @return void
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('mailchimp/ecommercesyncdata');
    }

    public function getMailchimpEcommerceDataTableName()
    {
        return $this->getCoreResource()
            ->getTableName('mailchimp/ecommercesyncdata');
    }

    /**
     * @return Mage_Core_Model_Resource
     */
    public function getCoreResource()
    {
        return Mage::getSingleton('core/resource');
    }

    /**
     * @return int
     */
    public function getStoreId()
    {
        return $this->_storeId;
    }

    /**
     * @param int $storeId
     */
    public function setStoreId($storeId)
    {
        $this->_storeId = $storeId;
    }

    /**
     * @return string
     */
    public function getMailchimpStoreId()
    {
        return $this->_mailchimpStoreId;
    }

    /**
     * @param string $mailchimpStoreId
     */
    public function setMailchimpStoreId($mailchimpStoreId)
    {
        $this->_mailchimpStoreId = $mailchimpStoreId;
    }

    /**
     * @param $collection Varien_Data_Collection_Db
     * @param $limit
     */
    public function limitCollection($collection, $limit)
    {
        $collection->getSelect()->limit($limit);
    }

    /**
     * @param $collection Varien_Data_Collection_Db
     * @param $where
     * @param int null $limit
     */
    public function addWhere($collection, $where = null, $limit = null)
    {
        if (isset($where)) {
            $collection->getSelect()->where($where);
        }

        if (isset($limit)) {
            $collection->getSelect()->limit($limit);
        }
    }
}
