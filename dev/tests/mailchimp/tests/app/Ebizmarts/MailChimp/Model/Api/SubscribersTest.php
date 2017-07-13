<?php

class Ebizmarts_MailChimp_Model_Api_SubscribersTest extends PHPUnit_Framework_TestCase
{
    const DEFAULT_STORE_ID = 1;

    public function setUp()
    {
        Mage::app('default');
    }

    /**
     * @param $magentoStatus
     * @param $storeId
     * @param $expected
     * @dataProvider magentoSubscriberStatus
     */
    public function testMailchimpStatus($magentoStatus, $storeId, $expected)
    {
        $subscribersApiMock =
            $this->getMockBuilder(Ebizmarts_MailChimp_Model_Api_Subscribers::class)
                ->disableOriginalConstructor()
                ->setMethods(array('magentoConfigNeedsConfirmation'))
                ->getMock();

        $return = $subscribersApiMock->translateMagentoStatusToMailchimpStatus($magentoStatus, $storeId);

        $this->assertEquals($expected, $return);
    }

    /**
     * @return array(subscriber_status, magento_store_id, subscriber_status_string)
     */
    public function magentoSubscriberStatus()
    {
        return array(
            array(Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED, self::DEFAULT_STORE_ID, "subscribed"),
            array(Mage_Newsletter_Model_Subscriber::STATUS_NOT_ACTIVE, self::DEFAULT_STORE_ID, "pending"),
            array(Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED, self::DEFAULT_STORE_ID, "unsubscribed"),
            array(Mage_Newsletter_Model_Subscriber::STATUS_UNCONFIRMED, self::DEFAULT_STORE_ID, "pending"),
        );
    }
}