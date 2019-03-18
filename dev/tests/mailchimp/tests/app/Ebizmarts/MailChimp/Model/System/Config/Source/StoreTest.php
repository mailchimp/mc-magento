<?php

class Ebizmarts_MailChimp_Model_System_Config_Source_StoreTest extends PHPUnit_Framework_TestCase
{
    const DEFAULT_STORE_ID = 1;

    public function setUp()
    {
        Mage::app('default');
    }

    public function testToOptionArray()
    {
        $selectMsg = '--- Select a Mailchimp Store ---';
        $mcStores = array(
            'stores' => array(
                array(
                    'id' => 'a1s2d3f4g5h6j7k8l9p0',
                    'list_id' => 'a1s2d3f4g5',
                    'name' => 'Madison Island - English',
                    'platform' => 'Magento',
                    'domain' => 'domain.com',
                    'is_syncing' => false,
                    'email_address' => 'email@example.com',
                    'currency_code' => 'USD',
                    'connected_site' => array(
                        'site_foreign_id'  => 'a1s2d3f4g5h6j7k8l9p0',
                        'site_script' => array(
                            'url' => 'https://chimpstatic.com/mcjs-connected/js/users/1647ea7abc3f2f3259e2613f9/a946187aed2d57d15cdac9987.js',
                            'fragment' => '<script id="mcjs">!function(c,h,i,m,p){m=c.createElement(h),p=c.getElementsByTagName(h)[0],m.async=1,m.src=i,p.parentNode.insertBefore(m,p)}(document,"script","https://chimpstatic.com/mcjs-connected/js/users/1647ea7abc3f2f3259e2613f9/a946187aed2d57d15cdac9987.js");</script>'
                        ),
                    ),
                    'automations' => array(
                        'abandoned_cart' => array(
                            'is_supported' => 1
                        ),
                        'abandoned_browse' => array(
                            'is_supported' => 1
                        )
                    ),
                    'list_is_active' => 1,
                    'created_at' => '2016-05-26T18:30:55+00:00',
                    'updated_at' => '2019-03-04T19:53:57+00:00'
                )
            )
        );

        $listMock = $this->getMockBuilder(Ebizmarts_MailChimp_Model_System_Config_Source_Store::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getHelper', 'getMCStores'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->getMock();

        $listMock->expects($this->once())->method('getHelper')->willReturn($helperMock);
        $listMock->expects($this->once())->method('getMCStores')->willReturn($mcStores);

        $helperMock->expects($this->once())->method('__')->with($selectMsg)->willReturn($selectMsg);

        $expectedResult = array(
            array(
                'value' => '',
                'label' => $selectMsg
            ),
            array(
                'value' => 'a1s2d3f4g5h6j7k8l9p0',
                'label' => 'Madison Island - English',
            )
        );
        $result = $listMock->toOptionArray();
        $this->assertEquals($expectedResult, $result);
    }
}
