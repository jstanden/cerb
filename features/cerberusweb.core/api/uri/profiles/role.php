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

class PageSection_ProfilesWorkerRole extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // role 
		@$context_id = intval(array_shift($stack)); // 123
		
		$context = CerberusContexts::CONTEXT_ROLE;
		
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
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string', '');
		
		$id = DevblocksPlatform::importGPC($_POST['id'] ?? null, 'integer', 0);
		$do_delete = DevblocksPlatform::importGPC($_POST['do_delete'] ?? null, 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(!$active_worker->is_superuser || !$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_ROLE)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				if(false == ($model = DAO_WorkerRole::get($id)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.record.not_found'));
				
				if(!Context_WorkerRole::isDeletableByActor($model, $active_worker))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				CerberusContexts::logActivityRecordDelete(CerberusContexts::CONTEXT_ROLE, $model->id, $model->name);
				
				DAO_WorkerRole::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				$name = DevblocksPlatform::importGPC($_POST['name'] ?? null, 'string', '');
				$member_query_worker = DevblocksPlatform::importGPC($_POST['member_query_worker'] ?? null, 'string', '');
				$editor_query_worker = DevblocksPlatform::importGPC($_POST['editor_query_worker'] ?? null, 'string', '');
				$reader_query_worker = DevblocksPlatform::importGPC($_POST['reader_query_worker'] ?? null, 'string', '');
				
				$privs_mode = DevblocksPlatform::importGPC($_POST['privs_mode'] ?? null, 'string','');
				$acl_privs = DevblocksPlatform::importGPC($_POST['acl_privs'] ?? null, 'array', []);
				
				if(in_array($privs_mode, ['all','']))
					$acl_privs = [];
				
				$error = null;
				
				if(empty($id)) { // New
					$fields = array(
						DAO_WorkerRole::NAME => $name,
						DAO_WorkerRole::MEMBER_QUERY_WORKER => $member_query_worker,
						DAO_WorkerRole::EDITOR_QUERY_WORKER => $editor_query_worker,
						DAO_WorkerRole::READER_QUERY_WORKER => $reader_query_worker,
						DAO_WorkerRole::PRIVS_MODE => $privs_mode,
						DAO_WorkerRole::PRIVS_JSON => json_encode($acl_privs),
						DAO_WorkerRole::UPDATED_AT => time(),
					);
					
					if(!DAO_WorkerRole::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_WorkerRole::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					$id = DAO_WorkerRole::create($fields);
					DAO_WorkerRole::onUpdateByActor($active_worker, $fields, $id);
					
					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_ROLE, $id);
					
				} else { // Edit
					$fields = array(
						DAO_WorkerRole::NAME => $name,
						DAO_WorkerRole::MEMBER_QUERY_WORKER => $member_query_worker,
						DAO_WorkerRole::EDITOR_QUERY_WORKER => $editor_query_worker,
						DAO_WorkerRole::READER_QUERY_WORKER => $reader_query_worker,
						DAO_WorkerRole::PRIVS_MODE => $privs_mode,
						DAO_WorkerRole::PRIVS_JSON => json_encode($acl_privs),
						DAO_WorkerRole::UPDATED_AT => time(),
					);
					
					if(!DAO_WorkerRole::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_WorkerRole::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					DAO_WorkerRole::update($id, $fields);
					DAO_WorkerRole::onUpdateByActor($active_worker, $fields, $id);
				}
				
				// Custom field saves
				$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'] ?? null, 'array', []);
				if(!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_ROLE, $id, $field_ids, $error))
					throw new Exception_DevblocksAjaxValidationError($error);
				
				// Avatar image
				$avatar_image = DevblocksPlatform::importGPC($_POST['avatar_image'] ?? null, 'string', '');
				DAO_ContextAvatar::upsertWithImage(CerberusContexts::CONTEXT_ROLE, $id, $avatar_image);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'label' => $name,
					'view_id' => $view_id,
				));
				return;
			}
			
		} catch (Exception_DevblocksAjaxValidationError $e) {
			echo json_encode(array(
				'status' => false,
				'error' => $e->getMessage(),
				'field' => $e->getFieldName(),
			));
			return;
			
		} catch (Exception $e) {
			echo json_encode(array(
				'status' => false,
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
