<?php

$installer = $this;

$installer->startSetup();

$eav = new Mage_Eav_Model_Entity_Setup('core_setup');

$eav->addAttribute('customer', 'mailchimp_sync_delta', array(
    'label'     => 'MailChimp last sync timestamp',
    'type'      => 'datetime',
    'input'     => 'text',
    'visible'   => true,
    'required'  => true,
    'position'  => 1,
));

$installer->run("
	CREATE TABLE IF NOT EXISTS `{$this->getTable('mailchimp_sync_batches')}` (
	  `id` int(10) unsigned NOT NULL auto_increment,
	  `status` varchar(10) NOT NULL,
	  `response_url` text,
	  PRIMARY KEY  (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

$installer->endSetup();

