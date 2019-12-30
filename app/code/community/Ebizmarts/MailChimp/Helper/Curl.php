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
    public function curlExec($url, $method, $curlOptions = array(), $params = array())
    {
        if ($url === false){
            return array();
        }
        Mage::log(__METHOD__ . " METHOD: $method", null, '', true);
        Mage::log(__METHOD__ . " URL: $url", null, '', true);
        Mage::log(__METHOD__ . " FINAL CURL_OPTIONS " . print_r($curlOptions, true), null, '', true);
        $curlError = null;
        $info = null;
        $curl = new Mage_HTTP_Client_Curl();
        foreach ($curlOptions as $key => $value){
            if(isset($value)){
                $curl->setOption($key, $value);
            }
        }
        $curlResult = null;

        try{
            if($method == Zend_Http_Client::GET){
                $curl->get($url);
            }
            elseif($method == Zend_Http_Client::POST){
                $curl->post($url, $params);
            }
            $curlResult = $curl->getBody();
        }
        catch(Exception $e){
            Mage::log(__METHOD__ . " " . $e->getTraceAsString(), null, '', true);
            /*Mage::log(__METHOD__ . " EEEERRRRRRRROOORRR " . $e->getMessage(), null, '', true);
            Mage::log(__METHOD__ . " EEEERRRRRRRROOORRR " . print_r($curl, true), null, '', true);
            Mage::log(__METHOD__ . " EEEERRRRRRRROOORRR status" . $curl->getStatus(), null, '', true);
            Mage::log(__METHOD__ . " EEEERRRRRRRROOORRR " . print_r($curl->getHeaders(), true), null, '', true);
            Mage::log(__METHOD__ . " EEEERRRRRRRROOORRR BODY " . $curlResult, null, '', true);*/
        }

    Mage::log(__METHOD__ . " urlResult: " . print_r($curlResult, true), null, '', true);
    return array('response' => $curlResult, 'error' => $curlError, 'info' => $info);
    }


    /**
     * @param array $options
     * @return array An array with...
     */
    public function curlExec_OLD($options = array(), $body = array())
    {
        $url = isset($options[CURLOPT_URL]) ? $options[CURLOPT_URL] : false;
        $method = isset($options[CURLOPT_CUSTOMREQUEST]) ? $options[CURLOPT_CUSTOMREQUEST] : false;
        $userAgent = isset($options[CURLOPT_USERAGENT]) ? $options[CURLOPT_USERAGENT] : Zend_Http_Client::HTTP_1;
        $headers = isset($options[CURLOPT_HTTPHEADER]) ? $options[CURLOPT_HTTPHEADER] : array();

        if ($url === false)
            return array();

        $curl = new Varien_Http_Adapter_Curl();
        unset($options[CURLOPT_URL]);
        unset($options[CURLOPT_CUSTOMREQUEST]);
//        $options[10005] = "anystring:10f946a9d8cb2c5ba25e472afaf02bf1-us20";

        Mage::log(__METHOD__ . " OPTIONS: " . print_r($options, true), null, '', true);

        $curl->addOptions($options);
        $curl->write($method, $url, $userAgent, $headers, $body);
        $response = $curl->read();Mage::log(__METHOD__ . " Response: " . print_r($response, true), null, '', true);
        $responseBody = $response === false ? '' : Zend_Http_Response::extractBody($response);
        $curlError = $response === false ? $curl->getError() : '';
        $info = $curl->getInfo();
        $curl->close();

        return array('response' => $responseBody, 'error' => $curlError, 'info' => $info);
    }
}
