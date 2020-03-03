<?php

/**
 * mc-magento Magento Component
 *
 * @category  Ebizmarts
 * @package   mc-magento
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @file:     MailchimpstoresController.php
 */
class Ebizmarts_MailChimp_Adminhtml_MailchimpstoresController extends Mage_Adminhtml_Controller_Action
{

    /**
     * @var Ebizmarts_MailChimp_Helper_Data
     */
    protected $_helper;

    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('newsletter')
            ->_addBreadcrumb($this->__('Newsletter'), $this->__('Mailchimp Store'));

        return $this;
    }

    public function indexAction()
    {
        $this->_loadStores();
        $this->_title($this->__('Newsletter'))
            ->_title($this->__('Mailchimp'));

        $this->loadLayout();
        $this->_setActiveMenu('newsletter/mailchimp');
        $this->renderLayout();
    }

    public function gridAction()
    {
        $this->loadLayout(false);
        $this->renderLayout();
    }

    protected function _initStore($idFieldName = 'id')
    {
        $this->_title($this->__('Mailchimp Stores'))->_title($this->__('Manage Mailchimp Stores'));
        $storeId = (int)$this->getRequest()->getParam($idFieldName);

        if ($storeId) {
            $store = $this->loadMailchimpStore($storeId);
            $this->sessionregisterStore($store);
        }

        return $this;
    }

    public function editAction()
    {
        $this->_title($this->__('Mailchimp'))->_title($this->__('Mailchimp Store'));
        $id = $this->getRequest()->getParam('id');
        $mailchimpStore = $this->loadMailchimpStore($id);
        $this->sessionregisterStore($mailchimpStore);
        $title = $id ? $this->__('Edit Store') : $this->__('New Store');
        $this->_initAction();

        $block = $this->getLayout()
            ->createBlock('mailchimp/adminhtml_mailchimpstores_edit')
            ->setData('action', $this->getUrl('*/*/save'));

        $this->_addBreadcrumb($title, $title)
            ->_addContent($block)
            ->renderLayout();
    }

    public function newAction()
    {
        $this->_forward('edit');
    }

    public function saveAction()
    {
        $isPost = $this->getRequest()->getPost();

        if ($isPost) {
            $isPost['apikey'] = $this->getMailchimpHelper()->decryptData($isPost['apikey']);
            $this->_updateMailchimp($isPost);
        }

        $this->_redirect('*/*/index');
    }

    protected function _updateMailchimp($formData)
    {
        $helper = $this->getMailchimpHelper();
        $address = $this->createAddressArray($formData);
        $emailAddress = $formData['email_address'];
        $currencyCode = $formData['currency_code'];
        $primaryLocale = $formData['primary_locale'];
        $timeZone = $formData['timezone'];
        $phone = $formData['phone'];
        $name = $formData['name'];
        $domain = $formData['domain'];
        $storeId = isset($formData['storeid']) ? $formData['storeid'] : null;
        $apiKey = $formData['apikey'];

        if ($helper->isApiKeyObscure($apiKey)) {
            $apiKey = $helper->getApiKey($storeId);
        }

        if ($storeId) {
            $apiStore = $helper->getApiStores();
            $apiStore->editMailChimpStore(
                $storeId,
                $apiKey,
                $name,
                $currencyCode,
                $domain,
                $emailAddress,
                $primaryLocale,
                $timeZone,
                $phone,
                $address
            );
        } else {
            $apiStore = $helper->getApiStores();
            $apiStore->createMailChimpStore(
                $apiKey,
                $formData['listid'],
                $name,
                $currencyCode,
                $domain,
                $emailAddress,
                $primaryLocale,
                $timeZone,
                $phone,
                $address
            );
        }
    }

    protected function _loadStores()
    {
        $helper = $this->getMailchimpHelper();
        $allApiKeys = $helper->getAllApiKeys();
        $resource = Mage::getSingleton('core/resource');
        $connection = $resource->getConnection('core_write');
        $tableName = $resource->getTableName('mailchimp/stores');
        $connection->delete($tableName);

        foreach ($allApiKeys as $apiKey) {
            try {
                $api = $helper->getApiByKey($apiKey);
            } catch (Ebizmarts_MailChimp_Helper_Data_ApiKeyException $e) {
                $helper->logError($e->getMessage());
                continue;
            }

            try {
                $root = $api->getRoot()->info();
                $stores = $api->getEcommerce()->getStores()->get(null, null, null, 100);
            } catch (MailChimp_Error $e) {
                $helper->logError($e->getFriendlyMessage());
                continue;
            } catch (Exception $e) {
                $helper->logError($e->getMessage());
                continue;
            }

            $apiKey = $helper->encryptData($apiKey);

            foreach ($stores['stores'] as $store) {
                if ($store['platform'] == 'Magento') {
                    try {
                        $list = $api->getLists()->getLists($store['list_id']);
                    } catch (MailChimp_Error $e) {
                        $helper->logError($e->getFriendlyMessage());
                        continue;
                    } catch (Exception $e) {
                        $helper->logError($e->getMessage());
                        continue;
                    }

                    $this->_saveStoreData($apiKey, $store, $root, $list);
                }
            }
        }
    }

    /**
     * @param $apiKey
     * @param $store
     * @param $root
     * @param $list
     */
    protected function _saveStoreData($apiKey, $store, $root, $list)
    {
        $storeData = Mage::getModel('mailchimp/stores');
        $storeData->setApikey($apiKey)
            ->setStoreid($store['id'])
            ->setListid($store['list_id'])
            ->setName($store['name'])
            ->setPlatform($store['platform'])
            ->setIsSync($store['is_syncing'])
            ->setEmailAddress($store['email_address'])
            ->setCurrencyCode($store['currency_code'])
            ->setMoneyFormat($store['money_format'])
            ->setPrimaryLocale($store['primary_locale'])
            ->setTimezone($store['timezone'])
            ->setPhone($store['phone'])
            ->setAddressAddressOne($store['address']['address1'])
            ->setAddressAddressTwo($store['address']['address2'])
            ->setAddressCity($store['address']['city'])
            ->setAddressProvince($store['address']['province'])
            ->setAddressProvinceCode($store['address']['province_code'])
            ->setAddressPostalCode($store['address']['postal_code'])
            ->setAddressCountry($store['address']['country'])
            ->setAddressCountryCode($store['address']['country_code'])
            ->setDomain($store['domain'])
            ->setMcAccountName($root['account_name'])
            ->setListName(key_exists('name', $list) ? $list['name'] : '')
            ->save();
    }

    public function getstoresAction()
    {
        $helper = $this->getMailchimpHelper();
        $apiKey = $helper->decryptData($this->getRequest()->getParam('api_key'));

        try {
            $api = $helper->getApiByKey($apiKey);
            $lists = $api->getLists()->getLists();
            $data = array();

            foreach ($lists['lists'] as $list) {
                $data[$list['id']] = array('id' => $list['id'], 'name' => $list['name']);
            }
        } catch (Ebizmarts_MailChimp_Helper_Data_ApiKeyException $e) {
            $data = array('error' => 1, 'message' => $e->getMessage());
            $helper->logError($e->getMessage());
        } catch (MailChimp_Error $e) {
            $data = array('error' => 1, 'message' => $e->getFriendlyMessage());
            $helper->logError($e->getFriendlyMessage());
        } catch (Exception $e) {
            $data = array('error' => 1, 'message' => $e->getMessage());
            $helper->logError($e->getMessage());
        }

        $jsonData = json_encode($data);
        $response = $this->getResponse();
        $response->setHeader('Content-type', 'application/json');
        $response->setBody($jsonData);
    }

    public function deleteAction()
    {
        $helper = $this->getMailchimpHelper();
        $id = $this->getRequest()->getParam('id');
        $store = $this->loadMailchimpStore($id);
        $mailchimpStoreId = $store->getStoreid();
        $apiKey = $helper->decryptData($store->getApikey());

        if ($store->getId()) {
            try {
                $apiStore = $helper->getApiStores();
                $apiStore->deleteMailChimpStore($mailchimpStoreId, $apiKey);
                $helper->deleteAllMCStoreData($mailchimpStoreId);
            } catch (Ebizmarts_MailChimp_Helper_Data_ApiKeyException $e) {
                $helper->logError($e->getMessage());
            } catch (MailChimp_Error $e) {
                $helper->logError($e->getFriendlyMessage());
            } catch (Exception $e) {
                $helper->logError($e->getMessage());
            }
        }

        $this->_redirect('*/*/index');
    }

    protected function _isAllowed()
    {
        $acl = '';
        switch ($this->getRequest()->getActionName()) {
        case 'index':
        case 'grid':
        case 'edit':
        case 'new':
        case 'save':
        case 'getstores':
        case 'delete':
            $acl = 'newsletter/mailchimp/mailchimpstores';
            break;
        }

        return Mage::getSingleton('admin/session')->isAllowed($acl);
    }

    /**
     * @param $store
     * @throws Mage_Core_Exception
     */
    protected function sessionregisterStore($store)
    {
        Mage::register('current_mailchimpstore', $store);
    }

    /**
     * @param $id
     * @return Ebizmarts_MailChimp_Model_Stores
     */
    protected function loadMailchimpStore($id)
    {
        return Mage::getModel('mailchimp/stores')->load($id);
    }

    /**
     * @return Ebizmarts_MailChimp_Helper_Data
     */
    protected function getMailchimpHelper()
    {
        if ($this->_helper === null) {
            $this->_helper = Mage::helper('mailchimp');
        }

        return $this->_helper;
    }

    /**
     * @param $formData
     * @return array
     */
    protected function createAddressArray($formData)
    {
        $address = array();
        $address['address1'] = $formData['address_address_one'];
        $address['address2'] = $formData['address_address_two'];
        $address['city'] = $formData['address_city'];
        $address['province'] = '';
        $address['province_code'] = '';
        $address['postal_code'] = $formData['address_postal_code'];
        $address['country'] = '';
        $address['country_code'] = $formData['address_country_code'];

        return $address;
    }
}
