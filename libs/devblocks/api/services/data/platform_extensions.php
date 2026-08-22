<?php
class _DevblocksDataProviderPlatformExtensions extends _DevblocksDataProvider {
	function getSuggestions($type, array $params=[]) {
		$suggestions  = [
			'' => [
				'point:',
				'filter:',
				'limit:',
				'page:',
				'format:',
			],
			'format:' => [
				'dictionaries',
			],
			'point:' => array_keys(DevblocksPlatform::getExtensionPointRegistry()),
		];
		
		return $suggestions;
	}
	
	function getData($query, $chart_fields, &$error=null, array $options=[]) {
		$extension_points = DevblocksPlatform::getExtensionPointRegistry();
		
		$chart_model = [
			'type' => 'platform.extensions',
			'point' => null,
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
				null;
				
			} else if($field->key == 'point') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$chart_model['point'] = $value;
				
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
		
		if(!$chart_model['point']) {
			$error = 'The `point:` parameter is required.';
			return false;
		}
		
		if(!array_key_exists($chart_model['point'], $extension_points)) {
			$error = sprintf('The `point:` parameter (%s) is not a valid extension point.', $chart_model['point']);
			return false;
		}
		
		// Data
		
		$data = [];
		$paging = [];
		
		$extensions = DevblocksPlatform::getExtensions($chart_model['point'], false);
		
		if ($chart_model['filter']) {
			$extensions = array_filter($extensions, function($extension) use ($chart_model) {
				$match = sprintf('%s %s', $extension->name, $extension->id);
				return stristr($match, $chart_model['filter']);
			});
		}
		
		foreach($extensions as $extension) {
			$data[] = [
				'id' => $extension->id,
				'name' => $extension->name,
				'class' => $extension->class,
				'plugin_id' => $extension->plugin_id,
				'params' => $extension->params,
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