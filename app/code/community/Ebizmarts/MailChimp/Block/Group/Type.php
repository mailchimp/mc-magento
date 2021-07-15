<?php

/**
 * Interest group type template selector block
 *
 * @category Ebizmarts
 * @package  Ebizmarts_MageMonkey
 * @author   Ebizmarts Team <info@ebizmarts.com>
 * @license  http://opensource.org/licenses/osl-3.0.php
 */
class Ebizmarts_MailChimp_Block_Group_Type extends Mage_Core_Block_Template
{
    protected $_currentInterest;
    protected $_helper;

    public function __construct(array $args = array())
    {
        $this->_helper = Mage::helper('mailchimp');

        if (isset($args['interests'])) {
            $this->_currentInterest = $interests = $args['interests'];
            $type = $interests['interest']['type'];
            $this->setTemplate("ebizmarts/mailchimp/group/type/$type.phtml");
        }

        parent::__construct($args);
    }

    /**
     * @param $data
     * @return string
     */
    public function escapeQuote($data)
    {
        return $this->getHelper()->mcEscapeQuote($data);
    }

    /**
     * @return Ebizmarts_MailChimp_Helper_Data
     */
    public function getHelper($type='')
    {
        return $this->_helper;
    }

    /**
     * @return mixed
     */
    protected function getCurrentInterest()
    {
        return $this->_currentInterest;
    }
}
