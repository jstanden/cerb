<?php
class _DevblocksDataProviderPlatformExtensionPoints extends _DevblocksDataProvider {
	function getSuggestions($type, array $params=[]) {
		$suggestions  = [
			'' => [
				'filter:',
				'limit:',
				'page:',
				'format:',
			],
			'format:' => [
				'dictionaries',
			],
		];
		
		return $suggestions;
	}
	
	function getData($query, $chart_fields, &$error=null, array $options=[]) {
		$extension_points = DevblocksPlatform::getExtensionPoints();
		
		$chart_model = [
			'type' => 'platform.extension.points',
			'filter' => null,
			'limit' => null,
			'page' => 0,
			'format' => 'dictionaries',
		];
		
		foreach($chart_fields as $field) {
			$oper = $value = null;
			
			if(!($field instanceof DevblocksSearchCriteria))
				continue;
			
			if($field->key == 'type') {
				DevblocksPlatform::noop();
				
			} else if($field->key == 'filter') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$chart_model['filter'] = $value;
				
			} else if($field->key == 'limit') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$chart_model['limit'] = intval($value);
				
			} else if($field->key == 'page') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$chart_model['page'] = intval($value);
				
			} else if($field->key == 'format') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$chart_model['format'] = $value;
				
			} else {
				$error = sprintf("The parameter '%s' is unknown.", $field->key);
				return false;
			}
		}
		
		// Data
		
		$data = [];
		$paging = [];
		
		if ($chart_model['filter']) {
			$extension_points = array_filter($extension_points, function($extension_point) use ($chart_model) {
				$match = sprintf('%s %s', $extension_point->name, $extension_point->id);
				return stristr($match, $chart_model['filter']);
			});
		}
		
		foreach($extension_points as $extension_point_key => $extension_point) {
			$data[] = [
				'id' => $extension_point_key,
				'name' => $extension_point['label'] ?? null,
				'class' => $extension_point['class'] ?? null,
			];
		}
		
		if($chart_model['limit']) {
			$page = $chart_model['page'] ?? 0;
			$limit = $chart_model['limit'] ?? 10;
			
			$total = count($data);
			
			$data = array_slice($data, $page * $limit, $limit);
			
			$paging = DevblocksPlatform::services()->data()->generatePaging($data, $total, $limit, $page);
		}
		
		$chart_model['data'] = $data;
		
		if($paging)
			$chart_model['paging'] = $paging;
		
		// Respond
		
		@$format = $chart_model['format'] ?: 'dictionaries';
		
		switch($format) {
			case 'dictionaries':
				return $this->_formatDataAsDictionaries($chart_model);
				
			default:
				$error = sprintf("`format:%s` is not valid for `type:%s`. Must be one of: dictionaries",
					$format,
					$chart_model['type']
				);
				return false;
		}
	}
	
	function _formatDataAsDictionaries($chart_model) {
		$meta = [
			'data' => $chart_model['data'],
			'_' => [
				'type' => $chart_model['type'],
				'format' => 'dictionaries',
			]
		];
		
		if(array_key_exists('paging', $chart_model)) {
			$meta['_']['paging'] = $chart_model['paging'];
		}
		
		return $meta;
	}
};