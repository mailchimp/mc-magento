<?php
/**
 * mc-magento Magento Component
 *
 * @category  Ebizmarts
 * @package   mc-magento
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     5/27/16 1:16 PM
 * @file:     ResetEcommerceData.php
 */
class Ebizmarts_MailChimp_Block_Adminhtml_System_Config_ResetEcommerceData
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('ebizmarts/mailchimp/system/config/resetecommercedata.phtml');
    }

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        return $this->_toHtml();
    }

    public function getButtonHtml()
    {
        $helper = $this->makeHelper();
        $scopeArray = $helper->getCurrentScope();
        if ($helper->getIfConfigExistsForScope(Ebizmarts_MailChimp_Model_Config::GENERAL_MCSTOREID, $scopeArray['scope_id'], $scopeArray['scope'])) {
            $label = $helper->__('Reset MailChimp Store');
            $button = $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(
                    array(
                        'id' => 'resetecommercedata_button',
                        'label' => $label,
                        'onclick' => 'javascript:resetecommerce(); return false;',
                        'title' => $helper->__('Re-create MailChimp store for current scope')
                    )
                );

            return $button->toHtml();
        }
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        $message = 'Are you sure you want to delete the current MailChimp store for this scope and create a new one?\nAutomations created for this store will need to be re-created.';
        return $this->helper('mailchimp')->__($message);
    }

    public function getAjaxCheckUrl()
    {
        $helper = $this->makeHelper();
        $scopeArray = $helper->getCurrentScope();
        return Mage::helper('adminhtml')->getUrl('adminhtml/ecommerce/resetEcommerceData', $scopeArray);
    }

    /**
     * @return Ebizmarts_MailChimp_Helper_Data
     */
    protected function makeHelper()
    {
        return $this->helper('mailchimp');
    }

}
