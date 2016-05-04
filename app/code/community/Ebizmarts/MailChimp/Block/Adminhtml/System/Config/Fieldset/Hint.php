<?php
class Ebizmarts_MailChimp_Block_Adminhtml_System_Config_Fieldset_Hint
    extends Mage_Adminhtml_Block_Abstract
    implements Varien_Data_Form_Element_Renderer_Interface
{
    protected $_template = 'mailchimp/system/config/fieldset/hint.phtml';

    /**
     * Render fieldset html
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        return $this->toHtml();
    }

    public function getMailChimpVersion()
    {
        /**
         * testing
         */
        Mage::getModel('mailchimp/api_customers')->SyncBatch(1);

        return (string)Mage::getConfig()->getNode('modules/Ebizmarts_MailChimp/version');
    }
}

