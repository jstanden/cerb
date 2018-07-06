<?php
abstract class _DevblocksDataProvider {
	abstract function getData($query, $chart_fields, array $options=[]);
}

class _DevblocksDataService {
	static $instance = null;
	
	private function __construct() {
		// We lazy load the connections
	}
	
	static function getInstance() {
		if(null == self::$instance) {
			self::$instance = new _DevblocksDataService();
		}
		
		return self::$instance;
	}
	
	function executeQuery($query) {
		$chart_fields = CerbQuickSearchLexer::getFieldsFromQuery($query);
		
		$type_field = array_filter($chart_fields, function($field) {
			if($field->key == 'type')
				return true;
			
			return false;
		});
		
		if(!is_array($type_field) || 1 != count($type_field))
			throw new Exception_DevblocksValidationError("A valid chart type is required.");
		
		CerbQuickSearchLexer::getOperStringFromTokens($type_field[0]->tokens, $oper, $chart_type);
		
		$results = [];
		
		switch($chart_type) {
			default:
				throw new Exception_DevblocksValidationError("A valid chart type is required.");
				break;
		}
		
		return $results;
	}
}