<?php

class Ebizmarts_MailChimp_Model_ProcessWebhookTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Mage::app('default');
    }

    public function testWebhookProfileCustomerExists()
    {
        $data = array('list_id' => 'a1s2d3f4t5', 'email' => 'pepe@ebizmarts.com');

        $processWebhookMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_ProcessWebhook::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getMailchimpTagsModel'))
            ->getMock();

        $mailchimpTagsApiMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Subscribers_MailchimpTags::class)
            ->disableOriginalConstructor()
            ->setMethods(array('processMergeFields'))
            ->getMock();

        $processWebhookMock->expects($this->once())
            ->method('getMailchimpTagsModel')
            ->willReturn($mailchimpTagsApiMock);

        $mailchimpTagsApiMock->expects($this->once())
            ->method('processMergeFields')
            ->with($data)
            ->willReturnSelf();

        $processWebhookMock->_profile($data);
    }
}
