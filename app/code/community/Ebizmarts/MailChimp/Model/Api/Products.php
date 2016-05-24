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

    const BATCH_LIMIT = 100;

    public function CreateBatchJson($mailchimpStoreId)
    {
        //create missing products first
        $collection = mage::getModel('catalog/product')->getCollection()
            ->addAttributeToSelect('mailchimp_sync_delta')
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('type_id')
            ->addAttributeToSelect('description')
            ->addAttributeToSelect('parent_product_ids')
            ->addAttributeToSelect('image')
            ->addAttributeToSelect('price')
            ->addAttributeToSelect('visibility')
            ->addAttributeToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
//            ->addAttributeToFilter('visibility', array('in' => array(
//                Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_SEARCH,
//                Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH,
//            )))
            ->addAttributeToFilter(array(array('attribute' => 'mailchimp_sync_delta', 'null' => true), array('attribute' => 'mailchimp_sync_delta', 'eq' => '')), '', 'left');
        $collection->getSelect()->limit(self::BATCH_LIMIT);

        $batchJson = '';
        $operationsCount = 0;
        $batchId = Ebizmarts_MailChimp_Model_Config::IS_PRODUCT . '_' . date('Y-m-d-H-i-s');

        foreach ($collection as $product)
        {
            //define variants and root products
            $varientProducts = array();
            if ($product->getTypeId() == "simple")
            {
                //check if parent exists
                $parentIds = Mage::getResourceSingleton('catalog/product_type_configurable')->getParentIdsByChild($product->getId());
                if (!empty($parentIds)) {
                    /**
                     * this product will be uploaded within his parent so we abort the data build
                     * we update the delta anyway
                     */

                    //update product delta
                    $product->setData("mailchimp_sync_delta", Varien_Date::now());
                    $product->setData("mailchimp_sync_error", "");
                    $product->save();

                    continue;
                } else {
                    //a simple product has only one variant (itself)
                    $varientProducts[] = $product;
                }
            } else if ($product->getTypeId() == "configurable") {
                //get children
                $childProducts = Mage::getModel('catalog/product_type_configurable')->getUsedProducts(null, $product);

                foreach ($childProducts as $child) {
                    $varientProducts[] = $child;
                }
            } else {
                //@toDo bundle
                //@toDo grouped
                //@toDo virtual
                //@toDo download
            }

            $data = $this->_buildProductData($product, false, $varientProducts);

            //enconde to JSON
            try {
                $productJson = json_encode($data);

            } catch (Exception $e)
            {
                //json encode failed
                Mage::helper('mailchimp')->log("Product ".$product->getId()." json encode failed");

                continue;
            }

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
                $product->setData("mailchimp_sync_error", "");
                $product->save();
            }
        }
        return $batchJson;
    }

    protected function _buildProductData($product, $isVarient = true, $variants = null)
    {
        $data = array();

        //data applied for both root and varient products
        $data["id"] = $product->getId();
        $data["title"] = $product->getName();
        $data["url"] = $product->getProductUrl();

        //image
        $productMediaConfig = Mage::getModel('catalog/product_media_config');
        $data["image_url"] = $productMediaConfig->getMediaUrl($product->getImage());;

        //missing data
        $data["published_at_foreign"] = "";

        if ($isVarient) 
        {
            //this is for a varient product
            $data["sku"] = $product->getSku();
            $data["price"] = $product->getPrice();

            //stock
            $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
            //$data["inventory_quantity"] = (int)$stock->getQty();
            $data["inventory_quantity"] = "fail";
            $data["backorders"] = (string)$stock->getBackorders();

            $data["visibility"] = $product->getVisibility();

        } else 
        {
            //this is for a root product
            $data["description"] = $product->getDescription();

            //mailchimp product type (magento category)
            $categoryIds = $product->getCategoryIds();
            if (count($categoryIds)) {
                $category = Mage::getModel('catalog/category')->load($categoryIds[0]);
                $data["type"] = $category->getName();
            }

            //missing data
            $data["vendor"] = "";
            $data["handle"] = "";

            //variants
            $data["variants"] = [];
            foreach ($variants as $variant) {
                $data["variants"][] = $this->_buildProductData($variant);
            }
        }

        return $data;
    }

    public function Update($product)
    {
        try {

            $apiKey = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_APIKEY);
            if ($apiKey) {
                $mailchimpStoreId = Mage::helper('mailchimp')->getStoreId();

                if ($product->getTypeId() == "simple")
                {
                    $data = $this->_buildProductData($product);

                    $parentIds = Mage::getResourceSingleton('catalog/product_type_configurable')->getParentIdsByChild($product->getId());

                    if(empty($parentIds)){
                        $parentIds = [$product->getId()];
                    }

                } else if ($product->getTypeId() == "configurable")
                {
                    throw new Mailchimp_Error('Mailchimp root products can not be updated');

                } else {
                    //@toDo bundle
                    //@toDo grouped
                    //@toDo virtual
                    //@toDo download

                    throw new Mailchimp_Error('These type of products are not supported');
                }

                $mailchimpApi = new Ebizmarts_Mailchimp($apiKey);

                foreach($parentIds as $parentId)
                {
                    $mailchimpApi->ecommerce->products->variants->modify(
                        $mailchimpStoreId,
                        $parentId,
                        $data["id"],
                        $data["title"],
                        $data["url"],
                        $data["sku"],
                        $data["price"],
                        $data["inventory_quantity"],
                        $data["image_url"],
                        $data["backorders"],
                        $data["visibility"]
                    );
                }

                //update product delta
                $product->setData("mailchimp_sync_delta", Varien_Date::now());
                $product->setData("mailchimp_sync_error", "");
                $product->save();

            }else{
                throw new Mailchimp_Error ('You must provide a MailChimp API key');
            }
        } catch (Mailchimp_Error $e)
        {
            Mage::helper('mailchimp')->log($e->getFriendlyMessage());
            
            //update product delta
            $product->setData("mailchimp_sync_delta", Varien_Date::now());
            $product->setData("mailchimp_sync_error", $e->getFriendlyMessage());
            $product->save();

        } catch (Exception $e)
        {
            Mage::helper('mailchimp')->log($e->getMessage());

            //update product delta
            $product->setData("mailchimp_sync_delta", Varien_Date::now());
            $product->setData("mailchimp_sync_error", $e->getMessage());
            $product->save();
        }
    }
}