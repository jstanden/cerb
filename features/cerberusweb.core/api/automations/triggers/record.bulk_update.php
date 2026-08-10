<?php

class AutomationTrigger_RecordBulkUpdate extends Extension_AutomationTrigger {
	const ID = 'cerb.trigger.record.bulkUpdate';
	const EVENT_NAME = 'record.bulkUpdate';

	function renderConfig(Model_Automation $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('inputs', $this->getInputsMeta());
		$tpl->assign('outputs', $this->getOutputsMeta());
		$tpl->display('devblocks:cerberusweb.core::automations/triggers/config_inputs_outputs.tpl');
	}

	function validateConfig(array &$params, &$error=null) {
		return true;
	}

	function getInputsMeta() : array {
		return [
			[
				'key' => 'state',
				'notes' => 'One of `start`, `batch`, or `end`. `start` runs once before the job is enqueued (can abort with `__exit:error`). `batch` runs per batch of record IDs. `end` runs after the job completes.',
			],
			[
				'key' => 'record_type',
				'notes' => 'The context string of the records being updated (e.g. `cerb.contexts.ticket`).',
			],
			[
				'key' => 'record_ids',
				'type' => 'array',
				'notes' => 'The record IDs in this batch (empty on `start`/`end`).',
			],
			[
				'key' => 'count',
				'type' => 'number',
				'notes' => 'The number of records in this batch, or the job total on `start`/`end`.',
			],
			[
				'key' => 'worker_*',
				'type' => 'record',
				'params' => [
					'record_type' => 'worker',
				],
				'notes' => 'The [worker](https://cerb.ai/docs/records/types/worker/#dictionary-placeholders) who started the bulk job. Supports key expansion.',
			],
			[
				'key' => 'worklist_id',
				'notes' => 'The originating worklist ID.',
			],
			[
				'key' => 'worklist_query',
				'notes' => 'The query that produced the bulk set.',
			],
			[
				'key' => 'queue_job_*',
				'type' => 'record',
				'params' => [
					'record_type' => 'queue_job',
				],
				'notes' => 'The [queue job](https://cerb.ai/docs/records/types/queue_job/) for this bulk run. Only present on `batch` and `end`. Supports key expansion.',
			],
			[
				'key' => 'inputs.*',
				'type' => 'dict',
				'notes' => 'The public inputs the worker configured for this attachment in the bulk popup. Access individual values as `inputs.<key>` (e.g. `inputs.color`).',
			],
			[
				'key' => 'setup.*',
				'type' => 'dict',
				'notes' => 'On `batch` and `end`, the `setup:` dict returned by the `start` run (its setup/teardown context). Access individual values as `setup.<key>` (e.g. `setup.queue_job_id`). Empty on `start`.',
			],
		];
	}

	function getOutputsMeta() : array {
		return [
			'return' => [
				[
					'key' => 'error',
					'notes' => 'On `state == start`, exit with `error` to abort the bulk job; the error message is shown to the worker. On `batch`/`end`, an `error` exit is logged.',
				],
				[
					'key' => 'setup',
					'type' => 'dict',
					'notes' => 'On `state == start`, return a `setup:` dict to cascade setup/teardown context to every `batch` and `end` run, accessed there as `setup.<key>` (e.g. `return:` a `setup:` with a queue job ID on `start`, then read `setup.queue_job_id` on `batch`/`end`).',
				],
			],
		];
	}

	function getUsageMeta(string $automation_name): array {
		$results = [];

		$linked_listener_ids = [];

		$listeners = DAO_AutomationEventListener::getByEvent(self::EVENT_NAME, true);

		foreach($listeners as $listener) {
			if(empty($listener->event_kata))
				continue;

			$tokens = DevblocksPlatform::services()->string()->tokenize($listener->event_kata, false);

			if(in_array($automation_name, $tokens))
				$linked_listener_ids[] = $listener->id;
		}

		if($linked_listener_ids)
			$results['automation_event_listener'] = $linked_listener_ids;

		return $results;
	}

	public function getEditorToolbarItems(array $toolbar): array {
		return $toolbar;
	}

	public function getAutocompleteSuggestions() : array {
		return [
			'*' => [
				'(.*):return:' => [
					'error: Error message',
					'setup:',
				],
			]
		];
	}
}
