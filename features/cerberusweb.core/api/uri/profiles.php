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

class Page_Profiles extends CerberusPageExtension {
	const ID = 'cerberusweb.page.profiles';
	
	function isVisible() {
		// The current session must be a logged-in worker to use this page.
		if(null == ($worker = CerberusApplication::getActiveWorker()))
			return false;
		
		return true;
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$active_worker = CerberusApplication::getActiveWorker();
		$visit = CerberusApplication::getVisit();
		$response = DevblocksPlatform::getHttpResponse();
		
		$stack = $response->path;
		@array_shift($stack); // profiles
		@$section_uri = array_shift($stack);

		if(empty($section_uri))
			$section_uri = 'worker';

		// Subpage
		$subpage = Extension_PageSection::getExtensionByPageUri($this->manifest->id, $section_uri, true);
		$tpl->assign('subpage', $subpage);
		
		$tpl->display('devblocks:cerberusweb.core::profiles/index.tpl');
	}
	
	function handleSectionActionAction() {
		@$section_uri = DevblocksPlatform::importGPC($_REQUEST['section'],'string','');
		@$action = DevblocksPlatform::importGPC($_REQUEST['action'],'string','');

		$inst = Extension_PageSection::getExtensionByPageUri($this->manifest->id, $section_uri, true);
		
		if($inst instanceof Extension_PageSection && method_exists($inst, $action.'Action')) {
			call_user_func(array($inst, $action.'Action'));
		}
	}
	
	function showTabAction() {
		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'],'string','');
		@$point = DevblocksPlatform::importGPC($_REQUEST['point'],'string','');
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer',0);
		
		$visit = CerberusApplication::getVisit();
		
		if(null != ($tab_mft = DevblocksPlatform::getExtension($ext_id))
				&& null != ($inst = $tab_mft->createInstance())
				&& $inst instanceof Extension_ContextProfileTab) {
			
			if(!empty($point))
				$visit->set($point, $inst->manifest->params['uri']);
			
			$inst->showTab($context, $context_id);
		}
	}
	
	static function getProfilePropertiesCustomFields($context, $values) {
		$custom_fields = DAO_CustomField::getByContext($context);
		$properties = array();
		
		foreach($custom_fields as $cf_id => $cfield) {
			if($cfield->custom_fieldset_id != 0)
				continue;
			
			if(!isset($values[$cf_id]))
				continue;
		
			$properties['cf_' . $cf_id] = array(
				'id' => $cf_id,
				'label' => $cfield->name,
				'type' => $cfield->type,
				'value' => $values[$cf_id],
			);
		}
		
		return $properties;
	}
	
	static function getProfilePropertiesCustomFieldsets($context, $context_id, $values) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		$custom_fields = DAO_CustomField::getByContext($context);
		$custom_fieldsets = DAO_CustomFieldset::getByContextLink($context, $context_id);
		
		$properties = array();
		
		if(is_array($custom_fieldsets))
		foreach($custom_fieldsets as $custom_fieldset) { /* @var $custom_fieldset Model_CustomFieldset */
			if(!$custom_fieldset->isReadableByWorker($active_worker))
				continue;
		
			$cf_group_fields = $custom_fieldset->getCustomFields();
			$cf_group_props = array();
			
			if(is_array($cf_group_fields))
			foreach($cf_group_fields as $cf_group_field_id => $cf_group_field) {
				if(!isset($custom_fields[$cf_group_field_id]))
					continue;
			
				$cf_group_props['cf_' . $cf_group_field_id] = array(
					'id' => $cf_group_field_id,
					'label' => $cf_group_field->name,
					'type' => $cf_group_field->type,
					'value' => isset($values[$cf_group_field->id]) ? $values[$cf_group_field->id] : null,
				);
			}
			
			$properties[$custom_fieldset->id] = array(
				'model' => $custom_fieldset,
				'properties' => $cf_group_props,
			);
			
		}
		
		return $properties;
	}
};
