<?php
/**
 * mc-magento Magento Component
 *
 * @category  Ebizmarts
 * @package   mc-magento
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     30/8/16 1:02 PM
 * @file:     CreateMergeFields.php
 */
class Ebizmarts_MailChimp_Block_Adminhtml_System_Config_CreateMergeFields
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    const CREATE_MERGE_PATH = 'adminhtml/ecommerce/createMergeFields';

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('ebizmarts/mailchimp/system/config/createmergefields.phtml');
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
                'id' => 'createmergefields_button',
                'label' => $this->helper('mailchimp')->__('Create Merge Fields'),
                'onclick' => 'javascript:createMergeFields(); return false;'
                )
            );

        return $button->toHtml();
    }
    public function getAjaxCheckUrl()
    {
        $helper = $this->makeHelper();
        $scopeArray = $helper->getCurrentScope();
        return Mage::helper('adminhtml')->getUrl(self::CREATE_MERGE_PATH, $scopeArray);
    }

    /**
     * @return Ebizmarts_MailChimp_Helper_Data
     */
    protected function makeHelper()
    {
        return Mage::helper('mailchimp');
    }

}
