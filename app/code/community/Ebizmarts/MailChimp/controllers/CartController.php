<?php
/**
 * mc-magento Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 7/6/16 10:14 AM
 * @file: CartController.php
 */

require_once Mage::getModuleDir('controllers', 'Mage_Checkout') . DS . 'CartController.php';

class Ebizmarts_MailChimp_CartController  extends Mage_Checkout_CartController
{
    public function loadquoteAction()
    {
        $params = $this->getRequest()->getParams();
        if (isset($params['id'])) {
            //restore the quote
            $quote = Mage::getModel('sales/quote')->load($params['id']);
            $url = Mage::getUrl(Mage::getStoreConfig(Ebizmarts_MailChimp_Model_Config::ABANDONEDCART_PAGE, $quote->getStoreId()));
            if (isset($params['mc_cid'])) {
                $url .= '?mc_cid='.$params['mc_cid'];
            }

            if ((!isset($params['token']) || (isset($params['token']) && $params['token'] != $quote->getMailchimpToken()))) {
                Mage::getSingleton('customer/session')->addNotice("Your token cart is incorrect");
                $this->_redirect($url);
            } else {
                $quote->setMailchimpAbandonedcartFlag(1);
                $quote->save();
                if (!$quote->getCustomerId()) {
                    $this->_getSession()->setQuoteId($quote->getId());
                    $this->getResponse()
                        ->setRedirect($url, 301);
                } else {
                    if (Mage::helper('customer')->isLoggedIn()) {
                        $this->getResponse()
                            ->setRedirect($url, 301);
                    } else {
                        Mage::getSingleton('customer/session')->addNotice("Login to complete your order");
                        $url = '/customer/account/login';
                        if (isset($params['mc_cid'])) {
                            $url .= '?mc_cid='.$params['mc_cid'];
                        }

                        $this->getResponse()->setRedirect($url, 301);
                        //$this->_redirect('customer/account/login',array('?','mc_cid='.$params['mc_cid']));
                    }
                }
            }
        }
    }
}