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

class PageSection_InternalCalendars extends Extension_PageSection {
	function render() {}
	
	public function handleActionForPage(string $action, string $scope=null) {
		if('internalAction' == $scope) {
			switch ($action) {
				case 'showCalendarTab':
					return $this->_internalAction_showCalendarTab();
				case 'getCalendarDatasourceParams':
					return $this->_internalAction_getCalendarDatasourceParams();
				case 'getDateInputAutoCompleteOptionsJson':
					return $this->_internalAction_getDateInputAutoCompleteOptionsJson();
				case 'parseDateJson':
					return $this->_internalAction_parseDateJson();
			}
		}
		return false;
	}
	
	/** @noinspection DuplicatedCode */
	private function _internalAction_showCalendarTab() {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$calendar_id = DevblocksPlatform::importGPC($_REQUEST['id'] ?? null, 'integer');
		$month = DevblocksPlatform::importGPC($_REQUEST['month'] ?? null, 'integer', 0);
		$year = DevblocksPlatform::importGPC($_REQUEST['year'] ?? null, 'integer', 0);
		
		if(false == ($calendar = DAO_Calendar::get($calendar_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!Context_Calendar::isReadableByActor($calendar, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$start_on_mon = (bool)($calendar->params['start_on_mon'] ?? false);
		
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
	}
	
	private function _internalAction_getCalendarDatasourceParams() {
		$extension_id = DevblocksPlatform::importGPC($_REQUEST['extension_id'] ?? null, 'string', '');
		$owner = DevblocksPlatform::importGPC($_REQUEST['owner'] ?? null, 'string', '');
		$params_prefix = DevblocksPlatform::importGPC($_REQUEST['params_prefix'] ?? null, 'string', '');
		
		if(empty($extension_id))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(false == ($extension = Extension_CalendarDatasource::get($extension_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		$calendar = new Model_Calendar();
		
		// Do we have a target owner?
		if($owner) {
			$owner_parts = explode(':', $owner);
			
			if(is_array($owner_parts) && 2 == count($owner_parts)) {
				$calendar->owner_context = $owner_parts[0];
				$calendar->owner_context_id = intval($owner_parts[1]);
			}
		}
		
		$extension->renderConfig($calendar, [], $params_prefix);
	}
	
	private function _internalAction_parseDateJson() {
		$date_string = DevblocksPlatform::importGPC($_POST['date'] ?? null, 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$date = DevblocksPlatform::services()->date();
		
		header('Content-Type: application/json');
		
		$results = [];
		
		if(strpos($date_string, '@')) {
			$date_parts = explode('@', $date_string, 2);
			@$date_string = trim($date_parts[0]);
			@$calendar_lookup = trim($date_parts[1]);
			
			@$calendars = DAO_Calendar::getReadableByActor($active_worker);
			$use_calendar = null; /* @var $use_calendar Model_Calendar */
			
			foreach($calendars as $calendar) {
				if($use_calendar)
					continue;
				
				if(preg_match(sprintf("/^%s/i", preg_quote($calendar_lookup)), $calendar->name)) {
					$use_calendar = $calendar;
					break;
				}
			}
			
			if($use_calendar) {
				$calendar_events = $use_calendar->getEvents(strtotime('-1 day midnight'), strtotime('+2 weeks 23:59:59'));
				$availability = $use_calendar->computeAvailability(strtotime('-1 day midnight'), strtotime('+2 weeks 23:59:59'), $calendar_events);
				$timestamp = $availability->scheduleInRelativeTime(time(), $date_string);
				
				if($timestamp) {
					$results['calendar_id'] = $use_calendar->id;
					$results['calendar_name'] = $use_calendar->name;
				}
			}
		}
		
		if(empty($timestamp))
			$timestamp = DevblocksPlatform::services()->date()->parseDateString($date_string);
		
		if(empty($timestamp)) {
			echo json_encode(false);
			return;
		}
		
		$results['timestamp'] = $timestamp;
		$results['to_string'] = $date->formatTime(null, $timestamp);
		
		echo json_encode($results);
	}
	
	private function _internalAction_getDateInputAutoCompleteOptionsJson() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		$term = DevblocksPlatform::importGPC($_REQUEST['term'] ?? null, 'string', '');
		
		$calendars = DAO_Calendar::getReadableByActor($active_worker);
		$date = DevblocksPlatform::services()->date();
		$timezones = $date->getTimezones();

		header('Content-Type: application/json');
		
		$options = [];
		
		if(empty($term) || substr($term,0,1) == '@')
		foreach($calendars as $calendar) {
			if(0 == strcasecmp($term, substr('@'.$calendar->name, 0, strlen($term))))
				$options[] = '@' . $calendar->name;
		}
		
		foreach($timezones as $tz) {
			$areas = explode('/', $tz);
			$areas[] = $tz;
			
			foreach($areas as $area) {
				if(0 == strcasecmp($term, substr($area, 0, strlen($term)))) {
					$options[] = $tz;
					break;
				}
			}
		}
		
		echo json_encode($options);
	}
}