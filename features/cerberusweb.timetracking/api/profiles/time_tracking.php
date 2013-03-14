<?php
/***********************************************************************
| Cerb(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2013, WebGroup Media LLC
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

class PageSection_ProfilesTimeTracking extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		$request = DevblocksPlatform::getHttpRequest();
		$translate = DevblocksPlatform::getTranslationService();
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		$stack = $request->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // time_tracking
		@$identifier = array_shift($stack);
		
		if(is_numeric($identifier)) {
			$id = intval($identifier);
		} elseif(preg_match("#.*?\-(\d+)$#", $identifier, $matches)) {
			@$id = intval($matches[1]);
		} else {
			@$id = intval($identifier);
		}
		
		if(null != ($time_entry = DAO_TimeTrackingEntry::get($id))) {
			$tpl->assign('time_entry', $time_entry);
		}
		
		// Remember the last tab/URL
		
		$point = 'profiles.page.time_tracking';
		$tpl->assign('point', $point);

		@$selected_tab = array_shift($stack);
		
		if(null == $selected_tab) {
			$selected_tab = $visit->get($point, '');
		}
		$tpl->assign('selected_tab', $selected_tab);
		
		// Custom fields
		
		$custom_fields = DAO_CustomField::getAll();
		$tpl->assign('custom_fields', $custom_fields);
		
		// Properties
		
		$properties = array();
		
		$properties['status'] = array(
			'label' => ucfirst($translate->_('common.status')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => ($time_entry->is_closed) ? $translate->_('status.closed') : $translate->_('status.open'),
		);
		
		$properties['log_date'] = array(
			'label' => ucfirst($translate->_('timetracking_entry.log_date')),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $time_entry->log_date,
		);
		
		$properties['time_spent'] = array(
			'label' => 'Time spent',
			'type' => null,
			'value' => $time_entry->time_actual_mins * 60,
		);
		
		// [TODO] Worker?
		
		@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_TIMETRACKING, $time_entry->id)) or array();
		
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
		$macros = DAO_TriggerEvent::getByOwner(CerberusContexts::CONTEXT_WORKER, $active_worker->id, 'event.macro.timetracking');
		$tpl->assign('macros', $macros);
		
		// Tabs
		$tab_manifests = Extension_ContextProfileTab::getExtensions(false, CerberusContexts::CONTEXT_TIMETRACKING);
		$tpl->assign('tab_manifests', $tab_manifests);
		
		// Template
		$tpl->display('devblocks:cerberusweb.timetracking::timetracking/profile.tpl');
	}
};