<?php
/**
 * Created by Ebizmarts Team.
 * Date: 2/20/19
 * Time: 3:11 PM
 */
class Ebizmarts_MailChimp_Block_Adminhtml_NotificationsTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var \Ebizmarts_MailChimp_Block_Adminhtml_Notifications $_block
     */
    private $_block;
    /**
     * @var \Ebizmarts_MailChimp_Helper_Data $_helperMock
     */
    private $_helperMock;

    public function setUp()
    {
        $app = Mage::app('default');
        $layout = $app->getLayout();
        $this->_block = new Ebizmarts_MailChimp_Block_Adminhtml_Notifications;
        $this->_helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('isImageCacheFlushed', 'isEcomSyncDataEnabledInAnyScope', 'getUrlForNotification'))
            ->getMock();

        /* We are required to set layouts before we can do anything with blocks */
        $this->_block->setLayout($layout);
    }

    public function testGetMessageNotification()
    {
        $helperMock = $this->_helperMock;

        $blockMock = $this->getMockBuilder(Ebizmarts_MailChimp_Block_Adminhtml_Notifications::class)
            ->disableOriginalConstructor()
            ->setMethods(array('makeHelper'))
            ->getMock();

        $blockMock->expects($this->once())->method('makeHelper')->willReturn($helperMock);

        $helperMock->expects($this->once())->method('isImageCacheFlushed')->willReturn(true);
        $helperMock->expects($this->once())->method('isEcomSyncDataEnabledInAnyScope')->willReturn(true);

        $blockMock->getMessageNotification();
    }

    public function testGetMessage()
    {
        $helperMock = $this->_helperMock;

        $blockMock = $this->getMockBuilder(Ebizmarts_MailChimp_Block_Adminhtml_Notifications::class)
            ->disableOriginalConstructor()
            ->setMethods(array('makeHelper'))
            ->getMock();

        $blockMock->expects($this->once())->method('makeHelper')->willReturn($helperMock);

        $blockMock->getMessage();
    }

    public function testGetAjaxCheckURL()
    {
        $helperMock = $this->_helperMock;

        $blockMock = $this->getMockBuilder(Ebizmarts_MailChimp_Block_Adminhtml_Notifications::class)
            ->disableOriginalConstructor()
            ->setMethods(array('makeHelper'))
            ->getMock();

        $blockMock->expects($this->once())->method('makeHelper')->willReturn($helperMock);

        $helperMock->expects($this->once())->method('getUrlForNotification');

        $blockMock->getAjaxCheckUrl();
    }
}
