<?php
class WorkspaceWidgetDatasource_WorklistMetric extends Extension_WorkspaceWidgetDatasource {
	private function _getSeriesIdxFromPrefix($params_prefix) {
		$matches = [];
		
		if(!empty($params_prefix) && preg_match("#\[series\]\[(\d+)\]#", $params_prefix, $matches) && count($matches) == 2) {
			return $matches[1];
		}
		
		return null;
	}
	
	function renderConfig(Model_WorkspaceWidget $widget, $params=array(), $params_prefix=null) {
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('widget', $widget);
		$tpl->assign('params', $params);
		$tpl->assign('params_prefix', $params_prefix);
		
		if(null !== ($series_idx = $this->_getSeriesIdxFromPrefix($params_prefix)))
			$tpl->assign('series_idx', $series_idx);
		
		// Prime the worklist
		
		$view_id = sprintf(
			"widget%d_worklist%s",
			$widget->id,
			(!is_null($series_idx) ? intval($series_idx) : '')
		);
		
		Extension_WorkspaceWidget::getViewFromParams($widget, $params, $view_id);
		
		// Worklists
		
		$context_mfts = Extension_DevblocksContext::getAll(false, 'workspace');
		$tpl->assign('context_mfts', $context_mfts);
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/datasources/config_worklist_metric.tpl');
	}
	
	function getData(Model_WorkspaceWidget $widget, array $params=array(), $params_prefix=null) {
		return $this->_getDataSingle($widget, $params, $params_prefix);
	}
	
	private function _getDataSingle(Model_WorkspaceWidget $widget, array $params=[], $params_prefix=null) {
		$series_idx = $this->_getSeriesIdxFromPrefix($params_prefix);
		
		$view_id = sprintf("widget%d_worklist%s",
			$widget->id,
			(!is_null($series_idx) ? intval($series_idx) : '')
		);
		
		if(null == ($view = Extension_WorkspaceWidget::getViewFromParams($widget, $params, $view_id)))
			return;
		
		if(!($context_ext = Extension_DevblocksContext::getByViewClass(get_class($view), true)))
			return false;
		
		if(!($dao_class = $context_ext->getDaoClass()))
			return false;
		
		if(!($search_class = $context_ext->getSearchClass()))
			return false;
		
		if(!($primary_key = $search_class::getPrimaryKey()))
			return false;
		
		$view->renderPage = 0;
		$view->renderLimit = 1;
		
		$db = DevblocksPlatform::services()->database();
		
		// We need to know what date fields we have
		$fields = $view->getFields();
		$metric_func = $params['metric_func'] ?? null;
		
		switch($metric_func) {
			case 'count':
				$metric_field = null;
				break;
				
			default:
				@$metric_field = $fields[$params['metric_field']];
				break;
		}
		
		$query_parts = $dao_class::getSearchQueryComponents([], $view->getParams());
		
		if($metric_field && DevblocksPlatform::strStartsWith($metric_field->token, 'cf_')) {
			$cfield = DAO_CustomField::get(substr($metric_field->token,3));
			
			switch($metric_func) {
				case 'sum':
					$select_func = 'SUM(field_value)';
					break;
					
				case 'avg':
					$select_func = 'AVG(field_value)';
					break;
					
				case 'min':
					$select_func = 'MIN(field_value)';
					break;
					
				case 'max':
					$select_func = 'MAX(field_value)';
					break;
					
				default:
				case 'count':
					$select_func = 'COUNT(*)';
					break;
			}
			
			$sql = sprintf("SELECT %s FROM %s WHERE context=%s AND field_id=%d AND context_id IN (%s)",
				$select_func,
				DAO_CustomFieldValue::getValueTableName($cfield->id),
				Cerb_ORMHelper::qstr($cfield->context),
				$cfield->id,
				sprintf("SELECT %s %s %s", $db->escape($primary_key), $query_parts['join'], $query_parts['where'])
			);
			
		} else {
			if($metric_field)
				$select_query = sprintf("%s.%s",
					$db->escape($metric_field->db_table),
					$db->escape($metric_field->db_column)
				);
			
			switch($metric_func) {
				case 'sum':
					$select_func = sprintf("SELECT SUM(%s) ",
						$select_query
					);
					break;
					
				case 'avg':
					$select_func = sprintf("SELECT AVG(%s) ",
						$select_query
					);
					break;
					
				case 'min':
					$select_func = sprintf("SELECT MIN(%s) ",
						$select_query
					);
					break;
					
				case 'max':
					$select_func = sprintf("SELECT MAX(%s) ",
						$select_query
					);
					break;
					
				default:
				case 'count':
					$select_func = 'SELECT COUNT(*) ';
					break;
			}
			
			$sql = 
				$select_func
				. $query_parts['join']
				. $query_parts['where']
				;
		}
		
		if(empty($sql))
			return false;
		
		$params['metric_value'] = $db->GetOneReader($sql);
		
		return $params;
	}
};

