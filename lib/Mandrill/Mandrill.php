<?php
if (defined("COMPILER_INCLUDE_PATH")) {
    include_once dirname(__FILE__) . '/Mandrill/Mandrill/Templates.php';
    include_once dirname(__FILE__) . '/Mandrill/Mandrill/Exports.php';
    include_once dirname(__FILE__) . '/Mandrill/Mandrill/Users.php';
    include_once dirname(__FILE__) . '/Mandrill/Mandrill/Rejects.php';
    include_once dirname(__FILE__) . '/Mandrill/Mandrill/Inbound.php';
    include_once dirname(__FILE__) . '/Mandrill/Mandrill/Tags.php';
    include_once dirname(__FILE__) . '/Mandrill/Mandrill/Messages.php';
    include_once dirname(__FILE__) . '/Mandrill/Mandrill/Whitelists.php';
    include_once dirname(__FILE__) . '/Mandrill/Mandrill/Ips.php';
    include_once dirname(__FILE__) . '/Mandrill/Mandrill/Internal.php';
    include_once dirname(__FILE__) . '/Mandrill/Mandrill/Subaccounts.php';
    include_once dirname(__FILE__) . '/Mandrill/Mandrill/Urls.php';
    include_once dirname(__FILE__) . '/Mandrill/Mandrill/Webhooks.php';
    include_once dirname(__FILE__) . '/Mandrill/Mandrill/Senders.php';
    include_once dirname(__FILE__) . '/Mandrill/Mandrill/Metadata.php';
    include_once dirname(__FILE__) . '/Mandrill/Mandrill/Exceptions.php';
} else {
    include_once dirname(__FILE__) . '/Mandrill/Templates.php';
    include_once dirname(__FILE__) . '/Mandrill/Exports.php';
    include_once dirname(__FILE__) . '/Mandrill/Users.php';
    include_once dirname(__FILE__) . '/Mandrill/Rejects.php';
    include_once dirname(__FILE__) . '/Mandrill/Inbound.php';
    include_once dirname(__FILE__) . '/Mandrill/Tags.php';
    include_once dirname(__FILE__) . '/Mandrill/Messages.php';
    include_once dirname(__FILE__) . '/Mandrill/Whitelists.php';
    include_once dirname(__FILE__) . '/Mandrill/Ips.php';
    include_once dirname(__FILE__) . '/Mandrill/Internal.php';
    include_once dirname(__FILE__) . '/Mandrill/Subaccounts.php';
    include_once dirname(__FILE__) . '/Mandrill/Urls.php';
    include_once dirname(__FILE__) . '/Mandrill/Webhooks.php';
    include_once dirname(__FILE__) . '/Mandrill/Senders.php';
    include_once dirname(__FILE__) . '/Mandrill/Metadata.php';
    include_once dirname(__FILE__) . '/Mandrill/Exceptions.php';
}

class Mandrill_Mandrill
{

    public $apikey;
    protected $_curlOptions = array();
    public $root = 'https://mandrillapp.com/api/1.0';
    public $debug = false;

    public static $errorMap = array(
        "ValidationError" => "Mandrill_ValidationError",
        "Invalid_Key" => "Mandrill_Invalid_Key",
        "PaymentRequired" => "Mandrill_PaymentRequired",
        "Unknown_Subaccount" => "Mandrill_Unknown_Subaccount",
        "Unknown_Template" => "Mandrill_Unknown_Template",
        "ServiceUnavailable" => "Mandrill_ServiceUnavailable",
        "Unknown_Message" => "Mandrill_Unknown_Message",
        "Invalid_Tag_Name" => "Mandrill_Invalid_Tag_Name",
        "Invalid_Reject" => "Mandrill_Invalid_Reject",
        "Unknown_Sender" => "Mandrill_Unknown_Sender",
        "Unknown_Url" => "Mandrill_Unknown_Url",
        "Unknown_TrackingDomain" => "Mandrill_Unknown_TrackingDomain",
        "Invalid_Template" => "Mandrill_Invalid_Template",
        "Unknown_Webhook" => "Mandrill_Unknown_Webhook",
        "Unknown_InboundDomain" => "Mandrill_Unknown_InboundDomain",
        "Unknown_InboundRoute" => "Mandrill_Unknown_InboundRoute",
        "Unknown_Export" => "Mandrill_Unknown_Export",
        "IP_ProvisionLimit" => "Mandrill_IP_ProvisionLimit",
        "Unknown_Pool" => "Mandrill_Unknown_Pool",
        "NoSendingHistory" => "Mandrill_NoSendingHistory",
        "PoorReputation" => "Mandrill_PoorReputation",
        "Unknown_IP" => "Mandrill_Unknown_IP",
        "Invalid_EmptyDefaultPool" => "Mandrill_Invalid_EmptyDefaultPool",
        "Invalid_DeleteDefaultPool" => "Mandrill_Invalid_DeleteDefaultPool",
        "Invalid_DeleteNonEmptyPool" => "Mandrill_Invalid_DeleteNonEmptyPool",
        "Invalid_CustomDNS" => "Mandrill_Invalid_CustomDNS",
        "Invalid_CustomDNSPending" => "Mandrill_Invalid_CustomDNSPending",
        "Metadata_FieldLimit" => "Mandrill_Metadata_FieldLimit",
        "Unknown_MetadataField" => "Mandrill_Unknown_MetadataField"
    );

