<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2019, Webgroup Media LLC
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
		$request = DevblocksPlatform::getHttpRequest();
		$stack = $request->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // group
		@$context_id = intval(array_shift($stack));
		
		$context = CerberusContexts::CONTEXT_GROUP;
		
		Page_Profiles::renderProfile($context, $context_id, $stack);
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
				@$reply_address_id = DevblocksPlatform::importGPC($_REQUEST['reply_address_id'],'integer',0);
				@$reply_html_template_id = DevblocksPlatform::importGPC($_REQUEST['reply_html_template_id'],'integer',0);
				@$reply_personal = DevblocksPlatform::importGPC($_REQUEST['reply_personal'],'string','');
				@$reply_signature_id = DevblocksPlatform::importGPC($_REQUEST['reply_signature_id'],'integer',0);
			
				$fields = array(
					DAO_Group::NAME => $name,
					DAO_Group::IS_PRIVATE => $is_private,
					DAO_Group::REPLY_ADDRESS_ID => $reply_address_id,
					DAO_Group::REPLY_HTML_TEMPLATE_ID => $reply_html_template_id,
					DAO_Group::REPLY_PERSONAL => $reply_personal,
					DAO_Group::REPLY_SIGNATURE_ID => $reply_signature_id,
				);
				
				if(empty($group_id)) { // new
					if(!DAO_Group::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_Group::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					$group_id = DAO_Group::create($fields);
					DAO_Group::onUpdateByActor($active_worker, $fields, $group_id);
					
					$bucket_fields = array(
						DAO_Bucket::NAME => 'Inbox',
						DAO_Bucket::GROUP_ID => $group_id,
						DAO_Bucket::IS_DEFAULT => 1,
						DAO_Bucket::UPDATED_AT => time(),
					);
					$bucket_id = DAO_Bucket::create($bucket_fields);
					
					// View marquee
					if(!empty($group_id) && !empty($view_id)) {
						C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_GROUP, $group_id);
					}
					
				} else { // update
					if(!DAO_Group::validate($fields, $error, $group_id))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_Group::onBeforeUpdateByActor($active_worker, $fields, $group_id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					DAO_Group::update($group_id, $fields);
					DAO_Group::onUpdateByActor($active_worker, $fields, $group_id);
				}
				
				// Members
				
				@$group_memberships = DevblocksPlatform::sanitizeArray(DevblocksPlatform::importGPC($_REQUEST['group_memberships'], 'array', []), 'int');
				$group_members = DAO_Group::getGroupMembers($group_id);
				
				// Update group memberships
				if(is_array($group_memberships)) {
					foreach ($group_memberships as $member_id => $membership) {
						$is_member = 0 != $membership;
						$is_manager = 2 == $membership;
						
						// If this worker shouldn't be a member
						if (!$is_member) {
							// If they were previously a member, remove them
							if (isset($group_members[$member_id])) {
								DAO_Group::unsetGroupMember($group_id, $member_id);
							}
							
							// If this worker should be a member/manager
						} else {
							DAO_Group::setGroupMember($group_id, $member_id, $is_manager);
							
							// If the worker wasn't previously a member/manager
							if (!isset($group_members[$member_id])) {
								DAO_Group::setMemberDefaultResponsibilities($group_id, $member_id);
							}
						}
						
						DAO_WorkerRole::clearWorkerCache($member_id);
					}
				}
				
				if($group_id) {
					// Settings
					
					@$subject_has_mask = DevblocksPlatform::importGPC($_REQUEST['subject_has_mask'],'integer',0);
					@$subject_prefix = DevblocksPlatform::importGPC($_REQUEST['subject_prefix'],'string','');
			
					DAO_GroupSettings::set($group_id, DAO_GroupSettings::SETTING_SUBJECT_HAS_MASK, $subject_has_mask);
					DAO_GroupSettings::set($group_id, DAO_GroupSettings::SETTING_SUBJECT_PREFIX, $subject_prefix);
					
					// Custom field saves
					@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', []);
					if(!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_GROUP, $group_id, $field_ids, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
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
	
	function showBulkPopupAction() {
		@$ids = DevblocksPlatform::importGPC($_REQUEST['ids']);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);

		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker->is_superuser)
			return;
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('view_id', $view_id);

		if(!empty($ids)) {
			$tpl->assign('ids', $ids);
		}
		
		// Custom fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_GROUP, false);
		$tpl->assign('custom_fields', $custom_fields);
		
		$tpl->display('devblocks:cerberusweb.core::groups/bulk.tpl');
	}
	
	function startBulkUpdateJsonAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker->is_superuser)
			return;
		
		@$filter = DevblocksPlatform::importGPC($_REQUEST['filter'],'string','');
		$ids = [];
		
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);

		@$send_as = DevblocksPlatform::importGPC($_POST['send_as'],'string',null);
		@$send_from_id = DevblocksPlatform::importGPC($_POST['send_from_id'],'string',null);
		@$signature_id = DevblocksPlatform::importGPC($_POST['signature_id'],'string',null);
		@$email_template_id = DevblocksPlatform::importGPC($_POST['email_template_id'],'string',null);
		@$is_private = DevblocksPlatform::importGPC($_POST['is_private'],'string',null);

		// Scheduled behavior
		@$behavior_id = DevblocksPlatform::importGPC($_POST['behavior_id'],'string','');
		@$behavior_when = DevblocksPlatform::importGPC($_POST['behavior_when'],'string','');
		@$behavior_params = DevblocksPlatform::importGPC($_POST['behavior_params'],'array',[]);
		
		$do = [];
		
		// Do: Email template
		if(0 != strlen($email_template_id) 
			&& false !== DAO_MailHtmlTemplate::get($email_template_id))
				$do['email_template_id'] = $email_template_id;
		
		// Do: Is Private
		if(0 != strlen($is_private)) 
			$do['is_private'] = DevblocksPlatform::importVar($is_private, 'bit', 0);
		
		// Do: Send as
		if(0 != strlen($send_as))
			$do['send_as'] = $send_as;
		
		// Do: Send from
		if(0 != strlen($send_from_id) 
			&& false !== DAO_Address::get($send_from_id))
				$do['send_from_id'] = $send_from_id;
		
		// Do: Signature
		if(0 != strlen($signature_id) 
			&& false !== DAO_EmailSignature::get($signature_id))
				$do['signature_id'] = $signature_id;
		
		// Do: Scheduled Behavior
		if(0 != strlen($behavior_id)) {
			$do['behavior'] = array(
				'id' => $behavior_id,
				'when' => $behavior_when,
				'params' => $behavior_params,
			);
		}
		
		// Do: Custom fields
		$do = DAO_CustomFieldValue::handleBulkPost($do);
		
		switch($filter) {
			// Checked rows
			case 'checks':
				@$ids_str = DevblocksPlatform::importGPC($_REQUEST['ids'],'string');
				$ids = DevblocksPlatform::parseCsvString($ids_str);
				break;
				
			case 'sample':
				@$sample_size = min(DevblocksPlatform::importGPC($_REQUEST['filter_sample_size'],'integer',0),9999);
				$filter = 'checks';
				$ids = $view->getDataSample($sample_size);
				break;
				
			default:
				break;
		}
		
		// If we have specific IDs, add a filter for those too
		if(!empty($ids)) {
			$view->addParams([
				new DevblocksSearchCriteria(SearchFields_Group::ID, 'in', $ids)
			], true);
		}
		
		// Create batches
		$batch_key = DAO_ContextBulkUpdate::createFromView($view, $do);
		
		header('Content-Type: application/json; charset=utf-8');
		
		echo json_encode(array(
			'cursor' => $batch_key,
		));
		
		return;
	}
};