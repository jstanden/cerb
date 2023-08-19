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

class PageSection_ProfilesCustomRecord extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // custom_record 
		@$context_id = intval(array_shift($stack)); // 123
		
		$context = CerberusContexts::CONTEXT_CUSTOM_RECORD;
		
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
		
		if(!$active_worker->is_superuser)
			throw new Exception_DevblocksAjaxValidationError("Only administrators can modify custom records.");
		
		try {
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_CUSTOM_RECORD)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				if(false == ($model = DAO_CustomRecord::get($id)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.record.not_found'));
				
				if(!Context_CustomRecord::isDeletableByActor($model, $active_worker))
					throw new Exception_DevblocksAjaxValidationError("You must delete all records of this type first.");
				
				CerberusContexts::logActivityRecordDelete(CerberusContexts::CONTEXT_CUSTOM_RECORD, $model->id, $model->name);
				
				DAO_CustomRecord::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				$name = DevblocksPlatform::importGPC($_POST['name'] ?? null, 'string', '');
				$name_plural = DevblocksPlatform::importGPC($_POST['name_plural'] ?? null, 'string', '');
				$uri = DevblocksPlatform::importGPC($_POST['uri'] ?? null, 'string', '');
				$params = DevblocksPlatform::importGPC($_POST['params'] ?? null, 'array', []);
				
				$error = null;
				
				if(empty($id)) { // New
					$role_privs = DevblocksPlatform::importGPC($_POST['role_privs'] ?? null, 'array', []);
					
					$fields = array(
						DAO_CustomRecord::NAME => $name,
						DAO_CustomRecord::NAME_PLURAL => $name_plural,
						DAO_CustomRecord::PARAMS_JSON => json_encode($params),
						DAO_CustomRecord::UPDATED_AT => time(),
						DAO_CustomRecord::URI => $uri,
					);
					
					if(!DAO_CustomRecord::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_CustomRecord::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					$id = DAO_CustomRecord::create($fields);
					DAO_CustomRecord::onUpdateByActor($active_worker, $fields, $id);
					
					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_CUSTOM_RECORD, $id);
					
					// Are we updating any role privileges?
					if(!empty($role_privs) && is_array($role_privs)) {
						$priv_prefix = sprintf('contexts.contexts.custom_record.%d.', $id);
						
						foreach($role_privs as $role_id => $privs) {
							if(false == ($role = DAO_WorkerRole::get($role_id)))
								continue;
							
							if('itemized' != $role->privs_mode)
								continue;
							
							$model_privs = $role->getPrivs();
							
							foreach($privs as $priv)
								$model_privs[] = $priv_prefix . $priv;
							
							$model_privs = array_unique($model_privs);
							sort($model_privs);
							
							DAO_WorkerRole::update($role_id, [
								DAO_WorkerRole::PRIVS_JSON => json_encode($model_privs),
							]);
						}
					}
					
				} else { // Edit
					$fields = array(
						DAO_CustomRecord::NAME => $name,
						DAO_CustomRecord::NAME_PLURAL => $name_plural,
						DAO_CustomRecord::PARAMS_JSON => json_encode($params),
						DAO_CustomRecord::UPDATED_AT => time(),
						DAO_CustomRecord::URI => $uri,
					);
					
					if(!DAO_CustomRecord::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_CustomRecord::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					DAO_CustomRecord::update($id, $fields);
					DAO_CustomRecord::onUpdateByActor($active_worker, $fields, $id);
					
					$owners = ($params['owners']['contexts'] ?? null) ?: [];
					
					$dao_class = 'DAO_AbstractCustomRecord_' . $id;
					$dao_class::clearOtherOwners($owners);
				}
				
				// Custom field saves
				$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'] ?? null, 'array', []);
				if(!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_CUSTOM_RECORD, $id, $field_ids, $error))
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
}