class WorkspaceWidgetDatasource_WorklistSeries extends Extension_WorkspaceWidgetDatasource {
	private function _getSeriesIdxFromPrefix($params_prefix) {
		$matches = [];
		
		if(!empty($params_prefix) && preg_match("#\[series\]\[(\d+)\]#", $params_prefix, $matches) && count($matches) == 2) {
			return $matches[1];
		}
		
		return null;
	}
	
	function renderConfig(Model_WorkspaceWidget $widget, $params=array(), $params_prefix=null) {
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('widget', $widget);
		$tpl->assign('params', $params);
		$tpl->assign('params_prefix', $params_prefix);
		
		if(null !== ($series_idx = $this->_getSeriesIdxFromPrefix($params_prefix)))
			$tpl->assign('series_idx', $series_idx);
		
		// Prime the worklist
		
		$view_id = sprintf(
			"widget%d_worklist%s",
			$widget->id,
			(!is_null($series_idx) ? intval($series_idx) : '')
		);
		
		Extension_WorkspaceWidget::getViewFromParams($widget, $params, $view_id);
		
		// Worklists
		
		$context_mfts = Extension_DevblocksContext::getAll(false, 'workspace');
		$tpl->assign('context_mfts', $context_mfts);
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/datasources/config_worklist_series.tpl');
	}
	
	function getData(Model_WorkspaceWidget $widget, array $params=[], $params_prefix=null) {
		return $this->_getDataSeries($widget, $params, $params_prefix);
	}
	
