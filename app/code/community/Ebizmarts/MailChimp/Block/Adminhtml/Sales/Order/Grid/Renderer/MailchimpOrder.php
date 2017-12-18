<?php
/**
 * mc-magento Magento Component
 *
 * @category  Ebizmarts
 * @package   mc-magento
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     12/12/17 3:28 PM
 * @file:     Abandoned.php
 */
class Ebizmarts_MailChimp_Block_Adminhtml_Sales_Order_Grid_Renderer_MailchimpOrder extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $mailchimpStoreId = Mage::helper('mailchimp')->getMCStoreId();
        $scopeArray = explode('-', Mage::helper('mailchimp')->getScopeString());
        $apiKey = Mage::helper('mailchimp')->getApiKey($scopeArray[1], $scopeArray[0]);
        $datacenter = explode('-', $apiKey);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        json_decode(curl_setopt($ch, CURLOPT_URL,'https://'.$datacenter[1].'.api.mailchimp.com/3.0/ecommerce/stores/'.$mailchimpStoreId.'/orders/'.$row->getData('increment_id')));
        curl_setopt($ch, CURLOPT_USERPWD, "noname:".$apiKey);
        $content = curl_exec($ch);
        $array=json_decode($content, true);
        $status = array_search('404', $array);

        if (!$status) {
            $result = Mage::helper('mailchimp')->__('Yes');
        } else {
            $result = Mage::helper('mailchimp')->__('No');
        }

        return $result;
    }
}