<?php

class Ebizmarts_MailChimp_Model_Api_CartsTest extends PHPUnit_Framework_TestCase
{
    /**
 * @var Ebizmarts_MailChimp_Model_Api_Carts
*/
    private $cartsApiMock;

    public function setUp()
    {
        Mage::app('default');

        $this->cartsApiMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Carts::class);
    }

    public function tearDown()
    {
        $this->cartsApiMock = null;
    }

    public function testCreateBatchJson()
    {
        $mailchimpStoreId = 'a1s2d3f4g5h6j7k8l9n0';
        $magentoStoreId = 1;
        $batchArray = array();

        $cartsApiMock = $this->cartsApiMock->setMethods(array('getHelper', '_getConvertedQuotes', '_getModifiedQuotes', '_getNewQuotes'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('isAbandonedCartEnabled', 'getAbandonedCartFirstDate', 'getDateMicrotime', 'getResendTurn'))
            ->getMock();

        $cartsApiMock->expects($this->once())->method('getHelper')->willReturn($helperMock);
        $cartsApiMock->expects($this->once())->method('_getConvertedQuotes')->with($mailchimpStoreId, $magentoStoreId)->willReturn($batchArray);
        $cartsApiMock->expects($this->once())->method('_getModifiedQuotes')->with($mailchimpStoreId, $magentoStoreId)->willReturn($batchArray);
        $cartsApiMock->expects($this->once())->method('_getNewQuotes')->with($mailchimpStoreId, $magentoStoreId)->willReturn($batchArray);

        $helperMock->expects($this->once())->method('isAbandonedCartEnabled')->with($magentoStoreId)->willReturn(true);
        $helperMock->expects($this->once())->method('getAbandonedCartFirstDate')->with($magentoStoreId)->willReturn('00-00-00 00:00:00');
        $helperMock->expects($this->once())->method('getDateMicrotime')->willReturn('00-00-00 00:00:00');
        $helperMock->expects($this->once())->method('getResendTurn')->with($magentoStoreId)->willReturn(null);

        $cartsApiMock->createBatchJson($mailchimpStoreId, $magentoStoreId);
    }
}
