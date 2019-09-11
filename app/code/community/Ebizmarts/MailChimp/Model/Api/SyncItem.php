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

        if ($syncError !== null) {
            $this->logSyncError(
                "Update Sync Data error: " . $syncError,
                $type,
                $mailchimpStoreId
            );
        }

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
     * @param $error
     * @param null $mailchimpStoreId
     * @param string $type
     * @param null $title
     * @param null $status
     * @param null $originalId
     * @param int $batchId
     * @param null $storeId
     * @param null $regType
     */
    protected function logSyncError(
        $error,
        $regType = null,
        $mailchimpStoreId = null,
        $storeId = null,
        $type = 'magento_side_error',
        $title = null,
        $status = null,
        $originalId = null,
        $batchId = -1
    ) {
        $this->getHelper()->logError($error);

        try {
            $this->_logMailchimpError(
                $error, $mailchimpStoreId, $type, $title,
                $status, $originalId, $batchId, $storeId, $regType
            );
        } catch (Exception $e) {
            $this->getHelper()->logError($e->getMessage());
        }
    }

    /**
     * @param $error
     * @param $mailchimpStoreId
     * @param $type
     * @param $title
     * @param $status
     * @param $originalId
     * @param $batchId
     * @param $storeId
     * @param $regType
     *
     * @throws Exception
     */
    protected function _logMailchimpError(
        $error,
        $mailchimpStoreId,
        $type,
        $title,
        $status,
        $originalId,
        $batchId,
        $storeId,
        $regType
    ) {
        $mailchimpErrors = Mage::getModel('mailchimp/mailchimperrors');

        $mailchimpErrors->setType($type);
        $mailchimpErrors->setTitle($title);
        $mailchimpErrors->setStatus($status);
        $mailchimpErrors->setErrors($error);
        $mailchimpErrors->setRegtype($regType);
        $mailchimpErrors->setOriginalId($originalId);
        $mailchimpErrors->setBatchId($batchId);
        $mailchimpErrors->setStoreId($storeId);
        $mailchimpErrors->setMailchimpStoreId($mailchimpStoreId);

        $mailchimpErrors->save();
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