    public function __construct($apikey = null)
    {
        if (!$apikey) {
            $apikey = getenv('MANDRILL_APIKEY');
        }

        if (!$apikey) {
            $apikey = $this->readConfigs();
        }

        if (!$apikey) {
            throw new Mandrill_Error('You must provide a Mandrill API key');
        }

        $this->apikey = $apikey;

        $curlOptions = array(
            CURLOPT_USERAGENT => 'Mandrill-PHP/1.0.53',
            CURLOPT_POST => true
        );

        if (!ini_get('open_basedir')) {
            $curlOptions[CURLOPT_FOLLOWLOCATION] = true;
        }

        $curlOptions[CURLOPT_HEADER] = false;
        $curlOptions[CURLOPT_RETURNTRANSFER] = true;
        $curlOptions[CURLOPT_CONNECTTIMEOUT] =  30;
        $curlOptions[CURLOPT_TIMEOUT] = 600;

        $this->setCurlOptionsAddOptions($curlOptions);

        $this->root = rtrim($this->root, '/') . '/';

        $this->templates = new Mandrill_Templates($this);
        $this->exports = new Mandrill_Exports($this);
        $this->users = new Mandrill_Users($this);
        $this->rejects = new Mandrill_Rejects($this);
        $this->inbound = new Mandrill_Inbound($this);
        $this->tags = new Mandrill_Tags($this);
        $this->messages = new Mandrill_Messages($this);
        $this->whitelists = new Mandrill_Whitelists($this);
        $this->ips = new Mandrill_Ips($this);
        $this->internal = new Mandrill_Internal($this);
        $this->subaccounts = new Mandrill_Subaccounts($this);
        $this->urls = new Mandrill_Urls($this);
        $this->webhooks = new Mandrill_Webhooks($this);
        $this->senders = new Mandrill_Senders($this);
        $this->metadata = new Mandrill_Metadata($this);
    }

    public function __destruct()
    {
        $this->_curlOptions = array();
    }

    public function call($url, $params)
    {
        $params['key'] = $this->apikey;
        $params = json_encode($params);

        $curlOptions = array();
        $curlOptions[CURLOPT_URL] = $this->root . $url . '.json';
        $curlOptions[CURLOPT_HTTPHEADER] = array('Content-Type: application/json');
        $curlOptions[CURLOPT_POSTFIELDS] = $params;
        $curlOptions[CURLOPT_VERBOSE] = $this->debug;

        $start = microtime(true);
        $this->log('Call to ' . $this->root . $url . '.json: ' . $params);

        if ($this->debug) {
            $curlBuffer = fopen('php://memory', 'w+');
            $curlOptions[CURLOPT_STDERR] = $curlBuffer;
        }

        /**
         * @var $curlHelper Ebizmarts_MailChimp_Helper_Curl
         */
        $curlHelper = Mage::helper('mailchimp/curl');
        $this->setCurlOptionsAddOptions($curlOptions);
        $curlFullResponse = $curlHelper->curlExec($url, 'GET', $this->_curlOptions);
        $info = $curlHelper->getStatus();
        $responseBody = $curlFullResponse['response'];
        $curlError = $curlFullResponse['error'];
        $time = microtime(true) - $start;

        if ($this->debug) {
            rewind($curlBuffer);
            $this->log(stream_get_contents($curlBuffer));
            fclose($curlBuffer);
        }

        $this->log('Completed in ' . number_format($time * 1000, 2) . 'ms');
        $this->log('Got response: ' . $responseBody);

        if (!empty($curlError)) {
            throw new Mandrill_HttpError("API call to $url failed: " . $curlError);
        }

        $result = json_decode($responseBody, true);

        if ($result === null) {
            throw new Mandrill_Error(
                'We were unable to decode the JSON response from the Mandrill API: ' . $responseBody
            );
        }

        try {
            if (floor($info / 100) >= 4) {
                throw $this->castError($result);
            }
        } catch (Exception $e) {
            $this->log($e->getMessage());
        }

        return $result;
    }

    public function readConfigs()
    {
        $paths = array('~/.mandrill.key', '/etc/mandrill.key');
        $fileHelper = $this->getFileHelper();

        foreach ($paths as $path) {
            if ($fileHelper->fileExists($path)) {
                $apikey = trim($fileHelper->read($path));

                if ($apikey) {
                    return $apikey;
                }
            }
        }

        return false;
    }

    public function castError($result)
    {
        if ($result['status'] !== 'error' || !$result['name']) {
            throw new Mandrill_Error('We received an unexpected error: ' . json_encode($result));
        }

        $class = (isset(self::$errorMap[$result['name']])) ? self::$errorMap[$result['name']] : 'Mandrill_Error';
        return new $class($result['message'], $result['code']);
    }

    public function log($msg)
    {
        if ($this->debug) {
            error_log($msg);
        }
    }

    /**
     * @param array $curlOptions
     */
    protected function setCurlOptionsAddOptions($curlOptions = array())
    {
        $this->_curlOptions += $curlOptions;
    }

    /**
     * @return Ebizmarts_MailChimp_Helper_File
     */
    protected function getFileHelper()
    {
        return Mage::helper('mailchimp/file');
    }
}
