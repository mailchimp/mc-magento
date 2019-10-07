<?php
/**
 * mailchimp-lib Magento Component
 *
 * @category  Ebizmarts
 * @package   mailchimp-lib
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     3/1/19 11:45 AM
 * @file      Comment.php
 */
class Ebizmarts_MailChimp_Model_Adminhtml_Includetaxes_Comment
{
    public function getCommentText()
    {
        $helper = Mage::helper('mailchimp');
        return $helper->__(
            'Send product price including tax if '
        )
        . '<a target="_blank" href="'
        .Mage::helper('adminhtml')->getUrl('adminhtml/system_config/edit/section/tax')
        .'">'. $helper->__("display prices") .'</a>'
        . $helper->__(' are configured in the same way.');
    }
}
