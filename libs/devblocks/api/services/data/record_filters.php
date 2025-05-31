<?php
class _DevblocksDataProviderRecordFilters extends _DevblocksDataProvider {
	function getSuggestions($type, array $params=[]) {
		$suggestions  = [
			'' => [
				'exclude_custom:yes',
				'exclude_links:yes',
				'filter:',
				'format:',
				'limit:',
				'of:',
				'page:',
				'with:',
			],
			'format:' => [
				'dictionaries',
			],
			'of:' => array_values(Extension_DevblocksContext::getUris()),
		];
		
		return $suggestions;
	}
	
	function getData($query, $chart_fields, &$error=null, array $options=[]) {
		$chart_model = [
			'exclude_custom' => false,
			'exclude_links' => false,
			'filter' => null,
			'format' => 'dictionaries',
			'limit' => null,
			'of' => null,
			'page' => 0,
			'type' => 'record.filters',
		];
		
		foreach($chart_fields as $field) {
			$oper = $value = null;
			
			if(!($field instanceof DevblocksSearchCriteria))
				continue;
			
			if($field->key == 'type') {
				null;
				
			} else if($field->key == 'of') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$chart_model['of'] = $value;
				
			} else if($field->key == 'exclude_custom') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$chart_model['exclude_custom'] = DevblocksPlatform::services()->string()->toBool($value);
				
			} else if($field->key == 'exclude_links') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$chart_model['exclude_links'] = DevblocksPlatform::services()->string()->toBool($value);
				
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
		
		if(!$chart_model['of']) {
			$error = 'The `of:` parameter is required.';
			return false;
		}
		
		if(!($record_type_ext = Extension_DevblocksContext::getByAlias($chart_model['of'], true))) {
			$error = 'The `of:` parameter is not a valid record type.';
			return false;
		}
		
		// Data
		
		$paging = [];
		
		$view = $record_type_ext->getTempView();
		
		if(method_exists($view, 'getQuickSearchFields')) {
			$fields = $view->getQuickSearchFields();
		} else {
			$fields = [];
		}
		
		ksort($fields);
		
		if ($chart_model['filter']) {
			$fields = array_filter($fields, function($field, $field_key) use ($chart_model) {
				@$match = sprintf('%s %s', $field_key, $field['type']);
				return stristr($match, $chart_model['filter']);
			}, ARRAY_FILTER_USE_BOTH);
		}
		
		if($chart_model['exclude_custom']) {
			$fields = array_filter($fields, function($field) {
				return !($field['options']['cf_id'] ?? false);
			});
		}
		
		if($chart_model['exclude_links']) {
			$fields = array_filter($fields, function($field, $field_key) {
				return !($field_key == 'links' || str_starts_with($field_key, 'links.'));
			}, ARRAY_FILTER_USE_BOTH);
		}
		
		$data = $fields;
		
		unset($fields);
		
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
				'type' => 'record.filters',
				'format' => 'dictionaries',
			]
		];
		
		if(array_key_exists('paging', $chart_model)) {
			$meta['_']['paging'] = $chart_model['paging'];
		}
		
		return $meta;
	}
};