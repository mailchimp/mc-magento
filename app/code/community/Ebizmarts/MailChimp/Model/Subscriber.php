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
        if (Mage::getStoreConfig(Ebizmarts_MailChimp_Model_Config::GENERAL_ACTIVE)) {
            return $this;
        } else {
            return parent::sendUnsubscriptionEmail();
        }
    }

    public function sendConfirmationRequestEmail()
    {
        if (Mage::getStoreConfig(Ebizmarts_MailChimp_Model_Config::GENERAL_ACTIVE)) {
            return $this;
        } else {
            return parent::sendConfirmationRequestEmail();
        }
    }

    public function sendConfirmationSuccessEmail()
    {
        if (Mage::getStoreConfig(Ebizmarts_MailChimp_Model_Config::GENERAL_ACTIVE)) {
            return $this;
        } else {
            return parent::sendConfirmationSuccessEmail();
        }
    }
}