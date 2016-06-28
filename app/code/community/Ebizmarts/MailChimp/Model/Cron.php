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

/**
 * Cron processor class
 *
 */
class Ebizmarts_MailChimp_Model_Cron
{

    public function syncBatchData($cron)
    {
        $mailchimpStoreId = Mage::helper('mailchimp')->getMCStoreId();
        $subscriberLimit = Ebizmarts_MailChimp_Model_Api_subscribers::BATCH_LIMIT;
        $stores = Mage::app()->getStores();

        Mage::getModel('mailchimp/api_batches')->getResults($mailchimpStoreId);
        Mage::getModel('mailchimp/api_batches')->sendEcommerceBatch($mailchimpStoreId);
        foreach ($stores as $store) {
            if($subscriberLimit > 0) {
                $batchResponse = array();
                list($batchResponse, $subscriberLimit) = Mage::getModel('mailchimp/api_batches')->sendSubscriberBatch($store->getId(), $subscriberLimit);
            }else{
                break;
            }
        }
    }
}