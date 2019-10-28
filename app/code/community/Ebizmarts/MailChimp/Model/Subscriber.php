<?php

/**
 * Created by PhpStorm.
 * User: santisp
 * Date: 25/09/14
 * Time: 12:26 PM
 */
class Ebizmarts_MailChimp_Model_Subscriber extends Mage_Newsletter_Model_Subscriber
{
    const MAILCHIMP_SUBSCRIBE = 'MailChimp';
    const SUBSCRIBE_CONFIRMATION = 'MailChimp_Confirmation';

    /**
     * @return $this|Mage_Newsletter_Model_Subscriber
     */
    public function sendUnsubscriptionEmail()
    {
        if (Mage::getStoreConfig(Ebizmarts_MailChimp_Model_Config::GENERAL_ACTIVE)
            && Mage::getStoreConfig(Ebizmarts_MailChimp_Model_Config::GENERAL_MAGENTO_MAIL) != 1
        ) {
            return $this;
        } else {
            return parent::sendUnsubscriptionEmail();
        }
    }

    /**
     * @return $this|Mage_Newsletter_Model_Subscriber
     */
    public function sendConfirmationRequestEmail()
    {
        if (Mage::getStoreConfig(Ebizmarts_MailChimp_Model_Config::GENERAL_ACTIVE)
            && Mage::getStoreConfig(Ebizmarts_MailChimp_Model_Config::GENERAL_MAGENTO_MAIL) != 1
        ) {
            return $this;
        } else {
            return parent::sendConfirmationRequestEmail();
        }
    }

    /**
     * @return $this|Mage_Newsletter_Model_Subscriber
     */
    public function sendConfirmationSuccessEmail()
    {
        if (Mage::getStoreConfig(Ebizmarts_MailChimp_Model_Config::GENERAL_ACTIVE)
            && Mage::getStoreConfig(Ebizmarts_MailChimp_Model_Config::GENERAL_MAGENTO_MAIL) != 1
        ) {
            return $this;
        } else {
            return parent::sendConfirmationSuccessEmail();
        }
    }

    /**
     * @param string $code
     * @return bool
     */
    public function confirm($code)
    {
        if ($this->getCode() == $code) {
            $this->setStatus(self::STATUS_SUBSCRIBED)
                ->setIsStatusChanged(true)
                ->setSubscriberSource(Ebizmarts_MailChimp_Model_Subscriber::SUBSCRIBE_CONFIRMATION)
                ->save();
            return true;
        }

        return false;
    }
}
