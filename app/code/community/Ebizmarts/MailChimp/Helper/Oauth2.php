<?php
/**
 * MailChimp For Magento
 *
 * @category  Ebizmarts_MailChimp
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     4/29/16 3:55 PM
 * @file:     Oauth2.php
 */
class Ebizmarts_MailChimp_Helper_Oauth2 extends Mage_Core_Helper_Abstract
{

    protected $_authorizeUri = "https://login.mailchimp.com/oauth2/authorize";
    protected $_accessTokenUri = "https://login.mailchimp.com/oauth2/token";
    protected $_redirectUri = "https://ebizmarts.com/magento/mc-magento/oauth2/complete.php";
    protected $_clientId = 200573319150;

    public function authorizeRequestUrl()
    {

        $url = $this->_authorizeUri;
        $redirectUri = urlencode($this->_redirectUri);

        return "{$url}?redirect_uri={$redirectUri}&response_type=code&client_id={$this->_clientId}";
    }
}
