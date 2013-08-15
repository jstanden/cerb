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
		
		// Properties
		
		$translate = DevblocksPlatform::getTranslationService();
		
		$properties = array();
				
// 				$properties['email'] = array(
// 					'label' => ucfirst($translate->_('common.email')),
// 					'type' => Model_CustomField::TYPE_SINGLE_LINE,
// 					'value' => $worker->email,
// 				);
				

		// Custom Fields

		@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_GROUP, $group->id)) or array();
		$tpl->assign('custom_field_values', $values);
		
		$properties_cfields = Page_Profiles::getProfilePropertiesCustomFields(CerberusContexts::CONTEXT_GROUP, $values);
		
		if(!empty($properties_cfields))
			$properties = array_merge($properties, $properties_cfields);
		
		// Custom Fieldsets

		$properties_custom_fieldsets = Page_Profiles::getProfilePropertiesCustomFieldsets(CerberusContexts::CONTEXT_GROUP, $group->id, $values);
		$tpl->assign('properties_custom_fieldsets', $properties_custom_fieldsets);
		
		// Properties
		
		$tpl->assign('properties', $properties);
		
		// Macros
		$macros = DAO_TriggerEvent::getByVirtualAttendantOwners(
			array(
				array(CerberusContexts::CONTEXT_APPLICATION, 0),
				array(CerberusContexts::CONTEXT_WORKER, $active_worker->id, null),
				array(CerberusContexts::CONTEXT_GROUP, $group->id, $group->name),
			),
			'event.macro.group'
		);
		$tpl->assign('macros', $macros);

		// Tabs
		$tab_manifests = Extension_ContextProfileTab::getExtensions(false, CerberusContexts::CONTEXT_GROUP);
		$tpl->assign('tab_manifests', $tab_manifests);
		
		// SSL
		$url_writer = DevblocksPlatform::getUrlService();
		$tpl->assign('is_ssl', $url_writer->isSSL());
		
		// Template
		$tpl->display('devblocks:cerberusweb.core::profiles/group.tpl');
	}
};