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

try {
//migrate data from older version to the new schemma
    $mailchimpSyncDataCollection = Mage::getModel('mailchimp/ecommercesyncdata')->getCollection();
    if (!count($mailchimpSyncDataCollection)) {
        $mailchimpStoreIdCollection = Mage::getModel('core/config_data')->getCollection()
            ->addFieldToFilter('path', array('eq' => Ebizmarts_MailChimp_Model_Config::GENERAL_MCSTOREID))
            ->addFieldToFilter('scope_id', array('eq' => 0));
        if (count($mailchimpStoreIdCollection)) {
            $mailchimpStoreId = $mailchimpStoreIdCollection->getFirstItem()->getValue();
            //migrate customers
            $customerCollection = Mage::getModel('customer/customer')->getCollection();
            foreach ($customerCollection as $customer) {
                $syncDelta = null;
                $syncError = null;
                $syncModified = null;
                if ($customer->getMailchimpSyncDelta() && $customer->getMailchimpSyncDelta() > '0000-00-00 00:00:00') {
                    $syncDelta = $customer->getMailchimpSyncDelta();
                    if ($customer->getMailchimpSyncError()) {
                        $syncError = $customer->getMailchimpSyncError();
                    }

                    if ($customer->getMailchimpSyncModified()) {
                        $syncModified = $customer->getMailchimpSyncModified();
                    }

                    Mage::helper('mailchimp')->saveEcommerceSyncData($customer->getEntityId(), Ebizmarts_MailChimp_Model_Config::IS_CUSTOMER, $mailchimpStoreId, $syncDelta, $syncError, $syncModified);
                }
            }

            //migrate products
            $productCollection = Mage::getModel('catalog/product')->getCollection();
            foreach ($productCollection as $product) {
                $syncDelta = null;
                $syncError = null;
                $syncModified = null;
                if ($product->getMailchimpSyncDelta() && $product->getMailchimpSyncDelta() > '0000-00-00 00:00:00') {
                    $syncDelta = $product->getMailchimpSyncDelta();
                    if ($product->getMailchimpSyncError()) {
                        $syncError = $product->getMailchimpSyncError();
                    }

                    if ($product->getMailchimpSyncModified()) {
                        $syncModified = $product->getMailchimpSyncModified();
                    }

                    Mage::helper('mailchimp')->saveEcommerceSyncData($product->getEntityId(), Ebizmarts_MailChimp_Model_Config::IS_PRODUCT, $mailchimpStoreId, $syncDelta, $syncError, $syncModified);
                }
            }

            //migrate orders
            $orderCollection = Mage::getModel('sales/order')->getCollection();
            foreach ($orderCollection as $order) {
                $syncDelta = null;
                $syncError = null;
                $syncModified = null;
                if ($order->getMailchimpSyncDelta() && $order->getMailchimpSyncDelta() > '0000-00-00 00:00:00') {
                    $syncDelta = $order->getMailchimpSyncDelta();
                    if ($order->getMailchimpSyncError()) {
                        $syncError = $order->getMailchimpSyncError();
                    }

                    if ($order->getMailchimpSyncModified()) {
                        $syncModified = $order->getMailchimpSyncModified();
                    }

                    Mage::helper('mailchimp')->saveEcommerceSyncData($order->getEntityId(), Ebizmarts_MailChimp_Model_Config::IS_ORDER, $mailchimpStoreId, $syncDelta, $syncError, $syncModified);
                }
            }

            //migrate carts
            $quoteCollection = Mage::getModel('sales/quote')->getCollection();
            foreach ($quoteCollection as $quote) {
                $syncDelta = null;
                $syncError = null;
                $syncDeleted = null;
                $token = null;
                if ($quote->getMailchimpSyncDelta() && $quote->getMailchimpSyncDelta() > '0000-00-00 00:00:00') {
                    $syncDelta = $quote->getMailchimpSyncDelta();
                    if ($quote->getMailchimpSyncError()) {
                        $syncError = $quote->getMailchimpSyncError();
                    }

                    if ($quote->getMailchimpSyncDeleted()) {
                        $syncDeleted = $quote->getMailchimpSyncDeleted();
                    }

                    if ($quote->getMailchimpToken()) {
                        $token = $quote->getMailchimpToken();
                    }

                    Mage::helper('mailchimp')->saveEcommerceSyncData($quote->getEntityId(), Ebizmarts_MailChimp_Model_Config::IS_QUOTE, $mailchimpStoreId, $syncDelta, $syncError, null, $syncDeleted, $token);
                }
            }
        }
    }
} catch (Exception $e) {
    Mage::log($e->getMessage(), null, 'MailChimp_Errors.log', true);
}

try {
//Remove attributes no longer used
    $installer->removeAttribute('catalog_product', 'mailchimp_sync_delta');
    $installer->removeAttribute('catalog_product', 'mailchimp_sync_error');
    $installer->removeAttribute('catalog_product', 'mailchimp_sync_modified');
    $installer->removeAttribute('customer', 'mailchimp_sync_delta');
    $installer->removeAttribute('customer', 'mailchimp_sync_error');
    $installer->removeAttribute('customer', 'mailchimp_sync_modified');

    $quoteTable = $this->getTable('sales/quote');
    $installer->getConnection()->dropColumn($quoteTable, 'mailchimp_sync_delta');
    $installer->getConnection()->dropColumn($quoteTable, 'mailchimp_sync_error');
    $installer->getConnection()->dropColumn($quoteTable, 'mailchimp_deleted');
    $installer->getConnection()->dropColumn($quoteTable, 'mailchimp_token');

    $orderTable = $this->getTable('sales/order');
    $installer->getConnection()->dropColumn($orderTable, 'mailchimp_sync_delta');
    $installer->getConnection()->dropColumn($orderTable, 'mailchimp_sync_error');
    $installer->getConnection()->dropColumn($orderTable, 'mailchimp_sync_modified');
} catch (Exception $e) {
    Mage::log($e->getMessage(), null, 'MailChimp_Errors.log', true);
}

$installer->endSetup();