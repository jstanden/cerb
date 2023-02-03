<?php
class _DevblocksDataProviderUsageBotBehaviors extends _DevblocksDataProvider {
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
			default:
			case 'table':
				return $this->_formatDataAsTable($chart_fields, $error);
				
			case 'timeseries':
				return $this->_formatDataAsTimeSeries($chart_fields, $error);
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
		
		$start = 'first day of this month -1 year';
		@$start_time = strtotime($start);
		$start_time -= $start_time % 86400;
		
		$end = 'now';
		@$end_time = strtotime($end);
		$end_time -= $end_time % 86400;
		
		$sort_by = 'uses';
		if(!in_array($sort_by, ['uses', 'avg_elapsed_ms', 'elapsed_ms', 'va_name', 'va_owner', 'event']))
			$sort_by = 'uses';

		// Scope
		
		$event_mfts = Extension_DevblocksEvent::getAll(false);
		$behaviors = DAO_TriggerEvent::getAll();
		$bots = DAO_Bot::getAll();

		// Data
		
		$sql = sprintf("SELECT 'invocations' AS type, dim0_value_id AS trigger_id, sum(sum) AS stat ".
			"from metric_value ".
			"where metric_id = (SELECT id FROM metric WHERE name = 'cerb.behavior.invocations') ".
			"AND metric_value.granularity = 86400 ".
			"AND (metric_value.bin BETWEEN %d AND %d) ".
			"group by dim0_value_id ".
			"UNION ALL ".
			"SELECT 'duration' AS type, dim0_value_id AS trigger_id, sum(sum) AS stat ".
			"from metric_value ".
			"where metric_id = (SELECT id FROM metric WHERE name = 'cerb.behavior.duration') ".
			"AND metric_value.granularity = 86400 ".
			"AND (metric_value.bin BETWEEN %d AND %d) ".
			"group by dim0_value_id",
			$start_time,
			$end_time,
			$start_time,
			$end_time
		);
		
		$results = $db->GetArrayReader($sql);
		
		$stats = [];
		
		foreach($results as $result) {
			if(!array_key_exists($result['trigger_id'], $stats)) {
				// Ignore invalid or previously deleted behaviors
				if(!array_key_exists($result['trigger_id'], $behaviors))
					continue;
				
				$behavior = $behaviors[$result['trigger_id']]; /* @var $behavior Model_TriggerEvent */
				
				$stats[$result['trigger_id']] = [
					'behavior_id' => $result['trigger_id'],
					'behavior_name' => $behavior->title,
					'bot_id' => $behavior->bot_id,
					'event_point' => $behavior->event_point,
					'uses' => 0,
					'elapsed_ms' => 0,
				];
			}
			
			if($result['type'] == 'invocations') {
				$stats[$result['trigger_id']]['uses'] = $result['stat'];
			} else {
				$stats[$result['trigger_id']]['elapsed_ms'] = $result['stat'];
			}
		}
		
		foreach($stats as &$stat) {
			$stat['uses'] = intval($stat['uses']);
			$stat['elapsed_ms'] = intval($stat['elapsed_ms']);
			
			// Avg. Runtime
			
			$stat['avg_elapsed_ms'] = !empty($stat['uses']) ? intval($stat['elapsed_ms'] / $stat['uses']) : $stat['elapsed_ms'];
			
			// Event
			
			@$event_mft = $event_mfts[$stat['event_point']];
			$stat['event'] = !empty($event_mft) ? $event_mft->name : '';
			
			// Bot
			
			if(!(@$bot = $bots[$stat['bot_id']]))
				continue;
			
			// Owner

			$meta = $bot->getOwnerMeta();
			
			$stat['bot_id'] = $bot->id;
			$stat['bot_name'] = $bot->name;
			$stat['bot_owner'] = sprintf("%s%s", $meta['context_ext']->manifest->name, (!empty($meta['name']) ? (': '.$meta['name']) : ''));
		}
		
		// Sort
		
		$sort_asc = false;
		switch($sort_by) {
			case 'event':
			case 'bot_name':
			case 'bot_owner':
				$sort_asc = true;
				break;
		}
		
