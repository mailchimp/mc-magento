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
class Ebizmarts_MailChimp_Model_Api_PromoRules
{
    const BATCH_LIMIT = 50;
    const TYPE_FIXED = 'fixed';
    const TYPE_PERCENTAGE = 'percentage';
    const TARGET_PER_ITEM = 'per_item';
    const TARGET_TOTAL = 'total';
    const TARGET_SHIPPING = 'shipping';

    protected $_batchId;
    /**
     * @var Ebizmarts_MailChimp_Helper_Data
     */
    protected $mailchimpHelper;
    /**
     * @var Ebizmarts_MailChimp_Model_Api_PromoCodes
     */
    protected $promoCodes;

    public function __construct()
    {
        $this->mailchimpHelper = Mage::helper('mailchimp');
        $this->promoCodes = Mage::getModel('mailchimp/api_promoCodes');
    }

    public function createBatchJson($mailchimpStoreId, $magentoStoreId)
    {
        $batchArray = array();
        $this->_batchId = 'storeid-' . $magentoStoreId . '_' . Ebizmarts_MailChimp_Model_Config::IS_PROMO_RULE . '_' . $this->getMailChimpHelper()->getDateMicrotime();
        $batchArray = array_merge($batchArray, $this->_getModifiedAndDeletedPromoRules($mailchimpStoreId));

        return $batchArray;
    }

    protected function _getModifiedAndDeletedPromoRules($mailchimpStoreId)
    {
        $batchArray = array();
        $deletedPromoRules = $this->makeModifiedAndDeletedPromoRulesCollection($mailchimpStoreId);

        $counter = 0;
        foreach ($deletedPromoRules as $promoRule) {
            $ruleId = $promoRule->getRelatedId();
            $batchArray[$counter]['method'] = "DELETE";
            $batchArray[$counter]['path'] = '/ecommerce/stores/' . $mailchimpStoreId . '/promo-rules/' . $ruleId;
            $batchArray[$counter]['operation_id'] = $this->_batchId . '_' . $ruleId;
            $batchArray[$counter]['body'] = '';
            $this->deletePromoRuleSyncData($ruleId, $mailchimpStoreId);
            $this->getPromoCodes()->deletePromoCodesSyncDataByRule($promoRule);
            $counter++;
        }

        return $batchArray;
    }

    public function getNewPromoRule($ruleId, $batchId, $mailchimpStoreId, $magentoStoreId)
    {
        $promoData = array();
        $promoRule = $this->getPromoRule($ruleId);
        try {
            $ruleData = $this->generateRuleData($promoRule);
            $promoRuleJson = json_encode($ruleData);
            if (!empty($ruleData)) {
                $promoData['method'] = "POST";
                $promoData['path'] = '/ecommerce/stores/' . $mailchimpStoreId . '/promo-rules';
                $promoData['operation_id'] = $batchId . '_' . Ebizmarts_MailChimp_Model_Config::IS_PROMO_RULE . '_' . $ruleId;
                $promoData['body'] = $promoRuleJson;
                //update promo rule delta
                $this->_updateSyncData($ruleId, $mailchimpStoreId, Varien_Date::now());
            } else {
                $error = $promoRule->getMailchimpSyncError();
                if (!$error) {
                    $error = 'Something went wrong when retrieving the information.';
                }
                $helper = $this->getMailChimpHelper();
                $this->_updateSyncData($ruleId, $mailchimpStoreId, Varien_Date::now(), $helper->__($error));
            }
        } catch (Exception $e) {
            Mage::helper('mailchimp')->logError($e->getMessage(), $magentoStoreId);
        }

        return $promoData;
    }

    /**
     * @return mixed
     */
    protected function getBatchLimitFromConfig()
    {
        $batchLimit = self::BATCH_LIMIT;
        return $batchLimit;
    }

    protected function getPromoRule($ruleId)
    {
        return Mage::getModel('salesrule/rule')->load($ruleId);
    }

    /**
     * @return Mage_SalesRule_Model_Resource_Rule_Collection
     */
    protected function getPromoRuleResourceCollection()
    {
        return Mage::getResourceModel('salesrule/rule_collection');
    }

