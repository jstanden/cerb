<?php

use Cerb\Extensions\QueueConsumer\QueueConsumer_Automation;

class AutomationTrigger_QueueConsumer extends Extension_AutomationTrigger {
	const ID = 'cerb.trigger.queue.consumer';

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
				'key' => 'queue_*',
				'type' => 'record',
				'params' => [
					'record_type' => 'queue',
				],
				'notes' => 'The [queue](https://cerb.ai/docs/records/types/queue/#dictionary-placeholders) being consumed. Supports key expansion.',
			],
			[
				'key' => 'queue_job_*',
				'type' => 'record',
				'params' => [
					'record_type' => 'queue_job',
				],
				'notes' => 'The [queue job](https://cerb.ai/docs/records/types/queue_job/) that owns this batch, if any. Supports key expansion.',
			],
			[
				'key' => 'messages',
				'type' => 'array',
				'params' => [],
				'notes' => 'An array of `{uuid, message, available_at, job_id}` dicts for the batch.',
			],
		];
	}

	function getOutputsMeta() : array {
		return [];
	}

	function getUsageMeta(string $automation_name): array {
		$results = [];

		$queues = DAO_Queue::getAll();

		$linked_queue_ids = [];

		foreach($queues as $queue) {
			if($queue->extension_id != QueueConsumer_Automation::ID)
				continue;

			$kata = $queue->extension_params['automations_kata'] ?? '';

			if($kata === '')
				continue;

			$tokens = DevblocksPlatform::services()->string()->tokenize($kata, false);

			if(in_array($automation_name, $tokens))
				$linked_queue_ids[] = $queue->id;
		}

		if($linked_queue_ids)
			$results['queue'] = $linked_queue_ids;

		return $results;
	}

	public function getEditorToolbarItems(array $toolbar): array {
		return $toolbar;
	}

	public function getAutocompleteSuggestions() : array {
		return [];
	}
}
