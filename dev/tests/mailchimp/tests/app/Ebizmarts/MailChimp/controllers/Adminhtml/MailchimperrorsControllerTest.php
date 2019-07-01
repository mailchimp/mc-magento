<?php

require_once BP . DS . 'app/code/community/Ebizmarts/MailChimp/controllers/Adminhtml/MailchimperrorsController.php';

class Ebizmarts_MailChimp_Adminhtml_MailchimperrorsControllerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Ebizmarts_MailChimp_Adminhtml_MailchimperrorsController $mailchimperrorsController
     */
    private $mailchimperrorsController;

    public function setUp()
    {
        Mage::app('default');
        $this->mailchimperrorsController = $this->getMockBuilder(Ebizmarts_MailChimp_Adminhtml_MailchimperrorsController::class);
    }

    public function tearDown()
    {
        $this->mailchimperrorsController = null;
    }

    public function testDownloadresponseAction()
    {
        $paramId = 'id';
        $errorId = 1;
        $storeId = 1;
        $batchId = 1;
        $mailchimpStoreId = 'a1s2d3f4g5h6j7k8l9n0';
        $files = array('/magento/var/mailchimp/1f103d0176/c9cf317023.json');
        $file = '/magento/var/mailchimp/1f103d0176/c9cf317023.json';
        $item = new stdClass();
        $item->status_code = 400;
        $item->operation_id = 'storeid-1_CUS_2018-02-06-18-46-06-86970300_64';
        $item->response = '{"type":"http://developer.mailchimp.com/documentation/mailchimp/guides/error-glossary/","title":"Invalid Resource","status":400,"detail":"The resource submitted could not be validated. For field-specific details, see the \'errors\' array.","instance":"","errors":[{"field":"email_address","message":"This email address looks fake or invalid. Please enter a real email address."}]}';
        $items = array($item);
        $magentoBaseDir = '/magento/';

        $mailchimperrorsControllerMock = $this->mailchimperrorsController
            ->disableOriginalConstructor()
            ->setMethods(array('makeHelper', 'getRequest', 'getResponse', 'getMailchimperrorsModel', 'getApiBatches',
                'getFileContent', 'unlink'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getMageApp', 'isEcomSyncDataEnabled'))
            ->getMock();

        $requestMock = $this->getMockBuilder(Mage_Core_Controller_Request_Http::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getParam'))
            ->getMock();

        $responseMock = $this->getMockBuilder(Mage_Core_Controller_Response_Http::class)
            ->disableOriginalConstructor()
            ->setMethods(array('setHeader', 'setBody'))
            ->getMock();

        $apiBatchesMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Batches::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getBatchResponse', 'getMagentoBaseDir', 'batchDirExists', 'removeBatchDir'))
            ->getMock();

        $mailchimperrorsMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Mailchimperrors::class)
            ->disableOriginalConstructor()
            ->setMethods(array('load', 'getBatchId', 'getStoreId', 'getMailchimpStoreId'))
            ->getMock();

        $mailchimperrorsControllerMock->expects($this->once())->method('makeHelper')->willReturn($helperMock);

        $mailchimperrorsControllerMock->expects($this->once())->method('getRequest')->willReturn($requestMock);

        $requestMock->expects($this->once())->method('getParam')->with($paramId)->willReturn($errorId);

        $mailchimperrorsControllerMock->expects($this->once())->method('getMailchimperrorsModel')->willReturn($mailchimperrorsMock);

        $mailchimperrorsControllerMock->expects($this->once())->method('getApiBatches')->willReturn($apiBatchesMock);

        $mailchimperrorsMock->expects($this->once())->method('load')->with($errorId)->willReturnSelf();
        $mailchimperrorsMock->expects($this->once())->method('getBatchId')->willReturn($batchId);
        $mailchimperrorsMock->expects($this->once())->method('getStoreId')->willReturn($storeId);
        $mailchimperrorsMock->expects($this->once())->method('getMailchimpStoreId')->willReturn($mailchimpStoreId);

        $helperMock->expects($this->once())->method('isEcomSyncDataEnabled')->with($storeId)->willReturn(true);

        $mailchimperrorsControllerMock->expects($this->once())->method('getResponse')->willReturn($responseMock);

        $responseMock->expects($this->exactly(2))->method('setHeader')->withConsecutive(
            array('Content-disposition', 'attachment; filename=' . $batchId . '.json'),
            array('Content-type', 'application/json')
        );

        $apiBatchesMock->expects($this->once())->method('getBatchResponse')->with($batchId, $storeId)->willReturn($files);

        $mailchimperrorsControllerMock->expects($this->once())->method('getFileContent')->with($file)->willReturn($items);
        $mailchimperrorsControllerMock->expects($this->once())->method('unlink')->with($file);

        $apiBatchesMock->expects($this->once())->method('getMagentoBaseDir')->willReturn($magentoBaseDir);
        $apiBatchesMock->expects($this->once())->method('batchDirExists')->with($magentoBaseDir, $batchId)->willReturn(true);
        $apiBatchesMock->expects($this->once())->method('removeBatchDir')->with($magentoBaseDir, $batchId);

        $responseMock->expects($this->once())->method('setBody');

        $mailchimperrorsControllerMock->downloadresponseAction();
    }
}
