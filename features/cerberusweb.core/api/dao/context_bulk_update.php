<?php
/**
 * Bulk-update payload passed to a DAO's static `bulkUpdate(Model_ContextBulkUpdate $update)` method.
 *
 * Bulk update jobs run through the parallel `cerb.records.bulk_update` queue. The consumer
 * (`_DevblocksRecordsService::processBulkUpdateQueue`) constructs a Model_ContextBulkUpdate from
 * each dequeued queue_message + the parent queue_job's metadata, then dispatches to the
 * record type's `DAO_X::bulkUpdate()`.
 */
class Model_ContextBulkUpdate {
	public array $actions = [];
	public array $context_ids = [];
	public int $job_id = 0;
	public int $num_records = 0;
	public int $worker_id = 0;
	public string $context = '';
	public string $view_id = '';
}
