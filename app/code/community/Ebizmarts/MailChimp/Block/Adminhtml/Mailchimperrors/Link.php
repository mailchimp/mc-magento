<?php
/**
 * mc-magento Magento Component
 *
 * @category  Ebizmarts
 * @package   mc-magento
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     6/10/16 2:23 PM
 * @file:     Link.php
 */
class Ebizmarts_MailChimp_Block_Adminhtml_Mailchimperrors_Link extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $id = $row->getData('original_id');
        switch ($row->getData('regtype')) {
        case Ebizmarts_MailChimp_Model_Config::IS_CUSTOMER:
            $url = Mage::helper("adminhtml")->getUrl("adminhtml/customer/edit", array('id'=>$id));
            $displayText = '<a href="' . $url . '" target="_blank">' . $this->__('Go to Customer view. Id:') . ' ' . $id . '</a>';
            break;
        case Ebizmarts_MailChimp_Model_Config::IS_ORDER:
            $url = Mage::helper("adminhtml")->getUrl("adminhtml/sales_order/view", array('order_id'=>$id));
            $displayText = '<a href="' . $url . '" target="_blank">' . $this->__('Go to Order view. Id:') . ' ' . $id . '</a>';
            break;
        case Ebizmarts_MailChimp_Model_Config::IS_PRODUCT:
            $url = Mage::helper("adminhtml")->getUrl("adminhtml/catalog_product/edit", array('id'=>$id));
            $displayText = '<a href="' . $url . '" target="_blank">' . $this->__('Go to Product view. Id:') . ' ' . $id . '</a>';
            break;
        case Ebizmarts_MailChimp_Model_Config::IS_QUOTE:
            $displayText = $this->__('Quote view not available. Id:') . ' ' . $id;
            break;
        case Ebizmarts_MailChimp_Model_Config::IS_SUBSCRIBER:
            $displayText = $this->__('Subscriber view not available. Id:') . ' ' . $id;
            break;
        case Ebizmarts_MailChimp_Model_Config::IS_PROMO_RULE:
            $displayText = $this->__('Promo Rule view not available. Id:') . ' ' . $id;
            break;
        case Ebizmarts_MailChimp_Model_Config::IS_PROMO_CODE:
            $displayText = $this->__('Promo Code view not available. Id:') . ' ' . $id;
            break;
        default:
            $displayText = $this->__('Something went wrong when retrieving original item.');
            break;
        }

        return $displayText;
    }
}