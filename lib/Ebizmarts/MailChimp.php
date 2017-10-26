<?php
/**
 * mailchimp-lib Magento Component
 *
 * @category  Ebizmarts
 * @package   mailchimp-lib
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     4/27/16 4:36 PM
 * @file:     Mailchimp.php
 */
if (defined("COMPILER_INCLUDE_PATH")) {
    include_once dirname(__FILE__) . '/Ebizmarts/MailChimp/Abstract.php';
    include_once dirname(__FILE__) . '/Ebizmarts/MailChimp/Root.php';
    include_once dirname(__FILE__) . '/Ebizmarts/MailChimp/Automation.php';
    include_once dirname(__FILE__) . '/Ebizmarts/MailChimp/AutomationEmails.php';
    include_once dirname(__FILE__) . '/Ebizmarts/MailChimp/AutomationEmailsQueue.php';
    include_once dirname(__FILE__) . '/Ebizmarts/MailChimp/Exceptions.php';
    include_once dirname(__FILE__) . '/Ebizmarts/MailChimp/AuthorizedApps.php';
    include_once dirname(__FILE__) . '/Ebizmarts/MailChimp/Automation.php';
    include_once dirname(__FILE__) . '/Ebizmarts/MailChimp/BatchOperations.php';
    include_once dirname(__FILE__) . '/Ebizmarts/MailChimp/CampaignFolders.php';
    include_once dirname(__FILE__) . '/Ebizmarts/MailChimp/Campaigns.php';
    include_once dirname(__FILE__) . '/Ebizmarts/MailChimp/CampaignsContent.php';
    include_once dirname(__FILE__) . '/Ebizmarts/MailChimp/CampaignsFeedback.php';
    include_once dirname(__FILE__) . '/Ebizmarts/MailChimp/CampaignsSendChecklist.php';
    include_once dirname(__FILE__) . '/Ebizmarts/MailChimp/Conversations.php';
    include_once dirname(__FILE__) . '/Ebizmarts/MailChimp/ConversationsMessages.php';
    include_once dirname(__FILE__) . '/Ebizmarts/MailChimp/Ecommerce.php';
    include_once dirname(__FILE__) . '/Ebizmarts/MailChimp/EcommerceStores.php';
    include_once dirname(__FILE__) . '/Ebizmarts/MailChimp/EcommerceCarts.php';
    include_once dirname(__FILE__) . '/Ebizmarts/MailChimp/EcommerceCustomers.php';
    include_once dirname(__FILE__) . '/Ebizmarts/MailChimp/EcommerceOrders.php';
    include_once dirname(__FILE__) . '/Ebizmarts/MailChimp/EcommerceOrdersLines.php';
    include_once dirname(__FILE__) . '/Ebizmarts/MailChimp/EcommerceProducts.php';
    include_once dirname(__FILE__) . '/Ebizmarts/MailChimp/EcommerceProductsVariants.php';
    include_once dirname(__FILE__) . '/Ebizmarts/MailChimp/EcommercePromoRules.php';
    include_once dirname(__FILE__) . '/Ebizmarts/MailChimp/EcommercePromoRulesPromoCodes.php';
    include_once dirname(__FILE__) . '/Ebizmarts/MailChimp/FileManagerFiles.php';
    include_once dirname(__FILE__) . '/Ebizmarts/MailChimp/FileManagerFolders.php';
    include_once dirname(__FILE__) . '/Ebizmarts/MailChimp/Lists.php';
    include_once dirname(__FILE__) . '/Ebizmarts/MailChimp/ListsAbuseReports.php';
    include_once dirname(__FILE__) . '/Ebizmarts/MailChimp/ListsActivity.php';
    include_once dirname(__FILE__) . '/Ebizmarts/MailChimp/ListsClients.php';
    include_once dirname(__FILE__) . '/Ebizmarts/MailChimp/ListsGrowthHistory.php';
    include_once dirname(__FILE__) . '/Ebizmarts/MailChimp/ListsInterestCategory.php';
    include_once dirname(__FILE__) . '/Ebizmarts/MailChimp/ListsInterestCategoryInterests.php';
    include_once dirname(__FILE__) . '/Ebizmarts/MailChimp/ListsMembers.php';
    include_once dirname(__FILE__) . '/Ebizmarts/MailChimp/ListsMembersActivity.php';
    include_once dirname(__FILE__) . '/Ebizmarts/MailChimp/ListsMembersGoals.php';
    include_once dirname(__FILE__) . '/Ebizmarts/MailChimp/ListsMembersNotes.php';
    include_once dirname(__FILE__) . '/Ebizmarts/MailChimp/ListsMergeFields.php';
    include_once dirname(__FILE__) . '/Ebizmarts/MailChimp/ListsSegments.php';
    include_once dirname(__FILE__) . '/Ebizmarts/MailChimp/ListsSegmentsMembers.php';
    include_once dirname(__FILE__) . '/Ebizmarts/MailChimp/ListsWebhooks.php';
    include_once dirname(__FILE__) . '/Ebizmarts/MailChimp/Reports.php';
    include_once dirname(__FILE__) . '/Ebizmarts/MailChimp/ReportsCampaignAdvice.php';
    include_once dirname(__FILE__) . '/Ebizmarts/MailChimp/ReportsClickReports.php';
    include_once dirname(__FILE__) . '/Ebizmarts/MailChimp/ReportsClickReportsMembers.php';
    include_once dirname(__FILE__) . '/Ebizmarts/MailChimp/ReportsDomainPerformance.php';
    include_once dirname(__FILE__) . '/Ebizmarts/MailChimp/ReportsEapURLReport.php';
    include_once dirname(__FILE__) . '/Ebizmarts/MailChimp/ReportsEmailActivity.php';
    include_once dirname(__FILE__) . '/Ebizmarts/MailChimp/ReportsLocation.php';
    include_once dirname(__FILE__) . '/Ebizmarts/MailChimp/ReportsSentTo.php';
    include_once dirname(__FILE__) . '/Ebizmarts/MailChimp/ReportsSubReports.php';
    include_once dirname(__FILE__) . '/Ebizmarts/MailChimp/ReportsUnsubscribes.php';
    include_once dirname(__FILE__) . '/Ebizmarts/MailChimp/TemplateFolders.php';
    include_once dirname(__FILE__) . '/Ebizmarts/MailChimp/Templates.php';
    include_once dirname(__FILE__) . '/Ebizmarts/MailChimp/TemplatesDefaultContent.php';
} else {
    include_once dirname(__FILE__) . '/MailChimp/Abstract.php';
    include_once dirname(__FILE__) . '/MailChimp/Root.php';
    include_once dirname(__FILE__) . '/MailChimp/Automation.php';
    include_once dirname(__FILE__) . '/MailChimp/AutomationEmails.php';
    include_once dirname(__FILE__) . '/MailChimp/AutomationEmailsQueue.php';
    include_once dirname(__FILE__) . '/MailChimp/Exceptions.php';
    include_once dirname(__FILE__) . '/MailChimp/AuthorizedApps.php';
    include_once dirname(__FILE__) . '/MailChimp/Automation.php';
    include_once dirname(__FILE__) . '/MailChimp/BatchOperations.php';
    include_once dirname(__FILE__) . '/MailChimp/CampaignFolders.php';
    include_once dirname(__FILE__) . '/MailChimp/Campaigns.php';
    include_once dirname(__FILE__) . '/MailChimp/CampaignsContent.php';
    include_once dirname(__FILE__) . '/MailChimp/CampaignsFeedback.php';
    include_once dirname(__FILE__) . '/MailChimp/CampaignsSendChecklist.php';
    include_once dirname(__FILE__) . '/MailChimp/Conversations.php';
    include_once dirname(__FILE__) . '/MailChimp/ConversationsMessages.php';
    include_once dirname(__FILE__) . '/MailChimp/Ecommerce.php';
    include_once dirname(__FILE__) . '/MailChimp/EcommerceStores.php';
    include_once dirname(__FILE__) . '/MailChimp/EcommerceCarts.php';
    include_once dirname(__FILE__) . '/MailChimp/EcommerceCustomers.php';
    include_once dirname(__FILE__) . '/MailChimp/EcommerceOrders.php';
    include_once dirname(__FILE__) . '/MailChimp/EcommerceOrdersLines.php';
    include_once dirname(__FILE__) . '/MailChimp/EcommerceProducts.php';
    include_once dirname(__FILE__) . '/MailChimp/EcommerceProductsVariants.php';
    include_once dirname(__FILE__) . '/MailChimp/EcommercePromoRules.php';
    include_once dirname(__FILE__) . '/MailChimp/EcommercePromoRulesPromoCodes.php';
    include_once dirname(__FILE__) . '/MailChimp/FileManagerFiles.php';
    include_once dirname(__FILE__) . '/MailChimp/FileManagerFolders.php';
    include_once dirname(__FILE__) . '/MailChimp/Lists.php';
    include_once dirname(__FILE__) . '/MailChimp/ListsAbuseReports.php';
    include_once dirname(__FILE__) . '/MailChimp/ListsActivity.php';
    include_once dirname(__FILE__) . '/MailChimp/ListsClients.php';
    include_once dirname(__FILE__) . '/MailChimp/ListsGrowthHistory.php';
    include_once dirname(__FILE__) . '/MailChimp/ListsInterestCategory.php';
    include_once dirname(__FILE__) . '/MailChimp/ListsInterestCategoryInterests.php';
    include_once dirname(__FILE__) . '/MailChimp/ListsMembers.php';
    include_once dirname(__FILE__) . '/MailChimp/ListsMembersActivity.php';
    include_once dirname(__FILE__) . '/MailChimp/ListsMembersGoals.php';
    include_once dirname(__FILE__) . '/MailChimp/ListsMembersNotes.php';
    include_once dirname(__FILE__) . '/MailChimp/ListsMergeFields.php';
    include_once dirname(__FILE__) . '/MailChimp/ListsSegments.php';
    include_once dirname(__FILE__) . '/MailChimp/ListsSegmentsMembers.php';
    include_once dirname(__FILE__) . '/MailChimp/ListsWebhooks.php';
    include_once dirname(__FILE__) . '/MailChimp/Reports.php';
    include_once dirname(__FILE__) . '/MailChimp/ReportsCampaignAdvice.php';
    include_once dirname(__FILE__) . '/MailChimp/ReportsClickReports.php';
    include_once dirname(__FILE__) . '/MailChimp/ReportsClickReportsMembers.php';
    include_once dirname(__FILE__) . '/MailChimp/ReportsDomainPerformance.php';
    include_once dirname(__FILE__) . '/MailChimp/ReportsEapURLReport.php';
    include_once dirname(__FILE__) . '/MailChimp/ReportsEmailActivity.php';
    include_once dirname(__FILE__) . '/MailChimp/ReportsLocation.php';
    include_once dirname(__FILE__) . '/MailChimp/ReportsSentTo.php';
    include_once dirname(__FILE__) . '/MailChimp/ReportsSubReports.php';
    include_once dirname(__FILE__) . '/MailChimp/ReportsUnsubscribes.php';
    include_once dirname(__FILE__) . '/MailChimp/TemplateFolders.php';
    include_once dirname(__FILE__) . '/MailChimp/Templates.php';
    include_once dirname(__FILE__) . '/MailChimp/TemplatesDefaultContent.php';
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
        $this->ecommerce->promoRules                        = new MailChimp_EcommercePromoRules($this);
        $this->ecommerce->promoRules->promoCodes            = new MailChimp_EcommercePromoRulesPromoCodes($this);
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