<?php
class AutomationTrigger_UiFunction extends Extension_AutomationTrigger {
	const ID = 'cerb.trigger.ui.function';
	
	function renderConfig(Model_Automation $model) {
	}
	
	function validateConfig(array &$params, &$error=null) {
		return true;
	}
	
	function getInputsMeta() {
		return;
		
		if(!array_key_exists('inputs_kata', $params))
			return [];
		
		$kata = DevblocksPlatform::services()->kata();
		$error = null;
		
		$inputs_kata = $kata->parse($params['inputs_kata'], $error);
		
		$dict = DevblocksDictionaryDelegate::instance([]);
		$inputs = $kata->formatTree($inputs_kata, $dict);
		
		$results = [];
		
		if(is_array($inputs))
		foreach($inputs as $input_key => $input_data) {
			list($input_type, $input_key) = explode('/', $input_key);
			
			$input = [
				'key' => $input_key,
				'type' => $input_type,
				'required' => @$input_data['required'] ?? false,
				'params' => @$input_data['params'] ?? [],
				'notes' => @$input_data['notes'] ?? '',
			];
			
			$results[] = $input;
		}
		
		return $results;
	}
	
	function getOutputsMeta() {
		return [];
	}
	
	public function getEditorToolbarItems(array $toolbar): array {
		return $toolbar;
	}
	
	public function getAutocompleteSuggestions() : array {
		return [];
	}
}