<?php

/**
 * mailchimp-lib Magento Component
 *
 * @category  Ebizmarts
 * @package   mailchimp-lib
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Ebizmarts_MailChimp_Model_Api_SyncItem
{
    /**
     * @var Ebizmarts_MailChimp_Helper_Data
     */
    protected $_mailchimpHelper;

    /**
     * @var Ebizmarts_MailChimp_Helper_Date
     */
    protected $_mailchimpDateHelper;

    public function __construct()
    {
        $this->_mailchimpHelper = Mage::helper('mailchimp');
        $this->_mailchimpDateHelper = Mage::helper('mailchimp/date');
    }

    /**
     * @param $id
     * @param $mailchimpStoreId
     * @param null $syncDelta
     * @param null $syncError
     * @param int $syncModified
     * @param null $syncedFlag
     * @param null $syncDeleted
     * @param null $token
     * @param bool $saveOnlyIfExists
     * @param bool $allowBatchRemoval
     * @param int $deletedRelatedId
     */
    protected function _updateSyncData(
        $id,
        $mailchimpStoreId,
        $syncDelta = null,
        $syncError = null,
        $syncModified = 0,
        $syncDeleted = null,
        $syncedFlag = null,
        $token = null,
        $saveOnlyIfExists = false,
        $allowBatchRemoval = true,
        $deletedRelatedId = null
    ) {
        $type = $this->getClassConstant();

        if (!empty($type)) {
            $helper = $this->getHelper();
            $helper->saveEcommerceSyncData(
                $id,
                $type,
                $mailchimpStoreId,
                $syncDelta,
                $syncError,
                $syncModified,
                $syncDeleted,
                $token,
                $syncedFlag,
                $saveOnlyIfExists,
                $deletedRelatedId,
                $allowBatchRemoval
            );
        }
    }

    /**
     * @return Ebizmarts_MailChimp_Helper_Data
     */
    protected function getHelper()
    {
        return $this->_mailchimpHelper;
    }

    /**
     * @return Ebizmarts_MailChimp_Helper_Date
     */
    protected function getDateHelper()
    {
        return $this->_mailchimpDateHelper;
    }

    /**
     * @return mixed
     */
    public function getMailchimpEcommerceDataTableName()
    {
        return $this->getCoreResource()
            ->getTableName('mailchimp/ecommercesyncdata');
    }

    /**
     * @param $magentoStoreId
     * @return mixed
     */
    public function getWebSiteIdFromMagentoStoreId($magentoStoreId)
    {
        return Mage::getModel('core/store')->load($magentoStoreId)->getWebsiteId();
    }

    /**
     * @return Mage_Core_Model_Resource
     */
    public function getCoreResource()
    {
        return Mage::getSingleton('core/resource');
    }

    /**
     * @return string
     */
    protected function getClassConstant()
    {
        return null;
    }
}
