<?php /** @noinspection PhpUnused */
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2019, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.ai/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.ai	    http://webgroup.media
***********************************************************************/

class PageSection_SetupDevelopersAutomationLogs extends Extension_PageSection {
	function render() {
		$visit = CerberusApplication::getVisit();
		$tpl = DevblocksPlatform::services()->template();
		$response = DevblocksPlatform::getHttpResponse();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$stack = $response->path;
		
		@array_shift($stack); // config
		@array_shift($stack); // automation_logs
		
		$visit->set(ChConfigurationPage::ID, 'automation_logs');
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/developers/automation-logs/index.tpl');
	}
	
	function handleActionForPage(string $action, string $scope=null) {
		if('configAction' == $scope) {
			switch ($action) {
				case 'delete':
					return $this->_configAction_delete();
				case 'refresh':
					return $this->_configAction_refresh();
			}
		}
		return false;
	}
	
	private function _configAction_delete() : void {
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$active_worker = CerberusApplication::getActiveWorker();

		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$ids = DevblocksPlatform::importGPC($_POST['ids'] ?? [], 'array:int', []);
		
		DAO_AutomationLog::delete($ids);
	}
	
	private function _configAction_refresh() {
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();

		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$level_to_label = [
			0 => 'emergency',
			1 => 'alert',
			2 => 'critical',
			3 => 'error',
			4 => 'warning',
			5 => 'notice',
			6 => 'info',
			7 => 'debug',
		];
		
		$sheets = DevblocksPlatform::services()->sheet()->withDefaultTypes();
		
		$page = DevblocksPlatform::importGPC($_POST['page'] ?? 0, 'integer', 0);
		$filters = DevblocksPlatform::importGPC($_POST['filters'] ?? null, 'array', []);
		$limit = 25;
		$sheet_dicts = [];
		
		$criterion = [
			new DevblocksSearchCriteria(SearchFields_AutomationLog::AUTOMATION_NAME,'!=',''),
		];
		
		if(array_key_exists('search', $filters) && !empty($filters['search'] ?? '')) {
			$query = str_replace('*', '%', !str_contains($filters['search'], '*')
				? ('*' . $filters['search'] . '*')
				: $filters['search']
			);
			
			$criterion[] = new DevblocksSearchCriteria(
				SearchFields_AutomationLog::LOG_MESSAGE,
				DevblocksSearchCriteria::OPER_LIKE,
				$query
			);
		}
		
		list($results, $total) = DAO_AutomationLog::search(
			[],
			$criterion,
			$limit,
			$page,
			SearchFields_AutomationLog::ID,
			false
		);
		
		$automations = DAO_Automation::getByUris(array_column($results, SearchFields_AutomationLog::AUTOMATION_NAME));
		
		if(is_array($automations)) {
			$automation_name_to_id = array_column($automations, 'id', 'name');
		} else {
			$automation_name_to_id = [];
		}
		
		foreach($results as $result) {
			$sheet_dicts[] = DevblocksDictionaryDelegate::instance([
				'id' => $result[SearchFields_AutomationLog::ID],
				'name' => $result[SearchFields_AutomationLog::AUTOMATION_NAME],
				'automation__context' => CerberusContexts::CONTEXT_AUTOMATION,
				'automation_id' => $automation_name_to_id[$result[SearchFields_AutomationLog::AUTOMATION_NAME]] ?? 0,
				'automation__label' => $result[SearchFields_AutomationLog::AUTOMATION_NAME],
				'node' => $result[SearchFields_AutomationLog::AUTOMATION_NODE],
				'created_at' => $result[SearchFields_AutomationLog::CREATED_AT],
				'log_level_id' => $result[SearchFields_AutomationLog::LOG_LEVEL],
				'log_level' => $level_to_label[$result[SearchFields_AutomationLog::LOG_LEVEL]] ?? '',
				'log_message' => $result[SearchFields_AutomationLog::LOG_MESSAGE],
			]);
		}
		
		$sheet_kata = <<< EOD
    layout:
      headings@bool: no
      paging@bool: yes
      title_column: log_message
    columns:
      selection/id:
      text/log_message:
        label: Message
      card/automation__label:
        params:
          underline@bool: no
      text/node:
        label: Path
      text/log_level:
        label: Level
      date/created_at:
    EOD;
		
		if(!($sheet = $sheets->parse($sheet_kata, $error)))
			$sheet = [];
		
		$layout = $sheets->getLayout($sheet);
		
		$rows = $sheets->getRows($sheet, $sheet_dicts);
		
		$columns = $sheets->getColumns($sheet);
		
		$tpl->assign('layout', $layout);
		$tpl->assign('rows', $rows);
		$tpl->assign('columns', $columns);
		
		$paging = $sheets->getPaging(
			count($results),
			$page,
			$limit,
			$total
		);
		
		if($layout['paging'] && $paging) {
			$tpl->assign('paging', $paging);
		}
		
		$tpl->display('devblocks:cerberusweb.core::ui/sheets/render.tpl');		
	}
}