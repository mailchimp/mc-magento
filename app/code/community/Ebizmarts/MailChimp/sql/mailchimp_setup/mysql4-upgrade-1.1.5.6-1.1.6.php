<?php
/**
 * mc-magento Magento Component
 *
 * @category  Ebizmarts
 * @package   mc-magento
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     6/9/16 4:05 PM
 * @file:     mysql4-upgrade-1.0.1-1.0.2.php
 */

$installer = $this;

$installer->startSetup();

try {
    $installer->run(
        "
 ALTER TABLE `{$this->getTable('mailchimp_errors')}`
 ADD column `mailchimp_store_id` VARCHAR(50) NOT NULL DEFAULT '';
 ALTER TABLE `{$this->getTable('newsletter_subscriber')}` 
 ADD column `mailchimp_sync_modified` INT(1) NOT NULL DEFAULT 0;
 "
    );
} catch (Exception $e) {
    Mage::log($e->getMessage(), null, 'MailChimp_Errors.log', true);
}

$installer->endSetup();