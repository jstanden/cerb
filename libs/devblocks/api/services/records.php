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
		$consumer_id = null;

		$context = $queue_job->metadata['context'] ?? '';
		$exporter = new WorklistExporter($context);

		// Pass worker_id through metadata so ACL filters apply during chunk render
		$render_metadata = $queue_job->metadata;
		$render_metadata['worker_id'] = $queue_job->worker_id;

		while($stop_time > time()) {
			if(!($queue_messages = $queue_service->dequeue($queue->name, $batch_size, $consumer_id, $job_id)))
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

	/**
	 * Create a bulk-update queue_job from the worklist filter and the parsed `$do` action set.
	 * Returns the created Model_QueueJob (with $queue_job->id populated) or null on failure.
	 */
	public function createBulkUpdateJob(\C4_AbstractView $view, array $do, int $worker_id, int $batch_size = 100) : ?\Model_QueueJob {
		$db = DevblocksPlatform::services()->database();

		if(empty($do))
			return null;

		if($batch_size < 1) $batch_size = 100;

		if(!($context_ext = \Extension_DevblocksContext::getByViewClass(get_class($view), true)))
			return null;

		if(!($dao_class = $context_ext->getDaoClass()))
			return null;

		if(!($search_class = $context_ext->getSearchClass()))
			return null;

		if(!($queue = \DAO_Queue::getByName('cerb.records.bulk_update')))
			return null;

		$query_parts = $dao_class::getSearchQueryComponents([], $view->getParams());

		if(empty($query_parts['primary_table']))
			return null;

		$primary_key = $search_class::getPrimaryKey();

		$count_sql = "SELECT COUNT(1) " . $query_parts['join'] . $query_parts['where'];
		$record_count = intval($db->GetOneReader($count_sql));

		if(!$record_count)
			return null;

		$aliases = \Extension_DevblocksContext::getAliasesForContext($context_ext->manifest);

		$queue_job = new \Model_QueueJob();
		$queue_job->queue_id = $queue->id;
		$queue_job->name = sprintf('Bulk update %s',
			$aliases['plural'] ?? $aliases['uri'] ?? $context_ext->id
		);
		$queue_job->status_id = \QueueJobStatus::RUNNING->value;
		$queue_job->count_total = $record_count;
		$queue_job->count_available = $record_count;
		$queue_job->worker_id = $worker_id;
		$queue_job->metadata = [
			'context'      => $context_ext->id,
			'view_id'      => $view->id,
			'query'        => $view->getParamsQuery(),
			'record_count' => $record_count,
			'batch_size'   => $batch_size,
			'actions'      => $do,
		];
		$queue_job = \DAO_QueueJob::create($queue_job);

		if(!$queue_job)
			return null;

		// Generate one queue_message per batch via INSERT...SELECT, mirroring the export producer.
		// Inner subquery dedupes IDs (joins may not be 1:1) and assigns each record a chunk index.
		$inner_select_sql =
			"SELECT " . $primary_key . " AS id " .
			$query_parts['join'] .
			$query_parts['where'] .
			" GROUP BY " . $primary_key
		;

		$db->ExecuteMaster('SET group_concat_max_len = 1024000');

		$sql = sprintf(
			"INSERT INTO queue_message (uuid, queue_id, job_id, status_id, status_at, message, cardinality) ".
			"SELECT UUID_TO_BIN(UUID()) AS uuid, ".
			"%d AS queue_id, ".
			"%d AS job_id, ".
			"0 AS status_id, ".
			"UNIX_TIMESTAMP() AS status_at, ".
			"CONCAT('{\"ids\":[', GROUP_CONCAT(id ORDER BY id), ']}') AS message, ".
			"COUNT(id) AS cardinality ".
			"FROM (SELECT id, CEIL(ROW_NUMBER() OVER (ORDER BY id) / %d) AS batch FROM (%s) AS deduped) AS batched ".
			"GROUP BY batch",
			$queue->id,
			$queue_job->id,
			$batch_size,
			$inner_select_sql
		);
		$db->ExecuteMaster($sql);

		return $queue_job;
	}

	public function processBulkUpdateQueue(\Model_Queue $queue, int $stop_time, int $count_hint, ?\Model_QueueJob $queue_job=null) : int {
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

		$context = $queue_job->metadata['context'] ?? '';
		$actions = $queue_job->metadata['actions'] ?? [];
		$worker = $queue_job->worker_id ? \DAO_Worker::get($queue_job->worker_id) : null;

		// Validate prerequisites once, outside the loop. If anything's wrong we
		// still need to surface failure on at least one message so the job
		// doesn't appear to make no progress; dequeue one to report against.
		if(!$context || !$actions || !$worker
			|| !($context_ext = \Extension_DevblocksContext::get($context))
			|| !($dao_class = $context_ext->getDaoClass())
			|| !method_exists($dao_class, 'bulkUpdate')
		) {
			$consumer_id = null;
			if(!($queue_messages = $queue_service->dequeue($queue->name, 1, $consumer_id, $job_id)))
				return 0;
			$queue_service->reportFailure($queue_messages, 'Missing context, actions, worker, or unsupported context');
			return count($queue_messages);
		}

		// One message at a time (each carries ~100 records) but loop so concurrent
		// workers can interleave on the same job within the $stop_time budget.
		$batch_size = 1;
		$processed = 0;
		$consumer_id = null;

		while($stop_time > time()) {
			if(!($queue_messages = $queue_service->dequeue($queue->name, $batch_size, $consumer_id, $job_id)))
				break;

			try {
				$touched_ids = [];

				foreach($queue_messages as $queue_message) {
					$record_ids = array_map('intval', $queue_message->message['ids'] ?? []);

					if(!$record_ids) continue;

					// ACL: drop IDs the worker can't write to (mirrors the legacy cursor behavior)
					if(!$worker->is_superuser) {
						$acl_results = \CerberusContexts::isWriteableByActor($context, $record_ids, $worker);

						if(is_array($acl_results)) {
							$acl_results = array_filter($acl_results, fn($bool) => $bool);
							$record_ids = array_keys($acl_results);
						}
					}

					if(!$record_ids) continue;

					$update = new \Model_ContextBulkUpdate();
					$update->job_id = $queue_job->id;
					$update->context = $context;
					$update->context_ids = $record_ids;
					$update->num_records = count($record_ids);
					$update->actions = $actions;
					$update->worker_id = $worker->id;
					$update->view_id = $queue_job->metadata['view_id'] ?? '';

					$dao_class::bulkUpdate($update);

					if(!empty($actions['comment'])) {
						$this->_processBulkCommentBatch($context, $record_ids, $actions['comment'], $worker);
					}

					// Track the post-ACL IDs we actually updated (for the job log audit trail)
					$touched_ids = array_merge($touched_ids, $record_ids);
				}

				$queue_service->reportSuccess(
					$queue_messages,
					sprintf('Updated %d %s', count($touched_ids), self::_recordNoun($context, count($touched_ids))),
					$touched_ids ? ['context' => $context, 'record_ids' => $touched_ids] : []
				);

			} catch(\Throwable $e) {
				DevblocksPlatform::logException($e);
				$queue_service->reportFailure($queue_messages, $e->getMessage());
			}

			$processed += count($queue_messages);
		}

		return $processed;
	}

	public function onBulkUpdateJobComplete(\Model_QueueJob $queue_job) : void {
		$context = $queue_job->metadata['context'] ?? '';
		$view_id = $queue_job->metadata['view_id'] ?? '';
		$record_count = intval($queue_job->metadata['record_count'] ?? 0);

		if(!$context || !$view_id || $record_count < 1)
			return;

		if(!($ctx = \Extension_DevblocksContext::get($context)))
			return;

		$string = sprintf("Bulk updated <b>%d %s</b> record%s.",
			$record_count,
			DevblocksPlatform::strLower($ctx->manifest->name),
			($record_count == 1 ? '' : 's')
		);

		\C4_AbstractView::marqueeAppend($view_id, $string);
	}

	private function _processBulkCommentBatch(string $context, array $record_ids, array $comment_params, \Model_Worker $worker) : void {
		$comment_text = $comment_params['message'] ?? '';

		if($comment_text === '')
			return;

		if(!$worker->hasPriv(sprintf("contexts.%s.comment", $context)))
			return;

		$is_markdown = !empty($comment_params['is_markdown']);
		$file_ids = array_map('intval', $comment_params['file_ids'] ?? []);

		foreach($record_ids as $record_id) {
			try {
				\DAO_Comment::createWithNotifications($context, (int)$record_id, $comment_text, $is_markdown, $worker, $file_ids);
			} catch(\Throwable $e) {
				DevblocksPlatform::logException($e);
			}
		}
	}
}