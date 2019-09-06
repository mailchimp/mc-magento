<?php

/**
 *
 * MailChimp For Magento
 *
 * Class Ebizmarts_MailChimp_Helper_Curl
 * @category  Ebizmarts_MailChimp
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     9/5/19 3:55 PM
 * @file:     Curl.php
 */

class Ebizmarts_MailChimp_Helper_Curl extends Mage_Core_Helper_Abstract
{
    /**
     * @param array $options
     * @return array An array with...
     */
    public function curlExec($options = array())
    {
        $url = isset($options[CURLOPT_URL]) ? $options[CURLOPT_URL] : false;
        $method = isset($options[CURLOPT_CUSTOMREQUEST]) ? $options[CURLOPT_CUSTOMREQUEST] : false;
        $userAgent = isset($options[CURLOPT_USERAGENT]) ? $options[CURLOPT_USERAGENT] : Zend_Http_Client::HTTP_1;
        $headers = isset($options[CURLOPT_HTTPHEADER]) ? $options[CURLOPT_HTTPHEADER] : array();
        $body = isset($options[CURLOPT_POSTFIELDS]) ? $options[CURLOPT_POSTFIELDS] : '';

        if($url === false)
            return array();

        $curl = new Varien_Http_Adapter_Curl();
        $curl->setOptions($options);
        $curl->write($method, $url, $userAgent, $headers, $body);
        $response = $curl->read();
        $responseBody = $response === false ? '' : Zend_Http_Response::extractBody($response);
        $curlError = $response === false ? $curl->getError() : '';
        $info = $curl->getInfo();
        $curl->close();

        return array('response' => $responseBody, 'error' => $curlError, 'info' => $info);
    }
}
