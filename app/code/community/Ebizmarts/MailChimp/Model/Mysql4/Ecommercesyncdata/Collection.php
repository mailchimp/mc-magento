<?php
/**
 * mc-magento Magento Component
 *
 * @category  Ebizmarts
 * @package   mc-magento
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     5/16/16 6:53 PM
 * @file:     Collection.php
 */

class Ebizmarts_MailChimp_Model_Mysql4_Ecommercesyncdata_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{

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
}