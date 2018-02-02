<?php

/**
 * mc-magento Magento Component
 *
 * @category  Ebizmarts
 * @package   mc-magento
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     12/8/16 2:04 PM
 * @file:     Monkey.php
 */
class Ebizmarts_MailChimp_Block_Adminhtml_Sales_Order_View_Info_Monkey extends Mage_Core_Block_Template
{

    public $campaignName = null;

    public function isReferred()
    {
        $order = $this->getCurrentOrder();
        $ret = false;
        if ($order->getMailchimpAbandonedcartFlag() || $order->getMailchimpCampaignId()) {
            $ret = true;
        }

        return $ret;
    }

    public function getCampaignId()
    {
        $order = $this->getCurrentOrder();
        return $order->getMailchimpCampaignId();
    }

    public function addCampaignName()
    {
        return $this->campaignName;
    }

    /**
     * @return Ebizmarts_MailChimp_Helper_Data
     */
    protected function getMailChimpHelper()
    {
        return Mage::helper('mailchimp');
    }

    /**
     * @return mixed
     */
    protected function getCurrentOrder()
    {
        return Mage::registry('current_order');
    }

    public function isDataAvailable()
    {
        $helper = $this->getMailChimpHelper();
        $campaignId = $this->getCampaignId();
        $order = $this->getCurrentOrder();
        $storeId = $order->getStoreId();
        if ($helper->isEcomSyncDataEnabled($storeId)) {
            $this->campaignName = $helper->getMailChimpCampaignNameById($campaignId, $storeId);
        }

        $dataAvailable = false;

        if ($order->getMailchimpCampaignId() && $this->campaignName) {
            $dataAvailable = true;
        }

        return $dataAvailable;
    }
}
