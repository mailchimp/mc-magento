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
        $MCMinSyncDateFlagPath = Ebizmarts_MailChimp_Model_Config::GENERAL_SUB_MCMINSYNCDATEFLAG;
        //get subscribers
        $collection = Mage::getModel('newsletter/subscriber')->getCollection()
            ->addFieldToFilter('subscriber_status', array('eq' => 1))
            ->addFieldToFilter('store_id', array('eq' => $storeId))
            ->addFieldToFilter(
                'mailchimp_sync_delta', array(
                array('null' => true),
                array('eq' => ''),
                array('lt' => Mage::helper('mailchimp')->getConfigValue($MCMinSyncDateFlagPath, $storeId))
                )
            );
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
                $errorMessage = "Subscriber ".$subscriber->getSubscriberId()." json encode failed";
                Mage::helper('mailchimp')->logError($errorMessage);
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
            $counter++;
        }
        return $subscriberArray;
    }

    protected function _buildSubscriberData($subscriber)
    {
        $data = array();
        $data["email_address"] = $subscriber->getSubscriberEmail();
        $mergeVars = Mage::getModel('mailchimp/api_customers')->getMergeVars($subscriber);
        if ($mergeVars) {
            $data["merge_fields"] = $mergeVars;
        }
        $data["status_if_new"] = $this->_getMCStatus($subscriber->getStatus());

        return $data;
    }

    /**
     * @param $subscriber
     */
    public function updateSubscriber($subscriber)
    {
        $apiKey = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_APIKEY);
        $listId = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_LIST);
        $status = $this->_getMCStatus($subscriber->getStatus());
        $userAgent = 'Mailchimp4Magento'.(string)Mage::getConfig()->getNode('modules/Ebizmarts_MailChimp/version');
        $api = new Ebizmarts_Mailchimp($apiKey, null, $userAgent);
        $mergeVars = Mage::getModel('mailchimp/api_customers')->getMergeVars($subscriber);

        try {
            $md5HashEmail = md5(strtolower($subscriber->getSubscriberEmail()));
            $api->lists->members->addOrUpdate(
                $listId, $md5HashEmail, $subscriber->getSubscriberEmail(), $status, null, $status, $mergeVars,
                null, null, null, null
            );
            $subscriber->setData("mailchimp_sync_delta", Varien_Date::now());
            $subscriber->setData("mailchimp_sync_error", "");
        } catch(Mailchimp_Error $e) {
            Mage::helper('mailchimp')->logError($e->getFriendlyMessage());
            Mage::getSingleton('adminhtml/session')->addError($e->getFriendlyMessage());
        } catch (Exception $e) {
            Mage::helper('mailchimp')->logError($e->getMessage());
        }
    }

    /**
     * Get status to send confirmation if Need to Confirm enabled on Magento
     *
     * @param null $status
     * @return string
     */
    protected function _getMCStatus($status = null)
    {
        $confirmationFlagPath = Mage_Newsletter_Model_Subscriber::XML_PATH_CONFIRMATION_FLAG;
        if ($status == Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED) {
            $status = 'unsubscribed';
        } elseif (
            Mage::helper('mailchimp')->getConfigValue($confirmationFlagPath) &&
            ($status == Mage_Newsletter_Model_Subscriber::STATUS_NOT_ACTIVE ||
                $status == Mage_Newsletter_Model_Subscriber::STATUS_UNCONFIRMED)
        ) {
            $status = 'pending';
        } elseif ($status == Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED) {
            $status = 'subscribed';
        }
        return $status;
    }

    public function removeSubscriber($subscriber)
    {
        $apiKey = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_APIKEY);
        $listId = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_LIST);
        $userAgent = 'Mailchimp4Magento'.(string)Mage::getConfig()->getNode('modules/Ebizmarts_MailChimp/version');
        $api = new Ebizmarts_Mailchimp($apiKey, null, $userAgent);
        try {
            $md5HashEmail = md5(strtolower($subscriber->getSubscriberEmail()));
            $api->lists->members->update($listId, $md5HashEmail, null, 'unsubscribed');
        } catch(Mailchimp_Error $e) {
            Mage::helper('mailchimp')->logError($e->getFriendlyMessage());
            Mage::getSingleton('adminhtml/session')->addError($e->getFriendlyMessage());
        } catch (Exception $e) {
            Mage::helper('mailchimp')->logError($e->getMessage());
        }
    }

    /**
     * @param $subscriber
     */
    public function deleteSubscriber($subscriber)
    {
        $apiKey = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_APIKEY);
        $listId = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_LIST);
        $api = new Ebizmarts_Mailchimp($apiKey);
        try {
            $md5HashEmail = md5(strtolower($subscriber->getSubscriberEmail()));
            $api->lists->members->update($listId, $md5HashEmail, null, 'cleaned');
        } catch(Mailchimp_Error $e) {
            Mage::helper('mailchimp')->logError($e->getFriendlyMessage());
            Mage::getSingleton('adminhtml/session')->addError($e->getFriendlyMessage());
        } catch (Exception $e) {
            Mage::helper('mailchimp')->logError($e->getMessage());
        }
    }
}