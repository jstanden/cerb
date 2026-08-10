<?php

use Cerb\Records\FileImporter;
use Cerb\Records\WorklistExporter;

class _DevblocksRecordsService {
	private static ?_DevblocksRecordsService $_instance = null;
	
	static function getInstance(): _DevblocksRecordsService {
		if (is_null(self::$_instance))
			self::$_instance = new _DevblocksRecordsService();
		
		return self::$_instance;
	}
	
	private function __construct() {
	}

	/**
	 * Resolve a context's singular/plural alias for use in job-log messages
	 * ("ticket"/"tickets", "custom record"/"custom records"). Falls back to a
	 * generic "record"/"records" if the context can't be resolved.
	 */
	private static function _recordNoun(string $context, int $count) : string {
		$context_ext = $context ? \Extension_DevblocksContext::get($context) : null;
		$aliases = $context_ext
			? \Extension_DevblocksContext::getAliasesForContext($context_ext->manifest)
			: null;

		if($count === 1)
			return $aliases['singular'] ?? '' ?: 'record';

		return $aliases['plural'] ?? '' ?: 'records';
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
		
		// A ZIP bundle imports files into an agent filesystem rather than rows into a record type. It
		// shares this queue (and its monitor UI) but none of the CSV/JSONL mapping machinery, and it
		// owns its own batching, so hand off before we dequeue.
		if('zip' === ($queue_job->metadata['format'] ?? ''))
			return \Cerb\Agent\FilesystemImporter::processQueue($queue, $stop_time, $count_hint, $queue_job);

		$processed = 0;
		$claim_id = null;
		$batch_size = 100;

		if(!($queue_messages = $queue_service->dequeue($queue->name, $batch_size, $claim_id, $job_id)))
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

			$queue_service->reportSuccess(
				$queue_messages,
				sprintf('Imported %d %s', count($results), self::_recordNoun($context, count($results))),
				$context ? ['context' => $context] : []
			);

		} catch (\Exception_DevblocksValidationError $e) {
			DevblocksPlatform::logException($e);
			$queue_service->reportFailure($queue_messages, $e->getMessage());
		}
		
		$processed += count($queue_messages);

		return $processed;
	}

	public function processExportQueue(Model_Queue $queue, int $stop_time, int $count_hint, ?Model_QueueJob $queue_job=null) : int {
		$queue_service = DevblocksPlatform::services()->queue();

		if($queue_job) {
			$job_id = $queue_job->id;

		} else {
			if(!($job_stats = \DAO_QueueJob::getAvailableMessages($queue)))
				return 0;

			shuffle($job_stats);

			$job_id = $job_stats[array_key_first($job_stats)]['job_id'] ?? null;

			if(!($queue_job = \DAO_QueueJob::get($job_id))) {
				return 0;
			}
		}

		// Small per-iteration batches so concurrent workers interleave on the same
		// job instead of one worker reserving everything in a single dequeue. The
		// outer while keeps HTTP overhead amortized (one request, many MySQL
		// dequeues) and bounded by the caller's $stop_time budget.
		$batch_size = 5;
		$processed = 0;
		$claim_id = null;

		$context = $queue_job->metadata['context'] ?? '';
		$exporter = new WorklistExporter($context);

		// Pass worker_id through metadata so ACL filters apply during chunk render
		$render_metadata = $queue_job->metadata;
		$render_metadata['worker_id'] = $queue_job->worker_id;

		while($stop_time > time()) {
			if(!($queue_messages = $queue_service->dequeue($queue->name, $batch_size, $claim_id, $job_id)))
				break;

			try {
				$record_count_in_batch = 0;

				foreach($queue_messages as $queue_message) {
					$payload = $queue_message->message;
					$chunk_idx = intval($payload['chunk'] ?? 0);
					$record_ids = array_map('intval', $payload['ids'] ?? []);

					$bytes = $exporter->renderChunkBytes($record_ids, $render_metadata);
					\DAO_QueueJobChunk::put($queue_job->id, $chunk_idx, $bytes);

					$record_count_in_batch += count($record_ids);
				}

				$queue_service->reportSuccess(
					$queue_messages,
					sprintf('Exported %d %s', $record_count_in_batch, self::_recordNoun($context, $record_count_in_batch))
				);

			} catch(\Throwable $e) {
				DevblocksPlatform::logException($e);
				$queue_service->reportFailure($queue_messages, $e->getMessage());
			}

			$processed += count($queue_messages);
		}

		return $processed;
	}

	public function onExportJobComplete(Model_QueueJob $queue_job) : void {
		$context = $queue_job->metadata['context'] ?? '';
		$file_name = $queue_job->metadata['file_name'] ?? 'export';
		$mime_type = $queue_job->metadata['mime_type'] ?? 'application/octet-stream';

		try {
			$exporter = new WorklistExporter($context);
		} catch(\Throwable $e) {
			DevblocksPlatform::logException($e);
			\DAO_QueueJobChunk::deleteByJobIds([$queue_job->id]);
			return;
		}

		$fp = DevblocksPlatform::getTempFile();

		if(!$fp) {
			\DAO_QueueJobChunk::deleteByJobIds([$queue_job->id]);
			return;
		}

		$separator = $exporter->getChunkSeparator($queue_job->metadata);

		fwrite($fp, $exporter->renderPrologue($queue_job->metadata));

		$first = true;
		\DAO_QueueJobChunk::streamByJobId($queue_job->id, function($data) use (&$first, $fp, $separator) {
			if(!$first && $separator !== '')
				fwrite($fp, $separator);
			fwrite($fp, $data);
			$first = false;
		});

		fwrite($fp, $exporter->renderEpilogue($queue_job->metadata));

		$stats = fstat($fp);
		fseek($fp, 0);
		$sha1_hash = hash_init('sha1');
		while(!feof($fp))
			hash_update($sha1_hash, fread($fp, 65536));
		$sha1_hash = hash_final($sha1_hash);
		fseek($fp, 0);

		$attachment_id = \DAO_Attachment::create([
			\DAO_Attachment::NAME => $file_name,
			\DAO_Attachment::MIME_TYPE => $mime_type,
			\DAO_Attachment::STORAGE_SHA1HASH => $sha1_hash,
			\DAO_Attachment::STORAGE_SIZE => $stats['size'],
			\DAO_Attachment::UPDATED => time(),
		]);

		if($attachment_id) {
			\Storage_Attachments::put($attachment_id, $fp);

			// Link to the queue job so any worker who can read the job can download the file
			\DAO_Attachment::addLinks(\CerberusContexts::CONTEXT_QUEUE_JOB, $queue_job->id, $attachment_id);

			// Stash the attachment id on the job for audit (the abstract notification
			// + Monitor widget both surface the attachment via the attachment_link)
			$metadata = $queue_job->metadata;
			$metadata['attachment_id'] = $attachment_id;
			\DAO_QueueJob::update($queue_job->id, [
				\DAO_QueueJob::METADATA => json_encode($metadata),
			]);
		}

		fclose($fp);

		\DAO_QueueJobChunk::deleteByJobIds([$queue_job->id]);
	}
}