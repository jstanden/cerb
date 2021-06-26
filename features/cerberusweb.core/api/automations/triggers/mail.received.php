<?php
class AutomationTrigger_MailReceived extends Extension_AutomationTrigger {
	const ID = 'cerb.trigger.mail.received';
	
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
				'key' => 'is_new_ticket',
				'type' => 'boolean',
				'params' => [],
				'notes' => '`true` if the message opened a new ticket; `false` if a follow-up reply',
			],
			[
				'key' => 'message_*',
				'type' => 'record',
				'params' => [
					'record_type' => 'message',
				],
				'notes' => 'The received [message](https://cerb.ai/docs/records/types/message/#dictionary-placeholders). Supports key expansion.',
			],
		];
	}
	
	function getOutputsMeta() : array {
		return [];
	}
	
	public function getEditorToolbarItems(array $toolbar): array {
		return $toolbar;
	}
	
	public function getEventToolbarItems(array $toolbar): array {
		$toolbar['interaction/library'] = [
			'icon' => 'magic',
			'tooltip' => 'Library',
			'uri' => 'ai.cerb.eventHandler.automation.mail.received',
			'inputs' => [
				'trigger' => $this->id,
			],
		];
		
		return $toolbar;
	}
	
	public function getAutocompleteSuggestions() : array {
		return [];
	}
	
	public static function trigger(string $message_id, bool $is_new) {
		$initial_state = [
			'message__context' => CerberusContexts::CONTEXT_MESSAGE,
			'message_id' => intval($message_id),
			'is_new_ticket' => $is_new
		];
		
		$events_kata = DAO_AutomationEvent::getKataByName('mail.received');
		
		$handlers = DevblocksPlatform::services()->ui()->eventHandler()->parse(
			$events_kata,
			DevblocksDictionaryDelegate::instance($initial_state)
		);
		
		DevblocksPlatform::services()->ui()->eventHandler()->handleEach(
			AutomationTrigger_MailReceived::ID,
			$handlers,
			$initial_state,
			$error
		);
	}	
}