	private function _getDataSeries(Model_WorkspaceWidget $widget, array $params=[], $params_prefix=null) {
		$date = DevblocksPlatform::services()->date();
		$db = DevblocksPlatform::services()->database();
		
		// Use the worker's timezone for MySQL date functions
		$db->QueryReader(sprintf("SET time_zone = %s", $db->qstr($date->formatTime('P', time()))));
		
		$series_idx = $this->_getSeriesIdxFromPrefix($params_prefix);
		
		$view_id = sprintf("widget%d_worklist%s",
			$widget->id,
			(!is_null($series_idx) ? intval($series_idx) : '')
		);
		
		if(null == ($view = Extension_WorkspaceWidget::getViewFromParams($widget, $params, $view_id)))
			return;
		
		$view_context = $params['worklist_model']['context'] ?? null;
		
		if(empty($view_context))
			return;
		
		if(null == ($context_ext = Extension_DevblocksContext::get($view_context)))
			return;

		if(null == ($dao_class = $context_ext->getDaoClass()))
			return;
		
		if(null == ($search_class = $context_ext->getSearchClass()))
			return;
		
		if(null == ($primary_key = $search_class::getPrimaryKey()))
			return;
		
		$data = [];
		
		$view->renderPage = 0;
		$view->renderLimit = 30;
		
		// Initial query planner
		
		$query_parts = $dao_class::getSearchQueryComponents(
			$view->view_columns,
			$view->getParams(),
			$view->renderSortBy,
			$view->renderSortAsc
		);
		
		// We need to know what date fields we have
		
		$fields = $view->getFields();
		$xaxis_field = null;
		
		switch($params['xaxis_field']) {
			case '_id':
				$xaxis_field = new DevblocksSearchField('_id', $query_parts['primary_table'], 'id', null, Model_CustomField::TYPE_NUMBER);
				break;
					
			default:
				@$xaxis_field = $fields[$params['xaxis_field']];
				break;
		}
		
		if(!empty($xaxis_field)) {
			$yaxis_func = $params['yaxis_func'] ?? null;
			$yaxis_field = null;
			
			switch($yaxis_func) {
				case 'count':
					break;
					
				default:
					@$yaxis_field = $fields[$params['yaxis_field']];
					
					if(empty($yaxis_field)) {
						$yaxis_func = 'count';
					}
					break;
			}
			
			switch($xaxis_field->type) {
				case Model_CustomField::TYPE_DATE:
					// X-axis tick
					$xaxis_tick = $params['xaxis_tick'] ?? null;
						
					if(empty($xaxis_tick))
						$xaxis_tick = 'day';
						
					switch($xaxis_tick) {
						case 'hour':
							$date_format_mysql = '%Y-%m-%d %H:00';
							$date_format_php = 'Y-m-d H\:\0\0';
							$date_label = $date_format_php;
							break;
								
						case 'minute':
							$date_format_mysql = '%Y-%m-%d %H:%I';
							$date_format_php = 'Y-m-d H\:i';
							$date_label = $date_format_php;
							break;
								
						default:
						case 'day':
							$date_format_mysql = '%Y-%m-%d';
							$date_format_php = 'Y-m-d';
							$date_label = $date_format_php;
							break;
								
						case 'week':
							$date_format_mysql = '%xW%v';
							$date_format_php = 'Y\WW';
							$date_label = $date_format_php;
							break;
								
						case 'month':
							$date_format_mysql = '%Y-%m';
							$date_format_php = 'Y-m';
							$date_label = 'M Y';
							break;
								
						case 'year':
							$date_format_mysql = '%Y-01-01';
							$date_format_php = 'Y\-\0\1\-\0\1';
							$date_label = 'Y';
							break;
					}
					
					switch($yaxis_func) {
						case 'sum':
							$select_func = sprintf("SUM(%s.%s)",
								$db->escape($yaxis_field->db_table),
								$db->escape($yaxis_field->db_column)
							);
							break;
								
						case 'avg':
							$select_func = sprintf("AVG(%s.%s)",
								$db->escape($yaxis_field->db_table),
								$db->escape($yaxis_field->db_column)
							);
							break;
								
						case 'min':
							$select_func = sprintf("MIN(%s.%s)",
								$db->escape($yaxis_field->db_table),
								$db->escape($yaxis_field->db_column)
							);
							break;
								
						case 'max':
							$select_func = sprintf("MAX(%s.%s)",
								$db->escape($yaxis_field->db_table),
								$db->escape($yaxis_field->db_column)
							);
							break;
							
						case 'value':
							$select_func = sprintf("%s.%s",
								$db->escape($yaxis_field->db_table),
								$db->escape($yaxis_field->db_column)
							);
							break;
								
						default:
						case 'count':
							$select_func = 'COUNT(*)';
							break;
					}
					
					// INNER JOIN the x-axis cfield
					if($xaxis_field && DevblocksPlatform::strStartsWith($xaxis_field->token, 'cf_')) {
						$xaxis_cfield_id = substr($xaxis_field->token, 3);
						$query_parts['join'] .= sprintf("INNER JOIN (SELECT field_value, context_id FROM %s WHERE field_id = %d) AS %s ON (%s.context_id=%s) ",
							'custom_field_numbervalue',
							$xaxis_cfield_id,
							$db->escape($xaxis_field->token),
							$db->escape($xaxis_field->token),
							$primary_key
						);
					}
					
					// INNER JOIN the y-axis cfield
					if($yaxis_field && DevblocksPlatform::strStartsWith($yaxis_field->token, 'cf_') && !($xaxis_field && $xaxis_field->token == $yaxis_field->token)) {
						$yaxis_cfield_id = substr($yaxis_field->token, 3);
						$query_parts['join'] .= sprintf("INNER JOIN (SELECT field_value, context_id FROM %s WHERE field_id = %d) AS %s ON (%s.context_id=%s) ",
							'custom_field_numbervalue',
							$yaxis_cfield_id,
							$db->escape($yaxis_field->token),
							$db->escape($yaxis_field->token),
							$primary_key
						);
					}
					
					$sql = sprintf("SELECT %s AS hits, DATE_FORMAT(FROM_UNIXTIME(%s.%s), '%s') AS histo ",
						$select_func,
						$db->escape($xaxis_field->db_table),
						$db->escape($xaxis_field->db_column),
						$date_format_mysql
					).
					str_replace('%','%%',$query_parts['join']).
					str_replace('%','%%',$query_parts['where']).
					sprintf("GROUP BY DATE_FORMAT(FROM_UNIXTIME(%s.%s), '%s') ",
						$db->escape($xaxis_field->db_table),
						$db->escape($xaxis_field->db_column),
						$date_format_mysql
					).
					'ORDER BY histo ASC'
					;
					
					$results = $db->GetArrayReader($sql);
					
					// Find the first and last date
					$xaxis_param = C4_AbstractView::findParam($xaxis_field->token, $view->getParams());
					
					if(is_array($xaxis_param)) {
						$xaxis_param = array_shift($xaxis_param);
					} else {
						$xaxis_param = null;
					}

					$current_tick = null;
					$last_tick = null;
					
					if(!empty($xaxis_param)) {
						if(2 == count($xaxis_param->value)) {
							$current_tick = strtotime($xaxis_param->value[0]);
							$last_tick = strtotime($xaxis_param->value[1]);
						}
					}
					
					$first_result = null;
					$last_result = null;
					
					if(empty($current_tick) && empty($last_tick)) {
						$last_result = end($results);
						$first_result = reset($results);
						$current_tick = strtotime($first_result['histo']);
						$last_tick = strtotime($last_result['histo']);
					}
					
					// Fill in time gaps from no data
						
					// var_dump($current_tick, $last_tick, $xaxis_tick);
					// var_dump($results);

					$array = array();
					
					foreach($results as $v) {
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
							$current_tick = strtotime(date($date_format_php, $current_tick));
							break;
							
						// Always Monday
						case 'week':
							$current_tick = strtotime('Monday this week', $current_tick);
							break;
					}
						
					do {
						$histo = date($date_format_php, $current_tick);
						// var_dump($histo);

						$value = (isset($results[$histo])) ? $results[$histo] : 0;
						
						$yaxis_label = ((int) $value != $value) ? sprintf("%0.2f", $value) : sprintf("%d", $value);
						
						if(isset($params['yaxis_format'])) {
							$yaxis_label = DevblocksPlatform::formatNumberAs($yaxis_label, @$params['yaxis_format']);
						}
						
						$data[$histo] = array(
							'x' => $histo,
							'y' => (float)$value,
							'x_label' => date($date_label, $current_tick),
							'y_label' => $yaxis_label,
						);

						$current_tick = strtotime(sprintf('+1 %s', $xaxis_tick), $current_tick);

					} while($current_tick <= $last_tick);
						
					unset($results);
					break;

				// x-axis is a number
				case Model_CustomField::TYPE_NUMBER:
					switch($xaxis_field->token) {
						case '_id':
							$order_by = null;
							$group_by = sprintf("GROUP BY %s.id%s ",
								str_replace('%','%%',$query_parts['primary_table']),
								($yaxis_field ? sprintf(", %s.%s", $db->escape($yaxis_field->db_table), $db->escape($yaxis_field->db_column)) : '')
							);
							
							if(empty($order_by))
								$order_by = sprintf("ORDER BY %s.id ", str_replace('%','%%',$db->escape($query_parts['primary_table'])));
							
							break;

						default:
							$group_by = sprintf("GROUP BY %s.%s",
								$db->escape($xaxis_field->db_table),
								$db->escape($xaxis_field->db_column),
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
								$db->escape($yaxis_field->db_table),
								$db->escape($yaxis_field->db_column),
							);
							break;
								
						case 'min':
							$select_func = sprintf("MIN(%s.%s)",
								$db->escape($yaxis_field->db_table),
								$db->escape($yaxis_field->db_column)
							);
							break;
								
						case 'max':
							$select_func = sprintf("MAX(%s.%s)",
								$db->escape($yaxis_field->db_table),
								$db->escape($yaxis_field->db_column)
							);
							break;
								
						case 'value':
							$select_func = sprintf("%s.%s",
								$db->escape($yaxis_field->db_table),
								$db->escape($yaxis_field->db_column)
							);
							break;
								
						default:
						case 'count':
							$select_func = 'COUNT(*)';
							break;
					}
					
					// Scatterplots ignore histograms if not aggregate
					if($widget->extension_id == 'core.workspace.widget.scatterplot') {
						if(!str_contains($select_func, '(')) {
							$group_by = null;
						}
					}
					
					// INNER JOIN the x-axis cfield
					if($xaxis_field && DevblocksPlatform::strStartsWith($xaxis_field->token, 'cf_')) {
						$xaxis_cfield_id = substr($xaxis_field->token, 3);
						$query_parts['join'] .= sprintf("INNER JOIN (SELECT field_value, context_id FROM %s WHERE field_id = %d) AS %s ON (%s.context_id=%s) ",
							'custom_field_numbervalue',
							$xaxis_cfield_id,
							$db->escape($xaxis_field->token),
							$db->escape($xaxis_field->token),
							$primary_key
						);
					}
					
					// INNER JOIN the y-axis cfield
					if($yaxis_field && DevblocksPlatform::strStartsWith($yaxis_field->token, 'cf_') && !($xaxis_field && $xaxis_field->token == $yaxis_field->token)) {
						$yaxis_cfield_id = substr($yaxis_field->token, 3);
						$query_parts['join'] .= sprintf("INNER JOIN (SELECT field_value, context_id FROM %s WHERE field_id = %d) AS %s ON (%s.context_id=%s) ",
							'custom_field_numbervalue',
							$yaxis_cfield_id,
							$db->escape($yaxis_field->token),
							$db->escape($yaxis_field->token),
							$primary_key
						);
					}
					
					$sql = sprintf("SELECT %s AS yaxis, %s.%s AS xaxis " .
						str_replace('%','%%',$query_parts['join']).
						str_replace('%','%%',$query_parts['where']).
						"%s ".
						"%s ",
						$select_func,
						$db->escape($xaxis_field->db_table),
						$db->escape($xaxis_field->db_column),
						$group_by,
						$order_by
					);
					
					$results = $db->GetArrayReader($sql);
					$data = [];
					
					$counter = 0;
					
					foreach($results as $result) {
						$x = ($params['xaxis_field'] == '_id') ? $counter++ : (float)$result['xaxis'];
						
						$xaxis_label = DevblocksPlatform::formatNumberAs((float)$result['xaxis'], @$params['xaxis_format']);
						$yaxis_label = DevblocksPlatform::formatNumberAs((float)$result['yaxis'], @$params['yaxis_format']);
						
						$data[$x] = array(
							'x' => $x,
							'y' => (float)$result['yaxis'],
							'x_label' => $xaxis_label,
							'y_label' => $yaxis_label,
						);
					}

					unset($results);
					break;
			}
		}
		
		$params['data'] = $data;
		unset($data);
		
		return $params;
	}
};

