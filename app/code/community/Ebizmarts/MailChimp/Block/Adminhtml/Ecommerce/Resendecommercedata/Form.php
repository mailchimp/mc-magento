<?php

class Ebizmarts_MailChimp_Block_Adminhtml_Ecommerce_Resendecommercedata_Form extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form(array('id' => 'edit_form', 'action' => $this->getUrl('*/*/resendEcommerceData'), 'method' => 'post'));
        $fieldset = $form->addFieldset('base_fieldset', array('legend' => Mage::helper('mailchimp')->__('Resend Data')));


        $fieldset->addField(
            'products', 'checkbox', array(
                'name'  => 'filter[]',
                'label' => "Products",
                'id'    => 'products',
                'title' => "Products",
                'value' => 'PRO',
                'required' => false,
                'checked'  => '1',
            )
        );
        $fieldset->addField(
            'customers', 'checkbox', array(
                'name' => 'filter[]',
                'label' => "Customers",
                'id' => 'customers',
                'title' => 'Customers',
                'value' => 'CUS',
                'required' => false,
                'checked'  => '1',
            )
        );
        $fieldset->addField(
            'orders', 'checkbox', array(
                'name' => 'filter[]',
                'label' => "Orders",
                'id' => 'orders',
                'title' => 'Orders',
                'value' => 'ORD',
                'required' => false,
                'checked'  => '1',
            )
        );
        $fieldset->addField(
            'carts', 'checkbox', array(
                'name' => 'filter[]',
                'label' => "Carts",
                'id' => 'carts',
                'title' => 'Carts',
                'value' => 'QUO',
                'required' => false,
                'checked'  => '1',
            )
        );
        $fieldset->addField(
            'promo', 'checkbox', array(
                'name' => 'filter[]',
                'label' => "Promo Rules",
                'id' => 'promo',
                'title' => 'Promo Rules',
                'value' => 'PCD, PRL',
                'required' => false,
                'checked'  => '1',
            )
        );

        $form->setUseContainer(true);
        $this->setForm($form);
        return parent::_prepareForm();
    }
}
