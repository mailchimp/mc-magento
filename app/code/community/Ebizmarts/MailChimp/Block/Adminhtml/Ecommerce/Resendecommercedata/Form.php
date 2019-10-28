<?php

class Ebizmarts_MailChimp_Block_Adminhtml_Ecommerce_Resendecommercedata_Form
    extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $request = $this->getRequest();
        $scope = $request->getParam('scope');
        $scopeId = $request->getParam('scope_id');

        $form = new Varien_Data_Form(
            array(
                'id' => 'edit_form',
                'action' => $this->getUrl('*/*/resendEcommerceData'),
                'method' => 'post'
            )
        );
        $fieldset = $form->addFieldset(
            'base_fieldset',
            array(
                'legend' => Mage::helper('mailchimp')->__('Resend Data')
            )
        );


        $fieldset->addField(
            'products',
            'checkbox',
            array(
                'name'  => 'filter[]',
                'label' => "Products",
                'id'    => 'products',
                'title' => "Products",
                'value' =>  Ebizmarts_MailChimp_Model_Config::IS_PRODUCT,
                'required' => false,
                'checked'  => '1',
            )
        );
        $fieldset->addField(
            'customers',
            'checkbox',
            array(
                'name' => 'filter[]',
                'label' => "Customers",
                'id' => 'customers',
                'title' => 'Customers',
                'value' => Ebizmarts_MailChimp_Model_Config::IS_CUSTOMER,
                'required' => false,
                'checked'  => '1',
            )
        );
        $fieldset->addField(
            'orders',
            'checkbox',
            array(
                'name' => 'filter[]',
                'label' => "Orders",
                'id' => 'orders',
                'title' => 'Orders',
                'value' => Ebizmarts_MailChimp_Model_Config::IS_ORDER,
                'required' => false,
                'checked'  => '1',
            )
        );
        $fieldset->addField(
            'carts',
            'checkbox',
            array(
                'name' => 'filter[]',
                'label' => "Carts",
                'id' => 'carts',
                'title' => 'Carts',
                'value' => Ebizmarts_MailChimp_Model_Config::IS_QUOTE,
                'required' => false,
                'checked'  => '1',
            )
        );
        $fieldset->addField(
            'promo',
            'checkbox',
            array(
                'name' => 'filter[]',
                'label' => "Promo Rules",
                'id' => 'promo',
                'title' => 'Promo Rules',
                'value' => Ebizmarts_MailChimp_Model_Config::IS_PROMO_CODE. ', '
                    . Ebizmarts_MailChimp_Model_Config::IS_PROMO_RULE,
                'required' => false,
                'checked'  => '1',
            )
        );
        $fieldset->addField(
            'scope',
            'hidden',
            array(
                'name' => 'scope',
                'id' => 'scope',
                'value' => $scope
            )
        );
        $fieldset->addField(
            'scopeId',
            'hidden',
            array(
                'name' => 'scope_id',
                'id' => 'scopeId',
                'value' => $scopeId
            )
        );


        $form->setUseContainer(true);
        $this->setForm($form);
        return parent::_prepareForm();
    }
}
