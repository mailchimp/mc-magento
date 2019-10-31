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
 * @file:     MailchimpSychBatches.php
 */

class Ebizmarts_MailChimp_Model_Ecommercesyncdata extends Mage_Core_Model_Abstract
{
    /**
     * Initialize model
     *
     * @return void
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('mailchimp/ecommercesyncdata');
    }

    /**
     * Save entry for ecommerce_sync_data table overwriting old item if exists or creating a new one if it does not.
     *
     * @param       $itemId
     * @param       $itemType
     * @param       $mailchimpStoreId
     * @param null  $syncDelta
     * @param null  $syncError
     * @param int   $syncModified
     * @param null  $syncDeleted
     * @param null  $token
     * @param null  $syncedFlag
     * @param bool  $saveOnlyIfexists
     * @param null  $deletedRelatedId
     * @param bool  $allowBatchRemoval
     */
    public function saveEcommerceSyncData(
        $itemId,
        $itemType,
        $mailchimpStoreId,
        $syncDelta = null,
        $syncError = null,
        $syncModified = 0,
        $syncDeleted = null,
        $token = null,
        $syncedFlag = null,
        $saveOnlyIfexists = false,
        $deletedRelatedId = null,
        $allowBatchRemoval = true
    ) {
        $ecommerceSyncDataItem = $this->getEcommerceSyncDataItem($itemId, $itemType, $mailchimpStoreId);

        if (!$saveOnlyIfexists || $ecommerceSyncDataItem->getMailchimpSyncDelta()) {
            $this->setEcommerceSyncDataItemValues(
                $itemType, $syncDelta, $syncError, $syncModified, $syncDeleted,
                $token, $syncedFlag, $deletedRelatedId, $allowBatchRemoval, $ecommerceSyncDataItem
            );

            $ecommerceSyncDataItem->save();
        }
    }

    /**
     *  Load Ecommerce Sync Data Item if exists or set the values for a new one and return it.
     *
     * @param  $itemId
     * @param  $itemType
     * @param  $mailchimpStoreId
     * @return Ebizmarts_MailChimp_Model_Ecommercesyncdata
     */
    public function getEcommerceSyncDataItem($itemId, $itemType, $mailchimpStoreId)
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('related_id', array('eq' => $itemId))
            ->addFieldToFilter('type', array('eq' => $itemType))
            ->addFieldToFilter('mailchimp_store_id', array('eq' => $mailchimpStoreId))
            ->setCurPage(1)
            ->setPageSize(1);

        if ($collection->getSize()) {
            $ecommerceSyndDataItem = $collection->getLastItem();
        } else {
            $ecommerceSyndDataItem = $this->setData("related_id", $itemId)
                ->setData("type", $itemType)
                ->setData("mailchimp_store_id", $mailchimpStoreId);
        }

        return $ecommerceSyndDataItem;
    }

    /**
     * @param $itemId
     * @param $itemType
     * @return Ebizmarts_MailChimp_Model_Resource_Ecommercesyncdata_Collection
     */
    public function getAllEcommerceSyncDataItemsPerId($itemId, $itemType)
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('related_id', array('eq' => $itemId))
            ->addFieldToFilter('type', array('eq' => $itemType));

        return $collection;
    }
}
