<?php
if(class_exists('Extension_PageSection')):
class PageSection_InternalDashboards extends Extension_PageSection {
	function render() {}
	
	function renderWidgetAction() {
		@$widget_id = DevblocksPlatform::importGPC($_REQUEST['widget_id'], 'integer', 0);
		
		$tpl = DevblocksPlatform::getTemplateService();
				
		if(!empty($widget_id) && null != ($widget = DAO_WorkspaceWidget::get($widget_id))) {
			$tpl->assign('widget', $widget);
			$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/render.tpl');
		}
	}
	
	function showWidgetPopupAction() {
		@$widget_id = DevblocksPlatform::importGPC($_REQUEST['widget_id'], 'integer', 0);
		
		$tpl = DevblocksPlatform::getTemplateService();

		if(empty($widget_id)) {
			// [TODO] Verify this ID
			@$workspace_tab_id = DevblocksPlatform::importGPC($_REQUEST['workspace_tab_id'], 'integer', 0);
			$tpl->assign('workspace_tab_id', $workspace_tab_id);
			
			$widget_extensions = Extension_WorkspaceWidget::getAll(false);
			$tpl->assign('widget_extensions', $widget_extensions);
			
			$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/add.tpl');
			
		} else {
			if(null != ($widget = DAO_WorkspaceWidget::get($widget_id))) {
				$tpl->assign('widget', $widget);
				
				if(null != ($extension = Extension_WorkspaceWidget::get($widget->extension_id))) {
					$tpl->assign('extension', $extension);
					$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/peek.tpl');
				}
			}
		}
	}
	
	function saveWidgetPopupAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$label = DevblocksPlatform::importGPC($_REQUEST['label'], 'string', 'Widget');
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'], 'integer', 0);

		if(!empty($id) && !empty($do_delete)) {
			DAO_WorkspaceWidget::delete($id);
			
		} else {
			$fields = array(
				DAO_WorkspaceWidget::LABEL => $label,
			);
			
			if(null != ($widget = DAO_WorkspaceWidget::get($id))) {
				DAO_WorkspaceWidget::update($widget->id, $fields);
				
				if(null != ($widget_extension = Extension_WorkspaceWidget::get($widget->extension_id))) {
					$widget_extension->saveConfig($widget);
				}
			}
		}
	}
	
	function addWidgetPopupJsonAction() {
		@$extension_id = DevblocksPlatform::importGPC($_REQUEST['extension_id'], 'string', null);
		@$workspace_tab_id = DevblocksPlatform::importGPC($_REQUEST['workspace_tab_id'], 'integer', 0);
		
		header('Content-Type: application/json');
		
		if(empty($extension_id) || null == ($extension = Extension_WorkspaceWidget::get($extension_id))) {
			echo json_encode(false);
			return;
		}
		
		if(empty($workspace_tab_id)) {
			echo json_encode(false);
			return;
		}
		
		$widget_id = DAO_WorkspaceWidget::create(array(
			DAO_WorkspaceWidget::LABEL => 'New widget',
			DAO_WorkspaceWidget::EXTENSION_ID => $extension_id,
			DAO_WorkspaceWidget::WORKSPACE_TAB_ID => $workspace_tab_id,
			DAO_WorkspaceWidget::POS => '0000',
		));
		
		echo json_encode(array(
			'widget_id' => $widget_id,
			'widget_extension_id' => $extension_id,
			'widget_tab_id' => $workspace_tab_id,
		));
	}
	
	function getContextFieldsJsonAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'], 'string', null);
		
		header('Content-Type: application/json');
		
		if(null == ($context_ext = Extension_DevblocksContext::get($context))) {
			echo json_encode(false);
			return;
		}

		$view_class = $context_ext->getViewClass();
		
		if(null == ($view = new $view_class())) { /* @var $view C4_AbstractView */ 
			echo json_encode(false);
			return;
		}
		
		$results = array();
		$params_avail = $view->getParamsAvailable();

		if(is_array($params_avail))
		foreach($params_avail as $param) { /* @var $param DevblocksSearchField */
			if(empty($param->db_label))
				continue;
		
			$results[] = array(
				'key' => $param->token,
				'label' => mb_convert_case($param->db_label, MB_CASE_LOWER),
				'type' => $param->type
			);
		}
		
		echo json_encode($results);
	}
	
	function setWidgetPositionsAction() {
		@$workspace_tab_id = DevblocksPlatform::importGPC($_REQUEST['workspace_tab_id'], 'integer', 0);
		@$columns = DevblocksPlatform::importGPC($_REQUEST['column'], 'array', array());

		if(is_array($columns))
		foreach($columns as $idx => $widget_ids) {
			foreach(DevblocksPlatform::parseCsvString($widget_ids) as $n => $widget_id) {
				$pos = sprintf("%d%03d", $idx, $n);
				
				DAO_WorkspaceWidget::update($widget_id, array(
					DAO_WorkspaceWidget::POS => $pos,
				));
			}
			
			// [TODO] Kill cache on dashboard
		}
	}
}
endif;

