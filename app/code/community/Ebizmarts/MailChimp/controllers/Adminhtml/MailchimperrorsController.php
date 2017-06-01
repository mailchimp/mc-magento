<?php

/**
 * mc-magento Magento Component
 *
 * @category  Ebizmarts
 * @package   mc-magento
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     6/10/16 12:35 PM
 * @file:     MailchimperrorsController.php
 */
class Ebizmarts_MailChimp_Adminhtml_MailchimperrorsController extends Mage_Adminhtml_Controller_Action
{
    const MAX_RETRIES = 5;

    public function indexAction()
    {
        $this->_title($this->__('Newsletter'))
            ->_title($this->__('MailChimp'));

        $this->loadLayout();
        $this->_setActiveMenu('newsletter/mailchimp');
        $this->renderLayout();
    }

    public function gridAction()
    {
        $this->loadLayout(false);
        $this->renderLayout();
    }

    public function downloadresponseAction()
    {
        $errorId = $this->getRequest()->getParam('id');
        $error = Mage::getModel('mailchimp/mailchimperrors')->load($errorId);
        $batchId = $error->getBatchId();
        $storeId = $error->getStoreId();
        $this->getResponse()->setHeader('Content-disposition', 'attachment; filename=' . $batchId . '.json');
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $counter = 0;
        do {
            $counter++;
            $files = Mage::getModel('mailchimp/api_batches')->getBatchResponse($batchId, $storeId);
            $fileContent = array();
            foreach ($files as $file) {
                $items = json_decode(file_get_contents($file));
                foreach ($items as $item) {
                    $fileContent[] = array('status_code' => $item->status_code, 'operation_id' => $item->operation_id, 'response' => json_decode($item->response));
                }

                unlink($file);
            }

            $baseDir = Mage::getBaseDir();
            if (is_dir($baseDir . DS . 'var' . DS . 'mailchimp' . DS . $batchId)) {
                rmdir($baseDir . DS . 'var' . DS . 'mailchimp' . DS . $batchId);
            }
        } while (!count($fileContent) && $counter < self::MAX_RETRIES);

        $this->getResponse()->setBody(json_encode($fileContent, JSON_PRETTY_PRINT));
        return;
    }

    protected function _isAllowed()
    {
        switch ($this->getRequest()->getActionName()) {
        case 'index':
        case 'grid':
        case 'downloadresponse':
            $acl = 'newsletter/mailchimp/mailchimperrors';
            break;
        }

        return Mage::getSingleton('admin/session')->isAllowed($acl);
    }
}