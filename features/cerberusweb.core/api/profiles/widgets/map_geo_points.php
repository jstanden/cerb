<?php
class ProfileWidget_MapGeoPoints extends Extension_ProfileWidget {
	const ID = 'cerb.profile.tab.widget.map.geopoints';
	
	function __construct($manifest = null) {
		parent::__construct($manifest);
	}
	
	function invoke(string $action, Model_ProfileWidget $model) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!Context_ProfileWidget::isReadableByActor($model, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		switch($action) {
			case 'mapClicked':
				return $this->_profileWidgetAction_mapClicked($model);
		}
		
		return false;
	}
	
	function render(Model_ProfileWidget $model, $context, $context_id) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		$error = null;
		
		$dict = new DevblocksDictionaryDelegate([
			'current_worker__context' => CerberusContexts::CONTEXT_WORKER,
			'current_worker_id' => $active_worker->id,
			'record__context' => $context,
			'record_id' => $context_id,
			'widget__context' => CerberusContexts::CONTEXT_PROFILE_WIDGET,
			'widget_id' => $model->id,
		]);
		
		if(!($map = DevblocksPlatform::services()->ui()->map()->parse($model->extension_params['map_kata'], $dict, $error)))
			return;
		
		DevblocksPlatform::services()->ui()->map()->render($map, $model);
	}
	
	function renderConfig(Model_ProfileWidget $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $model);
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/map/geopoints/config.tpl');
	}
	
	function invokeConfig($action, Model_ProfileWidget $model) {
		return false;
	}
	
	function saveConfig(array $fields, $id, &$error = null) {
		$kata = DevblocksPlatform::services()->kata();
		
		if(array_key_exists(DAO_ProfileWidget::EXTENSION_PARAMS_JSON, $fields)) {
			if(false == (@$params = json_decode($fields[DAO_ProfileWidget::EXTENSION_PARAMS_JSON], true)))
				return true;
			
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
		}
		
		return true;
	}
	
	private function _profileWidgetAction_mapClicked(Model_ProfileWidget $widget) {
		$feature_type = DevblocksPlatform::importGPC($_POST['feature_type'] ?? null, 'string', []);
		$feature_properties = DevblocksPlatform::importGPC($_POST['feature_properties'] ?? null, 'array', []);
		
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

		$handlers = $event_handler->parse($widget->extension_params['automation']['map_clicked'] ?? '', $dict, $error);
		
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
			$data = DevblocksDictionaryDelegate::instance($feature_properties ?? []);
			
			$sheet = $sheets->parse(DevblocksPlatform::services()->kata()->emit($sheet_schema));
			
			$sheets->addType('card', $sheets->types()->card());
			$sheets->addType('date', $sheets->types()->date());
			$sheets->addType('icon', $sheets->types()->icon());
			$sheets->addType('link', $sheets->types()->link());
			$sheets->addType('markdown', $sheets->types()->markdown());
			$sheets->addType('search', $sheets->types()->search());
			$sheets->addType('slider', $sheets->types()->slider());
			$sheets->addType('text', $sheets->types()->text());
			$sheets->addType('time_elapsed', $sheets->types()->timeElapsed());
			$sheets->setDefaultType('text');
			
			$layout = $sheets->getLayout($sheet);
			$columns = $sheets->getColumns($sheet);
			$rows = $sheets->getRows($sheet, [$data]);
			
			$tpl->assign('layout', $layout);
			$tpl->assign('columns', $columns);
			$tpl->assign('rows', $rows);
			
			$result['sheet'] = $tpl->fetch('devblocks:cerberusweb.core::ui/sheets/render_fieldsets.tpl');
		}
		
		echo json_encode($result);
	}
};