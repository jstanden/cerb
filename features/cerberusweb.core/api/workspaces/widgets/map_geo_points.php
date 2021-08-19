<?php
class WorkspaceWidget_MapGeoPoints extends Extension_WorkspaceWidget {
	public function invoke(string $action, Model_WorkspaceWidget $model) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!Context_WorkspaceWidget::isReadableByActor($model, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		switch($action) {
			case 'mapClicked':
				return $this->_workspaceWidgetAction_mapClicked($model);
		}
		
		return false;
	}
	
	function render(Model_WorkspaceWidget $widget) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		$error = null;
		
		$dict = new DevblocksDictionaryDelegate([
			'current_worker__context' => CerberusContexts::CONTEXT_WORKER,
			'current_worker_id' => $active_worker->id,
			'widget__context' => CerberusContexts::CONTEXT_WORKSPACE_WIDGET,
			'widget_id' => $widget->id,
		]);
		
		if(false == ($map = DevblocksPlatform::services()->ui()->map()->parse($widget->params['map_kata'], $dict, $error)))
			return;
		
		DevblocksPlatform::services()->ui()->map()->render($map, $widget);
	}
	
	function renderConfig(Model_WorkspaceWidget $widget) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $widget);
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/map/geopoints/config.tpl');
	}
	
	function invokeConfig($action, Model_WorkspaceWidget $model) {
		return false;
	}
	
	function saveConfig(Model_WorkspaceWidget $widget, ?string &$error=null) : bool {
		@$params = DevblocksPlatform::importGPC($_POST['params'], 'array', []);
		
		$kata = DevblocksPlatform::services()->kata();
		
		// Validate map KATA
		if(array_key_exists('map_kata', $params)) {
			if(false === $kata->validate($params['map_kata'], CerberusApplication::kataSchemas()->map(), $error)) {
				$error = 'Map: ' . $error;
				return false;
			}
		}
		
		// Validate events
		if(array_key_exists('automation', $params) && array_key_exists('map_clicked', $params['automation'])) {
			if(false === $kata->validate($params['automation']['map_clicked'], CerberusApplication::kataSchemas()->automationEvent(), $error)) {
				$error = 'map.clicked event: ' . $error;
				return false;
			}
		}
		
		DAO_WorkspaceWidget::update($widget->id, array(
			DAO_WorkspaceWidget::PARAMS_JSON => json_encode($params),
		));
		
		return true;
	}
	
	private function _workspaceWidgetAction_mapClicked(Model_WorkspaceWidget $widget) {
		@$feature_type = DevblocksPlatform::importGPC($_POST['feature_type'], 'string', []);
		@$feature_properties = DevblocksPlatform::importGPC($_POST['feature_properties'], 'array', []);
		
		$tpl = DevblocksPlatform::services()->template();
		$event_handler = DevblocksPlatform::services()->ui()->eventHandler();
		$sheets = DevblocksPlatform::services()->sheet();
		$active_worker = CerberusApplication::getActiveWorker();
		
		header('Content-Type: application/json; charset=utf-8');
		
		$error = null;
		
		$dict = DevblocksDictionaryDelegate::instance([
			'feature_type' => $feature_type,
			'feature_properties' => $feature_properties,
			'widget__context' => CerberusContexts::CONTEXT_WORKSPACE_WIDGET,
			'widget_id' => $widget->id,
			'worker__context' => CerberusContexts::CONTEXT_WORKER,
			'worker_id' => $active_worker->id,
		]);
		
		$handlers = $event_handler->parse($widget->params['automation']['map_clicked'] ?? '', $dict, $error);
		
		$initial_state = $dict->getDictionary();
		
		if(false == ($automation_results = $event_handler->handleOnce(AutomationTrigger_MapClicked::ID, $handlers, $initial_state, $error))) {
			echo json_encode([]);
			return;
		}
		
		$exit_code = $automation_results->get('__exit');
		
		$result = [
			'exit_code' => $exit_code,
		];
		
		if('error' == $exit_code) {
			$result['error'] = $automation_results->getKeyPath('__error.message') ?? 'Error in map click automation.';
			echo json_encode($result);
			return;
		}
		
		if(null != ($sheet_schema = $automation_results->getKeyPath('__return.sheet', null))) {
			$properties = DevblocksDictionaryDelegate::instance($feature_properties ?? []);
			
			$sheet = $sheets->parse(DevblocksPlatform::services()->kata()->emit($sheet_schema));
			
			$sheets->addType('card', $sheets->types()->card());
			$sheets->addType('date', $sheets->types()->date());
			$sheets->addType('icon', $sheets->types()->icon());
			$sheets->addType('link', $sheets->types()->link());
			$sheets->addType('search', $sheets->types()->search());
			$sheets->addType('slider', $sheets->types()->slider());
			$sheets->addType('text', $sheets->types()->text());
			$sheets->addType('time_elapsed', $sheets->types()->timeElapsed());
			$sheets->setDefaultType('text');
			
			$layout = $sheets->getLayout($sheet);
			$columns = $sheets->getColumns($sheet);
			$rows = $sheets->getRows($sheet, [$properties]);
			
			$tpl->assign('layout', $layout);
			$tpl->assign('columns', $columns);
			$tpl->assign('rows', $rows);
			
			$result['sheet'] = $tpl->fetch('devblocks:cerberusweb.core::ui/sheets/render_fieldsets.tpl');
		}
		
		echo json_encode($result);
	}
};