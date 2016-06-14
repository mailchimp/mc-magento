<?php
/**
 * mc-magento Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 6/10/16 12:35 PM
 * @file: MailchimperrorsController.php
 */
class Ebizmarts_Mailchimp_Adminhtml_MailchimperrorsController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $this->_title($this->__('Newsletter'))
            ->_title($this->__('MailChimp'));

        $this->loadLayout();
        $this->_setActiveMenu('newsletter/mailchimp');
        $this->renderLayout();
    }
    public function gridAction()
    {
        $this->loadLayout(false);
        $this->renderLayout();
    }
}