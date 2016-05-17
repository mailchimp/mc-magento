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
class Ebizmarts_MailChimp_Model_Api_Customers
{

    const BATCH_LIMIT = 5;
    const DEFAULT_OPT_IN = true;

    public function CreateBatchJson($mailchimpStoreId)
    {
        //create missing customers first
        $collection = mage::getModel('customer/customer')->getCollection()
            ->addAttributeToSelect('mailchimp_sync_delta')
            ->addAttributeToFilter(array(array('attribute' => 'mailchimp_sync_delta', 'null' => true),array('attribute' => 'mailchimp_sync_delta', 'eq' => '')), '', 'left');
        $collection->getSelect()->limit(self::BATCH_LIMIT);


        //if all synced, start updating old ones
        if ($collection->getSize() == 0) {
            $collection = mage::getModel('customer/customer')->getCollection()
                ->addAttributeToSelect('mailchimp_sync_delta')
                ->addAttributeToFilter(array(array('attribute' => 'mailchimp_sync_delta', 'lt' => new Zend_Db_Expr('updated_at'))), '', 'left');
            $collection->getSelect()->limit(self::BATCH_LIMIT);
        }

        $batchJson = "";
        $operationsCount = 0;
        $batchId = Ebizmarts_MailChimp_Model_Config::IS_CUSTOMER.'_'.date('Y-m-d-H-i-s');

        foreach ($collection as $customer) {
            $customerJson = $this->GeneratePOSTPayload($customer);
            if (!empty($customerJson)) {
                $operationsCount += 1;
                if ($operationsCount > 1) {
                    $batchJson .= ',';
                }
                $batchJson .= '{"method": "PUT",';
                $batchJson .= '"path": "/ecommerce/stores/' . $mailchimpStoreId . '/customers/' . $customer->getId() . '",';
                $batchJson .= '"operation_id": "' . $batchId . '_' . $customer->getId() . '",';
                $batchJson .= '"body": "' . addcslashes($customerJson, '"') . '"';
                $batchJson .= '}';

                //update customers delta
                $customer->setData("mailchimp_sync_delta", Varien_Date::now());
                $customer->setData("mailchimp_sync_error","");
                $customer->save();
            }
        }
        return $batchJson;
    }

    protected function GeneratePOSTPayload($customer)
    {
        $data = array();
        $data["id"] = $customer->getId();
        $data["email_address"] = $customer->getEmail();
//        $data["first_name"] = $customer->getFirstname();
//        $data["last_name"] = $customer->getLastname();

        $data["opt_in_status"] = self::DEFAULT_OPT_IN;

        $jsonData = "";

        //enconde to JSON
        try {

            $jsonData = json_encode($data);

        } catch (Exception $e) {
            //json encode failed
            //@toDo log somewhere
        }

        return $jsonData;
    }
}