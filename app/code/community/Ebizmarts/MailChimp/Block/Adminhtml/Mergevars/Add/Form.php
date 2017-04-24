<?php
/**
 * Author : Ebizmarts <info@ebizmarts.com>
 * Date   : 4/24/13
 * Time   : 4:00 PM
 * File   : Form.php
 * Module : Ebizmarts_MailChimp
 */
class Ebizmarts_MailChimp_Block_Adminhtml_Mergevars_Add_Form extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm() 
    {
        $form = new Varien_Data_Form(array('id' => 'edit_form', 'action' => $this->getUrl('*/*/saveadd'), 'method' => 'post'));
        $fieldset = $form->addFieldset('base_fieldset', array('legend' => Mage::helper('mailchimp')->__('Mergevars Data')));
        

        $fieldset->addField(
            'mergevar_label', 'text', array(
            'name'  => 'mergevar[label]',
            'label' => Mage::helper('mailchimp')->__('Merge Field Name'),
            'id'    => 'mergevar_label',
            'title' => Mage::helper('mailchimp')->__('Merge Field Name'),
            'required' => true
            )
        );
        $fieldset->addField(
            'mergevar_fieldtype', 'select', array(
            'name' => 'mergevar[fieldtype]',
            'label' => Mage::helper('mailchimp')->__('Merge Field Type'),
            'id' => 'mergevar_fieldtype',
            'values' => Mage::getSingleton('mailchimp/system_config_source_fieldtype')->getFieldTypes(),
            'required' => true
            )
        );

        $fieldset->addField(
            'mergevar_value', 'text', array(
            'name'  => 'mergevar[value]',
            'label' => Mage::helper('mailchimp')->__('Merge Field Tag'),
            'id'    => 'mergevar_value',
            'title' => Mage::helper('mailchimp')->__('Merge Field Tag'),
            'note'     => 'This value will be used when adding the logic in the Observer. Blank spaces are not allowed.',
            'required' => true
            )
        );



        $form->setUseContainer(true);
        $this->setForm($form);
        return parent::_prepareForm();
    }
}