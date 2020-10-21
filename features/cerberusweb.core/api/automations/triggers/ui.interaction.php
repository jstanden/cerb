<?php
class AutomationTrigger_UiInteraction extends Extension_AutomationTrigger {
	const ID = 'cerb.trigger.ui.interaction';
	
	public static function getFormComponentMeta() {
		return [
			'editor' => 'Cerb\Automation\Builder\Trigger\UiInteraction\Awaits\EditorAwait',
			'end' => 'Cerb\Automation\Builder\Trigger\UiInteraction\Awaits\EndAwait',
			'map' => 'Cerb\Automation\Builder\Trigger\UiInteraction\Awaits\MapAwait',
			'say' => 'Cerb\Automation\Builder\Trigger\UiInteraction\Awaits\SayAwait',
			'sheet' => 'Cerb\Automation\Builder\Trigger\UiInteraction\Awaits\SheetAwait',
			'submit' => 'Cerb\Automation\Builder\Trigger\UiInteraction\Awaits\SubmitAwait',
			'text' => 'Cerb\Automation\Builder\Trigger\UiInteraction\Awaits\TextAwait',
			'textarea' => 'Cerb\Automation\Builder\Trigger\UiInteraction\Awaits\TextareaAwait',
		];
	}
	
	function renderConfig(Model_Automation $model) {
	}
	
	function validateConfig(array &$params, &$error=null) {
		return true;
	}
	
	function getInputsMeta() {
		return [];
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
					'uri' => 'ai.cerb.automationBuilder.ui.interaction.await.promptEditor',
				],
				'interaction/respond_map' => [
					'label' => 'Map',
					'uri' => 'ai.cerb.automationBuilder.ui.interaction.await.map',
				],
				'interaction/respond_say' => [
					'label' => 'Say',
					'uri' => 'ai.cerb.automationBuilder.ui.interaction.await.say',
				],
				'interaction/prompt_sheet' => [
					'label' => 'Sheet',
					'uri' => 'ai.cerb.automationBuilder.ui.interaction.await.promptSheet',
				],
				'interaction/prompt_text' => [
					'label' => 'Text',
					'uri' => 'ai.cerb.automationBuilder.ui.interaction.await.promptText',
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
				'(.*):await:form:map:' => [
					'region:',
					'geojson@json:',
				],
				'(.*):await:form:map:region:' => [
					'usa',
					'world',
				],
			]
		];
	}
}