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
        $helper = Mage::helper('mailchimp');
        $orderId = $row->getData('increment_id');
        $status = Mage::getModel('mailchimp/api_orders')->getOrderFromAPI($orderId);

        if ($status) {
            $result = $helper->__('Yes');
        } else {
            $result = $helper->__('No');
        }

        return $result;
    }
}