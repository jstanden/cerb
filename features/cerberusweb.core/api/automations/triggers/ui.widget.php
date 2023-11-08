<?php
class AutomationTrigger_UiWidget extends Extension_AutomationTrigger {
	const ID = 'cerb.trigger.ui.widget';
	
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
				'key' => 'record_*',
				'notes' => 'The current record dictionary when a card or profile widget (empty on workspace widgets).',
			],
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
					'key' => 'html',
					'notes' => 'The HTML to render for the widget',
				]
			]
		];
	}
	
	function getUsageMeta(string $automation_name): array {
		$results = [];
		
		// Card widgets
		if(($linked_card_widgets = DAO_CardWidget::getWhere(sprintf("%s IN (%s) AND %s LIKE %s",
			Cerb_ORMHelper::escape(DAO_CardWidget::EXTENSION_ID),
			implode(',', Cerb_ORMHelper::qstrArray([
				'cerb.card.widget.automation',
			])),
			Cerb_ORMHelper::escape(DAO_CardWidget::EXTENSION_PARAMS_JSON),
			Cerb_ORMHelper::qstr('%' . $automation_name . '%')
		)))) {
			$linked_card_widgets = array_filter($linked_card_widgets, function($w) use ($automation_name) {
				$content = match($w->extension_id) {
					'cerb.card.widget.automation' => $w->extension_params['automation_kata'] ?? '',
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
				'cerb.profile.tab.widget.automation',
			])),
			Cerb_ORMHelper::escape(DAO_ProfileWidget::EXTENSION_PARAMS_JSON),
			Cerb_ORMHelper::qstr('%' . $automation_name . '%')
		)))) {
			$linked_profile_widgets = array_filter($linked_profile_widgets, function($w) use ($automation_name) {
				$content = match($w->extension_id) {
					'cerb.profile.tab.widget.automation' => $w->extension_params['automations_kata'] ?? '',
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
				'core.workspace.widget.automation',
			])),
			Cerb_ORMHelper::escape(DAO_WorkspaceWidget::PARAMS_JSON),
			Cerb_ORMHelper::qstr('%' . $automation_name . '%')
		)))) {
			$linked_workspace_widgets = array_filter($linked_workspace_widgets, function($w) use ($automation_name) {
				$content = match($w->extension_id) {
					'core.workspace.widget.automation' => $w->params['automations_kata'] ?? '',
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
		return [];
	}
}