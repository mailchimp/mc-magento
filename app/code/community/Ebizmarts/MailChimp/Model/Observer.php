<?php
/**
 * MailChimp For Magento
 *
 * @category Ebizmarts_MailChimp
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 4/29/16 3:55 PM
 * @file: Observer.php
 */
class Ebizmarts_MailChimp_Model_Observer
{

    /**
     * Handle save of System -> Configuration, section <mailchimp>
     *
     * @param Varien_Event_Observer $observer
     * @return void|Varien_Event_Observer
     */
    public function saveConfig(Varien_Event_Observer $observer)
    {
        $isEnabled = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_ACTIVE);

        if($isEnabled)
        {
            try {

                /**
                 * CREATE MAILCHIMP STORE
                 */
                $mailchimpStore = Mage::getModel('mailchimp/api_stores')->getMailChimpStore();
                if(!$mailchimpStore) {
                    Mage::helper('mailchimp')->resetMCEcommerceData();
                }

            } catch (Exception $e)
            {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }

        return $observer;
    }

    public function alterNewsletterGrid(Varien_Event_Observer $observer){

        $block = $observer->getEvent()->getBlock();
        if (!isset($block)) {
            return $this;
        }
        if($block instanceof Mage_Adminhtml_Block_Newsletter_Subscriber_Grid) {

            $block->addColumnAfter('firstname', array(
                'header' => Mage::helper('newsletter')->__('Customer First Name'),
                'index' => 'customer_firstname',
                'renderer' => 'mailchimp/adminhtml_newsletter_subscriber_renderer_firstname',
            ), 'type'
            );

            $block->addColumnAfter('lastname', array(
                'header' => Mage::helper('newsletter')->__('Customer Last Name'),
                'index' => 'customer_lastname',
                'renderer' => 'mailchimp/adminhtml_newsletter_subscriber_renderer_lastname'
            ), 'firstname');
        }
        return $observer;
    }

    public function customerSaveAfter(Varien_Event_Observer $observer)
    {
        $customer = $observer->getEvent()->getCustomer();

        if($customer->getMailchimpUpdateObserverRan())
        {
            return;
        }else{
            $customer->setMailchimpUpdateObserverRan(true);
        }

        //update mailchimp ecommerce data for that customer
        Mage::getModel('mailchimp/api_customers')->update($customer);
    }

    public function productSaveAfter(Varien_Event_Observer $observer)
    {
        $product = $observer->getEvent()->getProduct();

        if($product->getMailchimpUpdateObserverRan())
        {
            return;
        }else{
            $product->setMailchimpUpdateObserverRan(true);
        }

        //update mailchimp ecommerce data for that product variant
        Mage::getModel('mailchimp/api_products')->update($product);
    }
}
