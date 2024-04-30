<?php
namespace Cerb\Automation\Builder\Trigger\InteractionWorker\Awaits;

use _DevblocksValidationService;
use CerberusApplication;
use DevblocksPlatform;
use Model_AutomationContinuation;

class ChartAwait extends AbstractAwait {
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
		$chart = DevblocksPlatform::services()->chart();
		$dataset = DevblocksPlatform::services()->dataset();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$label = $this->_data['label'] ?? null;
		$chart_kata = $this->_data['schema'] ?? [];
		
		$tpl->assign('var', $this->_key);
		$tpl->assign('label', $label);
		$tpl->assign('continuation_token', $continuation->token);
		$tpl->assign('error', '');
		
		$chart_options = [
			'dark_mode' => \DAO_WorkerPref::get($active_worker->id,'dark_mode',0),
		];
		$error = null;
		
		// Display error
		if(
			!($datasets_kata = $dataset->parse($this->_data['datasets'], null, $error))
			|| !($chart_json = $chart->parse($chart_kata, $datasets_kata, $chart_options, $error))
		) {
			$tpl->assign('error', $error);
		} else {
			$tpl->assign('chart_json', json_encode($chart_json));
		}
		
		$tpl->display('devblocks:cerberusweb.core::automations/triggers/interaction.worker/await/chart.tpl');
	}
}