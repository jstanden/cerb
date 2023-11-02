<?php
class AutomationTrigger_MailMoved extends Extension_AutomationTrigger {
	const ID = 'cerb.trigger.mail.moved';
	
	function renderConfig(Model_Automation $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('inputs', $this->getInputsMeta());
		$tpl->assign('outputs', $this->getOutputsMeta());
		
		try {
			$tpl->display('devblocks:cerberusweb.core::automations/triggers/config_inputs_outputs.tpl');
		} catch (Exception | SmartyException $e) {
			error_log($e->getMessage());
		}
	}
	
	function validateConfig(array &$params, &$error=null) : bool {
		return true;
	}
	
	function getInputsMeta() : array {
		return [
			[
				'key' => 'ticket_*',
				'type' => 'record',
				'params' => [
					'record_type' => 'ticket',
				],
				'notes' => 'The new state of the moved [ticket](https://cerb.ai/docs/records/types/ticket/#dictionary-placeholders). Supports key expansion.',
			],
			[
				'key' => 'was_group_*',
				'type' => 'record',
				'params' => [
					'record_type' => 'group',
				],
				'notes' => 'The [group](https://cerb.ai/docs/records/types/group/#dictionary-placeholders) record before the ticket was moved. Supports key expansion. Empty if ticket was just created.',
			],
			[
				'key' => 'was_bucket_*',
				'type' => 'record',
				'params' => [
					'record_type' => 'bucket',
				],
				'notes' => 'The [bucket](https://cerb.ai/docs/records/types/bucket/#dictionary-placeholders) record before the ticket was moved. Supports key expansion. Empty if ticket was just created.',
			],
			[
				'key' => 'actor_*',
				'type' => 'record',
				'notes' => 'The current actor [record](https://cerb.ai/docs/records/types/). Supports key expansion. `actor__type` is the record type alias (e.g. `automation`, `worker`).',
			],
		];
	}
	
	function getOutputsMeta() : array {
		return [
			'return' => [],
		];
	}
	
	public function getEditorToolbarItems(array $toolbar): array {
		return $toolbar;
	}
	
	public function getEventToolbarItems(array $toolbar): array {
		return $toolbar;
	}
	
	public function getAutocompleteSuggestions() : array {
		return [];
	}
	
	public static function trigger(Model_Ticket $ticket) {
		$was_ticket = CerberusContexts::getCheckpoints(CerberusContexts::CONTEXT_TICKET, [$ticket->id]);
		$diffs = CerberusContexts::getCheckpointDiffs(CerberusContexts::CONTEXT_TICKET, [$ticket->id]);
		
		if(is_array($was_ticket) && array_key_exists($ticket->id, $was_ticket)) {
			$was_ticket = $was_ticket[$ticket->id];
			$diffs = array_filter($diffs, fn($diff) => array_intersect(['group_id','bucket_id'], array_keys($diff['fields'])));
			
			// Ignore the last group/bucket move (it's the current location)
			array_pop($diffs);
			
			foreach($diffs as $diff) {
				if(array_key_exists('group_id', $diff['fields']))
					$was_ticket['group_id'] = $diff['fields']['group_id'];
				if(array_key_exists('bucket_id', $diff['fields']))
					$was_ticket['bucket_id'] = $diff['fields']['bucket_id'];
			}
			
		} else {
			$was_ticket = new Model_Ticket();
		}
		
		$dict = DevblocksDictionaryDelegate::instance([]);
		$dict->mergeKeys('ticket_', DevblocksDictionaryDelegate::getDictionaryFromModel($ticket, CerberusContexts::CONTEXT_TICKET));
		$dict->mergeKeys('was_group_', DevblocksDictionaryDelegate::getDictionaryFromModel(DAO_Group::get($was_ticket['group_id']), CerberusContexts::CONTEXT_GROUP));
		$dict->mergeKeys('was_bucket_', DevblocksDictionaryDelegate::getDictionaryFromModel(DAO_Bucket::get($was_ticket['bucket_id']), CerberusContexts::CONTEXT_BUCKET));
		
		$actor = CerberusContexts::getCurrentActor();
		$dict->set('actor__context', $actor['context'] ?? CerberusContexts::CONTEXT_APPLICATION);
		$dict->set('actor_id', $actor['context_id'] ?? 0);
		
		$initial_state = $dict->getDictionary();
		
		$events_kata = DAO_AutomationEvent::getKataByName('mail.moved');
		
		$handlers = DevblocksPlatform::services()->ui()->eventHandler()->parse(
			$events_kata,
			DevblocksDictionaryDelegate::instance($initial_state),
			$error
		);
		
		if(false === $handlers && $error) {
			error_log('[KATA] Invalid mail.moved KATA: ' . $error);
			$handlers = [];
		}
		
		DevblocksPlatform::services()->ui()->eventHandler()->handleEach(
			AutomationTrigger_MailMoved::ID,
			$handlers,
			$initial_state,
			$error
		);
	}	
}