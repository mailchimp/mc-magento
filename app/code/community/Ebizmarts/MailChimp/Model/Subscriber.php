<?php

/**
 * Created by PhpStorm.
 * User: santisp
 * Date: 25/09/14
 * Time: 12:26 PM
 */
class Ebizmarts_MailChimp_Model_Subscriber extends Mage_Newsletter_Model_Subscriber
{
    const SUBSCRIBE_SOURCE = 'MailChimp';

    public function sendUnsubscriptionEmail()
    {
        if (Mage::getStoreConfig(Ebizmarts_MailChimp_Model_Config::GENERAL_ACTIVE) && Mage::getStoreConfig(Ebizmarts_MailChimp_Model_Config::GENERAL_MAGENTO_MAIL) != 1) {
            return $this;
        } else {
            return parent::sendUnsubscriptionEmail();
        }
    }

    public function sendConfirmationRequestEmail()
    {
        if (Mage::getStoreConfig(Ebizmarts_MailChimp_Model_Config::GENERAL_ACTIVE) && Mage::getStoreConfig(Ebizmarts_MailChimp_Model_Config::GENERAL_MAGENTO_MAIL) != 1) {
            return $this;
        } else {
            return parent::sendConfirmationRequestEmail();
        }
    }

    public function sendConfirmationSuccessEmail()
    {
        if (Mage::getStoreConfig(Ebizmarts_MailChimp_Model_Config::GENERAL_ACTIVE) && Mage::getStoreConfig(Ebizmarts_MailChimp_Model_Config::GENERAL_MAGENTO_MAIL) != 1) {
            return $this;
        } else {
            return parent::sendConfirmationSuccessEmail();
        }
    }

    public function confirm($code)
    {
        if($this->getCode()==$code) {
            $this->setStatus(self::STATUS_SUBSCRIBED)
                ->setIsStatusChanged(true)
                ->setSubscriberSource(Ebizmarts_MailChimp_Model_Subscriber::SUBSCRIBE_SOURCE)
                ->save();
            return true;
        }

        return false;
    }
}
