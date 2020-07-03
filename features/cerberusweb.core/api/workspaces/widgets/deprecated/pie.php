<?php
class WorkspaceWidget_PieChart extends Extension_WorkspaceWidget implements ICerbWorkspaceWidget_ExportData {
	public function invoke(string $action, Model_WorkspaceWidget $model) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!Context_WorkspaceWidget::isReadableByActor($model, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		return false;
	}

	private function _loadData(Model_WorkspaceWidget &$widget) {
		// Per series datasources
		@$datasource_extid = $widget->params['datasource'];

		if(empty($datasource_extid)) {
			return false;
		}
		
		if(null == ($datasource_ext = Extension_WorkspaceWidgetDatasource::get($datasource_extid)))
			return false;
		
		$data = $datasource_ext->getData($widget, $widget->params);

		// Convert raw data
		if(isset($data['data'])) {
			foreach($data['data'] as $wedge) {
				$label = @$wedge['metric_label'] ?: '';
				$value = @$wedge['metric_value'] ?: 0;
				
				if(empty($value))
					continue;
				
				$data['wedge_labels'][] = $label;
				$data['wedge_values'][] = $value;
			}
			
			unset($data['data']);
		}
		
		if(!empty($data))
			$widget->params = $data;
		
		$wedge_colors = array(
			'#57970A',
			'#007CBD',
			'#7047BA',
			'#8B0F98',
			'#CF2C1D',
			'#E97514',
			'#FFA100',
			'#3E6D07',
			'#345C05',
			'#005988',
			'#004B73',
			'#503386',
			'#442B71',
			'#640A6D',
			'#55085C',
			'#951F14',
			'#7E1A11',
			'#A8540E',
			'#8E470B',
			'#B87400',
			'#9C6200',
			'#CCCCCC',
		);
		$widget->params['wedge_colors'] = $wedge_colors;
		
		return true;
	}
	
	function render(Model_WorkspaceWidget $widget) {
		$tpl = DevblocksPlatform::services()->template();

		if(false == $this->_loadData($widget)) {
			echo "This pie chart doesn't have a data source. Configure it and select one.";
			return;
		}

		$tpl->assign('widget', $widget);
		
		// [TODO] Test arbitrary pie charts
		
		//$data = [];
		//foreach($counts as $d) {
		//	$data[] = [$d['label'], intval($d['hits'])];
		//}
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/pie_chart/pie_chart_legacy.tpl');
	}
	
	// Config
	
	function renderConfig(Model_WorkspaceWidget $widget) {
		if(empty($widget))
			return;
		
		$tpl = DevblocksPlatform::services()->template();
		
		// Widget
		
		$tpl->assign('widget', $widget);
		
		// Limit to widget
		
		$datasource_mfts = Extension_WorkspaceWidgetDatasource::getAll(false, $this->manifest->id);
		$tpl->assign('datasource_mfts', $datasource_mfts);
		
		// Template
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/pie_chart/config.tpl');
	}
	
	function invokeConfig($action, Model_WorkspaceWidget $model) {
		return false;
	}
	
	function saveConfig(Model_WorkspaceWidget $widget) {
		@$params = DevblocksPlatform::importGPC($_POST['params'], 'array', array());
		
		DAO_WorkspaceWidget::update($widget->id, array(
			DAO_WorkspaceWidget::PARAMS_JSON => json_encode($params),
		));
		
		// Clear worker models
		
		$view_id = sprintf("widget%d_worklist", $widget->id);
		DAO_WorkerViewModel::deleteByViewId($view_id);

		// Clear caches
		
		$cache = DevblocksPlatform::services()->cache();
		$cache->remove(sprintf("widget%d_datasource", $widget->id));
	}
	
	// Export
	
	function exportData(Model_WorkspaceWidget $widget, $format=null) {
		if(false == $this->_loadData($widget)) {
			return;
		}
		
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
		if(!isset($widget->params['wedge_labels']))
			return false;
		
		if(!is_array($widget->params['wedge_labels']))
			return false;
		
		$results = array();
		
		$results[] = array(
			'Label',
			'Count',
		);
		
		foreach(array_keys($widget->params['wedge_labels']) as $idx) {
			@$wedge_label = $widget->params['wedge_labels'][$idx];
			@$wedge_value = $widget->params['wedge_values'][$idx];

			$results[] = array(
				$wedge_label,
				$wedge_value,
			);
		}
		
		$fp = fopen("php://temp", 'r+');
		
		foreach($results as $result) {
			fputcsv($fp, $result);
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
		if(!isset($widget->params['wedge_labels']))
			return false;
		
		if(!is_array($widget->params['wedge_labels']))
			return false;
		
		
		$results = array(
			'widget' => array(
				'label' => $widget->label,
				'type' => $widget->extension_id,
				'version' => 'Cerb ' . APP_VERSION,
				'counts' => array(),
			),
		);

		foreach(array_keys($widget->params['wedge_labels']) as $idx) {
			@$wedge_label = $widget->params['wedge_labels'][$idx];
			@$wedge_value = $widget->params['wedge_values'][$idx];
			@$wedge_color = $widget->params['wedge_colors'][$idx];

			// Reuse the last color
			if(empty($wedge_color))
				$wedge_color = end($widget->params['wedge_colors']);
			
			$results['widget']['counts'][] = array(
				'label' => $wedge_label,
				'count' => $wedge_value,
				'color' => $wedge_color,
			);
		}
		
		return DevblocksPlatform::strFormatJson($results);
	}
};