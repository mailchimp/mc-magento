<?php

/**
 * mailchimp-lib Magento Component
 *
 * @category  Ebizmarts
 * @package   mailchimp-lib
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Ebizmarts_MailChimp_Model_Api_Stores
{

    /**
     * Create MailChimp store.
     *
     * @param $mailChimpStoreId
     * @param string|null $listId
     * @param $scopeId
     * @param $scope
     * @return mixed
     * @throws Exception
     */
    public function createMailChimpStore($mailChimpStoreId, $listId = null, $scopeId, $scope)
    {
        $helper = $this->makeHelper();
        if (!$listId) {
            $listId = $helper->getGeneralList($scopeId, $scope);
        }

        if ($listId != null && $listId != "") {
            try {
                $api = $helper->getApi($scopeId, $scope);

                $storeName = $helper->getMCStoreName($scopeId, $scope);
                $storeEmail = $helper->getConfigValueForScope('trans_email/ident_general/email', $scopeId, $scope);
                $storeDomain = $helper->getStoreDomain($scopeId, $scope);
                if (strpos($storeEmail, 'example.com') !== false) {
                    $storeEmail = null;
                    throw new Exception('Please, change the general email in Store Email Addresses/General Contact');
                }

                $currencyCode = $helper->getConfigValueForScope(Mage_Directory_Model_Currency::XML_PATH_CURRENCY_DEFAULT, $scopeId, $scope);
                $isSyncing = true;
                $primaryLocale = $helper->getStoreLanguageCode($scopeId, $scope);
                $timeZone = $helper->getStoreTimeZone($scopeId, $scope);
                $storePhone = $helper->getStorePhone($scopeId, $scope);
                $currencySymbol = $helper->getMageApp()->getLocale()->currency($currencyCode)->getSymbol();
                $response = $api->getEcommerce()->getStores()->add($mailChimpStoreId, $listId, $storeName, $currencyCode, $isSyncing, 'Magento', $storeDomain, $storeEmail, $currencySymbol, $primaryLocale, $timeZone, $storePhone);
                return $response;

            } catch (Ebizmarts_MailChimp_Helper_Data_ApiKeyException $e) {
                $helper->logError($e->getMessage());
            } catch (MailChimp_Error $e) {
                $helper->logError($e->getFriendlyMessage());
            }
        } else {
            throw new Exception('You don\'t have any lists configured in MailChimp');
        }
    }

    /**
     * Delete MailChimp store.
     *
     * @param $mailchimpStoreId
     * @param $scopeId
     * @param $scope
     */
    public function deleteMailChimpStore($mailchimpStoreId, $scopeId, $scope)
    {
        $helper = $this->makeHelper();
        try {
            $api = $helper->getApi($scopeId, $scope);
            $api->getEcommerce()->getStores()->delete($mailchimpStoreId);
        } catch (Ebizmarts_MailChimp_Helper_Data_ApiKeyException $e) {
            $helper->logError($e->getMessage());
        } catch (MailChimp_Error $e) {
            $helper->logError($e->getFriendlyMessage());
        } catch (Exception $e) {
            $helper->logError($e->getMessage());
        }

        $connection = $helper->getCoreResource()->getConnection('core_write');
        $resource = $this->getSyncBatchesResource();
        $connection->update($resource->getMainTable(), array('status' => 'canceled'), "status = 'pending'");
    }

    /**
     * Edit MailChimp store name for given scope.
     *
     * @param $name
     * @param $scopeId
     * @param $scope
     */
    public function modifyName($name, $scopeId, $scope)
    {
        $helper = $this->makeHelper();
        try {
            $api = $helper->getApi($scopeId, $scope);
            $mailchimpStoreId = $helper->getMCStoreId($scopeId, $scope);
            $api->getEcommerce()->getStores()->edit($mailchimpStoreId, $name);
        } catch (Ebizmarts_MailChimp_Helper_Data_ApiKeyException $e) {
            $helper->logError($e->getMessage());
        } catch (MailChimp_Error $e) {
            $helper->logError($e->getFriendlyMessage());
        } catch (Exception $e) {
            $helper->logError($e->getMessage());
        }
    }

    /**
     * Returns URL from MailChimp store data
     *
     * @param  $scopeId
     * @param  $scope
     * @return mixed
     */
    public function getMCJsUrl($scopeId, $scope)
    {
        $helper = $this->makeHelper();
        try {
            $api = $helper->getApi($scopeId, $scope);
            $mailchimpStoreId = $helper->getMCStoreId($scopeId, $scope);
            $response = $this->getStoreConnectedSiteData($api, $mailchimpStoreId);
            if (isset($response['connected_site']['site_script']['url'])) {
                $url = $response['connected_site']['site_script']['url'];
                $configValues = array(array(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_MC_JS_URL, $url));
                $realScope = $helper->getRealScopeForConfig(Ebizmarts_MailChimp_Model_Config::GENERAL_LIST, $scopeId, $scope);
                $helper->saveMailchimpConfig($configValues, $realScope['scope_id'], $realScope['scope']);
                return $url;
            }
        } catch (Ebizmarts_MailChimp_Helper_Data_ApiKeyException $e) {
            $helper->logError($e->getMessage());
        } catch (MailChimp_Error $e) {
            $helper->logError($e->getFriendlyMessage());
        } catch (Exception $e) {
            $helper->logError($e->getMessage());
        }
    }

    /**
     * Set is_syncing value for the given scope.
     *
     * @param $mailchimpApi
     * @param $isSincingValue
     * @param $mailchimpStoreId
     */
    public function editIsSyncing($mailchimpApi, $isSincingValue, $mailchimpStoreId)
    {
        $mailchimpApi->ecommerce->stores->edit($mailchimpStoreId, null, null, null, $isSincingValue);
    }

    /**
     * @param $api
     * @param $mailchimpStoreId
     * @return mixed
     */
    protected function getStoreConnectedSiteData($api, $mailchimpStoreId)
    {
        $response = $api->ecommerce->stores->get($mailchimpStoreId, 'connected_site');
        return $response;
    }

    /**
     * @return Ebizmarts_MailChimp_Helper_Data
     */
    protected function makeHelper()
    {
        return Mage::helper('mailchimp');
    }

    /**
     * @return Object
     */
    protected function getSyncBatchesResource()
    {
        return Mage::getResourceModel('mailchimp/synchbatches');
    }
}
