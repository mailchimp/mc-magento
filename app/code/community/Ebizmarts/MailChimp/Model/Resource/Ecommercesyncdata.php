<?php

/**
 * mc-magento Magento Component
 *
 * @category  Ebizmarts
 * @package   mc-magento
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     2019-10-02 15:53
 * @file:     Ecommercesyncdata.php
 */
class Ebizmarts_MailChimp_Model_Resource_Ecommercesyncdata extends Mage_Core_Model_Resource_Db_Abstract
{

    /**
     * Initialize
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('mailchimp/ecommercesyncdata', 'id');
    }
    public function markAllAsModified($id,$type)
    {
        $connection =  $this->_getWriteAdapter();
        $connection->update(
            $this->getMainTable(),
            array('mailchimp_sync_modified'=>1),
            array('related_id = ?'=> $id, 'type = ?'=>$type)
        );
        return $this;
    }
}
