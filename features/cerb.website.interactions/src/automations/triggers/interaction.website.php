<?php
class AutomationTrigger_InteractionWebsite extends Extension_AutomationTrigger {
	const ID = 'cerb.trigger.interaction.website';
	
	public static function getFormComponentMeta(): array {
		return [
			'end' => 'Cerb\Automation\Builder\Trigger\InteractionWebsite\Awaits\EndAwait',
			'say' => 'Cerb\Automation\Builder\Trigger\InteractionWebsite\Awaits\SayAwait',
			'sheet' => 'Cerb\Automation\Builder\Trigger\InteractionWebsite\Awaits\SheetAwait',
			'submit' => 'Cerb\Automation\Builder\Trigger\InteractionWebsite\Awaits\SubmitAwait',
			'text' => 'Cerb\Automation\Builder\Trigger\InteractionWebsite\Awaits\TextAwait',
			'textarea' => 'Cerb\Automation\Builder\Trigger\InteractionWebsite\Awaits\TextareaAwait',
		];
	}
	
	function renderConfig(Model_Automation $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('inputs', $this->getInputsMeta());
		$tpl->assign('outputs', $this->getOutputsMeta());
		$tpl->display('devblocks:cerberusweb.core::automations/triggers/config_inputs_outputs.tpl');
	}
	
	function validateConfig(array &$params, &$error=null) : bool {
		return true;
	}
	
	function getInputsMeta() : array {
		return [
			[
				'key' => 'interaction',
				'notes' => 'The name of the interaction.',
			],
			[
				'key' => 'interaction_params',
				'notes' => 'Arbitrary interaction parameters.',
			],
			[
				'key' => 'inputs',
				'notes' => 'Custom inputs from the caller.',
			],
			[
				'key' => 'portal_*',
				'notes' => 'The portal record.',
			],
			[
				'key' => 'client_ip',
				'notes' => 'The client IP address.',
			],
			[
				'key' => 'client_browser_name',
				'notes' => 'The client browser name (e.g. Safari).',
			],
			[
				'key' => 'client_browser_platform',
				'notes' => 'The client browser platform (e.g. Macintosh).',
			],
			[
				'key' => 'client_browser_version',
				'notes' => 'The client browser version.',
			],
		];
	}
	
	function getOutputsMeta() : array {
		return [];
	}
	
	public function getEditorToolbarItems(array $toolbar): array {
		$toolbar['menu/insert']['items']['menu/await'] = [
			'label' => 'Await',
			'items' => [
//				'interaction/respond_say' => [
//					'label' => 'Say',
//					'uri' => 'ai.cerb.automationBuilder.interaction.web.worker.await.say',
//				],
//				'interaction/prompt_sheet' => [
//					'label' => 'Sheet',
//					'uri' => 'ai.cerb.automationBuilder.interaction.web.worker.await.promptSheet',
//				],
//				'interaction/prompt_text' => [
//					'label' => 'Text',
//					'uri' => 'ai.cerb.automationBuilder.interaction.web.worker.await.promptText',
//				]
			], // items
		];
		
		return $toolbar;
	}
	
	public function getAutocompleteSuggestions() : array {
		return [
			'*' => [
				'(.*):await:' => [
					'form:',
				],
				
				'(.*):await:form:' => [
					'title:',
					'elements:',
				],
				
				'(.*):await:form:elements:' => [
					'say:',
					'sheet:',
					'submit:',
					'text:',
					'textarea:',
				],
				
				'(.*):await:form:elements:say:' => [
					'content@text:',
					'message@text:',
				],
				
			]
		];
	}
}