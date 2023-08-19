<?php /** @noinspection PhpUnused */

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

class PageSection_ProfilesContextScheduledBehavior extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // context_scheduled_behavior 
		@$context_id = intval(array_shift($stack)); // 123

		$context = CerberusContexts::CONTEXT_BEHAVIOR_SCHEDULED;
		
		Page_Profiles::renderProfile($context, $context_id, $stack);
	}
	
	function handleActionForPage(string $action, string $scope=null) {
		if('profileAction' == $scope) {
			switch ($action) {
				case 'getBulkParams':
					return $this->_profileAction_getBulkParams();
				case 'savePeekJson':
					return $this->_profileAction_savePeekJson();
				case 'renderContextScheduledBehavior':
					return $this->_profileAction_renderContextScheduledBehavior();
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
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_BEHAVIOR_SCHEDULED)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				if(false == ($model = DAO_ContextScheduledBehavior::get($id)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.record.not_found'));
				
				if(!Context_ContextScheduledBehavior::isDeletableByActor($model, $active_worker))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				CerberusContexts::logActivityRecordDelete(CerberusContexts::CONTEXT_BEHAVIOR_SCHEDULED, $model->id, $model->getBehavior()->title);
				
				DAO_ContextScheduledBehavior::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				$behavior_id = DevblocksPlatform::importGPC($_POST['behavior_id'] ?? null, 'integer', 0);
				$behavior_params = DevblocksPlatform::importGPC($_POST['behavior_params'] ?? null, 'array', []);
				$context = DevblocksPlatform::importGPC($_POST['context'] ?? null, 'string', '');
				$context_id = DevblocksPlatform::importGPC($_POST['context_id'] ?? null, 'integer', 0);
				$run_date = DevblocksPlatform::importGPC($_POST['run_date'] ?? null, 'string', '');
				
				if(empty($id)) { // New
					$fields = [
						DAO_ContextScheduledBehavior::BEHAVIOR_ID => $behavior_id,
						DAO_ContextScheduledBehavior::CONTEXT => $context,
						DAO_ContextScheduledBehavior::CONTEXT_ID => $context_id,
						DAO_ContextScheduledBehavior::RUN_DATE => @intval(strtotime($run_date)),
						DAO_ContextScheduledBehavior::VARIABLES_JSON => json_encode($behavior_params),
					];
					
					if(!DAO_ContextScheduledBehavior::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
						
					if(!DAO_ContextScheduledBehavior::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					$id = DAO_ContextScheduledBehavior::create($fields);
					DAO_ContextScheduledBehavior::onUpdateByActor($active_worker, $fields, $id);
					
					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_BEHAVIOR_SCHEDULED, $id);
					
				} else { // Edit
					$fields = [
						DAO_ContextScheduledBehavior::BEHAVIOR_ID => $behavior_id,
						DAO_ContextScheduledBehavior::CONTEXT => $context,
						DAO_ContextScheduledBehavior::CONTEXT_ID => $context_id,
						DAO_ContextScheduledBehavior::RUN_DATE => @intval(strtotime($run_date)),
						DAO_ContextScheduledBehavior::VARIABLES_JSON => json_encode($behavior_params),
					];
					
					if(!DAO_ContextScheduledBehavior::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
						
					if(!DAO_ContextScheduledBehavior::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					DAO_ContextScheduledBehavior::update($id, $fields);
					DAO_ContextScheduledBehavior::onUpdateByActor($active_worker, $fields, $id);
				}
				
				// Custom field saves
				$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'] ?? null, 'array', []);
				if(!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_BEHAVIOR_SCHEDULED, $id, $field_ids, $error))
					throw new Exception_DevblocksAjaxValidationError($error);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'label' => '', // [TODO]
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
	
	private function _profileAction_renderContextScheduledBehavior() {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$context = DevblocksPlatform::importGPC($_REQUEST['context'] ?? null,'string','');
		$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'] ?? null, 'integer',0);
		
		if(!CerberusContexts::isReadableByActor($context, $context_id, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$tpl->assign('context', $context);
		$tpl->assign('context_id', $context_id);
		$tpl->assign('expanded', true);
		
		$tpl->display('devblocks:cerberusweb.core::internal/macros/behavior/scheduled_behavior_profile.tpl');
	}
	
	private function _profileAction_getBulkParams() {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'] ?? null, 'integer', 0);
		
		$tpl->assign('field_name', 'behavior_params');
		
		if(false == ($trigger = DAO_TriggerEvent::get($trigger_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!Context_TriggerEvent::isReadableByActor($trigger, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$tpl->assign('variables', $trigger->variables);
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/assistant/behavior_variables_entry.tpl');
	}
}