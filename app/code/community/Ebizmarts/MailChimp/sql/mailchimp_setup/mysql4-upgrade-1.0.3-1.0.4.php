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
  ALTER TABLE `{$this->getTable('sales_flat_order')}` ADD COLUMN `mailchimp_campaign_id` VARCHAR(16) DEFAULT NULL;
  ALTER TABLE `{$this->getTable('newsletter_subscriber')}` ADD column `mailchimp_sync_delta` datetime NOT NULL;
  ALTER TABLE `{$this->getTable('newsletter_subscriber')}` ADD column `mailchimp_sync_error` VARCHAR(255) NOT NULL;
"
    );
}
catch (Exception $e)
{
    Mage::log($e->getMessage(), null, 'MailChimp_Errors.log', true);
}

$installer->endSetup();