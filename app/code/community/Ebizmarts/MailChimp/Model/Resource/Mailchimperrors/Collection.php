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
class Ebizmarts_MailChimp_Model_Resource_Mailchimperrors_Collection extends
    Mage_Core_Model_Resource_Db_Collection_Abstract
{

    /**
     * Set resource type
     *
     * @return void
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('mailchimp/mailchimperrors');
    }
}
