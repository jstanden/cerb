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

if(class_exists('Extension_PageSection')):
class PageSection_InternalCustomFieldGroups extends Extension_PageSection {
	function render() {}
	
	function showCustomFieldGroupPeekAction() {
		// [TODO] Check permissions
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
		@$layer = DevblocksPlatform::importGPC($_REQUEST['layer'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl = DevblocksPlatform::getTemplateService();

		$tpl->assign('view_id', $view_id);
		$tpl->assign('layer', $layer);
		
		// Model
		
		$types = Model_CustomField::getTypes();
		$tpl->assign('types', $types);
		
		if($id && null != ($custom_field_group = DAO_CustomFieldGroup::get($id))) {
			$tpl->assign('custom_field_group', $custom_field_group);
			
			$custom_fields = $custom_field_group->getCustomFields();
			$tpl->assign('custom_fields', $custom_fields);
		}
		
		// Contexts
		
		$contexts = Extension_DevblocksContext::getAll(false);
		$tpl->assign('contexts', $contexts);
		
		// Owners
		
		$roles = DAO_WorkerRole::getAll();
		$tpl->assign('roles', $roles);
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);

		$owner_groups = array();
		foreach($groups as $k => $v) {
			if($active_worker->is_superuser || $active_worker->isGroupManager($k))
				$owner_groups[$k] = $v;
		}
		$tpl->assign('owner_groups', $owner_groups);
		
		$owner_roles = array();
		foreach($roles as $k => $v) { /* @var $v Model_WorkerRole */
			if($active_worker->is_superuser)
				$owner_roles[$k] = $v;
		}
		$tpl->assign('owner_roles', $owner_roles);

		// Template
		
		$tpl->display('devblocks:cerberusweb.core::internal/custom_field_groups/peek.tpl');
	}
	
	function saveCustomFieldGroupPeekAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
		
		@$custom_field_group_id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$name = DevblocksPlatform::importGPC($_REQUEST['name'], 'string', '');
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'], 'string', '');
		@$owner = DevblocksPlatform::importGPC($_REQUEST['owner'], 'string', '');
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'], 'integer', 0);
		
		@$ids = DevblocksPlatform::importGPC($_REQUEST['ids'], 'array', array());
		@$types = DevblocksPlatform::importGPC($_REQUEST['types'], 'array', array());
		@$names = DevblocksPlatform::importGPC($_REQUEST['names'], 'array', array());
		@$options = DevblocksPlatform::importGPC($_REQUEST['options'], 'array', array());
		@$deletes = DevblocksPlatform::importGPC($_REQUEST['deletes'], 'array', array());
		
		// [TODO] Delete

		// Check permissions
		
		if(!empty($custom_field_group_id)) {
			if(
				false == ($custom_field_group = DAO_CustomFieldGroup::get($custom_field_group_id))
				|| !$custom_field_group->isWriteableByWorker($active_worker)
			)
			return;
			
			$context = $custom_field_group->context;
		}
		
		// Owner
		
		$owner_ctx = '';
		@list($owner_ctx_code, $owner_ctx_id) = explode('_', $owner, 2);
		
		switch(strtolower($owner_ctx_code)) {
			case 'w':
				$owner_ctx = CerberusContexts::CONTEXT_WORKER;
				break;
			case 'g':
				$owner_ctx = CerberusContexts::CONTEXT_GROUP;
				break;
			case 'r':
				$owner_ctx = CerberusContexts::CONTEXT_ROLE;
				break;
		}
		
		if(empty($owner_ctx))
			return;
		
		// Create field set
		if(empty($custom_field_group_id)) {
			$fields = array(
				DAO_CustomFieldGroup::NAME => $name,
				DAO_CustomFieldGroup::CONTEXT => $context,
				DAO_CustomFieldGroup::OWNER_CONTEXT => $owner_ctx,
				DAO_CustomFieldGroup::OWNER_CONTEXT_ID => $owner_ctx_id,
			);
			$custom_field_group_id = DAO_CustomFieldGroup::create($fields);
			
		// Update field set
		} else {
			$fields = array(
				DAO_CustomFieldGroup::NAME => $name,
				DAO_CustomFieldGroup::OWNER_CONTEXT => $owner_ctx,
				DAO_CustomFieldGroup::OWNER_CONTEXT_ID => $owner_ctx_id,
			);
			DAO_CustomFieldGroup::update($custom_field_group_id, $fields);
			
		}
		
		foreach($ids as $idx => $id) {
			if(
				!isset($types[$idx])
				|| !isset($names[$idx])
				|| empty($names[$idx])
				|| !isset($options[$idx])
			)
				continue;
			
			// Handle field deletion
			if(isset($deletes[$idx]) && !empty($deletes[$idx])) {
				if(empty($id)) {
					continue;
					
				} else {
					if(null == ($cfield = DAO_CustomField::get($id)))
						continue;

					// If we have permission to delete fields
					if(
						$active_worker->is_superuser
						|| ($cfield->custom_field_group_id == $custom_field_group->id
							&& $custom_field_group->isWriteableByWorker($active_worker))
					)
						DAO_CustomField::delete($id);
					
					continue;
				}
			}
			
			// Create field
			if(empty($id)) {
				$fields = array(
					DAO_CustomField::CONTEXT => $context,
					DAO_CustomField::NAME => $names[$idx],
					DAO_CustomField::CUSTOM_FIELD_GROUP_ID => $custom_field_group_id,
					DAO_CustomField::OPTIONS => $options[$idx],
					DAO_CustomField::TYPE => $types[$idx],
					DAO_CustomField::POS => $idx,
				);
				DAO_CustomField::create($fields);
				
			// Modify field
			} else {
				$fields = array(
					DAO_CustomField::NAME => $names[$idx],
					DAO_CustomField::OPTIONS => $options[$idx],
					DAO_CustomField::POS => $idx,
				);
				DAO_CustomField::update($id, $fields);
			}
		}
	}
	
	// [TODO] Render custom field group tab by owner
}
endif;