if(class_exists('Extension_WorkspaceTab')):
class WorkspaceTab_Dashboards extends Extension_WorkspaceTab {
	public function renderTab(Model_WorkspacePage $page, Model_WorkspaceTab $tab) {
		$tpl = DevblocksPlatform::getTemplateService();
		
		$tpl->assign('workspace_page', $page);
		$tpl->assign('workspace_tab', $tab);
		
		$widget_extensions = Extension_WorkspaceWidget::getAll();
		$tpl->assign('widget_extensions', $widget_extensions);
		
		// Get by workspace tab
		// [TODO] Cache
		$widgets = DAO_WorkspaceWidget::getWhere(
				sprintf("%s = %d",
					DAO_WorkspaceWidget::WORKSPACE_TAB_ID,
					$tab->id
				),
				DAO_WorkspaceWidget::POS,
				true
		);

		$columns = array();

		// [TODO] If the col_idx is greater than the number of cols on this dashboard,
		//   move widget to first col
		
		if(is_array($widgets))
		foreach($widgets as $widget) { /* @var $widget Model_WorkspaceWidget */
			$pos = !empty($widget->pos) ? $widget->pos : '0000';
			$col_idx = substr($pos,0,1);
			$n = substr($pos,1);
			
			if(!isset($columns[$col_idx]))
				$columns[$col_idx] = array();
			
			$columns[$col_idx][$widget->id] = $widget;
		}

		unset($widgets);
		
		$tpl->assign('columns', $columns);
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/tab.tpl');
	}
}
endif;

class WorkspaceWidget_Gauge extends Extension_WorkspaceWidget {
	function render(Model_WorkspaceWidget $widget) {
		$tpl = DevblocksPlatform::getTemplateService();

		switch(@$widget->params['datasource']) {
			case 'worklist':
				if(null == ($view_model = self::getParamsViewModel($widget, $widget->params)))
					return;
				
				// Force reload parameters (we can't trust the session)
				if(false == ($view = C4_AbstractViewLoader::unserializeAbstractView($view_model)))
					return;
				
				C4_AbstractViewLoader::setView($view->id, $view);
				
				// [TODO] Do something with the view
				
				$view->renderPage = 0;
				$view->renderLimit = 1;
				
				// [TODO] Just overload the query parts instead
				
				list($results, $count) = $view->getData();
				
				unset($results);
				
				$widget->params['metric_value'] = $count;
				$widget->params['metric_type'] = 'number';
				
				break;
				
			case 'sensor':
				if(class_exists('DAO_DatacenterSensor', true)
					&& null != ($sensor_id = @$widget->params['sensor_id'])
					&& null != ($sensor = DAO_DatacenterSensor::get($sensor_id))
					) {
						switch($sensor->metric_type) {
							case 'decimal':
								$widget->params['metric_value'] = floatval($sensor->metric);
								$widget->params['metric_type'] = $sensor->metric_type;
								break;
							case 'percent':
								$widget->params['metric_value'] = intval($sensor->metric);
								$widget->params['metric_type'] = $sensor->metric_type;
								break;
						}
					}
				break;
				
			case 'manual':
				break;
				
			default:
				break;
		}
		
		$tpl->assign('widget', $widget);
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/gauge/gauge.tpl');
	}
	
	// Config
	
