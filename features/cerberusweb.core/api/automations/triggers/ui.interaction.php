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
					'name' => 'cerb.automationBuilder.ui.interaction.yield.promptEditor',
				],
				'interaction/respond_map' => [
					'label' => 'Map',
					'name' => 'cerb.automationBuilder.ui.interaction.yield.map',
				],
				'interaction/respond_say' => [
					'label' => 'Say',
					'name' => 'cerb.automationBuilder.ui.interaction.yield.say',
				],
				'interaction/prompt_sheet' => [
					'label' => 'Sheet',
					'name' => 'cerb.automationBuilder.ui.interaction.yield.promptSheet',
				],
				'interaction/prompt_text' => [
					'label' => 'Text',
					'name' => 'cerb.automationBuilder.ui.interaction.yield.promptText',
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
			]
		];
	}
}