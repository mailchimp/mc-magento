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
    require_once dirname(__FILE__) . '/Ebizmarts/Mailchimp/Abstract.php';
    require_once dirname(__FILE__) . '/Ebizmarts/Mailchimp/Root.php';
    require_once dirname(__FILE__) . '/Ebizmarts/Mailchimp/Automation.php';
    require_once dirname(__FILE__) . '/Ebizmarts/Mailchimp/AutomationEmails.php';
    require_once dirname(__FILE__) . '/Ebizmarts/Mailchimp/AutomationEmailsQueue.php';
    require_once dirname(__FILE__) . '/Ebizmarts/Mailchimp/Exceptions.php';
    require_once dirname(__FILE__) . '/Ebizmarts/Mailchimp/AuthorizedApps.php';
    require_once dirname(__FILE__) . '/Ebizmarts/Mailchimp/Automation.php';
    require_once dirname(__FILE__) . '/Ebizmarts/Mailchimp/BatchOperations.php';
    require_once dirname(__FILE__) . '/Ebizmarts/Mailchimp/CampaignFolders.php';
    require_once dirname(__FILE__) . '/Ebizmarts/Mailchimp/Campaigns.php';
    require_once dirname(__FILE__) . '/Ebizmarts/Mailchimp/CampaignsContent.php';
    require_once dirname(__FILE__) . '/Ebizmarts/Mailchimp/CampaignsFeedback.php';
    require_once dirname(__FILE__) . '/Ebizmarts/Mailchimp/CampaignsSendChecklist.php';
    require_once dirname(__FILE__) . '/Ebizmarts/Mailchimp/Conversations.php';
    require_once dirname(__FILE__) . '/Ebizmarts/Mailchimp/ConversationsMessages.php';
    require_once dirname(__FILE__) . '/Ebizmarts/Mailchimp/Ecommerce.php';
    require_once dirname(__FILE__) . '/Ebizmarts/Mailchimp/EcommerceStores.php';
    require_once dirname(__FILE__) . '/Ebizmarts/Mailchimp/EcommerceCarts.php';
    require_once dirname(__FILE__) . '/Ebizmarts/Mailchimp/EcommerceCustomers.php';
    require_once dirname(__FILE__) . '/Ebizmarts/Mailchimp/EcommerceOrders.php';
    require_once dirname(__FILE__) . '/Ebizmarts/Mailchimp/EcommerceOrdersLines.php';
    require_once dirname(__FILE__) . '/Ebizmarts/Mailchimp/EcommerceProducts.php';
    require_once dirname(__FILE__) . '/Ebizmarts/Mailchimp/EcommerceProductsVariants.php';
    require_once dirname(__FILE__) . '/Ebizmarts/Mailchimp/FileManagerFiles.php';
    require_once dirname(__FILE__) . '/Ebizmarts/Mailchimp/FileManagerFolders.php';
    require_once dirname(__FILE__) . '/Ebizmarts/Mailchimp/Lists.php';
    require_once dirname(__FILE__) . '/Ebizmarts/Mailchimp/ListsAbuseReports.php';
    require_once dirname(__FILE__) . '/Ebizmarts/Mailchimp/ListsActivity.php';
    require_once dirname(__FILE__) . '/Ebizmarts/Mailchimp/ListsClients.php';
    require_once dirname(__FILE__) . '/Ebizmarts/Mailchimp/ListsGrowthHistory.php';
    require_once dirname(__FILE__) . '/Ebizmarts/Mailchimp/ListsInterestCategory.php';
    require_once dirname(__FILE__) . '/Ebizmarts/Mailchimp/ListsInterestCategoryInterests.php';
    require_once dirname(__FILE__) . '/Ebizmarts/Mailchimp/ListsMembers.php';
    require_once dirname(__FILE__) . '/Ebizmarts/Mailchimp/ListsMembersActivity.php';
    require_once dirname(__FILE__) . '/Ebizmarts/Mailchimp/ListsMembersGoals.php';
    require_once dirname(__FILE__) . '/Ebizmarts/Mailchimp/ListsMembersNotes.php';
    require_once dirname(__FILE__) . '/Ebizmarts/Mailchimp/ListsMergeFields.php';
    require_once dirname(__FILE__) . '/Ebizmarts/Mailchimp/ListsSegments.php';
    require_once dirname(__FILE__) . '/Ebizmarts/Mailchimp/ListsSegmentsMembers.php';
    require_once dirname(__FILE__) . '/Ebizmarts/Mailchimp/ListsWebhooks.php';
    require_once dirname(__FILE__) . '/Ebizmarts/Mailchimp/Reports.php';
    require_once dirname(__FILE__) . '/Ebizmarts/Mailchimp/ReportsCampaignAdvice.php';
    require_once dirname(__FILE__) . '/Ebizmarts/Mailchimp/ReportsClickReports.php';
    require_once dirname(__FILE__) . '/Ebizmarts/Mailchimp/ReportsClickReportsMembers.php';
    require_once dirname(__FILE__) . '/Ebizmarts/Mailchimp/ReportsDomainPerformance.php';
    require_once dirname(__FILE__) . '/Ebizmarts/Mailchimp/ReportsEapURLReport.php';
    require_once dirname(__FILE__) . '/Ebizmarts/Mailchimp/ReportsEmailActivity.php';
    require_once dirname(__FILE__) . '/Ebizmarts/Mailchimp/ReportsLocation.php';
    require_once dirname(__FILE__) . '/Ebizmarts/Mailchimp/ReportsSentTo.php';
    require_once dirname(__FILE__) . '/Ebizmarts/Mailchimp/ReportsSubReports.php';
    require_once dirname(__FILE__) . '/Ebizmarts/Mailchimp/ReportsUnsubscribes.php';
    require_once dirname(__FILE__) . '/Ebizmarts/Mailchimp/TemplateFolders.php';
    require_once dirname(__FILE__) . '/Ebizmarts/Mailchimp/Templates.php';
    require_once dirname(__FILE__) . '/Ebizmarts/Mailchimp/TemplatesDefaultContent.php';
} else {
    require_once dirname(__FILE__) . '/Mailchimp/Abstract.php';
    require_once dirname(__FILE__) . '/Mailchimp/Root.php';
    require_once dirname(__FILE__) . '/Mailchimp/Automation.php';
    require_once dirname(__FILE__) . '/Mailchimp/AutomationEmails.php';
    require_once dirname(__FILE__) . '/Mailchimp/AutomationEmailsQueue.php';
    require_once dirname(__FILE__) . '/Mailchimp/Exceptions.php';
    require_once dirname(__FILE__) . '/Mailchimp/AuthorizedApps.php';
    require_once dirname(__FILE__) . '/Mailchimp/Automation.php';
    require_once dirname(__FILE__) . '/Mailchimp/BatchOperations.php';
    require_once dirname(__FILE__) . '/Mailchimp/CampaignFolders.php';
    require_once dirname(__FILE__) . '/Mailchimp/Campaigns.php';
    require_once dirname(__FILE__) . '/Mailchimp/CampaignsContent.php';
    require_once dirname(__FILE__) . '/Mailchimp/CampaignsFeedback.php';
    require_once dirname(__FILE__) . '/Mailchimp/CampaignsSendChecklist.php';
    require_once dirname(__FILE__) . '/Mailchimp/Conversations.php';
    require_once dirname(__FILE__) . '/Mailchimp/ConversationsMessages.php';
    require_once dirname(__FILE__) . '/Mailchimp/Ecommerce.php';
    require_once dirname(__FILE__) . '/Mailchimp/EcommerceStores.php';
    require_once dirname(__FILE__) . '/Mailchimp/EcommerceCarts.php';
    require_once dirname(__FILE__) . '/Mailchimp/EcommerceCustomers.php';
    require_once dirname(__FILE__) . '/Mailchimp/EcommerceOrders.php';
    require_once dirname(__FILE__) . '/Mailchimp/EcommerceOrdersLines.php';
    require_once dirname(__FILE__) . '/Mailchimp/EcommerceProducts.php';
    require_once dirname(__FILE__) . '/Mailchimp/EcommerceProductsVariants.php';
    require_once dirname(__FILE__) . '/Mailchimp/FileManagerFiles.php';
    require_once dirname(__FILE__) . '/Mailchimp/FileManagerFolders.php';
    require_once dirname(__FILE__) . '/Mailchimp/Lists.php';
    require_once dirname(__FILE__) . '/Mailchimp/ListsAbuseReports.php';
    require_once dirname(__FILE__) . '/Mailchimp/ListsActivity.php';
    require_once dirname(__FILE__) . '/Mailchimp/ListsClients.php';
    require_once dirname(__FILE__) . '/Mailchimp/ListsGrowthHistory.php';
    require_once dirname(__FILE__) . '/Mailchimp/ListsInterestCategory.php';
    require_once dirname(__FILE__) . '/Mailchimp/ListsInterestCategoryInterests.php';
    require_once dirname(__FILE__) . '/Mailchimp/ListsMembers.php';
    require_once dirname(__FILE__) . '/Mailchimp/ListsMembersActivity.php';
    require_once dirname(__FILE__) . '/Mailchimp/ListsMembersGoals.php';
    require_once dirname(__FILE__) . '/Mailchimp/ListsMembersNotes.php';
    require_once dirname(__FILE__) . '/Mailchimp/ListsMergeFields.php';
    require_once dirname(__FILE__) . '/Mailchimp/ListsSegments.php';
    require_once dirname(__FILE__) . '/Mailchimp/ListsSegmentsMembers.php';
    require_once dirname(__FILE__) . '/Mailchimp/ListsWebhooks.php';
    require_once dirname(__FILE__) . '/Mailchimp/Reports.php';
    require_once dirname(__FILE__) . '/Mailchimp/ReportsCampaignAdvice.php';
    require_once dirname(__FILE__) . '/Mailchimp/ReportsClickReports.php';
    require_once dirname(__FILE__) . '/Mailchimp/ReportsClickReportsMembers.php';
    require_once dirname(__FILE__) . '/Mailchimp/ReportsDomainPerformance.php';
    require_once dirname(__FILE__) . '/Mailchimp/ReportsEapURLReport.php';
    require_once dirname(__FILE__) . '/Mailchimp/ReportsEmailActivity.php';
    require_once dirname(__FILE__) . '/Mailchimp/ReportsLocation.php';
    require_once dirname(__FILE__) . '/Mailchimp/ReportsSentTo.php';
    require_once dirname(__FILE__) . '/Mailchimp/ReportsSubReports.php';
    require_once dirname(__FILE__) . '/Mailchimp/ReportsUnsubscribes.php';
    require_once dirname(__FILE__) . '/Mailchimp/TemplateFolders.php';
    require_once dirname(__FILE__) . '/Mailchimp/Templates.php';
    require_once dirname(__FILE__) . '/Mailchimp/TemplatesDefaultContent.php';
}

