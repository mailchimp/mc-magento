<?php

/**
 * MailChimp For Magento
 *
 * @category  Ebizmarts_MailChimp
 * @author    Troy Patteson <troyp@smartnetworks.com.au>
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     4/29/16 3:55 PM
 * @file:     Observer.php
 */
class Ebizmarts_MailChimp_Model_Source_Loglevel
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
