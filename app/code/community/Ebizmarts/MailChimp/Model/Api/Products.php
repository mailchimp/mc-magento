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

    public function createBatchJson($mailchimpStoreId)
    {
        //create missing products first
        $collection = Mage::getModel('catalog/product')->getCollection()
            ->addAttributeToSelect('id')
            ->addAttributeToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
            ->addAttributeToFilter(
                array(
                array('attribute' => 'mailchimp_sync_delta', 'null' => true),
                array('attribute' => 'mailchimp_sync_delta', 'eq' => ''),
                array('attribute' => 'mailchimp_sync_delta', 'lt' => Mage::helper('mailchimp')->getMCMinSyncDateFlag()),
                array('attribute' => 'mailchimp_sync_modified', 'eq' => 1)
                ), '', 'left'
            );
        $collection->getSelect()->limit(self::BATCH_LIMIT);

        $batchArray = array();
        $batchId = Ebizmarts_MailChimp_Model_Config::IS_PRODUCT . '_' . date('Y-m-d-H-i-s');

        $counter = 0;
        foreach ($collection as $item) {
            $product = Mage::getModel('catalog/product')->load($item->getId());

            //define variants and root products
            if ($product->getMailchimpSyncModified()&&$product->getMailchimpSyncDelta()) {
                $batchArray = array_merge($this->_buildOldProductRequest($product, $batchId, $mailchimpStoreId), $batchArray);
                $counter = (count($batchArray));
                $product->setData("mailchimp_sync_delta", Varien_Date::now());
                $product->setData("mailchimp_sync_error", "");
                $product->setData('mailchimp_sync_modified', 0);
                $product->setMailchimpUpdateObserverRan(true);
                $product->save();
            } else {
                $data = $this->_buildNewProductRequest($product, $batchId, $mailchimpStoreId);
            }

            if (!empty($data)) {
                $batchArray[$counter] = $data;
                $counter++;

                //update product delta
                $product->setData("mailchimp_sync_delta", Varien_Date::now());
                $product->setData("mailchimp_sync_error", "");
                $product->setData('mailchimp_sync_modified', 0);
                $product->setMailchimpUpdateObserverRan(true);
                $product->save();
            } else {
                continue;
            }
        }
        return $batchArray;
    }
    protected function _buildNewProductRequest($product,$batchId,$mailchimpStoreId)
    {
        $variantProducts = array();
        if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_SIMPLE) {
            $variantProducts[] = $product;
        } else if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
            //get children
            $childProducts = Mage::getModel('catalog/product_type_configurable')->getChildrenIds($product->getId());

            //add itself as variant
            $variantProducts[] = $product;

            if (count($childProducts[0])) {
                foreach ($childProducts[0] as $childId) {
                    $variantProducts[] = Mage::getModel('catalog/product')->load($childId);
                }
            }
        } else if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_VIRTUAL || $product->getTypeId() == "downloadable") {
            $variantProducts = array();
            $variantProducts[] = $product;
        } else {
            // don't need to send the grouped products
            //@toDo bundle
            return array();
        }

        $bodyData = $this->_buildProductData($product, false, $variantProducts);
        try {
            $body = json_encode($bodyData);

        } catch (Exception $e) {
            //json encode failed
            Mage::helper('mailchimp')->logError("Product " . $product->getId() . " json encode failed");
            return array();
        }
        $data = array();
        $data['method'] = "POST";
        $data['path'] = "/ecommerce/stores/" . $mailchimpStoreId . "/products";
        $data['operation_id'] = $batchId . '_' . $product->getId();
        $data['body'] = $body;
        return $data;
    }
    protected  function _buildOldProductRequest($product,$batchId,$mailchimpStoreId)
    {
        $operations = array();
        if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_SIMPLE || $product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_VIRTUAL || $product->getTypeId() == "downloadable") {
            $data = $this->_buildProductData($product);

            $parentIds = Mage::getResourceSingleton('catalog/product_type_configurable')->getParentIdsByChild($product->getId());

            if (empty($parentIds)) {
                $parentIds = array($product->getId());
            }

            //add or update variant
            foreach ($parentIds as $parentId) {
                $variendata = array();
                //$variendata["id"] = $data["id"];
                $variendata["title"] = $data["title"];
                $variendata["url"] = $data["url"];
                $variendata["sku"] = $data["sku"];
                $variendata["price"] = $data["price"];
                $variendata["inventory_quantity"] = $data["inventory_quantity"];
                $variendata["image_url"] = $data["image_url"];
                $variendata["backorders"] = $data["backorders"];
                $variendata["visibility"] = $data["visibility"];
                $productdata = array();
                $productdata['method'] = "PUT";
                $productdata['path'] = "/ecommerce/stores/" . $mailchimpStoreId . "/products/".$parentId.'/variants/'.$data['id'];
                $productsdata['operation_id'] = $batchId . '_' . $parentId;
                try {
                    $body = json_encode($variendata);

                } catch (Exception $e) {
                    //json encode failed
                    Mage::helper('mailchimp')->logError("Product " . $product->getId() . " json encode failed");
                    continue;
                }
                $productdata['body'] = $body;
                $operations[] = $productdata;
            }

        }
        return $operations;
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
        $data["image_url"] = $productMediaConfig->getMediaUrl($product->getImage());

        //missing data
        $data["published_at_foreign"] = "";

        if ($isVarient) {
            //this is for a varient product
            $data["sku"] = $product->getSku();
            $data["price"] = $product->getPrice();

            //stock
            $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
            $data["inventory_quantity"] = (int)$stock->getQty();
            $data["backorders"] = (string)$stock->getBackorders();

            $data["visibility"] = $product->getVisibility();

        } else {
            //this is for a root product
            if($product->getDescription()) {
                $data["description"] = $product->getDescription();
            }

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
            $data["variants"] = array();
            foreach ($variants as $variant) {
                $data["variants"][] = $this->_buildProductData($variant);
            }
        }

        return $data;
    }
    public function update($product)
    {
        if (Mage::helper('mailchimp')->isEcomSyncDataEnabled()) {
            $product->setData('mailchimp_sync_error', '');
            $product->setData('mailchimp_sync_modified', 1);
        }
    }
}