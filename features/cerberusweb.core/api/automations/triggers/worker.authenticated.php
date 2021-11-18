<?php
class AutomationTrigger_WorkerAuthenticated extends Extension_AutomationTrigger {
	const ID = 'cerb.trigger.worker.authenticated';
	
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
				'notes' => 'The [worker](https://cerb.ai/docs/records/types/worker/#dictionary-placeholders) record. Supports key expansion.',
			],
			[
				'key' => 'client_ip',
				'notes' => 'The client IP address.',
			],
			[
				'key' => 'client_browser_name',
				'notes' => 'The client browser name (e.g. Chrome, Safari, Firefox, Edge).',
			],
			[
				'key' => 'client_browser_platform',
				'notes' => 'The client browser platform (e.g. Windows, Mac, Linux).',
			],
			[
				'key' => 'client_browser_version',
				'notes' => 'The client browser version (e.g. 88.0)',
			],
		];
	}
	
	function getOutputsMeta() {
		return [
			'return' => [
				[
					'key' => 'deny',
					'notes' => 'If defined, the worker login is denied with the given error message. For instance, combine this with an approved list of known client IPs, or reject very old browser versions.',
				],
			],
		];
	}
	
	public function getEditorToolbarItems(array $toolbar): array {
		return $toolbar;
	}
	
	public function getAutocompleteSuggestions() : array {
		return [
			'*' => [
				'(.*):return:' => [
					'deny@text: Access denied.',
				],
			]
		];
	}
}