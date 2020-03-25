<?php

$installer = $this;

try {
    $webhookStores = array();

    /* Check if webhook is created */
    $configDataCollection = Mage::getModel('core/config_data')
        ->getCollection()
        ->addFieldToFilter('path', 'mailchimp/general/webhook_active')
        ->addFieldToFilter('value', '1');

    foreach ($configDataCollection as $data) {
        $webhookStores []= $data->getScopeId();
    }

    /* If webhook is created, edites it and place the new "event" variable */
    if (!empty($webhookStores)) {
        $webhookData = array();
        $webhookIds = array();
        $helper = Mage::helper('mailchimp');

        // Get all the Webhooks ID related to the Stores.
        $configDataCollection = Mage::getModel('core/config_data')
            ->getCollection()
            ->addFieldToFilter('path', 'mailchimp/general/webhook_id')
            ->addFieldToFilter('scope_id', array('in' => $webhookStores));

        foreach ($configDataCollection as $data) {
            foreach ($webhookData as $webhook) {
                if ($webhook['store_id'] == $data->getScopeId()) {
                    $webhook ['webhook_id'] = $data->getValue();
                }
            }
        }

        foreach ($webhookStores as $storeId) {
            $listId = $helper->getGeneralList($storeId);
            $webhookData []= array('list_id' => $listId, 'store_id' => $storeId);
        }

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

        // Edits the webhooks through API.
        foreach ($webhookData as $webhook) {
            $helper
                ->getApi($webhook['store_id'])
                ->lists
                ->webhooks
                ->edit($webhook['list_id'], $webhook['webhook_id'], null, $events, $sources);
        }
    }
} catch (Exception $e) {
    Mage::log($e->getMessage(), null, 'MailChimp_Errors.log', true);
}


$installer->endSetup();
