<?php

/**
 * mailchimp-lib Magento Component
 *
 * @category Ebizmarts
 * @package mailchimp-lib
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Ebizmarts_MailChimp_Model_Api_subscribers
{
    const BATCH_LIMIT = 100;

    public function createBatchJson($listId, $storeId, $limit)
    {
        //get subscribers
        $collection = Mage::getModel('newsletter/subscriber')->getCollection()
            ->addFieldToFilter('subscriber_status', array('eq' => 1))
            ->addFieldToFilter('store_id', array('eq' => $storeId))
            ->addFieldToFilter('mailchimp_sync_delta', array(
                array('null' => true),
                array('eq' => ''),
                array('lt' => Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_SUB_MCMINSYNCDATEFLAG, $storeId))
            ));
        $collection->getSelect()->limit($limit);
        $subscriberArray = array();
        $batchId = Ebizmarts_MailChimp_Model_Config::IS_SUBSCRIBER . '_' . date('Y-m-d-H-i-s');

        $counter = 0;
        foreach ($collection as $subscriber) {
            $data = $this->_buildSubscriberData($subscriber);
            $md5HashEmail = md5(strtolower($subscriber->getSubscriberEmail()));
            $subscriberJson = "";

            //enconde to JSON
            try {
                $subscriberJson = json_encode($data);

            } catch (Exception $e) {
                //json encode failed
                Mage::helper('mailchimp')->logError("Subscriber ".$subscriber->getSubscriberId()." json encode failed");
            }

            if (!empty($subscriberJson)) {
                $subscriberArray[$counter]['method'] = "PUT";
                $subscriberArray[$counter]['path'] = "/lists/" . $listId . "/members/" . $md5HashEmail;
                $subscriberArray[$counter]['operation_id'] = $batchId . '_' . $subscriber->getSubscriberId();
                $subscriberArray[$counter]['body'] = $subscriberJson;

                //update subscribers delta
                $subscriber->setData("mailchimp_sync_delta", Varien_Date::now());
                $subscriber->setData("mailchimp_sync_error", "");
                $subscriber->save();
            }
            $counter += 1;
        }
        return $subscriberArray;
    }

    protected function _buildSubscriberData($subscriber)
    {
        $data = array();
        $data["email_address"] = $subscriber->getSubscriberEmail();
        $mergeVars = $this->getMergeVars($subscriber);
        if($mergeVars) {
            $data["merge_fields"] = $mergeVars;
        }
        $data["status_if_new"] = $this->_getMCStatus($subscriber->getStatus());

        return $data;
    }

    /**
     * @param $subscriber
     */
    public function updateSubscriber($subscriber){
        $apiKey = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_APIKEY);
        $listId = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_LIST);
        $status = $this->_getMCStatus($subscriber->getStatus());
        $api = new Ebizmarts_Mailchimp($apiKey,null,'Mailchimp4Magento'.(string)Mage::getConfig()->getNode('modules/Ebizmarts_MailChimp/version'));
        $mergeVars = $this->getMergeVars($subscriber);

        try {
            $md5HashEmail = md5(strtolower($subscriber->getSubscriberEmail()));
            $api->lists->members->addOrUpdate($listId, $md5HashEmail, null, $status, $mergeVars, null, null, null, null, $subscriber->getSubscriberEmail(), $status);
            $subscriber->setData("mailchimp_sync_delta", Varien_Date::now());
            $subscriber->setData("mailchimp_sync_error", "");
        }catch(Mailchimp_Error $e){
            Mage::helper('mailchimp')->logError($e->getFriendlyMessage());
            Mage::getSingleton('adminhtml/session')->addError($e->getFriendlyMessage());
        }catch (Exception $e){
            Mage::helper('mailchimp')->logError($e->getMessage());
        }
    }

    /**
     * Get status to send confirmation if Need to Confirm enabled on Magento
     *
     * @param null $status
     * @return string
     */
    protected function _getMCStatus($status = null)
    {
        if($status == Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED) {
            $status = 'unsubscribed';
        }
        elseif(Mage::helper('mailchimp')->getConfigValue(Mage_Newsletter_Model_Subscriber::XML_PATH_CONFIRMATION_FLAG) && ($status == Mage_Newsletter_Model_Subscriber::STATUS_NOT_ACTIVE || $status == Mage_Newsletter_Model_Subscriber::STATUS_UNCONFIRMED))
        {
            $status = 'pending';
        }
        elseif($status == Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED)
        {
            $status = 'subscribed';
        }
        return $status;
    }

    public function removeSubscriber($subscriber){
        $apiKey = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_APIKEY);
        $listId = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_LIST);
        $api = new Ebizmarts_Mailchimp($apiKey,null,'Mailchimp4Magento'.(string)Mage::getConfig()->getNode('modules/Ebizmarts_MailChimp/version'));
        try {
            $md5HashEmail = md5(strtolower($subscriber->getSubscriberEmail()));
            $api->lists->members->update($listId, $md5HashEmail, null, 'unsubscribed');
        }
        catch(Mailchimp_Error $e){
            Mage::helper('mailchimp')->logError($e->getFriendlyMessage());
            Mage::getSingleton('adminhtml/session')->addError($e->getFriendlyMessage());
        }
        catch (Exception $e){
            Mage::helper('mailchimp')->logError($e->getMessage());
        }
    }

    /**
     * @param $subscriber
     */
    public function deleteSubscriber($subscriber){
        $apiKey = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_APIKEY);
        $listId = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_LIST);
        $api = new Ebizmarts_Mailchimp($apiKey);
        try {
            $md5HashEmail = md5(strtolower($subscriber->getSubscriberEmail()));
            $api->lists->members->update($listId, $md5HashEmail, null, 'cleaned');
        }
        catch(Mailchimp_Error $e){
            Mage::helper('mailchimp')->logError($e->getFriendlyMessage());
            Mage::getSingleton('adminhtml/session')->addError($e->getFriendlyMessage());
        }
        catch (Exception $e){
            Mage::helper('mailchimp')->logError($e->getMessage());
        }
    }

    protected function getMergeVars($subscriber)
    {
        $mergeVars = array();
        if($subscriber->getSubscriberFirstname()){
            $mergeVars['FNAME'] = $subscriber->getSubscriberFirstname();
        }
        if($subscriber->getSubscriberLastname()){
            $mergeVars['LNAME'] = $subscriber->getSubscriberLastname();
        }
        return (!empty($mergeVars)) ? $mergeVars : null;

//        $storeId = $subscriber->getStoreId();
//        $maps = unserialize(Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_MAP_FIELDS, $storeId));
//        $websiteId = Mage::getModel('core/store')->load($storeId)->getWebsiteId();
//        $customer = Mage::getModel('customer/customer')->setWebsiteId($websiteId)->loadByEmail($subscriber->getSubscriberEmail());
//        $mergeVars = array();
//        foreach ($maps as $map) {
//            $customAtt = $map['magento'];
//            $chimpTag = $map['mailchimp'];
//            if ($chimpTag && $customAtt) {
//                $key = strtoupper($chimpTag);
//                $attrSetId = Mage::getResourceModel('eav/entity_attribute_collection')
//                    ->setEntityTypeFilter(1)
//                    ->addSetInfo()
//                    ->getData();
//                foreach ($attrSetId as $attribute) {
//                    if ($attribute['attribute_id'] == $customAtt) {
//                        $attributeCode = $attribute['attribute_code'];
//                        if ($customer->getId()) {
//                            if ($customer->getData($attributeCode)) {
//                                switch ($attributeCode) {
//                                    case 'default_billing':
//                                    case 'default_shipping':
//                                        $addr = explode('_', $attributeCode);
//                                        $address = $customer->{'getPrimary' . ucfirst($addr[1]) . 'Address'}();
//                                        if (!$address) {
//                                            if ($customer->{'getDefault' . ucfirst($addr[1])}()) {
//                                                $address = Mage::getModel('customer/address')->load($customer->{'getDefault' . ucfirst($addr[1])}());
//                                            }
//                                        }
//                                        if ($address) {
//                                            $mergeVars[$key] = array(
//                                                'addr1' => $address->getStreet(1),
//                                                'addr2' => $address->getStreet(2),
//                                                'city' => $address->getCity(),
//                                                'state' => (!$address->getRegion() ? $address->getCity() : $address->getRegion()),
//                                                'zip' => $address->getPostcode(),
//                                                'country' => $address->getCountryId()
//                                            );
//                                            $telephone = $address->getTelephone();
//                                            if ($telephone) {
//                                                $mergeVars['TELEPHONE'] = $telephone;
//                                            }
//                                            $company = $address->getCompany();
//                                            if ($company) {
//                                                $mergeVars['COMPANY'] = $company;
//                                            }
//                                            $country = $address->getCountryId();
//                                            if ($country) {
//                                                $countryName = Mage::getModel('directory/country')->load($country)->getName();
//                                                $mergeVars['COUNTRY'] = $countryName;
//                                            }
//                                            $zipCode = $address->getPostcode();
//                                            if ($zipCode) {
//                                                $mergeVars['ZIPCODE'] = $zipCode;
//                                            }
//                                        }
//                                        break;
//                                    case 'gender':
//                                        $genderValue = $customer->getData($attributeCode);
//                                        if ($genderValue == 1) {
//                                            $mergeVars[$key] = 'Male';
//                                        } elseif ($genderValue == 2) {
//                                            $mergeVars[$key] = 'Female';
//                                        }
//                                        break;
//                                    case 'group_id':
//                                        $group_id = (int)$customer->getData($attributeCode);
//                                        $customerGroup = Mage::helper('customer')->getGroups()->toOptionHash();
//                                        $mergeVars[$key] = $customerGroup[$group_id];
//                                        break;
//                                    default:
//                                        if($customer->getData($attributeCode)) {
//                                            $mergeVars[$key] = $customer->getData($attributeCode);
//                                        }
//                                        break;
//                                }
//                            }
//                        } else {
//                            switch ($attributeCode) {
//                                case 'group_id':
//                                    $mergeVars[$key] = 'NOT LOGGED IN';
//                                    break;
//                                case 'store_id':
//                                    $mergeVars[$key] = $storeId;
//                                    break;
//                                case 'website_id':
//                                    $websiteId = Mage::getModel('core/store')->load($storeId)->getWebsiteId();
//                                    $mergeVars[$key] = $websiteId;
//                                    break;
//                                case 'created_in':
//                                    $storeCode = Mage::getModel('core/store')->load($storeId)->getCode();
//                                    $mergeVars[$key] = $storeCode;
//                                    break;
//                                case 'firstname':
//                                    $firstName = $subscriber->getSubscriberFirstname();
//                                    if ($firstName) {
//                                        $mergeVars[$key] = $firstName;
//                                    }
//                                    break;
//                                case 'lastname':
//                                    $lastName = $subscriber->getSubscriberLastname();
//                                    if ($lastName) {
//                                        $mergeVars[$key] = $lastName;
//                                    }
//                            }
//                        }
//                    }
//                }
//            }
//        }
//        return (!empty($mergeVars)) ? $mergeVars : null;
    }
}