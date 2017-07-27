<?php

/**
 * mailchimp-lib Magento Component
 *
 * @category  Ebizmarts
 * @package   mailchimp-lib
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Ebizmarts_MailChimp_Model_Api_Products
{
    const BATCH_LIMIT = 100;
    private $_parentImageUrl = null;
    /** @var Mage_Catalog_Model_Product_Type_Configurable */
    private $productTypeConfigurable;

    private $mailchimpHelper;
    private $visibilityOptions;
    private $productTypeConfigurableResource;
    public static $noChildrenIds = array(0 => array());

    public function __construct()
    {
        $this->productTypeConfigurable = Mage::getModel('catalog/product_type_configurable');
        $this->productTypeConfigurableResource = Mage::getResourceSingleton('catalog/product_type_configurable');
        $this->mailchimpHelper = Mage::helper('mailchimp');
        $this->visibilityOptions = Mage::getModel('catalog/product_visibility')->getOptionArray();
    }

    public function createBatchJson($mailchimpStoreId, $magentoStoreId)
    {
        if (Mage::helper('catalog/category_flat')->isEnabled()) {
            Mage::app()->getStore($magentoStoreId)
                ->setConfig(Mage_Catalog_Helper_Category_Flat::XML_PATH_IS_ENABLED_FLAT_CATALOG_CATEGORY, 0)
                ->setConfig(Mage_Catalog_Helper_Product_Flat::XML_PATH_USE_PRODUCT_FLAT, 0);
        }
        $collection = $this->makeProductsNotSentCollection($magentoStoreId);
        $this->joinMailchimpSyncData($mailchimpStoreId, $collection);
        $batchArray = array();

        $batchId = $this->makeBatchId($magentoStoreId);
        $counter = 0;
        foreach ($collection as $product) {

            if ($product->getMailchimpSyncDeleted()) {
                $batchArray = array_merge($this->buildProductDataRemoval($product, $batchId, $mailchimpStoreId, $magentoStoreId), $batchArray);
            }

            if ($this->shouldSendProductUpdate($magentoStoreId, $product)) {
                $batchArray = array_merge($this->_buildUpdateProductRequest($product, $batchId, $mailchimpStoreId, $magentoStoreId), $batchArray);
                $counter = count($batchArray);
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
        if ($this->isSimpleProduct($product)) {
            $variantProducts[] = $product;
        } else if ($this->isConfigurableProduct($product)) {
            $variantProducts[] = $product;

            $collection = $this->makeProductChildrenCollection($magentoStoreId);

            $childProducts = $this->getConfigurableChildrenIds($product);
            $collection->addAttributeToFilter("entity_id", array("in" => $childProducts));

            foreach ($collection as $childProduct) {
                $variantProducts[] = $childProduct;
            }

        } else if ($this->isVirtualProduct($product) || $this->isDownloadableProduct($product)) {
            $variantProducts = array();
            $variantProducts[] = $product;
        } else {
            //@TODO bundle
            return array();
        }

        $bodyData = $this->_buildProductData($product, false, $variantProducts);
        try {
            $body = json_encode($bodyData);
        } catch (Exception $e) {
            //json encode failed
            $this->getMailChimpHelper()->logError("Product " . $product->getId() . " json encode failed", $magentoStoreId);
            return array();
        }

        $data = array();
        $data['method'] = "POST";
        $data['path'] = "/ecommerce/stores/" . $mailchimpStoreId . "/products";
        $data['operation_id'] = $batchId . '_' . $product->getId();
        $data['body'] = $body;
        return $data;
    }

    protected function _buildUpdateProductRequest($product, $batchId, $mailchimpStoreId, $magentoStoreId)
    {
        $operations = array();
        if ($this->isSimpleProduct($product) || $this->isVirtualProduct($product) || $this->isDownloadableProduct($product)) {
            $data = $this->_buildProductData($product);

            $parentIds = $this->productTypeConfigurableResource->getParentIdsByChild($product->getId());

            if (empty($parentIds)) {
                $parentIds = array($product->getId());
            }

            //add or update variant
            foreach ($parentIds as $parentId) {
                $productSyncData = Mage::helper('mailchimp')->getEcommerceSyncDataItem($parentId, Ebizmarts_MailChimp_Model_Config::IS_PRODUCT, $mailchimpStoreId);
                if ($productSyncData->getMailchimpSyncDelta() && $productSyncData->getMailchimpSyncDelta() > Mage::helper('mailchimp')->getEcommMinSyncDateFlag($magentoStoreId) && $productSyncData->getMailchimpSyncError() == '') {
                    $variendata = array();
                    $variendata["id"] = $data["id"];
                    $variendata["title"] = $data["title"];
                    $variendata["url"] = $data["url"];
                    $variendata["sku"] = $data["sku"];
                    $variendata["price"] = $data["price"];
                    $variendata["inventory_quantity"] = $data["inventory_quantity"];
                    $this->_parentImageUrl = Mage::helper('mailchimp')->getImageUrlById($parentId);
                    $dataImageUrl = (isset($data["image_url"])) ? $data["image_url"] : null;
                    $imageUrl = Mage::helper('mailchimp')->getMailChimpProductImageUrl($this->_parentImageUrl, $dataImageUrl);
                    if ($imageUrl) {
                        $variendata["image_url"] = $imageUrl;
                    }

                    $this->_parentImageUrl = null;
                    $variendata["backorders"] = $data["backorders"];
                    $variendata["visibility"] = $data["visibility"];
                    $productdata = array();
                    $productdata['method'] = "PUT";
                    $productdata['path'] = "/ecommerce/stores/" . $mailchimpStoreId . "/products/" . $parentId . '/variants/' . $data['id'];
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
        }

        return $operations;
    }

    protected function _buildProductData($product, $isVariant = true, $variants = array())
    {
        $data = array();

        //data applied for both root and varient products
        $data["id"] = $product->getId();
        $data["title"] = $product->getName();
        $data["url"] = $product->getProductUrl();

        //image
        $imageUrl = $this->getMailChimpHelper()->getMailChimpProductImageUrl($this->_parentImageUrl, $this->getMailChimpHelper()->getImageUrlById($product->getId()));
        if ($imageUrl) {
            $data["image_url"] = $imageUrl;
        }

        //missing data
        $data["published_at_foreign"] = "";

        if ($isVariant) {
            $data += $this->getProductVariantData($product);
        } else {
            //this is for a root product
            if ($product->getDescription()) {
                $data["description"] = $product->getDescription();
            }

            //mailchimp product type (magento category)
            $categoryId = $product->getCategoryId();
            if ($categoryId) {
                $category = Mage::getResourceModel('catalog/category')->checkId($categoryId);
                $data["type"] = $category->getName();
                $data["vendor"] = $data["type"];
            }

            //missing data
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
     * Get stores to update and call update function after modification.
     *
     * @param $productId
     * @param $storeId
     */
    public function update($productId, $storeId)
    {
        if ($storeId == 0) {
            $stores = Mage::app()->getStores();
            foreach ($stores as $curStoreId => $curStore) {
                $this->_updateIfEnabled($productId, $curStoreId);
            }
        } else {
            $this->_updateIfEnabled($productId, $storeId);
        }
    }

    /**
     * Update product sync data if
     *
     * @param $productId
     * @param $storeId
     */
    protected function _updateIfEnabled($productId, $storeId)
    {
        if ($this->getMailChimpHelper()->isEcomSyncDataEnabled($storeId)) {
            $mailchimpStoreId = $this->getMailChimpHelper()->getMCStoreId($storeId);
            $this->_updateSyncData($productId, $mailchimpStoreId, null, null, 1, null, true);
        }
    }

    /**
     * Return products belonging to an order or a cart in a valid format to be sent to MailChimp.
     *
     * @param  $order
     * @param  $mailchimpStoreId
     * @param  $magentoStoreId
     * @return array
     */
    public function sendModifiedProduct($order, $mailchimpStoreId, $magentoStoreId)
    {
        $data = array();
        $batchId = $this->makeBatchId($magentoStoreId);
        $items = $order->getAllVisibleItems();
        foreach ($items as $item) {
            $product = Mage::getModel('catalog/product')->load($item->getProductId());
            $productSyncData = $this->getMailChimpHelper()->getEcommerceSyncDataItem($product->getId(), Ebizmarts_MailChimp_Model_Config::IS_PRODUCT, $mailchimpStoreId);
            if ($product->getId() != $item->getProductId() || $this->isBundleProduct($product) || $this->isGroupedProduct($product)) {
                if ($product->getId()) {
                    $this->_updateSyncData($product->getId(), $mailchimpStoreId, Varien_Date::now(), "This product type is not supported on MailChimp.");
                }

                continue;
            }

            if ($productSyncData->getMailchimpSyncModified() && $productSyncData->getMailchimpSyncDelta() > Mage::helper('mailchimp')->getEcommMinSyncDateFlag($magentoStoreId)) {
                $data[] = $this->_buildUpdateProductRequest($product, $batchId, $mailchimpStoreId, $magentoStoreId);
                $this->_updateSyncData($product->getId(), $mailchimpStoreId, Varien_Date::now());
            } elseif (!$productSyncData->getMailchimpSyncDelta() || $productSyncData->getMailchimpSyncDelta() < Mage::helper('mailchimp')->getEcommMinSyncDateFlag($magentoStoreId)) {
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
        $this->getMailChimpHelper()->saveEcommerceSyncData(
            $productId,
            Ebizmarts_MailChimp_Model_Config::IS_PRODUCT,
            $mailchimpStoreId,
            $syncDelta,
            $syncError,
            $syncModified,
            $syncDeleted,
            null,
            $saveOnlyIfexists
        );
    }

    public function buildProductDataRemoval($product, $batchId, $mailchimpStoreId)
    {
        //@Todo handle product removal
        return array();

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

    /**
     * @param $magentoStoreId
     * @return string
     */
    public function makeBatchId($magentoStoreId)
    {
        $batchId = 'storeid-' . $magentoStoreId . '_' . Ebizmarts_MailChimp_Model_Config::IS_PRODUCT;
        $batchId .= '_' . Mage::helper('mailchimp')->getDateMicrotime();

        return $batchId;
    }

    /**
     * @param $magentoStoreId
     * @return Mage_Catalog_Model_Resource_Product_Collection
     */
    public function makeProductsNotSentCollection($magentoStoreId)
    {
        /**
         * @var Mage_Catalog_Model_Resource_Product_Collection $collection
         */
        $collection = $this->getProductResourceCollection();

        $collection->addStoreFilter($magentoStoreId);

        $this->joinQtyAndBackorders($collection);

        $this->joinCategoryId($collection);

        $this->joinProductAttributes($collection);

        $collection->getSelect()->group("e.entity_id");
        $collection->getSelect()->limit(self::BATCH_LIMIT);

        return $collection;
    }

    /**
     * @return string
     */
    public function getSyncdataTableName()
    {
        $mailchimpTableName = Mage::getSingleton('core/resource')->getTableName('mailchimp/ecommercesyncdata');

        return $mailchimpTableName;
    }

    /**
     * @param $magentoStoreId
     * @param $product
     * @return bool
     */
    protected function shouldSendProductUpdate($magentoStoreId, $product)
    {
        return $product->getMailchimpSyncModified() && $product->getMailchimpSyncDelta() && $product->getMailchimpSyncDelta() > Mage::helper('mailchimp')->getEcommMinSyncDateFlag($magentoStoreId) && $product->getMailchimpSyncError() == '';
    }

    /**
     * @param $product
     * @return bool
     */
    protected function isSimpleProduct($product)
    {
        return $product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_SIMPLE;
    }

    /**
     * @param $product
     * @return bool
     */
    protected function isVirtualProduct($product)
    {
        return $product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_VIRTUAL;
    }

    /**
     * @param $product
     * @return bool
     */
    protected function isDownloadableProduct($product)
    {
        return $product->getTypeId() == "downloadable";
    }

    /**
     * @param $product
     * @return bool
     */
    protected function isBundleProduct($product)
    {
        return $product->getTypeId() == 'bundle';
    }

    /**
     * @param $product
     * @return bool
     */
    protected function isGroupedProduct($product)
    {
        return $product->getTypeId() == 'grouped';
    }

    /**
     * @param $product
     * @return bool
     */
    protected function isConfigurableProduct($product)
    {
        return $product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE;
    }

    /**
     * @param $collection
     */
    protected function joinProductAttributes($collection)
    {
        $attributeCodes = array("name", "visibility", "description", "price");
        $config = Mage::getSingleton("eav/config");
        foreach ($attributeCodes as $_code) {
            $attributeName = $config->getAttribute("catalog_product", $_code);

            $attribteTableName = "product_attribute_" . $_code;

            $collection->getSelect()->join(
                array($attribteTableName => $attributeName->getBackendTable()),
                'e.entity_id = ' . $attribteTableName . '.entity_id',
                array('value AS ' . $_code)
            )->where(
                $attribteTableName . ".attribute_id = ?",
                $attributeName->getId()
            );
        }
    }

    /**
     * @param $collection
     */
    protected function joinQtyAndBackorders($collection)
    {
        $collection->joinField(
            'qty', 'cataloginventory/stock_item', 'qty', 'product_id=entity_id',
            '{{table}}.stock_id=1', 'left'
        );

        $collection->joinField(
            'backorders', 'cataloginventory/stock_item', 'backorders', 'product_id=entity_id',
            '{{table}}.stock_id=1', 'left'
        );
    }

    /**
     * @param $mailchimpStoreId
     * @param $collection
     */
    protected function joinMailchimpSyncData($mailchimpStoreId, $collection)
    {
        $joinCondition = "m4m.related_id = e.entity_id and m4m.type = '%s' AND m4m.mailchimp_store_id = '%s'";
        $mailchimpTableName = $this->getSyncdataTableName();
        $collection->getSelect()->joinLeft(
            array("m4m" => $mailchimpTableName),
            sprintf($joinCondition, Ebizmarts_MailChimp_Model_Config::IS_PRODUCT, $mailchimpStoreId), array(
                "m4m.related_id",
                "m4m.type",
                "m4m.mailchimp_store_id",
                "m4m.mailchimp_sync_delta",
                "m4m.mailchimp_sync_modified"
            )
        );
        $collection->getSelect()->where("m4m.mailchimp_sync_delta IS null OR m4m.mailchimp_sync_modified = 1");
    }

    /**
     * @param $collection
     */
    protected function joinCategoryId($collection)
    {
        $collection->joinField(
            'category_id', 'catalog/category_product', 'category_id', 'product_id = entity_id', null,
            'left'
        );
    }

    /**
     * @param $product
     * @return mixed
     */
    protected function getProductVariantData($product)
    {
        $data = array();
        $data["sku"]   = $product->getSku();
        $data["price"] = (float)$product->getPrice();

        //stock
        $data["inventory_quantity"] = (int)$product->getQty();
        $data["backorders"] = (string)$product->getBackorders();


        $data["visibility"] = $this->visibilityOptions[$product->getVisibility()];

        return $data;
    }

    /**
     * @return Mage_Catalog_Model_Resource_Product_Collection
     */
    protected function getProductResourceCollection()
    {
        return Mage::getResourceModel('catalog/product_collection');
    }

    /**
     * @param $product
     * @return array
     */
    protected function getConfigurableChildrenIds($product)
    {
        $childrenIds = $this->getChildrenIdsForConfigurable($product);

        if ($childrenIds === self::$noChildrenIds) {
            return array();
        }

        return $childrenIds;
    }

    /**
     * @param $magentoStoreId
     * @return Mage_Catalog_Model_Resource_Product_Collection
     */
    protected function makeProductChildrenCollection($magentoStoreId)
    {
        return $this->makeProductsNotSentCollection($magentoStoreId);
    }

    /**
     * @param $product
     * @return array
     */
    protected function getChildrenIdsForConfigurable($product)
    {
        return $this->productTypeConfigurable->getChildrenIds($product->getId());
    }

    /**
     * @return Ebizmarts_MailChimp_Helper_Data
     */
    protected function getMailChimpHelper()
    {
        return $this->mailchimpHelper;
    }
}
