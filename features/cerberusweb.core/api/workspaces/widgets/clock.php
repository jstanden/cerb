<?php
class WorkspaceWidget_Clock extends Extension_WorkspaceWidget implements ICerbWorkspaceWidget_ExportData {
	public function invoke(string $action, Model_WorkspaceWidget $model) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!Context_WorkspaceWidget::isReadableByActor($model, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		return false;
	}
	
	function render(Model_WorkspaceWidget $widget) {
		$tpl = DevblocksPlatform::services()->template();

		@$timezone = $widget->params['timezone'];
		
		if(empty($timezone)) {
			echo "This clock doesn't have a timezone. Configure it and set one.";
			return;
		}
		
		$datetimezone = new DateTimeZone($timezone);
		$datetime = new DateTime('now', $datetimezone);

		$offset = $datetimezone->getOffset($datetime);
		$tpl->assign('offset', $offset);
		
		$tpl->assign('widget', $widget);
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/clock/clock.tpl');
	}
	
	// Config
	
	function renderConfig(Model_WorkspaceWidget $widget) {
		if(empty($widget))
			return;
		
		$tpl = DevblocksPlatform::services()->template();
		
		// Widget
		
		$tpl->assign('widget', $widget);
		
		// Timezones
		
		$date = DevblocksPlatform::services()->date();
		
		$timezones = $date->getTimezones();
		$tpl->assign('timezones', $timezones);
		
		// Template
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/clock/config.tpl');
	}
	
	function saveConfig(Model_WorkspaceWidget $widget) {
		@$params = DevblocksPlatform::importGPC($_POST['params'], 'array', array());
		
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
		@$timezone = $widget->params['timezone'];
		$datetimezone = new DateTimeZone($timezone);
		$datetime = new DateTime('now', $datetimezone);
		
		$results = array(
			'Label' => $widget->label,
			'Timezone' => $widget->params['timezone'],
			'Timestamp' => $datetime->getTimestamp(),
			'Output' => $datetime->format('r'),
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
		@$timezone = $widget->params['timezone'];
		$datetimezone = new DateTimeZone($timezone);
		$datetime = new DateTime('now', $datetimezone);
		
		$results = array(
			'widget' => array(
				'label' => $widget->label,
				'type' => $widget->extension_id,
				'version' => 'Cerb ' . APP_VERSION,
				'time' => array(
					'timezone' => $widget->params['timezone'],
					'timestamp' => $datetime->getTimestamp(),
					'output' => $datetime->format('r'),
				),
			),
		);
		
		return DevblocksPlatform::strFormatJson($results);
	}
};