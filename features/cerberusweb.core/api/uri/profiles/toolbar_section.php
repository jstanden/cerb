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

class PageSection_ProfilesToolbarSection extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // toolbar_section
		@$context_id = intval(array_shift($stack)); // 123
		
		$context = Context_ToolbarSection::ID;
		
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
		
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		
		try {
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", Context_ToolbarSection::ID)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				if(!($model = DAO_ToolbarSection::get($id)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.record.not_found'));
				
				if(!Context_ToolbarSection::isDeletableByActor($model, $active_worker))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				CerberusContexts::logActivityRecordDelete(Context_ToolbarSection::ID, $model->id, $model->name);
				
				DAO_ToolbarSection::delete($id);
				
				echo json_encode([
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				]);
				return;
				
			} else {
				$is_disabled = DevblocksPlatform::importGPC($_POST['is_disabled'] ?? null, 'int', 0);
				$name = DevblocksPlatform::importGPC($_POST['name'] ?? null, 'string', '');
				$priority = DevblocksPlatform::importGPC($_POST['priority'] ?? null, 'integer', 0);
				$toolbar_kata = DevblocksPlatform::importGPC($_POST['toolbar_kata'] ?? null, 'string', '');
				$toolbar_name = DevblocksPlatform::importGPC($_POST['toolbar_name'] ?? null, 'string', '');
				
				$error = null;
				
				if(empty($id)) { // New
					$fields = [
						DAO_ToolbarSection::CREATED_AT => time(),
						DAO_ToolbarSection::IS_DISABLED => $is_disabled,
						DAO_ToolbarSection::NAME => $name,
						DAO_ToolbarSection::PRIORITY => $priority,
						DAO_ToolbarSection::TOOLBAR_KATA => $toolbar_kata,
						DAO_ToolbarSection::TOOLBAR_NAME => $toolbar_name,
						DAO_ToolbarSection::UPDATED_AT => time(),
					];
					
					if(!DAO_ToolbarSection::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_ToolbarSection::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					$id = DAO_ToolbarSection::create($fields);
					DAO_ToolbarSection::onUpdateByActor($active_worker, $fields, $id);
					
					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, Context_ToolbarSection::ID, $id);
					
				} else { // Edit
					$fields = [
						DAO_ToolbarSection::IS_DISABLED => $is_disabled,
						DAO_ToolbarSection::NAME => $name,
						DAO_ToolbarSection::PRIORITY => $priority,
						DAO_ToolbarSection::TOOLBAR_KATA => $toolbar_kata,
						DAO_ToolbarSection::TOOLBAR_NAME => $toolbar_name,
						DAO_ToolbarSection::UPDATED_AT => time(),
					];
					
					if(!DAO_ToolbarSection::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_ToolbarSection::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					DAO_ToolbarSection::update($id, $fields);
					DAO_ToolbarSection::onUpdateByActor($active_worker, $fields, $id);
				}
				
				if($id) {
					// Versioning
					try {
						DAO_RecordChangeset::create(
							'toolbar_section',
							$id,
							[
								'toolbar_kata' => $fields[DAO_ToolbarSection::TOOLBAR_KATA] ?? '',
							],
							$active_worker->id ?? 0
						);
						
					} catch (Exception $e) {
						DevblocksPlatform::logError('Error saving changeset: ' . $e->getMessage());
					}
					
					// Custom field saves
					$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'] ?? null, 'array', []);
					if(!DAO_CustomFieldValue::handleFormPost(Context_ToolbarSection::ID, $id, $field_ids, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
				}
				
				echo json_encode([
					'status' => true,
					'context' => Context_ToolbarSection::ID,
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