    /**
     * @param $magentoStoreId
     * @return Mage_SalesRule_Model_Resource_Rule_Collection
     */
    public function makePromoRulesCollection($magentoStoreId)
    {
        /**
         * @var Mage_SalesRule_Model_Resource_Rule_Collection $collection
         */
        $collection = $this->getPromoRuleResourceCollection();
        $websiteId = $this->getWebsiteIdByStoreId($magentoStoreId);
        $collection->addWebsiteFilter($websiteId);
        return $collection;
    }

    /**
     * @param $mailchimpStoreId
     * @return Ebizmarts_MailChimp_Model_Mysql4_Ecommercesyncdata_Collection
     */
    protected function makeModifiedAndDeletedPromoRulesCollection($mailchimpStoreId)
    {
        $deletedPromoRules = Mage::getResourceModel('mailchimp/ecommercesyncdata_collection');
        $deletedPromoRules->getSelect()->where("mailchimp_store_id = '" . $mailchimpStoreId . "' AND type = '" . Ebizmarts_MailChimp_Model_Config::IS_PROMO_RULE . "' AND (mailchimp_sync_modified = 1 OR mailchimp_sync_deleted = 1)");
        $deletedPromoRules->getSelect()->limit($this->getBatchLimitFromConfig());
        return $deletedPromoRules;
    }

    /**
     * @return string
     */
    public function getSyncDataTableName()
    {
        $mailchimpTableName = $this->getCoreResource()->getTableName('mailchimp/ecommercesyncdata');

        return $mailchimpTableName;
    }

    /**
     * @param $collection
     * @param $mailchimpStoreId
     */
    public function joinMailchimpSyncDataWithoutWhere($collection, $mailchimpStoreId)
    {
        $joinCondition = "m4m.related_id = main_table.rule_id and m4m.type = '%s' AND m4m.mailchimp_store_id = '%s'";
        $mailchimpTableName = $this->getSyncDataTableName();
        $collection->getSelect()->joinLeft(
            array("m4m" => $mailchimpTableName),
            sprintf($joinCondition, Ebizmarts_MailChimp_Model_Config::IS_PROMO_RULE, $mailchimpStoreId), array(
                "m4m.related_id",
                "m4m.type",
                "m4m.mailchimp_store_id",
                "m4m.mailchimp_sync_delta",
                "m4m.mailchimp_sync_modified"
            )
        );
    }

    /**
     * update promo rule sync data
     *
     * @param $ruleId
     * @param $mailchimpStoreId
     * @param null $syncDelta
     * @param null $syncError
     * @param int $syncModified
     * @param null $syncDeleted
     * @param bool $saveOnlyIfexists
     */
    protected function _updateSyncData($ruleId, $mailchimpStoreId, $syncDelta = null, $syncError = null, $syncModified = 0, $syncDeleted = null, $saveOnlyIfexists = false)
    {
        $this->getMailChimpHelper()->saveEcommerceSyncData(
            $ruleId,
            Ebizmarts_MailChimp_Model_Config::IS_PROMO_RULE,
            $mailchimpStoreId,
            $syncDelta,
            $syncError,
            $syncModified,
            $syncDeleted,
            null,
            $saveOnlyIfexists
        );
    }

    protected function deletePromoRuleSyncData($ruleId, $mailchimpStoreId)
    {
        $ruleSyncDataItem = $this->getMailChimpHelper()->getEcommerceSyncDataItem($ruleId, Ebizmarts_MailChimp_Model_Config::IS_PROMO_RULE, $mailchimpStoreId);
        $ruleSyncDataItem->delete();
    }

    protected function generateRuleData($promoRule)
    {
        $error = null;
        $data = array();
        $data['id'] = $promoRule->getRuleId();
        $data['title'] = $promoRule->getName();

        //Set title as description if description null
        $data['description'] = ($promoRule->getDescription()) ? $promoRule->getDescription() : $promoRule->getName();

        $fromDate = $promoRule->getFromDate();
        if ($fromDate) {
            $data['starts_at'] = $fromDate;
        }

        $toDate = $promoRule->getToDate();
        if ($toDate) {
            $data['ends_at'] = $toDate;
        }

        $data['amount'] = $this->getMailChimpDiscountAmount($promoRule);
        $promoAction = $promoRule->getSimpleAction();
        $data['type'] = $this->getMailChimpType($promoAction);
        $data['target'] = $this->getMailChimpTarget($promoAction);

        $data['enabled'] = (bool)$promoRule->getIsActive();

        if (!$data['target'] || !$data['type']) {
            $error = 'The rule type is not supported by the MailChimp schema.';
        }

        if (!$error && (!$data['amount'] || !$data['description'] || !$data['id'])) {
            $error = 'There is required information by the MailChimp schema missing.';
        }

        if ($error) {
            $data = array();
            $promoRule->setMailchimpSyncError($error);
        }

        return $data;
    }

