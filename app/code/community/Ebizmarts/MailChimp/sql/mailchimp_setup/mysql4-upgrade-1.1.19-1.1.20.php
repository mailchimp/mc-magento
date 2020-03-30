<?php

$installer = $this;

try {
    $webhookData = array();

    /* Check if webhook is created */
    $configDataCollection = Mage::getModel('core/config_data')
        ->getCollection()
        ->addFieldToFilter('path', 'mailchimp/general/webhook_id');

    foreach ($configDataCollection as $data) {
        $webhookData []= array(
            'webhook_id' => $data->getValue(),
            'scope_id' => $data->getScopeId(),
            'scope' => $data->getScope()
        );
    }

    /* If webhook is created, edites it and place the new "event" variable */
    if (!empty($webhookData)) {
        $helper = Mage::helper('mailchimp');

        $events = array(
            'subscribe' => true,
            'unsubscribe' => true,
            'profile' => true,
            'cleaned' => true,
            'upemail' => true,
            'campaign' => false
        );

        $sources = array(
            'user' => true,
            'admin' => true,
            'api' => false
        );

        // Get all ApiKeys
        $apiKeys = array();
        $apiKeyCollection = Mage::getModel('core/config_data')
            ->getCollection()
            ->addFieldToFilter('path', 'mailchimp/general/apiKey');

        foreach ($apiKeyCollection as $data)
        {
            $apiKey = $data->getValue();

            if ($apiKey !== null) {
                $apiKey = $helper->decryptData($apiKey);
            }

            $apiKeys []= array(
                'scope' => $data->getScope(),
                'scope_id' => $data->getScopeId(),
                'apikey' => $apiKey
            );
        }

        // Get all List Ids
        $listIds = array();
        $listCollection = Mage::getModel('core/config_data')
            ->getCollection()
            ->addFieldToFilter('path', 'mailchimp/general/list');

        foreach ($listCollection as $data)
        {
            $listIds []= array(
                'scope' => $data->getScope(),
                'scope_id' => $data->getScopeId(),
                'list' => $data->getValue()
            );
        }

        // Edits the webhooks through API.
        foreach ($webhookData as $webhook) {
            $listId = retrieveListId($listIds, $webhook['scope'], $webhook['scope_id']);
            $apiKey = retrieveApiKey($apiKeys, $webhook['scope'], $webhook['scope_id']);

            $helper
                ->getApiByKey($apiKey)
                ->lists
                ->webhooks
                ->edit($listId, $webhook['webhook_id'], null, $events, $sources);
        }
    }
} catch (Exception $e) {
    Mage::log($e->getMessage(), null, 'MailChimp_Errors.log', true);
}

function retrieveApiKey($apiKeys, $scope, $scopeId)
{
    $apiKey = searchInData($apiKeys, $scope, $scopeId);

    if ($apiKey === null) {
        throw new Exception('API Key not configured at default level.');
    }

    return $apiKey['apikey'];
}

function retrieveListId($listIds, $scope, $scopeId)
{
    $listId = searchInData($listIds, $scope, $scopeId);

    if ($listId === null) {
        throw new Exception('List not configured at default level.');
    }

    return $listId['list'];
}

function searchInData($searchArray, $scope, $scopeId)
{
    $returnArray = null;
    $websiteArray = null;
    $defaultArray = null;

    foreach ($searchArray as $data)
    {
        if ($data['scope'] == $scope && $data['scope_id'] == $scopeId) {
            $returnArray = $data;
        } elseif ($data['scope'] == 'website' && $data['scope_id'] == '1') {
            $websiteArray = $data;
        } elseif ($data['scope'] == 'default' && $data['scope_id'] == '1') {
            $defaultArray = $data;
        }
    }

    if ($returnArray === null) {
        $returnArray = $websiteArray;
    } elseif ($returnArray === null) {
        $returnArray = $defaultArray;
    }

    return $returnArray;
}


$installer->endSetup();
