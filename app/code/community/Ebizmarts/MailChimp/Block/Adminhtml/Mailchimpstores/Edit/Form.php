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
        $form = new Varien_Data_Form(array(
            'id'        => 'edit_form',
            'action'    => $this->getUrl('*/*/save'),
            'method'    => 'post',
        ));

        $store = Mage::registry('current_mailchimpstore');

        if ($store->getStoreid()) {
            $form->addField('storeid', 'hidden', array(
                'name' => 'storeid',
            ));
            $form->addField('apikey', 'hidden', array(
                'name' => 'apikey'
            ));
            $form->setValues($store->getData());
        }
        $fieldset   = $form->addFieldset('base_fieldset', array(
            'legend'    => Mage::helper('mailchimp')->__('Store Information'),
            'class'     => 'fieldset',
        ));
        if(!$store->getId()) {
            $stores = Mage::app()->getStores();
            $apikeys = array();
            foreach ($stores as $s) {
                $apikey = Mage::helper('mailchimp')->getConfigValueForScope(Ebizmarts_MailChimp_Model_Config::GENERAL_APIKEY,$s);
                if(!array_key_exists($apikey,$apikeys)) {
                    $apikeys[$apikey] = $apikey;
                }
            }
            $apikeyField =$fieldset->addField('apikey', 'select', array(
                'label'     => Mage::helper('mailchimp')->__('Api Key'),
                'title'     => Mage::helper('mailchimp')->__('Api Key'),
                'name'      => 'apikey',
                'required'  => true,
                'options'   => $apikeys,
            ));
            $getStoresUrl = Mage::helper('adminhtml')->getUrl('adminhtml/mailchimpstores/getstores');
            $apikeyField->setAfterElementHtml("<script>var GET_STORES_URL = '".$getStoresUrl."';</script>");

            $fieldset->addField('listid', 'select', array(
                'name'      => 'listid',
                'label'     => Mage::helper('mailchimp')->__('List'),
                'title'     => Mage::helper('mailchimp')->__('List'),
                'required'  => true,
                'options'   => array()
            ));
        }
        $fieldset->addField('name', 'text', array(
            'name'      => 'name',
            'label'     => Mage::helper('mailchimp')->__('Store Name'),
            'title'     => Mage::helper('mailchimp')->__('Store Name'),
            'required'  => true,
        ));
        $fieldset->addField('domain', 'text',array(
           'name'   => 'domain',
           'label'  => Mage::helper('mailchimp')->__('Domain'),
           'title'  => Mage::helper('mailchimp')->__('Domain'),
            'required' => true
        ));
        $fieldset->addField('email_address', 'text', array(
           'name'   => 'email_address',
           'label'  => Mage::helper('mailchimp')->__('Email'),
           'title'  => Mage::helper('mailchimp')->__('Email'),
           'required' => true
        ));
        $currencies = Mage::getModel('adminhtml/system_config_source_currency')->toOptionArray(false);
        $currencyArray = array();
        foreach ($currencies as $c) {
            $currencyArray[$c['value']] = $c['label'];
        }
        $fieldset->addField('currency_code', 'select', array(
            'label'     => Mage::helper('mailchimp')->__('Currency'),
            'title'     => Mage::helper('mailchimp')->__('Currency'),
            'name'      => 'currency_code',
            'required'  => true,
            'options'   => $currencyArray,
        ));
        $locales = Mage::getModel('adminhtml/system_config_source_locale')->toOptionArray();
        $localeArray = array();
        foreach ($locales as $c) {
            $localeArray[$c['value']] = $c['label'];
        }
        $fieldset->addField('primary_locale', 'select', array(
            'label'     => Mage::helper('mailchimp')->__('Locale'),
            'title'     => Mage::helper('mailchimp')->__('Locale'),
            'name'      => 'primary_locale',
            'required'  => true,
            'options'   => $localeArray,
        ));
        $timeszones = Mage::getModel('adminhtml/system_config_source_locale_timezone')->toOptionArray();
        $timezoneArray = array();
        foreach ($timeszones as $c) {
            $timezoneArray[$c['value']] = $c['label'];
        }
        $fieldset->addField('timezone', 'select', array(
            'label'     => Mage::helper('mailchimp')->__('Time Zone'),
            'title'     => Mage::helper('mailchimp')->__('Time Zone'),
            'name'      => 'timezone',
            'required'  => true,
            'options'   => $timezoneArray,
        ));

        $fieldset->addField('phone', 'text', array(
            'name'   => 'phone',
            'label'  => Mage::helper('mailchimp')->__('Phone'),
            'title'  => Mage::helper('mailchimp')->__('Phone'),
            'required' => true
        ));
        $fieldset->addField('address_address_one', 'text', array(
            'name'   => 'address_address_one',
            'label'  => Mage::helper('mailchimp')->__('Street'),
            'title'  => Mage::helper('mailchimp')->__('Street'),
            'required' => true
        ));

        $fieldset->addField('address_address_two', 'text', array(
            'name'   => 'address_address_two',
            'label'  => Mage::helper('mailchimp')->__('Street'),
            'title'  => Mage::helper('mailchimp')->__('Street'),
            'required' => false
        ));
        $fieldset->addField('address_city', 'text', array(
            'name'   => 'address_city',
            'label'  => Mage::helper('mailchimp')->__('City'),
            'title'  => Mage::helper('mailchimp')->__('City'),
            'required' => true
        ));
        $fieldset->addField('address_postal_code', 'text', array(
            'name'   => 'address_postal_code',
            'label'  => Mage::helper('mailchimp')->__('Postal Code'),
            'title'  => Mage::helper('mailchimp')->__('Postal Code'),
            'required' => true
        ));
        $countries = Mage::getModel('adminhtml/system_config_source_country')->toOptionArray();
        $countryArray = array();
        foreach ($countries as $c) {
            $countryArray[$c['value']] = $c['label'];
        }
        $fieldset->addField('address_country_code', 'select', array(
            'label'     => Mage::helper('mailchimp')->__('Country'),
            'title'     => Mage::helper('mailchimp')->__('Country'),
            'name'      => 'address_country_code',
            'required'  => true,
            'options'   => $countryArray,
        ));

        $form->setValues($store->getData());

        $form->setUseContainer(true);
        $this->setForm($form);
        return parent::_prepareForm();
    }
}
