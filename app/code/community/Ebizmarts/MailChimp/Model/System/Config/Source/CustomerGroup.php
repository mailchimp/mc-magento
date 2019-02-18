<?php

/**
 * Author : Ebizmarts <info@ebizmarts.com>
 * Date   : 8/29/14
 * Time   : 3:36 PM
 * File   : CustomerGroup.php
 * Module : magemonkey
 */
class Ebizmarts_MailChimp_Model_System_Config_Source_CustomerGroup
{
    protected $_categories = null;

    /**
     * Load lists and store on class property
     */
    public function __construct()
    {
        $helper = $this->makeHelper();
        $scopeArray = $helper->getCurrentScope();
        if ($helper->isSubscriptionEnabled($scopeArray['scope_id'], $scopeArray['scope'])) {
            $this->_categories = $helper->getListInterestCategories($scopeArray['scope_id'], $scopeArray['scope']);
        }
    }

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $groups = array();
        $helper = $this->makeHelper();
        if (is_array($this->_categories)) {
            foreach ($this->_categories as $category) {
                $groups[] = array('value'=> $category['id'], 'label' => $category['title']);
            }
        } else {
            $groups []= array('value' => '', 'label' => $helper->__('--- No data ---'));
        }
        return $groups;
    }

    /**
     * @return Ebizmarts_MailChimp_Helper_Data
     */
    protected function makeHelper()
    {
        return Mage::helper('mailchimp');
    }

}
