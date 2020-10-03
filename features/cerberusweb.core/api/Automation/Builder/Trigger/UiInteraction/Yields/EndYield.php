<?php
namespace Cerb\Automation\Builder\Trigger\UiInteraction\Yields;

use _DevblocksValidationService;
use DevblocksPlatform;
use Model_AutomationExecution;

class EndYield extends AbstractYield {
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
		
		$tpl->assign('event_data', $this->_data);
		
		$tpl->display('devblocks:cerberusweb.core::automations/triggers/ui.interaction/yield/end.tpl');
	}
}