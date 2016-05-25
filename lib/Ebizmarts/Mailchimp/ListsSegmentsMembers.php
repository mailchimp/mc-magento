<?php
/**
 * mailchimp-lib Magento Component
 *
 * @category Ebizmarts
 * @package mailchimp-lib
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 5/2/16 4:39 PM
 * @file: ListsSegmentsMembers.php
 */
class Mailchimp_ListsSegmentsMembers extends Mailchimp_Abstract
{
    /**
     * @param $listId               The unique id for the list.
     * @param $segmentId            The unique id for the segment.
     * @param null $fields          A comma-separated list of fields to return. Reference parameters of sub-objects with
     *                              dot notation.
     * @param null $excludeFields   A comma-separated list of fields to exclude. Reference parameters of sub-objects
     *                              with dot notation.
     * @param null $count           The number of records to return.
     * @param null $offset          The number of records from a collection to skip. Iterating over large collections
     *                              with this parameter can be slow.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function getAll($listId,$segmentId,$fields=null,$excludeFields=null,$count=null,$offset=null)
    {
        $_params = array();
        if($fields) $_params['fields'] = $fields;
        if($excludeFields) $_params['exclude_fields'] = $excludeFields;
        if($count) $_params['count'] = $count;
        if($offset) $_params['offset'] = $offset;
        return $this->master->call('lists/'.$listId.'/segments/'.$segmentId.'/members',$_params,Mailchimp::GET);
    }

    /**
     * @param $listId                   The unique id for the list.
     * @param $segmentId                The unique id for the segment.
     * @param null $id                  The MD5 hash of the lowercase version of the list member’s email address.
     * @param null $emailAddress        Email address for a subscriber.
     * @param null $uniqueEmailId       An identifier for the address across all of MailChimp.
     * @param $emailType                Type of email this member asked to get (‘html’ or ‘text’).
     * @param null $status              Subscriber’s current status.
     *                                  Possible Values: subscribed | unsubscribed | cleaned | pending
     * @param null $mergeFields         An individual merge var and value for a member.
     * @param null $interest            The key of this object’s properties is the ID of the interest in question.
     * @param null $stats               Open and click rates for this subscriber.
     * @param null $ipSignup            IP address the subscriber signed up from.
     * @param null $timestampSignup     The date and time the subscriber signed up for the list.
     * @param null $ipOpt               The IP address the subscriber used to confirm their opt-in status.
     * @param null $timestamp_op        The date and time the subscribe confirmed their opt-in status.
     * @param null $memberRating        Star rating for this member, between 1 and 5.
     * @param null $lastChanged         The date and time the member’s info was last changed.
     * @param null $language            If set/detected, the subscriber’s language.
     * @param null $vip                 VIP status for subscriber.
     * @param null $emailClient         The list member’s email client.
     * @param null $location            Subscriber location information.
     * @param null $lastNote            The most recent Note added about this member.
     * @param null $listId              The list id.
     * @param null $_links              A list of link types and descriptions for the API schema documents.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function add($listId,$segmentId,$id=null,$emailAddress=null,$uniqueEmailId=null,$emailType,$status=null,
                        $mergeFields=null,$interest=null,$stats=null,$ipSignup=null,$timestampSignup=null,$ipOpt=null,
                        $timestamp_op=null,$memberRating=null,$lastChanged=null,$language=null,$vip=null,
                        $emailClient=null,$location=null,$lastNote=null,$_listId=null,$_links=null)
    {
        $_params = array();
        if($id) $_params['id'] = $id;
        if($emailAddress) $_params['email_address'] = $emailAddress;
        if($uniqueEmailId) $_params['unique_email_id'] = $uniqueEmailId;
        if($emailType) $_params['email_type'] = $emailType;
        if($status) $_params['status'] = $status;
        if($mergeFields) $_params['merge_fields'] = $mergeFields;
        if($interest) $_params['interest'] = $interest;
        if($stats) $_params['stats'] = $stats;
        if($ipSignup) $_params['ip_signup'] = $ipSignup;
        if($timestampSignup) $_params['timestamp_signup'] = $timestampSignup;
        if($ipOpt) $_params['ip_opt'] = $ipOpt;
        if($timestamp_op) $_params['timestamp_op'] = $timestamp_op;
        if($memberRating) $_params['member_rating'] = $memberRating;
        if($lastChanged) $_params['last_changed'] = $lastChanged;
        if($language)  $_params['language'] = $language;
        if($vip) $_params['vip'] = $vip;
        if($emailClient) $_params['email_client'] = $emailClient;
        if($location) $_params['location'] = $location;
        if($lastNote) $_params['last_note'] = $lastNote;
        if($_listId) $_params['list_id'] =$_listId;
        if($_links) $_listId['_links'] = $_links;
        return $this->master->call('lists/'.$listId.'/segments/'.$segmentId.'/members',$_params,Mailchimp::POST);
    }
    public function delete($listId,$segmentId,$subscriberHash)
    {
        return $this->master->call('lists/'.$listId.'/segments/'.$segmentId.'/members/'.$subscriberHash,null,Mailchimp::DELETE);
    }
}