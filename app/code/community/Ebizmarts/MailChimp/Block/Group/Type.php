<?php

/**
 * Interest group type template selector block
 *
 * @category   Ebizmarts
 * @package    Ebizmarts_MageMonkey
 * @author     Ebizmarts Team <info@ebizmarts.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 */
class Ebizmarts_MailChimp_Block_Group_Type extends Mage_Core_Block_Template
{
    protected $_currentInterest;

    public function __construct(array $args = array())
    {
        if (isset($args['interests'])) {
            $this->_currentInterest = $interests = $args['interests'];
            $type = $interests['interest']['type'];
            $this->setTemplate("ebizmarts/mailchimp/group/type/$type.phtml");
        }
        parent::__construct($args);
    }

    /**
     * @return mixed
     */
    protected function getCurrentInterest()
    {
        return $this->_currentInterest;
    }
}
