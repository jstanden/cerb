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

class PageSection_ProfilesAutomationEvent extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // automation_event 
		@$context_id = intval(array_shift($stack)); // 123
		
		$context = CerberusContexts::CONTEXT_AUTOMATION_EVENT;
		
		Page_Profiles::renderProfile($context, $context_id, $stack);
	}
	
	function handleActionForPage(string $action, string $scope=null) {
		if('profileAction' == $scope) {
			switch($action) {
				case 'editorChangeEventJson':
					return $this->_profileAction_editorChangeEventJson();
				case 'refreshListeners':
					return $this->_profileAction_refreshListeners();
				case 'savePeekJson':
					return $this->_profileAction_savePeekJson();
				case 'tester':
					return $this->_profileAction_tester();
				case 'viewExplore':
					return $this->_profileAction_viewExplore();
			}
		}
		return false;
	}
	
	private function _profileAction_editorChangeEventJson() : void {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$event_id = DevblocksPlatform::importGPC($_POST['event_id'] ?? null, 'string');
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		
		try {
			if(!($event = DAO_AutomationEvent::getByName($event_id)))
				throw new Exception_DevblocksAjaxValidationError('Invalid event');
			
			if(!($event_ext = $event->getExtension()))
				throw new Exception_DevblocksAjaxValidationError('Invalid event');
			
			/* @var $event_ext Extension_AutomationTrigger */
			
			$response = [];
			
			// Toolbar
			$toolbar_dict = DevblocksDictionaryDelegate::instance([
				'caller_name' => 'cerb.eventHandler.automation',
				'worker__context' => CerberusContexts::CONTEXT_WORKER,
				'worker_id' => $active_worker->id
			]);
			$toolbar = $event_ext->getEventToolbar();
			
			if(($toolbar = DevblocksPlatform::services()->ui()->toolbar()->parse($toolbar, $toolbar_dict)))
				$response['toolbar_html'] = DevblocksPlatform::services()->ui()->toolbar()->fetch($toolbar);
			
			// Placeholders
			$tpl->assign('trigger_inputs', $event_ext->getEventPlaceholders());
			$response['placeholders_html'] = $tpl->fetch('devblocks:cerberusweb.core::automations/triggers/editor_event_handler_placeholders.tpl');
			
			echo json_encode($response);
			
		} catch (Throwable) {
			DevblocksPlatform::dieWithHttpError(500);
		}
	}
	
	private function _profileAction_refreshListeners() {
		$tpl = DevblocksPlatform::services()->template();
		
		$event_id = DevblocksPlatform::importGPC($_POST['event_id'] ?? null, 'string');
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'text/html; charset=utf-8');
		
		try {
			if(!($event = Extension_AutomationTrigger::get($event_id, false)))
				throw new Exception_DevblocksAjaxValidationError('Invalid event');
			
			$listeners = DAO_AutomationEventListener::getByEvent($event->name, true);
			$tpl->assign('listeners', $listeners);
			
			$tpl->assign('event_id', $event->id);
			$tpl->assign('event_name', $event->name);
			$tpl->display('devblocks:cerberusweb.core::records/types/automation_event/listeners.tpl');
			
		} catch(Exception) {
			DevblocksPlatform::dieWithHttpError(500);
		}
	}
	
	private function _profileAction_tester() {
		$event_handler = DevblocksPlatform::services()->ui()->eventHandler();
		
		$automations_kata = DevblocksPlatform::importGPC($_POST['automations_kata'] ?? null, 'string');
		$placeholders_kata = DevblocksPlatform::importGPC($_POST['placeholders_kata'] ?? null, 'string');
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		
		try {
			$error = null;
			
			if(false === ($values = DevblocksPlatform::services()->kata()->parse($placeholders_kata, $error)))
				throw new Exception_DevblocksValidationError($error);
			
			if(false === ($values = DevblocksPlatform::services()->kata()->formatTree($values, null, $error)))
				throw new Exception_DevblocksValidationError($error);
			
			$dict = DevblocksDictionaryDelegate::instance($values);
			
			if(false === ($handlers = $event_handler->parse($automations_kata, $dict, $error)))
				throw new Exception_DevblocksValidationError($error);
			
			echo json_encode(array_values($handlers));
			
		} catch(Exception_DevblocksValidationError $e) {
			echo json_encode([
				'error' => $e->getMessage(),
			]);
			
		} catch(Exception $e) {
			echo json_encode([
				'error' => 'An unexpected error occurred.',
			]);
		}
	}
	
	private function _profileAction_savePeekJson() {
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string', '');
		
		$id = DevblocksPlatform::importGPC($_POST['id'] ?? null, 'integer', 0);
		$do_delete = DevblocksPlatform::importGPC($_POST['do_delete'] ?? null, 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		
		try {
			if(!empty($id) && !empty($do_delete)) { // Delete
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
			} else {
				$name = DevblocksPlatform::importGPC($_POST['name'] ?? 'Automation Event', 'string');
				
				$error = null;
				
				if(empty($id)) { // New
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.create'));
					
				} else { // Edit
					$fields = array(
						DAO_AutomationEvent::UPDATED_AT => time(),
					);
					
					if(!DAO_AutomationEvent::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_AutomationEvent::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					DAO_AutomationEvent::update($id, $fields);
					DAO_AutomationEvent::onUpdateByActor($active_worker, $fields, $id);
				}
				
				// Custom field saves
				$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'] ?? null, 'array', []);
				if (!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_AUTOMATION_EVENT, $id, $field_ids, $error))
					throw new Exception_DevblocksAjaxValidationError($error);
				
				echo json_encode(array(
					'status' => true,
					'context' => CerberusContexts::CONTEXT_AUTOMATION_EVENT,
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
