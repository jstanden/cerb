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

		// [TODO] Test owner read/write access
		if(null == ($calendar = DAO_Calendar::get($calendar_id))) /* @var Model_Calendar $calendar */
			return;
		
		$calendar_properties = DevblocksCalendarHelper::getCalendar($month, $year);
		
		if(null == ($datasource_extension = $calendar->getDatasourceExtension()))
			return;
		
		$calendar_events = $datasource_extension->getData($calendar, $calendar_properties['date_range_from'], $calendar_properties['date_range_to']);
		
		// Template scope
		
		$tpl->assign('calendar', $calendar);
		$tpl->assign('datasource_extension', $datasource_extension);
		$tpl->assign('calendar_events', $calendar_events);
		$tpl->assign('calendar_properties', $calendar_properties);
		
		switch($datasource_extension->id) {
			case 'calendar.datasource.manual':
				$context = CerberusContexts::CONTEXT_CALENDAR_EVENT;
				break;
				
			case 'calendar.datasource.worklist':
				@$context = $calendar->params['worklist_model']['context'];
				break;
		}
		
		if(
			!empty($context)
			&& false !== ($context_ext = Extension_DevblocksContext::get($context))
			&& isset($context_ext->manifest->params['options'][0]['create'])
			) {
			$tpl->assign('context', $context);
		}
		
		// Template
		
		$tpl->display('devblocks:cerberusweb.core::internal/calendar_event/tab.tpl');
	}
	
	function saveCalendarPeekAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$name = DevblocksPlatform::importGPC($_REQUEST['name'], 'string', '');
		@$owner = DevblocksPlatform::importGPC($_REQUEST['owner'], 'string', '');
		@$extension_id = DevblocksPlatform::importGPC($_REQUEST['extension_id'], 'string', '');
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
				case 'w':
					$owner_ctx = CerberusContexts::CONTEXT_WORKER;
					break;
				case 'g':
					$owner_ctx = CerberusContexts::CONTEXT_GROUP;
					break;
				case 'r':
					$owner_ctx = CerberusContexts::CONTEXT_ROLE;
					break;
			}
			
			if(empty($owner_ctx))
				return;
			
			
			if(isset($params['worklist_model_json'])) {
				$params['worklist_model'] = json_decode($params['worklist_model_json'], true);
				unset($params['worklist_model_json']);
			}
			
			// Model
			
			if(empty($id)) { // New
				$fields = array(
					DAO_Calendar::UPDATED_AT => time(),
					DAO_Calendar::NAME => $name,
					DAO_Calendar::OWNER_CONTEXT => $owner_ctx,
					DAO_Calendar::OWNER_CONTEXT_ID => $owner_ctx_id,
					DAO_Calendar::EXTENSION_ID => $extension_id,
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
					DAO_Calendar::EXTENSION_ID => $extension_id,
					DAO_Calendar::PARAMS_JSON => json_encode($params),
				);
				DAO_Calendar::update($id, $fields);
				
			}

			// If we're adding a comment
			if(!empty($comment)) {
				@$also_notify_worker_ids = DevblocksPlatform::importGPC($_REQUEST['notify_worker_ids'],'array',array());
				
				$fields = array(
					DAO_Comment::CREATED => time(),
					DAO_Comment::CONTEXT => CerberusContexts::CONTEXT_CALENDAR,
					DAO_Comment::CONTEXT_ID => $id,
					DAO_Comment::COMMENT => $comment,
					DAO_Comment::OWNER_CONTEXT => CerberusContexts::CONTEXT_WORKER,
					DAO_Comment::OWNER_CONTEXT_ID => $active_worker->id,
				);
				$comment_id = DAO_Comment::create($fields, $also_notify_worker_ids);
			}
			
			// Custom fields
			@$field_ids = DevblocksPlatform::importGPC($_REQUEST['field_ids'], 'array', array());
			DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_CALENDAR, $id, $field_ids);
		}
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