<?php
/**
 * Author : Ebizmarts <info@ebizmarts.com>
 * File   : Fieldtype.php
 * Module : Ebizmarts_MailChimp
 */
class Ebizmarts_MailChimp_Model_System_Config_Source_Fieldtype
{
    public function getFieldTypes()
    {
        $fieldTypes = array(
            'text' => 'Text',
            'number' => 'Number',
            'radio' => 'Radio Buttons',
            'dropdown' => 'Drop Down',
            'date' => 'Date',
            'birthday' => 'Birthday',
            'address' => 'Address',
            'zip' => 'Zip Code (US Only)',
            'phone' => 'Phone',
            'website' => 'Website',
            'image' => 'Image'
        );
        return $fieldTypes;
    }
}