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

$installer->run(
    "
	CREATE TABLE IF NOT EXISTS `{$this->getTable('mailchimp_ecommerce_sync_data')}` (
	  `id`     INT(10) unsigned NOT NULL auto_increment,
	  `related_id` INT(10) DEFAULT 0,
	  `type` VARCHAR(3) NOT NULL,
	  `mailchimp_store_id`  VARCHAR(50) NOT NULL DEFAULT '',
	  `mailchimp_sync_error` VARCHAR(255) NOT NULL DEFAULT '',
	  `mailchimp_sync_delta` DATETIME NOT NULL,
	  `mailchimp_sync_modified` INT(1) NOT NULL DEFAULT 0,
	  `mailchimp_sync_deleted` INT(1) NOT NULL DEFAULT 0,
	  `mailchimp_token` VARCHAR(32) NOT NULL DEFAULT '',
	  PRIMARY KEY  (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;
"
);

try {
    $installer->run(
        "
 ALTER TABLE `{$this->getTable('mailchimp_errors')}`
 ADD column `store_id` INT(5) DEFAULT 0;
 "
    );
}
catch (Exception $e)
{
    Mage::log($e->getMessage(), null, 'MailChimp_Errors.log', true);
}

Mage::helper('mailchimp')->saveMailChimpConfig(array(array(Ebizmarts_MailChimp_Model_Config::GENERAL_MIGRATE_FROM_115, 1)), 0, 'default');


$installer->endSetup();