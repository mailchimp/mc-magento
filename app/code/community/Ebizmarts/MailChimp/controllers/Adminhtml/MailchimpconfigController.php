<?php
/**
 * Created by PhpStorm.
 * User: gonzalo
 * Date: 3/2/18
 * Time: 1:36 PM
 */
class Ebizmarts_MailChimp_Adminhtml_MailchimpconfigController extends Mage_Adminhtml_Controller_Action
{
    public function getListsAction()
    {
        $return = array();
        $params = $this->getRequest()->getParams();
        $apiKey = $params['apikey'];
        $helper = Mage::helper('mailchimp');
        $api = $helper->getApiByKey($apiKey);
        $lists = $api->lists->getLists(null, 'lists', null, 100);
        if (is_array($lists)) {
            $return[] = array('value' => '', 'label' => $helper->__('--- Select a list ---'));
            foreach ($lists['lists'] as $list) {
                $memberCount = $list['stats']['member_count'];
                $memberText = $helper->__('members');
                $label = $list['name'] . ' (' . $memberCount . ' ' . $memberText . ')';
                $return [] = array('value' => $list['id'], 'label' => $label);
            }
        } else {
            $return [] = array('value' => '', 'label' => $helper->__('--- No data ---'));
        }

        $response = $this->getResponse();
        $response->setHeader('Content-type', 'application/json');
        $response->setBody(json_encode($return, JSON_PRETTY_PRINT));
        return;
    }
    public function getDetailsAction()
    {
        $options = array();
        $params = $this->getRequest()->getParams();
        $apiKey = $params['apikey'];
        $helper = Mage::helper('mailchimp');
        $api = $helper->getApiByKey($apiKey);
        $apiInfo = $api->root->info('account_name,total_subscribers');
        if (isset($apiInfo['account_name'])) {
            $options['username'] = array('label' => __('User name:'), 'value' => $apiInfo['account_name']);
            $options['total_subscribers'] = array('label' => __('Total Account Subscribers:'), 'value' => $apiInfo['total_subscribers']);
        }



        $response = $this->getResponse();
        $response->setHeader('Content-type', 'application/json');
        $response->setBody(json_encode($options, JSON_PRETTY_PRINT));
        return;

    }
    protected function _isAllowed()
    {
        $acl = 'system/config/mailchimp';
        return Mage::getSingleton('admin/session')->isAllowed($acl);
    }
}