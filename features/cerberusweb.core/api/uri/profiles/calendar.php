<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2018, Webgroup Media LLC
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

class PageSection_ProfilesCalendar extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // calendar
		@$context_id = intval(array_shift($stack)); // 123
		
		$context = CerberusContexts::CONTEXT_CALENDAR;
		
		Page_Profiles::renderProfile($context, $context_id, $stack);
	}
	
	function savePeekJsonAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$name = DevblocksPlatform::importGPC($_REQUEST['name'], 'string', '');
		@$owner = DevblocksPlatform::importGPC($_REQUEST['owner'], 'string', '');
		@$params = DevblocksPlatform::importGPC($_REQUEST['params'], 'array', array());
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_CALENDAR)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				DAO_Calendar::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				// Owner
				
				@list($owner_context, $owner_context_id) = explode(':', $owner);
			
				switch($owner_context) {
					case CerberusContexts::CONTEXT_APPLICATION:
					case CerberusContexts::CONTEXT_ROLE:
					case CerberusContexts::CONTEXT_GROUP:
					case CerberusContexts::CONTEXT_BOT:
					case CerberusContexts::CONTEXT_WORKER:
						break;
						
					default:
						$owner_context = null;
						$owner_context_id = null;
						break;
				}

				// Clean params
				// [TODO] Move this
				
				if(isset($params['series']))
				foreach($params['series'] as $series_idx => $series) {
					if(isset($series['worklist_model_json'])) {
						$series['worklist_model'] = json_decode($series['worklist_model_json'], true);
						unset($series['worklist_model_json']);
						$params['series'][$series_idx] = $series;
					}
				}
				
				// Model
				
				if(empty($id)) { // New
					$fields = array(
						DAO_Calendar::UPDATED_AT => time(),
						DAO_Calendar::NAME => $name,
						DAO_Calendar::OWNER_CONTEXT => $owner_context,
						DAO_Calendar::OWNER_CONTEXT_ID => $owner_context_id,
						DAO_Calendar::PARAMS_JSON => json_encode($params),
					);
					
					if(!DAO_Calendar::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_Calendar::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(false == ($id = DAO_Calendar::create($fields)))
						return new Exception_DevblocksAjaxValidationError("An unexpected error occurred while saving the record.");
					
					DAO_Calendar::onUpdateByActor($active_worker, $fields, $id);
					
					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_CALENDAR, $id);
					
				} else { // Edit
					if(false == ($calendar = DAO_Calendar::get($id)))
						return;
					
					$fields = array(
						DAO_Calendar::UPDATED_AT => time(),
						DAO_Calendar::NAME => $name,
						DAO_Calendar::OWNER_CONTEXT => $owner_context,
						DAO_Calendar::OWNER_CONTEXT_ID => $owner_context_id,
						DAO_Calendar::PARAMS_JSON => json_encode($params),
					);
					
					$change_fields = Cerb_ORMHelper::uniqueFields($fields, $calendar);
					
					if(!DAO_Calendar::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_Calendar::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					DAO_Calendar::update($id, $change_fields);
					DAO_Calendar::onUpdateByActor($active_worker, $change_fields, $id);
				}
				
				// Custom field saves
				@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', []);
				if(!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_CALENDAR, $id, $field_ids, $error))
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
};
