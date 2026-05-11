<?php
class AutomationTrigger_RecordChanged extends Extension_AutomationTrigger {
	const ID = 'cerb.trigger.record.changed';
	
	function renderConfig(Model_Automation $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('inputs', $this->getInputsMeta());
		$tpl->assign('outputs', $this->getOutputsMeta());
		$tpl->display('devblocks:cerberusweb.core::automations/triggers/config_inputs_outputs.tpl');
	}
	
	function validateConfig(array &$params, &$error=null) {
		return true;
	}
	
	function getInputsMeta() {
		return [
			[
				'key' => 'change_type',
				'notes' => 'The type of change: `created`, `updated`, `deleted`',
			],
			[
				'key' => 'record_',
				'notes' => 'The changed record dictionary. Supports key expansion.',
			],
			[
				'key' => 'was_record_',
				'notes' => 'The record dictionary before the changes. Supports key expansion.',
			],
			[
				'key' => 'actor_*',
				'notes' => 'The current actor record. Supports key expansion.',
			],
		];
	}
	
	function getOutputsMeta() {
		return [
			'return' => [
			]
		];
	}
	
	function getUsageMeta(string $automation_name): array {
		return [];
	}
	
	public function getEditorToolbarItems(array $toolbar): array {
		return $toolbar;
	}
	
	public function getAutocompleteSuggestions() : array {
		return [];
	}
	
	public static function processQueueEvents(Model_Queue $queue, int $stop_time, int $count_hint, ?Model_QueueJob $queue_job=null) : int {
		// Disable context checkpoints to prevent infinite loops of record.changed
		$was_events_enabled = CerberusContexts::isContextCheckpointsEnabled();
		CerberusContexts::setContextCheckpointsEnabled(false);
		
		$processed = 0;
		
		try {
			self::_processQueueEvents($queue, $stop_time, $count_hint, $queue_job);
			$processed++;
			
		} catch (Throwable $e) {
			DevblocksPlatform::logException($e);
		}
		
		CerberusContexts::setContextCheckpointsEnabled($was_events_enabled);
		
		return $processed;
	}
	
	private static function _processQueueEvents(Model_Queue $queue, int $stop_time, int $count_hint, ?Model_QueueJob $queue_job=null) : void {
		$event_handler = DevblocksPlatform::services()->ui()->eventHandler();
		$queue_service = DevblocksPlatform::services()->queue();
		
		$record_changed_events = DAO_AutomationEvent::getByName('record.changed');
		
		$consumer_id = null;
		$batch_size = 25;
		
		if (!($queue_messages = $queue_service->dequeue($queue->name, $batch_size, $consumer_id)))
			return;
		
		foreach ($queue_messages as $queue_message) {
			$event_state = $queue_message->message;
			
			// Trigger automations
			if ($record_changed_events) {
				$is_deleted = $event_state['is_deleted'] ?? false;
				$is_created = $event_state['is_created'] ?? false;
				
				$change_type = match (true) {
					$is_deleted => 'deleted',
					$is_created => 'created',
					default => 'updated',
				};
				
				$actor = $event_state['actor'] ?? [];
				$context = $event_state['context'] ?? null;
				
				if (!($context_ext = Extension_DevblocksContext::get($context)))
					continue;
				
				// Hydrate the models (because of legacy behaviors)
				
				if (!($model_class = $context_ext->getModelClass()))
					continue;
				
				if (!($old_model = new $model_class()))
					continue;
				
				foreach ($event_state['old_model'] as $k => $v) {
					if (property_exists($old_model, $k))
						$old_model->$k = $v;
				}
				
				$dict_old = DevblocksDictionaryDelegate::getDictionaryFromModel($old_model, $context);
				
				$new_model = clone $old_model;
				
				foreach (($event_state['delta'] ?? []) as $k => $v) {
					if (property_exists($new_model, $k))
						$new_model->$k = $v;
				}
				
				$dict_new = DevblocksDictionaryDelegate::getDictionaryFromModel($new_model, $context);
				
				$dict = DevblocksDictionaryDelegate::instance([
					'change_type' => $change_type,
					'is_new' => $is_created, // @deprecated
					'actor__context' => $actor['context'] ?? null,
					'actor_id' => $actor['context_id'] ?? null,
				]);
				$dict->mergeKeys('record_', $dict_new->getDictionary('', false));
				$dict->mergeKeys('was_record_', $dict_old->getDictionary('', false));
				
				$initial_state = $dict->getDictionary();
				
				$error = null;
				
				$handlers = $record_changed_events->getKata($dict, $error);
				
				if (false === $handlers && $error) {
					DevblocksPlatform::logError('[KATA] Invalid record.changed KATA: ' . $error);
					$handlers = [];
				}
				
				$event_handler->handleEach(
					AutomationTrigger_RecordChanged::ID,
					$handlers,
					$initial_state,
					$error,
					null,
					function(Model_TriggerEvent $behavior, array $handler) use ($dict, $old_model, $new_model, $actor) {
						return self::_behaviorCallback($behavior, $handler, $dict, $old_model, $new_model, $actor);
					}
				);
			}
			
			$queue_message->reportStatus(QueueMessageStatus::DONE);
		}
	}
	
