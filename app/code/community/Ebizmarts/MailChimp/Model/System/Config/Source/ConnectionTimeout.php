<?php
/**
 * Cron Process available count limits options source
 *
 * @category   Ebizmarts
 * @package    Ebizmarts_MageMonkey
 * @author     Ebizmarts Team <info@ebizmarts.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 */
class Ebizmarts_MailChimp_Model_System_Config_Source_ConnectionTimeout
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 10, 'label' => Mage::helper('mailchimp')->__('10')),
            array('value' => 20, 'label' => Mage::helper('mailchimp')->__('20')),
            array('value' => 30, 'label' => Mage::helper('mailchimp')->__('30')),
            array('value' => 40, 'label' => Mage::helper('mailchimp')->__('40')),
            array('value' => 50, 'label' => Mage::helper('mailchimp')->__('50')),
            array('value' => 60, 'label' => Mage::helper('mailchimp')->__('60'))
        );
    }
}
