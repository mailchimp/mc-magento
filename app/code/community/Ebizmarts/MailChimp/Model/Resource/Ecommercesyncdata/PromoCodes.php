<?php

//resource


/**
 * mc-magento Magento Component
 *
 * @category  Ebizmarts
 * @package   mc-magento
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     2019-11-04 17:41
 */
class Ebizmarts_MailChimp_Model_Resource_Ecommercesyncdata_Promocodes extends
    Ebizmarts_MailChimp_Model_Resource_Ecommercesyncdata
{
    public function _construct()
    {
        parent::_construct();
        $this->setType(Ebizmarts_MailChimp_Model_Config::IS_PROMO_CODE);
    }
}

