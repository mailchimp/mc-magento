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
    protected $_mailChimpHelper;

    public function __construct()
    {
        $this->_mailChimpHelper = Mage::helper('mailchimp');
    }

    public function syncEcommerceBatchData()
    {
        if ($this->getHelper()->migrationFinished()) {
            Mage::getModel('mailchimp/api_batches')->handleEcommerceBatches();
        } else {
            $this->getHelper()->handleMigrationUpdates();
        }
    }

    public function syncSubscriberBatchData()
    {
        Mage::getModel('mailchimp/api_batches')->handleSubscriberBatches();
    }

    public function processWebhookData()
    {
        Mage::getModel('mailchimp/processWebhook')->processWebhookData();
    }

    public function deleteWebhookRequests()
    {
        Mage::getModel('mailchimp/processWebhook')->deleteProcessed();
    }

    protected function getHelper()
    {
        return $this->_mailChimpHelper;
    }
}
