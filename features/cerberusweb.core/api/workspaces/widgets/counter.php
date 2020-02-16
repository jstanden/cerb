<?php
class WorkspaceWidget_Counter extends Extension_WorkspaceWidget implements ICerbWorkspaceWidget_ExportData {
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
			echo "This counter doesn't have a data source. Configure it and select one.";
			return;
		}
		
		$tpl->assign('widget', $widget);
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/counter/counter.tpl');
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
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/counter/config.tpl');
	}
	
	function saveConfig(Model_WorkspaceWidget $widget) {
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
					'value' => $widget->params['metric_value'],
					'type' => $widget->params['metric_type'],
					'prefix' => $widget->params['metric_prefix'],
					'suffix' => $widget->params['metric_suffix'],
				),
			),
		);
		
		return DevblocksPlatform::strFormatJson($results);
	}
};