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

    public function syncEcommerceData($cron)
    {
        $storeId = Mage::helper('mailchimp')->getMCStoreId();

        Mage::getModel('mailchimp/api_batches')->getResults($storeId);
        Mage::getModel('mailchimp/api_batches')->sendBatch($storeId);
    }
}