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
	
	private function _getDataSingle(Model_WorkspaceWidget $widget, array $params=array(), $params_prefix=null) {
		$series_idx = $this->_getSeriesIdxFromPrefix($params_prefix);
		
		$view_id = sprintf("widget%d_worklist%s",
			$widget->id,
			(!is_null($series_idx) ? intval($series_idx) : '')
		);
		
		if(null == ($view = Extension_WorkspaceWidget::getViewFromParams($widget, $params, $view_id)))
			return;
		
		if(false == ($context_ext = Extension_DevblocksContext::getByViewClass(get_class($view), true)))
			return false;
		
		if(false == ($dao_class = $context_ext->getDaoClass()))
			return false;
		
		if(false == ($search_class = $context_ext->getSearchClass()))
			return false;
		
		if(false == ($primary_key = $search_class::getPrimaryKey()))
			return false;
		
		$view->renderPage = 0;
		$view->renderLimit = 1;
		
		$db = DevblocksPlatform::services()->database();
		
		// We need to know what date fields we have
		$fields = $view->getFields();
		@$metric_func = $params['metric_func'];
		
		switch($metric_func) {
			case 'count':
				$metric_field = null;
				break;
				
			default:
				@$metric_field = $fields[$params['metric_field']];
				break;
		}
		
		$query_parts = $dao_class::getSearchQueryComponents([], $view->getParams());
		
		$select_func = null;
		
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
				sprintf("SELECT %s %s %s", $primary_key, $query_parts['join'], $query_parts['where'])
			);
			
		} else {
			if($metric_field)
				$select_query = sprintf("%s.%s",
					$metric_field->db_table,
					$metric_field->db_column
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
	
	function getData(Model_WorkspaceWidget $widget, array $params=array(), $params_prefix=null) {
		return $this->_getDataSeries($widget, $params, $params_prefix);
	}
	
	private function _getDataSeries(Model_WorkspaceWidget $widget, array $params=array(), $params_prefix=null) {
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
		
		@$view_context = $params['worklist_model']['context'];
		
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
		
		$data = array();
		
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
			@$yaxis_func = $params['yaxis_func'];
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
					@$xaxis_tick = $params['xaxis_tick'];
						
					if(empty($xaxis_tick))
						$xaxis_tick = 'day';
						
					switch($xaxis_tick) {
						case 'hour':
							$date_format_mysql = '%Y-%m-%d %H:00';
							$date_format_php = '%Y-%m-%d %H:00';
							$date_label = $date_format_php;
							break;
								
						default:
						case 'day':
							$date_format_mysql = '%Y-%m-%d';
							$date_format_php = '%Y-%m-%d';
							$date_label = $date_format_php;
							break;
								
						case 'week':
							$date_format_mysql = '%xW%v';
							$date_format_php = '%YW%W';
							$date_label = $date_format_php;
							break;
								
						case 'month':
							$date_format_mysql = '%Y-%m';
							$date_format_php = '%Y-%m';
							$date_label = '%b %Y';
							break;
								
						case 'year':
							$date_format_mysql = '%Y-01-01';
							$date_format_php = '%Y-01-01';
							$date_label = '%Y';
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
							$select_func = sprintf("%s.%s",
								$yaxis_field->db_table,
								$yaxis_field->db_column
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
							$xaxis_field->token,
							$xaxis_field->token,
							$primary_key
						);
					}
					
					// INNER JOIN the y-axis cfield
					if($yaxis_field && DevblocksPlatform::strStartsWith($yaxis_field->token, 'cf_') && !($xaxis_field && $xaxis_field->token == $yaxis_field->token)) {
						$yaxis_cfield_id = substr($yaxis_field->token, 3);
						$query_parts['join'] .= sprintf("INNER JOIN (SELECT field_value, context_id FROM %s WHERE field_id = %d) AS %s ON (%s.context_id=%s) ",
							'custom_field_numbervalue',
							$yaxis_cfield_id,
							$yaxis_field->token,
							$yaxis_field->token,
							$primary_key
						);
					}
					
					$sql = sprintf("SELECT %s AS hits, DATE_FORMAT(FROM_UNIXTIME(%s.%s), '%s') AS histo ",
						$select_func,
						$xaxis_field->db_table,
						$xaxis_field->db_column,
						$date_format_mysql
					).
					str_replace('%','%%',$query_parts['join']).
					str_replace('%','%%',$query_parts['where']).
					sprintf("GROUP BY DATE_FORMAT(FROM_UNIXTIME(%s.%s), '%s') ",
						$xaxis_field->db_table,
						$xaxis_field->db_column,
						$date_format_mysql
					).
					'ORDER BY histo ASC'
					;
					
					$results = $db->GetArrayReader($sql);
					
					// Find the first and last date
					@$xaxis_param = array_shift(C4_AbstractView::findParam($xaxis_field->token, $view->getParams()));

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
							$current_tick = strtotime(strftime($date_format_php, $current_tick));
							break;
							
						// Always Monday
						case 'week':
							$current_tick = strtotime('Monday this week', $current_tick);
							break;
					}
						
					do {
						$histo = strftime($date_format_php, $current_tick);
						// var_dump($histo);

						$value = (isset($results[$histo])) ? $results[$histo] : 0;
						
						$yaxis_label = ((int) $value != $value) ? sprintf("%0.2f", $value) : sprintf("%d", $value);
						
						if(isset($params['yaxis_format'])) {
							$yaxis_label = DevblocksPlatform::formatNumberAs($yaxis_label, @$params['yaxis_format']);
						}
						
						$data[$histo] = array(
							'x' => $histo,
							'y' => (float)$value,
							'x_label' => strftime($date_label, $current_tick),
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
								($yaxis_field ? sprintf(", %s.%s", $yaxis_field->db_table, $yaxis_field->db_column) : '')
							);
							
							if(empty($order_by))
								$order_by = sprintf("ORDER BY %s.id ", str_replace('%','%%',$query_parts['primary_table']));
							
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
							$select_func = sprintf("%s.%s",
								$yaxis_field->db_table,
								$yaxis_field->db_column
							);
							break;
								
						default:
						case 'count':
							$select_func = 'COUNT(*)';
							break;
					}
					
					// Scatterplots ignore histograms if not aggregate
					if($widget->extension_id == 'core.workspace.widget.scatterplot') {
						if(false === strpos($select_func, '(')) {
							$group_by = null;
						}
					}
					
					// INNER JOIN the x-axis cfield
					if($xaxis_field && DevblocksPlatform::strStartsWith($xaxis_field->token, 'cf_')) {
						$xaxis_cfield_id = substr($xaxis_field->token, 3);
						$query_parts['join'] .= sprintf("INNER JOIN (SELECT field_value, context_id FROM %s WHERE field_id = %d) AS %s ON (%s.context_id=%s) ",
							'custom_field_numbervalue',
							$xaxis_cfield_id,
							$xaxis_field->token,
							$xaxis_field->token,
							$primary_key
						);
					}
					
					// INNER JOIN the y-axis cfield
					if($yaxis_field && DevblocksPlatform::strStartsWith($yaxis_field->token, 'cf_') && !($xaxis_field && $xaxis_field->token == $yaxis_field->token)) {
						$yaxis_cfield_id = substr($yaxis_field->token, 3);
						$query_parts['join'] .= sprintf("INNER JOIN (SELECT field_value, context_id FROM %s WHERE field_id = %d) AS %s ON (%s.context_id=%s) ",
							'custom_field_numbervalue',
							$yaxis_cfield_id,
							$yaxis_field->token,
							$yaxis_field->token,
							$primary_key
						);
					}
					
					$sql = sprintf("SELECT %s AS yaxis, %s.%s AS xaxis " .
						str_replace('%','%%',$query_parts['join']).
						str_replace('%','%%',$query_parts['where']).
						"%s ".
						"%s ",
						$select_func,
						$xaxis_field->db_table,
						$xaxis_field->db_column,
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
		
		@$data_query = DevblocksPlatform::importGPC($params['data_query'], 'string', '');
		
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
		
		if(false === ($results = $data->executeQuery($query)))
			return [];
		
		@$type = $results['_']['type'];
		@$format = $results['_']['format'];
		$data = $results['data'];
		
		switch($type) {
			case 'worklist.metrics':
				switch($format) {
					case 'table':
						$params['metric_value'] = @$data['rows'][0]['value'] ?: 0;
						break;
				}
				break;
		}
		
		return $params;
	}
};

class WorkspaceWidgetDatasource_BotBehavior extends Extension_WorkspaceWidgetDatasource {
	function renderConfig(Model_WorkspaceWidget $widget, $params=array(), $params_prefix=null) {
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('widget', $widget);
		$tpl->assign('params', $params);
		$tpl->assign('params_prefix', $params);
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/datasources/config_bot_behavior.tpl');
	}

	function getData(Model_WorkspaceWidget $widget, array $params=array(), $params_prefix=null) {
		@$behavior_id = $widget->params['behavior_id'];
		
		if(!$behavior_id 
			|| false == ($widget_behavior = DAO_TriggerEvent::get($behavior_id))
			|| $widget_behavior->event_point != Event_DashboardWidgetGetMetric::ID
			) {
			return false;
		}
		
		$actions = [];
		
		$event_model = new Model_DevblocksEvent(
			Event_DashboardWidgetGetMetric::ID,
			array(
				'widget' => $widget,
				'actions' => &$actions,
			)
		);
		
		if(false == ($event = $widget_behavior->getEvent()))
			return;
			
		$event->setEvent($event_model, $widget_behavior);
		
		$values = $event->getValues();
		
		$dict = DevblocksDictionaryDelegate::instance($values);
		
		$widget_behavior->runDecisionTree($dict, false, $event);
		
		$metric_value = 0;
		
		foreach($actions as $action) {
			switch($action['_action']) {
				case 'return_value':
					$metric_value = @$action['value'];
					break;
			}
		}
		
		$metric_value = floatval(str_replace(',','', $metric_value));
		$params['metric_value'] = $metric_value;
		return $params;
	}
};

class WorkspaceWidgetDatasource_URL extends Extension_WorkspaceWidgetDatasource {
	function renderConfig(Model_WorkspaceWidget $widget, $params=array(), $params_prefix=null) {
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('widget', $widget);
		$tpl->assign('params', $params);
		$tpl->assign('params_prefix', $params);
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/datasources/config_url.tpl');
	}
	
	function getData(Model_WorkspaceWidget $widget, array $params=array(), $params_prefix=null) {
		$cache = DevblocksPlatform::services()->cache();
		
		@$url = $params['url'];
		
		@$cache_mins = $params['url_cache_mins'];
		$cache_mins = max(1, intval($cache_mins));
		
		$cache_key = sprintf("widget%d_datasource", $widget->id);
		
		if(true || null === ($data = $cache->load($cache_key))) {
			$ch = DevblocksPlatform::curlInit($url);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			$raw_data = DevblocksPlatform::curlExec($ch);
			$info = curl_getinfo($ch);
			
			//@$status = $info['http_code'];
			//@$content_type = DevblocksPlatform::strLower($info['content_type']);
			
			$data = array(
				'raw_data' => $raw_data,
				'info' => $info,
			);
			
			DAO_WorkspaceWidget::update($widget->id, array(
				DAO_WorkspaceWidget::UPDATED_AT => time(),
			), DevblocksORMHelper::OPT_UPDATE_NO_READ_AFTER_WRITE);
			
			$cache->save($data, $cache_key, array(), $cache_mins*60);
		}
	
		switch($widget->extension_id) {
			case 'core.workspace.widget.chart':
			case 'core.workspace.widget.pie_chart':
			case 'core.workspace.widget.scatterplot':
				return $this->_getDataSeries($widget, $params, $data);
				break;
				
			case 'core.workspace.widget.counter':
			case 'core.workspace.widget.gauge':
				return $this->_getDataSingle($widget, $params, $data);
				break;
		}
	}
	
	private function _getDataSeries($widget, $params=array(), $data=null) {
		if(!is_array($data) || !isset($data['info']) || !isset($data['raw_data']))
			return;
		
		if(!isset($params['url_format']) || empty($params['url_format'])) {
			$content_type = $data['info']['content_type'];
		} else {
			$content_type = $params['url_format'];
		}
			
		$raw_data = $data['raw_data'];
		
		if(empty($raw_data) || empty($content_type)) {
			return;
		}
		
		$url_format = '';
		
		switch($content_type) {
			case 'application/json':
			case 'text/json':
				$url_format = 'json';
				break;
				
			case 'text/xml':
				$url_format = 'xml';
				break;
				
			case 'text/csv':
				$url_format = 'csv';
				break;
				
			default:
				return;
				break;
		}
		
		switch($url_format) {
			case 'json':
				if(false != (@$json = json_decode($raw_data, true))) {
					$results = array();
					
					if(is_array($json))
					foreach($json as $object) {
						if(!isset($object['value']))
							continue;
						
						$result = array();
						
						if(isset($object['value']))
							$result['metric_value'] = floatval($object['value']);
		
						if(isset($object['label']))
							$result['metric_label'] = $object['label'];
						
						/*
						if(isset($object['type']))
							$result['metric_type'] = $object['type'];
		
						if(isset($object['prefix']))
							$result['metric_prefix'] = $object['prefix'];
						
						if(isset($object['suffix']))
							$result['metric_suffix'] = $object['suffix'];
						*/
						
						$results[] = $result;
					}
					
					$params['data'] = $results;
				}
				break;
				
			case 'xml':
				if(null != ($xml = simplexml_load_string($raw_data))) {
					$results = array();
					
					foreach($xml as $object) {
						if(!isset($object->value))
							continue;
						
						$result = array();
						
						if(isset($object->value))
							$result['metric_value'] = floatval($object->value);
		
						if(isset($object->label))
							$result['metric_label'] = (string)$object->label;
						
						/*
						if(isset($object->type))
							$result['metric_type'] = (string)$object->type;
		
						if(isset($object->prefix))
							$result['metric_prefix'] = (string)$object->prefix;
						
						if(isset($object->suffix))
							$result['metric_suffix'] = (string)$object->suffix;
						*/
						
						$results[] = $result;
					}
					
					$params['data'] = $results;
				}
				break;
				
			case 'csv':
				$fp = DevblocksPlatform::getTempFile();
				fwrite($fp, $raw_data, strlen($raw_data));
				
				$results = array();
				
				fseek($fp, 0);
				
				while(false != ($row = fgetcsv($fp, 0, ',', '"'))) {
					if(is_array($row) && count($row) >= 1) {
						$result['metric_value'] = floatval($row[0]);
						$result['metric_label'] = @$row[1] ?: '';
						$results[] = $result;
					}
				}
				
				fclose($fp);
				
				$params['data'] = $results;
				break;
		}
		
		return $params;
	}
	
	private function _getDataSingle($widget, $params=array(), $data=null) {
		if(!is_array($data) || !isset($data['info']) || !isset($data['raw_data']))
			return;
		
		if(!isset($params['url_format']) || empty($params['url_format'])) {
			$content_type = $data['info']['content_type'];
		} else {
			$content_type = $params['url_format'];
		}
			
		$raw_data = $data['raw_data'];
		
		if(empty($raw_data) || empty($content_type)) {
			return;
		}
		
		$url_format = '';
		
		switch($content_type) {
			case 'application/json':
			case 'text/json':
				$url_format = 'json';
				break;
				
			case 'text/xml':
				$url_format = 'xml';
				break;
				
			default:
			case 'text/plain':
				$url_format = 'text';
				break;
		}
		
		switch($url_format) {
			case 'json':
				if(false != (@$json = json_decode($raw_data, true))) {
					if(isset($json['value']))
						$params['metric_value'] = floatval($json['value']);

					if(isset($json['label']))
						$params['metric_label'] = $json['label'];
	
					if((isset($params['metric_type']) && !empty($params['metric_type'])) && isset($json['type']))
						$params['metric_type'] = $json['type'];
	
					if((isset($params['metric_prefix']) && !empty($params['metric_prefix'])) && isset($json['prefix']))
						$params['metric_prefix'] = $json['prefix'];
					
					if((isset($params['metric_suffix']) && !empty($params['metric_suffix'])) && isset($json['suffix']))
						$params['metric_suffix'] = $json['suffix'];
				}
				break;
				
			case 'xml':
				if(null != ($xml = simplexml_load_string($raw_data))) {
					if(isset($xml->value))
						$params['metric_value'] = (float)$xml->value;
	
					if((isset($params['metric_type']) && !empty($params['metric_type'])) && isset($xml->type))
						$params['metric_type'] = (string) $xml->type;
	
					if((isset($params['metric_prefix']) && !empty($params['metric_prefix'])) && isset($xml->prefix))
						$params['metric_prefix'] = (string) $xml->prefix;
					
					if((isset($params['metric_suffix']) && !empty($params['metric_suffix'])) && isset($xml->suffix))
						$params['metric_suffix'] = (string) $xml->suffix;
				}
				break;
				
			default:
			case 'text':
				$params['metric_value'] = floatval($raw_data);
				break;
		}
		
		return $params;
	}
};