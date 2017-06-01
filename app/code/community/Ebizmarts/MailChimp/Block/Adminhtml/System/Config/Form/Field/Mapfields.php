<?php

/**
 * MailChimp For Magento
 *
 * @category  Ebizmarts_MailChimp
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     6/28/16 3:55 PM
 * @file:     Mapfields.php
 */
class Ebizmarts_MailChimp_Block_Adminhtml_System_Config_Form_Field_Mapfields extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    protected $_customerAttributes;

    public function __construct()
    {
        $this->addColumn(
            'mailchimp', array(
            'label' => Mage::helper('mailchimp')->__('MailChimp'),
            'style' => 'width:120px',
            )
        );
        $this->addColumn(
            'magento', array(
            'label' => Mage::helper('mailchimp')->__('Customer'),
            'style' => 'width:120px',
            )
        );
        $this->_addAfter = false;
        parent::__construct();
        $this->setTemplate('ebizmarts/mailchimp/system/config/form/field/array_dropdown.phtml');

        $this->_customerAttributes = array();
        $attrSetId = Mage::getResourceModel('eav/entity_attribute_collection')
            ->setEntityTypeFilter(1)
            ->addSetInfo()
            ->getData();

        foreach ($attrSetId as $option) {
            if ($option['frontend_label']) {
                $this->_customerAttributes[$option['attribute_id']] = $option['frontend_label'];
            }
        }

        $scopeArray = explode('-', Mage::helper('mailchimp')->getScopeString());
        $mapFields = Mage::helper('mailchimp')->getCustomMergeFieldsSerialized($scopeArray[1], $scopeArray[0]);
        $customFieldTypes = unserialize($mapFields);
        if(is_array($customFieldTypes)) {
            foreach ($customFieldTypes as $customFieldType) {
                $label = $customFieldType['label'];
                $value = $customFieldType['value'];
                $this->_customerAttributes[$value] = $label;
            }
        }

        ksort($this->_customerAttributes);
    }

    protected function _renderCellTemplate($columnName)
    {
        if (empty($this->_columns[$columnName])) {
            throw new Exception('Wrong column name specified.');
        }

        $column = $this->_columns[$columnName];
        $inputName = $this->getElement()->getName() . '[#{_id}][' . $columnName . ']';

        if ($columnName == 'magento') {
            $rendered = '<select name="' . $inputName . '">';
            foreach ($this->_customerAttributes as $att => $name) {
                $rendered .= '<option value="' . $att . '">' . $name . '</option>';
            }

            $rendered .= '</select>';
        } else {
            return '<input type="text" name="' . $inputName . '" value="#{' . $columnName . '}" ' . ($column['size'] ? 'size="' . $column['size'] . '"' : '') . '/>';
        }

        return $rendered;
    }
    
    protected function _getMailChimpValue()
    {
        return Mage::getSingleton('core/session')->getMailchimpValue();
    }

    protected function _getMailChimpLabel()
    {
        return Mage::getSingleton('core/session')->getMailchimpLabel();
    }
}