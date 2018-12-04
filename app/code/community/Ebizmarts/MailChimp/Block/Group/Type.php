<?php

/**
 * Checkout subscribe checkbox block renderer
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
        Mage::log(__METHOD__, null, 'ebizmarts.log', true);
        Mage::log($args, null, 'ebizmarts.log', true);
        if (isset($args['interests'])) {
            $this->_currentInterest = $interests = $args['interests'];
            $type = $interests['interest']['type'];
            $this->setTemplate("ebizmarts/mailchimp/group/type/$type.phtml");
        }
        parent::__construct($args);
    }

//    protected function _prepareLayout()
//    {
//        $interests = $this->getCurrentInterest();
//        if ($interests !== null) {
//
//            $typeName = $interests['interest']['type'] . 'Groups';
//            $this->setChild($type, $this->getLayout()->createBlock("mailchimp/group_type_$typeName", "mailchimp.group.type.$typeName", array('interests' => $interests)));
//        }
//        return parent::_prepareLayout();
//    }

//    public function setCurrentInterest($i)
//    {
//        Mage::log(__METHOD__, null, 'ebizmarts.log', true);
//        $this->setTemplate('mailchimp/group/type/'.$i.'_groups.phtml');
//        return $this->_currentInterest = $i;
//    }

    /**
     * @return mixed
     */
    protected function getCurrentInterest()
    {
        Mage::log(__METHOD__, null, 'ebizmarts.log', true);
        Mage::log($this->_currentInterest, null, 'ebizmarts.log', true);
        return $this->_currentInterest;
    }
}
