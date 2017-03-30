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

class Ebizmarts_MailChimp_Model_Api_Subscribers
{
    const BATCH_LIMIT = 100;

    public function createBatchJson($listId, $storeId, $limit)
    {
        //get subscribers
        $collection = Mage::getModel('newsletter/subscriber')->getCollection()
            ->addFieldToFilter('subscriber_status', array('eq' => 1))
            ->addFieldToFilter('store_id', array('eq' => $storeId))
            ->addFieldToFilter(
                array(
                    'mailchimp_sync_delta',
                    'mailchimp_sync_delta',
                    'mailchimp_sync_delta',
                    'mailchimp_sync_modified'
                ),
                array(
                    array('null' => true),
                    array('eq' => ''),
                    array('lt' => Mage::helper('mailchimp')->getMCMinSyncDateFlag($storeId)),
                    array('eq' => 1)
                )
            );
        $collection->getSelect()->limit($limit);
        $subscriberArray = array();
        $date = Mage::helper('mailchimp')->getDateMicrotime();
        $batchId = 'storeid-' . $storeId . '_' . Ebizmarts_MailChimp_Model_Config::IS_SUBSCRIBER . '_' . $date;

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
                $errorMessage = "Subscriber ".$subscriber->getSubscriberId()." json encode failed";
                Mage::helper('mailchimp')->logError($errorMessage, $storeId);
            }

            if (!empty($subscriberJson)) {
                $subscriberArray[$counter]['method'] = "PUT";
                $subscriberArray[$counter]['path'] = "/lists/" . $listId . "/members/" . $md5HashEmail;
                $subscriberArray[$counter]['operation_id'] = $batchId . '_' . $subscriber->getSubscriberId();
                $subscriberArray[$counter]['body'] = $subscriberJson;

                //update subscribers delta
                $subscriber->setData("mailchimp_sync_delta", Varien_Date::now());
                $subscriber->setData("mailchimp_sync_error", "");
                $subscriber->setData("mailchimp_sync_modified", 0);
                $subscriber->save();
            }

