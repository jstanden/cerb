<?php
class AutomationTrigger_RecordViewed extends Extension_AutomationTrigger {
	const ID = 'cerb.trigger.record.viewed';
	
	public static function trigger(DevblocksDictionaryDelegate $dict, $is_card=false) {
		$event_handler = DevblocksPlatform::services()->ui()->eventHandler();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(($record_viewed_event = DAO_AutomationEvent::getByName('record.viewed'))) {
			$event_dict = DevblocksDictionaryDelegate::instance([
				'is_card' => $is_card,
			]);
			$event_dict->mergeKeys('record_', $dict);
			$event_dict->mergeKeys('worker_', DevblocksDictionaryDelegate::getDictionaryFromModel($active_worker, CerberusContexts::CONTEXT_WORKER));
			
			$initial_state = $event_dict->getDictionary();
			$error = null;
			
			$handlers = $record_viewed_event->getKata($event_dict, $error);
			
			if(false === $handlers && $error) {
				error_log('[KATA] Invalid record.viewed KATA: ' . $error);
				$handlers = [];
			}
			
			$event_handler->handleEach(
				AutomationTrigger_RecordViewed::ID,
				$handlers,
				$initial_state,
				$error
			);
		}
	}
	
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
				'key' => 'record_*',
				'notes' => 'The viewed record dictionary. Supports key expansion.',
			],
			[
				'key' => 'worker_*',
				'notes' => 'The current worker record. Supports key expansion.',
			],
		];
	}
	
	function getOutputsMeta() {
		return [
			'return' => [
			]
		];
	}
	
	public function getEditorToolbarItems(array $toolbar): array {
		return $toolbar;
	}
	
	public function getAutocompleteSuggestions() : array {
		return [];
	}
}