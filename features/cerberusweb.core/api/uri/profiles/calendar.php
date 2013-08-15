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

class PageSection_ProfilesCalendar extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // calendar
		$id = array_shift($stack); // 123

		@$id = intval($id);
		
		if(null == ($calendar = DAO_Calendar::get($id))) { /* @var $calenar Model_Calendar */
			return;
		}
		$tpl->assign('calendar', $calendar);
	
		// Tab persistence
		
		$point = 'profiles.calendar.tab';
		$tpl->assign('point', $point);
		
		if(null == (@$tab_selected = $stack[0])) {
			$tab_selected = $visit->get($point, '');
		}
		$tpl->assign('tab_selected', $tab_selected);
	
		// Properties
			
		$properties = array();
			
		$properties['name'] = array(
			'label' => ucfirst($translate->_('common.name')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $calendar->name,
		);
		
		$context_ext = Extension_DevblocksContext::get($calendar->owner_context);
		$context_meta = $context_ext->getMeta($calendar->owner_context_id);
		
		$properties['owner'] = array(
			'label' => ucfirst($translate->_('common.owner')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => sprintf("%s (%s)", $context_meta['name'], $context_ext->manifest->name)
		);
			
		$properties['updated'] = array(
			'label' => ucfirst($translate->_('common.updated')),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $calendar->updated_at,
		);
			
	
		// Custom Fields

		@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_CALENDAR, $calendar->id)) or array();
		$tpl->assign('custom_field_values', $values);
		
		$properties_cfields = Page_Profiles::getProfilePropertiesCustomFields(CerberusContexts::CONTEXT_CALENDAR, $values);
		
		if(!empty($properties_cfields))
			$properties = array_merge($properties, $properties_cfields);
		
		// Custom Fieldsets

		$properties_custom_fieldsets = Page_Profiles::getProfilePropertiesCustomFieldsets(CerberusContexts::CONTEXT_CALENDAR, $calendar->id, $values);
		$tpl->assign('properties_custom_fieldsets', $properties_custom_fieldsets);
		
		// Properties
		
		$tpl->assign('properties', $properties);
			
		// Macros
		$macros = DAO_TriggerEvent::getByVirtualAttendantOwners(
			array(
				array(CerberusContexts::CONTEXT_APPLICATION, 0),
				array(CerberusContexts::CONTEXT_WORKER, $active_worker->id),
			),
			'event.macro.calendar'
		);
		$tpl->assign('macros', $macros);

		// Tabs
		$tab_manifests = Extension_ContextProfileTab::getExtensions(false, CerberusContexts::CONTEXT_CALENDAR);
		$tpl->assign('tab_manifests', $tab_manifests);
		
		// Template
		$tpl->display('devblocks:cerberusweb.core::profiles/calendar.tpl');
	}
};
