<?php
namespace Cerb\Automation\Builder\Trigger\InteractionWebsite\Awaits;

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
		$tpl = DevblocksPlatform::services()->templateSandbox();
		$session = \ChPortalHelper::getSession();
		
		$event_data = [
			'exit' => 'return',
			'return' => $this->_data,
		];
		
		$tpl->assign('session', $session);
		$tpl->assign('event_data_json', json_encode($event_data));
		$tpl->display('devblocks:cerberusweb.core::automations/triggers/interaction.website/await/end.tpl');
	}
}