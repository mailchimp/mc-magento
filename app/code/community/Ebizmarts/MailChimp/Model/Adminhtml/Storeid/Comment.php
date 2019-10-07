<?php

class Ebizmarts_MailChimp_Model_Adminhtml_Storeid_Comment
{
    public function getCommentText()
    {
        $helper = Mage::helper('mailchimp');
        return $helper->__(
            'Select the Mailchimp store you want to associate with this scope. '
            . 'You can create a new store at '
        )
        . '<a target="_blank" href="'
        . Mage::helper('adminhtml')->getUrl('adminhtml/mailchimpstores/index')
        .'">'.$helper->__('Newsletter -> Mailchimp -> Mailchimp Stores').'</a>';
    }
}
