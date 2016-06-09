<?php
/**
 * mc-magento Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 5/27/16 1:50 PM
 * @file: EcommerceController.php
 */
class Ebizmarts_Mailchimp_Adminhtml_EcommerceController extends Mage_Adminhtml_Controller_Action
{
    public function resetLocalErrorsAction()
    {
        $result = 1;
        try {
            Mage::helper('mailchimp')->resetErrors();
        } catch(Exception $e)
        {
            $result = 0;
        }
        Mage::app()->getResponse()->setBody($result);
    }
    public function resetEcommerceDataAction()
    {
        $result = 1;
        try {
            Mage::helper('mailchimp')->resetMCEcommerceData(true);
        }
        catch(Mailchimp_Error $e) {
            Mage::helper('mailchimp')->logError($e->getFriendlyMessage());
            $result = 0;
        }
        catch(Exception $e) {
            Mage::helper('mailchimp')->logError($e->getMessage());
        }
        Mage::app()->getResponse()->setBody($result);
    }
}