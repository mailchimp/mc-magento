<?php
/**
 * mailchimp-lib Magento Component
 *
 * @category  Ebizmarts
 * @package   mailchimp-lib
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     4/27/16 4:45 PM
 * @file:     Exceptions.php
 */

class MailChimp_Error extends Exception
{

    /**
     * @var array
     */
    protected $_mailchimpErrors;

    /**
     * @var string
     */
    protected $_mailchimpTitle;

    /**
     * @var string
     */
    protected $_mailchimpDetails;

    public function __construct($url = "", $title = "", $details = "", $errors = null)
    {
        $titleComplete = $title . " for Api Call: " . $url;
        parent::__construct($titleComplete . " - " . $details);
        $this->_mailchimpTitle = $titleComplete;
        $this->_mailchimpDetails = $details;
        $this->_mailchimpErrors = $errors;
    }

    public function getFriendlyMessage()
    {
        $errorDetails = "";
        if (!empty($this->_mailchimpErrors)) {
            foreach ($this->_mailchimpErrors as $error) {
                if (array_key_exists("message", $error)) {
                    $errorDetails .= $errorDetails != "" ? " / " : "";
                    if (array_key_exists("field", $error) && $error['field']) {
                        $errorDetails .= $error["field"] . " : ";
                    }

                    $errorDetails .= $error["message"];
                }
            }
        }

        if ($errorDetails != "") {
            return $this->_mailchimpTitle . " : " . $errorDetails;
        } else {
            return $this->getMessage();
        }
    }

    public function getMailchimpTitle()
    {
        return $this->_mailchimpTitle;
    }

    public function getMailchimpDetails()
    {
        return $this->_mailchimpDetails;
    }

    public function getMailchimpErrors()
    {
        return $this->_mailchimpErrors;
    }

}
