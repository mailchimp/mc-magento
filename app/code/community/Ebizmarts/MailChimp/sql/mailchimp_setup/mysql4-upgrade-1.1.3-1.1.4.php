<?php
/**
 * mc-magento Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 6/9/16 4:05 PM
 * @file: mysql4-upgrade-1.0.1-1.0.2.php
 */

$installer = $this;

$installer->startSetup();

$attributeCode = 'mailchimp_sync_delta';
$productEntityTypeId  = $installer->getEntityTypeId('catalog_product');
$customerEntityTypeId = $installer->getEntityTypeId('customer');
$productAttributeId   = $installer->getAttributeId($productEntityTypeId, $attributeCode);
$customerAttributeId  = $installer->getAttributeId($customerEntityTypeId, $attributeCode);

try {
    $installer->updateAttribute($productEntityTypeId, $productAttributeId, 'frontend_input', 'datetime');
} catch(Exception $e) {
    Mage::log($e->getMessage());
}
try {
    $installer->updateAttribute($customerEntityTypeId, $customerAttributeId, 'frontend_input', 'datetime');
} catch(Exception $e) {
    Mage::log($e->getMessage());
}

$installer->endSetup();