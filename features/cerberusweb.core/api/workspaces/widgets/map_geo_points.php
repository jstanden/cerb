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
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$data = DevblocksPlatform::services()->data();
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$projection = DevblocksPlatform::importGPC($widget->params['projection'], 'string', 'world');
		@$data_query = DevblocksPlatform::importGPC($widget->params['data_query'], 'string', null);
		
		$dict = DevblocksDictionaryDelegate::instance([
			'current_worker__context' => CerberusContexts::CONTEXT_WORKER,
			'current_worker_id' => $active_worker->id,
			'widget__context' => CerberusContexts::CONTEXT_WORKSPACE_WIDGET,
			'widget_id' => $widget->id,
		]);
		
		$query = $tpl_builder->build($data_query, $dict);
		
		if(!$query)
			return;
		
		$error = null;
		
		if(false === ($results = $data->executeQuery($query, $error))) {
			echo DevblocksPlatform::strEscapeHtml($error);
			return;
		}
		
		if(empty($results)) {
			return;
		}
		
		if(0 != strcasecmp('geojson', @$results['_']['format'])) {
			echo DevblocksPlatform::strEscapeHtml("The data should be in 'geojson' format.");
			return;
		}
		
		$points = $results['data'];
		
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
	
	function saveConfig(Model_WorkspaceWidget $widget) {
		@$params = DevblocksPlatform::importGPC($_POST['params'], 'array', []);
		
		DAO_WorkspaceWidget::update($widget->id, array(
			DAO_WorkspaceWidget::PARAMS_JSON => json_encode($params),
		));
	}
};