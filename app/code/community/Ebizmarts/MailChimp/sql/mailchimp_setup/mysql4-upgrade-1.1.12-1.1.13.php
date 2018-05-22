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

$installer->endSetup();
