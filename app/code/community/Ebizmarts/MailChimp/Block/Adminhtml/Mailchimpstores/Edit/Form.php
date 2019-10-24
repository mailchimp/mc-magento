<?php
/**
 * mc-magento Magento Component
 *
 * @category  Ebizmarts
 * @package   mc-magento
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @file:     Form.php
 */

class Ebizmarts_MailChimp_Block_Adminhtml_Mailchimpstores_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{

    protected function _prepareForm()
    {
        $helper = $this->makeHelper();

        $form = new Varien_Data_Form(
            array(
            'id'        => 'edit_form',
            'action'    => $this->getUrl('*/*/save'),
            'method'    => 'post',
            )
        );

        $store = Mage::registry('current_mailchimpstore');

        if ($store->getStoreid()) {
            $form->addField(
                'storeid', 'hidden', array(
                'name' => 'storeid',
                )
            );
            $form->addField(
                'apikey', 'hidden', array(
                'name' => 'apikey'
                )
            );
            $form->setValues($store->getData());
        }

        $fieldset   = $form->addFieldset(
            'base_fieldset', array(
            'legend'    => $helper->__('Store Information'),
            'class'     => 'fieldset',
            )
        );

        if (!$store->getId()) {
            $stores = Mage::app()->getStores();
            $apikeys = array();

            foreach ($stores as $s) {
                $prefix = '';
                $storeId = $s->getStoreId();

                if (!$helper->ping($storeId)) {
                    $prefix = '[Invalid]: ';
                }

                $apikey = $helper->getApiKey($storeId);

                if (!array_key_exists($apikey, $apikeys)) {
                    $encryptedApiKey = $helper->encryptData($apikey);
                    $apikeys[$encryptedApiKey] = $helper->mask($apikey, $prefix);
                }
            }

            $apikeyField = $fieldset->addField(
                'apikey', 'select', array(
                'label'     => $helper->__('Api Key'),
                'title'     => $helper->__('Api Key'),
                'name'      => 'apikey',
                'required'  => true,
                'options'   => $apikeys,
                )
            );

            $getStoresUrl = Mage::helper('adminhtml')->getUrl('adminhtml/mailchimpstores/getstores');
            $apikeyField->setAfterElementHtml("<script>var GET_STORES_URL = '".$getStoresUrl."';</script>");

            $fieldset->addField(
                'listid', 'select', array(
                'name'      => 'listid',
                'label'     => $helper->__('List'),
                'title'     => $helper->__('List'),
                'required'  => true,
                'options'   => array()
                )
            );
        }

        $fieldset->addField(
            'name', 'text', array(
            'name'      => 'name',
            'label'     => $helper->__('Store Name'),
            'title'     => $helper->__('Store Name'),
            'required'  => true,
            )
        );
        $fieldset->addField(
            'domain', 'text', array(
            'name'   => 'domain',
            'label'  => $helper->__('Domain'),
            'title'  => $helper->__('Domain'),
            'required' => true
            )
        );
        $fieldset->addField(
            'email_address', 'text', array(
            'name'   => 'email_address',
            'label'  => $helper->__('Email'),
            'title'  => $helper->__('Email'),
            'required' => true
            )
        );
        $currencies = Mage::getModel('adminhtml/system_config_source_currency')->toOptionArray(false);
        $currencyArray = array();
        foreach ($currencies as $c) {
            $currencyArray[$c['value']] = $c['label'];
        }

        $fieldset->addField(
            'currency_code', 'select', array(
            'label'     => $helper->__('Currency'),
            'title'     => $helper->__('Currency'),
            'name'      => 'currency_code',
            'required'  => true,
            'options'   => $currencyArray,
            )
        );
        $locales = Mage::getModel('adminhtml/system_config_source_locale')->toOptionArray();
        $localeArray = array();
        foreach ($locales as $c) {
            $localeArray[$c['value']] = $c['label'];
        }

        $fieldset->addField(
            'primary_locale', 'select', array(
            'label'     => $helper->__('Locale'),
            'title'     => $helper->__('Locale'),
            'name'      => 'primary_locale',
            'required'  => true,
            'options'   => $localeArray,
            )
        );
        $timeszones = Mage::getModel('adminhtml/system_config_source_locale_timezone')->toOptionArray();
        $timezoneArray = array();
        foreach ($timeszones as $c) {
            $timezoneArray[$c['value']] = $c['label'];
        }

        $fieldset->addField(
            'timezone', 'select', array(
            'label'     => $helper->__('Time Zone'),
            'title'     => $helper->__('Time Zone'),
            'name'      => 'timezone',
            'required'  => true,
            'options'   => $timezoneArray,
            )
        );

        $fieldset->addField(
            'phone', 'text', array(
            'name'   => 'phone',
            'label'  => $helper->__('Phone'),
            'title'  => $helper->__('Phone'),
            'required' => true
            )
        );
        $fieldset->addField(
            'address_address_one', 'text', array(
            'name'   => 'address_address_one',
            'label'  => $helper->__('Street'),
            'title'  => $helper->__('Street'),
            'required' => true
            )
        );

        $fieldset->addField(
            'address_address_two', 'text', array(
            'name'   => 'address_address_two',
            'label'  => $helper->__('Street'),
            'title'  => $helper->__('Street'),
            'required' => false
            )
        );
        $fieldset->addField(
            'address_city', 'text', array(
            'name'   => 'address_city',
            'label'  => $helper->__('City'),
            'title'  => $helper->__('City'),
            'required' => true
            )
        );
        $fieldset->addField(
            'address_postal_code', 'text', array(
            'name'   => 'address_postal_code',
            'label'  => $helper->__('Postal Code'),
            'title'  => $helper->__('Postal Code'),
            'required' => true
            )
        );
        $countries = Mage::getModel('adminhtml/system_config_source_country')->toOptionArray();
        $countryArray = array();
        foreach ($countries as $c) {
            $countryArray[$c['value']] = $c['label'];
        }

        $fieldset->addField(
            'address_country_code', 'select', array(
            'label'     => $helper->__('Country'),
            'title'     => $helper->__('Country'),
            'name'      => 'address_country_code',
            'required'  => true,
            'options'   => $countryArray,
            )
        );

        $form->setValues($store->getData());

        $form->setUseContainer(true);
        $this->setForm($form);
        return parent::_prepareForm();
    }

    /**
     * @return Ebizmarts_MailChimp_Helper_Data
     */
    protected function makeHelper()
    {
        return Mage::helper('mailchimp');
    }
}
