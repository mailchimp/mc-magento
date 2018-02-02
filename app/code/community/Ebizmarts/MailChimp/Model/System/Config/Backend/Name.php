<?php
/**
 * mc-magento Magento Component
 *
 * @category  Ebizmarts
 * @package   mc-magento
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     2/3/17 4:40 PM
 * @file:     Name.php
 */
class Ebizmarts_MailChimp_Model_System_Config_Backend_Name extends Mage_Core_Model_Config_Data
{

    public function save()
    {
        parent::save();
        $scopeId = $this->getScopeId();
        $scope = $this->getScope();
        $helper = $this->makeHelper();
        if ($this->isValueChanged()) {
            $name = $this->getValue();
            $realScope = $helper->getRealScopeForConfig(Ebizmarts_MailChimp_Model_Config::GENERAL_MCSTOREID, $scopeId, $scope);
            if ($realScope['scope_id'] == $scopeId && $realScope['scope'] == $scope) {
                $ecomEnabled = $helper->isEcomSyncDataEnabled($realScope['scope_id'], $realScope['scope']);
                if ($ecomEnabled) {
                    if ($name == '') {
                        $name = $helper->getMCStoreName($scopeId, $scope, true);
                    }
                    $helper->changeName($name, $scopeId, $scope);
                }
            }
        }
    }

    /**
     * @return Ebizmarts_MailChimp_Helper_Data
     */
    protected function makeHelper()
    {
        return Mage::helper('mailchimp');
    }
}
