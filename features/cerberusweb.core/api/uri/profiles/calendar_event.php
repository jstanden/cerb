<?php
/***********************************************************************
| Cerb(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2012, WebGroup Media LLC
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

class PageSection_ProfilesCalendarEvent extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		$request = DevblocksPlatform::getHttpRequest();
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		$stack = $request->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // calendar_event
		@$identifier = array_shift($stack);
		
		if(is_numeric($identifier)) {
			$id = intval($identifier);
		} elseif(preg_match("#.*?\-(\d+)$#", $identifier, $matches)) {
			@$id = intval($matches[1]);
		} else {
			@$id = intval($identifier);
		}
		
		if(null == ($event = DAO_CalendarEvent::get($id)))
			return;
		
		$tpl->assign('event', $event);

		// Remember the last tab/URL
		
		$point = sprintf("cerberusweb.profiles.calendar_event.%d", $event->id);
		$tpl->assign('point', $point);

		@$selected_tab = array_shift($stack);
		
		if(null == $selected_tab) {
			$selected_tab = $visit->get($point, '');
		}
		$tpl->assign('selected_tab', $selected_tab);
		
		// Properties
		
		$translate = DevblocksPlatform::getTranslationService();

		$properties = array();

		$properties['date_start'] = array(
			'label' => ucfirst($translate->_('dao.calendar_event.date_start')),
			'type' => null,
			'value' => $event->date_start,
		);
		
		$properties['date_end'] = array(
			'label' => ucfirst($translate->_('dao.calendar_event.date_end')),
			'type' => null,
			'value' => $event->date_end,
		);
		
		$properties['is_recurring'] = array(
			//'label' => ucfirst($translate->_('dao.calendar_event.is_available')),
			'label' => 'Is Repeating',
			'type' => Model_CustomField::TYPE_CHECKBOX,
			'value' => ($event->recurring_id != 0) ? 1 : 0,
		);
				
		$properties['is_available'] = array(
			'label' => ucfirst($translate->_('dao.calendar_event.is_available')),
			'type' => Model_CustomField::TYPE_CHECKBOX,
			'value' => $event->is_available,
		);
		
		@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_CALENDAR_EVENT, $event->id)) or array();

		foreach($custom_fields as $cf_id => $cfield) {
			if(!isset($values[$cf_id]))
				continue;
				
			$properties['cf_' . $cf_id] = array(
				'label' => $cfield->name,
				'type' => $cfield->type,
				'value' => $values[$cf_id],
			);
		}
		
		$tpl->assign('properties', $properties);				
		
		// Macros
		$macros = DAO_TriggerEvent::getByOwner(CerberusContexts::CONTEXT_WORKER, $active_worker->id, 'event.macro.calendar_event');
		$tpl->assign('macros', $macros);

		// Tabs
		$tab_manifests = Extension_ContextProfileTab::getExtensions(false, CerberusContexts::CONTEXT_CALENDAR_EVENT);
		$tpl->assign('tab_manifests', $tab_manifests);
		
		// Template
		$tpl->display('devblocks:cerberusweb.core::profiles/calendar_event.tpl');
	}
};