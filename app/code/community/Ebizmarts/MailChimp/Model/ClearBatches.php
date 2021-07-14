<?php

class Ebizmarts_MailChimp_Model_ClearBatches
{
    /**
     * @var Ebizmarts_MailChimp_Helper_Data
     */
    protected $_helper;
    public function __construct()
    {
        $this->_helper = Mage::helper('mailchimp');

    }
    public function clearBatches()
    {
        // Clean batches table, remove all the canceled or completed batch jobs
        try {
            $resource = $this->_helper->getCoreResource();
            $connection = $resource->getConnection('core_write');
            $tableNameBatches = $resource->getTableName('mailchimp/synchbatches');
            $where = 'status IN("completed","canceled") and ( date_add(modified_date, interval 1 month) < now() OR modified_date IS NULL)';
            $connection->delete($tableNameBatches, $where);
        }  catch (\Exception $e) {
            $this->_helper->logBatchStatus($e->getMessage());
        }
        // Clean errors table, remove all the errors that belongs to a non existing batch
        try {
            $resource = $this->_helper->getCoreResource();
            $connection = $resource->getConnection('core_write');
            $tableNameBatches = $resource->getTableName('mailchimp/synchbatches');
            $select = $connection->select();
            $select->from($tableNameBatches, ['batch_id']);
            $select->where('status IN("completed","canceled") and ( date_add(modified_date, interval 1 month) < now() OR modified_date IS NULL)');
            $existingBatchIds = $connection->fetchCol($select);
            $tableNameErrors = $resource->getTableName('mailchimp/mailchimperrors');
            if ($existingBatchIds) {
                $connection->delete($tableNameErrors, ['batch_id NOT IN ?' => $existingBatchIds]);
            } else {
                $connection->delete($tableNameErrors);
            }
        } catch (\Exception $e) {
            $this->_helper->logBatchStatus($e->getMessage());
        }
    }

}
