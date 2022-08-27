<?php
class AutomationTrigger_UiChartData extends Extension_AutomationTrigger {
	const ID = 'cerb.trigger.ui.chart.data';
	
	function renderConfig(Model_Automation $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('inputs', $this->getInputsMeta());
		$tpl->assign('outputs', $this->getOutputsMeta());
		$tpl->display('devblocks:cerberusweb.core::automations/triggers/config_inputs_outputs.tpl');
	}
	
	function validateConfig(array &$params, &$error=null) {
		return true;
	}
	
	function getInputsMeta() {
		return [
			[
				'key' => 'widget_*',
				'notes' => 'The card, profile, or workspace widget record.',
			],
			[
				'key' => 'worker_*',
				'notes' => 'The current worker record.',
			],
		];
	}
	
	function getOutputsMeta() {
		return [
			'return' => [
				[
					'key' => 'data',
					'notes' => 'The chart data as an array of series',
				]
			]
		];
	}
	
	public function getEditorToolbarItems(array $toolbar): array {
		return $toolbar;
	}
	
	public function getAutocompleteSuggestions() : array {
		return [
			'*' => [
				'(.*):return:' => [
					'data:',
				],
				'(.*):return:data:' => [
					'data0@csv: 1,2,3,4,5',
					[
						'caption' => 'data1@list:',
						'snippet' => "data1@list:\n\t5\n\t4\n\t3\n\t2\n\t1\n",
					],
					'data2@json: [2,4,6,8,10]',
				],
			]
		];
	}
}