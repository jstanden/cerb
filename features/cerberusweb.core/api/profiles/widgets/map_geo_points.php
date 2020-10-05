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
		
		return false;
	}
	
	function render(Model_ProfileWidget $model, $context, $context_id) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		$event_handler = DevblocksPlatform::services()->ui()->eventHandler();
		
		@$projection = DevblocksPlatform::importGPC($model->extension_params['projection'], 'string', 'world');
		
		$dict = DevblocksDictionaryDelegate::instance([
			'current_worker__context' => CerberusContexts::CONTEXT_WORKER,
			'current_worker_id' => $active_worker->id,
			'record__context' => $context,
			'record_id' => $context_id,
			'widget__context' => CerberusContexts::CONTEXT_PROFILE_WIDGET,
			'widget_id' => $model->id,
		]);
		
		$error = null;
		
		$handlers = $event_handler->parse($model->extension_params['automation_getpoints'], $dict, $error);
		
		$event_state = [
			'widget__context' => Context_ProfileWidget::ID,
			'widget_id' => $model->id,
			
			'worker__context' => CerberusContexts::CONTEXT_WORKER,
			'worker_id' => $active_worker->id,
		];
		
		$automation_results = $event_handler->handleOnce(AutomationTrigger_WidgetMapGetPoints::ID, $handlers, $event_state, $error);
		
		$points = [];
		
		if(false == $automation_results) {
			false;
			
		} else {
			if('return' != $automation_results->get('__exit')) {
				// [TODO] Error
				false;
				
			} else {
				$points = $automation_results->getKeyPath('__return.points', []);
			}
		}
		
		$handlers = $event_handler->parse($model->extension_params['automation_renderpoint'], $dict, $error);
		
		if($handlers) {
			$recurse_points = function(&$node) use (&$recurse_points, $event_handler, $handlers, $tpl, $model, $active_worker) {
				if(!is_array($node))
					return;
				
				if(array_key_exists('type', $node) && DevblocksPlatform::strLower($node['type']) == 'point') {
					$event_state = [
						'point' => $node,
						
						'widget__context' => Context_ProfileWidget::ID,
						'widget_id' => $model->id,
						
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
		$tpl->assign('widget', $model);
		
		switch($projection) {
			case 'usa':
				$tpl->display('devblocks:cerberusweb.core::internal/widgets/map/geopoints/render_usa.tpl');
				break;
				
			default:
				$tpl->display('devblocks:cerberusweb.core::internal/widgets/map/geopoints/render_world.tpl');
				break;
		}
	}
	
	function renderConfig(Model_ProfileWidget $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $model);
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/map/geopoints/config.tpl');
	}
	
	function invokeConfig($action, Model_ProfileWidget $model) {
		return false;
	}
};