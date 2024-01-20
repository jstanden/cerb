<?php
class ChRest_Automations extends Extension_RestController {
	function getAction($stack) {
		@$module = array_shift($stack);
		
		if('logs' == $module) {
			@$action = array_shift($stack);
			
			if('search' == $action) {
				$this->_getLogsSearch();
				return;
			}
		}
		
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	function putAction($stack) {
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	function postAction($stack) {
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	function deleteAction($stack) {
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	private function _getLogsSearch() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker->is_superuser)
			$this->error(self::ERRNO_ACL, 'Only administrators may search automation logs.');
		
		$q = DevblocksPlatform::importGPC($_REQUEST['q'] ?? null, 'string',null);
		$page = DevblocksPlatform::importGPC($_REQUEST['page'] ?? null, 'integer',0);
		$limit = DevblocksPlatform::importGPC($_REQUEST['limit'] ?? null, 'integer',25);
		$limit = DevblocksPlatform::intClamp($limit, 1, 250);
		
		$params = SearchFields_AutomationLog::getFieldsFromQuery($q);
		
		[$results, $total] = DAO_AutomationLog::search(
			[],
			$params,
			$limit ?? 25,
			$page ?? 0,
			SearchFields_AutomationLog::ID,
			false
		);
		
		$levels = array_flip(DAO_AutomationLog::getLevels());
		
		$dicts = array_map(
			function(array $result) use (&$levels) {
				return [
					'automation_name' => $result[SearchFields_AutomationLog::AUTOMATION_NAME],
					'automation_node' => $result[SearchFields_AutomationLog::AUTOMATION_NODE],
					'created_at' => intval($result[SearchFields_AutomationLog::CREATED_AT]),
					'id' => intval($result[SearchFields_AutomationLog::ID]),
					'level' => intval($result[SearchFields_AutomationLog::LOG_LEVEL]),
					'level__label' => $levels[$result[SearchFields_AutomationLog::LOG_LEVEL]] ?? '',
					'message' => $result[SearchFields_AutomationLog::LOG_MESSAGE],
				];
			},
			$results ?? []
		);
		
		$container = [
			'count' => count($results),
			'limit' => $limit,
			'total' => intval($total),
			'page' => intval($page),
			'results' => $dicts,
		];
		
		$this->success($container);
	}
};