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
    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('newsletter')
            ->_addBreadcrumb(Mage::helper('mailchimp')->__('Newsletter'), Mage::helper('mailchimp')->__('MailChimp Store'));
        return $this;
    }
    public function indexAction()
    {
        $this->_loadStores();
        $this->_title($this->__('Newsletter'))
            ->_title($this->__('MailChimp'));

        $this->loadLayout();
        $this->_setActiveMenu('newsletter/mailchimp');
        $this->renderLayout();
    }

    public function gridAction()
    {
        $this->loadLayout(false);
        $this->renderLayout();
    }    protected function _isAllowed()
    {
        $acl = '';
        switch ($this->getRequest()->getActionName()) {
            case 'index':
            case 'edit':
            case 'grid':
                $acl = 'newsletter/mailchimp/mailchimpstores';
                break;
        }

        return Mage::getSingleton('admin/session')->isAllowed($acl);
    }
    protected function _initStore($idFieldName = 'id')
    {
        $this->_title($this->__('MailChimp Stores'))->_title($this->__('Manage MailChimp Stores'));

        $storeId = (int) $this->getRequest()->getParam($idFieldName);
        $store = Mage::getModel('mailchimp/stores');

        if ($storeId) {
            $store->load($storeId);
        }

        Mage::register('current_mailchimpstore', $store);
        return $this;
    }

    public function editAction()
    {
        $this->_title($this->__('MailChimp'))->_title($this->__('MailChimp Store'));
        $id  = $this->getRequest()->getParam('id');
        $mailchimpStore = Mage::getModel('mailchimp/stores')->load($id);
        Mage::register('current_mailchimpstore', $mailchimpStore);


        $this->_initAction()
            ->_addBreadcrumb($id ? Mage::helper('mailchimp')->__('Edit Store') :  Mage::helper('mailchimp')->__('New Store'), $id ?  Mage::helper('mailchimp')->__('Edit Store') :  Mage::helper('mailchimp')->__('New Store'))
            ->_addContent($this->getLayout()->createBlock('mailchimp/adminhtml_mailchimpstores_edit')->setData('action', $this->getUrl('*/*/save')))
            ->renderLayout();


    }
    public function newAction()
    {
        $this->_forward('edit');
    }
    public function saveAction()
    {
        Mage::log(__METHOD__);
        $this->_redirectReferer();
    }
    protected function _updateMailchimp()
    {

    }
    protected function _loadStores()
    {
        $resource = Mage::getSingleton('core/resource');
        $connection = $resource->getConnection('core_write');
        $tableName = $resource->getTableName('mailchimp/stores');
        $connection->delete($tableName);
        /**
         * @var $helper Ebizmarts_MailChimp_Helper_Data
         */
        $helper = Mage::helper('mailchimp');
        $allApiKeys = $helper->getAllApiKeys();
        foreach($allApiKeys as $apiKey) {
            try {
                $api = $helper->getApiByKey($apiKey);
            } catch(\Ebizmarts_MailChimp_Helper_Data_ApiKeyException $e) {
                continue;
            }
            try {
                $root = $api->getRoot()->info();
                $stores = $api->getEcommerce()->getStores()->get(null, null, null, 100);
            } catch(Exception $e) {
                continue;
            }
            foreach($stores['stores'] as $store) {
                if($store['platform']=='Magento') {
                    try {
                        $list = $api->lists->getLists($store['list_id']);
                    } catch(Exception $e) {
                        continue;
                    }
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
                        ->setCountry($store['address']['country'])
                        ->setCountryCode($store['address']['country_code'])
                        ->setDomain($store['domain'])
                        ->setMcAccountName($root['account_name'])
                        ->setListName(key_exists('name',$list) ? $list['name']: '')
                        ->save();
                }
            }
        }

    }
}
