<?php
class AutomationTrigger_DataQuery extends Extension_AutomationTrigger {
	const ID = 'cerb.trigger.data.query';
	
	function renderConfig(Model_Automation $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('inputs', $this->getInputsMeta());
		$tpl->assign('outputs', $this->getOutputsMeta());
		
		try {
			$tpl->display('devblocks:cerberusweb.core::automations/triggers/config_inputs_outputs.tpl');
		} catch(SmartyException | Exception $e) {
			error_log($e->getMessage());
		}
	}
	
	function validateConfig(array &$params, &$error=null) : bool {
		return true;
	}
	
	function getEventPlaceholders() : array {
		$inputs = $this->getInputsMeta();
		
		$inputs[] = [
			'key' => 'query_format',
			'notes' => 'The requested format for the data query results.',
		];
		
		return $inputs;
	}

	function getInputsMeta() : array {
		return [
			[
				'key' => 'inputs',
				'notes' => 'Custom inputs from the caller.',
			],
			[
				'key' => 'query_format',
				'notes' => 'The requested format for the data query results.',
			],
		];
	}
	
	function getOutputsMeta() : array {
		return [
			'return' => [
				[
					'key' => 'data',
					'notes' => 'Array of data query results in the requested format',
					'required' => true,
				],
//				[
//					'key' => '_',
//					'notes' => 'Optional query results metadata',
//					'required' => false,
//				],
			]
		];
	}
	
	function getUsageMeta(string $automation_name): array {
		$results = [];
		
		// Card widgets
		if(($linked_card_widgets = DAO_CardWidget::getWhere(sprintf("%s IN (%s) AND %s LIKE %s",
			Cerb_ORMHelper::escape(DAO_CardWidget::EXTENSION_ID),
			implode(',', Cerb_ORMHelper::qstrArray([
				'cerb.card.widget.sheet',
			])),
			Cerb_ORMHelper::escape(DAO_CardWidget::EXTENSION_PARAMS_JSON),
			Cerb_ORMHelper::qstr('%' . $automation_name . '%')
		)))) {
			$linked_card_widgets = array_filter($linked_card_widgets, function($w) use ($automation_name) {
				$content = match($w->extension_id) {
					'cerb.card.widget.sheet' => implode(' ', [$w->params['data_query'] ?? '']),
					default => '',
				};
				
				$tokens = DevblocksPlatform::services()->string()->tokenize($content, false);
				
				return in_array($automation_name, $tokens);
			});
			
			if($linked_card_widgets)
				$results['card_widget'] = array_column($linked_card_widgets, 'id');
		}
		
		// Profile widgets
		if(($linked_profile_widgets = DAO_ProfileWidget::getWhere(sprintf("%s IN (%s) AND %s LIKE %s",
			Cerb_ORMHelper::escape(DAO_ProfileWidget::EXTENSION_ID),
			implode(',', Cerb_ORMHelper::qstrArray([
				'cerb.profile.tab.widget.sheet',
			])),
			Cerb_ORMHelper::escape(DAO_ProfileWidget::EXTENSION_PARAMS_JSON),
			Cerb_ORMHelper::qstr('%' . $automation_name . '%')
		)))) {
			$linked_profile_widgets = array_filter($linked_profile_widgets, function($w) use ($automation_name) {
				$content = match($w->extension_id) {
					'cerb.profile.tab.widget.sheet' => implode(' ', [$w->params['data_query'] ?? '']),
					default => '',
				};
				
				$tokens = DevblocksPlatform::services()->string()->tokenize($content, false);
				
				return in_array($automation_name, $tokens);
			});
			
			if($linked_profile_widgets)
				$results['profile_widget'] = array_column($linked_profile_widgets, 'id');
		}
		
		// Workspace widgets
		if(($linked_workspace_widgets = DAO_WorkspaceWidget::getWhere(sprintf("%s IN (%s) AND %s LIKE %s",
			Cerb_ORMHelper::escape(DAO_WorkspaceWidget::EXTENSION_ID),
			implode(',', Cerb_ORMHelper::qstrArray([
				'core.workspace.widget.sheet',
			])),
			Cerb_ORMHelper::escape(DAO_WorkspaceWidget::PARAMS_JSON),
			Cerb_ORMHelper::qstr('%' . $automation_name . '%')
		)))) {
			$linked_workspace_widgets = array_filter($linked_workspace_widgets, function($w) use ($automation_name) {
				$content = match($w->extension_id) {
					'core.workspace.widget.sheet' => implode(' ', [$w->params['data_query'] ?? '']),
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
		return [];
	}
	
	public function getAutocompleteSuggestions() : array {
		return [
			'*' => [
				'(.*):return:' => [
					'data:',
				],
			],
		];
	}
}