<?php
namespace Cerb\Automation\Builder\Trigger\InteractionWorker\Awaits;

use _DevblocksValidationService;
use CerberusApplication;
use CerberusContexts;
use DevblocksDictionaryDelegate;
use DevblocksPlatform;
use Model_AutomationContinuation;

class MapAwait extends AbstractAwait {
	function invoke(string $prompt_key, string $action, Model_AutomationContinuation $continuation) {
		return false;
	}

	function formatValue() {
		return $this->_value;
	}
	
	function validate(_DevblocksValidationService $validation) {
	}
	
	function render(Model_AutomationContinuation $continuation) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$error = null;
		
		$dict = new DevblocksDictionaryDelegate([
			'current_worker__context' => CerberusContexts::CONTEXT_WORKER,
			'current_worker_id' => $active_worker->id,
		]);
		
		$map_data = ['map' => $this->_data];

		if(!($map = DevblocksPlatform::services()->ui()->map()->parse($map_data, $dict, $error))) {
			if($this->_isBuilderPreview()) {
				$this->_renderSimulatedPlaceholder($this->_data['label'] ?? null, 'map', 'Map preview');
			}
			return;
		}

		$tpl->assign('label', $this->_data['label'] ?? null);
		$tpl->assign('map', $map);
		$tpl->display('devblocks:cerberusweb.core::automations/triggers/interaction.worker/await/map.tpl');
	}
}