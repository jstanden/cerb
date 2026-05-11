<?php

use Cerb\Records\FileImporter;

class _DevblocksRecordsService {
	private static ?_DevblocksRecordsService $_instance = null;
	
	static function getInstance(): _DevblocksRecordsService {
		if (is_null(self::$_instance))
			self::$_instance = new _DevblocksRecordsService();
		
		return self::$_instance;
	}
	
	private function __construct() {
	}
	
	public function processImportQueue(Model_Queue $queue, int $stop_time, int $count_hint, ?Model_QueueJob $queue_job=null) : int {
		$queue_service = DevblocksPlatform::services()->queue();
		
		if($queue_job) {
			$job_id = $queue_job->id;
			
		} else {
			if(!($job_stats = DAO_QueueJob::getAvailableMessages($queue)))
				return 0;
			
			shuffle($job_stats);
			
			$job_id = $job_stats[array_key_first($job_stats)]['job_id'] ?? null;
			
			// If no job ID, we can't process this
			if(!($queue_job = \DAO_QueueJob::get($job_id))) {
				return 0;
			}
		}
		
		$processed = 0;
		$consumer_id = null;
		$batch_size = 100;
		
		if(!($queue_messages = $queue_service->dequeue($queue->name, $batch_size, $consumer_id, $job_id)))
			return 0;

		if(!($import_token = $queue_job->metadata['import_token'] ?? null)) {
			$queue_service->reportFailure($queue_messages, 'Missing import token');
			return 0;
		}
		
		if(!($automation_resource = \DAO_AutomationResource::getByToken($import_token))) {
			$queue_service->reportFailure($queue_messages, 'Invalid import file');
			return 0;
		}
		
		try {
			$context = $queue_job->metadata['context'] ?? '';
			
			$importer = new FileImporter($automation_resource, $context);
			
			$mapping = new FileImporter\Mapping(
				$queue_job->metadata['mapping']['field'] ?? [],
				$queue_job->metadata['mapping']['column'] ?? [],
				$queue_job->metadata['mapping']['column_custom'] ?? [],
				$queue_job->metadata['mapping']['sync_dupes'] ?? [],
			);
			
			$results = array_map(
				fn($queue_message) => $importer->getFileRecordByOffset(
					$queue_message->message[1],
					$queue_message->message[2],
					$mapping
				),
				$queue_messages,
			);
			
			$results = $importer->bulkFormatRecordFields($results, $mapping);
			
			$results = $importer->bulkTagUpserts($results, $mapping);
			
			$importer->importRecords($results, $mapping);
			
			$queue_service->reportSuccess($queue_messages);
			
		} catch (\Exception_DevblocksValidationError $e) {
			DevblocksPlatform::logException($e);
		}
		
		$processed += count($queue_messages);
		
		return $processed;
	}
}