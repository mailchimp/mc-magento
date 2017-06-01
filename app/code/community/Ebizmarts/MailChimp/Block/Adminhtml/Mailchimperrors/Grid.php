<?php
/**
 * mc-magento Magento Component
 *
 * @category  Ebizmarts
 * @package   mc-magento
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     6/10/16 12:38 AM
 * @file:     Grid.php
 */
class Ebizmarts_MailChimp_Block_Adminhtml_Mailchimperrors_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('mailchimp_mailchimperrors_grid');
        $this->setDefaultSort('id');
        $this->setDefaultDir('DESC');
        $this->setUseAjax(true);
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel('mailchimp/mailchimperrors_collection');
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {

        //        $this->addColumn('action', array(
        //            'header' => Mage::helper('mailchimp')->__('Action'),
        //            'index' => 'action',
        //            'sortable' => false
        //        ));
        //        $this->addColumn('type', array(
        //            'header' => Mage::helper('mailchimp')->__('Type'),
        //            'index' => 'type',
        //            'sortable' => true
        //        ));
        $this->addColumn(
            'title', array(
            'header' => Mage::helper('mailchimp')->__('Title'),
            'index' => 'title',
            'sortable' => true
            )
        );
        $this->addColumn(
            'status', array(
            'header' => Mage::helper('mailchimp')->__('Status'),
            'index' => 'status',
            'width' => '100px',
            'sortable' => true
            )
        );
        $this->addColumn(
            'regtype', array(
            'header' => Mage::helper('mailchimp')->__('Reg Type'),
            'index' => 'regtype',
            'width' => '100px',
            'sortable' => true
            )
        );
        $this->addColumn(
            'store_id', array(
                'header' => Mage::helper('mailchimp')->__('Store Id'),
                'index' => 'store_id',
                'sortable' => false
            )
        );
        $this->addColumn(
            'errors', array(
                'header' => Mage::helper('mailchimp')->__('Error'),
                'index'  => 'errors',
                'sortable' => false
            )
        );
        $this->addColumn(
            'batch_id', array(
                'header' => Mage::helper('mailchimp')->__('Batch ID'),
                'index'  => 'batch_id',
                'sortable' => false
            )
        );
        $this->addColumn(
            'action_donwload', array(
                'header'   => $this->helper('mailchimp')->__('Download Response'),
                'width'    => 15,
                'sortable' => false,
                'filter'   => false,
                'type'     => 'action',
                'getter'   => 'getId',
                'actions'  => array(
                    array(
                        'url'     => array('base'=> '*/*/downloadresponse'),
                        'caption' => $this->helper('mailchimp')->__('Download'),
                        'field'   => 'id'
                    ),
                )
            )
        );
        $this->addColumn(
            'original_id', array(
            'header' => Mage::helper('mailchimp')->__('Original'),
            'index' => 'original_id',
            'sortable' => false,
            'renderer' => 'mailchimp/adminhtml_mailchimperrors_link'
            )
        );

        return parent::_prepareColumns();
    }

    public function getRowUrl($row)
    {
        return false;
    }
    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current' => true));
    }
}
