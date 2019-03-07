<?php

$installer = $this;

$installer->run(
    "
	CREATE TABLE IF NOT EXISTS `{$this->getTable('mailchimp_stores')}` (
	  `id`     INT(10) unsigned NOT NULL auto_increment,
	  `apikey` VARCHAR(50) NOT NULL,
	  `storeid` VARCHAR(50) NOT NULL,
	  `listid` VARCHAR(50) NOT NULL,
	  `name` VARCHAR(128) NOT NULL,
	  `platform`  VARCHAR(50) NOT NULL,
	  `is_sync` INT(1) NOT NULL DEFAULT 0,
	  `email_address` VARCHAR(128) NOT NULL,
	  `currency_code` CHAR(3) NOT NULL,
	  `money_format`  VARCHAR(10) NOT NULL,
	  `primary_locale` VARCHAR(5) NOT NULL,
	  `timezone`  VARCHAR(20) NOT NULL,
	  `phone` VARCHAR(50) NOT NULL,
	  `address_address_one` VARCHAR(50) NOT NULL,
	  `address_address_two` VARCHAR(50) NOT NULL,
	  `address_city` VARCHAR(50) NOT NULL,
	  `address_province` VARCHAR(50) NOT NULL,
	  `address_province_code` CHAR(2) NOT NULL,
	  `address_postal_code`  VARCHAR(50) NOT NULL,
	  `address_country`   VARCHAR(50) NOT NULL,
	  `address_country_code` CHAR(2) NOT NULL,
	  `domain` VARCHAR(512) NOT NULL,
	  `mc_account_name` VARCHAR(512) NOT NULL,
	  `list_name` VARCHAR(512) NOT NULL,
	  PRIMARY KEY  (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;
"
);

$installer->endSetup();
