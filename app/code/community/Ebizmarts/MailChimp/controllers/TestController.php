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

class Ebizmarts_MailChimp_TestController extends Mage_Core_Controller_Front_Action
{

    public function indexAction()
    {
        /**
         * testing
         */

        try {
            $response = Mage::getModel('mailchimp/api_customers')->SyncBatch(1);

//            $mailchimpApi = new Ebizmarts_Mailchimp("2cb911e2b6951805cdab47df20997033-us13");
//            $response = $mailchimpApi->batchOperation->status("fb207083a5");

//            $response = $mailchimpApi->ecommerce->stores->get();

//            $response = $mailchimpApi->ecommerce->customers->getAll();

            echo "<h1>RESPONSE</h1>";
            var_dump($response);



        } catch (Exception $e){
            echo "<h1>EXCEPTION</h1>";
            var_dump($e);
        }
    }

}