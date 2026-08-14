<?php
class AutomationTrigger_InteractionInternal extends AutomationTrigger_InteractionWorker {
	const ID = 'cerb.trigger.interaction.internal';
	
	public static function getFormComponentMeta() : array {
		// `uiCommand` is an internal-only await: it round-trips to a host editor via the caller's cerbBotTrigger
		// command bridge. Advertised here (not on the generic worker trigger) so only editor-paired interactions
		// offer it — no caller without the bridge can be handed a capability it can't fulfill.
		return array_merge(parent::getFormComponentMeta(), [
			'uiCommand' => ['class' => 'Cerb\Automation\Builder\Trigger\InteractionWorker\Awaits\UiCommandAwait', 'icon' => 'console'],
		]);
	}
	
	function renderConfig(Model_Automation $model) {
		parent::renderConfig($model);
	}
	
	function validateConfig(array &$params, &$error=null) : bool {
		return true;
	}
	
	function getEventPlaceholders() : array {
		return $this->getInputsMeta();
	}

	function getInputsMeta() : array {
		return parent::getInputsMeta();
	}
	
	function getOutputsMeta() : array {
		return parent::getOutputsMeta();
	}
	
	function getUsageMeta(string $automation_name): array {
		return parent::getUsageMeta($automation_name);
	}
	
	public function getEditorToolbarItems(array $toolbar): array {
		return parent::getEditorToolbarItems($toolbar);
	}
	
	public function getAutocompleteSuggestions() : array {
		$suggestions = parent::getAutocompleteSuggestions();

		// Advertise the internal-only `uiCommand` await (mirrors the getFormComponentMeta merge). All path-regex
		// keys are nested under the top-level '*' scope key. Append to the existing element-type list rather than
		// replacing it, and add its sub-key completions.
		$elements_key = '(.*):await:form:elements:';

		if(isset($suggestions['*'][$elements_key]) && is_array($suggestions['*'][$elements_key])) {
			$suggestions['*'][$elements_key][] = [
				'caption' => 'uiCommand:',
				'snippet' => "uiCommand/\${1:prompt_command}:\n\t\${2:}",
				'description' => "Round-trip a command to the host editor (read/write its live UI)",
			];
		}

		$suggestions['*']['(.*):await:form:elements:uiCommand:'] = [
			[
				'caption' => 'command:',
				'snippet' => "command: \${1:getGeometry}",
				'description' => "The host command to run (must be in the caller's ui_capabilities)",
			],
			[
				'caption' => 'params:',
				'snippet' => "params:\n\t\${1:}",
				'description' => "Arguments passed to the host command",
			],
			[
				'caption' => 'disabled@bool:',
				'snippet' => "disabled@bool: {{ \${1:__tool.parameters.command != 'setField'} }}",
				'description' => "Render inert (skip the round-trip) — enable one uiCommand per tool in a shared await:form",
			],
		];

		return $suggestions;
	}
}