		DevblocksPlatform::sortObjects($stats, sprintf('[%s]', $sort_by), $sort_asc);
		
		return [
			'data' => [
				'columns' => [
					'bot_name' => [
						'label' => DevblocksPlatform::translateCapitalized('common.bot'),
						'type' => 'context',
						'type_options' => [
							'context' => CerberusContexts::CONTEXT_BOT,
							'context_id_key' => 'bot_id',
						]
					],
					'behavior_name' => [
						'label' => DevblocksPlatform::translateCapitalized('common.behavior'),
						'type' => 'context',
						'type_options' => [
							'context' => CerberusContexts::CONTEXT_BEHAVIOR,
							'context_id_key' => 'behavior_id',
						]
					],
					'event' => [
						'label' => DevblocksPlatform::translateCapitalized('common.event'),
						'type' => Model_CustomField::TYPE_SINGLE_LINE,
					],
					'uses' => [
						'label' => '# Uses',
						'type' => Model_CustomField::TYPE_NUMBER,
					],
					'elapsed_ms' => [
						'label' => 'Total Runtime (ms)',
						'type' => Model_CustomField::TYPE_NUMBER,
					],
					'avg_elapsed_ms' => [
						'label' => 'Avg. Runtime (ms)',
						'type' => Model_CustomField::TYPE_NUMBER,
					]
				],
				'rows' => $stats,
			],
			'_' => [
				'type' => 'usage.behaviors',
				'format' => 'table'
			]
		];
	}
	
	private function _formatDataAsTimeSeries(array $chart_fields=[], &$error=null) {
		$db = DevblocksPlatform::services()->database();
		
		$chart_model = [
			'type' => 'usage.behaviors',
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
			$sql = sprintf("SELECT dim0_value_id AS behavior_id ".
				"FROM metric_value ".
				"WHERE metric_id = (SELECT id FROM metric WHERE name = 'cerb.behavior.invocations') ".
				"AND granularity = 86400 ".
				"AND bin BETWEEN %d AND %d ".
				"GROUP BY behavior_id ".
				"ORDER BY SUM(sum) DESC ".
				"LIMIT 10",
				strtotime('first day of this month -1 year 00:00:00 UTC'),
				strtotime('tomorrow UTC')
			);
			$results = $db->GetArrayReader($sql);
			$chart_model['ids'] = array_column($results, 'behavior_id');
		}
		
		$sql = sprintf("SELECT SUM(sum) AS metric, dim0_value_id AS behavior_id, DATE_FORMAT(FROM_UNIXTIME(bin),'%%Y-%%m') AS label ".
			"FROM metric_value ".
			"WHERE metric_id = (SELECT id FROM metric WHERE name = 'cerb.behavior.invocations') ".
			"AND granularity = 86400 ".
			"AND bin BETWEEN %d AND %d ".
			"%s".
			"GROUP BY label, dim0_value_id ".
			"ORDER BY label ",
			strtotime('first day of this month -1 year 00:00:00 UTC'),
			strtotime('tomorrow UTC'),
			$chart_model['ids'] 
				? sprintf("AND dim0_value_id IN (%s) ", implode(',', $chart_model['ids']))
				: ''
		);
		
		$results = $db->GetArrayReader($sql);
		
		$output = [
			'data' => [
				'ts' => [],
			],
			'_' => [
				'type' => 'usage.behaviors',
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
			$series_key = $result['behavior_id'];
			
			if(!array_key_exists($series_key, $series)) {
				$series[$series_key] = array_fill_keys($x_values, 0);
			}
			
			$series[$series_key][$result['label']] = floatval($result['metric']);
		}
		
		$behaviors = DAO_TriggerEvent::getIds(array_keys($series));
		
		foreach($series as $behavior_id => $y_values) {
			if(array_key_exists($behavior_id, $behaviors)) {
				$behavior_name = $behaviors[$behavior_id]->title ?: '(no name)';
			} else {
				$behavior_name = '(no name)';
			}
			
			$output['data'][$behavior_name] = array_values($y_values);
		}
		
		return $output;
	}
};