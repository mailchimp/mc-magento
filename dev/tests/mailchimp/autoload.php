<?php

$magentoRoot = getenv('MAGENTO_ROOT');
if (empty($magentoRoot)) {
    $magentoRoot = realpath(dirname(dirname(dirname(__DIR__))));
}

define('MAGENTO_ROOT', $magentoRoot);

//Set custom memory limit
ini_set('memory_limit', '512M');
//Include Magento libraries
require_once(MAGENTO_ROOT .'/app/Mage.php');
//Start the Magento application
Mage::app('default');
//Avoid issues "Headers already send"
session_start();
