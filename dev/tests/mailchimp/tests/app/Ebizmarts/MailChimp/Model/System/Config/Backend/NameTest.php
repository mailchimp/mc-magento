<?php

class Ebizmarts_MailChimp_Model_System_Config_Backend_NameTest extends PHPUnit_Framework_TestCase
{
    const DEFAULT_STORE_ID = 1;

    public function setUp()
    {
        Mage::app('default');
    }

    public function testSave()
    {
        $scopeId = 1;
        $scope = 'stores';
        $storeName = 'StoreName';
        $realScope = array('scope_id' => $scopeId, 'scope' => $scope);

        $backendNameMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_System_Config_Backend_Name::class)
                ->disableOriginalConstructor()
                ->setMethods(array('makeHelper', 'isValueChanged', 'getValue', 'getScopeId', 'getScope'))
                ->getMock();


        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getRealScopeForConfig', 'isEcomSyncDataEnabled', 'getMCStoreName', 'changeName'))
            ->getMock();

        $backendNameMock->expects($this->once())->method('getScopeId')->willReturn($scopeId);
        $backendNameMock->expects($this->once())->method('getScope')->willReturn($scope);
        $backendNameMock->expects($this->once())->method('isValueChanged')->willReturn(true);
        $backendNameMock->expects($this->once())->method('getValue')->willReturn('');

        $backendNameMock->expects($this->once())->method('makeHelper')->willReturn($helperMock);

        $helperMock->expects($this->once())->method('getRealScopeForConfig')->with(Ebizmarts_MailChimp_Model_Config::GENERAL_MCSTOREID, $scopeId, $scope)->willReturn($realScope);
        $helperMock->expects($this->once())->method('isEcomSyncDataEnabled')->with($realScope['scope_id'], $realScope['scope'])->willReturn(true);

        $helperMock->expects($this->once())->method('getMCStoreName')->with($scopeId, $scope, true)->willReturn($storeName);

        $helperMock->expects($this->once())->method('changeName')->with($storeName, $scopeId, $scope);

        $backendNameMock->save();
    }
}
