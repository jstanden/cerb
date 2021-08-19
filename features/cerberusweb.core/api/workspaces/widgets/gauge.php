<?php
class WorkspaceWidget_Gauge extends Extension_WorkspaceWidget implements ICerbWorkspaceWidget_ExportData {
	public function invoke(string $action, Model_WorkspaceWidget $model) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!Context_WorkspaceWidget::isReadableByActor($model, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		return false;
	}

	private function _loadData(Model_WorkspaceWidget &$widget) {
		@$datasource_extid = $widget->params['datasource'];

		if(empty($datasource_extid)) {
			return false;
		}
		
		if(null == ($datasource_ext = Extension_WorkspaceWidgetDatasource::get($datasource_extid)))
			return false;
		
		$data = $datasource_ext->getData($widget, $widget->params);
		
		if(!empty($data))
			$widget->params = $data;
		
		return true;
	}
	
	function render(Model_WorkspaceWidget $widget) {
		$tpl = DevblocksPlatform::services()->template();

		if(false == ($this->_loadData($widget))) {
			echo "This gauge doesn't have a data source. Configure it and select one.";
			return;
		}
		
		$tpl->assign('widget', $widget);
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/gauge/gauge.tpl');
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
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/gauge/config.tpl');
	}
	
	function invokeConfig($action, Model_WorkspaceWidget $model) {
		return false;
	}
	
	function saveConfig(Model_WorkspaceWidget $widget, ?string &$error=null) : bool {
		@$params = DevblocksPlatform::importGPC($_POST['params'], 'array', array());
		
		// Convert the serialized model to proper JSON before saving
		
		if(isset($params['worklist_model_json'])) {
			$worklist_model = json_decode($params['worklist_model_json'], true);
			unset($params['worklist_model_json']);
			
			if(empty($worklist_model) && isset($params['context'])) {
				if(false != ($context_ext = Extension_DevblocksContext::get($params['context']))) {
					if(false != (@$worklist_model = json_decode(C4_AbstractViewLoader::serializeViewToAbstractJson($context_ext->getChooserView(), $context_ext->id), true))) {
						$worklist_model['context'] = $context_ext->id;
					}
				}
			}
			
			$params['worklist_model'] = $worklist_model;
		}
		
		if(isset($params['threshold_values']))
		foreach($params['threshold_values'] as $idx => $val) {
			if(0 == strlen($val)) {
				unset($params['threshold_values'][$idx]);
				unset($params['threshold_labels'][$idx]);
				unset($params['threshold_colors'][$idx]);
				continue;
			}
			
			@$label = $params['threshold_labels'][$idx];
			
			if(empty($label))
				$params['threshold_labels'][$idx] = $val;
			
			@$color = strtoupper($params['threshold_colors'][$idx]);
			
			if(empty($color))
				$color = '#FFFFFF';
			
			$params['threshold_colors'][$idx] = $color;
		}
		
		$len = count($params['threshold_colors']);
		
		if($len) {
			if(0 == strcasecmp($params['threshold_colors'][0], '#FFFFFF')) {
				$params['threshold_colors'][0] = '#CF2C1D';
			}
			
			if(0 == strcasecmp($params['threshold_colors'][$len-1], '#FFFFFF')) {
				$params['threshold_colors'][$len-1] = '#66AD11';
			}
			
			$params['threshold_colors'] = DevblocksPlatform::colorLerpArray($params['threshold_colors']);
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
		$results = array(
			'Label' => $widget->label,
			'Value' => $widget->params['metric_value'],
			'Type' => $widget->params['metric_type'],
			'Prefix' => $widget->params['metric_prefix'],
			'Suffix' => $widget->params['metric_suffix'],
		);

		$fp = fopen("php://temp", 'r+');
		
		fputcsv($fp, array_keys($results));
		fputcsv($fp, array_values($results));
		
		rewind($fp);

		$output = "";
		
		while(!feof($fp)) {
			$output .= fgets($fp);
		}
		
		fclose($fp);
		
		return $output;
	}
	
	private function _exportDataAsJson(Model_WorkspaceWidget $widget) {
		$results = array(
			'widget' => array(
				'label' => $widget->label,
				'type' => $widget->extension_id,
				'version' => 'Cerb ' . APP_VERSION,
				'metric' => array(
					'value' => @$widget->params['metric_value'],
					'type' => $widget->params['metric_type'],
					'prefix' => $widget->params['metric_prefix'],
					'suffix' => $widget->params['metric_suffix'],
				),
				'thresholds' => array(),
			),
		);
		
		if(isset($widget->params['threshold_labels']) && is_array($widget->params['threshold_labels']))
		foreach(array_keys($widget->params['threshold_labels']) as $idx) {
			if(
				empty($widget->params['threshold_labels'][$idx])
				|| !isset($widget->params['threshold_values'][$idx])
			)
				continue;
		
			$results['widget']['thresholds'][] = array(
				'label' => $widget->params['threshold_labels'][$idx],
				'value' => $widget->params['threshold_values'][$idx],
				'color' => $widget->params['threshold_colors'][$idx],
			);
		}
		
		return DevblocksPlatform::strFormatJson($results);
	}
};