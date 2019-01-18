<?php
/**
 * mc-magento Magento Component
 *
 * @category  Ebizmarts
 * @package   mc-magento
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     5/16/16 6:23 PM
 * @file:     Interestgroup.php
 */

class Ebizmarts_MailChimp_Model_Interestgroup extends Mage_Core_Model_Abstract
{
    /**
     * Initialize model
     *
     * @return void
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('mailchimp/interestgroup');
    }

    public function getByRelatedIdStoreId($customerId, $subscriberId, $storeId)
    {
        $this->addData($this->getResource()->getByRelatedIdStoreId($customerId, $subscriberId, $storeId));
        return $this;
    }
}
