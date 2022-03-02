<?php
class WorkspaceWidget_Scatterplot extends Extension_WorkspaceWidget implements ICerbWorkspaceWidget_ExportData {
	public function invoke(string $action, Model_WorkspaceWidget $model) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!Context_WorkspaceWidget::isReadableByActor($model, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		return false;
	}

	private function _loadData(Model_WorkspaceWidget &$widget) {
		$series = $widget->params['series'] ?? null;
		
		if(empty($series)) {
			return false;
		}
		
		// Multiple datasources
		if(is_array($series))
		foreach($series as $series_idx => $series_params) {
			$datasource_extid = $series_params['datasource'] ?? null;

			if(empty($datasource_extid)) {
				unset($widget->params['series'][$series_idx]);
				continue;
			}
			
			if(null == ($datasource_ext = Extension_WorkspaceWidgetDatasource::get($datasource_extid))) {
				unset($widget->params['series'][$series_idx]);
				continue;
			}
			
			$params_prefix = sprintf("[series][%d]", $series_idx);
			
			$data = $datasource_ext->getData($widget, $series_params, $params_prefix);

			if(!empty($data)) {
				$widget->params['series'][$series_idx] = $data;
			} else {
				unset($widget->params['series'][$series_idx]);
			}
		}
		
		return true;
	}
	
	function render(Model_WorkspaceWidget $widget) {
		$tpl = DevblocksPlatform::services()->template();

		if(false == ($this->_loadData($widget))) {
			echo "This scatterplot doesn't have any data sources. Configure it and select one.";
			return;
		}
		
		$tpl->assign('widget', $widget);

		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/scatterplot/scatterplot.tpl');
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
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/scatterplot/config.tpl');
	}
	
	function invokeConfig($action, Model_WorkspaceWidget $model) {
		return false;
	}
	
	function saveConfig(Model_WorkspaceWidget $widget, ?string &$error=null) : bool {
		$params = DevblocksPlatform::importGPC($_POST['params'] ?? null, 'array', array());
		
		// [TODO] The extension should be able to filter the properties here
		
		foreach($params['series'] as $idx => $series) {
			// Convert the serialized model to proper JSON before saving
		
			if(isset($series['worklist_model_json'])) {
				$worklist_model = json_decode($series['worklist_model_json'], true);
				unset($series['worklist_model_json']);
				
				if(empty($worklist_model) && isset($series['context'])) {
					if(false != ($context_ext = Extension_DevblocksContext::get($series['context']))) {
						if(false != (@$worklist_model = json_decode(C4_AbstractViewLoader::serializeViewToAbstractJson($context_ext->getChooserView(), $context_ext->id), true))) {
							$worklist_model['context'] = $context_ext->id;
						}
					}
				}
				
				$series['worklist_model'] = $worklist_model;
				$params['series'][$idx] = $series;
			}
		}
		
		DAO_WorkspaceWidget::update($widget->id, array(
			DAO_WorkspaceWidget::PARAMS_JSON => json_encode($params),
		));

		// Clear worker models
		
		$view_id = sprintf("widget%d_worklist", $widget->id);
		DAO_WorkerViewModel::deleteByViewId($view_id);
		
		// Clear caches
		
		$cache = DevblocksPlatform::services()->cache();
		$cache->remove(sprintf("widget%d_datasource", $widget->id));
		
		return true;
	}
	
	// Export
	
	function exportData(Model_WorkspaceWidget $widget, $format=null) {
		if(false == ($this->_loadData($widget))) {
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
		$series = $widget->params['series'];
		
		$results = array();
		
		$results[] = array(
			'Series #',
			'Series Label',
			'Data X Label',
			'Data X Value',
			'Data Y Label',
			'Data Y Value',
		);
		
		if(is_array($series))
		foreach($series as $series_idx => $series_params) {
			if(!isset($series_params['data']) || empty($series_params['data']))
				continue;
		
			$data = $series_params['data'];
			
			if(is_array($data))
			foreach($data as $v) {
				$results[] = array(
					'series' => $series_idx,
					'series_label' => $series_params['label'],
					'x_label' => $v['x_label'],
					'x' => $v['x'],
					'y_label' => $v['y_label'],
					'y' => $v['y'],
				);
			}
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
		$series = $widget->params['series'];
		
		$results = array(
			'widget' => array(
				'label' => $widget->label,
				'type' => $widget->extension_id,
				'version' => 'Cerb ' . APP_VERSION,
				'series' => array(),
			),
		);
		
		if(is_array($series))
		foreach($series as $series_idx => $series_params) {
			if(!isset($series_params['data']) || empty($series_params['data']))
				continue;
		
			$data = $series_params['data'];
			
			$results['widget']['series'][$series_idx] = array(
				'id' => $series_idx,
				'label' => $series_params['label'],
				'data' => array(),
			);
			
			if(is_array($data))
			foreach($data as $v) {
				$row = array(
					'x' => $v['x'],
					'x_label' => $v['x_label'],
					'y' => $v['y'],
					'y_label' => $v['y_label'],
				);
				
				$results['widget']['series'][$series_idx]['data'][] = $row;
			}
		}
		
		return DevblocksPlatform::strFormatJson($results);
	}
};
