<?php
/**
 * mc-magento Magento Component
 *
 * @category  Ebizmarts
 * @package   mc-magento
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     6/9/16 4:46 PM
 * @file:     Interestgroup.php
 */
class Ebizmarts_MailChimp_Model_Mysql4_Interestgroup extends Mage_Core_Model_Mysql4_Abstract
{

    /**
     * Initialize
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('mailchimp/interestgroup', 'id');
    }

    public function getByRelatedIdStoreId($customerId = 0, $subscriberId = 0, $storeId)
    {
        $read = $this->_getReadAdapter();
        $select = $read->select()
            ->from($this->getMainTable())
            ->where('store_id = ?', $storeId)
            ->where('(' . $read->quoteInto('customer_id = ?', $customerId) . ' OR ' . $read->quoteInto('subscriber_id = ?', $subscriberId) . ')');

        $result = $read->fetchRow($select);

        if (!$result) {
            return array();
        }

        return $result;
    }
}
