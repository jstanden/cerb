<?php
namespace Cerb\Automation\Builder\Trigger\InteractionWebWorker\Awaits;

use _DevblocksValidationService;
use DevblocksPlatform;
use Model_AutomationExecution;

class SubmitAwait extends AbstractAwait {
	function invoke(string $prompt_key, string $action, Model_AutomationExecution $execution) {
		return false;
	}

	function formatValue() {
		return $this->_value;
	}
	
	function validate(_DevblocksValidationService $validation) {
	}
	
	function render(Model_AutomationExecution $execution) {
		$tpl = DevblocksPlatform::services()->template();
		
		@$show_continue = $this->_data['continue'];
		@$show_reset = $this->_data['reset'];
		
		$tpl->assign('continue_options', [
			'continue' => $show_continue,
			'reset' => $show_reset,
		]);
		
		$tpl->display('devblocks:cerberusweb.core::automations/triggers/interaction.web.worker/await/submit.tpl');
	}
}