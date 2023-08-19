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

class PageSection_ProfilesClassifierEntity extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // classifier_entity 
		@$context_id = intval(array_shift($stack)); // 123

		$context = CerberusContexts::CONTEXT_CLASSIFIER_ENTITY;
		
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
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_CLASSIFIER_ENTITY)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				if(false == ($model = DAO_ClassifierEntity::get($id)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.record.not_found'));
				
				if(!Context_ClassifierEntity::isDeletableByActor($model, $active_worker))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				CerberusContexts::logActivityRecordDelete(CerberusContexts::CONTEXT_CLASSIFIER_ENTITY, $model->id, $model->name);
				
				DAO_ClassifierEntity::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				$name = DevblocksPlatform::importGPC($_POST['name'] ?? null, 'string', '');
				$description = DevblocksPlatform::importGPC($_POST['description'] ?? null, 'string', '');
				$type = DevblocksPlatform::importGPC($_POST['type'] ?? null, 'string', '');
				$params = DevblocksPlatform::importGPC($_POST['params'] ?? null, 'array', []);
				
				// Validate types
				switch($type) {
					case 'list':
						@$labels = DevblocksPlatform::importVar($params[$type]['labels'], 'string', '');
						$lines = DevblocksPlatform::parseCrlfString($labels);
						$map = [];
						
						foreach($lines as $line) {
							$data = DevblocksPlatform::parseCsvString($line, false, null, 2);
							
							if(empty($data) || !is_array($data))
								continue;
							
							$key = $data[0];
							
							// If we only had a key, use the same ref for the value
							if(1 == count($data))
								$data[] = $key;
							
							if(!isset($map[$key]))
								$map[$key] = [];
							
							$map[$key][] = $data[1];
						}
						
						$params[$type]['map'] = $map;
						$params = $params[$type];
						break;
					
					case 'regexp':
						@$pattern = DevblocksPlatform::importVar($params[$type]['pattern'], 'string', '');
						
						if(empty($pattern))
							throw new Exception_DevblocksAjaxValidationError("A regular expression pattern is required.");
						
						if(false === @preg_match($pattern, null))
							throw new Exception_DevblocksAjaxValidationError("Invalid regular expression pattern.");
						
						$params = $params[$type];
						break;
					
					case 'text':
						$params = $params[$type];
						break;
				}
				
				if(empty($id)) { // New
					$fields = array(
						DAO_ClassifierEntity::DESCRIPTION => $description,
						DAO_ClassifierEntity::NAME => $name,
						DAO_ClassifierEntity::PARAMS_JSON => json_encode($params),
						DAO_ClassifierEntity::TYPE => $type,
						DAO_ClassifierEntity::UPDATED_AT => time(),
					);
					
					if(!DAO_ClassifierEntity::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_ClassifierEntity::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					$id = DAO_ClassifierEntity::create($fields);
					DAO_ClassifierEntity::onUpdateByActor($active_worker, $fields, $id);
					
					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_CLASSIFIER_ENTITY, $id);
					
				} else { // Edit
					$fields = array(
						DAO_ClassifierEntity::DESCRIPTION => $description,
						DAO_ClassifierEntity::NAME => $name,
						DAO_ClassifierEntity::PARAMS_JSON => json_encode($params),
						DAO_ClassifierEntity::TYPE => $type,
						DAO_ClassifierEntity::UPDATED_AT => time(),
					);
					
					if(!DAO_ClassifierEntity::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_ClassifierEntity::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					DAO_ClassifierEntity::update($id, $fields);
					DAO_ClassifierEntity::onUpdateByActor($active_worker, $fields, $id);
				}
				
				// Custom field saves
				$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'] ?? null, 'array', []);
				if(!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_CLASSIFIER_ENTITY, $id, $field_ids, $error))
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
