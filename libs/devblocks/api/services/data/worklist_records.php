<?php
class _DevblocksDataProviderWorklistRecords extends _DevblocksDataProvider {
	function getSuggestions($type, array $params=[]) {
		$of = $params['of'] ?? null;
		$of_schema = null;
		
		if($of) {
			if(
				false == ($context_ext = Extension_DevblocksContext::getByAlias($of, true))
				|| false == ($view = $context_ext->getTempView()))
			{
				$of_schema = null;
			} else {
				$of_schema = $view->getQueryAutocompleteSuggestions();
			}
		}
		
		if(!$of_schema) {
			$suggestions = [
				'' => [
					'of:',
				],
				'type:' => array_keys(DevblocksPlatform::services()->data()->getTypes()),
				'of:' => array_values(Extension_DevblocksContext::getUris()),
			];
			return $suggestions;
		}
		
		$suggestions  = [
			'' => [
				'of:',
				[
					'caption' => 'query:',
					'snippet' => 'query:(${1})',
				],
				[
					'caption' => 'query.required:',
					'snippet' => 'query.required:(${1})',
				],
				'format:',
				[
					'caption' => 'expand:',
					'snippet' => 'expand:[${1}]',
				],
				'page:',
				'timeout:',
			],
			'of:' => array_values(Extension_DevblocksContext::getUris()),
			'query:' => [],
			'query.required:' => [],
			'page:' => [
				[
					'caption' => '(number)',
					'snippet' => '${1:0}',
				]
			],
			'format:' => [
				'dictionaries',
			]
		];
		
		foreach($of_schema as $of_path => $of_suggestions) {
			if('_contexts' == $of_path) {
				if(!array_key_exists('_contexts', $suggestions))
					$suggestions['_contexts'] = [];
				
				foreach($of_suggestions as $ctx_path => $ctx_suggestion) {
					$suggestions['_contexts']['query:' . $ctx_path] = $ctx_suggestion;
					$suggestions['_contexts']['query.required:' . $ctx_path] = $ctx_suggestion;
				}
				
			} else {
				$suggestions['query:' . $of_path] = $of_suggestions;
				$suggestions['query.required:' . $of_path] = $of_suggestions;
			}
		}
		
		return $suggestions;
	}
	
	function getData($query, $chart_fields, &$error=null, array $options=[]) {
		$db = DevblocksPlatform::services()->database();
		
		$chart_model = [
			'type' => 'worklist.records',
			'expand' => [],
			'query' => '',
			'format' => 'dictionaries',
			'timeout' => 20000,
		];
		
		$context = null;
		
		foreach($chart_fields as $field) {
			$oper = $value = null;
			
			if(!($field instanceof DevblocksSearchCriteria))
				continue;

			if($field->key == 'type') {
				// Do nothing
				true;
				
			} else if($field->key == 'of') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				if(false == ($context = Extension_DevblocksContext::getByAlias($value, true)))
					continue;
				
				$chart_model['context'] = $context->id;
		
			} else if($field->key == 'expand') {
				CerbQuickSearchLexer::getOperArrayFromTokens($field->tokens, $oper, $value);
				$chart_model['expand'] = $value;
			
			} else if(in_array($field->key, ['query.require','query.required'])) {
				$data_query = CerbQuickSearchLexer::getTokensAsQuery($field->tokens);
				$data_query = substr($data_query, 1, -1);
				$chart_model['query_required'] = $data_query;
				
			} else if($field->key == 'query') {
				$data_query = CerbQuickSearchLexer::getTokensAsQuery($field->tokens);
				$data_query = substr($data_query, 1, -1);
				$chart_model['query'] = $data_query;
				
			} else if($field->key == 'page') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$chart_model['page'] = $value;
				
			} else if($field->key == 'format') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$chart_model['format'] = $value;
				
			} else if($field->key == 'timeout') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$chart_model['timeout'] = DevblocksPlatform::intClamp($value, 0, 60000);

			} else {
				$error = sprintf("The parameter '%s' is unknown.", $field->key);
				return false;
			}
		}
		
		// Sanitize
		
		if(!$context) {
			$error = "The `of:` field is not a valid context type.";
			return false;
		}
		
		$view = $context->getTempView();
		
		$view->addParamsWithQuickSearch(@$chart_model['query']);
		
		// Query
		
		$query = $chart_model['query'] ?? null;
		$query_required = $chart_model['query_required'] ?? null;
		
		$context_ext = Extension_DevblocksContext::get($chart_model['context'], true);
		$dao_class = $context_ext->getDaoClass();
		$search_class = $context_ext->getSearchClass();
		$view = $context_ext->getTempView();
		
		if(false === $view->addParamsRequiredWithQuickSearch($query_required, true, [], $error))
			return false;
		
		if(false === $view->addParamsWithQuickSearch($query, true, [], $error))
			return false;
		
		if(array_key_exists('page', $chart_model))
			$view->renderPage = $chart_model['page'];
		
		if(!method_exists($dao_class, 'getSearchQueryComponents')) {
			$error = sprintf('%s::getSearchQueryComponents() not implemented', $dao_class);
			return false;
		}
		
		$query_parts = $dao_class::getSearchQueryComponents(
			[],
			$view->getParams()
		);
		
		$sort_data = Cerb_ORMHelper::buildSort($view->renderSortBy, $view->renderSortAsc, $view->getFields(), $search_class);
		
		$select_fields = [];
		
		$select_fields[] = sprintf("%s AS %s",
			$search_class::getPrimaryKey(),
			$db->escape('id')
		);
		
		$sql = sprintf("SELECT %s%s %s %s %s LIMIT %d,%d",
			implode(', ', $select_fields),
			$sort_data['sql_select'] ? sprintf(", %s", $sort_data['sql_select']) : '',
			$query_parts['join'],
			$query_parts['where'],
			$sort_data['sql_sort'],
			$view->renderPage * $view->renderLimit,
			$view->renderLimit
		);
		
		try {
			if(false == ($results = $db->GetArrayReader($sql, $chart_model['timeout'])))
				$results = [];
			
		} catch (Exception_DevblocksDatabaseQueryTimeout $e) {
			$error = sprintf('Query timed out (%d ms)', $chart_model['timeout']);
			return false;
		}
		
		// Paging
		
		$sql_count = sprintf("SELECT COUNT(*) %s %s LIMIT %d",
			$query_parts['join'],
			$query_parts['where'],
			$view->renderLimit
		);
		
		try {
			$total = $db->GetOneReader($sql_count, $chart_model['timeout']);
			
		} catch(Exception_DevblocksDatabaseQueryTimeout $e) {
			$error = sprintf('Query timed out (%d ms)', $chart_model['timeout']);
			return false;
		}
		
		$paging = $view->getPaging($results, $total);
		$chart_model['paging'] = $paging;
		
		// Load models
		
		$ids = array_column($results, 'id');
		
		$models = $context_ext->getModelObjects($ids);
		$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, $context_ext->id, $chart_model['expand']);
		
		$chart_model['data'] = $dicts;
		
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
	
	function _formatDataAsDictionaries($chart_model) : array {
		$meta = [
			'data' => $chart_model['data'],
			'_' => [
				'type' => 'worklist.records',
				'format' => 'dictionaries',
			]
		];
		
		if(array_key_exists('paging', $chart_model)) {
			$meta['_']['paging'] = $chart_model['paging'];
		}
		
		return $meta;
	}
};