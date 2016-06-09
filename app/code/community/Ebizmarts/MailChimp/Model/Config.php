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
    const GENERAL_ACTIVE            = 'mailchimp/general/active';
    const GENERAL_APIKEY            = 'mailchimp/general/apikey';
    const GENERAL_OAUTH_WIZARD      = 'mailchimp/general/oauth_wizard';
    const GENERAL_ACCOUNT_DETAILS   = 'mailchimp/general/account_details';
    const GENERAL_LIST              = 'mailchimp/general/list';
    const GENERAL_MCSTOREID         = 'mailchimp/general/storeid';
    const GENERAL_MCMINSYNCDATEFLAG = 'mailchimp/general/mcminsyncdateflag';
    const GENERAL_REPORT_EMAIL      = 'mailchimp/general/reportemail';

    const ENABLE_POPUP              = 'mailchimp/emailcatcher/popup_general';
    const POPUP_HEADING             = 'mailchimp/emailcatcher/popup_heading';
    const POPUP_TEXT                = 'mailchimp/emailcatcher/popup_text';
    const POPUP_FNAME               = 'mailchimp/emailcatcher/popup_fname';
    const POPUP_LNAME               = 'mailchimp/emailcatcher/popup_lname';
    const POPUP_WIDTH               = 'mailchimp/emailcatcher/popup_width';
    const POPUP_HEIGHT              = 'mailchimp/emailcatcher/popup_height';
    const POPUP_SUBSCRIPTION        = 'mailchimp/emailcatcher/popup_subscription';
    const POPUP_CAN_CANCEL          = 'mailchimp/emailcatcher/popup_cancel';
    const POPUP_COOKIE_TIME         = 'mailchimp/emailcatcher/popup_cookie_time';
    const POPUP_INSIST              = 'mailchimp/emailcatcher/popup_insist';

//    const GENERAL_RESET_LOCALECOMMERCE360 = 'mailchimp/general/reset_localecommerce360';
//    const GENERAL_RESET_REMOTEECOMMERCE360 = 'mailchimp/general/reset_remoteecommerce360';
//    const GENERAL_CUTOMERGROUP = 'mailchimp/general/cutomergroup';
//    const GENERAL_CHANGECUSTOMERGROUP = 'mailchimp/general/changecustomergroup';
//    const GENERAL_SHOWREALLISTNAME = 'mailchimp/general/showreallistname';
//    const GENERAL_ADDITIONAL_LIST = 'mailchimp/general/additional_lists';
//    const GENERAL_CONFIRMATION_EMAIL = 'mailchimp/general/confirmation_email';
//    const GENERAL_DOUBLE_OPTIN = 'mailchimp/general/double_optin';
//    const GENERAL_MAP_FIELDS = 'mailchimp/general/map_fields';
//    const GENERAL_GUEST_NAME = 'mailchimp/general/guest_name';
//    const GENERAL_GUEST_LASTNAME = 'mailchimp/general/guest_lastname';
//    const GENERAL_CHECKOUT_SUBSCRIBE = 'mailchimp/general/checkout_subscribe';
//    const GENERAL_MARKFIELD = 'mailchimp/general/markfield';
//    const GENERAL_CHECKOUT_ASYNC = 'mailchimp/general/checkout_async';
//    const GENERAL_CRON_IMPORT = 'mailchimp/general/cron_import';
//    const GENERAL_CRON_EXPORT = 'mailchimp/general/cron_export';
//    const GENERAL_WEBHOOK_DELETE = 'mailchimp/general/webhook_delete';
//    const GENERAL_ADMINHTML_NOTIFICATION = 'mailchimp/general/adminhtml_notification';
//    const GENERAL_ENABLE_LOG = 'mailchimp/general/enable_log';
//
//
//    const ECOMMERCE360_ACTIVE = 'mailchimp/ecommerce360/active';
//    const ECOMMERCE360_ORDER_STATUS = 'mailchimp/ecommerce360/order_status';
//    const ECOMMERCE360_ORDER_MAX = 'mailchimp/ecommerce360/order_max';
//    const ECOMMERCE360_ATTRIBUTES = 'mailchimp/ecommerce360/attributes';
    const IS_CUSTOMER   = "CUS";
    const IS_PRODUCT    = "PRO";
    const IS_ORDER      = "ORD";
    const IS_QUOTE      = "QUO";
}