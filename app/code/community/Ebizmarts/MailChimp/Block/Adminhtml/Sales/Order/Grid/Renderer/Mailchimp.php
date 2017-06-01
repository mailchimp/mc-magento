<?php
/**
 * mc-magento Magento Component
 *
 * @category  Ebizmarts
 * @package   mc-magento
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     7/7/16 1:31 PM
 * @file:     Abandoned.php
 */
class Ebizmarts_MailChimp_Block_Adminhtml_Sales_Order_Grid_Renderer_Mailchimp extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $order = Mage::getModel('sales/order')->load($row->getData('entity_id'));
        if ($order->getMailchimpAbandonedcartFlag() || $order->getMailchimpCampaignId() || $order->getMailchimpLandingPage()) {
            $result = '<img src="' . $this->getSkinUrl("ebizmarts/mailchimp/images/logo-freddie-monocolor-200.png") . '" width="40" title="hep hep thanks MailChimp" />';
        } else {
            $result = '';
        }

        return $result;
    }
}