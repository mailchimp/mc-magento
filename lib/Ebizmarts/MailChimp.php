<?php
/**
 * mailchimp-lib Magento Component
 *
 * @category Ebizmarts
 * @package mailchimp-lib
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 4/27/16 4:36 PM
 * @file: Mailchimp.php
 */
if (defined("COMPILER_INCLUDE_PATH")) {
    require_once dirname(__FILE__) . '/Ebizmarts/MailChimp/Abstract.php';
    require_once dirname(__FILE__) . '/Ebizmarts/MailChimp/Root.php';
    require_once dirname(__FILE__) . '/Ebizmarts/MailChimp/Automation.php';
    require_once dirname(__FILE__) . '/Ebizmarts/MailChimp/AutomationEmails.php';
    require_once dirname(__FILE__) . '/Ebizmarts/MailChimp/AutomationEmailsQueue.php';
    require_once dirname(__FILE__) . '/Ebizmarts/MailChimp/Exceptions.php';
    require_once dirname(__FILE__) . '/Ebizmarts/MailChimp/AuthorizedApps.php';
    require_once dirname(__FILE__) . '/Ebizmarts/MailChimp/Automation.php';
    require_once dirname(__FILE__) . '/Ebizmarts/MailChimp/BatchOperations.php';
    require_once dirname(__FILE__) . '/Ebizmarts/MailChimp/CampaignFolders.php';
    require_once dirname(__FILE__) . '/Ebizmarts/MailChimp/Campaigns.php';
    require_once dirname(__FILE__) . '/Ebizmarts/MailChimp/CampaignsContent.php';
    require_once dirname(__FILE__) . '/Ebizmarts/MailChimp/CampaignsFeedback.php';
    require_once dirname(__FILE__) . '/Ebizmarts/MailChimp/CampaignsSendChecklist.php';
    require_once dirname(__FILE__) . '/Ebizmarts/MailChimp/Conversations.php';
    require_once dirname(__FILE__) . '/Ebizmarts/MailChimp/ConversationsMessages.php';
    require_once dirname(__FILE__) . '/Ebizmarts/MailChimp/Ecommerce.php';
    require_once dirname(__FILE__) . '/Ebizmarts/MailChimp/EcommerceStores.php';
    require_once dirname(__FILE__) . '/Ebizmarts/MailChimp/EcommerceCarts.php';
    require_once dirname(__FILE__) . '/Ebizmarts/MailChimp/EcommerceCustomers.php';
    require_once dirname(__FILE__) . '/Ebizmarts/MailChimp/EcommerceOrders.php';
    require_once dirname(__FILE__) . '/Ebizmarts/MailChimp/EcommerceOrdersLines.php';
    require_once dirname(__FILE__) . '/Ebizmarts/MailChimp/EcommerceProducts.php';
    require_once dirname(__FILE__) . '/Ebizmarts/MailChimp/EcommerceProductsVariants.php';
    require_once dirname(__FILE__) . '/Ebizmarts/MailChimp/FileManagerFiles.php';
    require_once dirname(__FILE__) . '/Ebizmarts/MailChimp/FileManagerFolders.php';
    require_once dirname(__FILE__) . '/Ebizmarts/MailChimp/Lists.php';
    require_once dirname(__FILE__) . '/Ebizmarts/MailChimp/ListsAbuseReports.php';
    require_once dirname(__FILE__) . '/Ebizmarts/MailChimp/ListsActivity.php';
    require_once dirname(__FILE__) . '/Ebizmarts/MailChimp/ListsClients.php';
    require_once dirname(__FILE__) . '/Ebizmarts/MailChimp/ListsGrowthHistory.php';
    require_once dirname(__FILE__) . '/Ebizmarts/MailChimp/ListsInterestCategory.php';
    require_once dirname(__FILE__) . '/Ebizmarts/MailChimp/ListsInterestCategoryInterests.php';
    require_once dirname(__FILE__) . '/Ebizmarts/MailChimp/ListsMembers.php';
    require_once dirname(__FILE__) . '/Ebizmarts/MailChimp/ListsMembersActivity.php';
    require_once dirname(__FILE__) . '/Ebizmarts/MailChimp/ListsMembersGoals.php';
    require_once dirname(__FILE__) . '/Ebizmarts/MailChimp/ListsMembersNotes.php';
    require_once dirname(__FILE__) . '/Ebizmarts/MailChimp/ListsMergeFields.php';
    require_once dirname(__FILE__) . '/Ebizmarts/MailChimp/ListsSegments.php';
    require_once dirname(__FILE__) . '/Ebizmarts/MailChimp/ListsSegmentsMembers.php';
    require_once dirname(__FILE__) . '/Ebizmarts/MailChimp/ListsWebhooks.php';
    require_once dirname(__FILE__) . '/Ebizmarts/MailChimp/Reports.php';
    require_once dirname(__FILE__) . '/Ebizmarts/MailChimp/ReportsCampaignAdvice.php';
    require_once dirname(__FILE__) . '/Ebizmarts/MailChimp/ReportsClickReports.php';
    require_once dirname(__FILE__) . '/Ebizmarts/MailChimp/ReportsClickReportsMembers.php';
    require_once dirname(__FILE__) . '/Ebizmarts/MailChimp/ReportsDomainPerformance.php';
    require_once dirname(__FILE__) . '/Ebizmarts/MailChimp/ReportsEapURLReport.php';
    require_once dirname(__FILE__) . '/Ebizmarts/MailChimp/ReportsEmailActivity.php';
    require_once dirname(__FILE__) . '/Ebizmarts/MailChimp/ReportsLocation.php';
    require_once dirname(__FILE__) . '/Ebizmarts/MailChimp/ReportsSentTo.php';
    require_once dirname(__FILE__) . '/Ebizmarts/MailChimp/ReportsSubReports.php';
    require_once dirname(__FILE__) . '/Ebizmarts/MailChimp/ReportsUnsubscribes.php';
    require_once dirname(__FILE__) . '/Ebizmarts/MailChimp/TemplateFolders.php';
    require_once dirname(__FILE__) . '/Ebizmarts/MailChimp/Templates.php';
    require_once dirname(__FILE__) . '/Ebizmarts/MailChimp/TemplatesDefaultContent.php';
} else {
    require_once dirname(__FILE__) . '/MailChimp/Abstract.php';
    require_once dirname(__FILE__) . '/MailChimp/Root.php';
    require_once dirname(__FILE__) . '/MailChimp/Automation.php';
    require_once dirname(__FILE__) . '/MailChimp/AutomationEmails.php';
    require_once dirname(__FILE__) . '/MailChimp/AutomationEmailsQueue.php';
    require_once dirname(__FILE__) . '/MailChimp/Exceptions.php';
    require_once dirname(__FILE__) . '/MailChimp/AuthorizedApps.php';
    require_once dirname(__FILE__) . '/MailChimp/Automation.php';
    require_once dirname(__FILE__) . '/MailChimp/BatchOperations.php';
    require_once dirname(__FILE__) . '/MailChimp/CampaignFolders.php';
    require_once dirname(__FILE__) . '/MailChimp/Campaigns.php';
    require_once dirname(__FILE__) . '/MailChimp/CampaignsContent.php';
    require_once dirname(__FILE__) . '/MailChimp/CampaignsFeedback.php';
    require_once dirname(__FILE__) . '/MailChimp/CampaignsSendChecklist.php';
    require_once dirname(__FILE__) . '/MailChimp/Conversations.php';
    require_once dirname(__FILE__) . '/MailChimp/ConversationsMessages.php';
    require_once dirname(__FILE__) . '/MailChimp/Ecommerce.php';
    require_once dirname(__FILE__) . '/MailChimp/EcommerceStores.php';
    require_once dirname(__FILE__) . '/MailChimp/EcommerceCarts.php';
    require_once dirname(__FILE__) . '/MailChimp/EcommerceCustomers.php';
    require_once dirname(__FILE__) . '/MailChimp/EcommerceOrders.php';
    require_once dirname(__FILE__) . '/MailChimp/EcommerceOrdersLines.php';
    require_once dirname(__FILE__) . '/MailChimp/EcommerceProducts.php';
    require_once dirname(__FILE__) . '/MailChimp/EcommerceProductsVariants.php';
    require_once dirname(__FILE__) . '/MailChimp/FileManagerFiles.php';
    require_once dirname(__FILE__) . '/MailChimp/FileManagerFolders.php';
    require_once dirname(__FILE__) . '/MailChimp/Lists.php';
    require_once dirname(__FILE__) . '/MailChimp/ListsAbuseReports.php';
    require_once dirname(__FILE__) . '/MailChimp/ListsActivity.php';
    require_once dirname(__FILE__) . '/MailChimp/ListsClients.php';
    require_once dirname(__FILE__) . '/MailChimp/ListsGrowthHistory.php';
    require_once dirname(__FILE__) . '/MailChimp/ListsInterestCategory.php';
    require_once dirname(__FILE__) . '/MailChimp/ListsInterestCategoryInterests.php';
    require_once dirname(__FILE__) . '/MailChimp/ListsMembers.php';
    require_once dirname(__FILE__) . '/MailChimp/ListsMembersActivity.php';
    require_once dirname(__FILE__) . '/MailChimp/ListsMembersGoals.php';
    require_once dirname(__FILE__) . '/MailChimp/ListsMembersNotes.php';
    require_once dirname(__FILE__) . '/MailChimp/ListsMergeFields.php';
    require_once dirname(__FILE__) . '/MailChimp/ListsSegments.php';
    require_once dirname(__FILE__) . '/MailChimp/ListsSegmentsMembers.php';
    require_once dirname(__FILE__) . '/MailChimp/ListsWebhooks.php';
    require_once dirname(__FILE__) . '/MailChimp/Reports.php';
    require_once dirname(__FILE__) . '/MailChimp/ReportsCampaignAdvice.php';
    require_once dirname(__FILE__) . '/MailChimp/ReportsClickReports.php';
    require_once dirname(__FILE__) . '/MailChimp/ReportsClickReportsMembers.php';
    require_once dirname(__FILE__) . '/MailChimp/ReportsDomainPerformance.php';
    require_once dirname(__FILE__) . '/MailChimp/ReportsEapURLReport.php';
    require_once dirname(__FILE__) . '/MailChimp/ReportsEmailActivity.php';
    require_once dirname(__FILE__) . '/MailChimp/ReportsLocation.php';
    require_once dirname(__FILE__) . '/MailChimp/ReportsSentTo.php';
    require_once dirname(__FILE__) . '/MailChimp/ReportsSubReports.php';
    require_once dirname(__FILE__) . '/MailChimp/ReportsUnsubscribes.php';
    require_once dirname(__FILE__) . '/MailChimp/TemplateFolders.php';
    require_once dirname(__FILE__) . '/MailChimp/Templates.php';
    require_once dirname(__FILE__) . '/MailChimp/TemplatesDefaultContent.php';
}

