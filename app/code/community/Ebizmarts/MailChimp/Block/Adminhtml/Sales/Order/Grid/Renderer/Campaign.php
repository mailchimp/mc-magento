<?php
/**
 * mc-magento Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 8/29/16 4:59 PM
 * @file: Campaign.php
 */

class Ebizmarts_MailChimp_Block_Adminhtml_Sales_Order_Grid_Renderer_Campaign extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $order = Mage::getModel('sales/order')->load($row->getData('entity_id'));
        if ($order->getMailchimpCampaignId()) {
            $result = '<img src="' . $this->getSkinUrl("ebizmarts/mailchimp/images/logo-freddie-monocolor-200.png") . '" width="40" title="hep hep" />';
        } else {
            $result = '';
        }
        return $result;
    }
}