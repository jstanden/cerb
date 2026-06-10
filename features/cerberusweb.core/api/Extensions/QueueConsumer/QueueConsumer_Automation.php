<?php
namespace Cerb\Extensions\QueueConsumer;

use AutomationTrigger_QueueConsumer;
use Cerb\Extensions\Extension_QueueConsumer;
use CerberusContexts;
use DAO_AutomationLog;
use DAO_Queue;
use DAO_QueueMessage;
use DevblocksDictionaryDelegate;
use DevblocksPlatform;
use Extension_AutomationTrigger;
use Model_Queue;
use Model_QueueJob;

class QueueConsumer_Automation extends Extension_QueueConsumer {
	const ID = 'cerb.queue.consumer.automation';

	function renderConfig(Model_Queue $model): void {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('model', $model);
		$tpl->assign('active_worker', \CerberusApplication::getActiveWorker());

		$trigger_ext = Extension_AutomationTrigger::get(AutomationTrigger_QueueConsumer::ID);
		$tpl->assign('trigger_ext', $trigger_ext);
		$tpl->assign('trigger_inputs', $trigger_ext ? $trigger_ext->getEventPlaceholders() : []);

		$tpl->display('devblocks:cerberusweb.core::records/types/queue/automation/config.tpl');
	}

	function invokeConfig($config_action, Model_Queue $model): void {
	}

	function saveConfig(array $fields, $id, &$error = null): bool {
		$params = $fields['extension_params'] ?? [];

		if(!is_array($params))
			$params = [];

		$automations_kata = (string) ($params['automations_kata'] ?? '');
		$batch_size = max(1, min(1000, intval($params['batch_size'] ?? 1)));

		if($automations_kata !== '') {
			$kata_service = DevblocksPlatform::services()->kata();
			$kata_error = null;

			if(false === $kata_service->parse($automations_kata, $kata_error)) {
				$error = sprintf("Invalid automations KATA: %s", $kata_error);
				return false;
			}
		}

		$extension_params = [
			'automations_kata' => $automations_kata,
			'batch_size' => $batch_size,
		];

		DAO_Queue::update($id, [
			DAO_Queue::EXTENSION_PARAMS_JSON => json_encode($extension_params),
		]);

		return true;
	}

	public function processQueueMessages(Model_Queue $queue, int $stop_time, int $count_hint, ?Model_QueueJob $queue_job = null): int {
		$automations_kata = $queue->extension_params['automations_kata'] ?? '';
		$batch_size = max(1, intval($queue->extension_params['batch_size'] ?? 1));

		if($automations_kata === '')
			return 0;

		$event_handler = DevblocksPlatform::services()->ui()->eventHandler();
		$processed = 0;
		$job_id = $queue_job?->id;
		
		// A common baseline dictionary
		$base_dict = DevblocksDictionaryDelegate::instance([]);
		$base_dict->mergeKeys('queue_', DevblocksDictionaryDelegate::getDictionaryFromModel($queue, CerberusContexts::CONTEXT_QUEUE));
		if($queue_job) $base_dict->mergeKeys('queue_job_', DevblocksDictionaryDelegate::getDictionaryFromModel($queue_job, CerberusContexts::CONTEXT_QUEUE_JOB));
		$base_dict = $base_dict->getDictionary(null, false);
		
		while($stop_time > time()) {
			$consumer_id = null;
			$messages = DAO_QueueMessage::dequeue($queue, $batch_size, $consumer_id, $job_id);
			
			if(!$messages)
				break;

			$uuids = array_map(fn($m) => $m->uuid, $messages);
			
			$dict = DevblocksDictionaryDelegate::instance($base_dict);
			
			$dict->set('messages', array_map(fn($m) => [
				'uuid' => $m->uuid,
				'message' => $m->message,
				'available_at' => $m->available_at,
				'job_id' => $m->job_id,
			], $messages));

			$error = null;
			$handlers = $event_handler->parse($automations_kata, $dict, $error);

			if(!$handlers) {
				DAO_QueueMessage::reportFailure($messages);
				$this->_logError($queue, $error ? sprintf("KATA error: %s", $error) : 'No enabled handler matched.');
				continue;
			}

			$results = $event_handler->handleOnce(
				AutomationTrigger_QueueConsumer::ID,
				$handlers,
				$dict->getDictionary(null, false),
				$error
			);
			
			$exit_code = $results?->get('__exit');

			if(!$results || $exit_code === 'error') {
				DAO_QueueMessage::reportFailure($messages);
				$err_msg = $results?->getKeyPath('__error.message') ?? $error ?? 'Unknown error';
				$this->_logError($queue, $err_msg);
			} else {
				DAO_QueueMessage::reportSuccess($uuids);
				$processed += count($messages);
			}
		}

		return $processed;
	}

	private function _logError(Model_Queue $queue, string $message): void {
		if(!class_exists('DAO_AutomationLog'))
			return;

		DAO_AutomationLog::create([
			DAO_AutomationLog::LOG_MESSAGE => sprintf("[queue %s] %s", $queue->name, $message),
			DAO_AutomationLog::LOG_LEVEL => 3,
			DAO_AutomationLog::CREATED_AT => time(),
			DAO_AutomationLog::AUTOMATION_NAME => '',
			DAO_AutomationLog::AUTOMATION_NODE => '',
		]);
	}
}
