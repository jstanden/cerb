<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2017, Webgroup Media LLC
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

class PageSection_ProfilesCalendarEvent extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$request = DevblocksPlatform::getHttpRequest();
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		$stack = $request->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // calendar_event
		@$id = intval(array_shift($stack));
		
		if(null == ($event = DAO_CalendarEvent::get($id)))
			return;
		
		$tpl->assign('event', $event);

		// Remember the last tab/URL
		
		$point = sprintf("cerberusweb.profiles.calendar_event.%d", $event->id);
		$tpl->assign('point', $point);

		// Properties
		
		$translate = DevblocksPlatform::getTranslationService();

		$properties = array();

		$properties['calendar_id'] = array(
			'label' => mb_ucfirst($translate->_('common.calendar')),
			'type' => Model_CustomField::TYPE_LINK,
			'params' => array('context' => CerberusContexts::CONTEXT_CALENDAR),
			'value' => $event->calendar_id,
		);
		
		$properties['date_start'] = array(
			'label' => mb_ucfirst($translate->_('dao.calendar_event.date_start')),
			'type' => null,
			'value' => $event->date_start,
		);
		
		$properties['date_end'] = array(
			'label' => mb_ucfirst($translate->_('dao.calendar_event.date_end')),
			'type' => null,
			'value' => $event->date_end,
		);
		
		$properties['is_available'] = array(
			'label' => mb_ucfirst($translate->_('dao.calendar_event.is_available')),
			'type' => Model_CustomField::TYPE_CHECKBOX,
			'value' => $event->is_available,
		);

		// Custom Fields

		@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_CALENDAR_EVENT, $event->id)) or array();
		$tpl->assign('custom_field_values', $values);
		
		$properties_cfields = Page_Profiles::getProfilePropertiesCustomFields(CerberusContexts::CONTEXT_CALENDAR_EVENT, $values);
		
		if(!empty($properties_cfields))
			$properties = array_merge($properties, $properties_cfields);
		
		// Custom Fieldsets

		$properties_custom_fieldsets = Page_Profiles::getProfilePropertiesCustomFieldsets(CerberusContexts::CONTEXT_CALENDAR_EVENT, $event->id, $values);
		$tpl->assign('properties_custom_fieldsets', $properties_custom_fieldsets);
		
		// Link counts
		
		$properties_links = array(
			CerberusContexts::CONTEXT_CALENDAR_EVENT => array(
				$event->id => 
					DAO_ContextLink::getContextLinkCounts(
						CerberusContexts::CONTEXT_CALENDAR_EVENT,
						$event->id,
						array(CerberusContexts::CONTEXT_CUSTOM_FIELDSET)
					),
			),
		);
		
		if(!empty($event->calendar_id)) {
			$properties_links[CerberusContexts::CONTEXT_CALENDAR] = array(
				$event->calendar_id => 
					DAO_ContextLink::getContextLinkCounts(
						CerberusContexts::CONTEXT_CALENDAR,
						$event->calendar_id,
						array(CerberusContexts::CONTEXT_CUSTOM_FIELDSET)
					),
			);
		}
		
		$tpl->assign('properties_links', $properties_links);
		
		// Properties
		
		$tpl->assign('properties', $properties);
		
		// Macros
		
		$macros = DAO_TriggerEvent::getReadableByActor(
			$active_worker,
			'event.macro.calendar_event'
		);
		$tpl->assign('macros', $macros);

		// Tabs
		$tab_manifests = Extension_ContextProfileTab::getExtensions(false, CerberusContexts::CONTEXT_CALENDAR_EVENT);
		$tpl->assign('tab_manifests', $tab_manifests);
		
		// Template
		$tpl->display('devblocks:cerberusweb.core::profiles/calendar_event.tpl');
	}
	
	function savePeekJsonAction() {
		@$event_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer', 0);
		@$name = DevblocksPlatform::importGPC($_REQUEST['name'],'string', '');
		@$date_start = DevblocksPlatform::importGPC($_REQUEST['date_start'],'string', '');
		@$date_end = DevblocksPlatform::importGPC($_REQUEST['date_end'],'string', '');
		@$is_available = DevblocksPlatform::importGPC($_REQUEST['is_available'],'integer', 0);
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'],'integer', 0);
		
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string', '');

		@$calendar_id = DevblocksPlatform::importGPC($_REQUEST['calendar_id'],'integer', 0);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		header("Content-type: application/json");
		
		try {
			// Delete
			if(!empty($do_delete) && !empty($event_id)) {
				// [TODO] Check ACL
				DAO_CalendarEvent::delete($event_id);
				
				echo json_encode(array(
					'status' => true,
					'id' => intval($event_id),
					'event_id' => intval($event_id),
					'view_id' => $view_id,
					'action' => 'delete',
				));
				return;
			}
			
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
			
			// Fields
			
			if(empty($name))
				throw new Exception_DevblocksAjaxValidationError("The 'Name:' is required.", 'name');
			
			if(empty($date_start))
				throw new Exception_DevblocksAjaxValidationError("The 'Start Time:' is required.", 'date_start');
			
			$fields = array(
				DAO_CalendarEvent::NAME => $name,
				DAO_CalendarEvent::DATE_START => $timestamp_start,
				DAO_CalendarEvent::DATE_END => $timestamp_end,
				DAO_CalendarEvent::IS_AVAILABLE => (!empty($is_available)) ? 1 : 0,
			);
			
			if(empty($event_id)) {
				if(empty($calendar_id))
					throw new Exception_DevblocksAjaxValidationError("The 'Calendar:' is required.", 'calendar_id');
				
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
			if($event_id) {
				@$field_ids = DevblocksPlatform::importGPC($_REQUEST['field_ids'], 'array', array());
				DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_CALENDAR_EVENT, $event_id, $field_ids);
			}
			
			echo json_encode(array(
				'status' => true,
				'id' => intval($event_id),
				'label' => $name,
				'view_id' => $view_id,
				'event_id' => intval($event_id),
				'action' => 'modify',
				'month' => intval(date('m', $timestamp_start)),
				'year' => intval(date('Y', $timestamp_start)),
			));
			return;
			
		} catch (Exception_DevblocksAjaxValidationError $e) {
			echo json_encode(array(
				'status' => false,
				'error' => $e->getMessage(),
				'field' => $e->getFieldName(),
			));
			return;
			
		} catch (Exception $e) {
			echo json_encode(array(
				'status' => false,
				'error' => 'An error occurred.',
			));
			return;
		}
	}
	
};