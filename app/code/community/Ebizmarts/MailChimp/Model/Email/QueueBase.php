<?php
if (Mage::helper('core')->isModuleEnabled('Aschroder_SMTPPro')
    && class_exists('Aschroder_SMTPPro_Model_Email_Queue')
) {
    class_alias('Aschroder_SMTPPro_Model_Email_Queue', 'Ebizmarts_MailChimp_Model_Email_QueueBase');
} elseif (Mage::helper('core')->isModuleEnabled('Aschroder_Email')
    && class_exists('Aschroder_Email_Model_Email_Queue')
) {
    class_alias('Aschroder_Email_Model_Email_Queue', 'Ebizmarts_MailChimp_Model_Email_QueueBase');
} else {
    class Ebizmarts_MailChimp_Model_Email_QueueBase extends Mage_Core_Model_Email_Queue
    {
    }
}