    /**
     * @return Ebizmarts_MailChimp_Helper_Data
     */
    protected function getMailChimpHelper()
    {
        return $this->mailchimpHelper;
    }

    protected function getMailChimpType($promoAction)
    {
        $mailChimpType = null;
        switch ($promoAction) {
            case Mage_SalesRule_Model_Rule::BY_PERCENT_ACTION:
                $mailChimpType = self::TYPE_PERCENTAGE;
                break;
            case Mage_SalesRule_Model_Rule::BY_FIXED_ACTION:
            case Mage_SalesRule_Model_Rule::CART_FIXED_ACTION:
                $mailChimpType = self::TYPE_FIXED;
                break;
        }
        return $mailChimpType;
    }

    protected function getMailChimpTarget($promoAction)
    {
        $mailChimpTarget = null;
        switch ($promoAction) {
            case Mage_SalesRule_Model_Rule::CART_FIXED_ACTION:
            case Mage_SalesRule_Model_Rule::BY_PERCENT_ACTION:
                $mailChimpTarget = self::TARGET_TOTAL;
                break;
            case Mage_SalesRule_Model_Rule::BY_FIXED_ACTION:
                $mailChimpTarget = self::TARGET_PER_ITEM;
                break;
        }
        return $mailChimpTarget;
    }

    public function update($ruleId)
    {
        $this->_setModified($ruleId);
    }

    protected function _setModified($ruleId)
    {
        $helper = $this->getMailChimpHelper();
        $promoRules = $helper->getAllEcommerceSyncDataItemsPerId($ruleId, Ebizmarts_MailChimp_Model_Config::IS_PROMO_RULE);
        foreach ($promoRules as $promoRule) {
            $mailchimpStoreId = $promoRule->getMailchimpStoreId();
            $helper->saveEcommerceSyncData($ruleId, Ebizmarts_MailChimp_Model_Config::IS_PROMO_RULE, $mailchimpStoreId, null, null, 1);
        }
    }

    public function markAsDeleted($ruleId)
    {
        $this->_setDeleted($ruleId);
    }

    protected function _setDeleted($ruleId)
    {
        $helper = $this->getMailChimpHelper();
        $promoRules = $helper->getAllEcommerceSyncDataItemsPerId($ruleId, Ebizmarts_MailChimp_Model_Config::IS_PROMO_RULE);
        foreach ($promoRules as $promoRule) {
            $mailchimpStoreId = $promoRule->getMailchimpStoreId();
            $helper->saveEcommerceSyncData($ruleId, Ebizmarts_MailChimp_Model_Config::IS_PROMO_RULE, $mailchimpStoreId, null, null, null, 1);
        }
    }

    protected function getMailChimpDiscountAmount($promoRule)
    {
        $action = $promoRule->getSimpleAction();
        if ($action == Mage_SalesRule_Model_Rule::BY_PERCENT_ACTION) {
            $mailChimpDiscount = ($promoRule->getDiscountAmount() / 100);
        } else {
            $mailChimpDiscount = $promoRule->getDiscountAmount();
        }
        return $mailChimpDiscount;
    }

    /**
     * @return Ebizmarts_MailChimp_Model_Api_PromoCodes
     */
    protected function getPromoCodes()
    {
        return $this->promoCodes;
    }

    /**
     * @param $magentoStoreId
     * @return mixed
     */
    protected function getWebsiteIdByStoreId($magentoStoreId)
    {
        return Mage::getModel('core/store')->load($magentoStoreId)->getWebsiteId();
    }

    /**
     * @return Mage_Core_Model_Abstract
     */
    protected function getCoreResource()
    {
        return Mage::getSingleton('core/resource');
    }
}
