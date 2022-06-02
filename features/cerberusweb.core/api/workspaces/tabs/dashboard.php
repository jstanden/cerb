<?php
class WorkspaceTab_Dashboards extends Extension_WorkspaceTab {
	const ID = 'core.workspace.tab.dashboard';
	
	public function renderTabConfig(Model_WorkspacePage $page, Model_WorkspaceTab $tab) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('tab', $tab);
		
		// Merge in the current worker dictionary
		$labels = $values = [];
		
		$merge_labels = $merge_values = [];
		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKSPACE_TAB, null, $merge_labels, $merge_values, '', true);
		CerberusContexts::merge('tab_', 'Tab ', $merge_labels, $merge_values, $labels, $values);
		
		$merge_labels = $merge_values = [];
		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, null, $merge_labels, $merge_values, '', true);
		CerberusContexts::merge('worker_', 'Worker ', $merge_labels, $merge_values, $labels, $values);
		
		$placeholders = Extension_DevblocksContext::getPlaceholderTree($labels);
		$tpl->assign('placeholders', $placeholders);

		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/tabs/dashboard/config.tpl');
	}
	
	function saveTabConfig(Model_WorkspacePage $page, Model_WorkspaceTab $tab, ?string &$error=null) : bool {
		$params = DevblocksPlatform::importGPC($_POST['params'] ?? null, 'array', []);
		
		if(array_key_exists('prompts_kata', $params)) {
			$kata = DevblocksPlatform::services()->kata();
			
			if(false === $kata->validate($params['prompts_kata'], CerberusApplication::kataSchemas()->dashboardPrompts(), $error))
				return false;
		}
		
		DAO_WorkspaceTab::update($tab->id, array(
			DAO_WorkspaceTab::PARAMS_JSON => json_encode($params),
		));
		
		return true;
	}
	
	public function renderTab(Model_WorkspacePage $page, Model_WorkspaceTab $tab) {
		$tpl = DevblocksPlatform::services()->template();
		
		$active_worker = CerberusApplication::getActiveWorker();
		$widgets = DAO_WorkspaceWidget::getByTab($tab->id);
		
		$layout = ($tab->params['layout'] ?? null) ?: '';
		
		$zones = [
			'content' => [],
		];
		
		switch($layout) {
			case 'sidebar_left':
				$zones = [
					'sidebar' => [],
					'content' => [],
				];
				break;
				
			case 'sidebar_right':
				$zones = [
					'content' => [],
					'sidebar' => [],
				];
				break;
				
			case 'thirds':
				$zones = [
					'left' => [],
					'center' => [],
					'right' => [],
				];
				break;
		}

		// Sanitize zones
		foreach($widgets as $widget_id => $widget) {
			if(array_key_exists($widget->zone, $zones)) {
				$zones[$widget->zone][$widget_id] = $widget;
				continue;
			}
			
			// If the zone doesn't exist, drop the widget into the first zone
			$zones[key($zones)][$widget_id] = $widget;
		}
		
		$tpl->assign('layout', $layout);
		$tpl->assign('zones', $zones);
		$tpl->assign('model', $tab);
		
		// Prompted placeholders
		$prompts_dict = new DevblocksDictionaryDelegate([]);
		$prompts_dict->mergeKeys('tab_', DevblocksDictionaryDelegate::getDictionaryFromModel($tab, CerberusContexts::CONTEXT_WORKSPACE_TAB));
		$prompts_dict->mergeKeys('worker_', DevblocksDictionaryDelegate::getDictionaryFromModel($active_worker, CerberusContexts::CONTEXT_WORKER));
		$prompts = $tab->getPlaceholderPrompts($prompts_dict);
		$tpl->assign('prompts', $prompts);
		
		$tab_prefs = $tab->getDashboardPrefsAsWorker($active_worker);
		$tpl->assign('tab_prefs', $tab_prefs);
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/tab.tpl');
	}
	
	function exportTabConfigJson(Model_WorkspacePage $page, Model_WorkspaceTab $tab) {
		$json = array(
			'tab' => array(
				'uid' => 'workspace_tab_' . $tab->id,
				'_context' => CerberusContexts::CONTEXT_WORKSPACE_TAB,
				'name' => $tab->name,
				'extension_id' => $tab->extension_id,
				'params' => $tab->params,
				'widgets' => array(),
			),
		);
		
		$widgets = DAO_WorkspaceWidget::getByTab($tab->id);
		
		foreach($widgets as $widget) {
			$widget_json = array(
				'uid' => 'workspace_widget_' . $widget->id,
				'_context' => CerberusContexts::CONTEXT_WORKSPACE_WIDGET,
				'label' => $widget->label,
				'extension_id' => $widget->extension_id,
				'pos' => $widget->pos,
				'width_units' => $widget->width_units,
				'zone' => $widget->zone,
				'params' => $widget->params,
			);
			
			$json['tab']['widgets'][] = $widget_json;
		}
		
		return json_encode($json);
	}
	
	function importTabConfigJson($json, Model_WorkspaceTab $tab) {
		if(empty($tab->id) || !is_array($json))
			return false;
		
		// Backwards compatibility
		if(isset($json['tab']))
			$json = $json['tab'];
		
		if(!isset($json['widgets']) || !is_array($json['widgets']))
			return false;
		
		foreach($json['widgets'] as $widget) {
			DAO_WorkspaceWidget::create([
				DAO_WorkspaceWidget::LABEL => $widget['label'],
				DAO_WorkspaceWidget::EXTENSION_ID => $widget['extension_id'],
				DAO_WorkspaceWidget::POS => $widget['pos'],
				DAO_WorkspaceWidget::PARAMS_JSON => json_encode($widget['params']),
				DAO_WorkspaceWidget::WORKSPACE_TAB_ID => $tab->id,
				DAO_WorkspaceWidget::WIDTH_UNITS => @$widget['width_units'] ?: 2,
				DAO_WorkspaceWidget::ZONE => @$widget['zone'] ?: '',
				DAO_WorkspaceWidget::UPDATED_AT => time(),
			]);
		}
		
		return true;
	}
}