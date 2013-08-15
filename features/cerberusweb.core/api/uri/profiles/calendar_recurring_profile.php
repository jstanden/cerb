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

class PageSection_ProfilesCalendarRecurringProfile extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // calendar_recurring_profile
		$id = array_shift($stack); // 123

		@$id = intval($id);
		
		if(null == ($calendar_recurring_profile = DAO_CalendarRecurringProfile::get($id))) {
			return;
		}
		$tpl->assign('calendar_recurring_profile', $calendar_recurring_profile);
	
		// Tab persistence
		
		$point = 'profiles.calendar_recurring_profile.tab';
		$tpl->assign('point', $point);
		
		if(null == (@$tab_selected = $stack[0])) {
			$tab_selected = $visit->get($point, '');
		}
		$tpl->assign('tab_selected', $tab_selected);
	
		// Properties
			
		$properties = array();
			
		$properties['calendar_id'] = array(
			'label' => ucfirst($translate->_('common.calendar')),
			'type' => null,
			'value' => DAO_Calendar::get($calendar_recurring_profile->calendar_id),
		);
		
		$properties['event_start'] = array(
			'label' => ucfirst($translate->_('dao.calendar_recurring_profile.event_start')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $calendar_recurring_profile->event_start,
		);
		
		$properties['event_end'] = array(
			'label' => ucfirst($translate->_('dao.calendar_recurring_profile.event_end')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $calendar_recurring_profile->event_end,
		);
		
		$properties['tz'] = array(
			'label' => ucfirst($translate->_('dao.calendar_recurring_profile.tz')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $calendar_recurring_profile->tz,
		);
		
		$properties['is_available'] = array(
			'label' => ucfirst($translate->_('dao.calendar_recurring_profile.is_available')),
			'type' => Model_CustomField::TYPE_CHECKBOX,
			'value' => $calendar_recurring_profile->is_available,
		);
		
		$properties['recur_start'] = array(
			'label' => ucfirst($translate->_('dao.calendar_recurring_profile.recur_start')),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $calendar_recurring_profile->recur_start,
		);
		
		$properties['recur_end'] = array(
			'label' => ucfirst($translate->_('dao.calendar_recurring_profile.recur_end')),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $calendar_recurring_profile->recur_end,
		);
		
		$properties['patterns'] = array(
			'label' => ucfirst($translate->_('dao.calendar_recurring_profile.patterns')),
			'type' => Model_CustomField::TYPE_MULTI_LINE,
			'value' => $calendar_recurring_profile->patterns,
		);
			
		// Custom Fields

		@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_CALENDAR_EVENT_RECURRING, $calendar_recurring_profile->id)) or array();
		$tpl->assign('custom_field_values', $values);
		
		$properties_cfields = Page_Profiles::getProfilePropertiesCustomFields(CerberusContexts::CONTEXT_CALENDAR_EVENT_RECURRING, $values);
		
		if(!empty($properties_cfields))
			$properties = array_merge($properties, $properties_cfields);
		
		// Custom Fieldsets

		$properties_custom_fieldsets = Page_Profiles::getProfilePropertiesCustomFieldsets(CerberusContexts::CONTEXT_CALENDAR_EVENT_RECURRING, $calendar_recurring_profile->id, $values);
		$tpl->assign('properties_custom_fieldsets', $properties_custom_fieldsets);
		
		// Properties
		
		$tpl->assign('properties', $properties);
			
		// Macros
		$macros = DAO_TriggerEvent::getByVirtualAttendantOwners(
			array(
				array(CerberusContexts::CONTEXT_APPLICATION, 0),
				array(CerberusContexts::CONTEXT_WORKER, $active_worker->id),
			),
			'event.macro.calendar_recurring_profile'
		);
		$tpl->assign('macros', $macros);

		// Tabs
		$tab_manifests = Extension_ContextProfileTab::getExtensions(false, CerberusContexts::CONTEXT_CALENDAR_EVENT_RECURRING);
		$tpl->assign('tab_manifests', $tab_manifests);
		
		// Template
		$tpl->display('devblocks:cerberusweb.core::internal/calendar_recurring_profile/profile.tpl');
	}
	
	function savePeekPopupJsonAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
		
		$default_tz = @$_SESSION['timezone'] ?: date_default_timezone_get();
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$calendar_id = DevblocksPlatform::importGPC($_REQUEST['calendar_id'], 'integer', 0);
		@$event_name = DevblocksPlatform::importGPC($_REQUEST['event_name'], 'string', '');
		@$event_start = DevblocksPlatform::importGPC($_REQUEST['event_start'], 'string', '');
		@$event_end = DevblocksPlatform::importGPC($_REQUEST['event_end'], 'string', '');
		@$tz = DevblocksPlatform::importGPC($_REQUEST['tz'], 'string', $default_tz);
		@$recur_start = intval(strtotime(DevblocksPlatform::importGPC($_REQUEST['recur_start'], 'string', '')));
		@$recur_end = intval(strtotime(DevblocksPlatform::importGPC($_REQUEST['recur_end'], 'string', '')));
		@$is_available = DevblocksPlatform::importGPC($_REQUEST['is_available'], 'integer', 0);
		@$patterns = DevblocksPlatform::importGPC($_REQUEST['patterns'], 'string', '');
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		header("Content-type: application/json");
		
		if(empty($calendar_id))
			return;
		
		if(!empty($id) && !empty($do_delete)) { // Delete
			DAO_CalendarRecurringProfile::delete($id);
			
			echo json_encode(array(
				'action' => 'delete',
			));
			
		} else {
			if(empty($id)) { // New
				$fields = array(
					DAO_CalendarRecurringProfile::CALENDAR_ID => $calendar_id,
					DAO_CalendarRecurringProfile::EVENT_NAME => $event_name,
					DAO_CalendarRecurringProfile::EVENT_START => $event_start ?: 'midnight',
					DAO_CalendarRecurringProfile::EVENT_END => $event_end ?: '',
					DAO_CalendarRecurringProfile::TZ => $tz,
					DAO_CalendarRecurringProfile::RECUR_START => $recur_start,
					DAO_CalendarRecurringProfile::RECUR_END => $recur_end,
					DAO_CalendarRecurringProfile::IS_AVAILABLE => $is_available ? 1 : 0,
					DAO_CalendarRecurringProfile::PATTERNS => $patterns,
				);
				$id = DAO_CalendarRecurringProfile::create($fields);
				
				// Context Link (if given)
				@$link_context = DevblocksPlatform::importGPC($_REQUEST['link_context'],'string','');
				@$link_context_id = DevblocksPlatform::importGPC($_REQUEST['link_context_id'],'integer','');
				if(!empty($id) && !empty($link_context) && !empty($link_context_id)) {
					DAO_ContextLink::setLink(CerberusContexts::CONTEXT_CALENDAR_EVENT_RECURRING, $id, $link_context, $link_context_id);
				}
				
				if(!empty($view_id) && !empty($id))
					C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_CALENDAR_EVENT_RECURRING, $id);
				
			} else { // Edit
				$fields = array(
					DAO_CalendarRecurringProfile::EVENT_NAME => $event_name,
					DAO_CalendarRecurringProfile::EVENT_START => $event_start ?: 'midnight',
					DAO_CalendarRecurringProfile::EVENT_END => $event_end ?: '',
					DAO_CalendarRecurringProfile::TZ => $tz,
					DAO_CalendarRecurringProfile::RECUR_START => $recur_start,
					DAO_CalendarRecurringProfile::RECUR_END => $recur_end,
					DAO_CalendarRecurringProfile::IS_AVAILABLE => $is_available ? 1 : 0,
					DAO_CalendarRecurringProfile::PATTERNS => $patterns,
				);
				DAO_CalendarRecurringProfile::update($id, $fields);
				
			}

			// Custom fields
			@$field_ids = DevblocksPlatform::importGPC($_REQUEST['field_ids'], 'array', array());
			DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_CALENDAR_EVENT_RECURRING, $id, $field_ids);
			
			echo json_encode(array(
				'action' => 'modify',
				'month' => intval(date('m', time())),
				'year' => intval(date('Y', time())),
			));
			return;
		}
	}
};
