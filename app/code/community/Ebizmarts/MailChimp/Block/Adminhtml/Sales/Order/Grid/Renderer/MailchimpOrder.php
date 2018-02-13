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
    const SYNCED = 1;

    public function render(Varien_Object $row)
    {
        $storeId = $row->getStoreId();
        $orderId = $row->getEntityId();
        $helper = $this->makeHelper();
        $isReset = $helper->getIsReset($storeId);
        if ($helper->isEcomSyncDataEnabled($storeId)) {
            $mailchimpStoreId = $helper->getMCStoreId($storeId);
            $resultArray = $this->makeApiOrders()->getSyncedOrder($orderId, $mailchimpStoreId);
            $id = $resultArray['order_id'];
            $status = $resultArray['synced_status'];

            if ($status == self::SYNCED) {
                $result = '<div style ="color:green">' . $helper->__("Yes") . '</div>';
            } elseif ($status === null && $id !== null && !$isReset)
                $result = '<div style ="color:#ed6502">' . $helper->__("Processing") . '</div>';
            elseif ($status === null || $isReset) {
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
     * @return Ebizmarts_MailChimp_Helper_Data
     */
    protected function makeHelper()
    {
        return Mage::helper('mailchimp');
    }

    /**
     * @return Ebizmarts_MailChimp_Model_Api_Orders
     */
    protected function makeApiOrders()
    {
        return Mage::getModel('mailchimp/api_orders');
    }

}
