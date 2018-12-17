<?php
/**
 * mc-magento Magento Component
 *
 * @category  Ebizmarts
 * @package   mc-magento
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     7/6/16 10:14 AM
 * @file:     GroupController.php
 */


class Ebizmarts_MailChimp_GroupController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        $helper = $this->getHelper();
        $order = $this->getSessionLastRealOrder();
        $session = $this->getCoreSession();
        $interestGroup = $this->getInterestGroupModel();
        $params = $this->getRequest()->getParams();
        $storeId = $order->getStoreId();
        $customerEmail = $order->getCustomerEmail();
        $customerId = $order->getCustomerId();
        $subscriber = $this->getSubscriberModel()
            ->loadByEmail($customerEmail);
        try {
            if (!$subscriber->getSubscriberId()) {
                $subscriber->setSubscriberEmail($customerEmail);
                $subscriber->setSubscriberFirstname($order->getCustomerFirstname());
                $subscriber->setSubscriberLastname($order->getCustomerLastname());
                $subscriber->subscribe($customerEmail);
            }
            $subscriberId = $subscriber->getSubscriberId();
            $interestGroup->getByRelatedIdStoreId($customerId, $subscriberId, $storeId);
            $encodedGroups = $helper->arrayEncode($params);
            $interestGroup->setGroupdata($encodedGroups);
            $interestGroup->setSubscriberId($subscriberId);
            $interestGroup->setCustomerId($customerId);
            $interestGroup->setStoreId($storeId);
            $interestGroup->setUpdatedAt($this->getCurrentDateTime());
            $interestGroup->save();

            $this->getApiSubscriber()->update($subscriber->getSubscriberEmail(), $storeId, '', 1);

            $session->addSuccess($this->__('Thanks for share your interest with us.'));
        } catch (Exception $e) {
            $helper->logError($e->getMessage());
            $session->addWarning($this->__('Something went wrong with the interests subscription. Please go to the account subscription menu to subscriber to the interests successfully.'));
        }
        $this->_redirect('/');
    }

    protected function getHelper()
    {
        return Mage::helper('mailchimp');
    }

    protected function getApiSubscriber()
    {
        return Mage::getModel('mailchimp/api_subscribers');
    }

    /**
     * @return mixed
     */
    protected function getSessionLastRealOrder()
    {
        return Mage::getSingleton('checkout/session')->getLastRealOrder();
    }

    /**
     * @return Mage_Core_Model_Session
     */
    protected function getCoreSession()
    {
        return Mage::getSingleton('core/session');
    }

    /**
     * @return Ebizmarts_MailChimp_Model_Interestgroup
     */
    protected function getInterestGroupModel()
    {
        return Mage::getModel('mailchimp/interestgroup');
    }

    /**
     * @return Mage_Newsletter_Model_Subscriber
     */
    protected function getSubscriberModel()
    {
        return Mage::getModel('newsletter/subscriber');
    }

    /**
     * @return mixed
     */
    protected function getCurrentDateTime()
    {
        return Mage::getModel('core/date')->date('d-m-Y H:i:s');
    }
}
