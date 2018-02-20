<?php

$installer = $this;

$installer->run(
    "
	CREATE TABLE IF NOT EXISTS `{$this->getTable('mailchimp_interest_group')}` (
	  `id` INT(10) unsigned NOT NULL auto_increment,
	  `subscriber_id` INT(10) DEFAULT 0,
	  `store_id` SMALLINT (5) NOT NULL,
	  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
	  `groupdata` TEXT(4096) NOT NULL,
	  PRIMARY KEY  (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;
"
);

$installer->endSetup();