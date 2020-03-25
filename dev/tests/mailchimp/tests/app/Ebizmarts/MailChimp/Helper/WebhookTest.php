<?php

class Ebizmarts_MailChimp_Helper_WebhookTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Mage::app('default');
    }

    public function testHandleWebhookChange()
    {
        $scopeId = 0;
        $scope = 'default';
        $realScopeArray = array('scope_id' => 0, 'scope' => 'default');
        $listId = 'a1s2d3f4g5';

        $webhookHelperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Webhook::class)
            ->disableOriginalConstructor()
            ->setMethods(
                array('deleteCurrentWebhook', 'createNewWebhook', 'getHelper')
            )
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(
                array('getRealScopeForConfig', 'getGeneralList', 'isSubscriptionEnabled')
            )
            ->getMock();

        $webhookHelperMock->expects($this->once())->method('getHelper')->willReturn($helperMock);
        $helperMock
            ->expects($this->once())
            ->method('getRealScopeForConfig')
            ->with(Ebizmarts_MailChimp_Model_Config::GENERAL_LIST, $scopeId, $scope)
            ->willReturn($realScopeArray);
        $helperMock->expects($this->once())->method('getGeneralList')->with($scopeId, $scope)->willReturn($listId);
        $webhookHelperMock
            ->expects($this->once())
            ->method('deleteCurrentWebhook')
            ->with($realScopeArray['scope_id'], $realScopeArray['scope'], $listId);
        $helperMock->expects($this->once())->method('isSubscriptionEnabled')->with($scopeId, $scope)->willReturn(1);
        $webhookHelperMock->expects($this->once())->method('createNewWebhook')->with($scopeId, $scope, $listId);

        $webhookHelperMock->handleWebhookChange($scopeId, $scope);
    }
}
