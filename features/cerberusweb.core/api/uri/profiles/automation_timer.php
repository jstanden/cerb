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

class PageSection_ProfilesAutomationTimer extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // automation_timer 
		@$context_id = intval(array_shift($stack)); // 123
		
		$context = CerberusContexts::CONTEXT_AUTOMATION_TIMER;
		
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
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(!$active_worker->is_superuser)
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_AUTOMATION_TIMER)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				if(false == ($model = DAO_AutomationTimer::get($id)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.record.not_found'));
				
				if(!Context_AutomationTimer::isDeletableByActor($model, $active_worker))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				CerberusContexts::logActivityRecordDelete(Context_AutomationTimer::ID, $model->id, $model->name);
				
				DAO_AutomationTimer::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				$name = DevblocksPlatform::importGPC($_POST['name'] ?? null, 'string', '');
				$next_run_at = DevblocksPlatform::importGPC($_POST['next_run_at'] ?? null, 'string', '');
				$is_disabled = DevblocksPlatform::importGPC($_POST['is_disabled'] ?? null, 'bit', 0);
				$is_recurring = DevblocksPlatform::importGPC($_POST['is_recurring'] ?? null, 'bit', 0);
				$recurring_patterns = DevblocksPlatform::importGPC($_POST['recurring_patterns'] ?? null, 'string', '');
				$recurring_timezone = DevblocksPlatform::importGPC($_POST['recurring_timezone'] ?? null, 'string', '');
				$automations_kata = DevblocksPlatform::importGPC($_POST['automations_kata'] ?? null, 'string', '');
				
				if(!$active_worker->is_superuser)
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.edit'));
				
				if(!$recurring_timezone)
					$recurring_timezone = DevblocksPlatform::getTimezone();
				
				$patterns = DevblocksPlatform::parseCrlfString($recurring_patterns);
				
				if($is_recurring && $recurring_patterns) {
					// Validate all patterns
					foreach($patterns as $pattern) {
						if(DevblocksPlatform::strStartsWith($pattern, '#'))
							continue;
						
						if(!Cron\CronExpression::isValidExpression($pattern))
							throw new Exception_DevblocksAjaxValidationError(sprintf("Invalid cron expression: `%s`", $pattern));
					}
				}
				
				$error = null;
				
				if(!$next_run_at) {
					if($is_recurring && $recurring_patterns) {
						$next_run_at = DevblocksPlatform::services()->date()->getNextOccurrence($patterns, $recurring_timezone);
					} else {
						$next_run_at = time();
					}
				} else {
					$next_run_at = strtotime($next_run_at);
				}
				
				$fields = [
					DAO_AutomationTimer::AUTOMATIONS_KATA => $automations_kata,
					DAO_AutomationTimer::NAME => $name,
					DAO_AutomationTimer::NEXT_RUN_AT => $next_run_at,
					DAO_AutomationTimer::IS_DISABLED => $is_disabled,
					DAO_AutomationTimer::IS_RECURRING => $is_recurring,
					DAO_AutomationTimer::RECURRING_PATTERNS => $recurring_patterns,
					DAO_AutomationTimer::RECURRING_TIMEZONE => $recurring_timezone,
					DAO_AutomationTimer::UPDATED_AT => time(),
				];
				
				if(empty($id)) { // New
					if(!DAO_AutomationTimer::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_AutomationTimer::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					$id = DAO_AutomationTimer::create($fields);
					DAO_AutomationTimer::onUpdateByActor($active_worker, $fields, $id);
					
					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_AUTOMATION_TIMER, $id);
					
				} else { // Edit
					if(!DAO_AutomationTimer::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_AutomationTimer::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					DAO_AutomationTimer::update($id, $fields);
					DAO_AutomationTimer::onUpdateByActor($active_worker, $fields, $id);
				}
				
				if($id) {
					// Custom field saves
					$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'] ?? null, 'array', []);
					if(!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_AUTOMATION_TIMER, $id, $field_ids, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
				}
				
				echo json_encode(array(
					'status' => true,
					'context' => CerberusContexts::CONTEXT_AUTOMATION_TIMER,
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
