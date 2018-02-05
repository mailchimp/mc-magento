<?php
/**
 * Cron Process available count limits options source
 *
 * @category   Ebizmarts
 * @package    Ebizmarts_MailChimp
 * @author     Ebizmarts Team <info@ebizmarts.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 */
class Ebizmarts_MailChimp_Model_System_Config_Source_OrderGrid
{
    const NONE = 0;
    const ICON = 1;
    const SYNCED = 2;
    const BOTH = 3;


    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $helper = Mage::helper('mailchimp');
        return array(
            array('value' => self::NONE, 'label' => $helper->__('None')),
            array('value' => self::ICON, 'label' => $helper->__('Icon for MailChimp orders')),
            array('value' => self::SYNCED, 'label' => $helper->__('If orders are synced to MailChimp')),
            array('value' => self::BOTH, 'label' => $helper->__('Both'))

        );
    }
}
