<?php
namespace Cerb\Records;

use AutomationTrigger_RecordBulkUpdate;
use C4_AbstractView;
use CerberusContexts;
use DAO_Automation;
use DAO_AutomationEvent;
use DAO_AutomationLog;
use DAO_Comment;
use DAO_Queue;
use DAO_QueueJob;
use DAO_Worker;
use DevblocksDictionaryDelegate;
use DevblocksPlatform;
use Extension_DevblocksContext;
use Model_ContextBulkUpdate;
use Model_Queue;
use Model_QueueJob;
use Model_Worker;
use QueueJobStatus;

class BulkUpdate {
	/**
	 * Create a bulk-update queue_job from the worklist filter and the parsed `$do` action set.
	 * Returns the created Model_QueueJob (with $queue_job->id populated) or null on failure.
	 */
	public static function createJob(C4_AbstractView $view, array $do, int $worker_id, int $batch_size = 100) : ?Model_QueueJob {
		$db = DevblocksPlatform::services()->database();

		if(empty($do))
			return null;

		if($batch_size < 1) $batch_size = 100;

		if(!($context_ext = Extension_DevblocksContext::getByViewClass(get_class($view), true)))
			return null;

		if(!($dao_class = $context_ext->getDaoClass()))
			return null;

		if(!($search_class = $context_ext->getSearchClass()))
			return null;

		if(!($queue = DAO_Queue::getByName('cerb.records.bulk_update')))
			return null;

		$query_parts = $dao_class::getSearchQueryComponents([], $view->getParams());

		if(empty($query_parts['primary_table']))
			return null;

		$primary_key = $search_class::getPrimaryKey();

		$count_sql = "SELECT COUNT(1) " . $query_parts['join'] . $query_parts['where'];
		$record_count = intval($db->GetOneReader($count_sql));

		if(!$record_count)
			return null;

		$aliases = Extension_DevblocksContext::getAliasesForContext($context_ext->manifest);

		$queue_job = new Model_QueueJob();
		$queue_job->queue_id = $queue->id;
		$queue_job->name = sprintf('Bulk update %s',
			$aliases['plural'] ?? $aliases['uri'] ?? $context_ext->id
		);
		$queue_job->status_id = QueueJobStatus::RUNNING->value;
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
		$queue_job = DAO_QueueJob::create($queue_job);

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

	public static function processQueue(Model_Queue $queue, int $stop_time, int $count_hint, ?Model_QueueJob $queue_job=null) : int {
		$queue_service = DevblocksPlatform::services()->queue();

		if($queue_job) {
			$job_id = $queue_job->id;

		} else {
			if(!($job_stats = DAO_QueueJob::getAvailableMessages($queue)))
				return 0;

			shuffle($job_stats);

			$job_id = $job_stats[array_key_first($job_stats)]['job_id'] ?? null;

			if(!($queue_job = DAO_QueueJob::get($job_id))) {
				return 0;
			}
		}

		$context = $queue_job->metadata['context'] ?? '';
		$actions = $queue_job->metadata['actions'] ?? [];
		$worker = $queue_job->worker_id ? DAO_Worker::get($queue_job->worker_id) : null;

		// Validate prerequisites once, outside the loop. If anything's wrong we
		// still need to surface failure on at least one message so the job
		// doesn't appear to make no progress; dequeue one to report against.
		if(!$context || !$actions || !$worker
			|| !($context_ext = Extension_DevblocksContext::get($context))
			|| !($dao_class = $context_ext->getDaoClass())
			|| !method_exists($dao_class, 'bulkUpdate')
		) {
			$consumer_id = null;
			if(!($queue_messages = $queue_service->dequeue($queue->name, 1, $consumer_id, $job_id)))
				return 0;
			$queue_service->reportFailure($queue_messages, 'Missing context, actions, worker, or unsupported context');
			return count($queue_messages);
		}

		// Resolve the authoritative handlers once for this job (context + worker are job-level
		// constants). Stored automations reference a handler by name; anything not found here
		// is an unknown/disabled binding and is skipped per batch below.
		$bulk_handlers = self::getHandlersByName($context, $worker, [
			'state' => 'batch',
			'worklist_id' => $queue_job->metadata['view_id'] ?? '',
			'worklist_query' => $queue_job->metadata['query'] ?? '',
		]);

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
						$acl_results = CerberusContexts::isWriteableByActor($context, $record_ids, $worker);

						if(is_array($acl_results)) {
							$acl_results = array_filter($acl_results, fn($bool) => $bool);
							$record_ids = array_keys($acl_results);
						}
					}

					if(!$record_ids) continue;

					$update = new Model_ContextBulkUpdate();
					$update->job_id = $queue_job->id;
					$update->context = $context;
					$update->context_ids = $record_ids;
					$update->num_records = count($record_ids);
					$update->actions = $actions;
					$update->worker_id = $worker->id;
					$update->view_id = $queue_job->metadata['view_id'] ?? '';

					$dao_class::bulkUpdate($update);

					if(!empty($actions['comment'])) {
						self::_processCommentBatch($context, $record_ids, $actions['comment'], $worker);
					}

					// Track the post-ACL IDs we actually updated (for the job log audit trail)
					$touched_ids = array_merge($touched_ids, $record_ids);

					$automations = $actions['automations'] ?? [];

					if(is_array($automations) && $automations) {
						$batch_state = [
							'state' => 'batch',
							'record_type' => $context,
							'record_ids' => $record_ids,
							'count' => count($record_ids),
							'worklist_id' => $queue_job->metadata['view_id'] ?? '',
							'worklist_query' => $queue_job->metadata['query'] ?? '',
						];

						foreach($automations as $automation) {
							$handler_name = (string) ($automation['handler'] ?? '');
							$handler = $bulk_handlers[$handler_name] ?? null;

							if(!$handler) {
								self::_logAutomationError($queue_job, $automation, sprintf('Unknown or unavailable bulk-update handler: %s', $handler_name ?: '(none)'));
								continue;
							}

							// Cascade the start run's `return:` output (captured at submit) into this batch.
							$state = $batch_state;
							$state['setup'] = (array) ($automation['setup'] ?? []);

							$batch_error = null;
							$batch_results = self::invokeAutomation($handler, (array) ($automation['inputs'] ?? []), $state, $queue_job, $worker, $batch_error);

							if(!$batch_results || $batch_results->get('__exit') === 'error') {
								$err_msg = $batch_results?->getKeyPath('__error.message') ?? $batch_error ?? 'Unknown error';
								self::_logAutomationError($queue_job, $automation, $err_msg);
							}
						}
					}
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
	
	/**
	 * Run state=start synchronously for each automation. Returns false if any
	 * automation exits 'error' (and sets $error to its message); true otherwise.
	 * Callers should abort the bulk submit on false.
	 *
	 * Each start run's returned `setup:` dict is captured onto its automation entry as `setup`
	 * (by reference), so it persists into the job metadata and cascades to every batch/end
	 * run of the same automation as a `setup.<key>` dict.
	 */
	public static function onJobStart(array &$automations, string $context, ?C4_AbstractView $view, ?Model_Worker $worker, ?string &$error = null) : bool {
		if(!$automations)
			return true;

		$worklist_query = ($view && method_exists($view, 'getParamsQuery')) ? (string) $view->getParamsQuery() : '';

		$bulk_handlers = self::getHandlersByName($context, $worker, [
			'state' => 'start',
			'worklist_id' => $view->id ?? '',
			'worklist_query' => $worklist_query,
		]);

		$start_state = [
			'state' => 'start',
			'record_type' => $context,
			'record_ids' => [],
			'count' => 0,
			'worklist_id' => $view->id ?? '',
			'worklist_query' => $worklist_query,
		];

		foreach($automations as $idx => $automation) {
			$handler = $bulk_handlers[(string) ($automation['handler'] ?? '')] ?? null;

			if(!$handler)
				continue;

			$start_error = null;
			$start_results = self::invokeAutomation($handler, (array) ($automation['inputs'] ?? []), $start_state, null, $worker, $start_error);

			if($start_results && $start_results->get('__exit') === 'error') {
				$error = $start_results->getKeyPath('__error.message') ?? $start_error ?? 'Aborted by automation';
				return false;
			}

			// Cascade this start run's returned `setup:` dict to its batch/end runs as `setup`.
			$automations[$idx]['setup'] = (array) ($start_results?->getKeyPath('__return.setup') ?? []);
		}

		return true;
	}

	public static function onJobComplete(Model_QueueJob $queue_job) : void {
		$context = $queue_job->metadata['context'] ?? '';
		$view_id = $queue_job->metadata['view_id'] ?? '';
		$record_count = intval($queue_job->metadata['record_count'] ?? 0);

		if(!$context || !$view_id || $record_count < 1)
			return;

		if(!($ctx = Extension_DevblocksContext::get($context)))
			return;

		$string = sprintf("Bulk updated <b>%d %s</b> record%s.",
			$record_count,
			DevblocksPlatform::strLower($ctx->manifest->name),
			($record_count == 1 ? '' : 's')
		);

		C4_AbstractView::marqueeAppend($view_id, $string);

		$automations = $queue_job->metadata['actions']['automations'] ?? [];

		if(is_array($automations) && $automations) {
			$worker = $queue_job->worker_id ? DAO_Worker::get($queue_job->worker_id) : null;

			$bulk_handlers = self::getHandlersByName($context, $worker, [
				'state' => 'end',
				'count' => $record_count,
				'worklist_id' => $view_id,
				'worklist_query' => $queue_job->metadata['query'] ?? '',
			]);

			$end_state = [
				'state' => 'end',
				'record_type' => $context,
				'record_ids' => [],
				'count' => $record_count,
				'worklist_id' => $view_id,
				'worklist_query' => $queue_job->metadata['query'] ?? '',
			];

			foreach($automations as $automation) {
				$handler_name = (string) ($automation['handler'] ?? '');
				$handler = $bulk_handlers[$handler_name] ?? null;

				if(!$handler) {
					self::_logAutomationError($queue_job, $automation, sprintf('Unknown or unavailable bulk-update handler: %s', $handler_name ?: '(none)'));
					continue;
				}

				// Cascade the start run's `return:` output (captured at submit) into the end run.
				$state = $end_state;
				$state['setup'] = (array) ($automation['setup'] ?? []);

				$end_error = null;
				$end_results = self::invokeAutomation($handler, (array) ($automation['inputs'] ?? []), $state, $queue_job, $worker, $end_error);

				if(!$end_results || $end_results->get('__exit') === 'error') {
					$err_msg = $end_results?->getKeyPath('__error.message') ?? $end_error ?? 'Unknown error';
					self::_logAutomationError($queue_job, $automation, $err_msg);
				}
			}
		}
	}

	/**
	 * Resolve the authoritative set of `record.bulkUpdate` event-listener handlers for the
	 * given actor/context, keyed by handler name (the `automation/<name>` label). Handlers
	 * that fail their `enabled:`/`disabled:` gate are dropped during parsing, so a handler
	 * appearing here is one the worker is actually allowed to run. This is the single source
	 * of truth used by both the render path and the run-time phases — never trust a uri or
	 * input set posted by the form.
	 */
	public static function getHandlersByName(string $context, ?Model_Worker $worker = null, array $extra_state = []) : array {
		$state = array_merge([
			'state' => 'menu',
			'record_type' => $context,
			'record_ids' => [],
			'count' => 0,
			'inputs' => [],
			'worklist_id' => '',
			'worklist_query' => '',
		], $extra_state);

		$dict = DevblocksDictionaryDelegate::instance($state);

		if($worker) {
			$dict->mergeKeys('worker_', DevblocksDictionaryDelegate::getDictionaryFromModel($worker, CerberusContexts::CONTEXT_WORKER));
		}

		$handlers = DAO_AutomationEvent::getKataByName(AutomationTrigger_RecordBulkUpdate::EVENT_NAME, $dict);

		$results = [];

		if(is_array($handlers)) {
			foreach($handlers as $handler) {
				if(($handler['type'] ?? '') !== 'automation')
					continue;

				$key = (string) ($handler['key'] ?? '');
				$uri = (string) ($handler['data']['uri'] ?? '');

				if($key === '' || $uri === '')
					continue;

				$results[$key] = $handler;
			}
		}

		return $results;
	}

	public static function getMenuItems(string $context, string $view_id = '', string $query = '', ?Model_Worker $worker = null, array $selected_record_ids = [], array $visible_record_ids = []) : array {
		$handlers = self::getHandlersByName($context, $worker, [
			'worklist_id' => $view_id,
			'worklist_query' => $query,
			'selected_record_ids' => array_map('intval', $selected_record_ids),
			'visible_record_ids' => array_map('intval', $visible_record_ids),
		]);

		$items = [];

		foreach($handlers as $key => $handler) {
			$uri = (string) ($handler['data']['uri'] ?? '');

			$automation = DAO_Automation::getByUri($uri, [AutomationTrigger_RecordBulkUpdate::ID]);

			if(!$automation)
				continue;

			$items[] = [
				'key' => $key,
				'context' => $context,
				'label' => (string) ($handler['data']['name'] ?? $automation->name),
				'description' => (string) ($handler['data']['description'] ?? $automation->description ?? ''),
			];
		}

		return $items;
	}

	/**
	 * Normalize $_POST['automations'] (or anything array-shaped) to [{handler, inputs}, ...].
	 * The form supplies only a handler name (the `automation/<name>` label) and the worker's
	 * public input values. We resolve the authoritative handler set for this actor/context and
	 * drop any entry whose handler isn't a predefined, enabled binding — a worker can never run
	 * an arbitrary automation, only one bound to the event. The canonical uri and any override
	 * inputs are resolved server-side from the handler at run time, never trusted from the post.
	 */
	public static function parseAutomationsFromPost(mixed $post_input, string $context, ?Model_Worker $worker = null) : array {
		$automations_post = DevblocksPlatform::importGPC($post_input, 'array', []);

		$handlers = self::getHandlersByName($context, $worker);

		$automations = [];

		foreach($automations_post as $automation) {
			$handler_name = (string) ($automation['handler'] ?? '');

			if($handler_name === '' || !array_key_exists($handler_name, $handlers))
				continue;

			$automations[] = [
				'handler' => $handler_name,
				'inputs' => (array) ($automation['inputs'] ?? []),
			];
		}

		return $automations;
	}

	/**
	 * Run one resolved handler for a single phase (start/batch/end). The handler is the
	 * authoritative binding from getHandlersByName(); $public_inputs are the worker's values
	 * from the popup. Handler-configured `data.inputs` are applied as overrides on top of the
	 * worker's inputs (overrides win), mirroring DevblocksUiEventHandler::handleOnce(). The
	 * real handler is passed straight through — there's no synthetic handler and no uri taken
	 * from the post.
	 */
	public static function invokeAutomation(array $handler, array $public_inputs, array $initial_state, ?Model_QueueJob $queue_job = null, ?Model_Worker $worker = null, ?string &$error = null) : ?DevblocksDictionaryDelegate {
		if((string) ($handler['data']['uri'] ?? '') === '')
			return null;

		$override_inputs = (array) ($handler['data']['inputs'] ?? []);

		// Overrides win: a worker can't change an input the handler fixed.
		$handler['data']['inputs'] = array_merge($public_inputs, $override_inputs);

		$dict = DevblocksDictionaryDelegate::instance($initial_state);

		if($worker) {
			$dict->mergeKeys('worker_', DevblocksDictionaryDelegate::getDictionaryFromModel($worker, CerberusContexts::CONTEXT_WORKER));
		}

		if($queue_job) {
			$dict->mergeKeys('queue_job_', DevblocksDictionaryDelegate::getDictionaryFromModel($queue_job, CerberusContexts::CONTEXT_QUEUE_JOB));
		}

		$event_handler = DevblocksPlatform::services()->ui()->eventHandler();

		return $event_handler->handleOnce(
			AutomationTrigger_RecordBulkUpdate::ID,
			[$handler],
			$dict->getDictionary(null, false),
			$error
		);
	}

	private static function _logAutomationError(Model_QueueJob $queue_job, array $attachment, string $message) : void {
		if(!class_exists('\DAO_AutomationLog'))
			return;

		DAO_AutomationLog::create([
			DAO_AutomationLog::LOG_MESSAGE => sprintf("[bulk_update job=%d %s] %s", $queue_job->id, (string)($attachment['handler'] ?? ''), $message),
			DAO_AutomationLog::LOG_LEVEL => 3,
			DAO_AutomationLog::CREATED_AT => time(),
			DAO_AutomationLog::AUTOMATION_NAME => (string)($attachment['handler'] ?? ''),
			DAO_AutomationLog::AUTOMATION_NODE => '',
		]);
	}

	private static function _processCommentBatch(string $context, array $record_ids, array $comment_params, Model_Worker $worker) : void {
		$comment_text = $comment_params['message'] ?? '';

		if($comment_text === '')
			return;

		if(!$worker->hasPriv(sprintf("contexts.%s.comment", $context)))
			return;

		$is_markdown = !empty($comment_params['is_markdown']);
		$file_ids = array_map('intval', $comment_params['file_ids'] ?? []);

		foreach($record_ids as $record_id) {
			try {
				DAO_Comment::createWithNotifications($context, (int)$record_id, $comment_text, $is_markdown, $worker, $file_ids);
			} catch(\Throwable $e) {
				DevblocksPlatform::logException($e);
			}
		}
	}

	/**
	 * Resolve a context's singular/plural alias for use in job-log messages
	 * ("ticket"/"tickets", "custom record"/"custom records"). Falls back to a
	 * generic "record"/"records" if the context can't be resolved.
	 */
	private static function _recordNoun(string $context, int $count) : string {
		$context_ext = $context ? Extension_DevblocksContext::get($context) : null;
		$aliases = $context_ext
			? Extension_DevblocksContext::getAliasesForContext($context_ext->manifest)
			: null;

		if($count === 1)
			return $aliases['singular'] ?? '' ?: 'record';

		return $aliases['plural'] ?? '' ?: 'records';
	}
	
	public static function handleBulkPost(array $do, string $context, ?C4_AbstractView $view, ?Model_Worker $active_worker = null, ?string &$error = null) : array|false {
		$bulk_automations = self::parseAutomationsFromPost($_POST['automations'] ?? null, $context, $active_worker);
		
		if($bulk_automations) {
			$start_error = null;
			
			if(!self::onJobStart($bulk_automations, $context, $view, $active_worker, $start_error)) {
				$error = $start_error ?: 'Aborted by automation';
				return false;
			}
			
			$do['automations'] = $bulk_automations;
		}
		
		return $do;
	}
}
