<?php

class Ebizmarts_MailChimp_Block_Adminhtml_Ecommerce_Resendecommercedata_Form extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form(array('id' => 'edit_form', 'action' => $this->getUrl('*/*/resend'), 'method' => 'post'));
        $fieldset = $form->addFieldset('base_fieldset', array('legend' => Mage::helper('mailchimp')->__('Resend Data')));


        $fieldset->addField(
            'products', 'checkbox', array(
                'name'  => 'filter[]',
                'label' => "Products",
                'id'    => 'products',
                'title' => "Products",
                'value' => 'products',
                'required' => false
            )
        );
        $fieldset->addField(
            'customers', 'checkbox', array(
                'name' => 'filter[]',
                'label' => "Customers",
                'id' => 'customers',
                'title' => 'Customers',
                'value' => 'customers',
                'required' => false
            )
        );
        $fieldset->addField(
            'orders', 'checkbox', array(
                'name' => 'filter[]',
                'label' => "Orders",
                'id' => 'orders',
                'title' => 'Orders',
                'value' => 'orders',
                'required' => false
            )
        );
        $fieldset->addField(
            'carts', 'checkbox', array(
                'name' => 'filter[]',
                'label' => "Carts",
                'id' => 'carts',
                'title' => 'Carts',
                'value' => 'carts',
                'required' => false
            )
        );
        $fieldset->addField(
            'promo', 'checkbox', array(
                'name' => 'filter[]',
                'label' => "Promo Codes",
                'id' => 'promo',
                'title' => 'Promo Codes',
                'value' => 'promo',
                'required' => false
            )
        );

        $form->setUseContainer(true);
        $this->setForm($form);
        return parent::_prepareForm();
    }
}
