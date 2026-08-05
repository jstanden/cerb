<?php
class AutomationTrigger_WorkerAuthenticateFailed extends Extension_AutomationTrigger {
	const ID = 'cerb.trigger.worker.authenticate.failed';
	
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
				'key' => 'worker_*',
				'type' => 'record',
				'params' => [
					'record_type' => 'worker',
				],
				'notes' => 'The [worker](https://cerb.ai/docs/records/types/worker/#dictionary-placeholders) record. Supports key expansion.',
			],
			[
				'key' => 'client_ip',
				'type' => 'text',
				'notes' => 'The client IP address.',
			],
			[
				'key' => 'client_browser_name',
				'type' => 'text',
				'notes' => 'The client browser name (e.g. Chrome, Safari, Firefox, Edge).',
			],
			[
				'key' => 'client_browser_platform',
				'type' => 'text',
				'notes' => 'The client browser platform (e.g. Windows, Mac, Linux).',
			],
			[
				'key' => 'client_browser_version',
				'type' => 'text',
				'notes' => 'The client browser version (e.g. 88.0)',
			],
		];
	}

	// Pre-fill the client_* fields from the current worker's own request (worker_* already defaults to you).
	function getSimulationInputs() : array {
		return $this->_mockClientSimulationDefaults(parent::getSimulationInputs());
	}
	
	function getOutputsMeta() {
		return [];
	}
	
	function getUsageMeta(string $automation_name): array {
		return [];
	}
	
	public function getEditorToolbarItems(array $toolbar): array {
		return $toolbar;
	}
	
	public function getAutocompleteSuggestions() : array {
		return [];
	}
}