class Ebizmarts_Mailchimp
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
            throw new Mailchimp_Error('You must provide a MailChimp API key');
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

        $this->root                                         = new Mailchimp_Root($this);
        $this->authorizedApps                               = new Mailchimp_AuthorizedApps($this);
        $this->automation                                   = new Mailchimp_Automation($this);
        $this->automation->emails                           = new Mailchimp_AutomationEmails($this);
        $this->automation->emails->queue                    = new Mailchimp_AutomationEmailsQuque($this);
        $this->batchOperation                               = new Mailchimp_BatchOperations($this);
        $this->campaignFolders                              = new Mailchimp_CampaignFolders($this);
        $this->campaigns                                    = new Mailchimp_Campaigns($this);
        $this->campaigns->content                           = new Mailchimp_CampaignsContent($this);
        $this->campaigns->feedback                          = new Mailchimp_CampaignsFeedback($this);
        $this->campaigns->sendChecklist                     = new Mailchimp_CampaignsSendChecklist($this);
        $this->conversations                                = new Mailchimp_Conversations($this);
        $this->conversations->messages                      = new Mailchimp_ConversationsMessages($this);
        $this->ecommerce                                    = new Mailchimp_Ecommerce($this);
        $this->ecommerce->stores                            = new Mailchimp_EcommerceStore($this);
        $this->ecommerce->carts                             = new Mailchimp_EcommerceCarts($this);
        $this->ecommerce->customers                         = new Mailchimp_EcommerceCustomers($this);
        $this->ecommerce->orders                            = new Mailchimp_EcommerceOrders($this);
        $this->ecommerce->orders->lines                     = new Mailchimp_EcommerceOrdersLines($this);
        $this->ecommerce->products                          = new Mailchimp_EcommerceProducts($this);
        $this->ecommerce->products->variants                = new Mailchimp_EcommerceProductsVariants($this);
        $this->fileManagerFiles                             = new Mailchimp_FileManagerFiles($this);
        $this->fileManagerFolders                           = new Mailchimp_FileManagerFolders($this);
        $this->lists                                        = new Mailchimp_Lists($this);
        $this->lists->abuseReports                          = new Mailchimp_ListsAbuseReports($this);
        $this->lists->activity                              = new Mailchimp_ListsActivity($this);
        $this->lists->clients                               = new Mailchimp_ListsClients($this);
        $this->lists->growthHistory                         = new Mailchimp_ListsGrowthHistory($this);
        $this->lists->interestCategory                      = new Mailchimp_ListsInterestCategory($this);
        $this->lists->interestCategory->interests           = new Mailchimp_ListInterestCategoryInterests($this);
        $this->lists->members                               = new Mailchimp_ListsMembers($this);
        $this->lists->members->memberActivity               = new Mailchimp_ListsMembersActivity($this);
        $this->lists->members->memberGoal                   = new Mailchimp_ListsMembersGoals($this);
        $this->lists->members->memberNotes                  = new Mailchimp_ListsMembersNotes($this);;
        $this->lists->mergeFields                           = new Mailchimp_ListsMergeFields($this);
        $this->lists->segments                              = new Mailchimp_ListsSegments($this);
        $this->lists->segments->segmentMembers              = new Mailchimp_ListsSegmentsMembers($this);
        $this->lists->webhooks                              = new Mailchimp_ListsWebhooks($this);
        $this->reports                                      = new Mailchimp_Reports($this);
        $this->reports->campaignAdvice                      = new Mailchimp_ReportsCampaignAdvice($this);
        $this->reports->clickReports                        = new Mailchimp_ReportsClickReports($this);
        $this->reports->clickReports->clickReportMembers    = new Mailchimp_ReportsClickReportsMembers($this);
        $this->reports->domainPerformance                   = new Mailchimp_ReportsDomainPerformance($this);
        $this->reports->eapURLReport                        = new Mailchimp_ReportsEapURLReport($this);
        $this->reports->emailActivity                       = new Mailchimp_ReportsEmailActivity($this);
        $this->reports->location                            = new Mailchimp_ReportsLocation($this);
        $this->reports->sentTo                              = new Mailchimp_ReportsSentTo($this);
        $this->reports->subReports                          = new Mailchimp_ReportsSubReports($this);
        $this->reports->unsubscribes                        = new Mailchimp_ReportsUnsubscribes($this);
        $this->templateFolders                              = new Mailchimp_TemplateFolders($this);
        $this->templates                                    = new Mailchimp_Templates($this);
        $this->templates->defaultContent                    = new Mailchimp_TemplatesDefaultContent($this);
    }
    public function call($url,$params,$method=Ebizmarts_Mailchimp::GET,$encodeJson=true)
    {
        if (count($params) && $encodeJson && $method!=Ebizmarts_Mailchimp::GET) {
            $params = json_encode($params);
        }

        $ch = $this->_ch;
        if (count($params)&&$method!=Ebizmarts_Mailchimp::GET) {
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
            throw new Mailchimp_Error("API call to $url failed: " . curl_error($ch));
        }

        if (floor($info['http_code'] / 100) >= 4) {
            $errors = (isset($result['errors'])) ? $result['errors'] : '';
            throw new Mailchimp_Error($url, $result['title'], $result['detail'], $errors);
        }

        return $result;
    }
}