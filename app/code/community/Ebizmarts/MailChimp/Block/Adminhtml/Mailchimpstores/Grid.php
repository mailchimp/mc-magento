<?php
/**
 * mc-magento Magento Component
 *
 * @category  Ebizmarts
 * @package   mc-magento
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     3/5/18 1:41 PM
 * @file:     Grid.php
 */
class Ebizmarts_MailChimp_Block_Adminhtml_Mailchimpstores_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('mailchimp_stores_grid');
        $this->setDefaultSort('id');
        $this->setDefaultDir('DESC');
        $this->setUseAjax(true);
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('mailchimp/stores')->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn(
            'mc_account_name',
            array(
                'header' => Mage::helper('mailchimp')->__('MC Account name'),
                'index' => 'mc_account_name',
                'width' => '100px',
                'sortable' => true
            )
        );
        $this->addColumn(
            'name',
            array(
                'header' => Mage::helper('mailchimp')->__('Store Name'),
                'index' => 'name',
                'width' => '100px',
                'sortable' => true
            )
        );
        $this->addColumn(
            'list_name',
            array(
                'header' => Mage::helper('mailchimp')->__('List Name'),
                'index' => 'list_name',
                'sortable' => false
            )
        );
        $this->addColumn(
            'email_address',
            array(
                'header' => Mage::helper('mailchimp')->__('Email'),
                'index'  => 'email_address',
                'sortable' => false
            )
        );

        return parent::_prepareColumns();
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }
    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current' => true));
    }
}
