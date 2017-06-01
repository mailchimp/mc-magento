<?php

/**
 * Account details renderer for configuration settings
 *
 * @category Ebizmarts
 * @package  Ebizmarts_Mandrill
 * @author   Ebizmarts Team <info@ebizmarts.com>
 * @license  http://opensource.org/licenses/osl-3.0.php
 */
class Ebizmarts_MailChimp_Block_Adminhtml_System_Config_Userinfo
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