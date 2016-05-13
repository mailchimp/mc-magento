<?php

$installer = $this;

$installer->startSetup();

$installer->getConnection()->addColumn(
    $installer->getTable('newsletter_subscriber'), 'subscriber_firstname', 'varchar(50)'
);

$installer->getConnection()->addColumn(
    $installer->getTable('newsletter_subscriber'), 'subscriber_lastname', 'varchar(50)'
);

$installer->endSetup();