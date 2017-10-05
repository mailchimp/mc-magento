<?php
/**
 * Cron Process available count limits options source
 *
 * @category   Ebizmarts
 * @package    Ebizmarts_MailChimp
 * @author     Ebizmarts Team <info@ebizmarts.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 */
class Ebizmarts_MailChimp_Model_System_Config_Source_ImageSize
{
    const BASE = 0;
    const SMALL = 1;
    const THUMBNAIL = 2;


    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $helper = Mage::helper('mailchimp');
        return array(
            array('value' => self::BASE, 'label' => $helper->__('Base')),
            array('value' => self::SMALL, 'label' => $helper->__('Small')),
            array('value' => self::THUMBNAIL, 'label' => $helper->__('Thumbnail'))

        );
    }
}
