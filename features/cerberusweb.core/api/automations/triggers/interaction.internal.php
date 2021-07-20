<?php
class AutomationTrigger_InteractionInternal extends AutomationTrigger_InteractionWorker {
	const ID = 'cerb.trigger.interaction.internal';
	
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
		return $this->getInputsMeta();
	}

	function getInputsMeta() : array {
		return parent::getInputsMeta();
	}
	
	function getOutputsMeta() : array {
		return parent::getOutputsMeta();
	}
	
	public function getEditorToolbarItems(array $toolbar): array {
		return parent::getEditorToolbarItems($toolbar);
	}
	
	public function getAutocompleteSuggestions() : array {
		return parent::getAutocompleteSuggestions();
	}
}