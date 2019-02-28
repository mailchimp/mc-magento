<?php
/**
 * @category   Ebizmarts
 * @package    Ebizmarts_MageMonkey
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
            array('value' => 'yes', 'label' => Mage::helper('mailchimp')->__('Yes')),
            array('value' => 'no', 'label' => Mage::helper('mailchimp')->__('No'))
        );
    }
}