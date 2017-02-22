<?php
/**
 * Created by PhpStorm.
 * User: santisp
 * Date: 22/05/15
 * Time: 05:23 PM
 */
class Ebizmarts_MailChimp_Block_Adminhtml_Newsletter_Subscriber_Renderer_Firstname extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{

    public function render(Varien_Object $row)
    {
        $subscriberFirstName = $row->getData('subscriber_firstname');
        $customerFirstName = $row->getData('customer_firstname');
        if ($customerFirstName) {
            return $this->escapeHtml($customerFirstName);
        } elseif ($subscriberFirstName) {
            return $this->escapeHtml($subscriberFirstName);
        } else {
            return '----';
        }
    }
}