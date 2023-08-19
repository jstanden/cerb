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

class PageSection_ProfilesClassifier extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // classifier
		@$context_id = intval(array_shift($stack)); // 123

		$context = CerberusContexts::CONTEXT_CLASSIFIER;
		
		Page_Profiles::renderProfile($context, $context_id, $stack);
	}
	
	function handleActionForPage(string $action, string $scope=null) {
		if('profileAction' == $scope) {
			switch ($action) {
				case 'predict':
					return $this->_profileAction_predict();
				case 'showImportPopup':
					return $this->_profileAction_showImportPopup();
				case 'saveImportPopupJson':
					return $this->_profileAction_saveImportPopupJson();
				case 'savePeekJson':
					return $this->_profileAction_savePeekJson();
				case 'viewExplore':
					return $this->_profileAction_viewExplore();
			}
		}
		return false;
	}
	
	private function _profileAction_savePeekJson() {
		$id = DevblocksPlatform::importGPC($_POST['id'] ?? null, 'integer', 0);
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string', '');
		$do_delete = DevblocksPlatform::importGPC($_POST['do_delete'] ?? null, 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_CLASSIFIER)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				if(false == ($model = DAO_Classifier::get($id)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.record.not_found'));
				
				if(!Context_Classifier::isDeletableByActor($model, $active_worker))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				CerberusContexts::logActivityRecordDelete(CerberusContexts::CONTEXT_CLASSIFIER, $model->id, $model->name);
				
				DAO_Classifier::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				$name = DevblocksPlatform::importGPC($_POST['name'] ?? null, 'string', '');
				list($owner_context, $owner_context_id) = array_pad(explode(':', DevblocksPlatform::importGPC($_POST['owner'], 'string', '')), 2, null);
				$do_retrain = DevblocksPlatform::importGPC($_POST['do_retrain'] ?? null, 'integer', 0);
				
				if(empty($owner_context) || false == Extension_DevblocksContext::get($owner_context))
					throw new Exception_DevblocksAjaxValidationError("The 'Owner' field is required.");
				
				if(empty($id)) { // New
					$fields = array(
						DAO_Classifier::CREATED_AT => time(),
						DAO_Classifier::UPDATED_AT => time(),
						DAO_Classifier::NAME => $name,
						DAO_Classifier::OWNER_CONTEXT => $owner_context,
						DAO_Classifier::OWNER_CONTEXT_ID => $owner_context_id,
						DAO_Classifier::PARAMS_JSON => json_encode([]),
					);
					
					if(!DAO_Classifier::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_Classifier::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(false == ($id = DAO_Classifier::create($fields)))
						throw new Exception_DevblocksAjaxValidationError("Failed to create the record.");
					
					DAO_Classifier::onUpdateByActor($active_worker, $fields, $id);
					
					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_CLASSIFIER, $id);
					
				} else { // Edit
					$fields = array(
						DAO_Classifier::UPDATED_AT => time(),
						DAO_Classifier::NAME => $name,
						DAO_Classifier::OWNER_CONTEXT => $owner_context,
						DAO_Classifier::OWNER_CONTEXT_ID => $owner_context_id,
					);
					
					if(!DAO_Classifier::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_Classifier::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					DAO_Classifier::update($id, $fields);
					DAO_Classifier::onUpdateByActor($active_worker, $fields, $id);
				}
				
				// Custom field saves
				$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'] ?? null, 'array', []);
				if(!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_CLASSIFIER, $id, $field_ids, $error))
					throw new Exception_DevblocksAjaxValidationError($error);
				
				if($do_retrain) {
					if(false != ($classifier = DAO_Classifier::get($id))) {
						$classifier->trainModel();
					}
				}
				
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
	
	private function _profileAction_showImportPopup() {
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl = DevblocksPlatform::services()->template();
		
		$classifier_id = DevblocksPlatform::importGPC($_REQUEST['classifier_id'] ?? null, 'integer', 0);
		
		if(!$classifier_id || false == ($classifier = DAO_Classifier::get($classifier_id))) {
			$tpl->assign('error_message', DevblocksPlatform::translate('error.core.record.not_found'));
			$tpl->display('devblocks:cerberusweb.core::internal/peek/peek_error.tpl');
			return;
		}
		
		if(!Context_Classifier::isWriteableByActor($classifier, $active_worker)) {
			$tpl->assign('error_message', DevblocksPlatform::translate('common.access_denied'));
			$tpl->display('devblocks:cerberusweb.core::internal/peek/peek_error.tpl');
			return;
		}
		
		$tpl->assign('classifier_id', $classifier_id);
		
		$tpl->display('devblocks:cerberusweb.core::internal/classifier/import_popup.tpl');
	}
	
	private function _profileAction_saveImportPopupJson() {
		$active_worker = CerberusApplication::getActiveWorker();
		$bayes = DevblocksPlatform::services()->bayesClassifier();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$classifier_id = DevblocksPlatform::importGPC($_POST['classifier_id'] ?? null, 'integer', 0);
		$examples_csv = DevblocksPlatform::importGPC($_POST['examples_csv'] ?? null, 'string', null);
		
		header('Content-Type: application/json');
		
		if(!$classifier_id || false == ($classifier = DAO_Classifier::get($classifier_id))) {
			echo json_encode([
				'status' => false,
				'error' => DevblocksPlatform::translate('error.core.record.not_found'),
			]);
			return;
		}
		
		if(!Context_Classifier::isWriteableByActor($classifier, $active_worker)) {
			echo json_encode([
				'status' => false,
				'error' => DevblocksPlatform::translate('common.access_denied'),
			]);
			return;
		}
		
		$examples = DevblocksPlatform::parseCrlfString($examples_csv);
		
		// Error if examples are blank
		if(empty($examples) || !is_array($examples)) {
			echo json_encode([
				'status' => false,
				'error' => "No examples were given.",
			]);
			return;
		}
		
		$classifications = DAO_ClassifierClass::getByClassifierId($classifier_id);
		$class_name_to_id = array_column($classifications, 'id', 'name');
		
		// Verify the format of the examples
		foreach($examples as $example) {
			if(empty($example))
				continue;
			
			$data = DevblocksPlatform::parseCsvString($example, false, null, 2);
			
			if(empty($data) || !is_array($data) || count($data) != 2) {
				echo json_encode([
					'status' => false,
					'error' => sprintf("Invalid training data: %s", DevblocksPlatform::strEscapeHtml($example)),
				]);
				return;
			}
			
			$classification = trim($data[0], '"');
			$expression = trim($data[1], '"');
			
			if(!$bayes::verify($expression)) {
				echo json_encode([
					'status' => false,
					'error' => sprintf("Invalid training data: %s", DevblocksPlatform::strEscapeHtml($example)),
				]);
				return;
			}
			
			// If the classification doesn't exist we need to create it
			if(!isset($class_name_to_id[$classification])) {
				$class_id = DAO_ClassifierClass::create([
					DAO_ClassifierClass::CLASSIFIER_ID => $classifier_id,
					DAO_ClassifierClass::NAME => $classification,
					DAO_ClassifierClass::UPDATED_AT => time()
				]);
				
				$class_name_to_id[$classification] = $class_id;
				
			} else {
				$class_id = $class_name_to_id[$classification];
			}
			
			DAO_ClassifierExample::create([
				DAO_ClassifierExample::CLASS_ID => $class_id,
				DAO_ClassifierExample::CLASSIFIER_ID => $classifier_id,
				DAO_ClassifierExample::EXPRESSION => $expression,
				DAO_ClassifierExample::UPDATED_AT => time(),
			]);
			
			$bayes::train($expression, $classifier_id, $class_id, true);
		}
		
		// Update the model
		$bayes::build($classifier_id);
		
		echo json_encode([
			'status' => true,
		]);
	}
	
	private function _profileAction_viewExplore() {
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string', '');
		$explore_from = DevblocksPlatform::importGPC($_POST['explore_from'] ?? null, 'int', 0);
		
		$http_response = Cerb_ORMHelper::generateRecordExploreSet($view_id, $explore_from);
		DevblocksPlatform::redirect($http_response);
	}
	
	private function _profileAction_predict() {
		$bayes = DevblocksPlatform::services()->bayesClassifier();
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$classifier_id = DevblocksPlatform::importGPC($_POST['classifier_id'] ?? null, 'integer', 0);
		$text = DevblocksPlatform::importGPC($_POST['text'] ?? null, 'string', '');
		
		$environment = [
			'me' => [
				'context' => CerberusContexts::CONTEXT_WORKER,
				'id' => $active_worker->id,
				'model' => $active_worker
			],
			'lang' => 'en_US',
			'timezone' => '',
		];
		
		$prediction = $bayes::predict($text, $classifier_id, $environment);
		$tpl->assign('prediction', $prediction['prediction']);
		
		$is_writeable = Context_Classifier::isWriteableByActor($classifier_id, $active_worker);
		$tpl->assign('is_writeable', $is_writeable);
		
		$tpl->display('devblocks:cerberusweb.core::internal/classifier/prediction.tpl');
	}
};
