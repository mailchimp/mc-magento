<?php
/**
 * mailchimp-lib Magento Component
 *
 * @category  Ebizmarts
 * @package   mailchimp-lib
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     4/29/16 3:47 PM
 * @file:     Automation.php
 */
class MailChimp_Automation extends MailChimp_Abstract
{
    /**
     * @var MailChimp_AutomationEmails
     */
    public $emails;

    public function get($id = null, $fields = null, $excludeFields = null, $status = null, $beforeSendTime = null,
                        $sinceSendTime = null, $beforeCreateTime = null, $sinceCreateTime = null)
    {
        $_params = array();
        if ($fields) {
            $_params['fields'] = $fields;
        }
        if ($excludeFields) {
            $_params['exclude_fields'] = $excludeFields;
        }
        if ($status) {
            $_params['status'] = $status;
        }
        if ($beforeSendTime) {
            $_params['before_send_time'] = $beforeSendTime;
        }
        if ($sinceSendTime) {
            $_params['since_send_time'] = $sinceSendTime;
        }
        if ($beforeCreateTime) {
            $_params['before_create_time'] = $beforeCreateTime;
        }
        if ($sinceCreateTime) {
            $_params['since_create_time'] = $sinceCreateTime;
        }
        if ($id) {
            return $this->_master->call('automations/' . $id, $_params, Ebizmarts_MailChimp::GET);
        } else {
            return $this->_master->call('automations', $_params, Ebizmarts_MailChimp::GET);
        }
    }
}