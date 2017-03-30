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
    private $_parentImageUrl = null;

    public function createBatchJson($mailchimpStoreId, $magentoStoreId)
    {
        $mailchimpTableName = Mage::getSingleton('core/resource')->getTableName('mailchimp/ecommercesyncdata');
        //create missing products first
        $collection = Mage::getModel('catalog/product')->getCollection();
        $collection->addStoreFilter($magentoStoreId);
        $collection->getSelect()->joinLeft(
            array('m4m' => $mailchimpTableName),
            "m4m.related_id = e.entity_id and m4m.type = '".Ebizmarts_MailChimp_Model_Config::IS_PRODUCT."'
            AND m4m.mailchimp_store_id = '" . $mailchimpStoreId . "'",
            array('m4m.*')
        );
        $collection->getSelect()->where(
            "m4m.mailchimp_sync_delta IS null ".
            "OR m4m.mailchimp_sync_modified = 1"
        );
        $collection->getSelect()->limit(self::BATCH_LIMIT);

        $batchArray = array();
        
        $batchId = 'storeid-' . $magentoStoreId . '_' . Ebizmarts_MailChimp_Model_Config::IS_PRODUCT . '_' . Mage::helper('mailchimp')->getDateMicrotime();
        $counter = 0;
        foreach ($collection as $item) {
            $product = Mage::getModel('catalog/product')->setStoreId($magentoStoreId)->load($item->getEntityId());
            if ($item->getMailchimpSyncDeleted()) {
                $batchArray = array_merge($this->buildProductDataRemoval($product, $batchId, $mailchimpStoreId, $magentoStoreId), $batchArray);
            }

            //define variants and root products
            if ($item->getMailchimpSyncModified() && $item->getMailchimpSyncDelta() && $item->getMailchimpSyncDelta() > Mage::helper('mailchimp')->getMCMinSyncDateFlag($magentoStoreId)) {
                $batchArray = array_merge($this->_buildOldProductRequest($product, $batchId, $mailchimpStoreId, $magentoStoreId), $batchArray);
                $counter = (count($batchArray));
                $this->_updateSyncData($product->getId(), $mailchimpStoreId, Varien_Date::now());
                continue;
            } else {
                $data = $this->_buildNewProductRequest($product, $batchId, $mailchimpStoreId, $magentoStoreId);
            }

            if (!empty($data)) {
                $batchArray[$counter] = $data;
                $counter++;

                //update product delta
                $this->_updateSyncData($product->getId(), $mailchimpStoreId, Varien_Date::now());
            } else {
                $this->_updateSyncData($product->getId(), $mailchimpStoreId, Varien_Date::now(), "This product type is not supported on MailChimp.");
            }
        }

        return $batchArray;
    }
    protected function _buildNewProductRequest($product, $batchId, $mailchimpStoreId, $magentoStoreId)
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
                    $variantProducts[] = Mage::getModel('catalog/product')->setStoreId($magentoStoreId)->load($childId);
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
            Mage::helper('mailchimp')->logError("Product " . $product->getId() . " json encode failed", $magentoStoreId);
            return array();
        }

        $data = array();
        $data['method'] = "POST";
        $data['path'] = "/ecommerce/stores/" . $mailchimpStoreId . "/products";
        $data['operation_id'] = $batchId . '_' . $product->getId();
        $data['body'] = $body;
        return $data;
    }
    protected  function _buildOldProductRequest($product, $batchId, $mailchimpStoreId, $magentoStoreId)
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
                $variendata["id"] = $data["id"];
                $variendata["title"] = $data["title"];
                $variendata["url"] = $data["url"];
                $variendata["sku"] = $data["sku"];
                $variendata["price"] = $data["price"];
                $variendata["inventory_quantity"] = $data["inventory_quantity"];
                $this->_parentImageUrl = Mage::helper('mailchimp')->getImageUrlById($parentId);
                $imageUrl = Mage::helper('mailchimp')->getMailChimpProductImageUrl($this->_parentImageUrl, $data["image_url"]);
                if ($imageUrl) {
                    $variendata["image_url"] = $imageUrl;
                }

                $this->_parentImageUrl = null;
                $variendata["backorders"] = $data["backorders"];
                $variendata["visibility"] = $data["visibility"];
                $productdata = array();
                $productdata['method'] = "PUT";
                $productdata['path'] = "/ecommerce/stores/" . $mailchimpStoreId . "/products/".$parentId.'/variants/'.$data['id'];
                $productdata['operation_id'] = $batchId . '_' . $parentId;
                try {
                    $body = json_encode($variendata);
                } catch (Exception $e) {
                    //json encode failed
                    Mage::helper('mailchimp')->logError("Product " . $product->getId() . " json encode failed", $magentoStoreId);
                    continue;
                }

                $productdata['body'] = $body;
                $operations[] = $productdata;
            }
        }

        return $operations;
    }
    
    protected function _buildProductData($product, $isVarient = true, $variants = array())
    {
        $data = array();

        //data applied for both root and varient products
        $data["id"] = $product->getId();
        $data["title"] = $product->getName();
        $data["url"] = $product->getProductUrl();

        //image
        $imageUrl = Mage::helper('mailchimp')->getMailChimpProductImageUrl($this->_parentImageUrl, Mage::helper('mailchimp')->getImageUrlById($product->getId()));
        if ($imageUrl) {
            $data["image_url"] = $imageUrl;
        }

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

            $visibilityOptions = Mage::getModel('catalog/product_visibility')->getOptionArray();
            $data["visibility"] = $visibilityOptions[$product->getVisibility()];
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
            if (isset($data["image_url"])) {
                $this->_parentImageUrl = $data["image_url"];
            }

            foreach ($variants as $variant) {
                $data["variants"][] = $this->_buildProductData($variant);
            }

            $this->_parentImageUrl = null;
        }

        return $data;
    }

    /**
     * Update product sync data after modification.
     *
     * @param $productId
     * @param $storeId
     */
    public function update($productId, $storeId)
    {
        if (Mage::helper('mailchimp')->isEcomSyncDataEnabled($storeId)) {
            $mailchimpStoreId = Mage::helper('mailchimp')->getMCStoreId($storeId);
            $this->_updateSyncData($productId, $mailchimpStoreId, null, null, 1, null, true);
        }
    }

    /**
     * Return products belonging to an order or a cart in a valid format to be sent to MailChimp.
     * 
     * @param $order
     * @param $mailchimpStoreId
     * @param $magentoStoreId
     * @return array
     */
    public function sendModifiedProduct($order, $mailchimpStoreId, $magentoStoreId)
    {
        $data = array();
        $batchId = 'storeid-' . $magentoStoreId . '_' . Ebizmarts_MailChimp_Model_Config::IS_PRODUCT . '_' . Mage::helper('mailchimp')->getDateMicrotime();
        $items = $order->getAllVisibleItems();
        foreach ($items as $item)
        {
            $product = Mage::getModel('catalog/product')->load($item->getProductId());
            $productSyncData = Mage::helper('mailchimp')->getEcommerceSyncDataItem($product->getId(), Ebizmarts_MailChimp_Model_Config::IS_PRODUCT, $mailchimpStoreId);
            if ($product->getId()!=$item->getProductId()||$product->getTypeId()=='bundle'||$product->getTypeId()=='grouped') {
                if ($product->getId()) {
                    $this->_updateSyncData($product->getId(), $mailchimpStoreId, Varien_Date::now(), "This product type is not supported on MailChimp.");
                }

                continue;
            }

            if ($productSyncData->getMailchimpSyncModified() && $productSyncData->getMailchimpSyncDelta() > Mage::helper('mailchimp')->getMCMinSyncDateFlag($magentoStoreId)) {
                $data[] = $this->_buildOldProductRequest($product, $batchId, $mailchimpStoreId, $magentoStoreId);
                $this->_updateSyncData($product->getId(), $mailchimpStoreId, Varien_Date::now());
            } elseif (!$productSyncData->getMailchimpSyncDelta() || $productSyncData->getMailchimpSyncDelta() < Mage::helper('mailchimp')->getMCMinSyncDateFlag($magentoStoreId)) {
                $data[] = $this->_buildNewProductRequest($product, $batchId, $mailchimpStoreId, $magentoStoreId);
                $this->_updateSyncData($product->getId(), $mailchimpStoreId, Varien_Date::now());
            }
        }

        return $data;
    }

    /**
     * update product sync data
     *
     * @param $productId
     * @param $mailchimpStoreId
     * @param null $syncDelta
     * @param null $syncError
     * @param int $syncModified
     * @param null $syncDeleted
     * @param bool $saveOnlyIfexists
     */
    protected function _updateSyncData($productId, $mailchimpStoreId, $syncDelta = null, $syncError = null, $syncModified = 0, $syncDeleted = null, $saveOnlyIfexists = false)
    {
        Mage::helper('mailchimp')->saveEcommerceSyncData($productId, Ebizmarts_MailChimp_Model_Config::IS_PRODUCT, $mailchimpStoreId, $syncDelta, $syncError, $syncModified, $syncDeleted, null, $saveOnlyIfexists);
    }

    public function buildProductDataRemoval($product, $batchId, $mailchimpStoreId)
    {
        //@Todo handle product removal
        $productdata = array();

        $parentIds = Mage::getResourceSingleton('catalog/product_type_configurable')->getParentIdsByChild($product->getId());

        if (empty($parentIds)) {
            $parentIds = array($product->getId());
        }

        //add or update variant
//        foreach ($parentIds as $parentId) {
//            $productdata['method'] = "DELETE";
//            $productdata['path'] = "/ecommerce/stores/" . $mailchimpStoreId . "/products/" . $parentId . '/variants/' . $data['id'];
//            $productdata['operation_id'] = $batchId . '_' . $parentId;
//        }
        return $productdata;
    }
}