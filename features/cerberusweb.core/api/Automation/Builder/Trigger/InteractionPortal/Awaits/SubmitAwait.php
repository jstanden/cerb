<?php
namespace Cerb\Automation\Builder\Trigger\InteractionPortal\Awaits;

use _DevblocksValidationService;
use DevblocksPlatform;
use Model_AutomationContinuation;

class SubmitAwait extends AbstractAwait {
	function invoke(string $prompt_key, string $prompt_action, array $prompt_params, Model_AutomationContinuation $continuation) {
		return false;
	}

	function formatValue() {
		return $this->_value;
	}
	
	function validate(_DevblocksValidationService $validation, Model_AutomationContinuation $continuation) {
	}
	
	function render(Model_AutomationContinuation $continuation) {
		$tpl = DevblocksPlatform::services()->templateSandbox();
		
		$show_continue = $this->_data['continue'] ?? false;
		$show_reset = $this->_data['reset'] ?? false;
		
		$tpl->assign('continue_options', [
			'continue' => $show_continue,
			'reset' => $show_reset,
		]);
		
		$tpl->display('devblocks:cerberusweb.core::automations/triggers/interaction.portal/await/submit.tpl');
	}
}