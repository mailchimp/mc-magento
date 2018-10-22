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

    //Force double-opt in for registered customers if "Need to Confirm" is enabled
    public function subscribe($email)
    {
            $this->loadByEmail($email);
            $customerSession = Mage::getSingleton('customer/session');

            if(!$this->getId()) {
                $this->setSubscriberConfirmCode($this->randomSequence());
            }

            $isConfirmNeed = (Mage::getStoreConfig(self::XML_PATH_CONFIRMATION_FLAG) == 1) ? true : false;
            $isOwnSubscribes = false;
            $ownerId = Mage::getModel('customer/customer')
                ->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
                ->loadByEmail($email)
                ->getId();
            $isSubscribeOwnEmail = $customerSession->isLoggedIn() && $ownerId == $customerSession->getId();

            if (!$this->getId() || $this->getStatus() == self::STATUS_UNSUBSCRIBED
                || $this->getStatus() == self::STATUS_NOT_ACTIVE
            ) {
                if ($isConfirmNeed === true) {
                        $this->setStatus(self::STATUS_NOT_ACTIVE);
                } else {
                    $this->setStatus(self::STATUS_SUBSCRIBED);
                }
                $this->setSubscriberEmail($email);
            }

            if ($isSubscribeOwnEmail) {
                $this->setStoreId($customerSession->getCustomer()->getStoreId());
                $this->setCustomerId($customerSession->getCustomerId());
            } else {
                $this->setStoreId(Mage::app()->getStore()->getId());
                $this->setCustomerId(0);
            }

            $this->setIsStatusChanged(true);

            try {
                $this->save();
                if ($isConfirmNeed === true
                    && $isOwnSubscribes === false
                ) {
                    $this->sendConfirmationRequestEmail();
                } else {
                    $this->sendConfirmationSuccessEmail();
                }

                return $this->getStatus();
            } catch (Exception $e) {
                throw new Exception($e->getMessage());
            }
    }
}
