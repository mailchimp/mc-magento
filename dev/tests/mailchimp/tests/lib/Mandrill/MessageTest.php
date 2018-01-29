<?php

class Ebizmarts_MailChimp_Model_MessageTest extends PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        Mage::app('default');
    }

    public function testHasAttachment()
    {
        /** @var Mandrill_Message|PHPUnit_Framework_MockObject_MockObject $messageMock */
        $messageMock = $this->getMockBuilder('Mandrill_Message')
            ->disableOriginalConstructor()
            ->getMock();

        $this->assertFalse($messageMock->hasAttachments);
    }

}