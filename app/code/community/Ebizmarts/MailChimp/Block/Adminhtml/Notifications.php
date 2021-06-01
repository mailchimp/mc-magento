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

    public function getMessageNotification()
    {
        $helper = $this->makeHelper();
        if ($helper->isImageCacheFlushed() && $helper->isEcomSyncDataEnabledInAnyScope()) {
            $message = '<strong style="color:red">Important: </strong>' .
                '<span>Image cache has been flushed please '
                . '<a href="#" onclick="openResendEcommerceDialog();">'
                . 'resend the products</a> in order to update image URL</span>';
            return $message;
        }
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        $helper = $this->makeHelper();
        $message = 'Are you sure you want to delete the local data in order to send all items again?\n'
            . 'Automations will work normally but the synchronization process for the old data will take '
            . 'longer than resetting the MailChimp store.';
        return $helper->__($message);
    }

    public function getAjaxCheckUrl()
    {
        $helper = $this->makeHelper();

        return $helper->getUrlForNotification();
    }

    public function getUrlForResendEcommerce()
    {
        $helper = $this->makeHelper();
        $scopeArray = $helper->getCurrentScope();
        $url = Mage::helper('adminhtml')
            ->getUrl(
                'adminhtml/ecommerce/renderresendecom',
                array('scope' => $scopeArray['scope'], 'scope_id' => $scopeArray['scope_id']
                )
            );

        return $url;
    }


    /**
     * @return Ebizmarts_MailChimp_Helper_Data
     */
    protected function makeHelper()
    {
        return Mage::helper('mailchimp');
    }
}
