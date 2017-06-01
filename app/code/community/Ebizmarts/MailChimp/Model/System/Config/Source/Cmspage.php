<?php
/**
 * mc-magento Magento Component
 *
 * @category  Ebizmarts
 * @package   mc-magento
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     7/6/16 2:56 PM
 * @file:     Cmspage.php
 */
class Ebizmarts_MailChimp_Model_System_Config_Source_Cmspage
{

    public function toOptionArray()
    {
        $collection = Mage::getResourceModel('cms/page_collection')->addOrder('title', 'asc');
        return array('checkout/cart' => "Shopping Cart (default page)") + $collection->toOptionIdArray();
    }

}
