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

class Ebizmarts_MailChimp_Model_Api_Customers
{

    const BATCH_LIMIT = 100;
    const DEFAULT_OPT_IN = true;

    public function createBatchJson($mailchimpStoreId)
    {
        //get customers
        $collection = Mage::getModel('customer/customer')->getCollection()
            ->addAttributeToSelect('mailchimp_sync_delta')
            ->addAttributeToSelect('mailchimp_sync_modified')
            ->addAttributeToSelect('firstname')
            ->addAttributeToSelect('lastname')
            ->addAttributeToFilter(array(
                array('attribute' => 'mailchimp_sync_delta', 'null' => true),
                array('attribute' => 'mailchimp_sync_delta', 'eq' => ''),
                array('attribute' => 'mailchimp_sync_delta', 'lt' => Mage::helper('mailchimp')->getMCMinSyncDateFlag()),
                array('attribute' => 'mailchimp_sync_modified', 'eq'=> 1)
            ), '', 'left');
        $collection->getSelect()->limit(self::BATCH_LIMIT);

        $customerArray = array();
        $batchId = Ebizmarts_MailChimp_Model_Config::IS_CUSTOMER . '_' . date('Y-m-d-H-i-s');

        $counter = 0;
        foreach ($collection as $customer) {
            $data = $this->_buildCustomerData($customer);
            $customerJson = "";

            //enconde to JSON
            try {
                $customerJson = json_encode($data);

            } catch (Exception $e) {
                //json encode failed
                Mage::helper('mailchimp')->logError("Customer ".$customer->getId()." json encode failed");
            }

            if (!empty($customerJson)) {
                if($customer->getMailchimpSyncModified())
                {
                    $customerArray[$counter]['method'] = "PATCH";
                    $customerArray[$counter]['path'] = "/ecommerce/stores/" . $mailchimpStoreId . "/customers/".$customer->getId();
                }
                else {
                    $customerArray[$counter]['method'] = "POST";
                    $customerArray[$counter]['path'] = "/ecommerce/stores/" . $mailchimpStoreId . "/customers";
                }
                $customerArray[$counter]['operation_id'] = $batchId . '_' . $customer->getId();
                $customerArray[$counter]['body'] = $customerJson;

                //update customers delta
                $customer->setData("mailchimp_sync_delta", Varien_Date::now());
                $customer->setData("mailchimp_sync_error", "");
                $customer->setData("mailchimp_sync_modified", 0);
                $customer->save();
            }
            $counter += 1;
        }
        return $customerArray;
    }

    protected function _buildCustomerData($customer)
    {
        $data = array();
        $data["id"] = $customer->getId();
        $data["email_address"] = $customer->getEmail();
        $data["first_name"] = $customer->getFirstname();
        $data["last_name"] = $customer->getLastname();
        $data["opt_in_status"] = self::DEFAULT_OPT_IN;

        //customer orders data
        $orderCollection = Mage::getModel('sales/order')->getCollection()
            ->addFieldToFilter('status', 'complete')
            ->addAttributeToFilter('customer_id', array('eq' => $customer->getId()));
        $totalOrders = 0;
        $totalAmountSpent = 0;
        foreach ($orderCollection as $order) {
            $totalOrders += 1;
            $totalAmountSpent += (int)$order->getGrandTotal();
        }
        $data["orders_count"] = $totalOrders;
        $data["total_spent"] = $totalAmountSpent;

        //addresses data
        foreach ($customer->getAddresses() as $address) {
            if (!array_key_exists("address", $data)) //send only first address
            {
                $street = $address->getStreet();
                $data["address"] = array(
                    "address1" => $street[0],
                    "address2" => count($street)>1 ? $street[1] : "",
                    "city" => $address->getCity(),
                    "province" => $address->getRegion() ? $address->getRegion() : "",
                    "province_code" => $address->getRegionCode() ? $address->getRegionCode() : "",
                    "postal_code" => $address->getPostcode(),
                    "country" => Mage::getModel('directory/country')->loadByCode($address->getCountry())->getName(),
                    "country_code" => $address->getCountry()
                );

                //company
                if ($address->getCompany()) {
                    $data["company"] = $address->getCompany();
                }
                break;
            }
        }

        return $data;
    }
    public function update($customer)
    {
        if (Mage::helper('mailchimp')->isEcomSyncDataEnabled()) {
//        $customer->setData("mailchimp_sync_delta", Varien_Date::now());
            $customer->setData("mailchimp_sync_error", "");
            $customer->setData("mailchimp_sync_modified", 1);
        }
    }
//    public function updateOld($customer)
//    {
//        try {
//
//            if (Mage::helper('mailchimp')->isEcomSyncDataEnabled()) {
//
//                $apiKey = Mage::helper('mailchimp')->getConfigValue(Ebizmarts_MailChimp_Model_Config::GENERAL_APIKEY);
//
//                $mailchimpStoreId = Mage::helper('mailchimp')->getMCStoreId();
//
//                $data = $this->_buildCustomerData($customer);
//
//                $mailchimpApi = new Ebizmarts_Mailchimp($apiKey);
//                $mailchimpApi->ecommerce->customers->addOrModify(
//                    $mailchimpStoreId,
//                    $data["id"],
//                    $data["email_address"],
//                    $data["opt_in_status"],
//                    array_key_exists("company",$data) ? $data["company"] : null,
//                    $data["first_name"],
//                    $data["last_name"],
//                    $data["orders_count"],
//                    $data["total_spent"],
//                    $data["address"]
//                );
//
//                //update customers delta
//                $customer->setData("mailchimp_sync_delta", Varien_Date::now());
//                $customer->setData("mailchimp_sync_error", "");
//                $customer->save();
//
//            }
//        } catch (Mailchimp_Error $e)
//        {
//            Mage::helper('mailchimp')->logError($e->getFriendlyMessage());
//
//            //update customers delta
//            $customer->setData("mailchimp_sync_delta", Varien_Date::now());
//            $customer->setData("mailchimp_sync_error", $e->getFriendlyMessage());
//            $customer->save();
//
//        } catch (Exception $e)
//        {
//            Mage::helper('mailchimp')->logError($e->getMessage());
//
//            //update customers delta
//            $customer->setData("mailchimp_sync_delta", Varien_Date::now());
//            $customer->setData("mailchimp_sync_error", $e->getMessage());
//            $customer->save();
//        }
//    }
}