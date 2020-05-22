<?php

class Ebizmarts_MailChimp_Helper_MigrationTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Mage::app('default');
    }

    public function testHandleDeleteMigrationConfigData()
    {
        $arrayMigrationConfigData = array('115' => true, '116' => true, '1164' => true);

        $migrationHelperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Migration::class)
            ->disableOriginalConstructor()
            ->setMethods(
                array(
                    'delete115MigrationConfigData', 'delete116MigrationConfigData',
                    'delete1164MigrationConfigData', 'getHelper'
                )
            )
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getConfig'))
            ->getMock();

        $modelConfigMock = $this->getMockBuilder(Mage_Core_Model_Config::class)
            ->disableOriginalConstructor()
            ->setMethods(array('cleanCache'))
            ->getMock();

        $migrationHelperMock->expects($this->once())->method('getHelper')->willReturn($helperMock);
        $migrationHelperMock->expects($this->once())->method('delete115MigrationConfigData');
        $migrationHelperMock->expects($this->once())->method('delete116MigrationConfigData');
        $migrationHelperMock->expects($this->once())->method('delete1164MigrationConfigData');
        $helperMock->expects($this->once())->method('getConfig')->willReturn($modelConfigMock);

        $modelConfigMock->expects($this->once())->method('cleanCache');

        $migrationHelperMock->handleDeleteMigrationConfigData($arrayMigrationConfigData);
    }
}
