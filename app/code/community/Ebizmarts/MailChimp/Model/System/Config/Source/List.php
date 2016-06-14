<?php
/**
 * MailChimp For Magento
 *
 * @category Ebizmarts_MailChimp
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 4/29/16 3:55 PM
 * @file: Account.php
 */
class Ebizmarts_MailChimp_Model_System_Config_Source_List
{

    /**
     * Lists for API key will be stored here
     *
     * @access protected
     * @var array Email lists for given API key
     */
    protected $_lists = null;

    /**
     * Load lists and store on class property
     */
    public function __construct()
    {
        if (is_null($this->_lists)) {
            $apiKey = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_APIKEY);
                if ($apiKey) {
                    $api = new Ebizmarts_Mailchimp($apiKey);
                    $this->_lists = $api->lists->getLists(null, 'lists', null, 100);
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
        $lists = array();

        if (is_array($this->_lists)) {
            $lists[] = array('value' => '', 'label' => Mage::helper('mailchimp')->__('--- Select a list ---'));
            foreach ($this->_lists['lists'] as $list) {
                $lists [] = array('value' => $list['id'], 'label' => $list['name'] . ' (' . $list['stats']['member_count'] . ' ' . Mage::helper('mailchimp')->__('members') . ')');
            }
        } else {
            $lists [] = array('value' => '', 'label' => Mage::helper('mailchimp')->__('--- No data ---'));
        }

        return $lists;
    }

}
