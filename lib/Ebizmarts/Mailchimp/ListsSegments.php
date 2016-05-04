<?php
/**
 * mailchimp-lib Magento Component
 *
 * @category Ebizmarts
 * @package mailchimp-lib
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 5/2/16 2:00 PM
 * @file: ListsSegments.php
 */
class Mailchimp_ListsSegments extends Mailchimp_Abstract
{
    /**
     * @var Mailchimp_ListsSegmentsMembers
     */
    public $segmentMembers;

    public function getInformation($listId,$fields=null)
    {
        $_params = array();
        if($fields)
        {
            $_params['fields'] = $fields;
        }
        return $this->master->call('lists/'.$listId.'/segments',$_params,Mailchimp::GET);
    }
}