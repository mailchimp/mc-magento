<?php

$installer = $this;

$installer->startSetup();

try{
    $installer->run("
      ALTER TABLE `{$this->getTable('mailchimp_sync_batches')}` CHANGE column `store_id` VARCHAR(50) NOT NULL;
    ");
}
catch(Exception $e){
    Mage::helper('mailchimp')->logError($e->getMessage());
}


$installer->endSetup();