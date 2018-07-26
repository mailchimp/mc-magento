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
        $order = Mage::getSingleton('checkout/session')->getLastRealOrder();
        $session = Mage::getSingleton('core/session');
        $helper = $this->getHelper();
        $params = $this->getRequest()->getParams();
        $storeId = $order->getStoreId();
        $interestGroup = Mage::getModel('mailchimp/interestgroup');
        $subscriber = Mage::getModel('newsletter/subscriber')
            ->loadByEmail($order->getCustomerEmail());
        $customerId = $order->getCustomerId();
        try {
            if (!$subscriber->getSubscriberId()) {
                $subscriber->setSubscriberEmail($order->getCustomerEmail());
                $subscriber->setSubscriberFirstname($order->getCustomerFirstname());
                $subscriber->setSubscriberLastname($order->getCustomerLastname());
                $subscriber->subscribe($order->getCustomerEmail());
            }
            $interestGroup->getByRelatedIdStoreId($customerId, $subscriber->getSubscriberId(),$storeId);
            $interestGroup->setGroupdata(serialize($params));
            $interestGroup->setSubscriberId($subscriber->getSubscriberId());
            $interestGroup->setCustomerId($order->getCustomerId());
            $interestGroup->setStoreId($storeId);
            $interestGroup->setUpdatedAt(Mage::getModel('core/date')->date('d-m-Y H:i:s'));
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
}