<?php
/**
 * mailchimp-lib Magento Component
 *
 * @category Ebizmarts
 * @package mailchimp-lib
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 4/27/16 4:45 PM
 * @file: Exceptions.php
 */

class Mailchimp_Error extends Exception
{

    /**
     * @var array
     */
    protected $_mailchimp_errors;

    /**
     * @var string
     */
    protected $_mailchimp_title;

    /**
     * @var string
     */
    protected $_mailchimp_details;

    public function __construct($url = "", $title = "", $details = "", $errors = null)
    {
        $titleComplete = $title . " for Api Call: " . $url;
        parent::__construct($titleComplete . " - " . $details);
        $this->_mailchimp_title = $titleComplete;
        $this->_mailchimp_details = $details;
        $this->_mailchimp_errors = $errors;
    }

    public function getFriendlyMessage()
    {
        $error_details = "";
        if(!empty($this->_mailchimp_errors))
        {
            foreach($this->_mailchimp_errors as $error)
            {
                if(array_key_exists("message",$error)){
                    $error_details .= $error_details != "" ? " / " : "";
                    if(array_key_exists("field", $error) && $error['field']){
                        $error_details .= $error["field"] . " : ";
                    }
                    $error_details .= $error["message"];
                }
            }
        }

        if($error_details != ""){
            return $this->_mailchimp_title . " : " . $error_details;
        }else{
            return $this->getMessage();
        }
    }

    public function getMailchimpTitle(){
        return $this->_mailchimp_title;
    }

    public function getMailchimpDetails(){
        return $this->_mailchimp_details;
    }

    public function getMailchimpErrors(){
        return $this->_mailchimp_errors;
    }

}

class Mailchimp_HttpError extends Mailchimp_Error
{

}
