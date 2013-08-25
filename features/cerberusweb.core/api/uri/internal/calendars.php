<?php
/***********************************************************************
 | Cerb(tm) developed by Webgroup Media, LLC.
 |-----------------------------------------------------------------------
 | All source code & content (c) Copyright 2013, Webgroup Media LLC
 |   unless specifically noted otherwise.
 |
 | This source code is released under the Devblocks Public License.
 | The latest version of this license can be found here:
 | http://cerberusweb.com/license
 |
 | By using this software, you acknowledge having read this license
 | and agree to be bound thereby.
 | ______________________________________________________________________
 |	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
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
		$visit = CerberusApplication::getVisit();

		if(!empty($point))
			$visit->set($point, 'calendar');

		if(null == ($calendar = DAO_Calendar::get($calendar_id))) /* @var Model_Calendar $calendar */
			return;
		
		$calendar_properties = DevblocksCalendarHelper::getCalendar($month, $year);
		
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
	
	function showCalendarsTabAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer',0);
		@$point = DevblocksPlatform::importGPC($_REQUEST['point'],'string','');
		
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();
		$visit = CerberusApplication::getVisit();
		$tpl = DevblocksPlatform::getTemplateService();
		
		if(empty($context))
			return;

		// Remember tab
		if(!empty($point))
			$visit->set($point, 'calendars');
		
		$tpl->assign('owner_context', $context);
		$tpl->assign('owner_context_id', $context_id);
		
		$view_id = str_replace('.','_',$point) . '_calendars';
		
		$view = C4_AbstractViewLoader::getView($view_id);
		
		if(null == $view) {
			$ctx = Extension_DevblocksContext::get(CerberusContexts::CONTEXT_CALENDAR);
			$view = $ctx->getChooserView($view_id);
		}
		
		if($active_worker->is_superuser && 0 == strcasecmp($context, 'all')) {
			$view->addParamsRequired(array(), true);
			
		} else {
			$view->addParamsRequired(array(
				SearchFields_Calendar::OWNER_CONTEXT => new DevblocksSearchCriteria(SearchFields_Calendar::OWNER_CONTEXT, DevblocksSearchCriteria::OPER_EQ, $context),
				SearchFields_Calendar::OWNER_CONTEXT_ID => new DevblocksSearchCriteria(SearchFields_Calendar::OWNER_CONTEXT_ID, DevblocksSearchCriteria::OPER_EQ, $context_id),
			), true);
		}
		
		C4_AbstractViewLoader::setView($view->id,$view);
		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:cerberusweb.core::internal/views/search_and_view.tpl');
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
		$visit = CerberusApplication::getVisit();

		if(!empty($point))
			$visit->set($point, 'calendar');

		$tpl->assign('context', $context);
		$tpl->assign('context_id', $context_id);
		
		$calendar_properties = DevblocksCalendarHelper::getCalendar($month, $year);
		$tpl->assign('calendar_properties', $calendar_properties);
		
		if(null != ($calendar = DAO_Calendar::get($calendar_id))) {
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
	
	function saveCalendarPeekAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$name = DevblocksPlatform::importGPC($_REQUEST['name'], 'string', '');
		@$owner = DevblocksPlatform::importGPC($_REQUEST['owner'], 'string', '');
		@$params = DevblocksPlatform::importGPC($_REQUEST['params'], 'array', array());
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!empty($id) && !empty($do_delete)) { // Delete
			DAO_Calendar::delete($id);
			
		} else {
			// Owner
			
			$owner_ctx = '';
			@list($owner_ctx_code, $owner_ctx_id) = explode('_', $owner, 2);
			
			switch(strtolower($owner_ctx_code)) {
				case 'a':
					$owner_ctx = CerberusContexts::CONTEXT_APPLICATION;
					break;
				case 'w':
					$owner_ctx = CerberusContexts::CONTEXT_WORKER;
					break;
				case 'g':
					$owner_ctx = CerberusContexts::CONTEXT_GROUP;
					break;
				case 'r':
					$owner_ctx = CerberusContexts::CONTEXT_ROLE;
					break;
				case 'v':
					$owner_ctx = CerberusContexts::CONTEXT_VIRTUAL_ATTENDANT;
					break;
			}
			
			if(empty($owner_ctx))
				return;
			
			// Clean params
			// [TODO] Move this
			
			if(isset($params['series']))
			foreach($params['series'] as $series_idx => $series) {
				if(isset($series['worklist_model_json'])) {
					$series['worklist_model'] = json_decode($series['worklist_model_json'], true);
					unset($series['worklist_model_json']);
					$params['series'][$series_idx] = $series;
				}
			}
			
			// Model
			
			if(empty($id)) { // New
				$fields = array(
					DAO_Calendar::UPDATED_AT => time(),
					DAO_Calendar::NAME => $name,
					DAO_Calendar::OWNER_CONTEXT => $owner_ctx,
					DAO_Calendar::OWNER_CONTEXT_ID => $owner_ctx_id,
					DAO_Calendar::PARAMS_JSON => json_encode($params),
				);
				$id = DAO_Calendar::create($fields);
				
				// Watchers
				@$add_watcher_ids = DevblocksPlatform::sanitizeArray(DevblocksPlatform::importGPC($_REQUEST['add_watcher_ids'],'array',array()),'integer',array('unique','nonzero'));
				if(!empty($add_watcher_ids))
					CerberusContexts::addWatchers(CerberusContexts::CONTEXT_CALENDAR, $id, $add_watcher_ids);
				
				// Context Link (if given)
				@$link_context = DevblocksPlatform::importGPC($_REQUEST['link_context'],'string','');
				@$link_context_id = DevblocksPlatform::importGPC($_REQUEST['link_context_id'],'integer','');
				if(!empty($id) && !empty($link_context) && !empty($link_context_id)) {
					DAO_ContextLink::setLink(CerberusContexts::CONTEXT_CALENDAR, $id, $link_context, $link_context_id);
				}
				
				if(!empty($view_id) && !empty($id))
					C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_CALENDAR, $id);
				
			} else { // Edit
				$fields = array(
					DAO_Calendar::UPDATED_AT => time(),
					DAO_Calendar::NAME => $name,
					DAO_Calendar::OWNER_CONTEXT => $owner_ctx,
					DAO_Calendar::OWNER_CONTEXT_ID => $owner_ctx_id,
					DAO_Calendar::PARAMS_JSON => json_encode($params),
				);
				DAO_Calendar::update($id, $fields);
				
			}

			// Custom fields
			@$field_ids = DevblocksPlatform::importGPC($_REQUEST['field_ids'], 'array', array());
			DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_CALENDAR, $id, $field_ids);
		}
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
			
			// Context Link (if given)
			@$link_context = DevblocksPlatform::importGPC($_REQUEST['link_context'],'string','');
			@$link_context_id = DevblocksPlatform::importGPC($_REQUEST['link_context_id'],'integer','');
			if(!empty($event_id) && !empty($link_context) && !empty($link_context_id)) {
				DAO_ContextLink::setLink(CerberusContexts::CONTEXT_CALENDAR_EVENT, $event_id, $link_context, $link_context_id);
			}
			
			// View marquee
			if(!empty($event_id) && !empty($view_id)) {
				C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_CALENDAR_EVENT, $event_id);
			}
			
		} else {
			DAO_CalendarEvent::update($event_id, $fields);
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