	function renderConfig(Model_WorkspaceWidget $widget) {
		if(empty($widget))
			return;
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		// Widget
		
		$tpl->assign('widget', $widget);
		
		// Worklists
		
		$context_mfts = Extension_DevblocksContext::getAll(false, 'workspace');
		$tpl->assign('context_mfts', $context_mfts);
		
		// Sensors
		
		if(class_exists('DAO_DatacenterSensor', true)) {
			$sensors = DAO_DatacenterSensor::getWhere();
			foreach($sensors as $sensor_id => $sensor) {
				if(!in_array($sensor->metric_type, array('decimal','percent')))
					unset($sensors[$sensor_id]);
			}
			$tpl->assign('sensors', $sensors);
		}
		
		// Template
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/gauge/config.tpl');
	}
	
	function saveConfig(Model_WorkspaceWidget $widget) {
		@$params = DevblocksPlatform::importGPC($_REQUEST['params'], 'array', array());		
		
		if(isset($params['threshold_values']))
		foreach($params['threshold_values'] as $idx => $val) {
			if(empty($val)) {
				unset($params['threshold_values'][$idx]);
				continue;
			}
			
			@$label = $params['threshold_labels'][$idx];
			
			if(empty($label))
				$params['threshold_labels'][$idx] = $val;
			
			@$color = $params['threshold_colors'][$idx];
			
			if(empty($color))
				$params['threshold_colors'][$idx] = sprintf("#%s%s%s",
					dechex(mt_rand(0,255)),
					dechex(mt_rand(0,255)),
					dechex(mt_rand(0,255))
				);
		}
		
		DAO_WorkspaceWidget::update($widget->id, array(
			DAO_WorkspaceWidget::PARAMS_JSON => json_encode($params),
		));
	}
};

