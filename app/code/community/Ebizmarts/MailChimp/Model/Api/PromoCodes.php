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
class Ebizmarts_MailChimp_Model_Api_PromoCodes
{
    const BATCH_LIMIT = 50;
    const TYPE_FIXED = 'fixed';
    const TYPE_PERCENTAGE = 'percentage';
    const TARGET_PER_ITEM = 'per_item';
    const TARGET_TOTAL = 'total';
    const TARGET_SHIPPING = 'shipping';

    protected $_batchId;
    protected $mailchimpHelper;
    protected $apiPromoRules;

    public function __construct()
    {
        $this->mailchimpHelper = Mage::helper('mailchimp');
        $this->apiPromoRules = Mage::getModel('mailchimp/api_promoRules');
    }

    public function createBatchJson($mailchimpStoreId, $magentoStoreId)
    {
        $batchArray = array();
        $this->_batchId = 'storeid-' . $magentoStoreId . '_' . Ebizmarts_MailChimp_Model_Config::IS_PROMO_CODE . '_' . Mage::helper('mailchimp')->getDateMicrotime();
        $batchArray = array_merge($batchArray, $this->_getDeletedPromoCodes($mailchimpStoreId, $magentoStoreId));
        $batchArray = array_merge($batchArray, $this->_getModifiedPromoCodes($mailchimpStoreId, $magentoStoreId));
        $batchArray = array_merge($batchArray, $this->_getNewPromoCodes($mailchimpStoreId, $magentoStoreId));

        return $batchArray;
    }

    protected function _getDeletedPromoCodes($mailchimpStoreId, $magentoStoreId)
    {
        $batchArray = array();
        $deletedPromoCodes = $this->makePromoCodesCollection();
        $this->joinMailchimpSyncDataWithoutWhere($deletedPromoCodes, $mailchimpStoreId);

        // filter promo rules that were modified
        $websiteId = Mage::getModel('core/store')->load($magentoStoreId)->getWebsiteId();
        $deletedPromoCodes->getSelect()->where("m4m.mailchimp_sync_deleted = 1 AND website.website_id = ".$websiteId);
        $deletedPromoCodes->getSelect()->limit($this->getBatchLimitFromConfig());

        $counter = 0;
        foreach ($deletedPromoCodes as $promoCode) {
            $ruleId = $promoCode->getRuleId();
            $codeId = $promoCode->getCouponId();
            $batchArray[$counter]['method'] = "DELETE";
            $batchArray[$counter]['path'] = '/ecommerce/stores/' . $mailchimpStoreId . '/promo-rules/' . $ruleId . '/promo-codes/' . $codeId;
            $batchArray[$counter]['operation_id'] = $this->_batchId . '_' . $codeId;
            $batchArray[$counter]['body'] = '';
            $this->_deleteSyncData($codeId, $mailchimpStoreId);
            $counter++;
        }

        return $batchArray;
    }

    protected function _getModifiedPromoCodes($mailchimpStoreId, $magentoStoreId)
    {
        $batchArray = array();
        $modifiedPromoCodes = $this->makePromoCodesCollection();
        $this->joinMailchimpSyncDataWithoutWhere($modifiedPromoCodes, $mailchimpStoreId);

        // filter promo rules that were modified
        $websiteId = Mage::getModel('core/store')->load($magentoStoreId)->getWebsiteId();
        $modifiedPromoCodes->getSelect()->where("m4m.mailchimp_sync_modified = 1 AND website.website_id = ".$websiteId);
        // limit the collection
        $modifiedPromoCodes->getSelect()->limit($this->getBatchLimitFromConfig());
        $counter = 0;
        foreach ($modifiedPromoCodes as $promoCode) {
            try {
                $ruleId = $promoCode->getRuleId();
                $codeId = $promoCode->getCouponId();
                $promoCodeJson = json_encode($this->generateCodeData($promoCode, $magentoStoreId));
                if (!empty($promoCodeJson)) {
                    $batchArray[$counter]['method'] = "PATCH";
                    $batchArray[$counter]['path'] = '/ecommerce/stores/' . $mailchimpStoreId . '/promo-rules/' . $ruleId . '/promo-codes/' . $codeId;
                    $batchArray[$counter]['operation_id'] = $this->_batchId . '_' . $codeId;
                    $batchArray[$counter]['body'] = $promoCodeJson;
                } else {
                    $error = Mage::helper('mailchimp')->__('Something went wrong when retrieving the information.');
                    $this->_updateSyncData($codeId, $mailchimpStoreId, Varien_Date::now(), $error);
                    continue;
                }

                //update promo rule delta
                $this->_updateSyncData($codeId, $mailchimpStoreId, Varien_Date::now());
                $counter++;
            } catch (Exception $e) {
                Mage::helper('mailchimp')->logError($e->getMessage(), $magentoStoreId);
            }
        }

        return $batchArray;
    }

