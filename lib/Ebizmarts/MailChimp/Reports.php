<?php
/**
 * mailchimp-lib Magento Component
 *
 * @category  Ebizmarts
 * @package   mailchimp-lib
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     5/2/16 4:48 PM
 * @file:     Reports.php
 */
class MailChimp_Reports extends MailChimp_Abstract
{
    /**
     * @var MailChimp_ReportsCampaignAdvice
     */
    public $campaignAdvice;
    /**
     * @var MailChimp_ReportsClickReports
     */
    public $clickReports;
    /**
     * @var MailChimp_ReportsDomainPerformance
     */
    public $domainPerformance;
    /**
     * @var ReportsEapURLReport
     */
    public $eapURLReport;
    /**
     * @var MailChimp_ReportsEmailActivity
     */
    public $emailActivity;
    /**
     * @var ReportsLocation
     */
    public $location;
    /**
     * @var MailChimp_ReportsSentTo
     */
    public $sentTo;
    /**
     * @var MailChimp_ReportsSubReports
     */
    public $subReports;
    /**
     * @var MailChimp_ReportsUnsubscribes
     */
    public $unsubscribes;
}