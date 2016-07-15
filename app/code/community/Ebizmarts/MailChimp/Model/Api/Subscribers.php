<?php

/**
 * mailchimp-lib Magento Component
 *
 * @category Ebizmarts
 * @package mailchimp-lib
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Ebizmarts_MailChimp_Model_Api_subscribers
{
    const BATCH_LIMIT = 100;

    public function createBatchJson($listId, $storeId, $limit)
    {
        //get subscribers
        $collection = Mage::getModel('newsletter/subscriber')->getCollection()
            ->addFieldToFilter('subscriber_status', array('eq' => 1))
            ->addFieldToFilter('store_id', array('eq' => $storeId))
            ->addFieldToFilter('mailchimp_sync_delta', array(
                array('null' => true),
                array('eq' => ''),
                array('lt' => Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_SUB_MCMINSYNCDATEFLAG, $storeId))
            ));
        $collection->getSelect()->limit($limit);
        $subscriberArray = array();
        $batchId = Ebizmarts_MailChimp_Model_Config::IS_SUBSCRIBER . '_' . date('Y-m-d-H-i-s');

        $counter = 0;
        foreach ($collection as $subscriber) {
            $data = $this->_buildSubscriberData($subscriber);
            $md5HashEmail = md5(strtolower($subscriber->getSubscriberEmail()));
            $subscriberJson = "";

            //enconde to JSON
            try {
                $subscriberJson = json_encode($data);

            } catch (Exception $e) {
                //json encode failed
                Mage::helper('mailchimp')->logError("Subscriber ".$subscriber->getSubscriberId()." json encode failed");
            }

            if (!empty($subscriberJson)) {
                $subscriberArray[$counter]['method'] = "PUT";
                $subscriberArray[$counter]['path'] = "/lists/" . $listId . "/members/" . $md5HashEmail;
                $subscriberArray[$counter]['operation_id'] = $batchId . '_' . $subscriber->getSubscriberId();
                $subscriberArray[$counter]['body'] = $subscriberJson;

                //update subscribers delta
                $subscriber->setData("mailchimp_sync_delta", Varien_Date::now());
                $subscriber->setData("mailchimp_sync_error", "");
                $subscriber->save();
            }
            $counter += 1;
        }
        return $subscriberArray;
    }

    protected function _buildSubscriberData($subscriber)
    {
        $data = array();
        $data["email_address"] = $subscriber->getSubscriberEmail();
        $mergeVars = $this->getMergeVars($subscriber);
        if($mergeVars) {
            $data["merge_fields"] = $mergeVars;
        }
        $data["status_if_new"] = Mage::helper('mailchimp')->getStatus($subscriber);

        return $data;
    }

    public function addGuestSubscriber($subscriber){
        $apiKey = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_APIKEY);
        $listId = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_LIST);
        $status = Mage::helper('mailchimp')->getStatus();
        $api = new Ebizmarts_Mailchimp($apiKey);
        $mergeVars = $this->getMergeVars($subscriber);
        try {
            $api->lists->members->add($listId, null, $status, $subscriber->getSubscriberEmail(), $mergeVars);
        }catch(Mailchimp_Error $e){
            $this->logError($e->getFriendlyMessage());
            Mage::getSingleton('adminhtml/session')->addError($e->getFriendlyMessage());
        }catch (Exception $e){
            $this->logError($e->getMessage());
        }
    }

    public function removeSubscriber($subscriber){
        $apiKey = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_APIKEY);
        $listId = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_LIST);
        $api = new Ebizmarts_Mailchimp($apiKey);
        try {
            $md5HashEmail = md5(strtolower($subscriber->getSubscriberEmail()));
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

    protected function getMergeVars($subscriber)
    {
        $mergeVars = array();
        if($subscriber->getSubscriberFirstname()){
            $mergeVars['FNAME'] = $subscriber->getSubscriberFirstname();
        }
        if($subscriber->getSubscriberLastname()){
            $mergeVars['LNAME'] = $subscriber->getSubscriberLastname();
        }
        return (!empty($mergeVars)) ? $mergeVars : null;
    }
}