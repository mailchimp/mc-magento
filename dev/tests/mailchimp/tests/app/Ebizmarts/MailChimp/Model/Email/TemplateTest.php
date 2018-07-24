<?php

class Ebizmarts_MailChimp_Model_TemplateTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Mage::app('default');
    }

    public function testSend()
    {
        $storeId = 1;
        $enabled = true;
        $email = 'email@address.com';
        $name = 'name';
        $variables = array('email' => $email, 'name' => $name);
        $message = 'message';
        $subject = 'subject';
        $returnPath = 1;
        $senderEmail = 'sender@email.com';
        $bcc = array('bcc@email.com');
        $userAgent = 'Ebizmarts_Mandrill1.1.12/MageCE1.9.3.7';
        $emailArray = array ('subject' => 'subject', 'to' => array(array('email' => $email, 'name' => $name), array('email' => 'bcc@email.com', 'type' => 'bcc')), 'from_name' => 'name',
            'from_email' => $senderEmail, 'headers' => array($userAgent), 'tags' => array('default_tag'), 'text' => 'message');
        $mandrillSenders = array(array('domain' => 'email.com'));

        /**
         * @var \Ebizmarts_MailChimp_Model_Email_Template $templateMock
         */
        $templateMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Email_Template::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getDesignConfig', 'isValidForSend', 'setUseAbsoluteLinks', 'getProcessedTemplate',
                'getProcessedTemplateSubject', 'getSenderEmail', 'getMail', 'getSenderName', 'isPlain', 'makeMandrillHelper',
                'hasQueue', 'getQueue', 'makeHelper', 'getSendingSetReturnPath', 'getSendersDomains', 'sendMail'))
            ->getMock();

        $varienObjectMock = $this->getMockBuilder(Varien_Object::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getStore'))
            ->getMock();

        $mandrillHelperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Mandrill::class)
            ->disableOriginalConstructor()
            ->setMethods(array('isMandrillEnabled', 'getUserAgent'))
            ->getMock();

        $mailObjectMock = $this->getMockBuilder(Mandrill_Message::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getBcc', 'getHeaders'))
            ->getMock();

        $templateMock->expects($this->once())->method('getDesignConfig')->willReturn($varienObjectMock);

        $varienObjectMock->expects($this->once())->method('getStore')->willReturn($storeId);

        $templateMock->expects($this->once())->method('makeMandrillHelper')->willReturn($mandrillHelperMock);

        $mandrillHelperMock->expects($this->once())->method('isMandrillEnabled')->with($storeId)->willReturn($enabled);

        $templateMock->expects($this->once())->method('isValidForSend')->willReturn(true);
        $templateMock->expects($this->once())->method('setUseAbsoluteLinks')->willReturn(true);
        $templateMock->expects($this->once())->method('getProcessedTemplate')->with($variables, true)->willReturn($message);
        $templateMock->expects($this->once())->method('getProcessedTemplateSubject')->with($variables)->willReturn($subject);
        $templateMock->expects($this->once())->method('getSendingSetReturnPath')->willReturn($returnPath);
        $templateMock->expects($this->exactly(2))->method('getSenderEmail')->willReturnOnConsecutiveCalls($senderEmail, $senderEmail);
        $templateMock->expects($this->once())->method('getMail')->willReturn($mailObjectMock);

        $mailObjectMock->expects($this->once())->method('getBcc')->willReturn($bcc);

        $templateMock->expects($this->once())->method('getSenderName')->willReturn($name);
        $templateMock->expects($this->once())->method('getSendersDomains')->with($mailObjectMock)->willReturn($mandrillSenders);

        $mailObjectMock->expects($this->once())->method('getHeaders')->willReturn(array());

        $mandrillHelperMock->expects($this->once())->method('getUserAgent')->willReturn($userAgent);

        $templateMock->expects($this->once())->method('isPlain')->willReturn(true);
        $templateMock->expects($this->once())->method('hasQueue')->willReturn(true);
        $templateMock->expects($this->once())->method('getQueue')->willReturn(true);
        $templateMock->expects($this->once())->method('sendMail')->with($emailArray, $mailObjectMock);

        $templateMock->send($email, $name, $variables);
    }
}
