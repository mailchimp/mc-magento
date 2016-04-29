<?php

$installer = $this;

$installer->startSetup();

$installer->run("

    CREATE TABLE IF NOT EXISTS `{$this->getTable('mailchimp_subscribers')}` (
      `id` INT(10) unsigned NOT NULL auto_increment,
      `email` varchar(128),
      `email_id` TEXT,
      `first_name` TEXT,
      `last_name` TEXT,
      `orders_count` INT(5) unsigned,
      `total_spent` INT(10) unsigned,
      `address` TEXT,
      `status` TEXT NOT NULL,
      `lists` TEXT NOT NULL,
      `merge_fields` TEXT,
      `language` TEXT,
      `created_at` DATETIME NOT NULL ,
      `store_id` smallint(5),
      `processed` smallint(1) default 0,
      PRIMARY KEY  (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    
    CREATE TABLE IF NOT EXISTS `{$this->getTable('mailchimp_orders')}` (
      `id` INT(10) unsigned NOT NULL auto_increment,
      `order_id` INT(10) unsigned NOT NULL,
      `email` varchar(128),
      `info` TEXT,
      `created_at` DATETIME NOT NULL ,
      `store_id` smallint(5),
      `processed` smallint(1) default 0,
      PRIMARY KEY  (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

");

$installer->run("
UPDATE `{$installer->getTable('mailchimp_orders')}` A JOIN `{$installer->getTable('sales_flat_order')}` B
  ON A.order_id = B.entity_id
  SET A.store_id = B.store_id
");

$installer->endSetup();

