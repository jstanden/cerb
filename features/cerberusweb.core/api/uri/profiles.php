<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2018, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.ai/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.ai	    http://webgroup.media
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
		$tpl = DevblocksPlatform::services()->template();
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
	
	static function renderProfile($context, $context_id) {
		$tpl = DevblocksPlatform::services()->template();
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();

		// Remember the last tab/URL
		$point = sprintf("profile.%s", $context);
		$tpl->assign('point', $point);
		
		// Context

		if(false == ($context_ext = Extension_DevblocksContext::get($context, true)))
			return;
		
		$tpl->assign('context_ext', $context_ext);
		
		// Model
		
		$dao_class = $context_ext->getDaoClass();
		
		if(false == ($record = $dao_class::get($context_id)))
			return;
		
		$tpl->assign('record', $record);
		
		// Dictionary
		
		$labels = $values = [];
		CerberusContexts::getContext($context, $record, $labels, $values, '', true, false);
		$dict = DevblocksDictionaryDelegate::instance($values);
		$tpl->assign('dict', $dict);
		
		// Interactions
		
		$interactions = Event_GetInteractionsForWorker::getInteractionsByPointAndWorker('record:' . $context, $dict, $active_worker);
		$interactions_menu = Event_GetInteractionsForWorker::getInteractionMenu($interactions);
		$tpl->assign('interactions_menu', $interactions_menu);

		// Template
		
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/profile.tpl');
	}
	
	function handleSectionActionAction() {
		// GET has precedence over POST
		@$section_uri = DevblocksPlatform::importGPC(isset($_GET['section']) ? $_GET['section'] : $_REQUEST['section'],'string','');
		@$action = DevblocksPlatform::importGPC(isset($_GET['action']) ? $_GET['action'] : $_REQUEST['action'],'string','');

		$inst = Extension_PageSection::getExtensionByPageUri($this->manifest->id, $section_uri, true);
		
		if($inst instanceof Extension_PageSection && method_exists($inst, $action.'Action')) {
			call_user_func(array($inst, $action.'Action'));
		}
	}
	
	function configTabsAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl = DevblocksPlatform::services()->template();
		
		if(!$active_worker->is_superuser)
			return;
		
		$tpl->assign('context', $context);
		
		$profile_tabs_available = DAO_ProfileTab::getByContext($context);
		$tpl->assign('profile_tabs', $profile_tabs_available);
		
		$profile_tabs_enabled = DevblocksPlatform::getPluginSetting('cerberusweb.core', 'profile:tabs:' . $context, [], true);
		$tpl->assign('profile_tabs_enabled', $profile_tabs_enabled);
		
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/config_tabs.tpl');
	}
	
	function configTabsSaveJsonAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$profile_tabs = DevblocksPlatform::importGPC($_REQUEST['profile_tabs'],'array',[]);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker->is_superuser)
			return json_encode(false);
		
		DevblocksPlatform::setPluginSetting('cerberusweb.core', 'profile:tabs:' . $context, $profile_tabs, true);
		
		return json_encode(true);
	}
	
	function showProfileTabAction() {
		@$tab_id = DevblocksPlatform::importGPC($_REQUEST['tab_id'],'string','');
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer',0);
		
		if(false == ($profile_tab = DAO_ProfileTab::get($tab_id)))
			return;
		
		if(false == ($extension = $profile_tab->getExtension()))
			return;
		
		$extension->showTab($profile_tab, $context, $context_id);
	}
	
	function handleProfileTabActionAction() {
		@$tab_id = DevblocksPlatform::importGPC($_REQUEST['tab_id'],'integer',0);
		@$action = DevblocksPlatform::importGPC(isset($_GET['action']) ? $_GET['action'] : $_REQUEST['action'],'string','');
		
		if(false == ($profile_tab = DAO_ProfileTab::get($tab_id)))
			return;
		
		if(false == ($extension = $profile_tab->getExtension()))
			return;
		
		if($extension instanceof Extension_ProfileTab && method_exists($extension, $action.'Action')) {
			call_user_func([$extension, $action.'Action']);
		}
	}
	
	static function getProfilePropertiesCustomFields($context, $values) {
		$custom_fields = DAO_CustomField::getByContext($context);
		$properties = [];
		
		foreach($custom_fields as $cf_id => $cfield) {
			if($cfield->custom_fieldset_id != 0)
				continue;
			
			if(!isset($values[$cf_id]))
				continue;
		
			$properties['cf_' . $cf_id] = [
				'id' => $cf_id,
				'label' => $cfield->name,
				'type' => $cfield->type,
				'value' => $values[$cf_id],
				'params' => @$cfield->params ?: [],
			];
		}
		
		return $properties;
	}
	
	static function getProfilePropertiesCustomFieldsets($context, $context_id, $values) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		$custom_fields = DAO_CustomField::getByContext($context);
		$custom_fieldsets = DAO_CustomFieldset::getByContextLink($context, $context_id);
		
		$properties = [];
		
		if(is_array($custom_fieldsets))
		foreach($custom_fieldsets as $custom_fieldset) { /* @var $custom_fieldset Model_CustomFieldset */
			if(!Context_CustomFieldset::isReadableByActor($custom_fieldset, $active_worker))
				continue;
		
			$cf_group_fields = $custom_fieldset->getCustomFields();
			$cf_group_props = [];
			
			if(is_array($cf_group_fields))
			foreach($cf_group_fields as $cf_group_field_id => $cf_group_field) {
				if(!isset($custom_fields[$cf_group_field_id]))
					continue;
			
				$cf_group_props['cf_' . $cf_group_field_id] = [
					'id' => $cf_group_field_id,
					'label' => $cf_group_field->name,
					'type' => $cf_group_field->type,
					'value' => isset($values[$cf_group_field->id]) ? $values[$cf_group_field->id] : null,
				];
				
				// Include parameters for abstract handling
				if(!empty($cf_group_field->params))
					$cf_group_props['cf_' . $cf_group_field_id]['params'] = $cf_group_field->params;
			}
			
			$properties[$custom_fieldset->id] = [
				'model' => $custom_fieldset,
				'properties' => $cf_group_props,
			];
			
		}
		
		return $properties;
	}
	
	static function getTimelineJson($models, $is_ascending=true, $start_index=null) {
		$json = array(
			'objects' => array(),
			'length' => count($models),
			'last' => 0,
			'index' => 0,
			'context' => '',
			'context_id' => 0,
		);
		
		foreach($models as $idx => $model) {
			if($model instanceof Model_Comment) {
				$context = CerberusContexts::CONTEXT_COMMENT;
				$context_id = $model->id;
				$object = array('context' => $context, 'context_id' => $model->id);
				$json['objects'][] = $object;
			} elseif($model instanceof Model_Message) {
				$context = CerberusContexts::CONTEXT_MESSAGE;
				$context_id = $model->id;
				$object = array('context' => $context, 'context_id' => $model->id);
				$json['objects'][] = $object;
			}
		}
		
		if(isset($json['objects']) && is_array($json['objects'])) {
			// Move to the end
			end($json['objects']);
			
			if(is_null($start_index) || !isset($json['objects'][$start_index])) {
				$start_index = key($json['objects']);
			}
			
			if(!is_null($start_index) && false != ($object = $json['objects'][$start_index])) {
				$json['last'] = key($json['objects']);
				$json['index'] = $start_index;
				$json['context'] = $object['context'];
				$json['context_id'] = $object['context_id'];
			}
		}
		
		return json_encode($json);
	}
};
