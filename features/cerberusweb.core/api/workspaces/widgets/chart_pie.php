<?php
class WorkspaceWidget_ChartPie extends Extension_WorkspaceWidget implements ICerbWorkspaceWidget_ExportData {
	function getData(Model_WorkspaceWidget $widget, &$error=null) {
		$active_worker = CerberusApplication::getActiveWorker();
		$data = DevblocksPlatform::services()->data();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		@$data_query = DevblocksPlatform::importGPC($widget->params['data_query'], 'string', null);
		@$cache_secs = DevblocksPlatform::importGPC($widget->params['cache_secs'], 'integer', 0);
		
		$dict = DevblocksDictionaryDelegate::instance([
			'current_worker__context' => CerberusContexts::CONTEXT_WORKER,
			'current_worker_id' => $active_worker->id,
			'widget__context' => CerberusContexts::CONTEXT_WORKSPACE_WIDGET,
			'widget_id' => $widget->id,
		]);
		
		$widget->_loadDashboardPrefsForWorker($active_worker, $dict);
		
		$query = $tpl_builder->build($data_query, $dict);
		
		if(!$query) {
			$error = "Invalid data query.";
			return false;
		}
		
		if(false == ($results = $data->executeQuery($query, $error, $cache_secs)))
			return false;
		
		return $results;
	}
	
	function render(Model_WorkspaceWidget $widget) {
		$tpl = DevblocksPlatform::services()->template();
		
		@$chart_as = DevblocksPlatform::importGPC($widget->params['chart_as'], 'string', null);
		@$options = DevblocksPlatform::importGPC($widget->params['options'], 'array', []);
		@$height = DevblocksPlatform::importGPC($widget->params['height'], 'integer', 0);
		
		$error = null;
		
		if(false == ($results = $this->getData($widget, $error))) {
			echo DevblocksPlatform::strEscapeHtml($error);
			return;
		}
		
		if(empty($results)) {
			echo "(no data)";
			return;
		}
		
		$config_json = [
			'bindto' => sprintf("#widget%d", $widget->id),
			'data' => [
				'columns' => $results['data'],
				'type' => $chart_as == 'pie' ? 'pie' : 'donut'
			],
			'donut' => [
				'label' => [
					'show' => false,
					'format' => null,
				],
			],
			'pie' => [
				'label' => [
					'show' => false,
					'format' => null,
				],
			],
			'tooltip' => [
				'format' => [
					'value' => null,
				],
			],
			'legend' => [
				'show' => true,
			]
		];
		
		$config_json['legend']['show']  = @$options['show_legend'] ? true : false;
		
		$config_json['size'] = ['height' => $height ?: 320];
		
		if(false != ($chart_meta = @$results['_']))
			$tpl->assign('chart_meta_json', json_encode($chart_meta));
		
		$tpl->assign('config_json', json_encode($config_json));
		$tpl->assign('widget', $widget);
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/chart/pie/render.tpl');
	}
	
	function renderConfig(Model_WorkspaceWidget $widget) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $widget);
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/chart/pie/config.tpl');
	}
	
	function saveConfig(Model_WorkspaceWidget $widget) {
		@$params = DevblocksPlatform::importGPC($_REQUEST['params'], 'array', []);
		
		DAO_WorkspaceWidget::update($widget->id, array(
			DAO_WorkspaceWidget::PARAMS_JSON => json_encode($params),
		));
	}
	
	// Export
	
	function exportData(Model_WorkspaceWidget $widget, $format=null) {
		switch(DevblocksPlatform::strLower($format)) {
			case 'csv':
				return $this->_exportDataAsCsv($widget);
				break;
				
			default:
			case 'json':
				return $this->_exportDataAsJson($widget);
				break;
		}
		
		return false;
	}
	
	private function _exportDataAsCsv(Model_WorkspaceWidget $widget) {
		$error = null;
		
		if(false == ($data = $this->getData($widget, $error)))
			return;
		
		$fp = fopen("php://temp", 'r+');
		
		// Headings
		fputcsv($fp, [
			'Label',
			'Value',
		]);
		
		foreach($data['data'] as $d) {
			fputcsv($fp, $d);
		}
		
		rewind($fp);

		$output = "";
		
		while(!feof($fp)) {
			$output .= fgets($fp);
		}
		
		fclose($fp);
		
		return $output;
	}
	
	private function _exportDataAsJson(Model_WorkspaceWidget $widget) {
		$error = null;
		
		if(false == ($data = $this->getData($widget, $error)))
			return;
		
		$results = array(
			'widget' => array(
				'label' => $widget->label,
				'type' => $widget->extension_id,
				'version' => 'Cerb ' . APP_VERSION,
				'results' => $data,
			),
		);
		
		return DevblocksPlatform::strFormatJson($results);
	}
};