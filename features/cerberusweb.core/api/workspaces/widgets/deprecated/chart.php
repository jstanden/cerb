<?php
class WorkspaceWidget_ChartLegacy extends Extension_WorkspaceWidget implements ICerbWorkspaceWidget_ExportData {
	private function _loadData(Model_WorkspaceWidget &$widget) {
		@$series = $widget->params['series'];

		if(empty($series)) {
			return false;
		}
		
		$xaxis_keys = [];
		
		// Multiple datasources
		if(is_array($series))
		foreach($series as $series_idx => $series_params) {
			@$datasource_extid = $series_params['datasource'];

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
				
				$xaxis_keys = array_merge(
					$xaxis_keys,
					array_column($data['data'], 'x_label', 'x')
				);
				
			} else {
				unset($widget->params['series'][$series_idx]);
			}
		}
		
		// Normalize the series x-axes
		
		if('bar' == $widget->params['chart_type']) {
			ksort($xaxis_keys);
			
			foreach($widget->params['series'] as $series_idx => &$series_params) {
				$data = $series_params['data'];
				$xaxis_diff = array_diff_key($xaxis_keys, $data);
				
				if($xaxis_diff) {
					foreach($xaxis_diff as $x => $x_label) {
						$data[$x] = [
							'x' => $x,
							'y' => 0,
							'x_label' => $x_label,
							'y_label' => DevblocksPlatform::formatNumberAs(0, $series_params['yaxis_format']),
						];
					}
					
					ksort($data);
				}
				
				$series_params['data'] = array_values($data);
			}
			
			$widget->params['xaxis_keys'] = $xaxis_keys;
			
		} else {
			foreach($widget->params['series'] as $series_idx => &$series_params) {
				$series_params['data'] = array_values($series_params['data']);
			}
		}
		
