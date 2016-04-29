<?php

class Ebizmarts_MailChimp_Block_Adminhtml_System_Config_Account
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $values = $element->getValues();

        $html = '<ul class="checkboxes">';

        foreach ($values as $dat) {
            $html .= "<li>{$dat['label']}</li>";
        }

        $html .= '</ul>';

        return $html;
    }
}