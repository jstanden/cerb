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

class PageSection_ProfilesBucket extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // bucket
		@$context_id = intval(array_shift($stack)); // 123
		
		$context = CerberusContexts::CONTEXT_BUCKET;
		
		Page_Profiles::renderProfile($context, $context_id, $stack);
	}
	
	function handleActionForPage(string $action, string $scope=null) {
		if('profileAction' == $scope) {
			switch ($action) {
				case 'savePeekJson':
					return $this->_profileAction_savePeekJson();
				case 'viewExplore':
					return $this->_profileAction_viewExplore();
			}
		}
		return false;
	}
	
	private function _profileAction_savePeekJson() {
		$id = DevblocksPlatform::importGPC($_POST['id'] ?? null, 'integer', 0);
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string', '');
		$do_delete = DevblocksPlatform::importGPC($_POST['do_delete'] ?? null, 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if($id && false == ($bucket = DAO_Bucket::get($id)))
				throw new Exception_DevblocksAjaxValidationError("The specified bucket record doesn't exist.");
			
			if($id && $do_delete) { // Delete
				$delete_moveto = DevblocksPlatform::importGPC($_POST['delete_moveto'] ?? null, 'integer',0);
				
				if(false == ($model = DAO_Bucket::get($id)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.record.not_found'));
				
				if(!Context_Bucket::isDeletableByActor($model, $active_worker))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				// Destination must exist
				if(empty($delete_moveto) || false == ($bucket_moveto = DAO_Bucket::get($delete_moveto)))
					throw new Exception_DevblocksAjaxValidationError("The destination bucket doesn't exist.");
				
				$where = sprintf("%s = %d", DAO_Ticket::BUCKET_ID, $id);
				
				DAO_Ticket::updateWhere(array(DAO_Ticket::BUCKET_ID => $bucket_moveto->id), $where);
				
				CerberusContexts::logActivityRecordDelete(CerberusContexts::CONTEXT_BUCKET, $model->id, $model->name);
				
				DAO_Bucket::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				$name = DevblocksPlatform::importGPC($_POST['name'] ?? null, 'string','');
				$enable_mail = DevblocksPlatform::importGPC($_POST['enable_mail'] ?? null, 'integer',0);
				
				$fields = [];
				
				if($enable_mail) {
					$reply_address_id = DevblocksPlatform::importGPC($_POST['reply_address_id'] ?? null, 'integer',0);
					$reply_personal = DevblocksPlatform::importGPC($_POST['reply_personal'] ?? null, 'string','');
					$reply_signature_id = DevblocksPlatform::importGPC($_POST['reply_signature_id'] ?? null, 'integer',0);
					$reply_html_template_id = DevblocksPlatform::importGPC($_POST['reply_html_template_id'] ?? null, 'integer',0);
					$reply_signing_key_id = DevblocksPlatform::importGPC($_POST['reply_signing_key_id'] ?? null, 'integer',0);
				} else {
					$reply_address_id = 0;
					$reply_personal = '';
					$reply_signature_id = 0;
					$reply_html_template_id = 0;
					$reply_signing_key_id = 0;
				}
				
				$fields[DAO_Bucket::REPLY_ADDRESS_ID] = $reply_address_id;
				$fields[DAO_Bucket::REPLY_PERSONAL] = $reply_personal;
				$fields[DAO_Bucket::REPLY_SIGNATURE_ID] = $reply_signature_id;
				$fields[DAO_Bucket::REPLY_HTML_TEMPLATE_ID] = $reply_html_template_id;
				$fields[DAO_Bucket::REPLY_SIGNING_KEY_ID] = $reply_signing_key_id;
				
				if(empty($id)) { // New
					$group_id = DevblocksPlatform::importGPC($_POST['group_id'] ?? null, 'integer',0);
					
					$fields[DAO_Bucket::NAME] = $name;
					$fields[DAO_Bucket::GROUP_ID] = $group_id;
					$fields[DAO_Bucket::UPDATED_AT] = time();
					
					if(!DAO_Bucket::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_Bucket::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					$id = DAO_Bucket::create($fields);
					DAO_Bucket::onUpdateByActor($active_worker, $fields, $id);
					
					// Default bucket responsibilities
					DAO_Group::setBucketDefaultResponsibilities($id);
					
					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_BUCKET, $id);
					
				} else { // Edit
					$fields[DAO_Bucket::NAME] = $name;
					$fields[DAO_Bucket::UPDATED_AT] = time();
					
					if(!DAO_Bucket::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_Bucket::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					DAO_Bucket::update($id, $fields);
					DAO_Bucket::onUpdateByActor($active_worker, $fields, $id);
				}
				
				// Custom field saves
				$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'] ?? null, 'array', []);
				if(!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_BUCKET, $id, $field_ids, $error))
					throw new Exception_DevblocksAjaxValidationError($error);
			}
			
			echo json_encode(array(
				'status' => true,
				'id' => $id,
				'label' => $name,
				'view_id' => $view_id,
			));
			return;
			
		} catch (Exception_DevblocksAjaxValidationError $e) {
				echo json_encode(array(
					'status' => false,
					'id' => $id,
					'error' => $e->getMessage(),
					'field' => $e->getFieldName(),
				));
				return;
			
		} catch (Exception $e) {
				echo json_encode(array(
					'status' => false,
					'id' => $id,
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
};
