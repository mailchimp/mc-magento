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
        $this->updateButton('delete', null, array(
            'label'     => Mage::helper('adminhtml')->__('Delete Store'),
            'class'     => 'delete',
            'onclick'   => 'deleteConfirm(\''
                . Mage::helper('core')->jsQuoteEscape(
                    Mage::helper('adminhtml')->__('Are you sure you want to delete this Mailchimp store?')
                )
                .'\', \''
                . $this->getDeleteUrl()
                . '\')',
            //@Todo if store is configured in any scope add one more warning and pass $isconfigured = true for deletion process to remove configured data
        ));
    }

    public function getStoreId()
    {
        return Mage::registry('current_store')->getId();
    }

    public function getHeaderText()
    {
        if (Mage::registry('current_mailchimpstore')->getId()) {
            return $this->escapeHtml(Mage::registry('current_mailchimpstore')->getName());
        }
        else {
            return Mage::helper('mailchimp')->__('New Store');
        }
    }

    protected function _prepareLayout()
    {
        $headBlock = Mage::app()->getLayout()->getBlock('head');
        $headBlock->addJs('ebizmarts/mailchimp/editstores.js');
        return parent::_prepareLayout();
    }
}

