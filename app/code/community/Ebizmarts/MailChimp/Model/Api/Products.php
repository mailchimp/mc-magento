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
    private $_parentId = null;
    private $_parentUrl = null;
    private $_parentPrice = null;
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
        $this->joinMailchimpSyncData($collection, $mailchimpStoreId);
        $batchArray = array();

        $batchId = $this->makeBatchId($magentoStoreId);
        $counter = 0;
        foreach ($collection as $product) {
            if ($product->getMailchimpSyncDeleted()) {
                $batchArray = array_merge($this->buildProductDataRemoval($product, $batchId, $mailchimpStoreId, $magentoStoreId), $batchArray);
                $counter = count($batchArray);
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
            $variantProducts[] = $product;
        } else {
            //@TODO bundle
            return array();
        }

        $bodyData = $this->_buildProductData($product, $magentoStoreId, false, $variantProducts);
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
        $variantProducts = array();
        if ($this->isSimpleProduct($product) || $this->isVirtualProduct($product) || $this->isDownloadableProduct($product)) {
            $variantProducts[] = $product;
            $parentIds = $this->productTypeConfigurableResource->getParentIdsByChild($product->getId());
            foreach ($parentIds as $parentId) {
                $parent = Mage::getModel('catalog/product')->load($parentId);
                $variantProducts[] = $parent;

                $collection = $this->makeProductChildrenCollection($magentoStoreId);

                $childProducts = $this->getConfigurableChildrenIds($parent);
                $collection->addAttributeToFilter("entity_id", array("in" => $childProducts));

                foreach ($collection as $childProduct) {
                    if ($childProduct->getId() != $product->getId()) {
                        $variantProducts[] = $childProduct;
                    }
                }
                $bodyData = $this->_buildProductData($parent, $magentoStoreId, false, $variantProducts);
                try {
                    $body = json_encode($bodyData);
                } catch (Exception $e) {
                    //json encode failed
                    $this->getMailChimpHelper()->logError("Product " . $product->getId() . " json encode failed", $magentoStoreId);
                    return array();
                }

                $data = array();
                $data['method'] = "PATCH";
                $data['path'] = "/ecommerce/stores/" . $mailchimpStoreId . "/products/" . $parent->getId();
                $data['operation_id'] = $batchId . '_' . $parent->getId();
                $data['body'] = $body;
                $operations[] = $data;
            }

        } else if ($this->isConfigurableProduct($product)) {
            $variantProducts[] = $product;

            $collection = $this->makeProductChildrenCollection($magentoStoreId);

            $childProducts = $this->getConfigurableChildrenIds($product);
            $collection->addAttributeToFilter("entity_id", array("in" => $childProducts));

            foreach ($collection as $childProduct) {
                if ($childProduct->getId() != $product->getId()) {
                    $variantProducts[] = $childProduct;
                }
            }
        } else {
            //@TODO bundle
            return array();
        }
        $bodyData = $this->_buildProductData($product, $magentoStoreId, false, $variantProducts);
        try {
            $body = json_encode($bodyData);
        } catch (Exception $e) {
            //json encode failed
            $this->getMailChimpHelper()->logError("Product " . $product->getId() . " json encode failed", $magentoStoreId);
            return array();
        }

        $data = array();
        $data['method'] = "PATCH";
        $data['path'] = "/ecommerce/stores/" . $mailchimpStoreId . "/products/" . $product->getId();
        $data['operation_id'] = $batchId . '_' . $product->getId();
        $data['body'] = $body;
        $operations[] = $data;
        return $operations;
    }

    protected function getParentData($productId, $magentoStoreId)
    {
        $product = Mage::getModel('catalog/product')->load($productId);
        $data = $this->_buildProductData($product, $magentoStoreId, false);
        $data['variants'][] = array('id' => $productId);
        try {
            $body = json_encode($data);
        } catch (Exception $e) {
            //json encode failed
            Mage::helper('mailchimp')->logError("Product " . $product->getId() . " json encode failed", $magentoStoreId);
        }
        return $body;
    }

    protected function _buildProductData($product, $magentoStoreId, $isVariant = true, $variants = array())
    {
        $data = array();

        //data applied for both root and varient products
        $data["id"] = $product->getId();
        $data["title"] = ($product->getName()) ? $product->getName() : $product->getDefaultName();
        $visibility = ($product->getVisibility()) ? $product->getVisibility() : $product->getDefaultVisibility();
        $url = null;
        if ($visibility == Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE) {
            $url = $this->getNotVisibleProductUrl($product->getId(), $magentoStoreId);
        } else {
            $url = $this->getProductUrl($product, $magentoStoreId);
        }
        if (!$url) {
            $url = Mage::app()->getStore($magentoStoreId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);
        }
        $data["url"] = $url;

        //image
        $imageUrl = $this->getMailChimpImageUrl($product, $magentoStoreId);
        if ($imageUrl) {
            $data["image_url"] = $imageUrl;
        }

        //missing data
        $data["published_at_foreign"] = "";

        if ($isVariant) {
            $data += $this->getProductVariantData($product);
        } else {
            //this is for a root product
            if ($product->getDescription() || $product->getDefaultDescription()) {
                $data["description"] = ($product->getDescription()) ? $product->getDescription() : $product->getDefaultDescription();
            }

            //mailchimp product type and vendor (magento category)
            $categoryName = $this->getProductCategories($product, $magentoStoreId);
            if ($categoryName) {
                $data["type"] = $categoryName;
                $data["vendor"] = $data["type"];
            }

            //missing data
            $data["handle"] = "";

            //variants
            if (count($variants)) {
                $data["variants"] = array();
                if (isset($data["image_url"])) {
                    $this->_parentImageUrl = $data["image_url"];
                }
                $this->_parentId = $product->getId();
                if ($visibility != Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE) {
                    $this->_parentUrl = $data['url'];
                }
                $price = ((float)$product->getPrice()) ? (float)$product->getPrice() : (float)$product->getDefaultPrice();
                if ($price) {
                    $this->_parentPrice = $price;
                }

                foreach ($variants as $variant) {
                    $data["variants"][] = $this->_buildProductData($variant, $magentoStoreId);
                }

                $this->_parentImageUrl = null;
                $this->_parentPrice = null;
                $this->_parentId = null;
                $this->_parentUrl = null;
            }
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
            $this->_updateIfEnabled($productId, $storeId);
        }
        $stores = Mage::app()->getStores();
        foreach ($stores as $curStoreId => $curStore) {
            $this->_updateIfEnabled($productId, $curStoreId);
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
        $mailchimpStoreId = $this->getMailChimpHelper()->getMCStoreId($storeId);
        $this->_updateSyncData($productId, $mailchimpStoreId, null, null, 1, null, true);
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
                $data = array_merge($this->_buildUpdateProductRequest($product, $batchId, $mailchimpStoreId, $magentoStoreId), $data);
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
        $this->mailchimpHelper->addResendFilter($collection, $magentoStoreId);

        $this->joinQtyAndBackorders($collection);

        $this->joinCategoryId($collection);

        $this->joinProductAttributes($collection, $magentoStoreId);

        $collection->getSelect()->group("e.entity_id");
        $collection->getSelect()->limit($this->getBatchLimitFromConfig());

        return $collection;
    }

    /**
     * @return mixed
     */
    protected function getBatchLimitFromConfig()
    {
        $helper = $this->mailchimpHelper;
        return $helper->getProductAmountLimit();
    }

    /**
     * @return string
     */
    public function getSyncDataTableName()
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
        $helper = $this->getMailChimpHelper();
        $resendTurn = $helper->getResendTurn($magentoStoreId);
        return !$resendTurn && $product->getMailchimpSyncModified() && $product->getMailchimpSyncDelta() && $product->getMailchimpSyncDelta() > Mage::helper('mailchimp')->getEcommMinSyncDateFlag($magentoStoreId) && $product->getMailchimpSyncError() == '';
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
     * @param $magentoStoreId
     */
    public function joinProductAttributes($collection, $magentoStoreId)
    {
        $attributeCodes = array("name", "visibility", "description", "price");
        $config = Mage::getSingleton("eav/config");
        foreach ($attributeCodes as $_code) {
            $attributeName = $config->getAttribute("catalog_product", $_code);

            $collection->joinField(
                $_code, $attributeName->getBackendTable(), 'value', 'entity_id = entity_id',
                '{{table}}.store_id = ' . $magentoStoreId . ' AND {{table}}.attribute_id = ' . $attributeName->getId(), 'left'
            );

            $collection->joinField(
                'default_' . $_code, $attributeName->getBackendTable(), 'value', 'entity_id = entity_id',
                '{{table}}.store_id = 0 AND {{table}}.attribute_id = ' . $attributeName->getId(), 'left'
            );
        }
    }

    /**
     * @param $collection
     */
    public function joinQtyAndBackorders($collection)
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
     * @param $collection
     * @param $mailchimpStoreId
     */
    protected function joinMailchimpSyncData($collection, $mailchimpStoreId)
    {
        $this->joinMailchimpSyncDataWithoutWhere($collection, $mailchimpStoreId);
        $collection->getSelect()->where("m4m.mailchimp_sync_delta IS null OR m4m.mailchimp_sync_modified = 1");
    }

    /**
     * @param $collection
     */
    public function joinCategoryId($collection)
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
        $data["sku"] = $product->getSku();

        $data["price"] = (float)$this->_parentPrice;

        //stock
        $data["inventory_quantity"] = (int)$product->getQty();
        $data["backorders"] = (string)$product->getBackorders();


        $visibility = ($product->getVisibility()) ? $product->getVisibility() : $product->getDefaultVisibility();
        $data["visibility"] = $this->visibilityOptions[$visibility];

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

    /**
     * @param $collection
     * @param $mailchimpStoreId
     */
    public function joinMailchimpSyncDataWithoutWhere($collection, $mailchimpStoreId)
    {
        $joinCondition = "m4m.related_id = e.entity_id and m4m.type = '%s' AND m4m.mailchimp_store_id = '%s'";
        $mailchimpTableName = $this->getSyncDataTableName();
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
    }

    public function getNotVisibleProductUrl($childId, $magentoStoreId)
    {
        $parentId = null;
        if (!$this->_parentId) {
            $parentId = $this->getParentIds($childId);
        } else {
            $parentId = $this->_parentId;
        }
        if ($parentId) {
            $collection = $this->getProductWithAttributesById($magentoStoreId, $parentId);

            $rc = Mage::getResourceModel('catalog/product');
            if ($this->_parentUrl) {
                $url = $this->_parentUrl;
            } else {
                $path = $rc->getAttributeRawValue($parentId, 'url_path', $magentoStoreId);
                $url = Mage::getUrl($path, array('_store' => $magentoStoreId));
            }
            $tailUrl = '#';
            $count = 0;
            foreach ($collection as $attribute) {
                if ($attribute->getAttributeId()) {
                    $attributeId = $attribute->getAttributeId();
                    $attributeValue = $rc->getAttributeRawValue($childId, $attribute->getAttributeId(), $magentoStoreId);
                    if ($count > 0) {
                        $tailUrl .= '&';
                    }
                    $tailUrl .= $attributeId . '=' . $attributeValue;
                }
                $count++;
            }
            if ($tailUrl != '#') {
                $url .= $tailUrl;
            }
        } else {
            $url = null;
        }
        return $url;
    }

    public function getParentImageUrl($childId, $magentoStoreId)
    {
        $imageUrl = null;
        $parentId = null;
        if (!$this->_parentId) {
            $parentId = $this->getParentIds($childId);
        } else {
            $parentId = $this->_parentId;
        }
        if ($parentId) {
            $helper = $this->getMailChimpHelper();
            $imageUrl = $helper->getImageUrlById($parentId, $magentoStoreId);
        }
        return $imageUrl;
    }

    /**
     * @param $product
     * @param $magentoStoreId
     * @return mixed
     */
    protected function getProductUrl($product, $magentoStoreId)
    {
        $oldStoreId = Mage::app()->getStore()->getId();
        Mage::app()->setCurrentStore($magentoStoreId);
        $url = $product->getProductUrl();
        Mage::app()->setCurrentStore($oldStoreId);
        return $url;
    }

    protected function getProductCategories($product, $magentoStoreId)
    {
        $categoryIds = $product->getCategoryIds();
        $categoryNames = array();
        $categoryName = null;
        if (is_array($categoryIds) && count($categoryIds)) {
            /* @var $collection Mage_Catalog_Model_Resource_Category_Collection */
            $collection = Mage::getModel('catalog/category')->getCollection();
            $collection->addAttributeToSelect(array('name'))
                ->setStoreId($magentoStoreId)
                ->addAttributeToFilter('is_active', array('eq' => '1'))
                ->addAttributeToFilter('entity_id', array('in' => $categoryIds))
                ->addAttributeToSort('level', 'asc');

            /* @var $category Mage_Catalog_Model_Category */
            foreach ($collection as $category) {
                $categoryNames[] = $category->getName();
            }
            $categoryName = (count($categoryNames)) ? implode(" - ", $categoryNames) : 'None';
        }
        return $categoryName;
    }

    /**
     * @param $childId
     * @return mixed
     */
    protected function getParentIds($childId)
    {
        $parentId = null;
        $parentIds = Mage::getResourceSingleton('catalog/product_type_configurable')->getParentIdsByChild($childId);
        if (count($parentIds)) {
            $parentId = $parentIds[0];
        }
        return $parentId;
    }

    /**
     * @param $magentoStoreId
     * @param $parentId
     * @return Mage_Catalog_Model_Resource_Product_Collection
     */
    protected function getProductWithAttributesById($magentoStoreId, $parentId)
    {
        $tableName = Mage::getSingleton('core/resource')->getTableName('catalog/product_super_attribute');
        $eavTableName = Mage::getSingleton('core/resource')->getTableName('eav/attribute');

        $collection = $this->getProductResourceCollection();
        $collection->addStoreFilter($magentoStoreId);
        $collection->addFieldToFilter('entity_id', array('eq' => $parentId));

        $collection->getSelect()->joinLeft(
            array("super_attribute" => $tableName),
            'entity_id=super_attribute.product_id'
        );

        $collection->getSelect()->joinLeft(
            array("eav_attribute" => $eavTableName),
            'super_attribute.attribute_id=eav_attribute.attribute_id'
        );
        $collection->getSelect()->reset(Zend_Db_Select::COLUMNS)->columns('eav_attribute.attribute_id');
        return $collection;
    }

    /**
     * @param $product
     * @param $magentoStoreId
     * @return mixed|null
     */
    protected function getMailChimpImageUrl($product, $magentoStoreId)
    {
        $imageUrl = $this->getMailChimpHelper()->getMailChimpProductImageUrl($this->_parentImageUrl, $this->getMailChimpHelper()->getImageUrlById($product->getId(), $magentoStoreId));
        if (!$imageUrl) {
            $imageUrl = $this->getParentImageUrl($product->getId(), $magentoStoreId);
        }
        return $imageUrl;
    }
}
