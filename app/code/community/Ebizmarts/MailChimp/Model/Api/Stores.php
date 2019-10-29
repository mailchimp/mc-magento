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
     * Create Mailchimp store.
     *
     * @param  $apiKey
     * @param  $listId
     * @param  $storeName
     * @param  $currencyCode
     * @param  $storeDomain
     * @param  $storeEmail
     * @param  $primaryLocale
     * @param  $timeZone
     * @param  $storePhone
     * @param  $address
     * @return mixed
     * @throws Exception
     */
    public function createMailChimpStore(
        $apiKey,
        $listId,
        $storeName,
        $currencyCode,
        $storeDomain,
        $storeEmail,
        $primaryLocale,
        $timeZone,
        $storePhone,
        $address
    ) {
        $helper = $this->makeHelper();
        $dateHelper = $this->makeDateHelper();
        $date = $dateHelper->getDateMicrotime();
        $mailchimpStoreId = hash('md5', $storeName . '_' . $date);

        try {
            $api = $helper->getApiByKey($apiKey);
            $isSyncing = true;
            $currencySymbol = $helper->getMageApp()->getLocale()->currency($currencyCode)->getSymbol();
            $response = $this->addStore(
                $api,
                $mailchimpStoreId,
                $listId,
                $storeName,
                $currencyCode,
                $isSyncing,
                $storeDomain,
                $storeEmail,
                $currencySymbol,
                $primaryLocale,
                $timeZone,
                $storePhone,
                $address
            );
            $configValues = array(
                array(
                    Ebizmarts_MailChimp_Model_Config::ECOMMERCE_MC_JS_URL . "_$mailchimpStoreId",
                    $response['connected_site']['site_script']['url']
                )
            );
            $helper->saveMailchimpConfig($configValues, 0, 'default');
            $successMessage = $helper->__("The Mailchimp store was successfully created.");
            $adminSession = $this->getAdminSession();
            $adminSession->addSuccess($successMessage);
        } catch (Ebizmarts_MailChimp_Helper_Data_ApiKeyException $e) {
            $response = $errorMessage = $e->getMessage();
            $helper->logError($errorMessage);
            $adminSession = $this->getAdminSession();
            $adminSession->addError($errorMessage);
        } catch (MailChimp_Error $e) {
            $adminSession = $this->getAdminSession();
            $response = $errorMessage = $e->getFriendlyMessage();
            $helper->logError($errorMessage);
            $errorMessage = $this->getUserFriendlyMessage($e);
            $adminSession->addError($errorMessage);
        } catch (Exception $e) {
            $response = $errorMessage = $e->getMessage();
            $helper->logError($errorMessage);
            $adminSession = $this->getAdminSession();
            $adminSession->addError($errorMessage);
        }

        return $response;
    }

    /**
     * Edit Mailchimp store.
     *
     * @param  $mailchimpStoreId
     * @param  $apiKey
     * @param  $storeName
     * @param  $currencyCode
     * @param  $storeDomain
     * @param  $storeEmail
     * @param  $primaryLocale
     * @param  $timeZone
     * @param  $storePhone
     * @param  $address
     * @return mixed|string
     * @throws Mage_Core_Exception
     */
    public function editMailChimpStore(
        $mailchimpStoreId,
        $apiKey,
        $storeName,
        $currencyCode,
        $storeDomain,
        $storeEmail,
        $primaryLocale,
        $timeZone,
        $storePhone,
        $address
    ) {
        $helper = $this->makeHelper();

        try {
            $api = $helper->getApiByKey($apiKey);
            $currencySymbol = $helper->getMageApp()->getLocale()->currency($currencyCode)->getSymbol();
            $response = $api->getEcommerce()->getStores()->edit(
                $mailchimpStoreId,
                $storeName,
                'Magento',
                $storeDomain,
                null,
                $storeEmail,
                $currencyCode,
                $currencySymbol,
                $primaryLocale,
                $timeZone,
                $storePhone,
                $address
            );
            $successMessage = $helper->__("The Mailchimp store was successfully edited.");
            $adminSession = $this->getAdminSession();
            $adminSession->addSuccess($successMessage);
        } catch (Ebizmarts_MailChimp_Helper_Data_ApiKeyException $e) {
            $response = $errorMessage = $e->getMessage();
            $helper->logError($errorMessage);
            $adminSession = $this->getAdminSession();
            $adminSession->addError($errorMessage);
        } catch (MailChimp_Error $e) {
            $adminSession = $this->getAdminSession();
            $response = $errorMessage = $e->getFriendlyMessage();
            $helper->logError($errorMessage);
            $errorMessage = $this->getUserFriendlyMessage($e);
            $adminSession->addError($errorMessage);
        } catch (Exception $e) {
            $response = $errorMessage = $e->getMessage();
            $helper->logError($errorMessage);
            $adminSession = $this->getAdminSession();
            $adminSession->addError($errorMessage);
        }

        return $response;
    }

    /**
     * @param $e MailChimp_Error
     * @return string
     */
    protected function getUserFriendlyMessage($e)
    {
        $helper = $this->makeHelper();
        $errorMessage = $e->getFriendlyMessage();

        if (strstr($errorMessage, 'A store with the domain')) {
            $errorMessage = $helper->__(
                'A Mailchimp store with the same domain already exists in this account. '
                    . 'You need to have a different URLs for each scope you set up the ecommerce data. '
                    . 'Possible solutions '
            )
                . "<a href='https://docs.magento.com/m1/ce/user_guide/search_seo/seo-url-rewrite-configure.html'>"
                . "HERE</a> and "
                . "<a href='https://docs.magento.com/m1/ce/user_guide/configuration/url-secure-unsecure.html'>"
                . "HERE</a>";
        } else {
            if (is_array($e->getMailchimpErrors())) {
                $errorDetail = "";

                foreach ($e->getMailchimpErrors() as $error) {
                    if (isset($error['field'])) {
                        $errorDetail .= "<br />    Field: " . $error['field'];
                    }

                    if (isset($error['message'])) {
                        $errorDetail .= " Message: " . $error['message'];
                    }
                }

                if (!empty($errorDetail)) {
                    $errorMessage = "Error: $errorDetail";
                }
            }
        }

        return $errorMessage;
    }

    /**
     * Delete MailChimp store.
     *
     * @param  $mailchimpStoreId
     * @param  $apiKey
     * @return mixed|string
     * @throws Mage_Core_Exception
     * @throws Ebizmarts_MailChimp_Helper_Data_ApiKeyException
     */
    public function deleteMailChimpStore($mailchimpStoreId, $apiKey)
    {
        $helper = $this->makeHelper();

        try {
            $api = $helper->getApiByKey($apiKey);
            $response = $api->getEcommerce()->getStores()->delete($mailchimpStoreId);
            $helper->cancelAllPendingBatches($mailchimpStoreId);
            $successMessage = $helper->__("The Mailchimp store was successfully deleted.");
            $adminSession = $this->getAdminSession();
            $adminSession->addSuccess($successMessage);
        } catch (Ebizmarts_MailChimp_Helper_Data_ApiKeyException $e) {
            $response = $errorMessage = $e->getMessage();
            $helper->logError($errorMessage);
            $adminSession = $this->getAdminSession();
            $adminSession->addError($errorMessage);
        } catch (MailChimp_Error $e) {
            $response = $errorMessage = $e->getFriendlyMessage();
            $helper->logError($errorMessage);
            $adminSession = $this->getAdminSession();
            $adminSession->addError($errorMessage);
        } catch (Exception $e) {
            $response = $errorMessage = $e->getMessage();
            $helper->logError($errorMessage);
            $adminSession = $this->getAdminSession();
            $adminSession->addError($errorMessage);
        }

        return $response;
    }

    /**
     * Remove all data associated to the given Mailchimp store id.
     *
     * @param $mailchimpStoreId
     */
    protected function deleteLocalMCStoreData($mailchimpStoreId)
    {
        $helper = $this->makeHelper();
        $helper->deleteAllMCStoreData($mailchimpStoreId);
    }

    /**
     * Set is_syncing value for the given scope.
     *
     * @param  $mailchimpApi Ebizmarts_MailChimp
     * @param  $isSincingValue
     * @param  $mailchimpStoreId
     * @throws MailChimp_Error
     */
    public function editIsSyncing($mailchimpApi, $isSincingValue, $mailchimpStoreId)
    {
        $mailchimpApi->getEcommerce()->getStores()->edit(
            $mailchimpStoreId,
            null,
            null,
            null,
            $isSincingValue
        );
    }

    /**
     * @return Ebizmarts_MailChimp_Helper_Data
     */
    protected function makeHelper()
    {
        return Mage::helper('mailchimp');
    }

    /**
     * @return Ebizmarts_MailChimp_Helper_Dat3
     */
    protected function makeDateHelper()
    {
        return Mage::helper('mailchimp/date');
    }

    /**
     * @return Mage_Adminhtml_Model_Session
     */
    protected function getAdminSession()
    {
        return Mage::getSingleton('adminhtml/session');
    }

    /**
     * @param $api
     * @param $mailchimpStoreId
     * @param $listId
     * @param $storeName
     * @param $currencyCode
     * @param $isSyncing
     * @param $storeDomain
     * @param $storeEmail
     * @param $currencySymbol
     * @param $primaryLocale
     * @param $timeZone
     * @param $storePhone
     * @param $address
     * @return mixed
     */
    protected function addStore(
        $api,
        $mailchimpStoreId,
        $listId,
        $storeName,
        $currencyCode,
        $isSyncing,
        $storeDomain,
        $storeEmail,
        $currencySymbol,
        $primaryLocale,
        $timeZone,
        $storePhone,
        $address
    ) {
        return $api->getEcommerce()->getStores()->add(
            $mailchimpStoreId,
            $listId,
            $storeName,
            $currencyCode,
            $isSyncing,
            'Magento',
            $storeDomain,
            $storeEmail,
            $currencySymbol,
            $primaryLocale,
            $timeZone,
            $storePhone,
            $address
        );
    }
}
