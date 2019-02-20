<?php
/**
 * mc-magento Magento Component
 *
 * @category  Ebizmarts
 * @package   mc-magento
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     2/20/19 10:37 AM
 * @file:     Notifications.php
 */
class Ebizmarts_MailChimp_Block_Adminhtml_Notifications extends Mage_Adminhtml_Block_Template
{

    public function getMessage()
    {
        $helper = $this->makeHelper();
        if ($helper->isImageCacheFlushed() && $helper->isEcommerceEnabled(0)) {
            $message = '<strong style=color:red>Important: </strong>'. '<span> Please resend ecommerce data, to be sure that all the product data is up to date in <strong>Mailchimp</strong> </span>';
            return $message;
        }
    }

    /**
     * @return Ebizmarts_MailChimp_Helper_Data
     */
    protected function makeHelper()
    {
        return Mage::helper('mailchimp');
    }
}