class WorkspaceWidgetDatasource_Manual extends Extension_WorkspaceWidgetDatasource {
	function renderConfig(Model_WorkspaceWidget $widget, $params=array(), $params_prefix=null) {
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('widget', $widget);
		$tpl->assign('params', $params);
		$tpl->assign('params_prefix', $params);
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/datasources/config_manual_metric.tpl');
	}
	
	function getData(Model_WorkspaceWidget $widget, array $params=array(), $params_prefix=null) {
		$metric_value = $params['metric_value'];
		$metric_value = floatval(str_replace(',','', $metric_value));
		$params['metric_value'] = $metric_value;
		return $params;
	}
};

class WorkspaceWidgetDatasource_DataQueryMetric extends Extension_WorkspaceWidgetDatasource {
	function renderConfig(Model_WorkspaceWidget $widget, $params=[], $params_prefix=null) {
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('widget', $widget);
		$tpl->assign('params', $params);
		$tpl->assign('params_prefix', $params);
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/datasources/config_data_query.tpl');
	}
	
	function getData(Model_WorkspaceWidget $widget, array $params=[], $params_prefix=null) {
		$data = DevblocksPlatform::services()->data();
		
		$data_query = DevblocksPlatform::importGPC($params['data_query'] ?? null, 'string', '');
		
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$dict = DevblocksDictionaryDelegate::instance([
			'current_worker__context' => CerberusContexts::CONTEXT_WORKER,
			'current_worker_id' => $active_worker->id,
			'widget__context' => CerberusContexts::CONTEXT_WORKSPACE_WIDGET,
			'widget_id' => $widget->id,
		]);
		
		$widget->_loadDashboardPrefsForWorker($active_worker, $dict);
		
		$query = $tpl_builder->build($data_query, $dict);
		
		if(false === ($results = $data->executeQuery($query, [])))
			return [];
		
		$type = $results['_']['type'] ?? null;
		$format = $results['_']['format'] ?? null;
		$data = $results['data'];
		
		switch($type) {
			case 'worklist.metrics':
				switch($format) {
					case 'table':
						$params['metric_value'] = ($data['rows'][0]['value'] ?? null) ?: 0;
						break;
				}
				break;
		}
		
		return $params;
	}
};
