<?php

$installer = $this;

try {
    $installer->run(
        "
ALTER TABLE `{$this->getTable('mailchimp_ecommerce_sync_data')}` 
ADD INDEX `mailchimp_store_id` (`mailchimp_store_id`), ADD INDEX `related_id` (`related_id`);
"
    );
}
catch (Exception $e)
{
    Mage::log($e->getMessage(), null, 'MailChimp_Errors.log', true);
}

$installer->endSetup();