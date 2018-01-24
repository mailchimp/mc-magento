<?php

/**
 * mc-magento Magento Component
 *
 * @category  Ebizmarts
 * @package   mc-magento
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     12/12/17 3:28 PM
 * @file:     Abandoned.php
 */
class Ebizmarts_MailChimp_Block_Adminhtml_Sales_Order_Grid_Renderer_MailchimpOrder extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $storeId = $row->getStoreId();
        $orderId = $row->getEntityId();
        $helper = $this->makeHelper();
        if ($helper->isEcomSyncDataEnabled($storeId)) {
            $mailchimpStoreId = $helper->getMCStoreId($storeId);
            $status = $this->makeApiOrders()->getSyncedOrder($orderId, $mailchimpStoreId);


            if ($status[0] == 1) {
                $result = '<div style ="color:green">' . $helper->__("Yes") . '</div>';
            } elseif ($status[0] === null && $status[1] !== null)
                $result = '<div style ="color:#ed6502">' . $helper->__("Processing") . '</div>';
            elseif ($status[0] === null) {
                $result = '<div style ="color:mediumblue">' . $helper->__("In queue") . '</div>';
            } else {
                $result = '<div style ="color:red">' . $helper->__("No") . '</div>';
            }

        } else {
            $result = '<div style ="color:red">' . $helper->__("No") . '</div>';
        }

        return $result;
    }

    /**
     * @return Mage_Core_Helper_Abstract
     */
    protected function makeHelper()
    {
        return Mage::helper('mailchimp');
    }

    /**
     * @return false|Mage_Core_Model_Abstract
     */
    protected function makeApiOrders()
    {
        return Mage::getModel('mailchimp/api_orders');
    }

}
