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

class PageSection_ProfilesClassifierExample extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // classifier_example 
		@$context_id = intval(array_shift($stack)); // 123

		$context = CerberusContexts::CONTEXT_CLASSIFIER_EXAMPLE;
		
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
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_CLASSIFIER_EXAMPLE)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				if(false == ($model = DAO_ClassifierExample::get($id)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.record.not_found'));
				
				if(!Context_ClassifierExample::isDeletableByActor($model, $active_worker))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				DAO_ClassifierExample::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				$expression = DevblocksPlatform::importGPC($_POST['expression'] ?? null, 'string', '');
				$classifier_id = DevblocksPlatform::importGPC($_POST['classifier_id'] ?? null, 'integer', 0);
				$class_id = DevblocksPlatform::importGPC($_POST['class_id'] ?? null, 'integer', 0);
				
				if(false == ($classifier = DAO_Classifier::get($classifier_id)))
					throw new Exception_DevblocksAjaxValidationError("The 'Classifier' is invalid.", 'classifier_id');
				
				if($class_id && false == ($class = DAO_ClassifierClass::get($class_id)))
					throw new Exception_DevblocksAjaxValidationError("The 'Classification' is invalid.", 'class_id');
				
				// Verify that the class_id belongs to the classifier_id
				if($class_id && $class->classifier_id != $classifier->id)
					throw new Exception_DevblocksAjaxValidationError("The 'Classification' doesn't belong to the given 'Classifier'.", 'class_id');
				
				if(!Context_Classifier::isWriteableByActor($classifier, $active_worker))
					throw new Exception_DevblocksAjaxValidationError("You do not have permission to modify this record.", 'classifier_id');
				
				$fields = array(
					DAO_ClassifierExample::EXPRESSION => $expression,
					DAO_ClassifierExample::CLASSIFIER_ID => $classifier_id,
					DAO_ClassifierExample::CLASS_ID => $class_id,
					DAO_ClassifierExample::UPDATED_AT => time(),
				);
				
				if(empty($id)) { // New
					if(!DAO_ClassifierExample::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_ClassifierExample::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					$id = DAO_ClassifierExample::create($fields);
					DAO_ClassifierExample::onUpdateByActor($active_worker, $fields, $id);
					
					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_CLASSIFIER_EXAMPLE, $id);
					
				} else { // Edit
					if(!DAO_ClassifierExample::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_ClassifierExample::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					DAO_ClassifierExample::update($id, $fields);
					DAO_ClassifierExample::onUpdateByActor($active_worker, $fields, $id);
				}
				
				// Custom field saves
				$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'] ?? null, 'array', []);
				if(!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_CLASSIFIER_EXAMPLE, $id, $field_ids, $error))
					throw new Exception_DevblocksAjaxValidationError($error);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'label' => $expression,
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
