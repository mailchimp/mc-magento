<?php
/**
 * mc-magento Magento Component
 *
 * @category  Ebizmarts
 * @package   mc-magento
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     30/05/18 2:46 PM
 * @file:     mysql4-upgrade-1.1.12-1.1.13.php
 */

$installer = $this;

$installer->startSetup();

$setup = new Mage_Eav_Model_Entity_Setup('core_setup');

$entityTypeId     = $setup->getEntityTypeId('customer');
$attributeSetId   = $setup->getDefaultAttributeSetId($entityTypeId);
$attributeGroupId = $setup->getDefaultAttributeGroupId($entityTypeId, $attributeSetId);

$setup->addAttribute("customer", "mailchimp_store_view",  array(
    "type"     => "int",
    "label"    => "Store View (For MailChimp)",
    "input"    => "select",
    "source"   => "mailchimp/system_config_source_mailchimpStoreView",
    "visible"  => true,
    "required" => false,
    "unique"     => false,
    "note"       => "A store view must be specified to sync this customer to MailChimp"

));

$attribute   = Mage::getSingleton("eav/config")->getAttribute("customer", "mailchimp_store_view");


$setup->addAttributeToGroup(
    $entityTypeId,
    $attributeSetId,
    $attributeGroupId,
    'mailchimp_store_view',
    '999'  //sort_order
);

$used_in_forms=array();

$used_in_forms[]="adminhtml_customer";

$attribute->setData("used_in_forms", $used_in_forms)
    ->setData("is_used_for_customer_segment", true)
    ->setData("is_system", 0)
    ->setData("is_user_defined", 1)
    ->setData("is_visible", 1)
    ->setData("sort_order", 100)
;
$attribute->save();

$installer->endSetup();
