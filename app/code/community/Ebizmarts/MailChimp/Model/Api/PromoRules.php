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
    protected $mailchimpHelper;

    public function __construct()
    {
        $this->mailchimpHelper = Mage::helper('mailchimp');
    }

    public function createBatchJson($mailchimpStoreId, $magentoStoreId)
    {
        $batchArray = array();
        $this->_batchId = 'storeid-' . $magentoStoreId . '_' . Ebizmarts_MailChimp_Model_Config::IS_PROMO_RULE . '_' . Mage::helper('mailchimp')->getDateMicrotime();
        $batchArray = array_merge($batchArray, $this->_getDeletedPromoRules($mailchimpStoreId, $magentoStoreId));
        $batchArray = array_merge($batchArray, $this->_getModifiedPromoRules($mailchimpStoreId, $magentoStoreId));

        return $batchArray;
    }

    protected function _getDeletedPromoRules($mailchimpStoreId, $magentoStoreId)
    {
        $batchArray = array();
        $deletedPromoRules = $this->makePromoRulesCollection($magentoStoreId);
        $this->joinMailchimpSyncDataWithoutWhere($deletedPromoRules, $mailchimpStoreId);

        // filter promo rules that were modified
        $deletedPromoRules->getSelect()->where("m4m.mailchimp_sync_deleted = 1");
        $deletedPromoRules->getSelect()->limit($this->getBatchLimitFromConfig());

        $counter = 0;
        foreach ($deletedPromoRules as $promoRule) {
            $ruleId = $promoRule->getRuleId();
            $batchArray[$counter]['method'] = "DELETE";
            $batchArray[$counter]['path'] = '/ecommerce/stores/' . $mailchimpStoreId . '/promo-rules/' . $ruleId;
            $batchArray[$counter]['operation_id'] = $this->_batchId . '_' . $ruleId;
            $batchArray[$counter]['body'] = '';
            $this->_deleteSyncData($ruleId, $mailchimpStoreId);
            $counter++;
        }

        return $batchArray;
    }

    protected function _getModifiedPromoRules($mailchimpStoreId, $magentoStoreId)
    {
        $batchArray = array();
        $modifiedPromoRules = $this->makePromoRulesCollection($magentoStoreId);
        $this->joinMailchimpSyncDataWithoutWhere($modifiedPromoRules, $mailchimpStoreId);

        // filter promo rules that were modified
        $modifiedPromoRules->getSelect()->where("m4m.mailchimp_sync_modified = 1");
        // limit the collection
        $modifiedPromoRules->getSelect()->limit($this->getBatchLimitFromConfig());
        $counter = 0;
        foreach ($modifiedPromoRules as $promoRule) {
            try {
                $ruleId = $promoRule->getRuleId();
                $promoRuleJson = json_encode($this->generateRuleData($promoRule));
                if (!empty($promoRuleJson)) {
                    $batchArray[$counter]['method'] = "PATCH";
                    $batchArray[$counter]['path'] = '/ecommerce/stores/' . $mailchimpStoreId . '/promo-rules/' . $ruleId;
                    $batchArray[$counter]['operation_id'] = $this->_batchId . '_' . $ruleId;
                    $batchArray[$counter]['body'] = $promoRuleJson;
                } else {
                    $error = Mage::helper('mailchimp')->__('Something went wrong when retrieving the information.');
                    $this->_updateSyncData($ruleId, $mailchimpStoreId, Varien_Date::now(), $error);
                    continue;
                }

                //update promo rule delta
                $this->_updateSyncData($ruleId, $mailchimpStoreId, Varien_Date::now());
                $counter++;
            } catch (Exception $e) {
                Mage::helper('mailchimp')->logError($e->getMessage(), $magentoStoreId);
            }
        }

        return $batchArray;
    }

    public function getNewPromoRule($ruleId, $mailchimpStoreId, $magentoStoreId)
    {
        $promoData = array();
        $promoRule = $this->getPromoRule($ruleId, $magentoStoreId, $mailchimpStoreId);
        try {
            $promoRuleJson = json_encode($this->generateRuleData($promoRule));
            if (!empty($promoRuleJson)) {
                $promoData['method'] = "POST";
                $promoData['path'] = '/ecommerce/stores/' . $mailchimpStoreId . '/promo-rules';
                $promoData['operation_id'] = $this->_batchId . '_' . $ruleId;
                $promoData['body'] = $promoRuleJson;
            } else {
                $error = Mage::helper('mailchimp')->__('Something went wrong when retrieving the information.');
                $this->_updateSyncData($ruleId, $mailchimpStoreId, Varien_Date::now(), $error);
            }

            //update promo rule delta
            $this->_updateSyncData($ruleId, $mailchimpStoreId, Varien_Date::now());
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

    protected function getPromoRule($ruleId, $magentoStoreId, $mailchimpStoreId)
    {
        $collection = $this->makePromoRulesCollection($magentoStoreId);
        $collection->addFieldToFilter('rule_id', array('eq' => $ruleId));
        $this->joinMailchimpSyncDataWithoutWhere($collection, $mailchimpStoreId);
        return $collection->getFirstItem();
    }

    /**
     * @return Mage_SalesRule_Model_Mysql4_Rule_Collection
     */
    protected function getPromoRuleResourceCollection()
    {
        return Mage::getResourceModel('salesrule/rule_collection');
    }

    /**
     * @param $magentoStoreId
     * @return Mage_SalesRule_Model_Mysql4_Rule_Collection
     */
    public function makePromoRulesCollection($magentoStoreId)
    {
        /**
         * @var Mage_SalesRule_Model_Mysql4_Rule_Collection $collection
         */
        $collection = $this->getPromoRuleResourceCollection();
        $websiteId = Mage::getModel('core/store')->load($magentoStoreId)->getWebsiteId();
        $collection->addWebsiteFilter($websiteId);
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
        $joinCondition = "m4m.related_id = main_table.rule_id and m4m.type = '%s' AND m4m.mailchimp_store_id = '%s'";
        $mailchimpTableName = $this->getSyncdataTableName();
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

    protected function _deleteSyncData($ruleId, $mailchimpStoreId)
    {
        $ruleSyncDataItem = $this->getMailChimpHelper()->getEcommerceSyncDataItem($ruleId, Ebizmarts_MailChimp_Model_Config::IS_PROMO_RULE, $mailchimpStoreId);
        $ruleSyncDataItem->delete();
    }

    protected function generateRuleData($promoRule)
    {
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
            $data['starts_at'] = $toDate;
        }

        $data['amount'] = $promoRule->getDiscountAmount();
        $promoAction = $promoRule->getSimpleAction();
        $data['type'] = $this->getMailChimpType($promoAction);
        $data['target'] = $this->getMailChimpTarget($promoAction);

        $data['enabled'] = (bool)$promoRule->getIsActive();

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

    public function delete($ruleId)
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

}
