<?php
namespace Cerb\Automation\Builder\Trigger\UiInteraction\Yields;

use _DevblocksValidationService;
use DevblocksPlatform;
use Model_AutomationExecution;

class MapYield extends AbstractYield {
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
		
		@$region = $this->_data['region'] ?? 'world';
		
		@$geojson = $this->_data['geojson'];
		$tpl->assign('points', $geojson);
		
		if('usa' == $region) {
			$tpl->display('devblocks:cerberusweb.core::automations/triggers/ui.interaction/yield/map_usa.tpl');
		} else {
			$tpl->display('devblocks:cerberusweb.core::automations/triggers/ui.interaction/yield/map_world.tpl');
		}
	}
}