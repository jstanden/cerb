<?php
class AutomationTrigger_MailSent extends Extension_AutomationTrigger {
	const ID = 'cerb.trigger.mail.sent';
	
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
				'key' => 'message_*',
				'type' => 'record',
				'params' => [
					'record_type' => 'message',
				],
				'notes' => 'The sent [message](https://cerb.ai/docs/records/types/message/#dictionary-placeholders). Supports key expansion.',
			],
		];
	}
	
	function getOutputsMeta() : array {
		return [];
	}
	
	public function getEditorToolbarItems(array $toolbar): array {
		return $toolbar;
	}
	
	public function getAutocompleteSuggestions() : array {
		return [];
	}
	
	public static function trigger(string $message_id) {
		$initial_state = [
			'message__context' => CerberusContexts::CONTEXT_MESSAGE,
			'message_id' => $message_id,
		];
		
		$events_kata = DAO_AutomationEvent::getKataByName('mail.sent');
		
		$handlers = DevblocksPlatform::services()->ui()->eventHandler()->parse(
			$events_kata,
			DevblocksDictionaryDelegate::instance($initial_state)
		);
		
		DevblocksPlatform::services()->ui()->eventHandler()->handleEach(
			AutomationTrigger_MailSent::ID,
			$handlers,
			$initial_state,
			$error
		);
	}	
}