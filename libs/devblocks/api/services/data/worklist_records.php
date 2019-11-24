<?php
class _DevblocksDataProviderWorklistRecords extends _DevblocksDataProvider {
	function getSuggestions($type, array $params=[]) {
		@$of = $params['of'];
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
				'type:' => DevblocksPlatform::services()->data()->getTypes(),
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
		];
		
		$context = null;
		
		foreach($chart_fields as $field) {
			$oper = $value = null;
			
			if(!($field instanceof DevblocksSearchCriteria))
				continue;

			if($field->key == 'type') {
				// Do nothing
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
		
		$dao_class = $context->getDaoClass();
		$search_class = $context->getSearchClass();
		$view = $context->getTempView();
		
		$view->addParamsWithQuickSearch(@$chart_model['query']);
		
		// Query
		
		@$query = $chart_model['query'];
		@$query_required = $chart_model['query_required'];
		
		$context_ext = Extension_DevblocksContext::get($chart_model['context'], true);
		$dao_class = $context_ext->getDaoClass();
		$search_class = $context_ext->getSearchClass();
		$view = $context_ext->getTempView();
		
		$view->addParamsRequiredWithQuickSearch($query_required);
		$view->addParamsWithQuickSearch($query);
		
		if(array_key_exists('page', $chart_model))
			$view->renderPage = $chart_model['page'];
		
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
		
		if(false == ($results = $db->GetArraySlave($sql)))
			$results = [];
		
		// Paging
		
		$sql_count = sprintf("SELECT COUNT(*) %s %s LIMIT %d",
			$query_parts['join'],
			$query_parts['where'],
			$view->renderLimit
		);
		$total = $db->GetOneSlave($sql_count);
		
		$paging = [
			'page' => [
				'of' => intval(ceil($total / $view->renderLimit)),
				'rows' => [
					'of' => intval($total),
					'count' => count($results),
					'limit' => intval($view->renderLimit),
				],
 			]
		];
		
		$paging['page']['index'] = DevblocksPlatform::intClamp($view->renderPage, 0, PHP_INT_MAX);
		
		$paging['page']['rows']['from'] = $paging['page']['index'] * $paging['page']['rows']['limit'] + 1;
		$paging['page']['rows']['to'] = min($paging['page']['rows']['from']+$paging['page']['rows']['limit'] - 1, $paging['page']['rows']['of']);
		
		if($paging['page']['rows']['from'] > $paging['page']['rows']['of']) {
			$paging['page']['rows']['from'] = 0;
			$paging['page']['rows']['to'] = 0;
		}
		
		if($paging['page']['index'] - 1 >= 0) {
			$paging['page']['prev'] = $paging['page']['index'] - 1;
			$paging['page']['first'] = 0;
		}
		
		if($paging['page']['index'] + 1 < $paging['page']['of']) {
			$paging['page']['next'] = $paging['page']['index'] + 1;
			$paging['page']['last'] = $paging['page']['of']-1;
		}
		
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
				break;
				
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