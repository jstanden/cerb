<?php
namespace Cerb\Automation\Builder\Trigger\InteractionWorker\Awaits;

use _DevblocksValidationService;
use DevblocksPlatform;
use Model_AutomationContinuation;

class EndAwait extends AbstractAwait {
	function invoke(string $prompt_key, string $action, Model_AutomationContinuation $continuation) {
		return false;
	}

	function validate(_DevblocksValidationService $validation) {
	}
	
	function formatValue() {
		return $this->_value;
	}
	
	function render(Model_AutomationContinuation $continuation) {
		$tpl = DevblocksPlatform::services()->template();
		
		$exit_code = 'return';
		
		$event_data = [
			'exit' => $exit_code,
			'return' => $this->_data,
		];
		
		$tpl->assign('event_data', $event_data);
		
		$tpl->display('devblocks:cerberusweb.core::automations/triggers/interaction.worker/await/end.tpl');
	}
}