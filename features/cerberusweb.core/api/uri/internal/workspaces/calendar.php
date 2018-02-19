<?php
class WorkspaceTab_Calendar extends Extension_WorkspaceTab {
	public function renderTab(Model_WorkspacePage $page, Model_WorkspaceTab $tab) {
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('workspace_page', $page);
		$tpl->assign('workspace_tab', $tab);

		$this->_showCalendarTab($page, $tab);
	}
	
	public function renderTabConfig(Model_WorkspacePage $page, Model_WorkspaceTab $tab) {
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('workspace_page', $page);
		$tpl->assign('workspace_tab', $tab);
		
		// Calendars
		
		$calendars = DAO_Calendar::getAll();
		$tpl->assign('calendars', $calendars);
		
		// Render template
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/tabs/calendar/config.tpl');
	}
	
	function saveTabConfig(Model_WorkspacePage $page, Model_WorkspaceTab $tab) {
		@$params = DevblocksPlatform::importGPC($_REQUEST['params'], 'array');

		DAO_WorkspaceTab::update($tab->id, array(
			DAO_WorkspaceTab::PARAMS_JSON => json_encode($params),
		));
	}
	
	private function _showCalendarTab(Model_WorkspacePage $page, Model_WorkspaceTab $tab) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$month = DevblocksPlatform::importGPC($_REQUEST['month'],'integer', 0);
		@$year = DevblocksPlatform::importGPC($_REQUEST['year'],'integer', 0);
		
		$tpl = DevblocksPlatform::services()->template();
		$visit = CerberusApplication::getVisit();
		
		if(false != ($calendar = DAO_Calendar::get(@$tab->params['calendar_id']))) { /* @var Model_Calendar $calendar */
			$start_on_mon = @$calendar->params['start_on_mon'] ? true : false;
			$calendar_properties = DevblocksCalendarHelper::getCalendar($month, $year, $start_on_mon);
			
			$calendar_events = $calendar->getEvents($calendar_properties['date_range_from'], $calendar_properties['date_range_to']);
			
			// Occlusion
			$availability = $calendar->computeAvailability($calendar_properties['date_range_from'], $calendar_properties['date_range_to'], $calendar_events);
			$availability->occludeCalendarEvents($calendar_events);

			// Template scope
			$tpl->assign('calendar', $calendar);
			$tpl->assign('calendar_events', $calendar_events);
			
			$tpl->assign('calendar_properties', $calendar_properties);
			$tpl->display('devblocks:cerberusweb.core::internal/calendar/tab.tpl');
			
		} else {
			$tpl->display('devblocks:cerberusweb.core::internal/calendar/tab_empty.tpl');
		}

	}
	
	function exportTabConfigJson(Model_WorkspacePage $page, Model_WorkspaceTab $tab) {
		$json = array(
			'tab' => array(
				'uid' => 'workspace_tab_' . $tab->id,
				'name' => $tab->name,
				'extension_id' => $tab->extension_id,
				'params' => $tab->params,
			),
		);
		
		return json_encode($json);
	}
	
	function importTabConfigJson($json, Model_WorkspaceTab $tab) {
		if(empty($tab) || empty($tab->id) || !is_array($json))
			return false;
		
		return true;
	}
}