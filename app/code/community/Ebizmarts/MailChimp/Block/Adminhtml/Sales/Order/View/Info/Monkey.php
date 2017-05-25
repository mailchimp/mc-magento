<?php
/**
 * mc-magento Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 12/8/16 2:04 PM
 * @file: Monkey.php
 */
class Ebizmarts_MailChimp_Block_Adminhtml_Sales_Order_View_Info_Monkey extends Mage_Core_Block_Template
{
    public function isReferred()
    {
        $order = Mage::registry('current_order');
        $ret = false;
        if ($order->getMailchimpAbandonedcartFlag() || $order->getMailchimpCampaignId())
        {
            $ret = true;
        }

        return $ret;
    }
    public function getCampaign()
    {
        $order = Mage::registry('current_order');
        return $order->getMailchimpCampaignId();
    }
}
