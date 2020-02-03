<?php
/**
 * mailchimp-lib Magento Component
 *
 * @category  Ebizmarts
 * @package   mailchimp-lib
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Ebizmarts_MailChimp_Model_Api_Subscribers_InterestGroupHandle
{
    /**
     * @var Ebizmarts_MailChimp_Helper_Data
     */
    protected $_helper;
    /**
     * @var Ebizmarts_MailChimp_Helper_Date
     */
    protected $_dateHelper;

    public function __construct()
    {
        $this->_helper = Mage::helper('mailchimp');
        $this->_dateHelper = Mage::helper('mailchimp/date');
    }

    /**
     * @param $groupings
     * @param $customerData
     * @param $listId
     * @param bool $isDataSubscriber
     * @throws Ebizmarts_MailChimp_Helper_Data_ApiKeyException
     * @throws MailChimp_Error
     * @throws MailChimp_HttpError
     */
    public function processGroupsData($groupings, $customerData, $listId, $isDataSubscriber = false)
    {
        $groups = array();
        $helper = $this->getHelper();
        $dateHelper = $this->getDateHelper();

        $customerEmail = $customerData->getEmail();
        $subscriber = $this->getSubscriberModel()->loadByEmail($customerEmail);
        $storeId = $subscriber->getStoreId();

        $api = $helper->getApi($storeId);
        $apiInterests = $api->getLists()->getInterestCategory()->getInterests();

        foreach ($groupings as $grouping) {
            $interests = $apiInterests->getAll($listId, $grouping['unique_id']);

            $groupsSave = array();
            foreach ($interests['interests'] as $mcGroup) {
                if (strpos($grouping['groups'], $mcGroup['name']) !== false) {
                    $groupsSave [$mcGroup['id']] = $mcGroup['id'];
                }
            }
            $groups [$grouping['unique_id']]= $groupsSave;
        }

        if (!$isDataSubscriber) {
            $customerId = $customerData->getId();
        } else {
            $customerId = $customerData->getCustomerId();
        }

        $interestGroup = $this->getInterestGroupModel();

        $subscriberId = $subscriber->getSubscriberId();
        $interestGroup->getByRelatedIdStoreId($customerId, $subscriberId, $storeId);
        $encodedGroups = $helper->arrayEncode($groups);

        $interestGroup->setGroupdata($encodedGroups);
        $interestGroup->setSubscriberId($subscriberId);
        $interestGroup->setCustomerId($customerId);
        $interestGroup->setStoreId($storeId);
        $interestGroup->setUpdatedAt($dateHelper->getCurrentDateTime());
        $interestGroup->save();
    }

    /**
     * @return false|Mage_Core_Model_Abstract
     */
    protected function getSubscriberModel()
    {
        return Mage::getModel('newsletter/subscriber');
    }

    /**
     * @return Ebizmarts_MailChimp_Model_Interestgroup
     */
    protected function getInterestGroupModel()
    {
        return Mage::getModel('mailchimp/interestgroup');
    }

    /**
     * @return Ebizmarts_MailChimp_Helper_Data
     */
    protected function getHelper()
    {
        return $this->_helper;
    }

    /**
     * @return Ebizmarts_MailChimp_Helper_Date|Mage_Core_Helper_Abstract
     */
    protected function getDateHelper()
    {
        return $this->_dateHelper;
    }
}
