<?php

/**
 * MailChimp For Magento
 *
 * @category  Ebizmarts_MailChimp
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     4/29/16 3:55 PM
 * @file:     Account.php
 */
class Ebizmarts_MailChimp_Model_System_Config_Source_List
{

    /**
     * Lists for API key will be stored here
     *
     * @access protected
     * @var    array Email lists for given API key
     */
    protected $_lists = array();

    /**
     * @var Ebizmarts_MailChimp_Helper_Data
     */
    protected $_helper;


    /**
     * Ebizmarts_MailChimp_Model_System_Config_Source_List constructor.
     *
     * @param  $params
     * @throws Exception
     */
    public function __construct($params)
    {
        $helper = $this->_helper = $this->makeHelper();
        $scopeArray = $helper->getCurrentScope();
        if (empty($this->_lists)) {
            $apiKey = (empty($params))
                ? $helper->getApiKey($scopeArray['scope_id'], $scopeArray['scope'])
                : $params['api_key'];
            if ($apiKey) {
                try {
                    $api = $helper->getApiByKey($apiKey);

                    //Add filter to only show the lists for the selected store when MC store selected.
                    $mcStoreId = (!empty($params))
                        ? $params['mailchimp_store_id']
                        : $helper->getMCStoreId($scopeArray['scope_id'], $scopeArray['scope']);
                    if ($mcStoreId !== '' && $mcStoreId !== null) {
                        $listId = $helper->getListIdByApiKeyAndMCStoreId($apiKey, $mcStoreId);
                        if ($listId !== false) {
                            $this->_lists['lists'][0] = $api->getLists()->getLists($listId);
                        }
                    } else {
                        $this->_lists = $api->getLists()->getLists(null, 'lists');
                    }

                    if (isset($this->_lists['lists']) && count($this->_lists['lists']) == 0) {
                        $apiKeyArray = explode('-', $apiKey);
                        $anchorUrl = 'https://' . $apiKeyArray[1] . '.admin.mailchimp.com/lists/new-list/';
                        $htmlAnchor = '<a target="_blank" href="' . $anchorUrl . '">' . $anchorUrl . '</a>';
                        $message = 'Please create an audience at ' . $htmlAnchor;
                        Mage::getSingleton('adminhtml/session')->addWarning($message);
                    }
                } catch (Ebizmarts_MailChimp_Helper_Data_ApiKeyException $e) {
                    $helper->logError($e->getMessage());
                } catch (MailChimp_Error $e) {
                    $helper->logError($e->getFriendlyMessage());
                }
            }
        }
    }

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $helper = $this->getHelper();
        $lists = array();
        $mcLists = $this->getMCLists();
        if (isset($mcLists['lists'])) {
            if (count($mcLists['lists']) > 1) {
                $lists[] = array('value' => '', 'label' => $helper->__('--- Select a Mailchimp Audience ---'));
            }

            foreach ($mcLists['lists'] as $list) {
                $memberCount = $list['stats']['member_count'];
                $memberText = $helper->__('members');
                $label = $list['name'] . ' (' . $memberCount . ' ' . $memberText . ')';
                $lists[] = array('value' => $list['id'], 'label' => $label);
            }
        } else {
            $lists[] = array('value' => '', 'label' => $helper->__('--- No data ---'));
        }

        return $lists;
    }

    /**
     * @return Ebizmarts_MailChimp_Helper_Data
     */
    protected function getHelper($type='')
    {
        return $this->_helper;
    }

    /**
     * @return Ebizmarts_MailChimp_Helper_Data
     */
    protected function makeHelper()
    {
        return Mage::helper('mailchimp');
    }

    /**
     * @return array|mixed
     */
    protected function getMCLists()
    {
        return $this->_lists;
    }
}
