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
        $isPost = $this->getRequest()->getPost();
        if($isPost) {
            $this->_updateMailchimp($isPost);
        }
        $this->_redirect('*/*/index');
    }
    protected function _updateMailchimp($formData)
    {
        $api = Mage::helper('mailchimp')->getApiByKey($formData['apikey']);
        $address = [];
        $address['address1']    = $formData['address_address_one'];
        $address['address2']    = $formData['address_address_two'];
        $address['city']        = $formData['address_city'];
        $address['province']    = '';
        $address['province_code'] = '';
        $address['postal_code'] = $formData['address_postal_code'];
        $address['country']     = '';
        $address['country_code'] = $formData['address_country_code'];
        // *****
        $emailAddress   = $formData['email_address'];
        $currencyCode   = $formData['currency_code'];
        $primaryLocale  = $formData['primary_locale'];
        $timeZone       = $formData['timezone'];
        $phone          = $formData['phone'];
        $name           = $formData['name'];
        $domain         = $formData['domain'];
        $storeId = isset($formData['storeid']) ? $formData['storeid'] : null;
        $is_sync = null;

        if ($storeId) {
            $api->ecommerce->stores->edit(
                $storeId,
                $name,
                'Magento',
                $domain,
                $is_sync,
                $emailAddress,
                $currencyCode,
                null,
                $primaryLocale,
                $timeZone,
                $phone,
                $address
            );
        } else {
            $date =  Mage::helper('mailchimp')->getDateMicrotime();
            $mailchimpStoreId = md5($name. '_' . $date);
            $is_sync = true;
            $ret =$api->ecommerce->stores->add(
                $mailchimpStoreId,
                $formData['listid'],
                $name,
                $currencyCode,
                $is_sync,
                'Magento',
                $domain,
                $emailAddress,
                null,
                $primaryLocale,
                $timeZone,
                $phone,
                $address
            );
            $formData['storeid'] = $mailchimpStoreId;
        }
        return $formData['storeid'];

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
                        ->setAddressCountry($store['address']['country'])
                        ->setAddressCountryCode($store['address']['country_code'])
                        ->setDomain($store['domain'])
                        ->setMcAccountName($root['account_name'])
                        ->setListName(key_exists('name',$list) ? $list['name']: '')
                        ->save();
                }
            }
        }

    }
    public function getstoresAction()
    {
        $apiKey = $this->getRequest()->getParam('apikey');
        $helper = Mage::helper('mailchimp');
        $data = array();
        try {
            $api = $helper->getApiByKey($apiKey);
            $lists = $api->lists->getLists();
            $data = array();
            foreach ($lists['lists'] as $list) {
                $data[$list['id']] = array('id' => $list['id'], 'name' => $list['name']);
            }
            $jsonData = json_encode($data);
        } catch(Exception $e) {

        }
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody($jsonData);
    }
    public function deleteAction()
    {
        $id = $this->getRequest()->getParam('id');
        $store = Mage::getModel('mailchimp/stores')->load($id);
        if($store->getId()) {
            try {
                $api = Mage::helper('mailchimp')->getApiByKey($store->getApikey());
                $api->ecommerce->stores->delete($store->getStoreid());
            } catch(Exception $e) {

            }
        }
        $this->_redirect('*/*/index');
    }
}
