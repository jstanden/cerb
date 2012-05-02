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

class PageSection_ProfilesTask extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$translate = DevblocksPlatform::getTranslationService();
		$visit = CerberusApplication::getVisit();
		$response = DevblocksPlatform::getHttpResponse();
		$active_worker = CerberusApplication::getActiveWorker();

		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // task
		@$id = intval(array_shift($stack));
		
		if(null != ($task = DAO_Task::get($id))) {
			$tpl->assign('task', $task);
		}
		
		// Remember the last tab/URL
		@$selected_tab = array_shift($stack);
		
		$point = 'core.page.tasks';
		$tpl->assign('point', $point);
		
		if(null == $selected_tab) {
			$selected_tab = $visit->get($point, '');
		}
		$tpl->assign('selected_tab', $selected_tab);
		
		// Custom fields
		
		$custom_fields = DAO_CustomField::getAll();
		$tpl->assign('custom_fields', $custom_fields);
		
		// Properties
		
		$properties = array();
		
		$properties['is_completed'] = array(
			'label' => ucfirst($translate->_('task.is_completed')),
			'type' => Model_CustomField::TYPE_CHECKBOX,
			'value' => $task->is_completed,
		);
		
		if(!$task->is_completed) {
			$properties['due_date'] = array(
				'label' => ucfirst($translate->_('task.due_date')),
				'type' => Model_CustomField::TYPE_DATE,
				'value' => $task->due_date,
			);
		} else {
			$properties['completed_date'] = array(
				'label' => ucfirst($translate->_('task.completed_date')),
				'type' => Model_CustomField::TYPE_DATE,
				'value' => $task->completed_date,
			);
		}
		
		$properties['updated_date'] = array(
			'label' => ucfirst($translate->_('common.updated')),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $task->updated_date,
		);
		
		@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_TASK, $task->id)) or array();
		
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
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		// Macros
		$macros = DAO_TriggerEvent::getByOwner(CerberusContexts::CONTEXT_WORKER, $active_worker->id, 'event.macro.task');
		$tpl->assign('macros', $macros);
		
		$tpl->display('devblocks:cerberusweb.core::profiles/task.tpl');		
	}
};