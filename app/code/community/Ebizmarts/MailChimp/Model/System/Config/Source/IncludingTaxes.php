<?php
/**
 * @category   Ebizmarts
 * @package    Ebizmarts_MageMonkey
 * @author     Ebizmarts Team <info@ebizmarts.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 */

class Ebizmarts_MailChimp_Model_System_Config_Source_IncludingTaxes
{
    const YES = 1;
    const NO = 0;

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $helper = Mage::helper('mailchimp');
        return array(
            array('value' => self::YES, 'label' => $helper->__('Yes')),
            array('value' => self::NO, 'label' => $helper->__('No'))
        );
    }
}