<?php
/**
 * Created by PhpStorm.
 * User: keller
 * Date: 9/21/18
 * Time: 4:15 PM
 */

class Ebizmarts_MailChimp_Block_Adminhtml_System_Config_CreateWebhook
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('ebizmarts/mailchimp/system/config/createwebhook.phtml');
    }

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        return $this->_toHtml();
    }

    public function getButtonHtml()
    {
        $helper = $this->makeHelper();
            $button = $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(
                    array(
                        'id' => 'createwebhook_button',
                        'label' => $helper->__('Create Webhook'),
                        'onclick' => 'javascript:createwebhook(); return false;',
                        'title' => $helper->__('Create the webhook for the list in current scope')
                    )
                );

            return $button->toHtml();
    }

    /**
     * @return string
     */
    public function getSuccessMessage()
    {
        $helper = $this->makeHelper();
        $message = 'The webhook was created successfully.';
        return $helper->__($message);
    }

    /**
     * @return string
     */
    public function getFailureMessage()
    {
        $helper = $this->makeHelper();
        $message = 'Something went wrong.';
        return $helper->__($message);
    }

    public function getAjaxCheckUrl()
    {
        $helper = $this->makeHelper();
        $scopeArray = $helper->getCurrentScope();
        return Mage::helper('adminhtml')->getUrl('adminhtml/mailchimp/createWebhook', $scopeArray);
    }

    /**
     * @return Ebizmarts_MailChimp_Helper_Data
     */
    protected function makeHelper()
    {
        return $this->helper('mailchimp');
    }
}
