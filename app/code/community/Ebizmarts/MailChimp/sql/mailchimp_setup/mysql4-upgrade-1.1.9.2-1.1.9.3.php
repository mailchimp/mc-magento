<?php

$installer = $this;

try {
    $installer->run(
        "
ALTER TABLE `{$this->getTable('mailchimp_ecommerce_sync_data')}` 
ADD COLUMN `deleted_related_id` INT(10) DEFAULT NULL;
"
    );
} catch (Exception $e) {
    Mage::log($e->getMessage(), null, 'MailChimp_Errors.log', true);
}

$installer->endSetup();