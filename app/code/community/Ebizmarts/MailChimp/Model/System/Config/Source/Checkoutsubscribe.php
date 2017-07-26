<?php

/**
 * Checkout subscribe available status options source
 *
 * @category   Ebizmarts
 * @package    Ebizmarts_MailChimp
 * @author     Ebizmarts Team <info@ebizmarts.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 */
class Ebizmarts_MailChimp_Model_System_Config_Source_Checkoutsubscribe
{
    const DISABLED = 0;
    const CHECKED_BY_DEFAULT = 1;
    const NOT_CHECKED_BY_DEFAULT = 2;
    const FORCE_HIDDEN = 3;
    const FORCE_VISIBLE = 4;

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $helper = Mage::helper('mailchimp');
        return array(
            array('value' => self::CHECKED_BY_DEFAULT, 'label' => $helper->__('Enabled - Checked by default')),
            array('value' => self::NOT_CHECKED_BY_DEFAULT, 'label' => $helper->__('Enabled - Not Checked by default')),
            array('value' => self::FORCE_HIDDEN, 'label' => $helper->__('Enabled - Force subscription hidden')),
            array('value' => self::FORCE_VISIBLE, 'label' => $helper->__('Enabled - Force subscription')),
            array('value' => self::DISABLED, 'label' => $helper->__('-- Disabled --'))
        );
    }
}