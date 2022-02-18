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
				
			case 'table':
			default:
				return $this->_formatDataAsTable($chart_fields, $error);
		}
	}
	
	private function _formatDataAsTable(array $chart_fields=[], &$error=null) {
		$db = DevblocksPlatform::services()->database();
		
		foreach($chart_fields as $field) {
			if(!($field instanceof DevblocksSearchCriteria))
				continue;
			
			if(in_array($field->key, ['type', 'format'])) {
				DevblocksPlatform::noop();
				
			} else {
				$error = sprintf("The parameter '%s' is unknown.", $field->key);
				return false;
			}
		}
		
		// Filter: Limit
		$limit = 0;
		
		// Filter: Workers
		
		$filter_worker_ids = [];
		$filter_worker_ids = DevblocksPlatform::sanitizeArray($filter_worker_ids, 'integer', ['unique','nonzero']);
		
		$sql = sprintf("SELECT snippet.id AS snippet_id, snippet.title AS snippet_title, SUM(metric_value.sum) AS snippet_uses ".
			"FROM metric_value ".
			"INNER JOIN snippet ON (metric_value.dim0_value_id=snippet.id) ".
			"WHERE metric_id = (SELECT id FROM metric WHERE name = 'cerb.snippet.uses') ". 
			"AND metric_value.bin BETWEEN %d AND %d ".
			"AND granularity = 86400 ".
			"%s ".
			"GROUP BY snippet.id, snippet.title ".
			"ORDER BY snippet_uses DESC ".
			"%s",
			strtotime('first day of this month -1 year 00:00:00 UTC'),
			strtotime('tomorrow UTC'),
			(!empty($filter_worker_ids) && is_array($filter_worker_ids) ? sprintf("AND metric_value.dim1_value_id IN (%s)", implode(',', $filter_worker_ids)) : ''),
			(!empty($limit) ? sprintf("LIMIT %d", $limit) : '')
		);
		
		$results = $db->GetArrayReader($sql);
		
		$results = array_map(
			function($result) {
				$result['snippet_uses'] = intval($result['snippet_uses']);
				return $result;
			},
			$results
		);
		
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
				DevblocksPlatform::noop();
				
			} else if($field->key == 'ids') {
				CerbQuickSearchLexer::getOperArrayFromTokens($field->tokens, $oper, $value);
				$chart_model['ids'] = $value;
				
			} else {
				$error = sprintf("The parameter '%s' is unknown.", $field->key);
				return false;
			}
		}
		
		// Default IDs
		
		$ts_from = strtotime('first day of this month -1 year 00:00:00 UTC');
		$ts_to = strtotime('tomorrow UTC');
		
		if(!$chart_model['ids']) {
			$sql = sprintf("SELECT dim0_value_id AS snippet_id ".
				"FROM metric_value ".
				"WHERE metric_id = (SELECT id FROM metric WHERE name = 'cerb.snippet.uses') ".
				"AND granularity = 86400 ".
				"AND bin BETWEEN %d AND %d ".
				"GROUP BY snippet_id ".
				"ORDER BY SUM(metric_value.sum) DESC ".
				"LIMIT 10",
				$ts_from,
				$ts_to
			);
			$results = $db->GetArrayReader($sql);
			$chart_model['ids'] = array_column($results, 'snippet_id');
		}
		
		$sql = sprintf("SELECT SUM(metric_value.sum) AS metric, dim0_value_id AS snippet_id, DATE_FORMAT(FROM_UNIXTIME(bin),'%%Y-%%m') AS label ".
			"FROM metric_value ".
			"WHERE metric_id = (SELECT id FROM metric WHERE name = 'cerb.snippet.uses') ".
			"AND granularity = 86400 ".
			"AND bin BETWEEN %d AND %d ".
			"%s".
			"GROUP BY label, snippet_id ".
			"ORDER BY label ",
			$ts_from,
			$ts_to,
			$chart_model['ids'] 
				? sprintf("AND dim0_value_id IN (%s) ", implode(',', $chart_model['ids']))
				: ''
		);
		
		$x_values = DevblocksPlatform::services()->date()->formatTimestamps(
			DevblocksPlatform::dateLerpArray(
				['first day of this month -1 year 00:00:00 UTC', 'tomorrow UTC'], 
				'month'
			), 
			'Y-m'
		);
		
		$results = $db->GetArrayReader($sql);
		
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