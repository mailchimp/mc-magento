<?php
/**
 * Cron Process available count limits options source
 *
 * @category   Ebizmarts
 * @package    Ebizmarts_MageMonkey
 * @author     Ebizmarts Team <info@ebizmarts.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 */
class Ebizmarts_MailChimp_Model_System_Config_Source_BatchLimit
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 50, 'label' => Mage::helper('mailchimp')->__('50')),
            array('value' => 100, 'label' => Mage::helper('mailchimp')->__('100')),
            array('value' => 200, 'label' => Mage::helper('mailchimp')->__('200')),
            array('value' => 500, 'label' => Mage::helper('mailchimp')->__('500')),
            array('value' => 1000, 'label' => Mage::helper('mailchimp')->__('1000')),
            array('value' => 5000, 'label' => Mage::helper('mailchimp')->__('5000'))
        );
    }
}