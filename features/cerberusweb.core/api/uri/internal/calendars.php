<?php
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
	
	function showCalendarTabAction() {
		@$calendar_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer');
		
		@$month = DevblocksPlatform::importGPC($_REQUEST['month'],'integer', 0);
		@$year = DevblocksPlatform::importGPC($_REQUEST['year'],'integer', 0);
		
		$tpl = DevblocksPlatform::services()->template();

		if(null == ($calendar = DAO_Calendar::get($calendar_id))) /* @var Model_Calendar $calendar */
			return;
		
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
		
		// Template
		
		$tpl->display('devblocks:cerberusweb.core::internal/calendar/tab.tpl');
	}
	
	function getCalendarDatasourceParamsAction() {
		@$extension_id = DevblocksPlatform::importGPC($_REQUEST['extension_id'],'string', '');
		@$params_prefix = DevblocksPlatform::importGPC($_REQUEST['params_prefix'],'string', '');
		
		if(empty($extension_id))
			return;
		
		if(false == ($extension = Extension_CalendarDatasource::get($extension_id)))
			return;
		
		$calendar = new Model_Calendar();
		$extension->renderConfig($calendar, array(), $params_prefix);
	}
	
	function parseDateJsonAction() {
		@$date_string = DevblocksPlatform::importGPC($_REQUEST['date'], 'string', '');
		
		@$active_worker = CerberusApplication::getActiveWorker();
		$date = DevblocksPlatform::services()->date();
		
		header('Content-Type: application/json');
		
		$results = array();
		
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
					$results['calendar_id'] = $calendar->id;
					$results['calendar_name'] = $calendar->name;
				}
			}
		}
		
		if(empty($timestamp))
			@$timestamp = strtotime($date_string);
		
		if(empty($timestamp)) {
			echo json_encode(false);
			return;
		}
		
		$results['timestamp'] = $timestamp;
		$results['to_string'] = $date->formatTime(null, $timestamp);
		
		echo json_encode($results);
	}
	
	function getDateInputAutoCompleteOptionsJsonAction() {
		@$term = DevblocksPlatform::importGPC($_REQUEST['term'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		$calendars = DAO_Calendar::getReadableByActor($active_worker);
		$date = DevblocksPlatform::services()->date();
		$timezones = $date->getTimezones();

		header('Content-Type: application/json');
		
		$options = array();
		
		if(empty($term) || substr($term,0,1) == '@')
		foreach($calendars as $calendar) {
			if(0 == strcasecmp($term, substr('@'.$calendar->name, 0, strlen($term))))
				$options[] = '@' . $calendar->name;
		}
		
		foreach($timezones as $tz) {
			$areas = explode('/', $tz);
			$areas[] = $tz;
			$found = false;
			
			foreach($areas as $area) {
				if($found)
					continue;
				
				if(0 == strcasecmp($term, substr($area, 0, strlen($term)))) {
					$found = true;
					$options[] = $tz;
					break;
				}
			}
		}
		
		echo json_encode($options);
	}
}