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
     * Ebizmarts_MailChimp_Model_System_Config_Source_CustomerGroup constructor.
     *
     * @param  $params
     * @throws Mage_Core_Exception
     */
    public function __construct($params)
    {
        $helper = $this->makeHelper();
        $scopeArray = $helper->getCurrentScope();
        $apiKey = (!empty($params))
            ? $params['api_key']
            : $helper->getApiKey($scopeArray['scope_id'], $scopeArray['scope']);
        $listId = (!empty($params))
            ? $params['list_id']
            : $helper->getGeneralList($scopeArray['scope_id'], $scopeArray['scope']);

        if (!empty($apiKey) && !empty($listId)) {
            $this->_categories = $helper->getListInterestCategoriesByKeyAndList($apiKey, $listId);
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
        if (!empty($this->_categories)) {
            foreach ($this->_categories as $category) {
                $groups[] = array('value'=> $category['id'], 'label' => $category['title']);
            }
        } else {
            $groups[] = array('value' => '', 'label' => $helper->__('--- No data ---'));
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
