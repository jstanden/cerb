<?php
class AutomationTrigger_UiInteraction extends Extension_AutomationTrigger {
	const ID = 'cerb.trigger.ui.interaction';
	
	public static function getFormComponentMeta() {
		return [
			'editor' => 'Cerb\Automation\Builder\Trigger\UiInteraction\Yields\EditorYield',
			'end' => 'Cerb\Automation\Builder\Trigger\UiInteraction\Yields\EndYield',
			'map' => 'Cerb\Automation\Builder\Trigger\UiInteraction\Yields\MapYield',
			'say' => 'Cerb\Automation\Builder\Trigger\UiInteraction\Yields\SayYield',
			'sheet' => 'Cerb\Automation\Builder\Trigger\UiInteraction\Yields\SheetYield',
			'submit' => 'Cerb\Automation\Builder\Trigger\UiInteraction\Yields\SubmitYield',
			'text' => 'Cerb\Automation\Builder\Trigger\UiInteraction\Yields\TextYield',
			'textarea' => 'Cerb\Automation\Builder\Trigger\UiInteraction\Yields\TextareaYield',
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
		$toolbar['menu/insert']['items']['menu/yield'] = [
			'label' => 'Yield',
			'items' => [
				'interaction/prompt_editor' => [
					'label' => 'Editor',
					'uri' => 'ai.cerb.automationBuilder.ui.interaction.yield.promptEditor',
				],
				'interaction/respond_map' => [
					'label' => 'Map',
					'uri' => 'ai.cerb.automationBuilder.ui.interaction.yield.map',
				],
				'interaction/respond_say' => [
					'label' => 'Say',
					'uri' => 'ai.cerb.automationBuilder.ui.interaction.yield.say',
				],
				'interaction/prompt_sheet' => [
					'label' => 'Sheet',
					'uri' => 'ai.cerb.automationBuilder.ui.interaction.yield.promptSheet',
				],
				'interaction/prompt_text' => [
					'label' => 'Text',
					'uri' => 'ai.cerb.automationBuilder.ui.interaction.yield.promptText',
				]
			], // items
		];
		
		return $toolbar;
	}
	
	public function getAutocompleteSuggestions() : array {
		return [
			'*' => [
				'(.*):yield:' => [
					'form:',
				],
				'(.*):yield:form:' => [
					'editor:',
					'map:',
					'say:',
					'sheet:',
					'submit:',
					'text:',
				],
				'(.*):yield:form:map:' => [
					'region:',
					'geojson@json:',
				],
				'(.*):yield:form:map:region:' => [
					'usa',
					'world',
				],
			]
		];
	}
}