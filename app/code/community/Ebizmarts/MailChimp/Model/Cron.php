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
        Mage::getModel('mailchimp/api_batches')->handleEcommerceBatches();
        Mage::getModel('mailchimp/api_batches')->handleSubscriberBatches();
    }
}