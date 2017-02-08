<?php
/**
 * mc-magento Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 6/10/16 12:35 PM
 * @file: MailchimperrorsController.php
 */
class Ebizmarts_MailChimp_Adminhtml_MailchimperrorsController extends Mage_Adminhtml_Controller_Action
{
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
        $batch_id = $this->getRequest()->getParam('batch_id');
        $this->getResponse()->setHeader('Content-disposition', 'attachment; filename='.$batch_id.'.json');
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $files = Mage::getModel('mailchimp/api_batches')->getBatchResponse($batch_id);
        $fileContent = array();
        foreach ($files as $file) {
            $items = json_decode(file_get_contents($file));
            foreach ($items as $item) {
//                $fileContent [] = $item;
                $fileContent [] = array('status_code'=>$item->status_code,'operation_id'=>$item->operation_id,'response'=>json_decode($item->response));
            }

            unlink($file);
        }

        $baseDir = Mage::getBaseDir();
        if (is_dir($baseDir . DS . 'var' . DS . 'mailchimp' . DS . $batch_id)) {
            rmdir($baseDir . DS . 'var' . DS . 'mailchimp' . DS . $batch_id);
        }

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