<?php

/**
 * mc-magento Magento Component
 *
 * @category  Ebizmarts
 * @package   mc-magento
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     27/12/17 6:23 PM
 * @file:     mysql4-upgrade-1.1.21-1.1.22.php
 */

$installer = $this;

$installer->startSetup();

try {
    $installer->run(
        "
 ALTER TABLE `{$this->getTable('mailchimp_sync_batches')}`
 ADD column `modified_date` DATETIME DEFAULT NULL;
 "
    );
} catch (Exception $e) {
    Mage::log($e->getMessage(), null, 'MailChimp_Errors.log', true);
}

