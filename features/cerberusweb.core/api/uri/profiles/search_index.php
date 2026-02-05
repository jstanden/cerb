<?php

use Cerb\Extensions\Extension_SearchIndex;

/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2026, Webgroup Media LLC
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

class PageSection_ProfilesRecordSearchIndex extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // search_index
		@$context_id = intval(array_shift($stack)); // 123
		
		$context = Context_SearchIndex::ID;
		
		Page_Profiles::renderProfile($context, $context_id, $stack);
	}
	
	function handleActionForPage(string $action, string $scope=null) {
		if('profileAction' == $scope) {
			switch($action) {
				case 'getExtensionConfig':
					return $this->_profileAction_getExtensionConfig();
				case 'savePeekJson':
					return $this->_profileAction_savePeekJson();
				case 'viewExplore':
					return $this->_profileAction_viewExplore();
			}
		}
		return false;
	}
	
	private function _profileAction_getExtensionConfig() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		// Must be an admin
		if(!$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$extension_id = DevblocksPlatform::importGPC($_POST['extension_id'] ?? null, 'string', null);
		$record_type = DevblocksPlatform::importGPC($_POST['record_type'] ?? null, 'string', null);
		
		if(!$extension_id)
			return;
		
		if(!($trigger_ext = Extension_SearchIndex::get($extension_id, true)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		/* @var $trigger_ext Extension_SearchIndex */
		
		$model = new Model_SearchIndex();
		$model->extension_id = $extension_id;
		$model->record_type = $record_type;
		
		$trigger_ext->renderConfig($model);
	}
	
	private function _profileAction_savePeekJson() {
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string', '');
		
		$id = DevblocksPlatform::importGPC($_POST['id'] ?? null, 'integer', 0);
		$do_delete = DevblocksPlatform::importGPC($_POST['do_delete'] ?? null, 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		$context = Context_SearchIndex::ID;
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		
		try {
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", $context)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				if(!($model = DAO_SearchIndex::get($id)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.record.not_found'));
				
				if(!Context_SearchIndex::isDeletableByActor($model, $active_worker))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				CerberusContexts::logActivityRecordDelete($context, $model->id, $model->name);
				
				DAO_SearchIndex::delete($id);
				
				echo json_encode([
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				]);
				return;
				
			} else {
				$name = DevblocksPlatform::importGPC($_POST['name'] ?? null, 'string', '');
				$record_filter = DevblocksPlatform::importGPC($_POST['record_filter'] ?? null, 'string', '');
				$record_type = DevblocksPlatform::importGPC($_POST['record_type'] ?? null, 'string', '');
				$uri = DevblocksPlatform::importGPC($_POST['uri'] ?? null, 'string', '');
				$extension_id = DevblocksPlatform::importGPC($_POST['extension_id'] ?? null, 'string', '');
				$extension_params = DevblocksPlatform::importGPC($_POST['params'] ?? null, 'array', []);
				$priority = DevblocksPlatform::importGPC($_POST['priority'] ?? null, 'int', 50);
				
				$error = null;
				
				if(empty($id)) { // New
					$fields = [
						DAO_SearchIndex::CREATED_AT => time(),
						DAO_SearchIndex::EXTENSION_ID => $extension_id,
						DAO_SearchIndex::EXTENSION_PARAMS_JSON => json_encode($extension_params),
						DAO_SearchIndex::NAME => $name,
						DAO_SearchIndex::PRIORITY => $priority,
						DAO_SearchIndex::RECORD_FILTER => $record_filter,
						DAO_SearchIndex::RECORD_TYPE => $record_type,
						DAO_SearchIndex::UPDATED_AT => time(),
						DAO_SearchIndex::URI => $uri,
					];
					
					if(!DAO_SearchIndex::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_SearchIndex::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					$id = DAO_SearchIndex::create($fields);
					DAO_SearchIndex::onUpdateByActor($active_worker, $fields, $id);
					
					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, $context, $id);
					
				} else { // Edit
					$fields = [
						DAO_SearchIndex::EXTENSION_PARAMS_JSON => json_encode($extension_params),
						DAO_SearchIndex::NAME => $name,
						DAO_SearchIndex::PRIORITY => $priority,
						DAO_SearchIndex::RECORD_FILTER => $record_filter,
						DAO_SearchIndex::UPDATED_AT => time(),
						DAO_SearchIndex::URI => $uri,
					];
					
					if(!DAO_SearchIndex::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_SearchIndex::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					DAO_SearchIndex::update($id, $fields);
					DAO_SearchIndex::onUpdateByActor($active_worker, $fields, $id);
				}
				
				if($id) {
					// Custom field saves
					$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'] ?? null, 'array', []);
					if(!DAO_CustomFieldValue::handleFormPost($context, $id, $field_ids, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
				}
				
				echo json_encode([
					'status' => true,
					'context' => $context,
					'id' => $id,
					'label' => $name,
					'view_id' => $view_id,
				]);
				return;
			}
			
		} catch (Exception_DevblocksAjaxValidationError $e) {
			echo json_encode([
				'status' => false,
				'error' => $e->getMessage(),
				'field' => $e->getFieldName(),
			]);
			return;
			
		} catch (Throwable) {
			echo json_encode([
				'status' => false,
				'error' => 'An error occurred.',
			]);
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
