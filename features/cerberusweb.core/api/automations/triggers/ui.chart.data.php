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
	
	function getUsageMeta(string $automation_name): array {
		$results = [];
		
		// Workspace widgets
		if(($linked_workspace_widgets = DAO_WorkspaceWidget::getWhere(sprintf("%s IN (%s) AND %s LIKE %s",
			Cerb_ORMHelper::escape(DAO_WorkspaceWidget::EXTENSION_ID),
			implode(',', Cerb_ORMHelper::qstrArray([
				'cerb.workspace.widget.chart.kata',
			])),
			Cerb_ORMHelper::escape(DAO_WorkspaceWidget::PARAMS_JSON),
			Cerb_ORMHelper::qstr('%' . $automation_name . '%')
		)))) {
			$linked_workspace_widgets = array_filter($linked_workspace_widgets, function($w) use ($automation_name) {
				$content = match($w->extension_id) {
					'cerb.workspace.widget.chart.kata' => implode(' ', [$w->params['datasets_kata'] ?? '', $w->params['chart_kata'] ?? '']),
					default => '',
				};
				
				$tokens = DevblocksPlatform::services()->string()->tokenize($content, false);
				
				return in_array($automation_name, $tokens);
			});
			
			if($linked_workspace_widgets)
				$results['workspace_widget'] = array_column($linked_workspace_widgets, 'id');
		}
		
		return $results;
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