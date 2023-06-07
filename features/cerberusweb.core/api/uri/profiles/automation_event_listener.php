<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2023, Webgroup Media LLC
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

class PageSection_ProfilesAutomationEventListener extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // automation_event_listener 
		@$context_id = intval(array_shift($stack)); // 123
		
		$context = CerberusContexts::CONTEXT_AUTOMATION_EVENT_LISTENER;
		
		Page_Profiles::renderProfile($context, $context_id, $stack);
	}
	
	function handleActionForPage(string $action, string $scope=null) {
		if('profileAction' == $scope) {
			switch($action) {
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
		
		if(!$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		
		try {
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_AUTOMATION_EVENT_LISTENER)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				if(!($model = DAO_AutomationEventListener::get($id)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.record.not_found'));
				
				if(!Context_AutomationEventListener::isDeletableByActor($model, $active_worker))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				CerberusContexts::logActivityRecordDelete(Context_AutomationEventListener::ID, $model->id, $model->name);
				
				DAO_AutomationEventListener::delete($id);
				
				echo json_encode([
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				]);
				return;
				
			} else {
				$name = DevblocksPlatform::importGPC($_POST['name'] ?? null, 'string', '');
				$event_name = DevblocksPlatform::importGPC($_POST['event_name'] ?? null, 'string', '');
				$event_kata = DevblocksPlatform::importGPC($_POST['event_kata'] ?? null, 'string', '');
				$priority = DevblocksPlatform::importGPC($_POST['priority'] ?? null, 'int', 0);
				$is_disabled = DevblocksPlatform::importGPC($_POST['is_disabled'] ?? null, 'int', 0);
				
				$error = null;
				
				if(empty($id)) { // New
					$fields = [
						DAO_AutomationEventListener::CREATED_AT => time(),
						DAO_AutomationEventListener::EVENT_KATA => $event_kata,
						DAO_AutomationEventListener::EVENT_NAME => $event_name,
						DAO_AutomationEventListener::IS_DISABLED => $is_disabled,
						DAO_AutomationEventListener::NAME => $name,
						DAO_AutomationEventListener::PRIORITY => $priority,
						DAO_AutomationEventListener::UPDATED_AT => time(),
					];
					
					if(!DAO_AutomationEventListener::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_AutomationEventListener::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					$id = DAO_AutomationEventListener::create($fields);
					DAO_AutomationEventListener::onUpdateByActor($active_worker, $fields, $id);
					
					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_AUTOMATION_EVENT_LISTENER, $id);
					
				} else { // Edit
					$fields = [
						DAO_AutomationEventListener::EVENT_KATA => $event_kata,
						DAO_AutomationEventListener::EVENT_NAME => $event_name,
						DAO_AutomationEventListener::IS_DISABLED => $is_disabled,
						DAO_AutomationEventListener::NAME => $name,
						DAO_AutomationEventListener::PRIORITY => $priority,
						DAO_AutomationEventListener::UPDATED_AT => time(),
					];
					
					if(!DAO_AutomationEventListener::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_AutomationEventListener::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					DAO_AutomationEventListener::update($id, $fields);
					DAO_AutomationEventListener::onUpdateByActor($active_worker, $fields, $id);
				}
				
				if($id) {
					// Custom field saves
					$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'] ?? null, 'array', []);
					if(!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_AUTOMATION_EVENT_LISTENER, $id, $field_ids, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					// Versioning
					try {
						DAO_RecordChangeset::create(
							'automation_event_listener',
							$id,
							[
								'automations_kata' => $fields[DAO_AutomationEventListener::EVENT_KATA] ?? '',
							],
							$active_worker->id ?? 0
						);
						
					} catch (Exception $e) {
						DevblocksPlatform::logError('Error saving changeset: ' . $e->getMessage());
					}
				}
				
				echo json_encode([
					'status' => true,
					'context' => CerberusContexts::CONTEXT_AUTOMATION_EVENT_LISTENER,
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
			
		} catch (Exception $e) {
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
