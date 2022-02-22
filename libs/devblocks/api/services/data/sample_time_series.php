<?php
class _DevblocksDataProviderSampleTimeSeries extends _DevblocksDataProvider {
	function getSuggestions($type, array $params=[]) {
		return [
			'' => [
				'x.count:',
				'x.unit:',
				[
					'caption' => 'series:',
					'snippet' => "series.\${1:alias}:(\n  label:\"\${2:# Tickets}\"\n  y.min:\${3:1000}\n  y.max:\${4:100000}\n  trend:\${5:random}\n)",
					'suppress_autocomplete' => true,
				],
				'format:',
			],
			'series.*:' => [
				'' => [
					'label:',
					'y.min:',
					'y.max:',
					'trend:',
				],
				'label:' => [],
				'y.min:' => [],
				'y.max:' => [],
				'trend:' => [
					'down',
					'up',
					'random',
				],
			],
			'x.count:' => [],
			'x.unit:' => [
				'days',
				'weeks',
				'months',
				'years',
			],
			'format:' => [
				'timeseries',
			]
		];
	}
	
	function getData($query, $chart_fields, &$error=null, array $options=[]) {
		
		$chart_model = [
			'type' => 'sample.timeseries',
			'x.count' => '12',
			'x.unit' => 'months',
			'series' => [],
			'format' => 'timeseries',
		];
		
		foreach($chart_fields as $field) {
			if(!($field instanceof DevblocksSearchCriteria))
				continue;
			
			$oper = $value = null;
			
			if($field->key == 'type') {
				// Do nothing
				
			} else if($field->key == 'format') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$chart_model['format'] = $value;
				
			} else if($field->key == 'x.count') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$chart_model['x.count'] = intval($value);
				
			} else if($field->key == 'x.unit') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$chart_model['x.unit'] = DevblocksPlatform::strLower($value);
				
			} else if(DevblocksPlatform::strStartsWith($field->key, 'series.')) {
				$series_query = CerbQuickSearchLexer::getTokensAsQuery($field->tokens);
				$series_query = substr($series_query, 1, -1);
				
				$series_fields = CerbQuickSearchLexer::getFieldsFromQuery($series_query);
				
				$series_id = explode('.', $field->key, 2)[1];
				
				$series_model = [
					'id' => $series_id,
					'label' => DevblocksPlatform::strTitleCase(str_replace('_',' ',$series_id)),
					'y.min' => 0,
					'y.max' => 1000,
					'trend' => 'random',
				];
				
				foreach($series_fields as $series_field) {
					if($series_field->key == 'label') {
						CerbQuickSearchLexer::getOperStringFromTokens($series_field->tokens, $oper, $value);
						$series_model['label'] = $value;
					} else if($series_field->key == 'trend') {
						CerbQuickSearchLexer::getOperStringFromTokens($series_field->tokens, $oper, $value);
						$series_model['trend'] = $value;
					} else if($series_field->key == 'y.min') {
						CerbQuickSearchLexer::getOperStringFromTokens($series_field->tokens, $oper, $value);
						$series_model['y.min'] = intval($value);
					} else if($series_field->key == 'y.max') {
						CerbQuickSearchLexer::getOperStringFromTokens($series_field->tokens, $oper, $value);
						$series_model['y.max'] = intval($value);
					}
				}
				
				$chart_model['series'][] = $series_model;
				
			} else {
				$error = sprintf("The parameter '%s' is unknown.", $field->key);
				return false;
			}
		}
		
		$x_domain = [];
		$x_count = $chart_model['x.count'];
		
		$xaxis_format = '%Y-%m-%d';
		$xaxis_step = 'day';
		
		switch($chart_model['x.unit']) {
			case 'day':
			case 'days':
				$ts = strtotime(sprintf('today -%d days', $x_count));
				for($i=$x_count-1; $i >= 0; $i--) {
					$x_domain[] = date('Y-m-d', $ts);
					$ts = strtotime('+1 day', $ts);
				}
				break;
				
			case 'week':
			case 'weeks':
				$xaxis_step = 'week';
				$ts = strtotime(sprintf('Monday -%d weeks', $x_count));
				for($i=$x_count-1; $i >= 0; $i--) {
					$x_domain[] = date('Y-m-d', $ts);
					$ts = strtotime('+1 week', $ts);
				}
				break;
				
			case 'month':
			case 'months':
				$xaxis_step = 'month';
				$xaxis_format = '%Y-%m';
				
				for($i=$x_count-1; $i >= 0; $i--) {
					$ts = strtotime(sprintf('first day of this month -%d months', $i));
					$x_domain[] = date('Y-m', $ts);
				}
				break;
				
			case 'year':
			case 'years':
				$xaxis_step = 'year';
				$xaxis_format = '%Y';
				
				for($i=$x_count-1; $i >= 0; $i--) {
					$ts = strtotime(sprintf('first day of this year -%d years', $i));
					$x_domain[] = date('Y', $ts);
				}
				break;
		}
		
		$data = [
			'ts' => $x_domain,
		];
		
		foreach($chart_model['series'] as $series) {
			$y_min = $series['y.min'];
			$y_max = $series['y.max'];
			@$trend = $series['trend'] ?: 'random';
			
			$y_values = [];
			
			foreach(array_keys($x_domain) as $x_idx) {
				switch($trend) {
					case 'up':
						$y_p = ($x_idx+1)/($x_count-1);
						$y_inc = ceil($y_p * $y_max * 0.2);
						$y = ($y_p*$y_max*0.8) + mt_rand(-$y_inc,$y_inc);
						break;
						
					case 'down':
						$y_p = 1-(($x_idx)/($x_count));
						$y_inc = ceil($y_p * $y_max * 0.2);
						$y = ($y_p*$y_max*0.8) + mt_rand(-$y_inc,$y_inc);
						break;
						
					default:
					case 'random':
						$y = mt_rand($y_min, $y_max);
						break;
				}
				
				$y = DevblocksPlatform::intClamp($y, $y_min, $y_max);
				$y_values[] = $y;
			}
			
			$data[$series['label']] = $y_values;
		}
		
		return [
			'data' => $data,
			'_' => [
				'type' => 'sample.timeseries',
				'format' => 'timeseries',
				'format_params' => [
					'xaxis_key' => 'ts',
					'xaxis_step' => $xaxis_step,
					'xaxis_format' => $xaxis_format,
				],
			]
		];
	}
};