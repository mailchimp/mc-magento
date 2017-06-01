<?php
/**
 * mailchimp-lib Magento Component
 *
 * @category  Ebizmarts
 * @package   mailchimp-lib
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     5/2/16 3:48 PM
 * @file:     ListsInterestCategory.php
 */
class MailChimp_ListsInterestCategory extends MailChimp_Abstract
{
    /**
     * @var MailChimp_ListInterestCategoryInterests
     */
    public $interests;

    /**
     * @param $listId             The unique id for the list.
     * @param $title              The text description of this category. This field appears on signup forms and is
     *                            often phrased as a question.
     * @param null                                                                                        $displayOrder The order that the categories are displayed in the list. Lower numbers display first.
     * @param $type               Determines how this category’s interests are displayed on signup forms.
     *                            (checkboxes | dropdown | radio | hidden)
     * @return mixed
     * @throws MailChimp_Error
     * @throws MailChimp_HttpError
     */
    public function add($listId, $title, $type, $displayOrder=null)
    {
        $_params = array('title'=>$title,'type'=>$type);
        if($displayOrder) { $_params['display_order'] = $displayOrder;
        }
        return $this->_master->call('lists/'.$listId.'/interest-categories', $_params, Ebizmarts_MailChimp::POST);
    }

    /**
     * @param $listId               The unique id for the list.
     * @param null                                            $fields        A comma-separated list of fields to return. Reference parameters of sub-objects
     *                                                                       with dot notation.
     * @param null                                            $excludeFields A comma-separated list of fields to exclude. Reference parameters of sub-objects
     *                                                                       with dot notation.
     * @param null                                            $count         The number of records to return.
     * @param null                                            $offset        The number of records from a collection to skip. Iterating over large collections
     *                                                                       with this parameter can be slow.
     * @param null                                            $type          Restrict results a type of interest group
     * @return mixed
     * @throws MailChimp_Error
     * @throws MailChimp_HttpError
     */
    public function getAll($listId,$fields=null,$excludeFields=null,$count=null,$offset=null,$type=null)
    {
        $_params = array();
        if($fields) { $_params['fields'] = $fields;
        }
        if($excludeFields) { $_params['exclude_fields'] = $excludeFields;
        }
        if($count) { $_params['count'] = $count;
        }
        if($offset) { $_params['offset'] = $offset;
        }
        if($type) { $_params['type'] = $type;
        }
        return $this->_master->call('lists/'.$listId.'/interest-categories', $_params, Ebizmarts_MailChimp::GET);
    }

    /**
     * @param $listId               The unique id for the list.
     * @param $interestCategoryId   The unique id for the interest category.
     * @param null                                                         $fields        A comma-separated list of fields to return. Reference parameters of sub-objects
     *                                                                                    with dot notation.
     * @param null                                                         $excludeFields A comma-separated list of fields to exclude. Reference parameters of sub-objects
     *                                                                                    with dot notation.
     * @return mixed
     * @throws MailChimp_Error
     * @throws MailChimp_HttpError
     */
    public function get($listId,$interestCategoryId,$fields=null,$excludeFields=null)
    {
        $_params = array();
        if($fields) { $_params['fields'] = $fields;
        }
        if($excludeFields) { $_params['exclude_fields'] = $excludeFields;
        }
        $url = 'lists/'.$listId.'/interest-categories/'.$interestCategoryId;
        return $this->_master->call($url, $_params, Ebizmarts_MailChimp::GET);
    }

    /**
     * @param $listId             The unique id for the list.
     * @param $interestCategoryId The unique id for the interest category.
     * @param $title              The text description of this category. This field appears on signup forms and is
     *                            often phrased as a question.
     * @param null                                                                                        $displayOrder The order that the categories are displayed in the list. Lower numbers display first.
     * @param $type               Determines how this category’s interests are displayed on signup forms.
     *                            (checkboxes | dropdown | radio | hidden)
     * @return mixed
     * @throws MailChimp_Error
     * @throws MailChimp_HttpError
     */
    public function modify($listId, $interestCategoryId, $title, $type, $displayOrder=null)
    {
        $_params = array('title'=>$title,'type'=>$type);
        if($displayOrder) { $_params['display_order'] = $displayOrder;
        }
        $url = 'lists/'.$listId.'/interest-categories/'.$interestCategoryId;
        return $this->_master->call($url, $_params, Ebizmarts_MailChimp::PATCH);
    }

    /**
     * @param $listId               The unique id for the list.
     * @param $interestCategoryId   The unique id for the interest category.
     * @return mixed
     * @throws MailChimp_Error
     * @throws MailChimp_HttpError
     */
    public function delete($listId,$interestCategoryId)
    {
        $url = 'lists/'.$listId.'/interest-categories/'.$interestCategoryId;
        return $this->_master->call($url, null, Ebizmarts_MailChimp::DELETE);
    }

}