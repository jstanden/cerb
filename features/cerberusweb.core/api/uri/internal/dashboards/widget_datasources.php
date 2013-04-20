<?php
// [TODO] This could split up into two worklist datasources (metric, series).
//		This would allow reuse without having to know about the caller at all.
class WorkspaceWidgetDatasource_Worklist extends Extension_WorkspaceWidgetDatasource {
	private function _getSeriesIdxFromPrefix($params_prefix) {
		if(!empty($params_prefix) && preg_match("#\[series\]\[(\d+)\]#", $params_prefix, $matches) && count($matches) == 2) {
			return $matches[1];
		}
		
		return null;
	}
	
	function renderConfig(Model_WorkspaceWidget $widget, $params=array(), $params_prefix=null) {
		$tpl = DevblocksPlatform::getTemplateService();
		
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
		
		if(null != ($view = Extension_WorkspaceWidget::getViewFromParams($widget, $params, $view_id))) {
			C4_AbstractViewLoader::setView($view->id, $view);
		}
		
		// Worklists
		
		$context_mfts = Extension_DevblocksContext::getAll(false, 'workspace');
		$tpl->assign('context_mfts', $context_mfts);
		
		switch($widget->extension_id) {
			case 'core.workspace.widget.chart':
			case 'core.workspace.widget.scatterplot':
				$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/datasources/config_worklist_series.tpl');
				break;
				
			case 'core.workspace.widget.counter':
			case 'core.workspace.widget.gauge':
			case 'core.workspace.widget.pie_chart':
				$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/datasources/config_worklist_metric.tpl');
				break;
		}
	}
	
	function getData(Model_WorkspaceWidget $widget, array $params=array(), $params_prefix=null) {
		switch($widget->extension_id) {
			case 'core.workspace.widget.chart':
			case 'core.workspace.widget.scatterplot':
				return $this->_getDataSeries($widget, $params, $params_prefix);
				break;
				
			case 'core.workspace.widget.counter':
			case 'core.workspace.widget.gauge':
			case 'core.workspace.widget.pie_chart':
				return $this->_getDataSingle($widget, $params, $params_prefix);
				break;
		}
	}
	
	private function _getDataSingle(Model_WorkspaceWidget $widget, array $params=array(), $params_prefix=null) {
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

		if(null == ($dao_class = @$context_ext->manifest->params['dao_class']))
			return;
		
		C4_AbstractViewLoader::setView($view->id, $view);
		
		$view->renderPage = 0;
		$view->renderLimit = 1;
		
		$query_parts = $dao_class::getSearchQueryComponents(
			$view->view_columns,
			$view->getParams(),
			$view->renderSortBy,
			$view->renderSortAsc
		);
		
		$db = DevblocksPlatform::getDatabaseService();
		
		// We need to know what date fields we have
		$fields = $view->getFields();
		@$metric_func = $params['metric_func'];
		@$metric_field = $fields[$params['metric_field']];
				
		if(empty($metric_func))
			$metric_func = 'count';
		
		switch($metric_func) {
			case 'sum':
				$select_func = sprintf("SUM(%s.%s)",
					$metric_field->db_table,
					$metric_field->db_column
				);
				break;
				
			case 'avg':
				$select_func = sprintf("AVG(%s.%s)",
					$metric_field->db_table,
					$metric_field->db_column
				);
				break;
				
			case 'min':
				$select_func = sprintf("MIN(%s.%s)",
					$metric_field->db_table,
					$metric_field->db_column
				);
				break;
				
			case 'max':
				$select_func = sprintf("MAX(%s.%s)",
					$metric_field->db_table,
					$metric_field->db_column
				);
				break;
				
			default:
			case 'count':
				$select_func = 'COUNT(*)';
				break;
		}
			
		$sql = sprintf("SELECT %s AS counter_value " .
			str_replace('%','%%',$query_parts['join']).
			str_replace('%','%%',$query_parts['where']),
			$select_func
		);

		switch($widget->extension_id) {
			case 'core.workspace.widget.counter':
			case 'core.workspace.widget.gauge':
				$params['metric_value'] = $db->GetOne($sql);
				break;
		}
		
		return $params;
	}
	
