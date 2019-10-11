<?php
class _DevblocksDataProviderUsageSnippets extends _DevblocksDataProvider {
	function getSuggestions($type, array $params=[]) {
		return [
			'' => [
				'format:',
			],
			'format:' => [
				'table',
				'timeseries',
			]
		];
	}
	
	function getData($query, $chart_fields, &$error=null, array $options=[]) {
		$format = 'table';
		
		foreach($chart_fields as $field) {
			if(!($field instanceof DevblocksSearchCriteria))
				continue;
			
			$oper = null;
			
			if($field->key == 'format') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $format);
			}
		}
		
		switch($format) {
			case 'timeseries':
				return $this->_formatDataAsTimeSeries($chart_fields, $error);
				break;
				
			case 'table':
			default:
				return $this->_formatDataAsTable($chart_fields, $error);
				break;
		}
	}
	
	private function _formatDataAsTable(array $chart_fields=[], &$error=null) {
		$db = DevblocksPlatform::services()->database();
		
		/*
		$chart_model = [
			'type' => 'usage.snippets',
			'format' => 'table',
		];
		*/
		
		foreach($chart_fields as $field) {
			if(!($field instanceof DevblocksSearchCriteria))
				continue;
			
			if(in_array($field->key, ['type', 'format'])) {
				// Do nothing
				
			} else {
				$error = sprintf("The parameter '%s' is unknown.", $field->key);
				return false;
			}
		}
		
		// Filter: Start + End
		
		$start = 'first day of this month -1 year';
		@$start_time = strtotime($start);
		$start_time -= $start_time % 86400;
		
		$end = 'now';
		@$end_time = strtotime($end);
		$end_time -= $end_time % 86400;

		// Filter: Limit
		$limit = 0;
		
		// Filter: Workers
		
		$filter_worker_ids = [];
		$filter_worker_ids = DevblocksPlatform::sanitizeArray($filter_worker_ids, 'integer', ['unique','nonzero']);
		
		// [TODO] Return timestamp data
		
		$sql = sprintf("SELECT snippet.id AS snippet_id, snippet.title AS snippet_title, SUM(snippet_use_history.uses) AS snippet_uses ".
			"FROM snippet_use_history ".
			"INNER JOIN snippet ON (snippet_use_history.snippet_id=snippet.id) ".
			"WHERE snippet_use_history.ts_day BETWEEN %d AND %d ".
			"%s ".
			"GROUP BY snippet.id, snippet.title ".
			"ORDER BY snippet_uses %%s ".
			"%s",
			$start_time,
			$end_time,
			(!empty($filter_worker_ids) && is_array($filter_worker_ids) ? sprintf("AND snippet_use_history.worker_id IN (%s)", implode(',', $filter_worker_ids)) : ''),
			(!empty($limit) ? sprintf("LIMIT %d", $limit) : '')
		);
		
		// [TODO] sort option
		if(true) {
			$results = $db->GetArraySlave(sprintf($sql, 'DESC'));
		} else {
			$results = $db->GetArraySlave(sprintf($sql, 'ASC'));
		}
		
		return [
			'data' => [
				'columns' => [
					'snippet_title' => [
						'label' => 'Name',
						'type' => 'context',
						'type_options' => [
							'context' => CerberusContexts::CONTEXT_SNIPPET,
							'context_id_key' => 'snippet_id',
						]
					],
					'snippet_uses' => [
						'label' => '# Uses',
						'type' => Model_CustomField::TYPE_NUMBER,
					]
				],
				'rows' => $results,
			],
			'_' => [
				'type' => 'usage.snippets',
				'format' => 'table'
			]
		];
	}
	
	private function _formatDataAsTimeSeries(array $chart_fields=[], &$error=null) {
		$db = DevblocksPlatform::services()->database();
		
		$chart_model = [
			'type' => 'usage.snippets',
			'format' => 'timeseries',
			'ids' => [],
		];
		
		foreach($chart_fields as $field) {
			if(!($field instanceof DevblocksSearchCriteria))
				continue;
			
			$oper = $value = null;
			
			if(in_array($field->key, ['type', 'format'])) {
				// Do nothing
				
			} else if($field->key == 'ids') {
				CerbQuickSearchLexer::getOperArrayFromTokens($field->tokens, $oper, $value);
				$chart_model['ids'] = $value;
				
			} else {
				$error = sprintf("The parameter '%s' is unknown.", $field->key);
				return false;
			}
		}
		
		// Default IDs
		
		if(!$chart_model['ids']) {
			$sql = sprintf("SELECT snippet_id ".
				"FROM snippet_use_history ".
				"WHERE ts_day BETWEEN %d AND %d ".
				"GROUP BY snippet_id ".
				"ORDER BY COUNT(*) DESC ".
				"LIMIT 10",
				strtotime('first day of this month -1 year 00:00:00'),
				strtotime('today 00:00:00')
			);
			$results = $db->GetArraySlave($sql);
			$chart_model['ids'] = array_column($results, 'snippet_id');
		}
		
		// [TODO] Pick a range
		// [TODO] Pick a metric
		// [TODO] Pick a granularity on date
		// [TODO] Pick specific snippets
		// [TODO] Pick specific workers
		// [TODO] Top-N snippets
		
		$sql = sprintf("SELECT SUM(uses) AS metric, snippet_id, DATE_FORMAT(FROM_UNIXTIME(ts_day),'%%Y-%%m') AS label ".
			"FROM snippet_use_history ".
			"WHERE ts_day BETWEEN %d AND %d ".
			"%s".
			"GROUP BY label, snippet_id ".
			"ORDER BY label ",
			strtotime('first day of this month -1 year 00:00:00'),
			strtotime('today 00:00:00'),
			$chart_model['ids'] 
				? sprintf("AND snippet_id IN (%s) ", implode(',', $chart_model['ids']))
				: ''
		);
		
		$results = $db->GetArraySlave($sql);
		
		$output = [
			'data' => [
				'ts' => [],
			],
			'_' => [
				'type' => 'usage.snippets',
				'format' => 'timeseries',
				'format_params' => [
					'xaxis_key' => 'ts',
					'xaxis_step' => 'month',
					'xaxis_format' => '%Y-%m',
				]
			]
		];
		
		$x_values = array_values(array_unique(array_column($results, 'label')));
		
		$output['data']['ts'] = $x_values;
		
		$series = [];
		
		foreach($results as $result) {
			$series_key = $result['snippet_id'];
			
			if(!array_key_exists($series_key, $series)) {
				$series[$series_key] = array_fill_keys($x_values, 0);
			}
			
			$series[$series_key][$result['label']] = floatval($result['metric']);
		}
		
		$snippets = DAO_Snippet::getIds(array_keys($series));
		
		foreach($series as $snippet_id => $y_values) {
			$snippet_name = @$snippets[$snippet_id]->title ?: '(no name)';
			$output['data'][$snippet_name] = array_values($y_values);
		}
		
		return $output;
	}
};