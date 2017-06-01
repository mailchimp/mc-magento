<?php
/**
 * mc-magento Magento Component
 *
 * @category  Ebizmarts
 * @package   mc-magento
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @file:     MergevarsController.php
 */
class Ebizmarts_MailChimp_Adminhtml_MergevarsController extends Mage_Adminhtml_Controller_Action
{

    public function addmergevarAction()
    {

        $this->loadLayout();
        $this->renderLayout();
    }

    public function saveaddAction()
    {
        $postData = $this->getRequest()->getPost('mergevar', array());
        $label = $postData['label'];
        $value = $postData['value'];
        $fieldType = $postData['fieldtype'];
        $scopeArray = explode('-', Mage::helper('mailchimp')->getScopeString());
        $blankSpacesAmount = (count(explode(' ', $value)) - 1);
        
        if (is_numeric($value)) {
            Mage::getSingleton('adminhtml/session')->addError($this->__('There was an error processing the new field. MailChimp tag value can not be numeric.'));
        } elseif (Mage::helper('mailchimp')->customMergeFieldAlreadyExists($value, $scopeArray[1], $scopeArray[0])) {
            Mage::getSingleton('adminhtml/session')->addError($this->__('There was an error processing the new field. MailChimp tag value already exists.'));
        } elseif ($blankSpacesAmount > 0) {
            Mage::getSingleton('adminhtml/session')->addError($this->__('There was an error processing the new field. MailChimp tag value can not contain blank spaces.'));
        } else {
            $customMergeFields = Mage::helper('mailchimp')->getCustomMergeFields($scopeArray[1], $scopeArray[0]);
            $customMergeFields[] = array('label' => $label, 'value' => $value, 'field_type' => $fieldType);
            $configValues = array(array(Ebizmarts_MailChimp_Model_Config::GENERAL_CUSTOM_MAP_FIELDS, serialize($customMergeFields)));
            Mage::helper('mailchimp')->saveMailchimpConfig($configValues, $scopeArray[1], $scopeArray[0]);
            Mage::getSingleton('core/session')->setMailChimpValue($value);
            Mage::getSingleton('core/session')->setMailChimpLabel($label);
            Mage::getSingleton('adminhtml/session')->addSuccess($this->__('The custom value was added successfully.'));
        }

        $this->_redirect("*/*/addmergevar");

    }

    protected function _isAllowed()
    {
        switch ($this->getRequest()->getActionName()) {
        case 'addmergevar':
        case 'saveadd':
            $acl = 'system/config/mailchimp';
            break;
        }

        return Mage::getSingleton('admin/session')->isAllowed($acl);
    }
}