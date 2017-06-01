<?php
/**
 * mc-magento Magento Component
 *
 * @category  Ebizmarts
 * @package   mc-magento
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     5/27/16 1:02 PM
 * @file:     ResetProducts.php
 */
class Ebizmarts_MailChimp_Block_Adminhtml_System_Config_ResetList
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('ebizmarts/mailchimp/system/config/resetlist.phtml');
    }

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        return $this->_toHtml();
    }

    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setData(
                array(
                'id' => 'changelist_button',
                'label' => $this->helper('mailchimp')->__('Change List'),
                'onclick' => 'javascript:resetlist(); return false;',
                'style' => 'display:none'
                )
            );

        return $button->toHtml();
    }
    public function getAjaxCheckUrl()
    {
        return Mage::helper('adminhtml')->getUrl('adminhtml/ecommerce/resetList');
    }
    public function getMessage()
    {
        return __(Mage::getStoreConfig(Ebizmarts_MailChimp_Model_Config::WARNING_MESSAGE));
    }
    public function getPopupMessage()
    {
        return __(Mage::getStoreConfig(Ebizmarts_MailChimp_Model_Config::POPUP_MESSAGE));
    }

}