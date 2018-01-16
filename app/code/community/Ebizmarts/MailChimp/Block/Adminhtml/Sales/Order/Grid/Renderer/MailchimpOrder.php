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
        $store = Mage::getSingleton('adminhtml/config_data')->getStore();
        $scopeId = Mage::getModel('core/store')->load($store)->getId();
        $helper = Mage::helper('mailchimp');
        $order = Mage::getModel('sales/order')->load($row->getData('entity_id'));
        $orderId = $order->getEntityId();
        $mailchimpStoreId = $helper->getMCStoreId($scopeId);
        $status = Mage::getModel('mailchimp/api_orders')->getSyncedOrder($orderId, $mailchimpStoreId);


            if ($status[0] == 1) {
                $result = $helper->__('Yes');
            } elseif ($status[0] === null && $status[1] !== null)
                $result = $helper->__('Processing');
            elseif ($status[0] === null){
                $result = $helper->__('In queue');
            }else{
                $result = $helper->__('No');
            }

            return $result;
        }

}