<?php
/***********************************************************************
| Cerb(tm) developed by WebGroup Media, LLC.
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

class ChGroupsPage extends CerberusPageExtension  {
	function isVisible() {
		// The current session must be a logged-in worker to use this page.
		if(null == ($worker = CerberusApplication::getActiveWorker()))
			return false;
		return true;
	}
	
	function getActivity() {
		return new Model_Activity('activity.default');
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$active_worker = CerberusApplication::getActiveWorker();
		$visit = CerberusApplication::getVisit();
		
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		$command = array_shift($stack); // groups
		
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		@$group_id = intval(array_shift($stack));

		// Only group managers and superusers can configure
		if(empty($group_id) || (!$active_worker->isGroupManager($group_id) && !$active_worker->is_superuser)) {
			// do nothing (only show list)
			
		} else {
			$group =& $groups[$group_id];
			$tpl->assign('group', $group);
			
			// Remember the last tab/URL
			if(null == ($selected_tab = @$response->path[2])) {
				$selected_tab = $visit->get('cerberusweb.groups.tab', '');
			}
			$tpl->assign('selected_tab', $selected_tab);
		}
		
		$tpl->display('devblocks:cerberusweb.core::groups/index.tpl');
	}
	
	function showTabMailAction() {
		@$group_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$active_worker = CerberusApplication::getActiveWorker();
		$visit = CerberusApplication::getVisit();
		
		$visit->set('cerberusweb.groups.tab', 'mail');
		
		if(!$active_worker->isGroupManager($group_id) && !$active_worker->is_superuser) {
			return;
		} else {
			$group = DAO_Group::get($group_id);
			$tpl->assign('group', $group);
		}
		
		$group_settings = DAO_GroupSettings::getSettings($group_id);
		$tpl->assign('group_settings', $group_settings);
		
		// Template
		$tpl->display('devblocks:cerberusweb.core::groups/manage/index.tpl');
	}
	
	// Post
	function saveTabMailAction() {
		@$group_id = DevblocksPlatform::importGPC($_REQUEST['group_id'],'integer');

		@$active_worker = CerberusApplication::getActiveWorker();
		if(!$active_worker->isGroupManager($group_id) && !$active_worker->is_superuser)
			return;
			
		//========== GENERAL
		@$subject_has_mask = DevblocksPlatform::importGPC($_REQUEST['subject_has_mask'],'integer',0);
		@$subject_prefix = DevblocksPlatform::importGPC($_REQUEST['subject_prefix'],'string','');

		DAO_GroupSettings::set($group_id, DAO_GroupSettings::SETTING_SUBJECT_HAS_MASK, $subject_has_mask);
		DAO_GroupSettings::set($group_id, DAO_GroupSettings::SETTING_SUBJECT_PREFIX, $subject_prefix);
		   
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('groups',$group_id)));
	}
	
	function showTabMembersAction() {
		@$group_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$active_worker = CerberusApplication::getActiveWorker();
		$visit = CerberusApplication::getVisit();
		
		$visit->set('cerberusweb.groups.tab', 'members');
		
		if(!$active_worker->isGroupManager($group_id) && !$active_worker->is_superuser) {
			return;
		} else {
			$group = DAO_Group::get($group_id);
			$tpl->assign('group', $group);
		}
		
		$members = DAO_Group::getGroupMembers($group_id);
		$tpl->assign('members', $members);
		
		$workers = DAO_Worker::getAllActive();
		$tpl->assign('workers', $workers);
		
		$tpl->display('devblocks:cerberusweb.core::groups/manage/members.tpl');
	}
	
	function saveTabMembersAction() {
		@$group_id = DevblocksPlatform::importGPC($_REQUEST['group_id'],'integer');
		@$worker_ids = DevblocksPlatform::importGPC($_REQUEST['worker_ids'],'array',array());
		@$worker_levels = DevblocksPlatform::importGPC($_REQUEST['worker_levels'],'array',array());
		
		@$active_worker = CerberusApplication::getActiveWorker();
		@$members = DAO_Group::getGroupMembers($group_id);
		
		if(!$active_worker->isGroupManager($group_id) && !$active_worker->is_superuser)
			return;
		
		if(is_array($worker_ids) && !empty($worker_ids))
		foreach($worker_ids as $idx => $worker_id) {
			@$level = $worker_levels[$idx];
			
			if(isset($members[$worker_id]) && empty($level)) {
				DAO_Group::unsetGroupMember($group_id, $worker_id);
				DAO_WorkerRole::clearWorkerCache($worker_id);
				
			} elseif(!empty($level)) { // member|manager
				DAO_Group::setGroupMember($group_id, $worker_id, (1==$level)?false:true);
				
				// If this is a new addition
				if(!isset($members[$worker_id]))
					DAO_WorkerRole::clearWorkerCache($worker_id);
			}
		}
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('groups',$group_id,'members')));
	}
	
	function showTabBucketsAction() {
		@$group_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$visit->set('cerberusweb.groups.tab', 'buckets');

		if(!$active_worker->isGroupManager($group_id) && !$active_worker->is_superuser) {
			return;
		} else {
			$group = DAO_Group::get($group_id);
			$tpl->assign('group', $group);
		}
		
		$buckets = DAO_Bucket::getByGroup($group_id);
		$tpl->assign('buckets', $buckets);
		
		$tpl->display('devblocks:cerberusweb.core::groups/manage/buckets/index.tpl');
	}
	
	function saveBucketsOrderAction() {
		@$group_id = DevblocksPlatform::importGPC($_REQUEST['group_id'],'integer');
		@$bucket_ids = DevblocksPlatform::importGPC($_REQUEST['bucket_id'],'array',array());
		
		@$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker->isGroupManager($group_id) && !$active_worker->is_superuser)
			return;
		
		// Save the order
		if(is_array($bucket_ids))
		foreach($bucket_ids as $pos => $bucket_id) {
			if(empty($bucket_id))
				continue;
				
			DAO_Bucket::update($bucket_id,array(
				DAO_Bucket::POS => $pos,
			));
		}
	}
	
	function showBucketPeekAction() {
		@$group_id = DevblocksPlatform::importGPC($_REQUEST['group_id'],'integer',0);
		@$bucket_id = DevblocksPlatform::importGPC($_REQUEST['id'],'string',''); // Keep as string
		
		$tpl = DevblocksPlatform::getTemplateService();

		if(!empty($bucket_id)) {
			$bucket = DAO_Bucket::get($bucket_id);
			$group_id = $bucket->group_id;
			$tpl->assign('bucket', $bucket);
		}
		if(!empty($group_id)) {
			$group = DAO_Group::get($group_id);
			$tpl->assign('group', $group);
		}
		
		$tpl->assign('group_id', $group_id);
		$tpl->assign('bucket_id', $bucket_id);
		$tpl->assign('replyto_addresses', DAO_AddressOutgoing::getAll());
		
		// All buckets
		$buckets = DAO_Bucket::getByGroup($group_id);
		$tpl->assign('buckets', $buckets);
		
		// Signature
		$worker_token_labels = array();
		$worker_token_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, null, $worker_token_labels, $worker_token_values);
		$tpl->assign('worker_token_labels', $worker_token_labels);

		// Template
		$tpl->display('devblocks:cerberusweb.core::groups/manage/buckets/peek.tpl');
	}
	
	function saveBucketPeekAction() {
		@$form_submit = DevblocksPlatform::importGPC($_REQUEST['form_submit'],'string','');
		@$group_id = DevblocksPlatform::importGPC($_REQUEST['group_id'],'integer',0);
		@$bucket_id = DevblocksPlatform::importGPC($_REQUEST['bucket_id'],'string',''); // Keep as string
		@$name = DevblocksPlatform::importGPC($_REQUEST['name'],'string','');
		@$is_hidden = DevblocksPlatform::importGPC($_REQUEST['is_hidden'],'integer',0);
		@$reply_address_id = DevblocksPlatform::importGPC($_REQUEST['reply_address_id'],'integer',0);
		@$reply_personal = DevblocksPlatform::importGPC($_REQUEST['reply_personal'],'string','');
		@$reply_signature = DevblocksPlatform::importGPC($_REQUEST['reply_signature'],'string','');
		
		// ACL
		@$active_worker = CerberusApplication::getActiveWorker();
		if(!$active_worker->isGroupManager($group_id) && !$active_worker->is_superuser)
			return;
		
		switch($form_submit) {
			case 'delete':
				@$delete_moveto = DevblocksPlatform::importGPC($_REQUEST['delete_moveto'],'integer',0);
				$buckets = DAO_Bucket::getAll();
				// Bucket must exist
				if(empty($bucket_id) || !isset($buckets[$bucket_id]))
					break;
				// Destination must be inbox or exist
				if(!empty($delete_moveto) && !isset($buckets[$delete_moveto]))
					break;
				$where = sprintf("%s = %d",DAO_Ticket::BUCKET_ID, $bucket_id);
				DAO_Ticket::updateWhere(array(DAO_Ticket::BUCKET_ID => $delete_moveto), $where);
				DAO_Bucket::delete($bucket_id);
				break;
				
			case 'save':
				if('0' == $bucket_id) { // Inbox
					$fields = array(
						DAO_Group::REPLY_ADDRESS_ID => $reply_address_id,
						DAO_Group::REPLY_PERSONAL => $reply_personal,
						DAO_Group::REPLY_SIGNATURE => $reply_signature,
					);
					DAO_Group::update($group_id, $fields);
					
				} else { // Bucket
					$fields = array(
						DAO_Bucket::NAME => (empty($name) ? 'New Bucket' : $name),
						DAO_Bucket::IS_ASSIGNABLE => ($is_hidden ? 0 : 1),
						DAO_Bucket::REPLY_ADDRESS_ID => $reply_address_id,
						DAO_Bucket::REPLY_PERSONAL => $reply_personal,
						DAO_Bucket::REPLY_SIGNATURE => $reply_signature,
					);
		
					// Create?
					if(empty($bucket_id)) {
						$bucket_id = DAO_Bucket::create($name, $group_id);
					}
						
					DAO_Bucket::update($bucket_id, $fields);
				}
				break;
		}
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('groups',$group_id,'buckets')));
	}
	
	function showTabFieldsAction() {
		@$group_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$active_worker = CerberusApplication::getActiveWorker();
		$visit = CerberusApplication::getVisit();
		
		$visit->set('cerberusweb.groups.tab', 'fields');		
		
		if(!$active_worker->isGroupManager($group_id) && !$active_worker->is_superuser) {
			return;
		} else {
			$group = DAO_Group::get($group_id);
			$tpl->assign('group', $group);
		}
		
		$group_fields = DAO_CustomField::getByContextAndGroupId(CerberusContexts::CONTEXT_TICKET, $group_id); 
		$tpl->assign('group_fields', $group_fields);
					
		$types = Model_CustomField::getTypes();
		$tpl->assign('types', $types);
		
		$tpl->display('devblocks:cerberusweb.core::groups/manage/fields.tpl');
	}
	
	// Post
	function saveTabFieldsAction() {
		@$group_id = DevblocksPlatform::importGPC($_POST['group_id'],'integer');
		
		@$active_worker = CerberusApplication::getActiveWorker();
		if(!$active_worker->isGroupManager($group_id) && !$active_worker->is_superuser)
			return;
			
		@$ids = DevblocksPlatform::importGPC($_POST['ids'],'array',array());
		@$names = DevblocksPlatform::importGPC($_POST['names'],'array',array());
		@$orders = DevblocksPlatform::importGPC($_POST['orders'],'array',array());
		@$options = DevblocksPlatform::importGPC($_POST['options'],'array',array());
		@$allow_delete = DevblocksPlatform::importGPC($_POST['allow_delete'],'integer',0);
		@$deletes = DevblocksPlatform::importGPC($_POST['deletes'],'array',array());
		
		if(!empty($ids))
		foreach($ids as $idx => $id) {
			@$name = $names[$idx];
			@$order = intval($orders[$idx]);
			@$option = $options[$idx];
			@$delete = (false !== array_search($id,$deletes) ? 1 : 0);
			
			if($allow_delete && $delete) {
				DAO_CustomField::delete($id);
				
			} else {
				$fields = array(
					DAO_CustomField::NAME => $name, 
					DAO_CustomField::POS => $order,
					DAO_CustomField::OPTIONS => !is_null($option) ? $option : '',
				);
				DAO_CustomField::update($id, $fields);
			}
		}
		
		// Add custom field
		@$add_name = DevblocksPlatform::importGPC($_POST['add_name'],'string','');
		@$add_type = DevblocksPlatform::importGPC($_POST['add_type'],'string','');
		@$add_options = DevblocksPlatform::importGPC($_POST['add_options'],'string','');
		
		if(!empty($add_name) && !empty($add_type)) {
			$fields = array(
				DAO_CustomField::NAME => $add_name,
				DAO_CustomField::TYPE => $add_type,
				DAO_CustomField::GROUP_ID => $group_id,
				DAO_CustomField::CONTEXT => CerberusContexts::CONTEXT_TICKET,
				DAO_CustomField::OPTIONS => $add_options,
			);
			$id = DAO_CustomField::create($fields);
		}
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('groups',$group_id,'fields')));
	}
	
	function saveGroupsPanelAction() {
		@$group_id = DevblocksPlatform::importGPC($_REQUEST['group_id'],'integer',0);
		@$name = DevblocksPlatform::importGPC($_REQUEST['name'],'string','');

		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');

		$fields = array(
			DAO_Group::NAME => $name			
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

		// Custom field saves
		@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', array());
		DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_GROUP, $group_id, $field_ids);
		
		exit;
	}
};
