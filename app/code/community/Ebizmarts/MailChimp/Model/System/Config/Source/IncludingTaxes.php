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
        $helper = $this->getHelper();
        return array(
            array('value' => 0, 'label' => $helper->__('No')),
            array('value' => 1, 'label' => $helper->__('Yes'))
        );
    }

    /**
     * @return Mage_Core_Helper_Abstract
     */
    protected function getHelper($type='mailchimp')
    {
        return Mage::helper($type);
    }
}
