<?php
class _DevblocksDataProviderSampleGeoPoints extends _DevblocksDataProvider {
	function getSuggestions($type, array $params=[]) {
		return [
			'' => [
				[
					'caption' => 'series:',
					'snippet' => "series.\${1:cities}:(\n  new_york:(name:\"\${2:New York}\" coordinates:\"POINT(\${3:-73.935242 40.73061})\")\n)",
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
			'format:' => [
				'geojson',
			]
		];
	}
	
	function getData($query, $chart_fields, &$error=null, array $options=[]) {
		$data = [
			'type' => 'Topology',
			'objects' => [],
		];
		
		foreach($chart_fields as $field) {
			if(!($field instanceof DevblocksSearchCriteria))
				continue;
			
			if($field->key == 'type') {
				// Do nothing
			} else if(DevblocksPlatform::strStartsWith($field->key, 'series.')) {
				$series_query = CerbQuickSearchLexer::getTokensAsQuery($field->tokens);
				$series_query = substr($series_query, 1, -1);
				
				$series_fields = CerbQuickSearchLexer::getFieldsFromQuery($series_query);
				
				$series_id = explode('.', $field->key, 2)[1];
				
				$geometries = [];
				
				$data['objects'][$series_id] = [
					'type' => 'GeometryCollection',
					'geometries' => &$geometries,
				];
				
				foreach($series_fields as $series_field) {
					$geometry = [
						'type' => 'Point',
						'coordinates' => [],
						'properties' => [],
					];
					
					$point_query = CerbQuickSearchLexer::getTokensAsQuery($series_field->tokens);
					$point_query = substr($point_query, 1, -1);
					
					$point_fields = CerbQuickSearchLexer::getFieldsFromQuery($point_query);
					
					foreach($point_fields as $point_field) {
						$oper = $value = null;
						
						if($point_field->key == 'coordinates') {
							CerbQuickSearchLexer::getOperStringFromTokens($point_field->tokens, $oper, $value);
							$geometry['properties']['coordinates'] = $value;
							
							if(false != ($geo = DevblocksPlatform::parseGeoPointString($value))) {
								$geometry['coordinates'] = [$geo['longitude'], $geo['latitude']];
							}
							
						} else {
							CerbQuickSearchLexer::getOperStringFromTokens($point_field->tokens, $oper, $value);
							$geometry['properties'][$point_field->key] = $value;
						}
					}
					
					$geometries[] = $geometry;
				}
				
			} else if($field->key == 'format') {
				// Always GeoJSON
				
			} else {
				$error = sprintf("The parameter '%s' is unknown.", $field->key);
				return false;
			}
		}
		
		return [
			'data' => $data,
			'_' => [
				'type' => 'sample.geo.points',
				'format' => 'geojson',
			]
		];
	}
};