<?php
/**
 * @category Ebizmarts
 * @package mailchimp-lib
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Cron processor class
 */
class Ebizmarts_MailChimp_Model_Cron
{
    /**
 * @var Ebizmarts_MailChimp_Helper_Data 
*/
    private $mailChimpHelper;

    public function __construct()
    {
        $this->mailChimpHelper = Mage::helper('mailchimp');
    }

    public function syncEcommerceBatchData(Mage_Cron_Model_Schedule $schedule)
    {
        if ($this->getHelper()->migrationFinished()) {
            Mage::getModel('mailchimp/api_batches')->handleEcommerceBatches();
        } else {
            $this->getHelper()->handleMigrationUpdates();
        }
    }

    public function syncSubscriberBatchData(Mage_Cron_Model_Schedule $schedule)
    {
        Mage::getModel('mailchimp/api_batches')->handleSubscriberBatches();
    }

    public function processWebhookData($cron)
    {
        Mage::getModel('mailchimp/processWebhook')->processWebhookData();
    }

    public function deleteWebhookRequests($cron)
    {
        Mage::getModel('mailchimp/processWebhook')->deleteProcessed();
    }

    private function getHelper()
    {
        return $this->mailChimpHelper;
    }
}