	private static function _behaviorCallback(Model_TriggerEvent $behavior, array $handler, $dict, $old_model, $new_model, $actor) {
		$events = DevblocksPlatform::services()->event();
		
		$is_deleted = 'deleted' == $dict->get('change_type');
		
		DevblocksPlatform::services()->log()->info(sprintf(
			"Testing behavior `%s` [%d]; point:%s",
			$behavior->title,
			$behavior->id,
			$behavior->event_point
		));
		
		// Don't run behaviors on deleted records
		if ($is_deleted)
			return false;
		
		$context = $dict->get('record__context');
		
		$event_model = null;
		
		if ($behavior->event_point == Event_RecordChanged::ID) {
			$event_model_params = [
				'context' => $dict->get('record__context'),
				'new_model' => $new_model,
				'old_model' => $old_model,
				'actor' => $actor,
			];
			
			$event_model = new Model_DevblocksEvent(
				Event_RecordChanged::ID,
				$event_model_params
			);
			
		} else if (
			$behavior->event_point == Event_TaskCreatedByWorker::ID
			&& CerberusContexts::isSameContext($context, CerberusContexts::CONTEXT_TASK)
		) {
			$event_model = new Model_DevblocksEvent(
				Event_TaskCreatedByWorker::ID,
				[
					'context_id' => $dict->get('record_id'),
					'worker_id' => null,
				]
			);
			
		} else if (
			$behavior->event_point == Event_CommentCreatedByWorker::ID
			&& CerberusContexts::isSameContext($context, CerberusContexts::CONTEXT_COMMENT)
		) {
			$event_model = new Model_DevblocksEvent(
				Event_CommentCreatedByWorker::ID,
				[
					'context_id' => $dict->get('record_id'),
				]
			);
			
		} else if (
			$behavior->event_point == Event_MailAssignedInGroup::ID
			&& CerberusContexts::isSameContext($context, CerberusContexts::CONTEXT_TICKET)
		) {
			$behavior_bot = $behavior->getBot();
			
			if (
				$behavior_bot->owner_context != CerberusContexts::CONTEXT_GROUP
				|| $behavior_bot->owner_context_id != $dict->get('record_group_id')
			)
				return false;
			
			// If the owner changed
			if ($dict->get('was_record_owner_id') != $dict->get('record_owner_id')) {
				$event_model = new Model_DevblocksEvent(
					Event_MailAssignedInGroup::ID,
					[
						'context_id' => $dict->get('record_id'),
					]
				);
			}
			
		} else if (
			!APP_OPT_GROUP_BEHAVIOR_TRIGGERS
			&& $behavior->event_point == Event_MailMovedToGroup::ID
			&& CerberusContexts::isSameContext($context, CerberusContexts::CONTEXT_TICKET)
		) {
			$behavior_bot = $behavior->getBot();
			
			if (
				$behavior_bot->owner_context != CerberusContexts::CONTEXT_GROUP
				|| $behavior_bot->owner_context_id != $dict->get('record_group_id')
			)
				return false;
			
			// If the ticket moved group/bucket
			if (
				$dict->get('was_record_group_id') != $dict->get('record_group_id')
				|| $dict->get('was_record_bucket_id') != $dict->get('record_bucket_id')
			) {
				$event_model = new Model_DevblocksEvent(
					Event_MailMovedToGroup::ID,
					[
						'context_id' => $dict->get('record_id'),
					]
				);
			}
			
		} else if (
			$behavior->event_point == Event_MailClosedInGroup::ID
			&& CerberusContexts::isSameContext($context, CerberusContexts::CONTEXT_TICKET)
		) {
			$behavior_bot = $behavior->getBot();
			
			if (
				$behavior_bot->owner_context != CerberusContexts::CONTEXT_GROUP
				|| $behavior_bot->owner_context_id != $dict->get('record_group_id')
			)
				return false;
			
			// If the status went closed
			if (
				$dict->get('record_status_id') == Model_Ticket::STATUS_CLOSED
				&& $dict->get('was_record_status_id') != $dict->get('record_status_id')
			) {
				$event_model = new Model_DevblocksEvent(
					Event_MailClosedInGroup::ID,
					[
						'context_id' => $dict->get('record_id'),
					]
				);
			}
			
		} else if (
			$behavior->event_point == Event_CommentOnTicketInGroup::ID
			&& CerberusContexts::isSameContext($context, CerberusContexts::CONTEXT_COMMENT)
		) {
			$behavior_bot = $behavior->getBot();
			
			if (
				$behavior_bot->owner_context != CerberusContexts::CONTEXT_GROUP
				|| $behavior_bot->owner_context_id != ($dict->get('record_target_group_id'))
			)
				return false;
			
			$event_model = new Model_DevblocksEvent(
				Event_CommentOnTicketInGroup::ID,
				[
					'context_id' => $dict->get('record_target_id'),
					'comment_id' => $dict->get('record_id'),
				]
			);
			
		} else {
			return false;
		}
		
		if (!$event_model)
			return false;
		
		if ($behavior->is_disabled || !($event = $behavior->getEvent()))
			return false;
		
		$event_model->params['_whisper']['_trigger_id'] = [$behavior->id];
		
		$event->setEvent($event_model, $behavior);
		
		$values = $event->getValues();
		
		// Inputs
		
		if (array_key_exists('inputs', $handler['data'])) {
			foreach ($handler['data']['inputs'] as $k => $v) {
				if (DevblocksPlatform::strStartsWith($k, 'var_'))
					$values[$k] = $v;
			}
		}
		
		$event->setValues($values);

		// Run behavior
		return $events->trigger($event_model);
	}
}