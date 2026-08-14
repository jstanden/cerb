<?php
class AutomationTrigger_InteractionInternal extends AutomationTrigger_InteractionWorker {
	const ID = 'cerb.trigger.interaction.internal';
	
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
}