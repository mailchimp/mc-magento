<?php
/**
 * mailchimp-lib Magento Component
 *
 * @category Ebizmarts
 * @package mailchimp-lib
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 4/29/16 3:55 PM
 * @file: Campaigns.php
 */
class Mailchimp_Campaigns extends Mailchimp_Abstract
{
    /**
     * @var Mailchimp_CampaignsContent
     */
    public $content;
    /**
     * @var Mailchimp_CampaignsFeedback
     */
    public $feedback;
    /**
     * @var Mailchimp_CampaignsSendChecklist
     */
    public $sendChecklist;
}