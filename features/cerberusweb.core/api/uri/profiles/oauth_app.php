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

class PageSection_ProfilesOAuthApp extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // oauth_app 
		@$context_id = intval(array_shift($stack)); // 123
		
		$context = Context_OAuthApp::ID;
		
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
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", Context_OAuthApp::ID)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				if(false == ($model = DAO_OAuthApp::get($id)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.record.not_found'));
				
				if(!Context_OAuthApp::isDeletableByActor($model, $active_worker))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				CerberusContexts::logActivityRecordDelete(Context_OAuthApp::ID, $model->id, $model->name);
				
				DAO_OAuthApp::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				$name = DevblocksPlatform::importGPC($_POST['name'] ?? null, 'string', '');
				$client_id = DevblocksPlatform::importGPC($_POST['client_id'] ?? null, 'string', '');
				$client_secret = DevblocksPlatform::importGPC($_POST['client_secret'] ?? null, 'string', '');
				$url = DevblocksPlatform::importGPC($_POST['url'] ?? null, 'string', '');
				$callback_url = DevblocksPlatform::importGPC($_POST['callback_url'] ?? null, 'string', '');
				$scopes_yaml = DevblocksPlatform::importGPC($_POST['scopes_yaml'] ?? null, 'string', '');
				
				$error = null;
				
				if(empty($id)) { // New
					$fields = [
						DAO_OAuthApp::CLIENT_ID => $client_id,
						DAO_OAuthApp::CLIENT_SECRET => $client_secret,
						DAO_OAuthApp::CALLBACK_URL => $callback_url,
						DAO_OAuthApp::NAME => $name,
						DAO_OAuthApp::UPDATED_AT => time(),
						DAO_OAuthApp::URL => $url,
						DAO_OAuthApp::SCOPES => $scopes_yaml,
					];
					
					if(!DAO_OAuthApp::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_OAuthApp::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
						
					$id = DAO_OAuthApp::create($fields);
					DAO_OAuthApp::onUpdateByActor($active_worker, $fields, $id);
					
					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, Context_OAuthApp::ID, $id);
					
				} else { // Edit
					$fields = [
						DAO_OAuthApp::CLIENT_ID => $client_id,
						DAO_OAuthApp::CLIENT_SECRET => $client_secret,
						DAO_OAuthApp::CALLBACK_URL => $callback_url,
						DAO_OAuthApp::NAME => $name,
						DAO_OAuthApp::UPDATED_AT => time(),
						DAO_OAuthApp::URL => $url,
						DAO_OAuthApp::SCOPES => $scopes_yaml,
					];
					
					if(!DAO_OAuthApp::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
						
					if(!DAO_OAuthApp::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					DAO_OAuthApp::update($id, $fields);
					DAO_OAuthApp::onUpdateByActor($active_worker, $fields, $id);
					
				}
	
				// Custom field saves
				$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'] ?? null, 'array', []);
				if(!DAO_CustomFieldValue::handleFormPost(Context_OAuthApp::ID, $id, $field_ids, $error))
					throw new Exception_DevblocksAjaxValidationError($error);
				
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
