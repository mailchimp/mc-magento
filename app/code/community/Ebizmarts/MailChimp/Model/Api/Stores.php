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
     * @param  $mailChimpStoreId
     * @param  null $listId
     * @param  $scope
     * @param  $scopeId
     * @throws Exception
     */
    public function createMailChimpStore($mailChimpStoreId, $listId = null, $scopeId, $scope)
    {
        $api = Mage::helper('mailchimp')->getApi($scopeId, $scope);
        if ($api) {
            if (!$listId) {
                $listId = Mage::helper('mailchimp')->getGeneralList($scopeId, $scope);
            }

            if ($listId != null && $listId != "") {
                $storeName = Mage::helper('mailchimp')->getMCStoreName($scopeId, $scope);
                $storeEmail = Mage::helper('mailchimp')->getConfigValueForScope('trans_email/ident_general/email', $scopeId, $scope);
                $storeDomain = Mage::helper('mailchimp')->getStoreDomain($scopeId, $scope);
                if (strpos($storeEmail, 'example.com') !== false) {
                    $storeEmail = null;
                    throw new Exception('Please, change the general email in Store Email Addresses/General Contact');
                }

                $currencyCode = Mage::helper('mailchimp')->getConfigValueForScope(Mage_Directory_Model_Currency::XML_PATH_CURRENCY_DEFAULT, $scopeId, $scope);
                $isSyncing = true;
                $primaryLocale = Mage::helper('mailchimp')->getStoreLanguageCode($scopeId, $scope);
                $timeZone = Mage::helper('mailchimp')->getStoreTimeZone($scopeId, $scope);
                $storePhone = Mage::helper('mailchimp')->getStorePhone($scopeId, $scope);
                $currencySymbol = Mage::app()->getLocale()->currency($currencyCode)->getSymbol();
                $response = $api->ecommerce->stores->add($mailChimpStoreId, $listId, $storeName, $currencyCode, $isSyncing, 'Magento', $storeDomain, $storeEmail, $currencySymbol, $primaryLocale, $timeZone, $storePhone);
                return $response;
            } else {
                throw new Exception('You don\'t have any lists configured in MailChimp');
            }
        } else {
            throw new Exception('You must provide a MailChimp API key');
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
        try {
            $api = Mage::helper('mailchimp')->getApi($scopeId, $scope);
            $api->ecommerce->stores->delete($mailchimpStoreId);
        } catch (MailChimp_Error $e) {
            Mage::helper('mailchimp')->logError($e->getFriendlyMessage(), $scopeId, $scope);
        } catch (Exception $e) {
            Mage::helper('mailchimp')->logError($e->getMessage(), $scopeId, $scope);
        }

        $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $resource = Mage::getResourceModel('mailchimp/synchbatches');
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
        try {
            $api = Mage::helper('mailchimp')->getApi($scopeId, $scope);
            $mailchimpStoreId = Mage::helper('mailchimp')->getMCStoreId($scopeId, $scope);
            $api->ecommerce->stores->edit($mailchimpStoreId, $name);
        } catch (MailChimp_Error $e) {
            Mage::helper('mailchimp')->logError($e->getFriendlyMessage(), $scopeId, $scope);
        } catch (Exception $e) {
            Mage::helper('mailchimp')->logError($e->getMessage(), $scopeId, $scope);
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
        try {
            $api = Mage::helper('mailchimp')->getApi($scopeId, $scope);
            $mailchimpStoreId = Mage::helper('mailchimp')->getMCStoreId($scopeId, $scope);
            $response = $this->getStoreConnectedSiteData($api, $mailchimpStoreId);
            if (isset($response['connected_site']['site_script']['url'])) {
                $url = $response['connected_site']['site_script']['url'];
                $configValues = array(array(Ebizmarts_MailChimp_Model_Config::ECOMMERCE_MC_JS_URL, $url));
                $realScope = Mage::helper('mailchimp')->getRealScopeForConfig(Ebizmarts_MailChimp_Model_Config::GENERAL_LIST, $scopeId, $scope);
                Mage::helper('mailchimp')->saveMailchimpConfig($configValues, $realScope['scope_id'], $realScope['scope']);
                return $url;
            }
        } catch (MailChimp_Error $e) {
            Mage::helper('mailchimp')->logError($e->getFriendlyMessage(), $scopeId, $scope);
        } catch (Exception $e) {
            Mage::helper('mailchimp')->logError($e->getMessage(), $scopeId, $scope);
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
}