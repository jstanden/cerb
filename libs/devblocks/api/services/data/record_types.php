<?php
class _DevblocksDataProviderRecordTypes extends _DevblocksDataProvider {
	function getSuggestions($type, array $params=[]) {
		return [
			'' => [
				'filter:',
				'limit:',
				'options:',
				'page:',
				'format:',
			],
			'format:' => [
				'dictionaries',
			]
		];
	}
	
	function getData($query, $chart_fields, &$error=null, array $options=[]) {
		$chart_model = [
			'type' => 'record.types',
			'filter' => null,
			'limit' => null,
			'options' => [],
			'page' => 0,
			'format' => 'dictionaries',
		];
		
		foreach($chart_fields as $field) {
			$oper = $value = null;
			
			if(!($field instanceof DevblocksSearchCriteria))
				continue;
			
			if($field->key == 'type') {
				null;
				
			} else if($field->key == 'filter') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$chart_model['filter'] = $value;
				
			} else if($field->key == 'limit') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$chart_model['limit'] = intval($value);
				
			} else if($field->key == 'options') {
				CerbQuickSearchLexer::getOperArrayFromTokens($field->tokens, $oper, $value);
				$chart_model['options'] = $value;
				
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
		
		$record_type_exts = Extension_DevblocksContext::getAll(true);
		
		foreach($record_type_exts as $record_type_ext) {
			if(null != ($chart_model['options'] ?? null)) {
				$options = $record_type_ext->manifest->params['options'][0] ?? [];
				
				// Filter by options like 'search'
				$matching_options = array_intersect($chart_model['options'], array_keys($options));
				
				if(!$matching_options)
					continue;
			}
			
			$aliases = Extension_DevblocksContext::getAliasesForContext($record_type_ext->manifest);
			
			if ($chart_model['filter']) {
				$match = sprintf("%s %s %s", $aliases['uri'] ?? null, $aliases['label_singular'] ?? null, $aliases['label_plural'] ?? null);
				
				if (false === stristr($match, $chart_model['filter']))
					continue;
			}
			
			$data[] = [
				'id' => $record_type_ext->id,
				'uri' => $aliases['uri'],
				'label_singular' => DevblocksPlatform::strTitleCase($aliases['singular']),
				'label_plural' => DevblocksPlatform::strTitleCase($aliases['plural'])
			];
		}
		
		DevblocksPlatform::sortObjects($data, '[label_plural]');
		
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
		
		$format = ($chart_model['format'] ?? null) ?: 'dictionaries';
		
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
				'type' => 'record.types',
				'format' => 'dictionaries',
			]
		];
		
		if(array_key_exists('paging', $chart_model)) {
			$meta['_']['paging'] = $chart_model['paging'];
		}
		
		return $meta;
	}
};