<?php
/***********************************************************************
 | Cerb(tm) developed by Webgroup Media, LLC.
 |-----------------------------------------------------------------------
 | All source code & content (c) Copyright 2002-2016, Webgroup Media LLC
 |   unless specifically noted otherwise.
 |
 | This source code is released under the Devblocks Public License.
 | The latest version of this license can be found here:
 | http://cerb.io/license
 |
 | By using this software, you acknowledge having read this license
 | and agree to be bound thereby.
 | ______________________________________________________________________
 |	http://cerb.io	    http://webgroup.media
 ***********************************************************************/

if(class_exists('Extension_PageSection')):
class PageSection_InternalCalendars extends Extension_PageSection {
	function render() {}
	
	function showCalendarTabAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$calendar_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer');
		
		@$point = DevblocksPlatform::importGPC($_REQUEST['point'],'string','');
		@$month = DevblocksPlatform::importGPC($_REQUEST['month'],'integer', 0);
		@$year = DevblocksPlatform::importGPC($_REQUEST['year'],'integer', 0);
		
		$tpl = DevblocksPlatform::getTemplateService();

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
		
		// Contexts (for creating events)

		if(CerberusContexts::isWriteableByActor($calendar->owner_context, $calendar->owner_context_id, $active_worker)) {
			$create_contexts = $calendar->getCreateContexts();
			$tpl->assign('create_contexts', $create_contexts);
		}
		
		// Template
		
		$tpl->display('devblocks:cerberusweb.core::internal/calendar/tab.tpl');
	}
	
	function showCalendarAvailabilityTabAction() {
		@$calendar_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer');
		
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer', 0);
		@$point = DevblocksPlatform::importGPC($_REQUEST['point'],'string','');
		@$month = DevblocksPlatform::importGPC($_REQUEST['month'],'integer', 0);
		@$year = DevblocksPlatform::importGPC($_REQUEST['year'],'integer', 0);
		
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl = DevblocksPlatform::getTemplateService();

		$calendar = DAO_Calendar::get($calendar_id);
		
		$tpl->assign('context', $context);
		$tpl->assign('context_id', $context_id);
		
		$start_on_mon = @$calendar->params['start_on_mon'] ? true : false;
		
		$calendar_properties = DevblocksCalendarHelper::getCalendar($month, $year, $start_on_mon);
		$tpl->assign('calendar_properties', $calendar_properties);
		
		if($calendar) {
			$calendar_events = $calendar->getEvents($calendar_properties['date_range_from'], $calendar_properties['date_range_to']);
			
			$availability = $calendar->computeAvailability($calendar_properties['date_range_from'], $calendar_properties['date_range_to'], $calendar_events);
	
			unset($calendar_events);
			
			// Convert availability back to abstract calendar events
	
			$calendar_events = $availability->getAsCalendarEvents($calendar_properties);
			
			$tpl->assign('calendar', $calendar);
			$tpl->assign('calendar_events', $calendar_events);
			
			// Contexts (for creating events)

			if(CerberusContexts::isWriteableByActor($calendar->owner_context, $calendar->owner_context_id, $active_worker)) {
				$create_contexts = $calendar->getCreateContexts();
				$tpl->assign('create_contexts', $create_contexts);
			}
			
		} else {
			$calendars = DAO_Calendar::getOwnedByWorker($active_worker);
			$tpl->assign('calendars', $calendars);
			
		}
		
		$tpl->display('devblocks:cerberusweb.core::internal/calendar/tab_availability.tpl');
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
	
	function saveCalendarEventPopupJsonAction() {
		@$event_id = DevblocksPlatform::importGPC($_REQUEST['event_id'],'integer', 0);
		@$name = DevblocksPlatform::importGPC($_REQUEST['name'],'string', '');
		@$date_start = DevblocksPlatform::importGPC($_REQUEST['date_start'],'string', '');
		@$date_end = DevblocksPlatform::importGPC($_REQUEST['date_end'],'string', '');
		@$is_available = DevblocksPlatform::importGPC($_REQUEST['is_available'],'integer', 0);
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'],'integer', 0);
		
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string', '');

		@$calendar_id = DevblocksPlatform::importGPC($_REQUEST['calendar_id'],'integer', 0);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		header("Content-type: application/json");
		
		// Start/end times

		@$timestamp_start = strtotime($date_start);
		
		if(empty($timestamp_start))
			$timestamp_start = time();
		
		@$timestamp_end = strtotime($date_end, $timestamp_start);

		if(empty($timestamp_end))
			$timestamp_end = $timestamp_start;
			
		// If the second timestamp is smaller, add a day
		if($timestamp_end < $timestamp_start)
			$timestamp_end = strtotime("+1 day", $timestamp_end);
		
		// Delete
		
		if(!empty($do_delete) && !empty($event_id)) {
			DAO_CalendarEvent::delete($event_id);
			
			echo json_encode(array(
				'event_id' => intval($event_id),
				'action' => 'delete',
			));
			return;
		}
		
		// Fields
		
		$fields = array(
			DAO_CalendarEvent::NAME => $name,
			DAO_CalendarEvent::DATE_START => $timestamp_start,
			DAO_CalendarEvent::DATE_END => $timestamp_end,
			DAO_CalendarEvent::IS_AVAILABLE => (!empty($is_available)) ? 1 : 0,
		);
		
		if(empty($event_id)) {
			$fields[DAO_CalendarEvent::CALENDAR_ID] = $calendar_id;
			$event_id = DAO_CalendarEvent::create($fields);
			
			// View marquee
			if(!empty($event_id) && !empty($view_id)) {
				C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_CALENDAR_EVENT, $event_id);
			}
			
		} else {
			if(false == ($calendar_event = DAO_CalendarEvent::get($event_id)))
				return;
			
			$changed_fields = Cerb_ORMHelper::uniqueFields($fields, $calendar_event);
			
			if(!empty($changed_fields))
				DAO_CalendarEvent::update($event_id, $changed_fields);
		}
		
		// Custom fields
		@$field_ids = DevblocksPlatform::importGPC($_REQUEST['field_ids'], 'array', array());
		DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_CALENDAR_EVENT, $event_id, $field_ids);
		
		echo json_encode(array(
			'event_id' => intval($event_id),
			'action' => 'modify',
			'month' => intval(date('m', $timestamp_start)),
			'year' => intval(date('Y', $timestamp_start)),
		));
		return;
	}
	
	function parseDateJsonAction() {
		@$date_string = DevblocksPlatform::importGPC($_REQUEST['date'], 'string', '');
		
		@$active_worker = CerberusApplication::getActiveWorker();
		$date = DevblocksPlatform::getDateService();
		
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
		$date = DevblocksPlatform::getDateService();
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
endif;