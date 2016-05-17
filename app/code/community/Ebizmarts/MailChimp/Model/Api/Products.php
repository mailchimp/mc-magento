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
class Ebizmarts_MailChimp_Model_Api_Products
{

    const BATCH_LIMIT = 5;

    public function CreateBatchJson($mailchimpStoreId)
    {
        //create missing products first
        $collection = mage::getModel('catalog/product')->getCollection()
            ->addAttributeToSelect('mailchimp_sync_delta')
            ->addAttributeToSelect('name')
            ->addAttributeToFilter('type_id', array('eq' => 'simple'))//GET ONLY SIMPLE PRODUCTS FOR NOW
            ->addAttributeToFilter(array(array('attribute' => 'mailchimp_sync_delta', 'null' => true)), '', 'left');
        $collection->getSelect()->limit(self::BATCH_LIMIT);

        //if all synced, start updating old ones
//            if ($collection->getSize() == 0) {
//                $collection = mage::getModel('catalog/product')->getCollection()
//                    ->addAttributeToSelect('mailchimp_sync_delta')
//                    ->addAttributeToFilter(array(array('attribute' => 'mailchimp_sync_delta', 'lt' => new Zend_Db_Expr('updated_at'))), '', 'left');
//                $collection->getSelect()->limit(self::BATCH_LIMIT);
//            }

        $batchJson = '';
        $operationsCount = 0;
        $batchId = Ebizmarts_MailChimp_Model_Config::IS_PRODUCT.'_'.date('Y-m-d-H-i-s');

        foreach ($collection as $product) {
            $productJson = $this->GeneratePOSTPayload($product);
            if (!empty($productJson)) {
                $operationsCount += 1;
                if ($operationsCount > 1) {
                    $batchJson .= ',';
                }
                $batchJson .= '{"method": "POST",';
                $batchJson .= '"path": "/ecommerce/stores/' . $mailchimpStoreId . '/products",';
                $batchJson .= '"operation_id": "' . $batchId . '_' . $product->getId() . '",';
                $batchJson .= '"body": "' . addcslashes($productJson, '"') . '"';
                $batchJson .= '}';

                //update product delta
                $product->setData("mailchimp_sync_delta", Varien_Date::now());
                $product->setData("mailchimp_sync_error","");
                $product->save();
            }
        }
        return $batchJson;
    }

    protected function GeneratePOSTPayload($product)
    {
        $data = array();
        $data["id"] = $product->getId();
        $data["title"] = $product->getName();

        if ($product->getTypeId() == "simple") {
            $data["variants"] = [
                [
                    "id" => $product->getId(),
                    "title" => $product->getName(),
                    "sku" => $product->getSku()
                ]
            ];
        } else {
            //@toDo configurable
            //@toDo bundle
            //@toDo grouped
        }

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