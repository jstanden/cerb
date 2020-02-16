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
	
	function savePeekJsonAction() {
		@$id = DevblocksPlatform::importGPC($_POST['id'], 'integer', 0);
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'], 'string', '');
		@$do_delete = DevblocksPlatform::importGPC($_POST['do_delete'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(403);
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_CLASSIFIER)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				DAO_Classifier::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				@$name = DevblocksPlatform::importGPC($_POST['name'], 'string', '');
				@list($owner_context, $owner_context_id) = explode(':', DevblocksPlatform::importGPC($_POST['owner'], 'string', ''));
				@$do_retrain = DevblocksPlatform::importGPC($_POST['do_retrain'], 'integer', 0);
				
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
				@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', []);
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
	
	function showImportPopupAction() {
		@$classifier_id = DevblocksPlatform::importGPC($_REQUEST['classifier_id'], 'integer', 0);
		
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl = DevblocksPlatform::services()->template();
		
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
	
	function saveImportPopupJsonAction() {
		@$classifier_id = DevblocksPlatform::importGPC($_POST['classifier_id'], 'integer', 0);
		@$examples_csv = DevblocksPlatform::importGPC($_POST['examples_csv'], 'string', null);
		
		$active_worker = CerberusApplication::getActiveWorker();
		$bayes = DevblocksPlatform::services()->bayesClassifier();
		
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
	
	function viewExploreAction() {
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$url_writer = DevblocksPlatform::services()->url();
		
		// Generate hash
		$hash = md5($view_id.$active_worker->id.time());
		
		// Loop through view and get IDs
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);

		// Page start
		@$explore_from = DevblocksPlatform::importGPC($_POST['explore_from'],'integer',0);
		if(empty($explore_from)) {
			$orig_pos = 1+($view->renderPage * $view->renderLimit);
		} else {
			$orig_pos = 1;
		}

		$view->renderPage = 0;
		$view->renderLimit = 250;
		$pos = 0;
		
		do {
			$models = array();
			list($results, $total) = $view->getData();

			// Summary row
			if(0==$view->renderPage) {
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'title' => $view->name,
					'created' => time(),
//					'worker_id' => $active_worker->id,
					'total' => $total,
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&type=classifier', true),
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $opp_id => $row) {
				if($opp_id==$explore_from)
					$orig_pos = $pos;
				
				$url = $url_writer->writeNoProxy(sprintf("c=profiles&type=classifier&id=%d-%s", $row[SearchFields_Classifier::ID], DevblocksPlatform::strToPermalink($row[SearchFields_Classifier::NAME])), true);
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_Classifier::ID],
					'url' => $url,
				);
				$models[] = $model;
			}
			
			DAO_ExplorerSet::createFromModels($models);
			
			$view->renderPage++;
			
		} while(!empty($results));
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('explore',$hash,$orig_pos)));
	}
	
	function predictAction() {
		@$classifier_id = DevblocksPlatform::importGPC($_POST['classifier_id'], 'integer', 0);
		@$text = DevblocksPlatform::importGPC($_POST['text'], 'string', '');
		
		$bayes = DevblocksPlatform::services()->bayesClassifier();
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$environment = [
			'me' => ['context' => CerberusContexts::CONTEXT_WORKER, 'id' => $active_worker->id, 'model' => $active_worker],
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
