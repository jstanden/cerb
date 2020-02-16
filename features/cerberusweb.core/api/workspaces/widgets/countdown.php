<?php
class WorkspaceWidget_Countdown extends Extension_WorkspaceWidget implements ICerbWorkspaceWidget_ExportData {
	function render(Model_WorkspaceWidget $widget) {
		$tpl = DevblocksPlatform::services()->template();

		if(!isset($widget->params['target_timestamp'])) {
			echo "This countdown doesn't have a target date. Configure it and set one.";
			return;
		}
		
		$tpl->assign('widget', $widget);
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/countdown/countdown.tpl');
	}
	
	// Config
	
	function renderConfig(Model_WorkspaceWidget $widget) {
		if(empty($widget))
			return;
		
		$tpl = DevblocksPlatform::services()->template();
		
		// Widget
		
		$tpl->assign('widget', $widget);
		
		// Template
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/countdown/config.tpl');
	}
	
	function saveConfig(Model_WorkspaceWidget $widget) {
		@$params = DevblocksPlatform::importGPC($_POST['params'], 'array', array());
		
		if(isset($params['target_timestamp'])) {
			@$timestamp = intval(strtotime($params['target_timestamp']));
			$params['target_timestamp'] = $timestamp;
		}
		
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
		@$diff = max(0, intval($widget->params['target_timestamp']) - time());
		
		$results = array(
			'Label' => $widget->label,
			'Timestamp' => $widget->params['target_timestamp'],
			'Output' => DevblocksPlatform::strSecsToString($diff, 2),
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
		@$diff = max(0, intval($widget->params['target_timestamp']) - time());
		
		$results = array(
			'widget' => array(
				'label' => $widget->label,
				'type' => $widget->extension_id,
				'version' => 'Cerb ' . APP_VERSION,
				'countdown' => array(
					'output' => DevblocksPlatform::strSecsToString($diff, 2),
					'timestamp' => $widget->params['target_timestamp'],
				),
			),
		);
		
		return DevblocksPlatform::strFormatJson($results);
	}
};