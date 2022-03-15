<?php
namespace Cerb\Automation\Builder\Trigger\InteractionWebsite\Awaits;

use _DevblocksValidationService;
use DevblocksPlatform;
use Model_AutomationContinuation;

class SubmitAwait extends AbstractAwait {
	function invoke(string $prompt_key, string $action, Model_AutomationContinuation $continuation) {
		return false;
	}

	function formatValue() {
		return $this->_value;
	}
	
	function validate(_DevblocksValidationService $validation) {
	}
	
	function render(Model_AutomationContinuation $continuation) {
		$tpl = DevblocksPlatform::services()->templateSandbox();
		$session = \ChPortalHelper::getSession();
		
		$show_continue = $this->_data['continue'] ?? false;
		$show_reset = $this->_data['reset'] ?? false;
		
		$tpl->assign('session', $session);
		
		$tpl->assign('continue_options', [
			'continue' => $show_continue,
			'reset' => $show_reset,
		]);
		
		$tpl->display('devblocks:cerberusweb.core::automations/triggers/interaction.website/await/submit.tpl');
	}
}