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
     * Load lists and store on class property
     */
    public function __construct()
    {
        $helper = $this->_helper = $this->makeHelper();
        $scopeArray = $helper->getCurrentScope();
        if (empty($this->_lists)) {
            $apiKey = $helper->getApiKey($scopeArray['scope_id'], $scopeArray['scope']);
            if ($apiKey) {
                try {
                    $api = $helper->getApi($scopeArray['scope_id'], $scopeArray['scope']);
                    $this->_lists = $api->lists->getLists(null, 'lists', null, 100);
                    if (isset($this->_lists['lists']) && count($this->_lists['lists']) == 0) {
                        $apiKeyArray = explode('-', $apiKey);
                        $anchorUrl = 'https://' . $apiKeyArray[1] . '.admin.mailchimp.com/lists/new-list/';
                        $htmlAnchor = '<a target="_blank" href="' . $anchorUrl . '">' . $anchorUrl . '</a>';
                        $message = 'Please create a list at '. $htmlAnchor;
                        Mage::getSingleton('adminhtml/session')->addWarning($message);
                    }
                } catch (Ebizmarts_MailChimp_Helper_Data_ApiKeyException $e) {
                    $helper->logError($e->getMessage());
                } catch(MailChimp_Error $e) {
                    Mage::getSingleton('adminhtml/session')->addError($e->getFriendlyMessage());
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
        $scopeArray = $helper->getCurrentScope();
        $lists = array();
        $listId = $helper->getGeneralList($scopeArray['scope_id'], $scopeArray['scope']);
        $mcLists = $this->getMCLists();
        if (isset($mcLists['lists'])) {
            foreach ($mcLists['lists'] as $list) {
                if($listId == $list['id']) {
                    $memberCount = $list['stats']['member_count'];
                    $memberText = $helper->__('members');
                    $label = $list['name'] . ' (' . $memberCount . ' ' . $memberText . ')';
                    $lists[] = array('value' => $list['id'], 'label' => $label);
                }
            }
        } else {
            $lists[] = array('value' => '', 'label' => $helper->__('--- No data ---'));
        }
        return $lists;
    }

    /**
     * @return Ebizmarts_MailChimp_Helper_Data
     */
    protected function getHelper()
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
