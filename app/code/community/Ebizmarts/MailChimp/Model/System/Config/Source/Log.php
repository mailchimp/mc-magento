<?php
/**
 * Cron Process available count limits options source
 *
 * @category   Ebizmarts
 * @package    Ebizmarts_MailChimp
 * @author     Ebizmarts Team <info@ebizmarts.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 */
class Ebizmarts_MailChimp_Model_System_Config_Source_Log
{
    const NONE = 0;
    const ERROR_LOG = 1;
    const REQUEST_LOG = 2;
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
            array('value' => self::ERROR_LOG, 'label' => $helper->__('Error logs')),
            array('value' => self::REQUEST_LOG, 'label' => $helper->__('Request logs')),
            array('value' => self::BOTH, 'label' => $helper->__('Both'))

        );
    }
}
