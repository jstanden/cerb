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

class PageSection_ProfilesWorker extends Extension_PageSection {
	function render() {
		$request = DevblocksPlatform::getHttpRequest();
		$stack = $request->path;

		if(isset($stack[2])) {
			$this->_renderWorkerPage();
		} else {
			$this->_renderIndexPage();
		}
	}
	
	private function _renderIndexPage() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		$request = DevblocksPlatform::getHttpRequest();

		$stack = $request->path;
		
		@array_shift($stack); // profiles
		@array_shift($stack); // worker
		
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		$workers = DAO_Worker::getAllActive();
		$tpl->assign('workers', $workers);
		
		$tpl->display('devblocks:cerberusweb.core::profiles/workers.tpl');
	}
	
	private function _renderWorkerPage() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		$request = DevblocksPlatform::getHttpRequest();

		$active_worker = CerberusApplication::getActiveWorker();		
		
		$stack = $request->path;
		
		@array_shift($stack); // profiles
		@array_shift($stack); // worker
		
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		$workers = DAO_Worker::getAllActive();
		$tpl->assign('workers', $workers);
		
		@$id = array_shift($stack);

		switch($id) {
			case 'me':
				$worker_id = $active_worker->id;
				break;
				
			default:
				@$worker_id = intval($id);
				break;
		}

		$point = 'cerberusweb.profiles.worker.' . $worker_id;

		if(empty($worker_id) || null == ($worker = DAO_Worker::get($worker_id)))
			return;
			
		$tpl->assign('worker', $worker);
		
		// Remember the last tab/URL
		if(null == ($selected_tab = @$request->path[3])) {
			$selected_tab = $visit->get($point, '');
		}
		$tpl->assign('selected_tab', $selected_tab);
		
		// Counts
		$counts = DAO_ContextLink::getContextLinkCounts(CerberusContexts::CONTEXT_WORKER, $worker_id);
		$watching_total = intval(array_sum($counts));
		$tpl->assign('watching_total', $watching_total);
		
		// Custom fields
		
		$custom_fields = DAO_CustomField::getAll();
		$tpl->assign('custom_fields', $custom_fields);
		
		// Properties
		
		$translate = DevblocksPlatform::getTranslationService();
		
		$properties = array();
		
		$properties['email'] = array(
			'label' => ucfirst($translate->_('common.email')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $worker->email,
		);
		
		$properties['is_superuser'] = array(
			'label' => ucfirst($translate->_('worker.is_superuser')),
			'type' => Model_CustomField::TYPE_CHECKBOX,
			'value' => $worker->is_superuser,
		);
		
		@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_WORKER, $worker->id)) or array();

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

		$macros = DAO_TriggerEvent::getByOwner(CerberusContexts::CONTEXT_WORKER, $active_worker->id, 'event.macro.worker');
		$tpl->assign('macros', $macros);

		// Template
		
		$tpl->display('devblocks:cerberusweb.core::profiles/worker.tpl');		
	}
};