    protected function _getNewPromoCodes($mailchimpStoreId, $magentoStoreId)
    {
        $batchArray = array();
        $newPromoCodes = $this->makePromoCodesCollection();

        $this->joinMailchimpSyncDataWithoutWhere($newPromoCodes, $mailchimpStoreId);
        // be sure that the orders are not in mailchimp
        $websiteId = Mage::getModel('core/store')->load($magentoStoreId)->getWebsiteId();
        $newPromoCodes->getSelect()->where("m4m.mailchimp_sync_delta IS NULL AND website.website_id = ".$websiteId);
        // limit the collection
        $newPromoCodes->getSelect()->limit($this->getBatchLimitFromConfig());
        $counter = 0;
        foreach ($newPromoCodes as $promoCode) {
            $codeId = $promoCode->getCouponId();
            $ruleId = $promoCode->getRuleId();
            try {
                //Skip promo codes when rule associated has not been sent yet.
                $promoRuleSyncData = $this->getMailChimpHelper()->getEcommerceSyncDataItem($ruleId, Ebizmarts_MailChimp_Model_Config::IS_PROMO_RULE, $mailchimpStoreId);
                if (!$promoRuleSyncData->getMailchimpSyncDelta() || $promoRuleSyncData->getMailchimpSyncDelta() < Mage::helper('mailchimp')->getEcommMinSyncDateFlag($magentoStoreId)) {
                    $batchArray[$counter] = $this->getApiPromoRules()->getNewPromoRule($ruleId, $mailchimpStoreId, $magentoStoreId);
                    $counter++;
                }

                if ($promoRuleSyncData->getMailchimpSyncError()) {
                    $error = Mage::helper('mailchimp')->__('Parent rule with id ' . $ruleId . 'has not been correctly sent.');
                    $this->_updateSyncData($codeId, $mailchimpStoreId, Varien_Date::now(), $error);
                    continue;
                }

                $promoCodeJson = json_encode($this->generateCodeData($promoCode, $magentoStoreId));
                if (!empty($orderJson)) {
                    $batchArray[$counter]['method'] = "POST";
                    $batchArray[$counter]['path'] = '/ecommerce/stores/' . $mailchimpStoreId . '/promo-codes';
                    $batchArray[$counter]['operation_id'] = $this->_batchId . '_' . $codeId;
                    $batchArray[$counter]['body'] = $promoCodeJson;
                    $counter++;
                } else {
                    $error = Mage::helper('mailchimp')->__('Something went wrong when retrieving the information.');
                    $this->_updateSyncData($codeId, $mailchimpStoreId, Varien_Date::now(), $error);
                    continue;
                }

                //update order delta
                $this->_updateSyncData($codeId, $mailchimpStoreId, Varien_Date::now());
            } catch (Exception $e) {
                Mage::helper('mailchimp')->logError($e->getMessage(), $magentoStoreId);
            }
        }

        return $batchArray;
    }

    /**
     * @return mixed
     */
    protected function getBatchLimitFromConfig()
    {
        $batchLimit = self::BATCH_LIMIT;
        return $batchLimit;
    }

    /**
     * @return Mage_Catalog_Model_Resource_Product_Collection
     */
    protected function getPromoCodeResourceCollection()
    {
        return Mage::getResourceModel('salesrule/coupon_collection');
    }

    /**
     * @return Mage_Catalog_Model_Resource_Product_Collection
     */
    public function makePromoCodesCollection()
    {
        /**
         * @var Mage_Catalog_Model_Resource_Product_Collection $collection
         */
        $collection = $this->getPromoCodeResourceCollection();

        $this->addWebsiteColumn($collection);
        return $collection;
    }

    /**
     * @return string
     */
    public function getSyncdataTableName()
    {
        $mailchimpTableName = Mage::getSingleton('core/resource')->getTableName('mailchimp/ecommercesyncdata');

        return $mailchimpTableName;
    }

