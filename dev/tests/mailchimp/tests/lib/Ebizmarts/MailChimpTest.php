<?php

class MailChimpTest extends PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        Mage::app('default');
    }

    public function testPublicPropertiesExist()
    {
        /** @var Mandrill_Message|PHPUnit_Framework_MockObject_MockObject $messageMock */
        $apiMock = $this->getMockBuilder('Ebizmarts_MailChimp')
            ->disableOriginalConstructor()
            ->getMock();

        $this->assertObjectHasAttribute('root', $apiMock);
        $this->assertObjectHasAttribute('authorizedApps', $apiMock);
        $this->assertObjectHasAttribute('automation', $apiMock);
        $this->assertObjectHasAttribute('batchOperation', $apiMock);
        $this->assertObjectHasAttribute('campaignFolders', $apiMock);
        $this->assertObjectHasAttribute('campaigns', $apiMock);
        $this->assertObjectHasAttribute('conversations', $apiMock);
        $this->assertObjectHasAttribute('ecommerce', $apiMock);
        $this->assertObjectHasAttribute('fileManagerFiles', $apiMock);
        $this->assertObjectHasAttribute('fileManagerFolders', $apiMock);
        $this->assertObjectHasAttribute('lists', $apiMock);
        $this->assertObjectHasAttribute('reports', $apiMock);
        $this->assertObjectHasAttribute('templateFolders', $apiMock);
        $this->assertObjectHasAttribute('templates', $apiMock);
    }
}
