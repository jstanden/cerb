<?php
class WorkspaceWidget_MapGeoPoints extends Extension_WorkspaceWidget {
	public function invoke(string $action, Model_WorkspaceWidget $model) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!Context_WorkspaceWidget::isReadableByActor($model, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		return false;
	}
	
	function render(Model_WorkspaceWidget $widget) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		$event_handler = DevblocksPlatform::services()->ui()->eventHandler();
		
		@$projection = DevblocksPlatform::importGPC($widget->params['projection'], 'string', 'world');
		
		// [TODO] Migrate data query params to automations
		
		$error = null;
		
		$dict = new DevblocksDictionaryDelegate([
			'current_worker__context' => CerberusContexts::CONTEXT_WORKER,
			'current_worker_id' => $active_worker->id,
			'widget__context' => CerberusContexts::CONTEXT_WORKSPACE_WIDGET,
			'widget_id' => $widget->id,
		]);
		
		$handlers = $event_handler->parse($widget->params['automation_getpoints'], $dict, $error);
		
		$event_state = [
			'widget__context' => Context_WorkspaceWidget::ID,
			'widget_id' => $widget->id,
			
			'worker__context' => CerberusContexts::CONTEXT_WORKER,
			'worker_id' => $active_worker->id,
		];
		
		$points = [];
		
		$automation_results = $event_handler->handleOnce(AutomationTrigger_WidgetMapGetPoints::ID, $handlers, $event_state, $error);
		
		if($automation_results) {
			if('return' != $automation_results->get('__exit')) {
				// [TODO] Error
				false;
				
			} else {
				$points = $automation_results->getKeyPath('__return.points', []);
			}
		}
		
		$handlers = $event_handler->parse($widget->params['automation_renderpoint'], $dict, $error);
		
		if($handlers) {
			$recurse_points = function(&$node) use (&$recurse_points, $event_handler, $handlers, $tpl, $widget, $active_worker) {
				if(!is_array($node))
					return;
				
				if(array_key_exists('type', $node) && DevblocksPlatform::strLower($node['type']) == 'point') {
					$event_state = [
						'point' => $node,
						
						'widget__context' => Context_WorkspaceWidget::ID,
						'widget_id' => $widget->id,
						
						'worker__context' => CerberusContexts::CONTEXT_WORKER,
						'worker_id' => $active_worker->id,
					];
					
					$automation_results = $event_handler->handleOnce(AutomationTrigger_WidgetMapRenderPoint::ID, $handlers, $event_state, $error);
					
					if($automation_results) {
						$new_point = $automation_results->getKeyPath('__return.point', []);
						
						$sheet = DevblocksPlatform::services()->sheet();
						$sheet->addType('card', $sheet->types()->card());
						$sheet->addType('text', $sheet->types()->text());
						$sheet->setDefaultType('text');
						
						$error = null;
						
						$sheet_schema = @$new_point['properties']['cerb']['map']['point']['label']['sheet'] ?: [];
						
						if(is_array($sheet_schema) && $sheet_schema) {
							$layout = $sheet->getLayout($sheet_schema);
							$columns = $sheet->getColumns($sheet_schema);
							$rows = $sheet->getRows($sheet_schema, [DevblocksDictionaryDelegate::instance($new_point['properties'])]);
							
							$tpl->assign('layout', $layout);
							$tpl->assign('columns', $columns);
							$tpl->assign('rows', $rows);
							
							$html = $tpl->fetch('devblocks:cerberusweb.core::ui/sheets/render_fieldsets.tpl');
							
							$tpl->clearAssign('layout');
							$tpl->clearAssign('columns');
							$tpl->clearAssign('rows');
							
							$new_point = DevblocksPlatform::arrayDictSet($new_point, 'properties.cerb.map.point.label', $html);
						}
						
						$node = $new_point;
					}
				
				} else {
					foreach($node as &$child) {
						$recurse_points($child);
					}
				}
			};
			
			$recurse_points($points);
		}
		
		$tpl->assign('points', $points);
		$tpl->assign('widget', $widget);
		
		switch($projection) {
			case 'usa':
				$tpl->display('devblocks:cerberusweb.core::internal/widgets/map/geopoints/render_usa.tpl');
				break;
				
			default:
				$tpl->display('devblocks:cerberusweb.core::internal/widgets/map/geopoints/render_world.tpl');
				break;
		}
	}
	
	function renderConfig(Model_WorkspaceWidget $widget) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $widget);
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/map/geopoints/config.tpl');
	}
	
	function invokeConfig($action, Model_WorkspaceWidget $model) {
		return false;
	}
	
	function saveConfig(Model_WorkspaceWidget $widget) {
		@$params = DevblocksPlatform::importGPC($_POST['params'], 'array', []);
		
		DAO_WorkspaceWidget::update($widget->id, array(
			DAO_WorkspaceWidget::PARAMS_JSON => json_encode($params),
		));
	}
};