            $counter++;
        }

        return $subscriberArray;
    }

    protected function _buildSubscriberData($subscriber)
    {
        $storeId = $subscriber->getStoreId();
        $data = array();
        $data["email_address"] = $subscriber->getSubscriberEmail();
        $mergeVars = $this->getMergeVars($subscriber);
        if ($mergeVars) {
            $data["merge_fields"] = $mergeVars;
        }

        $data["status_if_new"] = $this->_getMCStatus($subscriber->getStatus(), $storeId);

        return $data;
    }

    public function getMergeVars($subscriber)
    {
        $storeId = $subscriber->getStoreId();
        $mapFields = Mage::helper('mailchimp')->getMapFields($storeId);
        $maps = unserialize($mapFields);
        $websiteId = Mage::getModel('core/store')->load($storeId)->getWebsiteId();
        $attrSetId = Mage::getResourceModel('eav/entity_attribute_collection')
            ->setEntityTypeFilter(1)
            ->addSetInfo()
            ->getData();
        $mergeVars = array();
        $subscriberEmail = $subscriber->getSubscriberEmail();
        $customer = Mage::getModel('customer/customer')->setWebsiteId($websiteId)->loadByEmail($subscriberEmail);

        foreach ($maps as $map) {
            $customAtt = $map['magento'];
            $chimpTag = $map['mailchimp'];
            if ($chimpTag && $customAtt) {
                $eventValue = null;
                $key = strtoupper($chimpTag);
                if (is_numeric($customAtt)) {
                    foreach ($attrSetId as $attribute) {
                        if ($attribute['attribute_id'] == $customAtt) {
                            $attributeCode = $attribute['attribute_code'];
                            switch ($attributeCode) {
                                case 'email':
                                    break;
                                case 'default_billing':
                                case 'default_shipping':
                                    $address = $customer->getPrimaryAddress($attributeCode);

                                    if ($address) {
                                        $street = $address->getStreet();
                                        $eventValue = $mergeVars[$key] = array(
                                            "addr1" => $street[0] ? $street[0] : "",
                                            "addr2" => count($street) > 1 ? $street[1] : "",
                                            "city" => $address->getCity() ? $address->getCity() : "",
                                            "state" => $address->getRegion() ? $address->getRegion() : "",
                                            "zip" => $address->getPostcode() ? $address->getPostcode() : "",
                                            "country" => $address->getCountry() ? Mage::getModel('directory/country')->loadByCode($address->getCountry())->getName() : ""
                                        );
                                    }
                                    break;
                                case 'gender':
                                    if ($customer->getData($attributeCode)) {
                                        $genderValue = $customer->getData($attributeCode);
                                        if ($genderValue == 1) {
                                            $eventValue = $mergeVars[$key] = 'Male';
                                        } elseif ($genderValue == 2) {
                                            $eventValue = $mergeVars[$key] = 'Female';
                                        }
                                    }
                                    break;
                                case 'group_id':
                                    if ($customer->getData($attributeCode)) {
                                        $group_id = (int)$customer->getData($attributeCode);
                                        $customerGroup = Mage::helper('customer')->getGroups()->toOptionHash();
                                        $eventValue = $mergeVars[$key] = $customerGroup[$group_id];
                                    } else {
                                        $eventValue = $mergeVars[$key] = Mage::helper('mailchimp')->__('NOT LOGGED IN');
                                    }
                                    break;
                                case 'firstname':
                                    $firstName = $customer->getFirstname();

                                    if (!$firstName) {
                                        $firstName = $subscriber->getSubscriberFirstname();
                                    }

                                    if ($firstName) {
                                        $eventValue = $mergeVars[$key] = $firstName;
                                    }
                                    break;
                                case 'lastname':
                                    $lastName = $customer->getLastname();

                                    if (!$lastName) {
                                        $lastName = $subscriber->getSubscriberLastname();
                                    }

                                    if ($lastName) {
                                        $eventValue = $mergeVars[$key] = $lastName;
                                    }
                                    break;
                                case 'store_id':
                                    $eventValue = $mergeVars[$key] = $storeId;
                                    break;
                                case 'website_id':
                                    $websiteId = Mage::getModel('core/store')->load($storeId)->getWebsiteId();
                                    $eventValue = $mergeVars[$key] = $websiteId;
                                    break;
                                case 'created_in':
                                    if ($customer->getData($attributeCode)) {
                                        $eventValue = $mergeVars[$key] = $customer->getData($attributeCode);
                                    } else {
                                        $storeCode = Mage::getModel('core/store')->load($storeId)->getCode();
                                        $eventValue = $mergeVars[$key] = $storeCode;
                                    }
                                    break;
                                case 'dob':
                                    if ($customer->getData($attributeCode)) {
                                        $eventValue = $mergeVars[$key] = date("m/d", strtotime($customer->getData($attributeCode)));
                                    }
                                    break;
                                default:
                                    if ($customer->getData($attributeCode)) {
                                        $eventValue = $mergeVars[$key] = $customer->getData($attributeCode);
                                    }
                                    break;
                            }

                            Mage::dispatchEvent(
                                'mailchimp_merge_field_send_before', array(
                                'subscriber_email' => $subscriberEmail,
                                'merge_field_tag' => $attributeCode,
                                'merge_field_value' => &$eventValue
                                )
                            );
                        }
                    }
                } else {
                    switch ($customAtt) {
                        case 'billing_company':
                        case 'shipping_company':
                            $addr = explode('_', $customAtt);
                            $address = $customer->getPrimaryAddress('default_' . ucfirst($addr[0]));

                            if ($address) {
                                $company = $address->getCompany();
                                if ($company) {
                                    $eventValue = $mergeVars[$key] = $company;
                                }
                            }
                            break;
                        case 'billing_telephone':
                        case 'shipping_telephone':
                            $addr = explode('_', $customAtt);
                            $address = $customer->getPrimaryAddress('default_' . ucfirst($addr[0]));

                            if ($address) {
                                $telephone = $address->getTelephone();
                                if ($telephone) {
                                    $eventValue = $mergeVars[$key] = $telephone;
                                }
                            }
                            break;
                        case 'billing_country':
                        case 'shipping_country':
                            $addr = explode('_', $customAtt);
                            $address = $customer->getPrimaryAddress('default_' . ucfirst($addr[0]));

                            if ($address) {
                                $countryCode = $address->getCountry();
                                if ($countryCode) {
                                    $countryName = Mage::getModel('directory/country')->loadByCode($countryCode)->getName();
                                    $eventValue = $mergeVars[$key] = $countryName;
                                }
                            }
                            break;
                        case 'billing_zipcode':
                        case 'shipping_zipcode':
                            $addr = explode('_', $customAtt);
                            $address = $customer->getPrimaryAddress('default_' . ucfirst($addr[0]));

                            if ($address) {
                                $zipCode = $address->getPostcode();
                                if ($zipCode) {
                                    $eventValue = $mergeVars[$key] = $zipCode;
                                }
                            }
                            break;
                        case 'dop':
                            $dop = Mage::helper('mailchimp')->getLastDateOfPurchase($subscriberEmail);
                            if ($dop) {
                                $eventValue = $mergeVars[$key] = $dop;
                            }
                            break;
                    }

                    Mage::dispatchEvent(
                        'mailchimp_merge_field_send_before', array(
                        'subscriber_email' => $subscriberEmail,
                        'merge_field_tag' => $customAtt,
                        'merge_field_value' => &$eventValue
                        )
                    );
                }

                if ($eventValue) {
                    $mergeVars[$key] = $eventValue;
                }
            }
        }

        return (!empty($mergeVars)) ? $mergeVars : null;
    }

    /**
     * @param $subscriber
     * @param bool $updateStatus If set to true, it will force the status update even for those already subscribed.
     */
    public function updateSubscriber($subscriber, $updateStatus = false)
    {
        $storeId = $subscriber->getStoreId();
        $listId = Mage::helper('mailchimp')->getGeneralList($storeId);
        $newStatus = $this->_getMCStatus($subscriber->getStatus(), $storeId);
        $forceStatus = ($updateStatus) ? $newStatus : null;
        $api = Mage::helper('mailchimp')->getApi($storeId);
        $mergeVars = $this->getMergeVars($subscriber);
        $md5HashEmail = md5(strtolower($subscriber->getSubscriberEmail()));
        try {
            $api->lists->members->addOrUpdate(
                $listId, $md5HashEmail, $subscriber->getSubscriberEmail(), $newStatus, null, $forceStatus, $mergeVars,
                null, null, null, null
            );
            $subscriber->setData("mailchimp_sync_delta", Varien_Date::now());
            $subscriber->setData("mailchimp_sync_error", "");
            $subscriber->setData("mailchimp_sync_modified", 0);
        } catch(Mailchimp_Error $e) {
            if ($newStatus === 'subscribed' && strstr($e->getMailchimpDetails(), 'is in a compliance state')) {
                try {
                    $api->lists->members->update($listId, $md5HashEmail, null, 'pending', $mergeVars);
                    $subscriber->setSubscriberStatus(Mage_Newsletter_Model_Subscriber::STATUS_UNCONFIRMED);
                    $message = Mage::helper('mailchimp')->__('To begin receiving the newsletter, you must first confirm your subscription');
                    Mage::getSingleton('core/session')->addWarning($message);
                } catch(Mailchimp_Error $e) {
                    Mage::helper('mailchimp')->logError($e->getFriendlyMessage(), $storeId);
                    Mage::getSingleton('core/session')->addError($e->getFriendlyMessage());
                    $subscriber->unsubscribe();
                } catch (Exception $e) {
                    Mage::helper('mailchimp')->logError($e->getMessage(), $storeId);
                }
            } else {
                $subscriber->unsubscribe();
                Mage::helper('mailchimp')->logError($e->getFriendlyMessage(), $storeId);
                Mage::getSingleton('core/session')->addError($e->getFriendlyMessage());
            }
        } catch (Exception $e) {
            Mage::helper('mailchimp')->logError($e->getMessage(), $storeId);
        }
    }

    /**
     * Get status to send confirmation if Need to Confirm enabled on Magento
     *
     * @param $status
     * @param $storeId
     * @return string
     */
    protected function _getMCStatus($status, $storeId)
    {
        $confirmationFlagPath = Mage_Newsletter_Model_Subscriber::XML_PATH_CONFIRMATION_FLAG;
        if ($status == Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED) {
            $status = 'unsubscribed';
        } elseif (Mage::helper('mailchimp')->getConfigValueForScope($confirmationFlagPath, $storeId) &&
            ($status == Mage_Newsletter_Model_Subscriber::STATUS_NOT_ACTIVE ||
                $status == Mage_Newsletter_Model_Subscriber::STATUS_UNCONFIRMED)
        ) {
            $status = 'pending';
        } elseif ($status == Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED) {
            $status = 'subscribed';
        }

        return $status;
    }

    public function removeSubscriber($subscriber)
    {
        $storeId = $subscriber->getStoreId();
        $listId = Mage::helper('mailchimp')->getGeneralList($storeId);
        $api = Mage::helper('mailchimp')->getApi($storeId);
        try {
            $md5HashEmail = md5(strtolower($subscriber->getSubscriberEmail()));
            $api->lists->members->update($listId, $md5HashEmail, null, 'unsubscribed');
        } catch(Mailchimp_Error $e) {
            Mage::helper('mailchimp')->logError($e->getFriendlyMessage(), $storeId);
            Mage::getSingleton('adminhtml/session')->addError($e->getFriendlyMessage());
        } catch (Exception $e) {
            Mage::helper('mailchimp')->logError($e->getMessage(), $storeId);
        }
    }

    /**
     * @param $subscriber
     */
    public function deleteSubscriber($subscriber)
    {
        $storeId = $subscriber->getStoreId();
        $listId = Mage::helper('mailchimp')->getGeneralList($storeId);
        $api = Mage::helper('mailchimp')->getApi($storeId);
        try {
            $md5HashEmail = md5(strtolower($subscriber->getSubscriberEmail()));
            $api->lists->members->update($listId, $md5HashEmail, null, 'cleaned');
        } catch(Mailchimp_Error $e) {
            Mage::helper('mailchimp')->logError($e->getFriendlyMessage(), $storeId);
            Mage::getSingleton('adminhtml/session')->addError($e->getFriendlyMessage());
        } catch (Exception $e) {
            Mage::helper('mailchimp')->logError($e->getMessage(), $storeId);
        }
    }

    public function update($emailAddress, $storeId)
    {
        $subscriber = Mage::getSingleton('newsletter/subscriber')->loadByEmail($emailAddress);
        if ($subscriber->getStatus() == Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED && $subscriber->getMailchimpSyncDelta() > Mage::helper('mailchimp')->getMCMinSyncDateFlag($storeId)) {
            $subscriber->setMailchimpSyncModified(1)
                ->save();
        }
    }
}