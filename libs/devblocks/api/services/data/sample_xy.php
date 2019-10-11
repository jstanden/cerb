<?php
class _DevblocksDataProviderSampleXy extends _DevblocksDataProvider {
	function getSuggestions($type, array $params=[]) {
		return [
			'' => [
				[
					'caption' => 'series:',
					'snippet' => "series.\${1:alias}:(\n  label:\"\${2:Enterprise}\"\n  samples:\${3:100}\n  trend:\${4:up}\n  x.min:\${5:120}\n  x.max:\${6:172800}\n  y.min:\${7:0}\n  y.max:\${8:100}\n)",
					'suppress_autocomplete' => true,
				],
				'format:',
			],
			'series.*:' => [
				'' => [
					'label:',
					'samples:',
					'trend:',
					'x.min:',
					'x.max:',
					'y.min:',
					'y.max:',
				],
				'label:' => [],
				'samples:' => [],
				'trend:' => [
					'down',
					'up',
					'random',
				],
				'x.min:' => [],
				'x.max:' => [],
				'y.min:' => [],
				'y.max:' => [],
			],
			'format:' => [
				'scatterplot',
			]
		];
	}
	
	function getData($query, $chart_fields, &$error=null, array $options=[]) {
		
		$chart_model = [
			'type' => 'sample.xy',
			'format' => 'scatterplot',
			'series' => [],
		];
		
		foreach($chart_fields as $field) {
			if(!($field instanceof DevblocksSearchCriteria))
				continue;
			
			$oper = $value = null;
			
			if(in_array($field->key, ['type', 'format'])) {
				// Do nothing
				
			} else if(DevblocksPlatform::strStartsWith($field->key, 'series.')) {
				$series_query = CerbQuickSearchLexer::getTokensAsQuery($field->tokens);
				$series_query = substr($series_query, 1, -1);
				
				$series_fields = CerbQuickSearchLexer::getFieldsFromQuery($series_query);
				
				$series_id = explode('.', $field->key, 2)[1];
				
				$series_model = [
					'id' => $series_id,
					'label' => DevblocksPlatform::strTitleCase(str_replace('_',' ',$series_id)),
					'trend' => 'up',
					'samples' => 100,
					'x.min' => 0,
					'x.max' => 2000,
					'y.min' => 0,
					'y.max' => 100,
				];
				
				foreach($series_fields as $series_field) {
					if($series_field->key == 'label') {
						CerbQuickSearchLexer::getOperStringFromTokens($series_field->tokens, $oper, $value);
						$series_model['label'] = $value;
					} else if($series_field->key == 'samples') {
						CerbQuickSearchLexer::getOperStringFromTokens($series_field->tokens, $oper, $value);
						$series_model['samples'] = intval($value);
					} else if($series_field->key == 'trend') {
						CerbQuickSearchLexer::getOperStringFromTokens($series_field->tokens, $oper, $value);
						$series_model['trend'] = in_array($value,['up','down']) ? $value : 'up';
					} else if($series_field->key == 'x.min') {
						CerbQuickSearchLexer::getOperStringFromTokens($series_field->tokens, $oper, $value);
						$series_model['x.min'] = intval($value);
					} else if($series_field->key == 'x.max') {
						CerbQuickSearchLexer::getOperStringFromTokens($series_field->tokens, $oper, $value);
						$series_model['x.max'] = intval($value);
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
		
		$data = [];
		
		foreach($chart_model['series'] as $series) {
			$x_values = [$series['label'] . '_x'];
			$y_values = [$series['label']];
			
			$samples = $series['samples'];
			$trend = $series['trend'];
			$x_min = $series['x.min'];
			$x_max = $series['x.max'];
			$y_min = $series['y.min'];
			$y_max = $series['y.max'];
			
			for($i=0;$i<$samples;$i++) {
				$x = mt_rand($x_min, $x_max);
				$x_p = $x/$x_max;
				$y = mt_rand($y_min, $y_max);
				
				$y_var = 0.1 * $y_max;
				
				if('down' == $trend) {
					$y = DevblocksPlatform::intClamp(((1-$x_p) * $y_max) + mt_rand(-$y_var, $y_var), $y_min, $y_max);
				} else {
					$y = DevblocksPlatform::intClamp(($x_p * $y_max) + mt_rand(-$y_var, $y_var), $y_min, $y_max);
				}
				
				$x_values[] = $x;
				$y_values[] = $y;
			}
			
			$data[] = $x_values;
			$data[] = $y_values;
		}
		
		return [
			'data' => $data,
			'_' => [
				'type' => 'sample.xy',
				'format' => 'scatterplot',
			]
		];
	}
};