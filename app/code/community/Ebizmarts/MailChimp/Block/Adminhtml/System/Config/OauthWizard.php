<?php
/**
 * MailChimp For Magento
 *
 * @category  Ebizmarts_MailChimp
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     4/29/16 3:55 PM
 * @file:     OauthWizard.php
 */
class Ebizmarts_MailChimp_Block_Adminhtml_System_Config_OauthWizard extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    /**
     * Set template to itself
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate('ebizmarts/mailchimp/system/config/oauth_wizard.phtml');
        }

        return $this;
    }

    /**
     * Unset some non-related element parameters
     *
     * @param  Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Get the button and scripts contents
     *
     * @param  Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $originalData = $element->getOriginalData();

        $label = $originalData['button_label'];

        $this->addData(
            array(
            'button_label' => $this->helper('mailchimp')->__($label),
            'button_url' => $this->helper('mailchimp/oauth2')->authorizeRequestUrl(),
            'html_id' => $element->getHtmlId(),
            )
        );
        return $this->_toHtml();
    }
}
