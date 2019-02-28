<?php
/**
 * @category   Ebizmarts
 * @package    Ebizmarts_MailChimp
 * @author     Ebizmarts Team <info@ebizmarts.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 */

class Ebizmarts_MailChimp_Model_System_Config_Source_IncludingTaxes
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 0, 'label' => Mage::helper('mailchimp')->__('No')),
            array('value' => 1, 'label' => Mage::helper('mailchimp')->__('Yes'))
        );
    }
}