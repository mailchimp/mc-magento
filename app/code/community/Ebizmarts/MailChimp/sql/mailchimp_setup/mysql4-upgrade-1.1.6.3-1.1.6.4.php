<?php

$installer = $this;

Mage::helper('mailchimp')->saveMailChimpConfig(array(array(Ebizmarts_MailChimp_Model_Config::GENERAL_MIGRATE_FROM_116, 1)), 0, 'default');

$installer->endSetup();