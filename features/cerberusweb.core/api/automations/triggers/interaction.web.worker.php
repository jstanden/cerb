<?php
class AutomationTrigger_InteractionWebWorker extends Extension_AutomationTrigger {
	const ID = 'cerb.trigger.interaction.web.worker';
	
	public static function getFormComponentMeta() {
		return [
			'editor' => 'Cerb\Automation\Builder\Trigger\InteractionWebWorker\Awaits\EditorAwait',
			'end' => 'Cerb\Automation\Builder\Trigger\InteractionWebWorker\Awaits\EndAwait',
			'fileUpload' => 'Cerb\Automation\Builder\Trigger\InteractionWebWorker\Awaits\FileUploadAwait',
			'map' => 'Cerb\Automation\Builder\Trigger\InteractionWebWorker\Awaits\MapAwait',
			'say' => 'Cerb\Automation\Builder\Trigger\InteractionWebWorker\Awaits\SayAwait',
			'sheet' => 'Cerb\Automation\Builder\Trigger\InteractionWebWorker\Awaits\SheetAwait',
			'submit' => 'Cerb\Automation\Builder\Trigger\InteractionWebWorker\Awaits\SubmitAwait',
			'text' => 'Cerb\Automation\Builder\Trigger\InteractionWebWorker\Awaits\TextAwait',
			'textarea' => 'Cerb\Automation\Builder\Trigger\InteractionWebWorker\Awaits\TextareaAwait',
		];
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
				'notes' => 'The active worker record. Supports key expansion.',
			],
		];
	}
	
	function getOutputsMeta() {
		return [];
	}
	
	public function getEditorToolbarItems(array $toolbar): array {
		$toolbar['menu/insert']['items']['menu/await'] = [
			'label' => 'Await',
			'items' => [
				'interaction/prompt_editor' => [
					'label' => 'Editor',
					'uri' => 'ai.cerb.automationBuilder.interaction.web.worker.await.promptEditor',
				],
				'interaction/respond_map' => [
					'label' => 'Map',
					'uri' => 'ai.cerb.automationBuilder.interaction.web.worker.await.map',
				],
				'interaction/respond_say' => [
					'label' => 'Say',
					'uri' => 'ai.cerb.automationBuilder.interaction.web.worker.await.say',
				],
				'interaction/prompt_sheet' => [
					'label' => 'Sheet',
					'uri' => 'ai.cerb.automationBuilder.interaction.web.worker.await.promptSheet',
				],
				'interaction/prompt_text' => [
					'label' => 'Text',
					'uri' => 'ai.cerb.automationBuilder.interaction.web.worker.await.promptText',
				]
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
					'editor:',
					'fileUpload:',
					'map:',
					'say:',
					'sheet:',
					'submit:',
					'text:',
					'textarea:',
				],
				
				'(.*):await:form:elements:editor:' => [
					'label:',
					'syntax:',
					'default:',
				],
				'(.*):await:form:elements:editor:syntax:' => [
					'cerb_query',
					'html',
					'json',
					'markdown',
					'text',
					'yaml',
				],
				
				'(.*):await:form:elements:fileUpload:' => [
					'label:',
				],
			
				// [TODO] Maps KATA	
				'(.*):await:form:elements:map:' => [
					'resource:',
					'projection:',
					'regions:',
					'points:',
				],
				
				'(.*):await:form:elements:say:' => [
					'content@text:',
					'message@text:',
				],
				
			]
		];
	}
}