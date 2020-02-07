<?php

class Ebizmarts_MailChimp_Model_Adminhtml_Resendsubscribers_CommentTest extends PHPUnit_Framework_TestCase
{
    /**
     * @param array $data
     * @dataProvider commentTextProvider
     */
    public function testGetCommentText($data)
    {
        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getCurrentScope'))
            ->getMock();

        $commentMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_Adminhtml_Resendsubscribers_Comment::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getMcHelper'))
            ->getMock();

        $commentMock
            ->expects($this->once())
            ->method('getMcHelper')
            ->willReturn($helperMock);

        $helperMock
            ->expects($this->once())
            ->method('getCurrentScope')
            ->willReturn($data['scopeArray']);

        $result = $commentMock->getCommentText();

        $this->assertEquals($data['text'], $result);

    }

    public function commentTextProvider()
    {
        return array(
            "testDefaultConfig" => array(
                array(
                    "scopeArray" => array("scope" => "default"),
                    "text" => "This will resend the subscribers for all Websites and Store Views."
                )
            ),
            "testWebsiteScope" => array(
                array(
                    "scopeArray" => array("scope" => "websites"),
                    "text" => "This will resend the subscribers for this Website only."
                )
            ),
            "testStoreViewScope" => array(
                array(
                    "scopeArray" => array("scope" => "stores"),
                    "text" => "This will resend the subscribers for this Store View only."
                )
            )
        );
    }

}