		return true;
	}
	
	function render(Model_WorkspaceWidget $widget) {
		$tpl = DevblocksPlatform::services()->template();
		
		if(false == ($this->_loadData($widget))) {
			echo "This chart doesn't have any data sources. Configure it and select one.";
			return;
		}
		
		// Calculate subtotals
		
		$chart_type = DevblocksPlatform::importVar(@$widget->params['chart_type'], 'string', '');
		$chart_display = DevblocksPlatform::importVar(@$widget->params['chart_display'], 'string', '');
		$series_subtotals = DevblocksPlatform::importVar(@$widget->params['chart_subtotal_series'], 'array', []);
		
		if(in_array($chart_display,['','table']) && $series_subtotals) {
			$subtotals = array_fill_keys($series_subtotals, []);
			
			foreach($widget->params['series'] as $series_idx => &$series) {
				$data = array_column($series['data'], 'y');
				$sum = array_sum($data);
				$yaxis_format = $series['yaxis_format'];
				
				if($data) {
					if(array_key_exists('sum', $subtotals)) {
						$subtotals['sum'][$series_idx] = [
							'value' => $sum,
							'format' => $yaxis_format,
						];
					}
					
					if(array_key_exists('mean', $subtotals)) {
						$subtotals['mean'][$series_idx] = [
							'value' => $sum/count($data),
							'format' => $yaxis_format,
						];
					}
					
					if(array_key_exists('min', $subtotals)) {
						$subtotals['min'][$series_idx] = [
							'value' => min($data),
							'format' => $yaxis_format,
						];
					}
					
					if(array_key_exists('max', $subtotals)) {
						$subtotals['max'][$series_idx] = [
							'value' => max($data),
							'format' => $yaxis_format,
						];
					}
				}
			}
			
			$widget->params['subtotals'] = $subtotals;
		}
		
		$row_subtotals = DevblocksPlatform::importVar(@$widget->params['chart_subtotal_row'], 'array', []);
		
		// If this is a bar chart with more than one series
		if($chart_type == 'bar' && $row_subtotals && count($widget->params['series']) > 1) {
			$yaxis_formats = array_count_values(array_column($widget->params['series'], 'yaxis_format'));
			
			// If all of the series have a consistent format
			if(1 == count($yaxis_formats)) {
				$yaxis_format = key($yaxis_formats);
				$x_subtotals = array_fill_keys($row_subtotals, []);
				$values = [];
				
				foreach($widget->params['series'] as $series_idx => &$series) {
					foreach($series['data'] as $data) {
						$values[$data['x']][] = $data['y'];
					}
				}
				
				foreach($values as $x => $data) {
					if(array_key_exists('sum', $x_subtotals)) {
						$x_subtotals['sum'][$x] = [
							'value' => array_sum($data),
						];
					}
					
					if(array_key_exists('mean', $x_subtotals)) {
						$x_subtotals['mean'][$x] = [
							'value' => array_sum($data) / count($data),
						];
					}
					
					if(array_key_exists('min', $x_subtotals)) {
						$x_subtotals['min'][$x] = [
							'value' => min($data),
						];
					}
					
					if(array_key_exists('max', $x_subtotals)) {
						$x_subtotals['max'][$x] = [
							'value' => max($data),
						];
					}
				}
				
				$widget->params['x_subtotals'] = [
					'format' => $yaxis_format,
					'data' => $x_subtotals,
				];
			}
		}
		
		$tpl->assign('widget', $widget);
		
		switch($widget->params['chart_type']) {
			case 'bar':
				$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/_legacy/chart/bar_chart_legacy.tpl');
				break;
				
			default:
			case 'line':
				$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/_legacy/chart/line_chart_legacy.tpl');
				break;
		}
	}
	
	// Config
	
	function renderConfig(Model_WorkspaceWidget $widget) {
		if(empty($widget))
			return;
		
		$tpl = DevblocksPlatform::services()->template();
		
		// Widget
		
		$tpl->assign('widget', $widget);
		
		// Datasource Extensions
		
		$datasource_mfts = Extension_WorkspaceWidgetDatasource::getAll(false, $this->manifest->id);
		$tpl->assign('datasource_mfts', $datasource_mfts);
		
		// Template
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/_legacy/chart/config.tpl');
	}
	
	function saveConfig(Model_WorkspaceWidget $widget) {
		@$params = DevblocksPlatform::importGPC($_POST['params'], 'array', array());
		
		foreach($params['series'] as $idx => $series) {
			// [TODO] The extension should be able to filter the properties here (on all widgets)
			// [TODO] $datasource = $series['datasource'];
			
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
			
			if(isset($series['line_color'])) {
				if(false != ($rgb = $this->_hex2RGB($series['line_color']))) {
					$params['series'][$idx]['fill_color'] = sprintf("rgba(%d,%d,%d,0.15)", $rgb['r'], $rgb['g'], $rgb['b']);
				}
			}
		}
		
		DAO_WorkspaceWidget::update($widget->id, array(
			DAO_WorkspaceWidget::PARAMS_JSON => json_encode($params),
		));
		
		// Clear worker models
		
		$view_id = sprintf("widget%d_worklist", $widget->id);
		DAO_WorkerViewModel::deleteByViewId($view_id);
	}
	
	// Source: http://www.php.net/manual/en/function.hexdec.php#99478
	private function _hex2RGB($hex_color) {
		$hex_color = preg_replace("/[^0-9A-Fa-f]/", '', $hex_color); // Gets a proper hex string
		$rgb = array();
		
		// If a proper hex code, convert using bitwise operation. No overhead... faster
		if (strlen($hex_color) == 6) {
			$color_value = hexdec($hex_color);
			$rgb['r'] = 0xFF & ($color_value >> 0x10);
			$rgb['g'] = 0xFF & ($color_value >> 0x8);
			$rgb['b'] = 0xFF & $color_value;
			
		// If shorthand notation, need some string manipulations
		} elseif (strlen($hex_color) == 3) {
			$rgb['r'] = hexdec(str_repeat(substr($hex_color, 0, 1), 2));
			$rgb['g'] = hexdec(str_repeat(substr($hex_color, 1, 1), 2));
			$rgb['b'] = hexdec(str_repeat(substr($hex_color, 2, 1), 2));
			
		} else {
			return false;
		}
		
		return $rgb;
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
				$row = array(
					'series' => $series_idx,
					'series_label' => $series_params['label'],
					'x_label' => $v['x_label'],
					'x' => $v['x'],
					'y_label' => $v['y_label'],
					'y' => $v['y'],
				);

				$results[] = $row;
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
				'series' => [],
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