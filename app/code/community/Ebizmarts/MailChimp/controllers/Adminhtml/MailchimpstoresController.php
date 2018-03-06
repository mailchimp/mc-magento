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
    public function __construct(Zend_Controller_Request_Abstract $request, Zend_Controller_Response_Abstract $response, array $invokeArgs = array())
    {
        parent::__construct($request, $response, $invokeArgs);
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
            $root = $api->getRoot()->info();
            $stores = $api->getEcommerce()->getStores()->get(null,null,null,100);
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
                        ->setListName($list['name'])
                        ->save();
                }
            }
        }
    }

    public function indexAction()
    {
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
}