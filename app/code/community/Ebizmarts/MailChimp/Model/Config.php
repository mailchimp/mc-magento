<?php
/**
 * MailChimp For Magento
 *
 * @category Ebizmarts_MailChimp
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 4/29/16 3:55 PM
 * @file: Config.php
 */
class Ebizmarts_MailChimp_Model_Config
{
    const GENERAL_ACTIVE                = 'mailchimp/general/active';
    const GENERAL_APIKEY                = 'mailchimp/general/apikey';
    const GENERAL_OAUTH_WIZARD          = 'mailchimp/general/oauth_wizard';
    const GENERAL_ACCOUNT_DETAILS       = 'mailchimp/general/account_details';
    const GENERAL_LIST                  = 'mailchimp/general/list';
    const GENERAL_OLD_LIST              = 'mailchimp/general/old_list';
    const GENERAL_LIST_CHANGED_SCOPES   = 'mailchimp/general/list_changed_scopes';
    const GENERAL_MCSTOREID             = 'mailchimp/general/storeid';
    const GENERAL_MCISSYNCING           = 'mailchimp/general/is_syicing';
    const GENERAL_MCMINSYNCDATEFLAG     = 'mailchimp/general/mcminsyncdateflag';
    const GENERAL_MCSTORE_RESETED       = 'mailchimp/general/mcstore_reset';
    const GENERAL_SUB_MCMINSYNCDATEFLAG = 'mailchimp/general/sub_mcminsyncdateflag';
    const GENERAL_TWO_WAY_SYNC          = 'mailchimp/general/webhook_active';
    const GENERAL_UNSUBSCRIBE           = 'mailchimp/general/webhook_delete';
    const GENERAL_LOG                   = 'mailchimp/general/enable_log';
    const GENERAL_MAP_FIELDS            = 'mailchimp/general/map_fields';
    const GENERAL_CUSTOM_MAP_FIELDS     = 'mailchimp/general/customer_map_fields';

    const ECOMMERCE_ACTIVE              = 'mailchimp/ecommerce/active';
    const ECOMMERCE_CUSTOMERS_OPTIN     = 'mailchimp/ecommerce/customers_optin';
    const ECOMMERCE_FIRSTDATE           = 'mailchimp/ecommerce/firstdate';

    const ENABLE_POPUP                  = 'mailchimp/emailcatcher/popup_general';
    const POPUP_HEADING                 = 'mailchimp/emailcatcher/popup_heading';
    const POPUP_TEXT                    = 'mailchimp/emailcatcher/popup_text';
    const POPUP_FNAME                   = 'mailchimp/emailcatcher/popup_fname';
    const POPUP_LNAME                   = 'mailchimp/emailcatcher/popup_lname';
    const POPUP_WIDTH                   = 'mailchimp/emailcatcher/popup_width';
    const POPUP_HEIGHT                  = 'mailchimp/emailcatcher/popup_height';
    const POPUP_SUBSCRIPTION            = 'mailchimp/emailcatcher/popup_subscription';
    const POPUP_CAN_CANCEL              = 'mailchimp/emailcatcher/popup_cancel';
    const POPUP_COOKIE_TIME             = 'mailchimp/emailcatcher/popup_cookie_time';
    const POPUP_INSIST                  = 'mailchimp/emailcatcher/popup_insist';

    const ABANDONEDCART_ACTIVE      = 'mailchimp/abandonedcart/active';
    const ABANDONEDCART_FIRSTDATE   = 'mailchimp/abandonedcart/firstdate';
    const ABANDONEDCART_PAGE        = 'mailchimp/abandonedcart/page';
    const MONKEY_GRID               = 'mailchimp/general/monkey_grid';

    const WARNING_MESSAGE           = 'mailchimp/warning_message';
    const POPUP_MESSAGE             = 'mailchimp/popup_message';

    const MANDRILL_APIKEY           = 'mandrill/general/apikey';
    const MANDRILL_ACTIVE           = 'mandrill/general/active';
    const MANDRILL_LOG              = 'mandrill/general/enable_log';

    const IS_CUSTOMER   = "CUS";
    const IS_PRODUCT    = "PRO";
    const IS_ORDER      = "ORD";
    const IS_QUOTE      = "QUO";
    const IS_SUBSCRIBER = "SUB";
}