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
        $apiKey = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_APIKEY);
        $isEnabled = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_ACTIVE);
        $listId = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_LIST);
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

            } catch (Mailchimp_Error $e)
            {
                Mage::helper('mailchimp')->logError($e->getFriendlyMessage());
                Mage::getSingleton('adminhtml/session')->addError($e->getFriendlyMessage());

            } catch (Exception $e)
            {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        if($listId != '' && Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_TWO_WAY_SYNC)) {
            $webhooksKey = Mage::helper('mailchimp')->getWebhooksKey();

            //Generating Webhooks URL
            $hookUrl = Mage::getModel('core/url')->getUrl(Ebizmarts_MailChimp_Model_ProcessWebhook::WEBHOOKS_PATH, array('wkey' => $webhooksKey));

            if (FALSE != strstr($hookUrl, '?', true)) {
                $hookUrl = strstr($hookUrl, '?', true);
            }

            $this->_saveCustomerGroups($listId, $apiKey, $hookUrl);
        }

        return $observer;
    }

    protected function _saveCustomerGroups($listId, $apiKey, $hookUrl)
    {
        $api = new Ebizmarts_Mailchimp($apiKey);
        $webhookId = Mage::helper('mailchimp')->getMCStoreId();
        $events = array(
            'subscribe' => true,
            'unsubscribe' => true,
            'profile' => true,
            'cleaned' => true,
            'upemail' => true,
            'campaign' => false
        );
        $sources = array(
            'user' => true,
            'admin' => true,
            'api' => true
        );
        try {
            $response = $api->lists->webhooks->getAll($listId);
            if (isset($response['webhooks'][0]) && count($response['webhooks'][0]['url']) == $hookUrl) {
                $api->lists->webhooks->add($listId, $hookUrl, $events, $sources);
            }
        }
        catch(Mailchimp_Error $e)
        {
            Mage::helper('mailchimp')->logError($e->getFriendlyMessage());
            Mage::getSingleton('adminhtml/session')->addError($e->getFriendlyMessage());
        }
        catch (Exception $e){
            Mage::helper('mailchimp')->logError($e->getMessage());
        }
    }

    public function handleSubscriber(Varien_Event_Observer $observer)
    {
        $isEnabled = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_ACTIVE);
        if($isEnabled){
            $subscriber = $observer->getEvent()->getSubscriber();
            if (TRUE === $subscriber->getIsStatusChanged()) {
                $apiKey = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_APIKEY);
                $listId = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_LIST);
                //@Todo Create Api/Subscriber class for subscriber functions
                $status = Mage::helper('mailchimp')->getStatus();
                $api = new Ebizmarts_Mailchimp($apiKey);
                $mergeVars = array();
                if($subscriber->getFirstName()){
                    $mergeVars['FNAME'] = $subscriber->getFirstName();
                }
                if($subscriber->getLastName()){
                    $mergeVars['LNAME'] = $subscriber->getLastName();
                }
                try {
                    $api->lists->members->add($listId, null, $status, $subscriber->getEmail(), $mergeVars);
                }catch(Mailchimp_Error $e){
                    Mage::helper('mailchimp')->logError($e->getFriendlyMessage());
                    Mage::getSingleton('adminhtml/session')->addError($e->getFriendlyMessage());
                }catch (Exception $e){
                    Mage::helper('mailchimp')->logError($e->getMessage());
                }
            }
        }
    }

    public function handleSubscriberDeletion(Varien_Event_Observer $observer)
    {
        $isEnabled = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_ACTIVE);
        if($isEnabled){
            $subscriber = $observer->getEvent()->getSubscriber();
            if (TRUE === $subscriber->getIsStatusChanged()) {
                $apiKey = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_APIKEY);
                $listId = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_LIST);
                $api = new Ebizmarts_Mailchimp($apiKey);
                try {
                    $md5HashEmail = md5(strtolower($subscriber->getEmail()));
                    $api->lists->members->update($listId, $md5HashEmail, null, 'unsubscribed');
                }
                catch(Mailchimp_Error $e){
                    Mage::helper('mailchimp')->logError($e->getFriendlyMessage());
                    Mage::getSingleton('adminhtml/session')->addError($e->getFriendlyMessage());
                }
                catch (Exception $e){
                    Mage::helper('mailchimp')->logError($e->getMessage());
                }
            }
        }
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

    public function productSaveBefore(Varien_Event_Observer $observer)
    {
        $product = $observer->getEvent()->getProduct();

        if($product->getMailchimpUpdateObserverRan())
        {
            return;
        }else{
            $product->setMailchimpUpdateObserverRan(true);
        }

        //update mailchimp ecommerce data for that product variant
//        Mage::getModel('mailchimp/api_products')->update($product);
    }
}
