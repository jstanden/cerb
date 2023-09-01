<?php /** @noinspection DuplicatedCode */
/** @noinspection PhpUnused */

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
	
	function handleActionForPage(string $action, string $scope=null) {
		if('profileAction' == $scope) {
			switch ($action) {
				case 'savePeekJson':
					return $this->_profileAction_savePeekJson();
				case 'showBulkPopup':
					return $this->_profileAction_showBulkPopup();
				case 'startBulkUpdateJson':
					return $this->_profileAction_startBulkUpdateJson();
				case 'viewExplore':
					return $this->_profileAction_viewExplore();
			}
		}
		return false;
	}
	
	private function _profileAction_savePeekJson() {
		$group_id = DevblocksPlatform::importGPC($_POST['id'] ?? null, 'integer',0);
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string','');
		$do_delete = DevblocksPlatform::importGPC($_POST['do_delete'] ?? null, 'integer',0);

		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(!($active_worker->is_superuser || $active_worker->isGroupManager($group_id)))
				throw new Exception_DevblocksAjaxValidationError("You do not have access to modify this group.");
		
			if($do_delete) {
				$move_deleted_buckets = DevblocksPlatform::importGPC($_POST['move_deleted_buckets'] ?? null, 'array', []);
				$buckets = DAO_Bucket::getAll();
				
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_GROUP)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				if(false == ($model = DAO_Group::get($group_id)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.record.not_found'));
				
				if(!Context_Group::isDeletableByActor($model, $active_worker))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				// Handle preferred bucket relocation
				
				if(is_array($move_deleted_buckets))
				foreach($move_deleted_buckets as $from_bucket_id => $to_bucket_id) {
					if(!isset($buckets[$from_bucket_id]) || !isset($buckets[$to_bucket_id]))
						continue;
					
					DAO_Ticket::updateWhere(array(DAO_Ticket::GROUP_ID => $buckets[$to_bucket_id]->group_id, DAO_Ticket::BUCKET_ID => $to_bucket_id), sprintf("%s = %d", DAO_Ticket::BUCKET_ID, $from_bucket_id));
				}
				
				CerberusContexts::logActivityRecordDelete(CerberusContexts::CONTEXT_GROUP, $model->id, $model->name);
				
				DAO_Group::delete($model->id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $group_id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				$name = DevblocksPlatform::importGPC($_POST['name'] ?? null, 'string','');
				$is_private = DevblocksPlatform::importGPC($_POST['is_private'] ?? null, 'bit',0);
				$reply_address_id = DevblocksPlatform::importGPC($_POST['reply_address_id'] ?? null, 'integer',0);
				$reply_html_template_id = DevblocksPlatform::importGPC($_POST['reply_html_template_id'] ?? null, 'integer',0);
				$reply_personal = DevblocksPlatform::importGPC($_POST['reply_personal'] ?? null, 'string','');
				$reply_signature_id = DevblocksPlatform::importGPC($_POST['reply_signature_id'] ?? null, 'integer',0);
				$reply_signing_key_id = DevblocksPlatform::importGPC($_POST['reply_signing_key_id'] ?? null, 'integer',0);
			
				$profile_image_changed = false;
	
				$fields = [
					DAO_Group::NAME => $name,
					DAO_Group::IS_PRIVATE => $is_private,
					DAO_Group::REPLY_ADDRESS_ID => $reply_address_id,
					DAO_Group::REPLY_HTML_TEMPLATE_ID => $reply_html_template_id,
					DAO_Group::REPLY_PERSONAL => $reply_personal,
					DAO_Group::REPLY_SIGNATURE_ID => $reply_signature_id,
					DAO_Group::REPLY_SIGNING_KEY_ID => $reply_signing_key_id,
				];
				
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
					DAO_Bucket::create($bucket_fields);
					
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
				
				$group_memberships = DevblocksPlatform::sanitizeArray(DevblocksPlatform::importGPC($_POST['group_memberships'] ?? null, 'array', []), 'int');
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
					
					$subject_has_mask = DevblocksPlatform::importGPC($_POST['subject_has_mask'] ?? null, 'integer',0);
					$subject_prefix = DevblocksPlatform::importGPC($_POST['subject_prefix'] ?? null, 'string','');
			
					DAO_GroupSettings::set($group_id, DAO_GroupSettings::SETTING_SUBJECT_HAS_MASK, $subject_has_mask);
					DAO_GroupSettings::set($group_id, DAO_GroupSettings::SETTING_SUBJECT_PREFIX, $subject_prefix);
					
					// Custom field saves
					$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'] ?? null, 'array', []);
					if(!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_GROUP, $group_id, $field_ids, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					// Avatar image
					$avatar_image = DevblocksPlatform::importGPC($_POST['avatar_image'] ?? null, 'string', '');
					$profile_image_changed = DAO_ContextAvatar::upsertWithImage(CerberusContexts::CONTEXT_GROUP, $group_id, $avatar_image);
				}
			} // end new/edit
			
			$event_data = [
				'status' => true,
				'id' => $group_id,
				'label' => $name,
				'view_id' => $view_id,
			];
			
			if($profile_image_changed) {
				$url_writer = DevblocksPlatform::services()->url();
				$type = 'group';
				$event_data['record_image_url'] =
					$url_writer->write(sprintf('c=avatars&type=%s&id=%d', rawurlencode($type), $group_id), true)
					. '?v=' . time()
				;
			}
			
			echo json_encode($event_data);
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
	
	private function _profileAction_viewExplore() {
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string', '');
		$explore_from = DevblocksPlatform::importGPC($_POST['explore_from'] ?? null, 'int', 0);
		
		$http_response = Cerb_ORMHelper::generateRecordExploreSet($view_id, $explore_from);
		DevblocksPlatform::redirect($http_response);
	}
	
	private function _profileAction_showBulkPopup() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		$ids = DevblocksPlatform::importGPC($_REQUEST['ids'] ?? null);
		$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'] ?? null);
		
		if(!$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
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
	
	private function _profileAction_startBulkUpdateJson() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$filter = DevblocksPlatform::importGPC($_POST['filter'] ?? null, 'string','');
		$ids = [];
		
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string');
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);

		$send_as = DevblocksPlatform::importGPC($_POST['send_as'] ?? null, 'string',null);
		$send_from_id = DevblocksPlatform::importGPC($_POST['send_from_id'] ?? null, 'string',null);
		$signature_id = DevblocksPlatform::importGPC($_POST['signature_id'] ?? null, 'string',null);
		$email_template_id = DevblocksPlatform::importGPC($_POST['email_template_id'] ?? null, 'string',null);
		$signing_key_id = DevblocksPlatform::importGPC($_POST['signing_key_id'] ?? null, 'string',null);
		$is_private = DevblocksPlatform::importGPC($_POST['is_private'] ?? null, 'string',null);

		// Scheduled behavior
		$behavior_id = DevblocksPlatform::importGPC($_POST['behavior_id'] ?? null, 'string','');
		$behavior_when = DevblocksPlatform::importGPC($_POST['behavior_when'] ?? null, 'string','');
		$behavior_params = DevblocksPlatform::importGPC($_POST['behavior_params'] ?? null, 'array',[]);
		
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
		
		// Do: Signing key
		if(0 != strlen($signing_key_id)
			&& false !== DAO_GpgPrivateKey::get($signing_key_id))
				$do['signing_key_id'] = $signing_key_id;
		
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
				$ids_str = DevblocksPlatform::importGPC($_POST['ids'] ?? null, 'string');
				$ids = DevblocksPlatform::parseCsvString($ids_str);
				break;
				
			case 'sample':
				$sample_size = min(DevblocksPlatform::importGPC($_POST['filter_sample_size'] ?? null,'integer',0),9999);
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
}