class WorkspaceWidget_Chart extends Extension_WorkspaceWidget {
	function render(Model_WorkspaceWidget $widget) {
		$tpl = DevblocksPlatform::getTemplateService();

		switch(@$widget->params['datasource']) {
			case 'worklist':
				foreach($widget->params['series'] as $series_idx => $series) {
					if(null == ($view_model = self::getParamsViewModel($widget, $series)))
						continue;
					
					if(null == ($context_ext = Extension_DevblocksContext::get($series['view_context'])))
						continue;
	
					if(null == ($dao_class = @$context_ext->manifest->params['dao_class']))
						continue;
					
					// Force reload parameters (we can't trust the session)
					if(false == ($view = C4_AbstractViewLoader::unserializeAbstractView($view_model)))
						continue;
					
					C4_AbstractViewLoader::setView($view->id, $view);
					
					$view->renderPage = 0;
					$view->renderLimit = 30;
					
					//list($results, $count) = $view->getData();
					
					$query_parts = $dao_class::getSearchQueryComponents(
						$view->view_columns,
						$view->getParams(),
						$view->renderSortBy,
						$view->renderSortAsc
					);
					
// 					var_dump($query_parts);
					
					$db = DevblocksPlatform::getDatabaseService();
					
					// We need to know what date fields we have
					$fields = $view->getFields();
					$xaxis_field = null;
					$xaxis_field_type = null;
					
					switch($series['xaxis_field']) {
						case '_id':
							$xaxis_field = new DevblocksSearchField('_id', $query_parts['primary_table'], 'id', null, Model_CustomField::TYPE_NUMBER);
							break;
							
						default:
							@$xaxis_field = $fields[$series['xaxis_field']];
							break;
					}
					
					if(!empty($xaxis_field))
					switch($xaxis_field->type) {
						case Model_CustomField::TYPE_DATE:
							// X-axis tick
							@$xaxis_tick = $series['xaxis_tick'];
							
							if(empty($xaxis_tick))
								$xaxis_tick = 'day';
							
							switch($xaxis_tick) {
								case 'hour':
									$date_format = '%Y-%m-%d %H:00';
									break;
									
								default:
								case 'day':
									$date_format = '%Y-%m-%d';
									break;
									
								case 'week':
									$date_format = '%YW%U';
									break;
									
								case 'month':
									$date_format = '%Y-%m';
									break;
									
								case 'year':
									$date_format = '%Y-01-01';
									break;
							}
							
							@$yaxis_func = $series['yaxis_func'];
							@$yaxis_field = $fields[$series['yaxis_field']];
							
							if(empty($yaxis_field))
								$yaxis_func = 'count';
							
							switch($yaxis_func) {
								case 'sum':
									$select_func = sprintf("SUM(%s.%s)",
										$yaxis_field->db_table,
										$yaxis_field->db_column
									);
									break;
									
								case 'avg':
									$select_func = sprintf("AVG(%s.%s)",
										$yaxis_field->db_table,
										$yaxis_field->db_column
									);
									break;
									
								case 'min':
									$select_func = sprintf("MIN(%s.%s)",
										$yaxis_field->db_table,
										$yaxis_field->db_column
									);
									break;
									
								case 'max':
									$select_func = sprintf("MAX(%s.%s)",
										$yaxis_field->db_table,
										$yaxis_field->db_column
									);
									break;
									
								default:
								case 'count':
									$select_func = 'COUNT(*)';
									break;
							}
							
							$sql = sprintf("SELECT %s AS hits, DATE_FORMAT(FROM_UNIXTIME(%s.%s), '%s') AS histo ", 
									$select_func,
									$xaxis_field->db_table,
									$xaxis_field->db_column,
									$date_format
								).
								$query_parts['join'].
								$query_parts['where'].
								sprintf("GROUP BY DATE_FORMAT(FROM_UNIXTIME(%s.%s), '%s') ",
									$xaxis_field->db_table,
									$xaxis_field->db_column,
									$date_format
								).
								'ORDER BY histo ASC'
							;
							
							$results = $db->GetArray($sql);
							$data = array();
			
							// Find the first and last date
							@$xaxis_param = array_shift(C4_AbstractView::findParam($xaxis_field->token, $view->getParams()));

							$current_tick = null;
							$last_tick = null;
							
							if(!empty($xaxis_param)) {
								if(2 == count($xaxis_param->value)) {
									$current_tick = strtotime($xaxis_param->value[0] . ($xaxis_tick=='year'?'-1':''));
									$last_tick = strtotime($xaxis_param->value[1] . ($xaxis_tick=='year'?'-1':''));
								}
							}
							
							$first_result = null;
							$last_result = null;
							
							if(empty($current_tick) && empty($last_tick)) {
								$last_result = end($results);
								$first_result = reset($results);
								$current_tick = strtotime($first_result['histo'] . ($xaxis_tick=='year'?'-1':''));
								$last_tick = strtotime($last_result['histo'] . ($xaxis_tick=='year'?'-1':''));
							}
							
							// Fill in time gaps from no data
							
// 							var_dump($current_tick, $last_tick, $xaxis_tick);
// 							var_dump($results);

							$array = array();
							
							foreach($results as $k => $v) {
								$array[$v['histo']] = $v['hits'];
							}
							
							$results = $array;
							unset($array);
							
							// Set the first histogram bucket to the beginning of its increment
							//   e.g. 2012-July-09 10:20 -> 2012-July-09 00:00
							switch($xaxis_tick) {
								case 'hour':
								case 'day':
								case 'month':
								case 'year':
									$current_tick = strtotime(strftime($date_format, $current_tick));
									break;
									
								case 'week':
									$current_tick = strtotime('Sunday', $current_tick);
									break;
							}
							
							do {
								$histo = strftime($date_format, $current_tick);
// 								var_dump($histo);
								
								$value = (isset($results[$histo])) ? $results[$histo] : 0; 
								
								$data[] = array($histo, (float)$value);
								$current_tick = strtotime(sprintf('+1 %s', $xaxis_tick), $current_tick);
								
							} while($current_tick <= $last_tick);
							
							unset($results);
							
// 							var_dump($data);
							
							$widget->params['series'][$series_idx]['data'] = $data;
							
							unset($data);
							
							//$tpl->assign('data', $data);
							
							break;
						
						case Model_CustomField::TYPE_NUMBER:
							@$yaxis_func = $series['yaxis_func'];
							@$yaxis_field = $fields[$series['yaxis_field']];
							
							if(empty($yaxis_func))
								$yaxis_func = 'count';
							
							switch($xaxis_field->token) {
								case '_id':
									$order_by = null;
									$group_by = sprintf("GROUP BY %s.id ", $query_parts['primary_table']);
									
									if(isset($fields[$view->renderSortBy])) {
										$order_by = sprintf("ORDER BY %s.%s %s",
											$fields[$view->renderSortBy]->db_table,
											$fields[$view->renderSortBy]->db_column,
											($view->renderSortAsc) ? 'ASC' : 'DESC'
										);
									}
									
									if(empty($order_by))
										$order_by = sprintf("ORDER BY %s.id ", $query_parts['primary_table']);
									
									break;
								
								default:
									$group_by = sprintf("GROUP BY %s.%s",
										$xaxis_field->db_table,
										$xaxis_field->db_column
									);
									
									$order_by = 'ORDER BY xaxis ASC';
									break;
							}
							
							
							switch($yaxis_func) {
								case 'sum':
									$select_func = sprintf("SUM(%s.%s)",
										$yaxis_field->db_table,
										$yaxis_field->db_column
									);
									break;
									
								case 'avg':
									$select_func = sprintf("AVG(%s.%s)",
										$yaxis_field->db_table,
										$yaxis_field->db_column
									);
									break;
									
								case 'min':
									$select_func = sprintf("MIN(%s.%s)",
										$yaxis_field->db_table,
										$yaxis_field->db_column
									);
									break;
									
								case 'max':
									$select_func = sprintf("MAX(%s.%s)",
										$yaxis_field->db_table,
										$yaxis_field->db_column
									);
									break;
									
								case 'value':
									$select_func = sprintf("DISTINCT %s.%s",
										$yaxis_field->db_table,
										$yaxis_field->db_column
									);
									break;
									
								default:
								case 'count':
									$select_func = 'COUNT(*)';
									break;
							}							
							
							// Scatterplots ignore histograms
							switch($widget->params['chart_type']) {
								case 'scatterplot':
									$group_by = null;
									break;
							}
							
							$sql = sprintf("SELECT %s AS yaxis, %s.%s AS xaxis " .
								$query_parts['join'].
								$query_parts['where'].
								"%s ".
								"%s ",
									$select_func,
									$xaxis_field->db_table,
									$xaxis_field->db_column,
									$group_by,
									$order_by
								);
								
							$results = $db->GetArray($sql);
							$data = array();

// 							echo $sql,"<br>\n";
							
							foreach($results as $result) {
								$data[] = array((float)$result['xaxis'], (float)$result['yaxis']);
							}

							unset($results);

							$widget->params['series'][$series_idx]['data'] = $data;

							unset($data);
							
							break;
					}
				}
				break;
				
			default:
				break;
		}
		
		$tpl->assign('widget', $widget);
		
		switch($widget->params['chart_type']) {
			case 'bar':
				$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/chart/bar_chart.tpl');
				break;
				
			case 'scatterplot':
				$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/chart/scatterplot.tpl');
				break;
				
			default:
			case 'line':
				$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/chart/line_chart.tpl');
				break;
		}
	}
	
	// Config
	
	function renderConfig(Model_WorkspaceWidget $widget) {
		if(empty($widget))
			return;
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		// Widget
		
		$tpl->assign('widget', $widget);
		
		// Worklists
		
		$context_mfts = Extension_DevblocksContext::getAll(false, 'workspace');
		$tpl->assign('context_mfts', $context_mfts);
		
		// Template
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/chart/config.tpl');
	}
	
	function saveConfig(Model_WorkspaceWidget $widget) {
		@$params = DevblocksPlatform::importGPC($_REQUEST['params'], 'array', array());		
		
		foreach($params['series'] as $idx => $series) {
			if(isset($series['view_context']) && isset($series['view_model'])) {
				if(isset($series['line_color'])) {
					if(false != ($rgb = $this->_hex2RGB($series['line_color']))) {
						$params['series'][$idx]['fill_color'] = sprintf("rgba(%d,%d,%d,0.15)", $rgb['r'], $rgb['g'], $rgb['b']);
					}
				}
			} else {
				unset($params['series'][$idx]);
			}
		}
		
		DAO_WorkspaceWidget::update($widget->id, array(
			DAO_WorkspaceWidget::PARAMS_JSON => json_encode($params),
		));
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
};

