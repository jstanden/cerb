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

class PageSection_ProfilesWorker extends Extension_PageSection {
	function render() {
		$request = DevblocksPlatform::getHttpRequest();
		$stack = $request->path;

		if(isset($stack[2])) {
			$this->_renderWorkerPage();
		}
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
		
		// Custom Fields

		@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_WORKER, $worker->id)) or array();
		$tpl->assign('custom_field_values', $values);
		
		$properties_cfields = Page_Profiles::getProfilePropertiesCustomFields(CerberusContexts::CONTEXT_WORKER, $values);
		
		if(!empty($properties_cfields))
			$properties = array_merge($properties, $properties_cfields);

		// Custom Fieldsets

		$properties_custom_fieldsets = Page_Profiles::getProfilePropertiesCustomFieldsets(CerberusContexts::CONTEXT_WORKER, $worker->id, $values);
		$tpl->assign('properties_custom_fieldsets', $properties_custom_fieldsets);
		
		// Properties
		
		$tpl->assign('properties', $properties);
		
		// Macros
		$macros = DAO_TriggerEvent::getByOwner(CerberusContexts::CONTEXT_WORKER, $active_worker->id, 'event.macro.worker');
		$tpl->assign('macros', $macros);

		// Tabs
		$tab_manifests = Extension_ContextProfileTab::getExtensions(false, CerberusContexts::CONTEXT_WORKER);
		$tpl->assign('tab_manifests', $tab_manifests);
		
		// Prefs
		$profile_worker_prefs = DAO_WorkerPref::getByWorker($worker->id);
		$tpl->assign('profile_worker_prefs', $profile_worker_prefs);
		
		// SSL
		$url_writer = DevblocksPlatform::getUrlService();
		$tpl->assign('is_ssl', $url_writer->isSSL());
		
		// Template
		$tpl->display('devblocks:cerberusweb.core::profiles/worker.tpl');
	}
	
	function setAvailabilityCalendarAction() {
		@$availability_calendar_id = DevblocksPlatform::importGPC($_REQUEST['availability_calendar_id'],'integer', 0);
		
		$active_worker = CerberusApplication::getActiveWorker();

		if(!empty($availability_calendar_id)) {
			if(false == ($calendar = DAO_Calendar::get($availability_calendar_id)))
				$availability_calendar_id = 0;
			
			if(!$calendar->isWriteableByWorker($active_worker))
				$availability_calendar_id = 0;
		}
		
		if(empty($availability_calendar_id)) {
			$fields = array(
				DAO_Calendar::NAME => $active_worker->getName() .  "'s Schedule",
				DAO_Calendar::OWNER_CONTEXT => CerberusContexts::CONTEXT_WORKER,
				DAO_Calendar::OWNER_CONTEXT_ID => $active_worker->id,
				DAO_Calendar::PARAMS_JSON => '{"manual_disabled":"0","sync_enabled":"0","color_available":"#A0D95B","color_busy":"#C8C8C8"}',
				DAO_Calendar::UPDATED_AT => time(),
			);
			$availability_calendar_id = DAO_Calendar::create($fields);
		}
		
		if(!empty($availability_calendar_id)) {
			DAO_WorkerPref::set($active_worker->id, 'availability_calendar_id', $availability_calendar_id);
		}
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('profiles','worker','me','availability')));
	}
};