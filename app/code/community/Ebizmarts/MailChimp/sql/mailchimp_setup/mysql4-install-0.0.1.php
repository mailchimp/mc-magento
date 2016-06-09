<?php

$installer = $this;

$installer->startSetup();

$installer->getConnection()->addColumn(
    $installer->getTable('newsletter_subscriber'), 'subscriber_firstname', 'varchar(50)'
);

$installer->getConnection()->addColumn(
    $installer->getTable('newsletter_subscriber'), 'subscriber_lastname', 'varchar(50)'
);
$eav = new Mage_Eav_Model_Entity_Setup('core_setup');

$eav->addAttribute('customer', 'mailchimp_sync_delta', array(
    'label'     => 'MailChimp last sync timestamp',
    'type'      => 'datetime',
    'input'     => 'text',
    'visible'   => true,
    'required'  => false,
    'position'  => 1,
));
$eav->addAttribute('customer', 'mailchimp_sync_error', array(
    'label'     => 'MailChimp Error Description',
    'type'      => 'varchar',
    'input'     => 'text',
    'visible'   => true,
    'required'  => false,
    'position'  => 1,
));


$attribute   = Mage::getSingleton("eav/config")->getAttribute("customer", "mailchimp_sync_delta");
$used_in_forms=array();

$used_in_forms[]="adminhtml_customer";
$attribute->setData("used_in_forms", $used_in_forms)
    ->setData("is_used_for_customer_segment", true)
    ->setData("is_system", 0)
    ->setData("is_user_defined", 1)
    ->setData("is_visible", 1)
    ->setData("sort_order", 100)
;
$attribute->save();

$attribute   = Mage::getSingleton("eav/config")->getAttribute("customer", "mailchimp_sync_error");
$used_in_forms=array();

$used_in_forms[]="adminhtml_customer";
$attribute->setData("used_in_forms", $used_in_forms)
    ->setData("is_used_for_customer_segment", true)
    ->setData("is_system", 0)
    ->setData("is_user_defined", 1)
    ->setData("is_visible", 1)
    ->setData("sort_order", 100)
;
$attribute->save();



$eav->addAttribute('catalog_product', 'mailchimp_sync_delta', array(
    'label'     => 'MailChimp last sync timestamp',
    'type'      => 'datetime',
    'input'     => 'text',
    'visible'   => true,
    'required'  => false,
    'position'  => 1,
));

$eav->addAttribute('catalog_product', 'mailchimp_sync_error', array(
    'label'     => 'MailChimp Error Description',
    'type'      => 'varchar',
    'input'     => 'text',
    'visible'   => true,
    'required'  => false,
    'position'  => 1,
));

try {
    $installer->run("
 ALTER TABLE `{$this->getTable('sales_flat_quote')}` ADD column `mailchimp_sync_delta` datetime NOT NULL;
 ALTER TABLE `{$this->getTable('sales_flat_quote')}` ADD column `mailchimp_sync_error` VARCHAR(255) NOT NULL;
 ALTER TABLE `{$this->getTable('sales_flat_order')}` ADD column `mailchimp_sync_delta` datetime NOT NULL;
 ALTER TABLE `{$this->getTable('sales_flat_order')}` ADD column `mailchimp_sync_error` VARCHAR(255) NOT NULL;
");
}
catch (Exception $e)
{
    Mage::helper('mailchimp')->logError($e->getMessage());
}

$installer->run("
	CREATE TABLE IF NOT EXISTS `{$this->getTable('mailchimp_sync_batches')}` (
	  `id` int(10) unsigned NOT NULL auto_increment,
	  `store_id` int(11) NOT NULL,
	  `batch_id` varchar(24) NOT NULL,
	  `status` varchar(10) NOT NULL,
	  PRIMARY KEY  (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

$baseDir = Mage::getBaseDir();

try {
    mkdir($baseDir . DS . 'var' . DS . 'mailchimp');
}
catch (Exception $e){
    Mage::helper('mailchimp')->logError($e->getMessage());
}

$installer->endSetup();

