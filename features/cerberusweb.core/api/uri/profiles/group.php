<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2017, Webgroup Media LLC
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

class PageSection_ProfilesGroup extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::services()->template();
		$request = DevblocksPlatform::getHttpRequest();
		
		$context = CerberusContexts::CONTEXT_GROUP;
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
			return;
		
		$tpl->assign('group', $group);
		
		// Dictionary
		$labels = array();
		$values = array();
		CerberusContexts::getContext($context, $group, $labels, $values, '', true, false);
		$dict = DevblocksDictionaryDelegate::instance($values);
		$tpl->assign('dict', $dict);
		
		// Properties
		
		$translate = DevblocksPlatform::getTranslationService();
		
		$properties = array();
		
		$reply_to = $group->getReplyTo();
		
		$properties['reply_to'] = array(
			'label' => mb_ucfirst($translate->_('common.email')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $reply_to->email,
		);
		
		$properties['is_default'] = array(
			'label' => mb_ucfirst($translate->_('common.default')),
			'type' => Model_CustomField::TYPE_CHECKBOX,
			'value' => $group->is_default,
		);
		
		$properties['is_private'] = array(
			'label' => mb_ucfirst($translate->_('common.private')),
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
						array(CerberusContexts::CONTEXT_CUSTOM_FIELDSET)
					),
			),
		);
		
		$tpl->assign('properties_links', $properties_links);
		
		// Properties
		
		$tpl->assign('properties', $properties);
		
		// Tabs
		$tab_manifests = Extension_ContextProfileTab::getExtensions(false, CerberusContexts::CONTEXT_GROUP);
		$tpl->assign('tab_manifests', $tab_manifests);
		
		// Interactions
		$interactions = Event_GetInteractionsForWorker::getInteractionsByPointAndWorker('record:' . $context, $dict, $active_worker);
		$interactions_menu = Event_GetInteractionsForWorker::getInteractionMenu($interactions);
		$tpl->assign('interactions_menu', $interactions_menu);
		
		// Template
		$tpl->display('devblocks:cerberusweb.core::profiles/group.tpl');
	}
	
	function savePeekJsonAction() {
		@$group_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'],'integer',0);

		$active_worker = CerberusApplication::getActiveWorker();
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(!($active_worker->is_superuser || $active_worker->isGroupManager($group_id)))
				throw new Exception_DevblocksAjaxValidationError("You do not have access to modify this group.");
		
			if($do_delete) {
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_GROUP)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				@$move_deleted_buckets = DevblocksPlatform::importGPC($_REQUEST['move_deleted_buckets'],'array',array());
				$buckets = DAO_Bucket::getAll();
				
				if(false == ($deleted_group = DAO_Group::get($group_id)))
					throw new Exception_DevblocksAjaxValidationError("The group you are attempting to delete doesn't exist.");
				
				// Handle preferred bucket relocation
				
				if(is_array($move_deleted_buckets))
				foreach($move_deleted_buckets as $from_bucket_id => $to_bucket_id) {
					if(!isset($buckets[$from_bucket_id]) || !isset($buckets[$to_bucket_id]))
						continue;
					
					DAO_Ticket::updateWhere(array(DAO_Ticket::GROUP_ID => $buckets[$to_bucket_id]->group_id, DAO_Ticket::BUCKET_ID => $to_bucket_id), sprintf("%s = %d", DAO_Ticket::BUCKET_ID, $from_bucket_id));
				}
				
				DAO_Group::delete($deleted_group->id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $group_id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				@$name = DevblocksPlatform::importGPC($_REQUEST['name'],'string','');
				@$is_private = DevblocksPlatform::importGPC($_REQUEST['is_private'],'bit',0);
			
				$fields = array(
					DAO_Group::NAME => $name,
					DAO_Group::IS_PRIVATE => $is_private,
				);
				
				if(empty($group_id)) { // new
					if(!$active_worker->hasPriv(sprintf("contexts.%s.create", CerberusContexts::CONTEXT_GROUP)))
						throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.create'));
					
					if(!DAO_Group::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					$group_id = DAO_Group::create($fields);
					
					// View marquee
					if(!empty($group_id) && !empty($view_id)) {
						C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_GROUP, $group_id);
					}
					
				} else { // update
					if(!$active_worker->hasPriv(sprintf("contexts.%s.update", CerberusContexts::CONTEXT_GROUP)))
						throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.edit'));
					
					if(!DAO_Group::validate($fields, $error, $group_id))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					DAO_Group::update($group_id, $fields);
				}
				
				// Members
				
				@$member_ids = DevblocksPlatform::sanitizeArray(DevblocksPlatform::importGPC($_REQUEST['member_ids'], 'array', array()), 'int');
				@$member_levels = DevblocksPlatform::sanitizeArray(DevblocksPlatform::importGPC($_REQUEST['member_levels'], 'array', array()), 'int');
	
				// Load the current group members
				$group_members = DAO_Group::getGroupMembers($group_id);
				
				if(is_array($member_ids))
				foreach($member_ids as $idx => $member_id) {
					if(!isset($member_levels[$idx]))
						continue;
					
					$is_member = 0 != $member_levels[$idx];
					$is_manager = 2 == $member_levels[$idx];
					
					// If this worker shouldn't be a member
					if(!$is_member) {
						// If they were previously a member, remove them
						if(isset($group_members[$member_id])) {
							DAO_Group::unsetGroupMember($group_id, $member_id);
						}
						
					// If this worker should be a member/manager
					} else {
						DAO_Group::setGroupMember($group_id, $member_id, $is_manager);
						
						// If the worker wasn't previously a member/manager
						if(!isset($group_members[$member_id])) {
							DAO_Group::setMemberDefaultResponsibilities($group_id, $member_id);
						}
					}
				}
		
				if($group_id) {
					// Settings
					
					@$subject_has_mask = DevblocksPlatform::importGPC($_REQUEST['subject_has_mask'],'integer',0);
					@$subject_prefix = DevblocksPlatform::importGPC($_REQUEST['subject_prefix'],'string','');
			
					DAO_GroupSettings::set($group_id, DAO_GroupSettings::SETTING_SUBJECT_HAS_MASK, $subject_has_mask);
					DAO_GroupSettings::set($group_id, DAO_GroupSettings::SETTING_SUBJECT_PREFIX, $subject_prefix);
					
					// Custom field saves
					
					@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', array());
					DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_GROUP, $group_id, $field_ids);
					
					// Avatar image
					@$avatar_image = DevblocksPlatform::importGPC($_REQUEST['avatar_image'], 'string', '');
					DAO_ContextAvatar::upsertWithImage(CerberusContexts::CONTEXT_GROUP, $group_id, $avatar_image);
				}
			} // end new/edit
			
			echo json_encode(array(
				'status' => true,
				'id' => $group_id,
				'label' => $name,
				'view_id' => $view_id,
			));
			return;
			
		} catch (Exception_DevblocksAjaxValidationError $e) {
				echo json_encode(array(
					'status' => false,
					'id' => $group_id,
					'error' => $e->getMessage(),
					'field' => $e->getFieldName(),
				));
				return;
			
		} catch (Exception $e) {
				echo json_encode(array(
					'status' => false,
					'id' => $group_id,
					'error' => 'An error occurred.',
				));
				return;
			
		}
		
	}
	
	function showMembersTabAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		
		if(!$id || false == ($group = DAO_Group::get($id)))
			return;
		
		$tpl = DevblocksPlatform::services()->template();

		$defaults = C4_AbstractViewModel::loadFromClass('View_Worker');
		$defaults->id = 'group_members';
		$defaults->name = 'Members';
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
		
		$tpl = DevblocksPlatform::services()->template();

		$defaults = C4_AbstractViewModel::loadFromClass('View_Bucket');
		$defaults->id = 'group_buckets';
		$defaults->name = 'Buckets';
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
		$url_writer = DevblocksPlatform::services()->url();
		
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