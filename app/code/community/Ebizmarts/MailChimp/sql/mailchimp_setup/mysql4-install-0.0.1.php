<?php

$installer = $this;

$installer->startSetup();

$eav = new Mage_Eav_Model_Entity_Setup('core_setup');

$eav->addAttribute('customer', 'mailchimp_sync_delta', array(
    'label'     => 'MailChimp last sync timestamp',
    'type'      => 'datetime',
    'input'     => 'text',
    'visible'   => true,
    'required'  => true,
    'position'  => 1,
));

$installer->endSetup();

