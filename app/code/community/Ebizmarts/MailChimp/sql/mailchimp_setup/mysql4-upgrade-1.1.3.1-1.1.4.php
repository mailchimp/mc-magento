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

$deltaAttributeCode = 'mailchimp_sync_delta';
$errorAttributeCode = 'mailchimp_sync_error';
$modifiedAttributeCode = 'mailchimp_sync_modified';
$productEntityTypeId = $installer->getEntityTypeId('catalog_product');
$customerEntityTypeId = $installer->getEntityTypeId('customer');
$productDeltaAttributeId = $installer->getAttributeId($productEntityTypeId, $deltaAttributeCode);
$productErrorAttributeId = $installer->getAttributeId($productEntityTypeId, $errorAttributeCode);
$productModifiedAttributeId = $installer->getAttributeId($productEntityTypeId, $modifiedAttributeCode);
$customerDeltaAttributeId = $installer->getAttributeId($customerEntityTypeId, $deltaAttributeCode);
$customerErrorAttributeId = $installer->getAttributeId($customerEntityTypeId, $errorAttributeCode);
$customerModifiedAttributeId = $installer->getAttributeId($customerEntityTypeId, $modifiedAttributeCode);
try {
    $installer->updateAttribute($productEntityTypeId, $productDeltaAttributeId, 'is_user_defined', 1);
    $installer->updateAttribute($productEntityTypeId, $productErrorAttributeId, 'is_user_defined', 1);
    $installer->updateAttribute($productEntityTypeId, $productModifiedAttributeId, 'is_user_defined', 1);
    $installer->updateAttribute($productEntityTypeId, $productDeltaAttributeId, 'frontend_input', 'datetime');
    $installer->updateAttribute($customerEntityTypeId, $customerDeltaAttributeId, 'is_user_defined', 1);
    $installer->updateAttribute($customerEntityTypeId, $customerErrorAttributeId, 'is_user_defined', 1);
    $installer->updateAttribute($customerEntityTypeId, $customerModifiedAttributeId, 'is_user_defined', 1);
    $installer->updateAttribute($customerEntityTypeId, $customerDeltaAttributeId, 'frontend_input', 'datetime');
} catch(Exception $e) {
    Mage::log($e->getMessage(), null, 'MailChimp_Errors.log', true);
}

$installer->endSetup();