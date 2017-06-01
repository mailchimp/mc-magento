<?php
/**
 * MailChimp For Magento
 *
 * @category  Ebizmarts_MailChimp
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     4/29/16 3:55 PM
 * @file:     Hint.php
 */
class Ebizmarts_MailChimp_Block_Adminhtml_System_Config_Fieldset_Mailchimp_Hint
    extends Mage_Adminhtml_Block_Abstract
    implements Varien_Data_Form_Element_Renderer_Interface
{
    protected $_template = 'ebizmarts/mailchimp/system/config/fieldset/hint.phtml';

    /**
     * Render fieldset html
     *
     * @param  Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        return $this->toHtml();
    }

    public function getMailChimpVersion()
    {
        return (string)Mage::getConfig()->getNode('modules/Ebizmarts_MailChimp/version');
    }

    public function getMigrationFinishedStatus()
    {
        return Mage::helper('mailchimp')->migrationFinished();
    }
}

