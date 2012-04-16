<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
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

class PageSection_ProfilesGroup extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		$request = DevblocksPlatform::getHttpRequest();
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		$stack = $request->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // group
		
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		$workers = DAO_Worker::getAllActive();
		$tpl->assign('workers', $workers);
		
		@$group_id = intval(array_shift($stack));
		$point = 'cerberusweb.profiles.group.' . $group_id;

		if(empty($group_id) || null == ($group = DAO_Group::get($group_id)))
			throw new Exception();
		
		$tpl->assign('group', $group);
		
		// Remember the last tab/URL
		if(null == ($selected_tab = @$request->path[3])) {
			$selected_tab = $visit->get($point, '');
		}
		$tpl->assign('selected_tab', $selected_tab);
		
		// Custom fields
		
		$custom_fields = DAO_CustomField::getAll();
		$tpl->assign('custom_fields', $custom_fields);
		
		// Properties
		
		$translate = DevblocksPlatform::getTranslationService();
		
		$properties = array();
				
// 				$properties['email'] = array(
// 					'label' => ucfirst($translate->_('common.email')),
// 					'type' => Model_CustomField::TYPE_SINGLE_LINE,
// 					'value' => $worker->email,
// 				);
				
		@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_GROUP, $group->id)) or array();

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
		$macros = DAO_TriggerEvent::getByOwners(
			array(
				array(CerberusContexts::CONTEXT_WORKER, $active_worker->id, null),
				array(CerberusContexts::CONTEXT_GROUP, $group->id, $group->name),
			),
			'event.macro.group'
		);
		$tpl->assign('macros', $macros);
		
		// Template
				
		$tpl->display('devblocks:cerberusweb.core::profiles/group.tpl');
	}
};