<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2015, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerberusweb.com/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerbweb.com	    http://www.webgroupmedia.com/
***********************************************************************/

class PageSection_ProfilesGroup extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
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
		
		// Properties
		
		$translate = DevblocksPlatform::getTranslationService();
		
		$properties = array();
		
		$reply_to = $group->getReplyTo();
		
		$properties['reply_to'] = array(
			'label' => ucfirst($translate->_('common.email')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $reply_to->email,
		);
		
		$properties['is_default'] = array(
			'label' => ucfirst($translate->_('common.default')),
			'type' => Model_CustomField::TYPE_CHECKBOX,
			'value' => $group->is_default,
		);
		
		$properties['is_private'] = array(
			'label' => ucfirst($translate->_('common.private')),
			'type' => Model_CustomField::TYPE_CHECKBOX,
			'value' => $group->is_private,
		);
				
		// Custom Fields

		@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_GROUP, $group->id)) or array();
		$tpl->assign('custom_field_values', $values);
		
		$properties_cfields = Page_Profiles::getProfilePropertiesCustomFields(CerberusContexts::CONTEXT_GROUP, $values);
		
		if(!empty($properties_cfields))
			$properties = array_merge($properties, $properties_cfields);
		
		// Custom Fieldsets

		$properties_custom_fieldsets = Page_Profiles::getProfilePropertiesCustomFieldsets(CerberusContexts::CONTEXT_GROUP, $group->id, $values);
		$tpl->assign('properties_custom_fieldsets', $properties_custom_fieldsets);
		
		// Link counts
		
		$properties_links = array(
			CerberusContexts::CONTEXT_GROUP => array(
				$group->id => 
					DAO_ContextLink::getContextLinkCounts(
						CerberusContexts::CONTEXT_GROUP,
						$group->id,
						array(CerberusContexts::CONTEXT_WORKER, CerberusContexts::CONTEXT_CUSTOM_FIELDSET)
					),
			),
		);
		
		$tpl->assign('properties_links', $properties_links);
		
		// Properties
		
		$tpl->assign('properties', $properties);
		
		// Macros
		
		$macros = DAO_TriggerEvent::getReadableByActor(
			$active_worker,
			'event.macro.group'
		);

		// Filter macros to only those owned by the current group
		
		$macros = array_filter($macros, function($macro) use ($group) { /* @var $macro Model_TriggerEvent */
			$va = $macro->getVirtualAttendant(); /* @var $va Model_VirtualAttendant */
			
			if($va->owner_context == CerberusContexts::CONTEXT_GROUP && $va->owner_context_id != $group->id)
				return false;
			
			return true;
		});
		
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
	
	function savePeekAction() {
		@$group_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');

		@$name = DevblocksPlatform::importGPC($_REQUEST['name'],'string','');
		@$is_private = DevblocksPlatform::importGPC($_REQUEST['is_private'],'integer',0);

		$fields = array(
			DAO_Group::NAME => $name,
			DAO_Group::IS_PRIVATE => $is_private,
		);
		
		if(empty($group_id)) { // new
			$group_id = DAO_Group::create($fields);
			
			// View marquee
			if(!empty($group_id) && !empty($view_id)) {
				C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_GROUP, $group_id);
			}
			
		} else { // update
			DAO_Group::update($group_id, $fields);
		}
		
		// Members
		
		@$member_ids = DevblocksPlatform::sanitizeArray(DevblocksPlatform::importGPC($_REQUEST['member_ids'], 'array', array()), 'int');
		@$member_levels = DevblocksPlatform::sanitizeArray(DevblocksPlatform::importGPC($_REQUEST['member_levels'], 'array', array()), 'int');
		
		DAO_Group::clearGroupMembers($group_id);
		
		foreach($member_ids as $idx => $member_id) {
			if(!isset($member_levels[$idx]))
				continue;
			
			$is_member = 0 != $member_levels[$idx];
			$is_manager = 2 == $member_levels[$idx];
			
			if(!$is_member)
				continue;
			
			DAO_Group::setGroupMember($group_id, $member_id, $is_manager);
		}

		// Settings
		
		@$subject_has_mask = DevblocksPlatform::importGPC($_REQUEST['subject_has_mask'],'integer',0);
		@$subject_prefix = DevblocksPlatform::importGPC($_REQUEST['subject_prefix'],'string','');

		DAO_GroupSettings::set($group_id, DAO_GroupSettings::SETTING_SUBJECT_HAS_MASK, $subject_has_mask);
		DAO_GroupSettings::set($group_id, DAO_GroupSettings::SETTING_SUBJECT_PREFIX, $subject_prefix);
		
		// Custom field saves
		
		@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', array());
		DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_GROUP, $group_id, $field_ids);
		
		exit;
	}
	
	function showMembersTabAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		
		if(!$id || false == ($group = DAO_Group::get($id)))
			return;
		
		$tpl = DevblocksPlatform::getTemplateService();

		$defaults = new C4_AbstractViewModel();
		$defaults->id = 'group_members';
		$defaults->name = 'Members';
		$defaults->class_name = 'View_Worker';
		$defaults->renderSubtotals = '';
		
		$view = C4_AbstractViewLoader::getView($defaults->id, $defaults);
		
		$view->addParamsRequired(array(
			new DevblocksSearchCriteria(SearchFields_Worker::VIRTUAL_GROUPS, DevblocksSearchCriteria::OPER_IN, array($group->id)),
		), true);
		
		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:cerberusweb.core::internal/views/search_and_view.tpl');
	}
	
	function showBucketsTabAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		
		if(!$id || false == ($group = DAO_Group::get($id)))
			return;
		
		$tpl = DevblocksPlatform::getTemplateService();

		$defaults = new C4_AbstractViewModel();
		$defaults->id = 'group_buckets';
		$defaults->name = 'Buckets';
		$defaults->class_name = 'View_Bucket';
		$defaults->view_columns = array(
			SearchFields_Bucket::NAME,
			SearchFields_Bucket::IS_DEFAULT,
			SearchFields_Bucket::UPDATED_AT,
		);
		$defaults->renderSortBy = SearchFields_Bucket::NAME;
		$defaults->renderSortAsc = true;
		$defaults->renderSubtotals = '';

		$view = C4_AbstractViewLoader::getView($defaults->id, $defaults);
		
		$view->addParamsRequired(array(
			new DevblocksSearchCriteria(SearchFields_Bucket::GROUP_ID, '=', $group->id),
		), true);
		
		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:cerberusweb.core::internal/views/search_and_view.tpl');
	}
	

	function viewExploreAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$url_writer = DevblocksPlatform::getUrlService();
		
		// Generate hash
		$hash = md5($view_id.$active_worker->id.time());
		
		// Loop through view and get IDs
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);

		// Page start
		@$explore_from = DevblocksPlatform::importGPC($_REQUEST['explore_from'],'integer',0);
		if(empty($explore_from)) {
			$orig_pos = 1+($view->renderPage * $view->renderLimit);
		} else {
			$orig_pos = 1;
		}
		
		$view->renderPage = 0;
		$view->renderLimit = 250;
		$pos = 0;
		
		do {
			$models = array();
			list($results, $total) = $view->getData();

			// Summary row
			if(0==$view->renderPage) {
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'title' => $view->name,
					'created' => time(),
					//'worker_id' => $active_worker->id,
					'total' => $total,
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&type=group', true),
//					'toolbar_extension_id' => 'cerberusweb.explorer.toolbar.',
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $group_id => $row) {
				if($group_id==$explore_from)
					$orig_pos = $pos;
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_Group::ID],
					'url' => $url_writer->writeNoProxy(sprintf("c=profiles&type=group&id=%d", $row[SearchFields_Group::ID]), true),
				);
				$models[] = $model;
			}
			
			DAO_ExplorerSet::createFromModels($models);
			
			$view->renderPage++;
			
		} while(!empty($results));
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('explore',$hash,$orig_pos)));
	}
};