<?php

class Ebizmarts_MailChimp_Model_TemplateTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Mage::app('default');
    }

    public function testSend()
    {
        $apiKey = 'a1s2d3f4g5';
        $storeId = 1;
        $enabled = true;
        $email = 'email@address.com';
        $name = 'name';
        $variables = array('email' => $email, 'name' => $name, 'tags' => array('tagOne', 'tagTwo'));
        $message = 'message';
        $subject = 'subject';
        $returnPath = 1;
        $senderEmail = 'sender@email.com';
        $bcc = array('bcc@email.com');
        $userAgent = 'Ebizmarts_Mandrill1.1.12/MageCE1.9.3.7';
        $emailArray = array ('subject' => 'subject', 'to' => array(array('email' => $email, 'name' => $name), array('email' => 'bcc@email.com', 'type' => 'bcc')), 'from_name' => 'name',
            'from_email' => $senderEmail, 'headers' => array($userAgent), 'tags' => array('tagOne', 'tagTwo'), 'html' => 'message');
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
            ->setConstructorArgs(array($apiKey))
            ->setMethods(array('getBcc', 'getHeaders', 'getAttachments'))
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

        $mailObjectMock->expects($this->once())->method('getAttachments')->willReturn(null);

        $templateMock->expects($this->once())->method('isPlain')->willReturn(false);
        $templateMock->expects($this->once())->method('hasQueue')->willReturn(false);
        $templateMock->expects($this->once())->method('sendMail')->with($emailArray, $mailObjectMock);

        $templateMock->send($email, $name, $variables);
    }

    public function testGetMailCreateNewMandrillEmail()
    {
        $storeId = 1;

        $templateMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Email_Template::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getDesignConfig', 'isMandrillEnabled', 'createMandrillMessage'))
            ->getMock();

        $varienObjectMock = $this->getMockBuilder(Varien_Object::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getStore'))
            ->getMock();

        $templateMock->expects($this->once())->method('getDesignConfig')->willReturn($varienObjectMock);
        $templateMock->expects($this->once())->method('createMandrillMessage')->with($storeId);
        $templateMock->expects($this->once())->method('isMandrillEnabled')->with($storeId)->willReturnOnConsecutiveCalls(
            true,
            true
        );

        $varienObjectMock->expects($this->once())->method('getStore')->willReturn($storeId);

        $templateMock->getMail();
    }

    public function testGetMailMandrillEmailDisabled()
    {
        $storeId = 1;

        $templateMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Email_Template::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getDesignConfig', 'isMandrillEnabled'))
            ->getMock();

        $varienObjectMock = $this->getMockBuilder(Varien_Object::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getStore'))
            ->getMock();

        $templateMock->expects($this->once())->method('getDesignConfig')->willReturn($varienObjectMock);
        $templateMock->expects($this->once())->method('isMandrillEnabled')->with($storeId)->willReturn(false);

        $varienObjectMock->expects($this->once())->method('getStore')->willReturn($storeId);

        $templateMock->getMail();
    }
}
