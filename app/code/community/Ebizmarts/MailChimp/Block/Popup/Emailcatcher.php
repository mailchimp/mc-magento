<?php
/**
 * MailChimp For Magento
 *
 * @category  Ebizmarts_MailChimp
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     4/29/16 3:55 PM
 * @file:     Emailcatcher.php
 */
class Ebizmarts_MailChimp_Block_Popup_Emailcatcher extends Mage_Core_Block_Template
{

    protected function _canCancel()
    {
        $storeId = Mage::app()->getStore()->getId();
        return Mage::getStoreConfig(Ebizmarts_MailChimp_Model_Config::ENABLE_POPUP, $storeId) && Mage::getStoreConfig(Ebizmarts_MailChimp_Model_Config::POPUP_CAN_CANCEL, $storeId);
    }

    protected function _popupHeading()
    {
        $storeId = Mage::app()->getStore()->getId();
        return Mage::getStoreConfig(Ebizmarts_MailChimp_Model_Config::POPUP_HEADING, $storeId);
    }

    protected function _popupMessage()
    {
        $storeId = Mage::app()->getStore()->getId();
        return Mage::getStoreConfig(Ebizmarts_MailChimp_Model_Config::POPUP_TEXT, $storeId);
    }

    protected function _modalSubscribe()
    {
        $storeId = Mage::app()->getStore()->getId();
        return Mage::getStoreConfig(Ebizmarts_MailChimp_Model_Config::POPUP_SUBSCRIPTION, $storeId);
    }

    protected function _getStoreId()
    {
        return Mage::app()->getStore()->getId();
    }

    protected function _handleCookie()
    {
        $storeId = Mage::app()->getStore()->getId();
        $emailCookie = Mage::getModel('core/cookie')->get('email');
        $subscribeCookie = Mage::getModel('core/cookie')->get('subscribe');
        $cookieValues = explode('/', $emailCookie);
        $email = $cookieValues[0];
        $email = str_replace(' ', '+', $email);
        if (isset($cookieValues[1])) {
            $fName = $cookieValues[1];
        }

        if (isset($cookieValues[2])) {
            $lName = $cookieValues[2];
        }

        if ($subscribeCookie == 'true') {
            $subscriber = Mage::getModel('newsletter/subscriber')->loadByEmail($email);
            if (!$subscriber->getId()) {
                $subscriber = Mage::getModel('newsletter/subscriber')
                    ->setStoreId($storeId);

                if ($fName) {
                    $subscriberFname = filter_var($fName, FILTER_SANITIZE_STRING);
                    $subscriber->setSubscriberFirstname($subscriberFname);
                }

                if ($lName) {
                    $subscriberLname = filter_var($lName, FILTER_SANITIZE_STRING);
                    $subscriber->setSubscriberLastname($subscriberLname);
                }

                $subscriber->setStoreId($storeId)
                    ->subscribe($email);
                return 'location.reload';
            }
        }
    }
}