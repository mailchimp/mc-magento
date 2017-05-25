<?php

$installer = $this;

try {
    $installer->run(
        "
ALTER TABLE `{$this->getTable('mailchimp_ecommerce_sync_data')}`
ADD INDEX `type` (`type`);
"
    );
}
catch (Exception $e)
{
    Mage::log($e->getMessage(), null, 'MailChimp_Errors.log', true);
}

$installer->endSetup();
