<?php

/**
 * MailChimp For Magento
 *
 * @category  Ebizmarts_MailChimp
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     3/20/2020 11:14 AM
 * @file:     Webhook.php
 */
class Ebizmarts_MailChimp_Helper_Webhook extends Mage_Core_Helper_Abstract
{
    /**
     * @var Ebizmarts_MailChimp_Helper_Data
     */
    protected $_helper;

    public function __construct()
    {
        $this->_helper = Mage::helper('mailchimp');
    }

    /**
     * @param       $scopeId
     * @param null  $scope
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function getWebhookDeleteAction($scopeId, $scope = null)
    {
        $helper = $this->getHelper();
        return $helper->getConfigValueForScope(
            Ebizmarts_MailChimp_Model_Config::GENERAL_UNSUBSCRIBE,
            $scopeId,
            $scope
        );
    }

    /**
     * Get webhook Id.
     *
     * @param int   $scopeId
     * @param null  $scope
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function getWebhookId($scopeId = 0, $scope = null)
    {
        $helper = $this->getHelper();
        return $helper->getConfigValueForScope(
            Ebizmarts_MailChimp_Model_Config::GENERAL_WEBHOOK_ID,
            $scopeId,
            $scope
        );
    }

    /**
     * @return string
     */
    public function getWebhooksKey()
    {
        $helper = $this->getHelper();
        $crypt = hash('md5', (string)$helper->getConfig()->getNode('global/crypt/key'));
        $key = substr($crypt, 0, (strlen($crypt) / 2));

        return $key;
    }

    /**
     * @param           $scopeId
     * @param string    $scope
     */
    public function handleWebhookChange($scopeId, $scope = 'stores')
    {
        $helper = $this->getHelper();
        $webhookScope = $helper->getRealScopeForConfig(
            Ebizmarts_MailChimp_Model_Config::GENERAL_LIST,
            $scopeId,
            $scope
        );
        $listId = $helper->getGeneralList($scopeId, $scope);
        $this->deleteCurrentWebhook($webhookScope['scope_id'], $webhookScope['scope'], $listId);

        if ($helper->isSubscriptionEnabled($scopeId, $scope)) {
            $this->createNewWebhook($webhookScope['scope_id'], $webhookScope['scope'], $listId);
        }
    }

    /**
     * @param $scopeId
     * @param $scope
     * @param $listId
     * @throws Mage_Core_Exception
     */
    protected function deleteCurrentWebhook($scopeId, $scope, $listId)
    {
        $helper = $this->getHelper();

        try {
            $api = $helper->getApi($scopeId, $scope);
            $webhookId = $this->getWebhookId($scopeId, $scope);
            $apiKey = $helper->getApiKey($scopeId, $scope);

            if ($webhookId && $apiKey && $listId) {
                try {
                    $api->getLists()->getWebhooks()->delete($listId, $webhookId);
                } catch (MailChimp_Error $e) {
                    $helper->logError($e->getFriendlyMessage());
                } catch (Exception $e) {
                    $helper->logError($e->getMessage());
                }

                $helper->getConfig()
                    ->deleteConfig(Ebizmarts_MailChimp_Model_Config::GENERAL_WEBHOOK_ID, $scope, $scopeId);
            } else {
                $webhookUrl = $this->getWebhookUrl($scopeId, $scope);
                try {
                    if ($listId) {
                        $this->_deletedWebhooksByListId($api, $listId, $webhookUrl);
                    }
                } catch (MailChimp_Error $e) {
                    $helper->logError($e->getFriendlyMessage());
                } catch (Exception $e) {
                    $helper->logError($e->getMessage());
                }
            }
        } catch (Ebizmarts_MailChimp_Helper_Data_ApiKeyException $e) {
            $helper->logError($e->getMessage());
        }
    }

