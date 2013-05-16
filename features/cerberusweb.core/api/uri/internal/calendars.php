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
		@$calendar_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer');
		
		@$point = DevblocksPlatform::importGPC($_REQUEST['point'],'string','');
		@$month = DevblocksPlatform::importGPC($_REQUEST['month'],'integer', 0);
		@$year = DevblocksPlatform::importGPC($_REQUEST['year'],'integer', 0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();

		if(!empty($point))
			$visit->set($point, 'calendar');

		if(null == ($calendar = DAO_Calendar::get($calendar_id)))
			return;
		
		// [TODO] Test owner read/write access
		
		// [TODO] Validate month/year
		// [TODO] gmdate + gmmktime?

		if(empty($month) || empty($year)) {
			$month = date('m');
			$year = date('Y');
		}
		
		$calendar_date = mktime(0,0,0,$month,1,$year);
		
		$num_days = date('t', $calendar_date);
		$first_dow = date('w', $calendar_date);
		
		$prev_month_date = mktime(0,0,0,$month,0,$year);
		$prev_month = date('m', $prev_month_date);
		$prev_year = date('Y', $prev_month_date);

		$next_month_date = mktime(0,0,0,$month+1,1,$year);
		$next_month = date('m', $next_month_date);
		$next_year = date('Y', $next_month_date);
		
		$days = array();

		for($day = 1; $day <= $num_days; $day++) {
			$timestamp = mktime(0,0,0,$month,$day,$year);
			
			$days[$timestamp] = array(
				'dom' => $day,
				'dow' => (($first_dow+$day-1) % 7),
				'is_padding' => false,
				'timestamp' => $timestamp,
			);
		}
		
		// How many cells do we need to pad the first and last weeks?
		$first_day = reset($days);
		$left_pad = $first_day['dow'];
		$last_day = end($days);
		$right_pad = 6-$last_day['dow'];

		$calendar_cells = $days;
		
		if($left_pad > 0) {
			$prev_month_days = date('t', $prev_month_date);
			
			for($i=1;$i<=$left_pad;$i++) {
				$dom = $prev_month_days - ($i-1);
				$timestamp = mktime(0,0,0,$prev_month,$dom,$prev_year);
				$day = array(
					'dom' => $dom,
					'dow' => $first_dow - $i,
					'is_padding' => true,
					'timestamp' => $timestamp,
				);
				$calendar_cells[$timestamp] = $day;
			}
		}
		
		if($right_pad > 0) {
			for($i=1;$i<=$right_pad;$i++) {
				$timestamp = mktime(0,0,0,$next_month,$i,$next_year);
				
				$day = array(
					'dom' => $i,
					'dow' => (($first_dow + $num_days + $i - 1) % 7),
					'is_padding' => true,
					'timestamp' => $timestamp,
				);
				$calendar_cells[$timestamp] = $day;
			}
		}
		
		// Sort calendar
		ksort($calendar_cells);
		
		// Break into weeks
		$calendar_weeks = array_chunk($calendar_cells, 7, true);

		// Events
		$first_cell = array_slice($calendar_cells, 0, 1, false);
		$last_cell = array_slice($calendar_cells, -1, 1, false);
		$range_from = array_shift($first_cell);
		$range_to = array_shift($last_cell);
		
		unset($days);
		unset($calendar_cells);
		
		$date_range_from = strtotime('00:00', $range_from['timestamp']);
		$date_range_to = strtotime('23:59', $range_to['timestamp']);
		
		// [TODO] Calendar datasources (manual, worklist, sync)
		// [TODO] Convert to DAO
		
		$db = DevblocksPlatform::getDatabaseService();
		$sql = sprintf(
			"SELECT id, name, recurring_id, is_available, date_start, date_end ".
			"FROM calendar_event ".
			"WHERE calendar_id = %d ".
			"AND ((date_start >= %d AND date_start <= %d) OR (date_end >= %d AND date_end <= %d)) ".
			"ORDER BY is_available DESC, date_start ASC",
			$calendar_id,
			$date_range_from,
			$date_range_to,
			$date_range_from,
			$date_range_to
		);
		
		$results = $db->GetArray($sql);

		$calendar_events = array();
		
		foreach($results as $row) {
			$day_range = range(strtotime('midnight', $row['date_start']), strtotime('midnight', $row['date_end']), 86400);
			
			foreach($day_range as $epoch) {
				if(!isset($calendar_events[$epoch]))
					$calendar_events[$epoch] = array();
				
				$calendar_events[$epoch][$row['id']] = array(
					'id' => $row['id'],
					'name' => $row['name'],
					'is_available' => $row['is_available'],
				);
			}
		}

		// Template scope
		$tpl->assign('calendar', $calendar);
		$tpl->assign('today', strtotime('today'));
		$tpl->assign('prev_month', $prev_month);
		$tpl->assign('prev_year', $prev_year);
		$tpl->assign('next_month', $next_month);
		$tpl->assign('next_year', $next_year);
		$tpl->assign('calendar_date', $calendar_date);
		$tpl->assign('calendar_weeks', $calendar_weeks);
		$tpl->assign('calendar_events', $calendar_events);
		
		$tpl->display('devblocks:cerberusweb.core::internal/calendar_event/tab.tpl');
	}
	
	function saveCalendarEventPopupJsonAction() {
		@$event_id = DevblocksPlatform::importGPC($_REQUEST['event_id'],'integer', 0);
		@$name = DevblocksPlatform::importGPC($_REQUEST['name'],'string', '');
		@$date_start = DevblocksPlatform::importGPC($_REQUEST['date_start'],'string', '');
		@$date_end = DevblocksPlatform::importGPC($_REQUEST['date_end'],'string', '');
		@$is_available = DevblocksPlatform::importGPC($_REQUEST['is_available'],'integer', 0);
		@$repeat_freq = DevblocksPlatform::importGPC($_REQUEST['repeat_freq'],'string', '');
		@$repeat_end = DevblocksPlatform::importGPC($_REQUEST['repeat_end'],'string', '');
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'],'integer', 0);
		
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string', '');

		@$calendar_id = DevblocksPlatform::importGPC($_REQUEST['calendar_id'],'integer', 0);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		/*
		 * [TODO] When deleting a recurring profile, ask about deleting its children (this/all)
		 */

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
		
		// Recurring events
		
		$event = null;
		$recurring_id = 0;
		
		if(!empty($event_id)) {
			if(null != ($event = DAO_CalendarEvent::get($event_id)))
				$recurring_id = $event->recurring_id;
		}
		
		// Delete
		
		if(!empty($do_delete) && !empty($event_id)) {
			if(!empty($recurring_id)) {
				@$delete_scope = DevblocksPlatform::importGPC($_REQUEST['delete_scope'],'string', '');
				
				switch($delete_scope) {
					case 'future':
					case 'all':
						$starting_date = ($delete_scope == 'future') ? $timestamp_start : 0;
						
						DAO_CalendarEvent::deleteByRecurringIds($recurring_id, $starting_date);
						
						// Remove recurring profile
						DAO_CalendarRecurringProfile::delete($recurring_id);
						
						// Removing recurring profile from remaining events (like deleting it, but not existing events)
						DAO_CalendarEvent::updateWhere(
							array(
								DAO_CalendarEvent::RECURRING_ID => 0,
							),
							sprintf("%s = %d",
								DAO_CalendarEvent::RECURRING_ID,
								$recurring_id
							)
						);
						break;
				}
			}
			
			DAO_CalendarEvent::delete($event_id);
			
			echo json_encode(array(
				'event_id' => intval($event_id),
				'action' => 'delete',
			));
			return;
		}
		
		// Recurring
		
		if(!empty($repeat_freq)) {
			@$repeat_options = DevblocksPlatform::importGPC($_REQUEST['repeat_options'][$repeat_freq], 'array', array());
			@$repeat_ends = DevblocksPlatform::importGPC($_REQUEST['repeat_ends'][$repeat_end], 'array', array());

			switch($repeat_end) {
				case 'date':
					if(isset($repeat_ends['on'])) {
						$repeat_ends['on'] = strtotime("11:59pm", @strtotime($repeat_ends['on'], $timestamp_start));
					}
					break;
			}
			
			$params = array(
				'freq' => $repeat_freq,
				'options' => $repeat_options,
				'end' => array(
					'term' => $repeat_end,
					'options' => $repeat_ends,
				),
			);
			
		 	/*
		 	 * Create recurring profile if this is a new event, otherwise modify the
		 	 * existing one and all associated events.
		 	 */
			
			$recurring_has_changed = true;
			
			if(empty($recurring_id)) {
				$fields = array(
					DAO_CalendarRecurringProfile::EVENT_NAME => $name,
					DAO_CalendarRecurringProfile::IS_AVAILABLE => (!empty($is_available)) ? 1 : 0,
					DAO_CalendarRecurringProfile::DATE_START => $timestamp_start,
					DAO_CalendarRecurringProfile::DATE_END => $timestamp_end,
					DAO_CalendarRecurringProfile::CALENDAR_ID => $calendar_id,
					DAO_CalendarRecurringProfile::PARAMS_JSON => json_encode($params),
				);
				$recurring_id = DAO_CalendarRecurringProfile::create($fields);
				
			} else {
				@$edit_scope = DevblocksPlatform::importGPC($_REQUEST['edit_scope'],'string', 'future');

				if($edit_scope == 'this') {
					$recurring_has_changed = false;
				}
				
				if(null == ($recurring = DAO_CalendarRecurringProfile::get($recurring_id))) {
					$recurring_has_changed = false;
				}
				
				// Modify all events, or just this one?
				if(!$recurring_has_changed) {
					// Unassign the recurring profile
					$recurring_id = 0;
					
				} else {
					// Delete other events
					DAO_CalendarEvent::delete($event_id);
					DAO_CalendarEvent::deleteByRecurringIds($recurring_id, $timestamp_start);
					$prior_recurring_events = DAO_CalendarEvent::countByRecurringId($recurring_id);
					
					// If we're modifying the recurring profile, branch the profile (past + future)
					// Otherwise just edit the same recurring profile with the new details
					if($prior_recurring_events) {
						// We're closing out an old recurring profile
						$options = $recurring->params;
						
						// Set the end date of the old recurring profile to just before the new one
						if(isset($options['end']))
							unset($options['end']);
						$options['end'] = array(
							'term' => 'date',
							'options' => array(
								'on' => strtotime('yesterday 11:59pm', $timestamp_start),
							),
						);
						
						// Close out the old recurring profile
						$fields = array(
							DAO_CalendarRecurringProfile::PARAMS_JSON => json_encode($options),
						);
						DAO_CalendarRecurringProfile::update($recurring_id, $fields);
						
						// Create the new recurring profile
						$fields = array(
							DAO_CalendarRecurringProfile::EVENT_NAME => $name,
							DAO_CalendarRecurringProfile::IS_AVAILABLE => (!empty($is_available)) ? 1 : 0,
							DAO_CalendarRecurringProfile::DATE_START => $timestamp_start,
							DAO_CalendarRecurringProfile::DATE_END => $timestamp_end,
							DAO_CalendarRecurringProfile::CALENDAR_ID => $calendar_id,
							DAO_CalendarRecurringProfile::PARAMS_JSON => json_encode($params),
						);
						$recurring_id = DAO_CalendarRecurringProfile::create($fields);
						
					} else {
						$fields = array(
							DAO_CalendarRecurringProfile::EVENT_NAME => $name,
							DAO_CalendarRecurringProfile::IS_AVAILABLE => (!empty($is_available)) ? 1 : 0,
							DAO_CalendarRecurringProfile::DATE_START => $timestamp_start,
							DAO_CalendarRecurringProfile::DATE_END => $timestamp_end,
							DAO_CalendarRecurringProfile::PARAMS_JSON => json_encode($params),
						);
						DAO_CalendarRecurringProfile::update($recurring_id, $fields);
					}
				}
				
			}

			if($recurring_has_changed) {
				if(null != ($recurring = DAO_CalendarRecurringProfile::get($recurring_id)))
					$recurring->createRecurringEvents(strtotime("today", $timestamp_start));
				
				echo json_encode(array(
					'action' => 'recurring',
					'month' => intval(date('m', $timestamp_start)),
					'year' => intval(date('Y', $timestamp_start)),
				));
				return;
			}
		}
		
		// Fields
		
		$fields = array(
			DAO_CalendarEvent::NAME => $name,
			DAO_CalendarEvent::RECURRING_ID => $recurring_id,
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
}
endif;