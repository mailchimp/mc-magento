<?php
/**
 * mailchimp-lib Magento Component
 *
 * @category  Ebizmarts
 * @package   mailchimp-lib
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     4/29/16 1:18 PM
 * @file:     Root.php
 */
class MailChimp_Root extends MailChimp_Abstract
{
    public function info($fields=null, $excludeFields=null)
    {
        $_params = array();
        if ($fields) {
            $_params['fields'] = $fields;
        }

        if ($excludeFields) {
            $_params['exclude_fields'] = $excludeFields;
        }

        return $this->_master->call('', $_params, Ebizmarts_MailChimp::GET);
    }
}