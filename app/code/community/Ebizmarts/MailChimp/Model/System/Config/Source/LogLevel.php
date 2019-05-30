<?php

/**
 * MailChimp For Magento
 *
 * @category   Ebizmarts
 * @package    Ebizmarts_MailChimp
 * @author     Ebizmarts Team <info@ebizmarts.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 */
class Ebizmarts_MailChimp_Model_System_Config_Source_LogLevel
{
    /**
     * Retrieve option array
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array(
                'value' => -1,
                'label' => 'No logging'
            ),
            array(
                'value' => Zend_Log::ERR,
                'label' => 'Log only errors'
            ),
            array(
                'value' => Zend_Log::WARN,
                'label' => 'Log errors and warnings only'
            ),
            array(
                'value' => Zend_Log::NOTICE,
                'label' => 'Log all except debug'
            ),
            array(
                'value' => Zend_Log::DEBUG,
                'label' => 'Log everything'
            ),
        );
    }
}
