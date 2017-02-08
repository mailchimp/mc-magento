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
            ->addFieldToFilter(
                'mailchimp_sync_delta', array(
                array('null' => true),
                array('eq' => ''),
                array('lt' => Mage::helper('mailchimp')->getMCMinSyncDateFlag())
                )
            );
        $collection->getSelect()->limit($limit);
        $subscriberArray = array();
        $date = Mage::helper('mailchimp')->getDateMicrotime();
        $batchId = Ebizmarts_MailChimp_Model_Config::IS_SUBSCRIBER . '_' . $date;

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
     * @param bool $updateStatus If set to true, it will force the status update even for those already subscribed.
     */
    public function updateSubscriber($subscriber, $updateStatus = false)
    {
        $listId = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_LIST);
        $newStatus = $this->_getMCStatus($subscriber->getStatus());
        $forceStatus = ($updateStatus) ? $newStatus : null;
        $api = Mage::helper('mailchimp')->getApi();
        $mergeVars = Mage::getModel('mailchimp/api_customers')->getMergeVars($subscriber);

        try {
            $md5HashEmail = md5(strtolower($subscriber->getSubscriberEmail()));
            $api->lists->members->addOrUpdate(
                $listId, $md5HashEmail, $subscriber->getSubscriberEmail(), $newStatus, null, $forceStatus, $mergeVars,
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
        } elseif (Mage::helper('mailchimp')->getConfigValue($confirmationFlagPath) &&
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
        $listId = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_LIST);
        $api = Mage::helper('mailchimp')->getApi();
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
        $listId = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_LIST);
        $api = Mage::helper('mailchimp')->getApi();
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