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
 ALTER TABLE `{$this->getTable('sales_flat_quote')}`
 ADD column `mailchimp_landing_page` VARCHAR (512) NOT NULL DEFAULT '';
 ALTER TABLE `{$this->getTable('sales_flat_quote')}`
 ADD column `mailchimp_campaign_id` VARCHAR (16) NOT NULL DEFAULT '';
 ALTER TABLE `{$this->getTable('sales_flat_order')}`
 ADD column `mailchimp_landing_page` VARCHAR (512) NOT NULL DEFAULT '';
"
    );
}
catch (Exception $e)
{
    Mage::log($e->getMessage(), null, 'MailChimp_Errors.log', true);
}


$installer->endSetup();