class Ebizmarts_MailChimp
{
    protected $_apiKey;
    protected $_ch;
    protected $_root    = 'https://api.mailchimp.com/3.0';
    protected $_debug   = false;

    const POST      = 'POST';
    const GET       = 'GET';
    const PATCH     = 'PATCH';
    const DELETE    = 'DELETE';
    const PUT       = 'PUT';

    public function __construct($apiKey=null,$opts=array(),$userAgent=null)
    {
        if (!$apiKey) {
            throw new MailChimp_Error('You must provide a MailChimp API key');
        }

        $this->_apiKey   = $apiKey;
        $dc             = 'us1';
        if (strstr($this->_apiKey, "-")) {
            list($key, $dc) = explode("-", $this->_apiKey, 2);
            if (!$dc) {
                $dc = "us1";
            }
        }

        $this->_root = str_replace('https://api', 'https://' . $dc . '.api', $this->_root);
        $this->_root = rtrim($this->_root, '/') . '/';

        if (!isset($opts['timeout']) || !is_int($opts['timeout'])) {
            $opts['timeout'] = 600;
        }

        if (isset($opts['debug'])) {
            $this->_debug = true;
        }


        $this->_ch = curl_init();

        if (isset($opts['CURLOPT_FOLLOWLOCATION']) && $opts['CURLOPT_FOLLOWLOCATION'] === true) {
            curl_setopt($this->_ch, CURLOPT_FOLLOWLOCATION, true);
        }

        if ($userAgent) {
            curl_setopt($this->_ch, CURLOPT_USERAGENT, $userAgent);
        } else {
            curl_setopt($this->_ch, CURLOPT_USERAGENT, 'Ebizmart-MailChimp-PHP/3.0.0');
        }

        curl_setopt($this->_ch, CURLOPT_HEADER, false);
        curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->_ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($this->_ch, CURLOPT_TIMEOUT, $opts['timeout']);
        curl_setopt($this->_ch, CURLOPT_USERPWD, "noname:".$this->_apiKey);

        $this->root                                         = new MailChimp_Root($this);
        $this->authorizedApps                               = new MailChimp_AuthorizedApps($this);
        $this->automation                                   = new MailChimp_Automation($this);
        $this->automation->emails                           = new MailChimp_AutomationEmails($this);
        $this->automation->emails->queue                    = new MailChimp_AutomationEmailsQuque($this);
        $this->batchOperation                               = new MailChimp_BatchOperations($this);
        $this->campaignFolders                              = new MailChimp_CampaignFolders($this);
        $this->campaigns                                    = new MailChimp_Campaigns($this);
        $this->campaigns->content                           = new MailChimp_CampaignsContent($this);
        $this->campaigns->feedback                          = new MailChimp_CampaignsFeedback($this);
        $this->campaigns->sendChecklist                     = new MailChimp_CampaignsSendChecklist($this);
        $this->conversations                                = new MailChimp_Conversations($this);
        $this->conversations->messages                      = new MailChimp_ConversationsMessages($this);
        $this->ecommerce                                    = new MailChimp_Ecommerce($this);
        $this->ecommerce->stores                            = new MailChimp_EcommerceStore($this);
        $this->ecommerce->carts                             = new MailChimp_EcommerceCarts($this);
        $this->ecommerce->customers                         = new MailChimp_EcommerceCustomers($this);
        $this->ecommerce->orders                            = new MailChimp_EcommerceOrders($this);
        $this->ecommerce->orders->lines                     = new MailChimp_EcommerceOrdersLines($this);
        $this->ecommerce->products                          = new MailChimp_EcommerceProducts($this);
        $this->ecommerce->products->variants                = new MailChimp_EcommerceProductsVariants($this);
        $this->fileManagerFiles                             = new MailChimp_FileManagerFiles($this);
        $this->fileManagerFolders                           = new MailChimp_FileManagerFolders($this);
        $this->lists                                        = new MailChimp_Lists($this);
        $this->lists->abuseReports                          = new MailChimp_ListsAbuseReports($this);
        $this->lists->activity                              = new MailChimp_ListsActivity($this);
        $this->lists->clients                               = new MailChimp_ListsClients($this);
        $this->lists->growthHistory                         = new MailChimp_ListsGrowthHistory($this);
        $this->lists->interestCategory                      = new MailChimp_ListsInterestCategory($this);
        $this->lists->interestCategory->interests           = new MailChimp_ListInterestCategoryInterests($this);
        $this->lists->members                               = new MailChimp_ListsMembers($this);
        $this->lists->members->memberActivity               = new MailChimp_ListsMembersActivity($this);
        $this->lists->members->memberGoal                   = new MailChimp_ListsMembersGoals($this);
        $this->lists->members->memberNotes                  = new MailChimp_ListsMembersNotes($this);;
        $this->lists->mergeFields                           = new MailChimp_ListsMergeFields($this);
        $this->lists->segments                              = new MailChimp_ListsSegments($this);
        $this->lists->segments->segmentMembers              = new MailChimp_ListsSegmentsMembers($this);
        $this->lists->webhooks                              = new MailChimp_ListsWebhooks($this);
        $this->reports                                      = new MailChimp_Reports($this);
        $this->reports->campaignAdvice                      = new MailChimp_ReportsCampaignAdvice($this);
        $this->reports->clickReports                        = new MailChimp_ReportsClickReports($this);
        $this->reports->clickReports->clickReportMembers    = new MailChimp_ReportsClickReportsMembers($this);
        $this->reports->domainPerformance                   = new MailChimp_ReportsDomainPerformance($this);
        $this->reports->eapURLReport                        = new MailChimp_ReportsEapURLReport($this);
        $this->reports->emailActivity                       = new MailChimp_ReportsEmailActivity($this);
        $this->reports->location                            = new MailChimp_ReportsLocation($this);
        $this->reports->sentTo                              = new MailChimp_ReportsSentTo($this);
        $this->reports->subReports                          = new MailChimp_ReportsSubReports($this);
        $this->reports->unsubscribes                        = new MailChimp_ReportsUnsubscribes($this);
        $this->templateFolders                              = new MailChimp_TemplateFolders($this);
        $this->templates                                    = new MailChimp_Templates($this);
        $this->templates->defaultContent                    = new MailChimp_TemplatesDefaultContent($this);
    }
    public function call($url,$params,$method=Ebizmarts_MailChimp::GET,$encodeJson=true)
    {
        if (count($params) && $encodeJson && $method!=Ebizmarts_MailChimp::GET) {
            $params = json_encode($params);
        }

        $ch = $this->_ch;
        if (count($params)&&$method!=Ebizmarts_MailChimp::GET) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        } else {
            if (count($params)) {
                $_params = http_build_query($params);
                $url .= '?' . $_params;
            }
        }

        curl_setopt($ch, CURLOPT_URL, $this->_root . $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_VERBOSE, $this->_debug);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);


        $responseBody = curl_exec($ch);

        $info = curl_getinfo($ch);

        $result = json_decode($responseBody, true);


        if (curl_error($ch)) {
            throw new MailChimp_Error("API call to $url failed: " . curl_error($ch));
        }

        if (floor($info['http_code'] / 100) >= 4) {
            $errors = (isset($result['errors'])) ? $result['errors'] : '';
            throw new MailChimp_Error($url, $result['title'], $result['detail'], $errors);
        }

        return $result;
    }
}