<?php
namespace Cerb\Automation\Builder\Trigger\InteractionWebWorker\Awaits;

use _DevblocksValidationService;
use DevblocksPlatform;
use Model_AutomationExecution;

class EndAwait extends AbstractAwait {
	function invoke(string $prompt_key, string $action, Model_AutomationExecution $execution) {
		return false;
	}

	function validate(_DevblocksValidationService $validation) {
	}
	
	function formatValue() {
		return $this->_value;
	}
	
	function render(Model_AutomationExecution $execution) {
		$tpl = DevblocksPlatform::services()->template();
		
		$exit_code = 'return';
		
		$event_data = [
			'exit' => $exit_code,
			'return' => $this->_data,
		];
		
		$tpl->assign('event_data', $event_data);
		
		$tpl->display('devblocks:cerberusweb.core::automations/triggers/interaction.web.worker/await/end.tpl');
	}
}