<?php
class ApiCommand_CerbWorklistSearch extends Extension_AutomationApiCommand {
	const ID = 'cerb.commands.worklist.search';
	
	function run(array $params=[], &$error=null) : array|false {
		$worklist_id = $params['worklist_id'] ?? null;
		$worker_id = intval($params['worker_id'] ?? null);
		$worklist_query = $params['worklist_query'] ?? null;
		
		if(!$worklist_id) {
			$error = '`params:worklist_id:` is required';
			return false;
		}
		
		if(!$worker_id || !is_numeric($worker_id)) {
			$error = '`params:worker_id:` is required and must be numeric.';
			return false;
		}
		
		if($worklist_query && !is_string($worklist_query)) {
			$error = '`params:worklist_query:` must be a string.';
			return false;
		}
		
		if(!($model = DAO_WorkerViewModel::getView($worker_id, $worklist_id))) {
			return [];
		}
		
		$view = \C4_AbstractViewLoader::unserializeAbstractView($model);
		$view->setAutoPersist(false);
		
		if($worklist_query)
			$view->addParamsWithQuickSearch($worklist_query, false);
		
		$models = $view->getDataAsObjects();
		$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, $view->getRecordType());
		
		return [
			'worklist' => json_decode(\C4_AbstractViewLoader::serializeViewToAbstractJson($view), true),
			'data' => 1 == $view->renderLimit ? current($dicts) : $dicts,
		];
	}
	
	public function getAutocompleteSuggestions($key_path, $prefix) : array {
		return match ($key_path) {
			'' => [
				'worklist_id:',
				'worklist_query:',
				'worker_id:',
			],
			default => [],
		};
	}
}