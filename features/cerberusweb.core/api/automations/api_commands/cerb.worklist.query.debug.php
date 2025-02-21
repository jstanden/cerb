<?php
class ApiCommand_CerbWorklistQueryDebug extends Extension_AutomationApiCommand {
	const ID = 'cerb.commands.worklist.query.debug';
	
	function run(array $params=[], &$error=null) : array|false {
		$record_type = $params['record_type'] ?? null;
		$query = $params['query'] ?? '';
		
		if(!$record_type) {
			$error = '`params:record_type:` is required';
			return false;
		}
		
		if(!($record_ext = Extension_DevblocksContext::getByAlias($record_type, true))) {
			$error = '`params:record_type:` is not a valid record type';
			return false;
		}
		
		if(!is_string($query)) {
			$error = '`params:query:` must be a string';
			return false;
		}
		
		$view = $record_ext->getTempView();
		$view->addParamsWithQuickSearch($query);
		$view->setAutoPersist(false);
		
		$dao_class = $record_ext->getDaoClass();
		
		$query_parts = $dao_class::getSearchQueryComponents(
			[],
			$view->getParams(),
			$view->renderSortBy,
			$view->renderSortAsc
		);
		
		$select_sql = $query_parts['select'];
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];
		$sort_sql = $query_parts['sort'];
		
		$query_parts['limit'] = $view->renderLimit ?? 0;
		$query_parts['page'] = $view->renderPage ?? 0;
		
		$results = [];
		
		$results['sql'] =
			$select_sql.
			$join_sql.
			$where_sql.
			$sort_sql.
			($query_parts['limit'] ? sprintf(" LIMIT %d,%d", $query_parts['page']*$query_parts['limit'], $query_parts['limit']) : '')
		;
		
		return $results;
	}
	
	public function getAutocompleteSuggestions($key_path, $prefix, $key_fullpath, $script) : array {
		return match ($key_path) {
			'' => [
				'record_type:',
				'query:',
			],
			'record_type:' => array_values(Extension_DevblocksContext::getUris()),
			default => [],
		};
	}
}