    /**
     * @param $api
     * @param $listId
     * @param $webhookUrl
     */
    protected function _deletedWebhooksByListId($api, $listId, $webhookUrl)
    {
        $webhooks = $api->getLists()->getWebhooks()->getAll($listId);

        foreach ($webhooks['webhooks'] as $webhook) {
            if (strpos($webhook['url'], $webhookUrl) !== false) {
                $this->deleteWebhookFromList($api->getLists()->getWebhooks(), $listId, $webhook['id']);
            }
        }
    }

    /**
     * @param $apiWebhook
     * @param $listId
     * @param $webhookId
     */
    public function deleteWebhookFromList($apiWebhook, $listId, $webhookId)
    {
        $apiWebhook->delete($listId, $webhookId);
    }

    /**
     * Returns true on successful creation, or error message if it fails
     */
    public function createNewWebhook($scopeId, $scope, $listId)
    {
        //TODO: cambiar llamadas de este metodo
        $helper = $this->getHelper();
        $hookUrl = $this->getWebhookUrl();

        try {
            $api = $helper->getApi($scopeId, $scope);

            if ($helper->getTwoWaySyncEnabled($scopeId, $scope)) {
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
                    'api' => true
                );
            } else {
                $events = array(
                    'subscribe' => true,
                    'unsubscribe' => true,
                    'profile' => true,
                    'cleaned' => false,
                    'upemail' => false,
                    'campaign' => false
                );
                $sources = array(
                    'user' => true,
                    'admin' => true,
                    'api' => false
                );
            }

            try {
                $response = $api->getLists()->getWebhooks()->getAll($listId);
                $createWebhook = true;

                if (isset($response['total_items']) && $response['total_items'] > 0) {
                    foreach ($response['webhooks'] as $webhook) {
                        if ($webhook['url'] == $hookUrl) {
                            $createWebhook = false;
                            break;
                        }
                    }
                }

                if ($createWebhook) {
                    $newWebhook = $api->getLists()->getWebhooks()->add($listId, $hookUrl, $events, $sources);
                    $newWebhookId = $newWebhook['id'];
                    $configValues = array(array(Ebizmarts_MailChimp_Model_Config::GENERAL_WEBHOOK_ID, $newWebhookId));
                    $helper->saveMailchimpConfig($configValues, $scopeId, $scope);

                    return true;
                } else {
                    return $this->__('The webhook already exists.');
                }
            } catch (MailChimp_Error $e) {
                $errorMessage = $e->getFriendlyMessage();
                $helper->logError($errorMessage);
                $textToCompare = 'The resource submitted could not be validated. '
                    . 'For field-specific details, see the \'errors\' array.';

                if ($e->getMailchimpDetails() == $textToCompare) {
                    $errorMessage = 'Your store could not be accessed by MailChimp\'s Api. '
                        . 'Please confirm the URL: ' . $hookUrl
                        . ' is accessible externally to allow the webhook creation.';
                    $helper->logError($errorMessage);
                }

                return $helper->__($errorMessage);
            } catch (Exception $e) {
                $helper->logError($e->getMessage());
            }
        } catch (Ebizmarts_MailChimp_Helper_Data_ApiKeyException $e) {
            $helper->logError($e->getMessage());
        }
    }

    /**
     * @return string
     */
    protected function getWebhookUrl()
    {
        $helper = $this->getHelper();
        $store = $helper->getMageApp()->getDefaultStoreView();
        $webhooksKey = $this->getWebhooksKey();
        //Generating Webhooks URL
        $url = Ebizmarts_MailChimp_Model_ProcessWebhook::WEBHOOKS_PATH;
        $hookUrl = $store->getUrl(
            $url,
            array(
                'wkey' => $webhooksKey,
                '_nosid' => true,
                '_secure' => true,
            )
        );

        if (false != strstr($hookUrl, '?', true)) {
            $hookUrl = strstr($hookUrl, '?', true);
        }

        return $hookUrl;
    }

    /**
     * @return Ebizmarts_MailChimp_Helper_Data
     */
    protected function getHelper($type='')
    {
        return $this->_helper;
    }
}
