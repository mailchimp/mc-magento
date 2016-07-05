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
            ->addAttributeToSelect('mailchimp_sync_delta')
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('type_id')
            ->addAttributeToSelect('description')
            ->addAttributeToSelect('parent_product_ids')
            ->addAttributeToSelect('image')
            ->addAttributeToSelect('price')
            ->addAttributeToSelect('visibility')
            ->addAttributeToSelect('mailchimp_sync_modified')
            ->addAttributeToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
            ->addAttributeToFilter(array(
                array('attribute' => 'mailchimp_sync_delta', 'null' => true),
                array('attribute' => 'mailchimp_sync_delta', 'eq' => ''),
                array('attribute' => 'mailchimp_sync_delta', 'lt' => Mage::helper('mailchimp')->getMCMinSyncDateFlag()),
                array('attribute' => 'mailchimp_sync_modified', 'eq' => 1)
            ), '', 'left');
        $collection->getSelect()->limit(self::BATCH_LIMIT);

        $batchArray = array();
        $batchId = Ebizmarts_MailChimp_Model_Config::IS_PRODUCT . '_' . date('Y-m-d-H-i-s');

        $counter = 0;
        foreach ($collection as $product) {
            //define variants and root products
            if($product->getMailchimpSyncModified()&&!$product->getMailchimpSyncDelta()) {
                $data = $this->_buildOldProductRequest($product,$batchId,$mailchimpStoreId);
            }
            else {
                $data = $this->_buildNewProductRequest($product,$batchId,$mailchimpStoreId);
            }

            if (!empty($data)) {
                $batchArray[$counter] = $data;

                //update product delta
                $product->setData("mailchimp_sync_delta", Varien_Date::now());
                $product->setData("mailchimp_sync_error", "");
                $product->setData('mailchimp_sync_modified',0);
                $product->setMailchimpUpdateObserverRan(true);
                $product->save();
            }
            else {
                continue;
            }
            $counter += 1;
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

            if(count($childProducts[0])) {
                foreach ($childProducts[0] as $childId) {
                    $variantProducts[] = Mage::getModel('catalog/product')->load($childId);
                }
            }
        } else if($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_VIRTUAL || $product->getTypeId() == "downloadable") {
            $variantProducts = array();
            $variantProducts[] = $product;
        } else {
            // don't need to send the grouped products
            //@toDo bundle
            return array();
        }

        $data = $this->_buildProductData($product, false, $variantProducts);
        try {
            $body = json_encode($data);

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
        $data["image_url"] = $productMediaConfig->getMediaUrl($product->getImage());;

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
//            $product->setData('mailchimp_sync_delta', null);
            $product->setData('mailchimp_sync_error', '');
            $product->setData('mailchimp_sync_modified',1);
        }
    }
//    public function old_update($product)
//    {
//        try {
//
//            if (Mage::helper('mailchimp')->isEcomSyncDataEnabled()) {
//                $apiKey = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_APIKEY);
//                $mailchimpStoreId = Mage::helper('mailchimp')->getMCStoreId();
//
//                if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_SIMPLE || $product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_VIRTUAL || $product->getTypeId() == "downloadable") {
//                    $data = $this->_buildProductData($product);
//
//                    $parentIds = Mage::getResourceSingleton('catalog/product_type_configurable')->getParentIdsByChild($product->getId());
////                    $parentIds = $product->getTypeInstance()->getParentIdsByChild($product->getId());
//
//                    if (empty($parentIds)) {
//                        $parentIds = array($product->getId());
//                    }
//
//                    //add or update variant
//                    $mailchimpApi = new Ebizmarts_Mailchimp($apiKey);
//                    foreach ($parentIds as $parentId) {
//                        $mailchimpApi->ecommerce->products->variants->addOrModify(
//                            $mailchimpStoreId,
//                            $parentId,
//                            $data["id"],
//                            $data["title"],
//                            $data["url"],
//                            $data["sku"],
//                            $data["price"],
//                            $data["inventory_quantity"],
//                            $data["image_url"],
//                            $data["backorders"],
//                            $data["visibility"]
//                        );
//                    }
//
//                } else if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
//                    //check if it was never uploaded and create it
//                    if (!$product->getMailchimpSyncDelta()) {
//
//                        $dataRootProduct = $this->_buildProductData($product, false, array($product));
//
//                        $mailchimpApi = new Ebizmarts_Mailchimp($apiKey);
//                        $mailchimpApi->ecommerce->products->add(
//                            $mailchimpStoreId,
//                            $dataRootProduct["id"],
//                            $dataRootProduct["title"],
//                            $dataRootProduct["handle"],
//                            $dataRootProduct["url"],
//                            $dataRootProduct["description"],
//                            $dataRootProduct["type"],
//                            $dataRootProduct["vendor"],
//                            $dataRootProduct["image_url"],
//                            $dataRootProduct["variants"],
//                            $dataRootProduct["published_at_foreign"]
//                        );
//                    } else {
//
//                        throw new Exception('MailChimp root products can not be updated');
//                    }
//
//                } else {
//                    //@toDo bundle
//
//                    throw new Exception('These type of products are not yet supported');
//                }
//
//                //update product delta
//                $product->setData("mailchimp_sync_delta", Varien_Date::now());
//                $product->setData("mailchimp_sync_error", "");
//                $product->save();
//            }
//
//        } catch (Mailchimp_Error $e) {
//            Mage::helper('mailchimp')->logError($e->getFriendlyMessage());
//
//            //update product delta
//            $product->setData("mailchimp_sync_delta", Varien_Date::now());
//            $product->setData("mailchimp_sync_error", $e->getFriendlyMessage());
//            $product->save();
//
//        } catch
//        (Exception $e) {
//            Mage::helper('mailchimp')->logError($e->getMessage());
//
//            //update product delta
//            $product->setData("mailchimp_sync_delta", Varien_Date::now());
//            $product->setData("mailchimp_sync_error", $e->getMessage());
//            $product->save();
//        }
//    }
}