    /**
     * @param $collection
     * @param $mailchimpStoreId
     */
    public function joinMailchimpSyncDataWithoutWhere($collection, $mailchimpStoreId)
    {
        $joinCondition = "m4m.related_id = main_table.coupon_id and m4m.type = '%s' AND m4m.mailchimp_store_id = '%s'";
        $mailchimpTableName = $this->getSyncdataTableName();
        $collection->getSelect()->joinLeft(
            array("m4m" => $mailchimpTableName),
            sprintf($joinCondition, Ebizmarts_MailChimp_Model_Config::IS_PRODUCT, $mailchimpStoreId), array(
                "m4m.related_id",
                "m4m.type",
                "m4m.mailchimp_store_id",
                "m4m.mailchimp_sync_delta",
                "m4m.mailchimp_sync_modified"
            )
        );
    }

    /**
     * update product sync data
     *
     * @param $codeId
     * @param $mailchimpStoreId
     * @param null $syncDelta
     * @param null $syncError
     * @param int $syncModified
     * @param null $syncDeleted
     * @param bool $saveOnlyIfexists
     */
    protected function _updateSyncData($codeId, $mailchimpStoreId, $syncDelta = null, $syncError = null, $syncModified = 0, $syncDeleted = null, $saveOnlyIfexists = false)
    {
        $this->getMailChimpHelper()->saveEcommerceSyncData(
            $codeId,
            Ebizmarts_MailChimp_Model_Config::IS_PROMO_CODE,
            $mailchimpStoreId,
            $syncDelta,
            $syncError,
            $syncModified,
            $syncDeleted,
            null,
            $saveOnlyIfexists
        );
    }

    protected function _deleteSyncData($codeId, $mailchimpStoreId)
    {
        $ruleSyncDataItem = $this->getMailChimpHelper()->getEcommerceSyncDataItem($codeId, Ebizmarts_MailChimp_Model_Config::IS_PROMO_CODE, $mailchimpStoreId);
        $ruleSyncDataItem->delete();
    }

    protected function generateCodeData($promoCode, $magentoStoreId)
    {
        $data = array();
        $code = $promoCode->getCode();
        $data['id'] = $promoCode->getCouponId();
        $data['code'] = $code;

        //Set title as description if description null
        $data['redemption_url'] = $this->getRedemptionUrl($code, $magentoStoreId);

        $data['usage_count'] = $promoCode->getTimesUsed();

        return $data;
    }

    /**
     * @return Ebizmarts_MailChimp_Helper_Data
     */
    protected function getMailChimpHelper()
    {
        return $this->mailchimpHelper;
    }

    protected function addWebsiteColumn($collection)
    {
        $websiteTableName = Mage::getSingleton('core/resource')->getTableName('salesrule/website');
        $collection->getSelect()->joinLeft(
            array('website' => $websiteTableName),
            'main_table.rule_id=website.rule_id',
            array('*')
        );
    }

    protected function getRedemptionUrl($code, $magentoStoreId)
    {
        $url = Mage::getModel('core/url')->setStore($magentoStoreId)->getUrl('', array('_nosid' => true, '_secure' => true)) . 'mailchimp/cart/loadcoupon?code=' . $code;
        return $url;
    }

    /**
     * @return Ebizmarts_MailChimp_Model_Api_PromoRules|false|Mage_Core_Model_Abstract
     */
    public function getApiPromoRules()
    {
        return $this->apiPromoRules;
    }

    public function update($ruleId)
    {
        $this->_setModified($ruleId);
    }

    protected function _setModified($codeId)
    {
        $helper = $this->getMailChimpHelper();
        $promoCodes = $helper->getAllEcommerceSyncDataItemsPerId($codeId, Ebizmarts_MailChimp_Model_Config::IS_PROMO_CODE);
        foreach($promoCodes as $promoCode) {
            $mailchimpStoreId = $promoCode->getMailchimpStoreId();
            $helper->saveEcommerceSyncData($codeId, Ebizmarts_MailChimp_Model_Config::IS_PROMO_CODE, $mailchimpStoreId, null, null, 1);
        }
    }

    public function delete($codeId)
    {
        $this->_setDeleted($codeId);
    }

    protected function _setDeleted($codeId)
    {
        $helper = $this->getMailChimpHelper();
        $promoCodes = $helper->getAllEcommerceSyncDataItemsPerId($codeId, Ebizmarts_MailChimp_Model_Config::IS_PROMO_CODE);
        foreach($promoCodes as $promoCode) {
            $mailchimpStoreId = $promoCode->getMailchimpStoreId();
            $helper->saveEcommerceSyncData($codeId, Ebizmarts_MailChimp_Model_Config::IS_PROMO_CODE, $mailchimpStoreId, null, null, null, 1);
        }
    }

}
