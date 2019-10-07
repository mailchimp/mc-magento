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

class MailChimp_HttpError extends MailChimp_Error
{
    /**
     * @var array
     */
    protected $_mailchimpErrors;

    /**
     * @var string
     */
    protected $_mailchimpTitleComplete;

    /**
     * @var string
     */
    protected $_mailchimpDetails;

    /**
     * @var string
     */
    protected $_mailchimpTitle;

    /**
     * @var string
     */
    protected $_mailchimpUrl;

    /**
     * @var string
     */
    protected $_mailchimpMethod;

    /**
     * @var string
     */
    protected $_mailchimpParams;

    public function __construct($url = "", $method = "", $params = "", $title = "", $details = "", $errors = null)
    {
        $titleComplete = $title . " for Api Call: " . $url;
        parent::__construct($titleComplete . " - " . $details);
        $this->_mailchimpTitleComplete = $titleComplete;
        $this->_mailchimpDetails = $details;
        $this->_mailchimpErrors = $errors;
        $this->_mailchimpUrl = $url;
        $this->_mailchimpTitle = $title;
        $this->_mailchimpMethod = $method;
        $this->_mailchimpParams = $params;
    }

    public function getFriendlyMessage()
    {
        $friendlyMessage = $this->_mailchimpTitle . " for Api Call: ["
            . $this->_mailchimpUrl. "] using method ["
            .$this->_mailchimpMethod."]\n";
        $friendlyMessage .= "\tDetail: [".$this->_mailchimpDetails."]\n";
        if (!empty($this->_mailchimpErrors)) {
            $errorDetails = "";
            foreach ($this->_mailchimpErrors as $error) {
                $field = array_key_exists('field', $error) ? $error['field'] : '';
                $message = array_key_exists('message', $error) ? $error['message'] : '';
                $line = "\t\t field [$field] : $message\n";
                $errorDetails .= $line;
            }

            $friendlyMessage .= "\tErrors:\n".$errorDetails;
        }

        if (!is_array($this->_mailchimpParams)) {
            $friendlyMessage .= "\tParams:\n\t\t".$this->_mailchimpParams;
        } elseif (!empty($this->_mailchimpParams)) {
            $friendlyMessage .= "\tParams:\n\t\t" . json_encode($this->_mailchimpParams) . "\n";
        }

        return $friendlyMessage;
    }

    /**
     * @return string
     */
    public function getMailchimpTitleComplete()
    {
        return $this->_mailchimpTitleComplete;
    }

    /**
     * @return string
     */
    public function getMailchimpDetails()
    {
        return $this->_mailchimpDetails;
    }

    /**
     * @return array|null
     */
    public function getMailchimpErrors()
    {
        return $this->_mailchimpErrors;
    }

    /**
     * @return string
     */
    public function getMailchimpTitle()
    {
        return $this->_mailchimpTitle;
    }

    /**
     * @return string
     */
    public function getMailchimpUrl()
    {
        return $this->_mailchimpUrl;
    }

    /**
     * @return string
     */
    public function getMailchimpMethod()
    {
        return $this->_mailchimpMethod;
    }

    /**
     * @return string
     */
    public function getMailchimpParams()
    {
        return $this->_mailchimpParams;
    }
}
