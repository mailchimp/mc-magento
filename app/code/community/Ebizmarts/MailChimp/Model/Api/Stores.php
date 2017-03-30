<?php

/**
 * mailchimp-lib Magento Component
 *
 * @category Ebizmarts
 * @package mailchimp-lib
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Ebizmarts_MailChimp_Model_Api_Stores
{

//    /**
//     * Return MailChimp store for given scope if exists, else return null.
//     *
//     * @param $scope
//     * @param $scopeId
//     * @return array|null
//     * @throws Exception
//     */
//    public function getMailChimpStore($scope, $scopeId)
//    {
//        $api = Mage::helper('mailchimp')->getApi($scopeId, $scope);
//
//        if ($api) {
//            $storeExists = null;
//            $mailchimpStoreId = Mage::helper('mailchimp')->getMCStoreId($scopeId, $scope);
//
//            if ($mailchimpStoreId == null || $mailchimpStoreId == "") {
//                return null;
//            }
//
//            try {
//                $store = $api->ecommerce->stores->get($mailchimpStoreId);
//                if (is_array($store) && isset($store['id'])) {
//                    $storeExists = $store;
//                }
//            }
//            catch (Mailchimp_Error $e) {
//                Mage::helper('mailchimp')->logError($e->getFriendlyMessage());
//            }
//            catch (Exception $e) {
//                Mage::helper('mailchimp')->logError($e->getMessage());
//            }
//
//            return $storeExists;
//        } else {
//            throw new Exception('You must provide a MailChimp API key');
//        }
//    }

    /**
     * Create MailChimp store.
     * 
     * @param $mailChimpStoreId
     * @param null $listId
     * @param $scope
     * @param $scopeId
     * @throws Exception
     */
    public function createMailChimpStore($mailChimpStoreId, $listId=null, $scopeId, $scope)
    {
        $api = Mage::helper('mailchimp')->getApi($scopeId, $scope);
        if ($api) {
            if (!$listId) {
                $listId = Mage::helper('mailchimp')->getGeneralList($scopeId, $scope);
            }

            if ($listId != null && $listId != "") {
                $storeName = Mage::helper('mailchimp')->getMCStoreName($scopeId, $scope);
                $storeEmail = Mage::helper('mailchimp')->getConfigValueForScope('trans_email/ident_general/email', $scopeId, $scope);
                if (strpos($storeEmail, 'example.com') !== false) {
                    $storeEmail = null;
                    throw new Exception('Please, change the general email in Store Email Addresses/General Contact');
                }

                $currencyCode = Mage::helper('mailchimp')->getConfigValueForScope(Mage_Directory_Model_Currency::XML_PATH_CURRENCY_DEFAULT, $scopeId, $scope);
                $isSyncing = true;
                $api->ecommerce->stores->add($mailChimpStoreId, $listId, $storeName, $currencyCode, $isSyncing, 'Magento', null, $storeEmail);
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
        } catch (Mailchimp_Error $e) {
            Mage::helper('mailchimp')->logError($e->getFriendlyMessage(), $scopeId, $scope);
        } catch (Exception $e) {
            Mage::helper('mailchimp')->logError($e->getMessage(), $scopeId, $scope);
        }

        $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $resource = Mage::getResourceModel('mailchimp/synchbatches');
        $connection->update($resource->getMainTable(), array('status'=>'canceled'), "status = 'pending'");
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
            $storeId = Mage::helper('mailchimp')->getMCStoreId($scopeId, $scope);
            $api->ecommerce->stores->edit($storeId, $name);
        } catch (Mailchimp_Error $e) {
            Mage::helper('mailchimp')->logError($e->getFriendlyMessage(), $scopeId, $scope);
        } catch (Exception $e) {
            Mage::helper('mailchimp')->logError($e->getMessage(), $scopeId, $scope);
        }
    }
}