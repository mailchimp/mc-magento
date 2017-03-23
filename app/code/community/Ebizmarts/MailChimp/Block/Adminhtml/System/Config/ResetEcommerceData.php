<?php
/**
 * mc-magento Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 5/27/16 1:16 PM
 * @file: ResetEcommerceData.php
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
        $scopeArray = explode('-', Mage::helper('mailchimp')->getScopeString());
        if (Mage::helper('mailchimp')->getIfMCStoreIdExistsForScope($scopeArray[1], $scopeArray[0])) {
            $label = $this->helper('mailchimp')->__('Reset Ecommerce Data');
            $button = $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(
                    array(
                        'id' => 'resetecommercedata_button',
                        'label' => $label,
                        'onclick' => 'javascript:resetecommerce(); return false;',
                        'title' => $this->helper('mailchimp')->__('Re-create MailChimp store for current scope')
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
        $scopeString = Mage::helper('mailchimp')->getScopeString();
        return Mage::helper('adminhtml')->getUrl('adminhtml/ecommerce/resetEcommerceData', array('scope' => $scopeString));
    }

}