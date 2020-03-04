<?php
/**
 * mc-magento Magento Component
 *
 * @category  Ebizmarts
 * @package   mc-magento
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @file:     Edit.php
 */
class Ebizmarts_MailChimp_Block_Adminhtml_Mailchimpstores_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        $this->_objectId = 'id';
        $this->_controller = 'adminhtml_mailchimpstores';
        $this->_blockGroup = 'mailchimp';

        parent::__construct();

        $this->removeButton('reset');
        $this->updateButton(
            'delete', null, array(
            'label'     => Mage::helper('adminhtml')->__('Delete Store'),
            'class'     => 'delete',
            'onclick'   => 'deleteMCStoreConfirm(\''
                . Mage::helper('core')->jsQuoteEscape(
                    Mage::helper('adminhtml')->__('Are you sure you want to delete this Mailchimp store?')
                )
                .'\', \''
                . $this->getDeleteUrl()
                . '\')',
            'sort_order' => 0
            )
        );

        $scopeArray = $this->getScopeArrayIfValueExists();

        if ($scopeArray !== false) {
            $jsCondition = 'true';
        } else {
            $jsCondition = 'false';
        }

        $mcInUseMessage = $this->getMCInUseMessage($scopeArray);
        $this->_formScripts[] = "function deleteMCStoreConfirm(message, url) {
            if ($jsCondition) {
                if (confirm(message)) {
                    deleteConfirm('$mcInUseMessage', url);
                }
            } else {
                deleteConfirm(message, url);
            }
        }";
    }

    public function getStoreId()
    {
        return Mage::registry('current_store')->getId();
    }

    public function getHeaderText()
    {
        if (Mage::registry('current_mailchimpstore')->getId()) {
            return $this->escapeHtml(Mage::registry('current_mailchimpstore')->getName());
        } else {
            return Mage::helper('mailchimp')->__('New Store');
        }
    }

    protected function _prepareLayout()
    {
        $headBlock = Mage::app()->getLayout()->getBlock('head');
        $headBlock->addJs('ebizmarts/mailchimp/editstores.js');
        return parent::_prepareLayout();
    }

    /**
     * @param $scope
     * @return string
     * @throws Mage_Core_Exception
     * @throws Mage_Core_Model_Store_Exception
     */
    protected function getMCInUseMessage($scope)
    {
        $helper = $this->makeHelper();
        if ($scope !== false) {
            $scopeName = $helper->getScopeName($scope);
            $message = $helper->__(
                "This store is currently in use for this Magento store at %s scope. Do you want to proceed anyways?",
                $scopeName
            );
        } else {
            $message = $helper->__(
                "This store is currently in use for this Magento store. Do you want to proceed anyways?"
            );
        }

        return $message;
    }

    /**
     * @return array
     */
    protected function getScopeArrayIfValueExists()
    {
        $helper = $this->makeHelper();
        $currentMCStoreId = Mage::registry('current_mailchimpstore')->getStoreid();
        $keyIfExist = $helper->getScopeByMailChimpStoreId($currentMCStoreId);

        if ($keyIfExist === null) {
            $keyIfExist = false;
        }

        return $keyIfExist;
    }

    /**
     * @return Ebizmarts_MailChimp_Helper_Data
     */
    protected function makeHelper()
    {
        return Mage::helper('mailchimp');
    }
}
