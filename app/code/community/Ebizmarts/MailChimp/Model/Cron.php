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
    /**
     * @var Ebizmarts_MailChimp_Helper_Migration
     */
    protected $_mailChimpMigrationHelper;

    public function __construct()
    {
        $this->_mailChimpHelper = Mage::helper('mailchimp');
        $this->_mailChimpMigrationHelper = Mage::helper('mailchimp/migration');
    }

    public function syncEcommerceBatchData()
    {
        if ($this->getMigrationHelper()->migrationFinished()) {
            Mage::getModel('mailchimp/api_batches')->handleEcommerceBatches();
        } else {
            $this->getMigrationHelper()->handleMigrationUpdates();
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

    public function clearEcommerceData()
    {
        Mage::getModel('mailchimp/clearEcommerce')->clearEcommerceData();
    }
    public function clearBatches()
    {
        Mage::getModel('mailchimp/clearBatches')->clearBatches();
    }

    protected function getHelper($type='')
    {
        return $this->_mailChimpHelper;
    }

    protected function getMigrationHelper()
    {
        return $this->_mailChimpMigrationHelper;
    }
}
