<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2019, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.ai/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.ai	    http://webgroup.media
 ***********************************************************************/

class PageSection_InternalDashboards extends Extension_PageSection {
	function render() {}
	
	function getContextFieldsJsonAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'], 'string', null);
		
		header('Content-Type: application/json');
		
		if(null == ($context_ext = Extension_DevblocksContext::get($context))) {
			echo json_encode(false);
			return;
		}

		$view_class = $context_ext->getViewClass();
		
		if(null == ($view = new $view_class())) { /* @var $view C4_AbstractView */
			echo json_encode(false);
			return;
		}
		
		$results = [];
		$params_avail = $view->getParamsAvailable();
		
		$subtotals = [];
		
		if($view instanceof IAbstractView_Subtotals) /* @var $view IAbstractView_Subtotals */
			$subtotals = $view->getSubtotalFields();
		
		if(is_array($params_avail))
		foreach($params_avail as $param) { /* @var $param DevblocksSearchField */
			if(empty($param->db_label))
				continue;
		
			$results[] = array(
				'key' => $param->token,
				'label' => mb_convert_case($param->db_label, MB_CASE_LOWER),
				'type' => $param->type,
				'sortable' => $param->is_sortable,
				'subtotals' => array_key_exists($param->token, $subtotals),
			);
		}
		
		echo json_encode($results);
	}
	
	function getContextPlaceholdersJsonAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'], 'string', null);
		
		header('Content-Type: application/json');
		
		$labels = [];
		$values = [];
		
		CerberusContexts::getContext($context, null, $labels, $values, null, true);
		
		if(empty($labels)) {
			echo json_encode(false);
			return;
		}
		
		$types = @$values['_types'] ?: [];
		$results = array();
		
		foreach($labels as $k => $v) {
			$results[] = array(
				'key' => $k,
				'label' => $v,
				'type' => @$types[$k] ?: '',
			);
		}
		
		echo json_encode($results);
	}
	
	function setWidgetPositionsAction() {
		@$columns = DevblocksPlatform::importGPC($_REQUEST['column'], 'array', array());

		if(is_array($columns))
		foreach($columns as $idx => $widget_ids) {
			foreach(DevblocksPlatform::parseCsvString($widget_ids) as $n => $widget_id) {
				$pos = sprintf("%d%03d", $idx, $n);
				
				DAO_WorkspaceWidget::update($widget_id, array(
					DAO_WorkspaceWidget::POS => $pos,
				));
			}
			
			// [TODO] Kill cache on dashboard
		}
	}
	
	function handleWidgetActionAction() {
		@$widget_id = DevblocksPlatform::importGPC($_REQUEST['widget_id'], 'string', '');
		@$widget_action = DevblocksPlatform::importGPC($_REQUEST['widget_action'], 'string', '');
		
		if(false == ($active_worker = CerberusApplication::getActiveWorker()))
			return;
		
		if(false == ($widget = DAO_WorkspaceWidget::get($widget_id)))
			return;
		
		if(!Context_WorkspaceWidget::isReadableByActor($widget, $active_worker))
			return;
		
		if(false == ($widget_extension = $widget->getExtension()))
			return;
		
		if($widget_extension instanceof Extension_WorkspaceWidget && method_exists($widget_extension, $widget_action.'Action')) {
			call_user_func([$widget_extension, $widget_action.'Action']);
		}
	}
}

class WorkspaceTab_Dashboards extends Extension_WorkspaceTab {
	const ID = 'core.workspace.tab.dashboard';
	
	public function renderTabConfig(Model_WorkspacePage $page, Model_WorkspaceTab $tab) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('tab', $tab);

		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/tabs/dashboard/config.tpl');
	}
	
	function saveTabConfig(Model_WorkspacePage $page, Model_WorkspaceTab $tab) {
		@$params = DevblocksPlatform::importGPC($_REQUEST['params'], 'array', []);
		
		DAO_WorkspaceTab::update($tab->id, array(
			DAO_WorkspaceTab::PARAMS_JSON => json_encode($params),
		));
	}
	
	public function renderTab(Model_WorkspacePage $page, Model_WorkspaceTab $tab) {
		$tpl = DevblocksPlatform::services()->template();
		
		$active_worker = CerberusApplication::getActiveWorker();
		$widgets = DAO_WorkspaceWidget::getByTab($tab->id);
		
		@$layout = $tab->params['layout'] ?: '';
		
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
		$prompts = $tab->getPlaceholderPrompts();
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
