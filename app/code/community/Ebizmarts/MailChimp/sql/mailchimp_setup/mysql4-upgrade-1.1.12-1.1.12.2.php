<?php

$installer = $this;

/** @var Mage_Eav_Model_Entity_Setup $installer */
$installer->startSetup();

try {
    $salesOrderTableName = $installer->getTable('sales/order');
    $indexFields = array('customer_id');

    $installer->getConnection()->addIndex(
        $salesOrderTableName,
        $installer->getIdxName($salesOrderTableName, $indexFields),
        $indexFields,
        Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX
    );
} catch (Exception $e) {
    Mage::log($e->getMessage(), null, 'MailChimp_Errors.log', true);
}
$setup = new Mage_Eav_Model_Entity_Setup('core_setup');

$entityTypeId     = $setup->getEntityTypeId('customer');
$attributeSetId   = $setup->getDefaultAttributeSetId($entityTypeId);
$attributeGroupId = $setup->getDefaultAttributeGroupId($entityTypeId, $attributeSetId);

$setup->addAttribute("customer", "mailchimp_store_view",  array(
    "type"     => "int",
    "label"    => "Store View (For MailChimp)",
    "input"    => "select",
    "source"   => "mailchimp/system_config_source_mailchimpStoreView",
    "visible"  => true,
    "required" => false,
    "unique"     => false,
    "note"       => "A store view must be specified to sync this customer to MailChimp"

));

try {
    $attribute = Mage::getSingleton("eav/config")->getAttribute("customer", "mailchimp_store_view");


    $setup->addAttributeToGroup(
        $entityTypeId,
        $attributeSetId,
        $attributeGroupId,
        'mailchimp_store_view',
        '999'  //sort_order
    );

    $used_in_forms = array();

    $used_in_forms[] = "adminhtml_customer";

    $attribute->setData("used_in_forms", $used_in_forms)
        ->setData("is_used_for_customer_segment", true)
        ->setData("is_system", 0)
        ->setData("is_user_defined", 1)
        ->setData("is_visible", 1)
        ->setData("sort_order", 100);
    $attribute->save();
} catch (Exception $e) {
    Mage::log($e->getMessage(), null, 'MailChimp_Errors.log', true);
}

$installer->deleteConfigData(Ebizmarts_MailChimp_Model_Config::ENABLE_POPUP);

$installer->endSetup();
