<?php

class Ebizmarts_MailChimp_Model_MessageTest extends PHPUnit_Framework_TestCase
{
    const ATTACHMENT_TEST_CONTENT = 'attachment. Test.';
    const ATTACHMENT_TEST_NAME = 'test.txt';

    public function setUp()
    {
        Mage::app('default');
    }

    public function testHasAttachment()
    {
        $apiKey = 'a1s2d3f4g5';

        /** @var Mandrill_Message|PHPUnit_Framework_MockObject_MockObject $messageMock */
        $messageMock = $this->getMockBuilder('Mandrill_Message')
            ->setConstructorArgs(array($apiKey))
            ->getMock();

        $this->assertFalse($messageMock->hasAttachments);
    }

    public function testAddGetAttachment()
    {
        $apiKey = 'a1s2d3f4g5';

        /** @var Mandrill_Message|PHPUnit_Framework_MockObject_MockObject $messageMock */
        $messageMock = $this->getMockBuilder('Mandrill_Message')
            ->setConstructorArgs(array($apiKey))
            ->setMethods(array('getMail'))
            ->getMock();

        $this->assertFalse($messageMock->hasAttachments);

        $mp = $this->createAttachment();

        $messageMock->addAttachment($mp);

        $this->assertTrue($messageMock->hasAttachments);

        $attachments = $messageMock->getAttachments();
        $this->assertCount(1, $attachments);

        $firstAttachment = $attachments[0];
        $this->assertArrayHasKey('type', $firstAttachment);
        $this->assertArrayHasKey('name', $firstAttachment);
        $this->assertArrayHasKey('content', $firstAttachment);

        $this->assertEquals('text/plain', $firstAttachment['type']);
        $this->assertEquals(self::ATTACHMENT_TEST_NAME, $firstAttachment['name']);
        $this->assertEquals(base64_encode(self::ATTACHMENT_TEST_CONTENT), $firstAttachment['content']);
    }

    /**
     * @return Zend_Mime_Part
     */
    private function createAttachment()
    {
        $mp              = new Zend_Mime_Part(self::ATTACHMENT_TEST_CONTENT);
        $mp->encoding    = Zend_Mime::ENCODING_BASE64;
        $mp->type        = Zend_Mime::TYPE_TEXT;
        $mp->disposition = Zend_Mime::DISPOSITION_ATTACHMENT;
        $mp->filename    = self::ATTACHMENT_TEST_NAME;

        return $mp;
    }

}
