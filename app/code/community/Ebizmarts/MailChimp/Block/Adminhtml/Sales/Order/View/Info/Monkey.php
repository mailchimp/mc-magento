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

    /**
     * @var string $campaignName
     */
    protected $campaignName = null;

    /**
     * @var Mage_Sales_Model_Order $order
     */
    protected $order = null;

    public function isReferred()
    {
        $order = $this->getCurrentOrder();
        $ret = false;
        if ($order->getMailchimpAbandonedcartFlag() || $order->getMailchimpCampaignId()) {
            $ret = true;
        }

        return $ret;
    }

    /**
     * @return string
     */
    public function getCampaignId()
    {
        $order = $this->getCurrentOrder();
        return $order->getMailchimpCampaignId();
    }

    /**
     * @return string
     */
    public function getCampaignName()
    {
        if (!$this->campaignName) {
            $campaignId = $this->getCampaignId();
            $order = $this->getCurrentOrder();
            $storeId = $order->getStoreId();
            $helper = $this->getMailChimpHelper();

            if ($helper->isEcomSyncDataEnabled($storeId)) {
                $this->campaignName = $helper->getMailChimpCampaignNameById($campaignId, $storeId);
            }
        }

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
     * @return Mage_Sales_Model_Order
     */
    protected function getCurrentOrder()
    {
        if (!$this->order) {
            $this->order = Mage::registry('current_order');
        }
        return $this->order;
    }

    /**
     * Return true if campaign data is available with the current api and list selected.
     *
     * @return bool
     */
    public function isDataAvailable()
    {
        $dataAvailable = false;
        $campaignName = $this->getCampaignName();

        if ($campaignName) {
            $dataAvailable = true;
        }

        return $dataAvailable;
    }
}
