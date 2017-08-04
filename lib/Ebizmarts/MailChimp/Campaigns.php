<?php

/**
 * mailchimp-lib Magento Component
 *
 * @category  Ebizmarts
 * @package   mailchimp-lib
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     4/29/16 3:55 PM
 * @file:     Campaigns.php
 */
class MailChimp_Campaigns extends MailChimp_Abstract
{
    /**
     * @var MailChimp_CampaignsContent
     */
    public $content;
    /**
     * @var MailChimp_CampaignsFeedback
     */
    public $feedback;
    /**
     * @var MailChimp_CampaignsSendChecklist
     */
    public $sendChecklist;

    public function get($id = null, $fields = null, $excludeFields = null, $count = null, $offset = null,
                        $type = null, $status = null, $beforeSendTime = null, $sinceSendTime = null, $beforeCreateTime = null,
                        $sinceCreateTime = null, $listId = null, $folderId = null, $sortField = null, $sortDir = null)
    {
        $_params = array();
        if ($fields) {
            $_params['fields'] = $fields;
        }
        if ($excludeFields) {
            $_params['exclude_fields'] = $excludeFields;
        }
        if ($count) {
            $_params['count'] = $count;
        }
        if ($offset) {
            $_params['offset'] = $offset;
        }
        if ($type) {
            $_params['type'] = $type;
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
        if ($listId) {
            $_params['list_id'] = $listId;
        }
        if ($folderId) {
            $_params['folder_id'] = $folderId;
        }
        if ($sortField) {
            $_params['sort_field'] = $sortField;
        }
        if ($sortDir) {
            $_params['sort_dir'] = $sortDir;
        }
        if ($id) {
            return $this->_master->call('campaigns/' . $id, $_params, Ebizmarts_MailChimp::GET);
        } else {
            return $this->_master->call('campaigns', $_params, Ebizmarts_MailChimp::GET);
        }
    }
}