<?php
if (Mage::helper('core')->isModuleEnabled('Aschroder_SMTPPro')
    && class_exists('Aschroder_SMTPPro_Model_Email_Template')
) {
    class Ebizmarts_MailChimp_Model_Email_TemplateBase extends Aschroder_SMTPPro_Model_Email_Template
    {
    }
} elseif (Mage::helper('core')->isModuleEnabled('Aschroder_Email')
    && class_exists('Aschroder_Email_Model_Email_Template')
) {
    class Ebizmarts_MailChimp_Model_Email_TemplateBase extends Aschroder_Email_Model_Email_Template
    {
    }
} else {
    class Ebizmarts_MailChimp_Model_Email_TemplateBase extends Mage_Core_Model_Email_Template
    {
    }
}
