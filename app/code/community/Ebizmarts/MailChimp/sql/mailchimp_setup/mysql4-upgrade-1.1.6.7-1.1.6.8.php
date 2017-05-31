<?php

$installer = $this;

$installer->run(
    "
	CREATE TABLE IF NOT EXISTS `{$this->getTable('mailchimp_webhook_request')}` (
	  `id`     INT(10) unsigned NOT NULL auto_increment,
	  `type` VARCHAR(20) NOT NULL,
	  `fired_at` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
	  `data_request` VARCHAR(4096),
	  `processed` INT(1) NOT NULL DEFAULT 0,
	  PRIMARY KEY  (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;
"
);

$installer->endSetup();