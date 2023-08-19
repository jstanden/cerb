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
		$name = DevblocksPlatform::importGPC($_POST['name'] ?? null, 'string', '');
		$owner = DevblocksPlatform::importGPC($_POST['owner'] ?? null, 'string', '');
		$params = DevblocksPlatform::importGPC($_POST['params'] ?? null, 'array', array());
		$timezone = DevblocksPlatform::importGPC($_POST['timezone'] ?? null, 'string', '');
		$do_delete = DevblocksPlatform::importGPC($_POST['do_delete'] ?? null, 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_CALENDAR)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				if(false == ($model = DAO_Calendar::get($id)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.record.not_found'));
				
				if(!Context_Calendar::isDeletableByActor($model, $active_worker))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				CerberusContexts::logActivityRecordDelete(CerberusContexts::CONTEXT_CALENDAR, $model->id, $model->name);
				
				DAO_Calendar::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				$package_uri = DevblocksPlatform::importGPC($_POST['package'] ?? null, 'string', '');
				
				$mode = 'build';
				
				if(!$id && $package_uri)
					$mode = 'library';
				
				// Owner
				
				list($owner_context, $owner_context_id) = array_pad(explode(':', $owner), 2, null);
			
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
				
				if(!CerberusContexts::isOwnableBy($owner_context, $owner_context_id, $active_worker))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.owner'));
				
				switch($mode) {
					case 'library':
						$prompts = DevblocksPlatform::importGPC($_POST['prompts'] ?? null, 'array', []);
						
						if(empty($package_uri))
							throw new Exception_DevblocksAjaxValidationError("You must select a package from the library.");
						
						if(false == ($package = DAO_PackageLibrary::getByUri($package_uri)))
							throw new Exception_DevblocksAjaxValidationError("You selected an invalid package.");
						
						if($package->point != 'calendar')
							throw new Exception_DevblocksAjaxValidationError("The selected package is not for this extension point.");
						
						$package_json = $package->getPackageJson();
						$records_created = [];
						
						$prompts['owner_context'] = $owner_context;
						$prompts['owner_context_id'] = $owner_context_id;
						
						try {
							CerberusApplication::packages()->import($package_json, $prompts, $records_created);
							
						} catch(Exception_DevblocksValidationError $e) {
							throw new Exception_DevblocksAjaxValidationError($e->getMessage());
							
						} catch (Exception $e) {
							throw new Exception_DevblocksAjaxValidationError("An unexpected error occurred.");
						}
						
						if(!array_key_exists(Context_Calendar::ID, $records_created))
							throw new Exception_DevblocksAjaxValidationError("There was an issue creating the record.");
						
						$new_calendar = reset($records_created[Context_Calendar::ID]);
						
						if($view_id)
							C4_AbstractView::setMarqueeContextCreated($view_id, Context_Calendar::ID, $new_calendar['id']);
						
						echo json_encode([
							'status' => true,
							'id' => $new_calendar['id'],
							'label' => $new_calendar['label'],
							'view_id' => $view_id,
						]);
						return;
						break;
						
					case 'build':
						$error = null;
						
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
								DAO_Calendar::TIMEZONE => $timezone,
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
								DAO_Calendar::TIMEZONE => $timezone,
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
						$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'] ?? null, 'array', []);
						if(!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_CALENDAR, $id, $field_ids, $error))
							throw new Exception_DevblocksAjaxValidationError($error);
						
						echo json_encode(array(
							'status' => true,
							'id' => $id,
							'label' => $name,
							'view_id' => $view_id,
						));
						return;
				}
			}
			
			throw new Exception_DevblocksAjaxValidationError("An unexpected error occurred.");
			
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
