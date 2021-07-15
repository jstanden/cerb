<?php
class AutomationTrigger_MailDraftValidate extends AutomationTrigger_InteractionWorker {
	const ID = 'cerb.trigger.mail.draft.validate';
	
	public static function getFormComponentMeta() : array {
		return parent::getFormComponentMeta();
	}
	
	function renderConfig(Model_Automation $model) {
		parent::renderConfig($model);
	}
	
	function validateConfig(array &$params, &$error=null) : bool {
		return true;
	}
	
	function getEventPlaceholders() : array {
		$inputs = $this->getInputsMeta();
		
		$inputs[] = [
			'key' => 'draft_*',
			'notes' => 'The [draft](https://cerb.ai/docs/records/types/draft/#dictionary-placeholders) record. Supports key expansion.',
		];
		
		return $inputs;
	}

	function getInputsMeta() : array {
		return [
			[
				'key' => 'caller_name',
				'notes' => 'The caller which started the interaction.',
			],
			[
				'key' => 'caller_params',
				'notes' => 'Built-in parameters based on the caller type.',
			],
			[
				'key' => 'inputs',
				'notes' => 'Custom inputs from the caller.',
			],
			[
				'key' => 'worker_*',
				'notes' => 'The active [worker](https://cerb.ai/docs/records/types/worker/#dictionary-placeholders) record. Supports key expansion.',
			],
		];
	}
	
	function getOutputsMeta() : array {
		return [
			'return' => [
				[
					'key' => 'reject',
					'notes' => '`true` to reject validation',
					'required' => false,
				],
			]
		];
	}
	
	public function getEditorToolbarItems(array $toolbar): array {
		return parent::getEditorToolbarItems($toolbar);
	}
	
	public function getAutocompleteSuggestions() : array {
		return parent::getAutocompleteSuggestions();
	}
}