<?php
/**
 * mailchimp-lib Magento Component
 *
 * @category  Ebizmarts
 * @package   mailchimp-lib
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     4/29/16 4:22 PM
 * @file:     Ecommerce.php
 */
class MailChimp_Abstract
{
    /**
     * @var Mailchimp
     */
    protected $_master;

    /**
     * MailChimp_Abstract constructor.
     *
     * @param Ebizmarts_MailChimp $m
     */
    public function __construct(Ebizmarts_MailChimp $m)
    {
        $this->_master = $m;
    }
}