	private function _getDataSeries(Model_WorkspaceWidget $widget, array $params=array(), $params_prefix=null) {
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

		if(null == ($dao_class = @$context_ext->manifest->params['dao_class']))
			continue;
			
		C4_AbstractViewLoader::setView($view->id, $view);
		
		$data = array();
			
		$view->renderPage = 0;
		$view->renderLimit = 30;
			
		$query_parts = $dao_class::getSearchQueryComponents(
			$view->view_columns,
			$view->getParams(),
			$view->renderSortBy,
			$view->renderSortAsc
		);
			
		$db = DevblocksPlatform::getDatabaseService();
			
		// We need to know what date fields we have
		$fields = $view->getFields();
		$xaxis_field = null;
		$xaxis_field_type = null;
			
		switch($params['xaxis_field']) {
			case '_id':
				$xaxis_field = new DevblocksSearchField('_id', $query_parts['primary_table'], 'id', null, Model_CustomField::TYPE_NUMBER);
				break;
					
			default:
				@$xaxis_field = $fields[$params['xaxis_field']];
				break;
		}
			
		if(!empty($xaxis_field))
			switch($xaxis_field->type) {
				case Model_CustomField::TYPE_DATE:
					// X-axis tick
					@$xaxis_tick = $params['xaxis_tick'];
						
					if(empty($xaxis_tick))
						$xaxis_tick = 'day';
						
					switch($xaxis_tick) {
						case 'hour':
							$date_format = '%Y-%m-%d %H:00';
							$date_label = $date_format;
							break;
								
						default:
						case 'day':
							$date_format = '%Y-%m-%d';
							$date_label = $date_format;
							break;
								
						case 'week':
							$date_format = '%YW%U';
							$date_label = $date_format;
							break;
								
						case 'month':
							$date_format = '%Y-%m';
							$date_label = '%b %Y';
							break;
								
						case 'year':
							$date_format = '%Y-01-01';
							$date_label = '%Y';
							break;
					}
						
					@$yaxis_func = $params['yaxis_func'];
					@$yaxis_field = $fields[$params['yaxis_field']];
						
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
					str_replace('%','%%',$query_parts['join']).
					str_replace('%','%%',$query_parts['where']).
					sprintf("GROUP BY DATE_FORMAT(FROM_UNIXTIME(%s.%s), '%s') ",
						$xaxis_field->db_table,
						$xaxis_field->db_column,
						$date_format
					).
					'ORDER BY histo ASC'
					;
					
					$results = $db->GetArray($sql);
					
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
						// var_dump($histo);

						$value = (isset($results[$histo])) ? $results[$histo] : 0;

						$data[] = array(
							'x' => $histo,
							'y' => (float)$value,
							'x_label' => strftime($date_label, $current_tick),
							'y_label' => ((int) $value != $value) ? sprintf("%0.2f", $value) : sprintf("%d", $value),
						);

						$current_tick = strtotime(sprintf('+1 %s', $xaxis_tick), $current_tick);

					} while($current_tick <= $last_tick);
						
					unset($results);
					break;

				case Model_CustomField::TYPE_NUMBER:
					@$yaxis_func = $params['yaxis_func'];
					@$yaxis_field = $fields[$params['yaxis_field']];
						
					if(empty($yaxis_func))
						$yaxis_func = 'count';
						
					switch($xaxis_field->token) {
						case '_id':
							$order_by = null;
							$group_by = sprintf("GROUP BY %s.id ", str_replace('%','%%',$query_parts['primary_table']));
								
							if(isset($fields[$view->renderSortBy])) {
								$order_by = sprintf("ORDER BY %s.%s %s",
									$fields[$view->renderSortBy]->db_table,
									$fields[$view->renderSortBy]->db_column,
									($view->renderSortAsc) ? 'ASC' : 'DESC'
								);
							}
								
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

					$results = $db->GetArray($sql);
					$data = array();

					// echo $sql,"<br>\n";
						
					$counter = 0;
						
					foreach($results as $result) {
						$x = ($params['xaxis_field'] == '_id') ? $counter++ : (float)$result['xaxis'];

						$data[] = array(
							'x' => $x,
							'y' => (float)$result['yaxis'],
							'x_label' => (float)$result['xaxis'],
							'y_label' => (float)$result['yaxis'],
						);
					}

					unset($results);
					break;
		}
		
		$params['data'] = $data;
		unset($data);
		
		return $params;
	}
};

class WorkspaceWidgetDatasource_Manual extends Extension_WorkspaceWidgetDatasource {
	function renderConfig(Model_WorkspaceWidget $widget, $params=array(), $params_prefix=null) {
		$tpl = DevblocksPlatform::getTemplateService();
		
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

class WorkspaceWidgetDatasource_URL extends Extension_WorkspaceWidgetDatasource {
	function renderConfig(Model_WorkspaceWidget $widget, $params=array(), $params_prefix=null) {
		$tpl = DevblocksPlatform::getTemplateService();
		
		$tpl->assign('widget', $widget);
		$tpl->assign('params', $params);
		$tpl->assign('params_prefix', $params);
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/datasources/config_url.tpl');
	}
	
	function getData(Model_WorkspaceWidget $widget, array $params=array(), $params_prefix=null) {
		$cache = DevblocksPlatform::getCacheService();
		
		@$url = $params['url'];
		
		@$cache_mins = $params['url_cache_mins'];
		$cache_mins = max(1, intval($cache_mins));
		
		$cache_key = sprintf("widget%d_datasource", $widget->id);
		
		if(true || null === ($data = $cache->load($cache_key))) {
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			$raw_data = curl_exec($ch);
			$info = curl_getinfo($ch);
			
			//@$status = $info['http_code'];
			@$content_type = strtolower($info['content_type']);
			
			$data = array(
				'raw_data' => $raw_data,
				'info' => $info,
			);
			
			DAO_WorkspaceWidget::update($widget->id, array(
				DAO_WorkspaceWidget::UPDATED_AT => time(),
			));
			
			$cache->save($data, $cache_key, array(), $cache_mins*60);
		}
	
		$content_type = $data['info']['content_type'];
		$raw_data = $data['raw_data'];
		
		if(empty($raw_data) || empty($content_type)) {
			// [TODO] Die...
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