<?php

$installer = $this;

$installer->run(
    "
	CREATE TABLE IF NOT EXISTS `{$this->getTable('mailchimp_interest_group')}` (
	  `id` INT(10) unsigned NOT NULL auto_increment,
	  `customer_id` INT(10) DEFAULT NULL,
	  `subscriber_id` INT(10) DEFAULT NULL,
	  `store_id` SMALLINT (5) NOT NULL DEFAULT 0,
	  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
	  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
	  `groupdata` TEXT(4096) NOT NULL,
	  PRIMARY KEY  (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;
"
);

$installer->endSetup();
