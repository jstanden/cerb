<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2014, Webgroup Media LLC
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
		$tpl = DevblocksPlatform::services()->template();
		$visit = CerberusApplication::getVisit();
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // classifier
		$id = array_shift($stack); // 123

		@$id = intval($id);
		
		if(null == ($classifier = DAO_Classifier::get($id))) {
			return;
		}
		$tpl->assign('classifier', $classifier);
	
		// Tab persistence
		
		$point = 'profiles.classifier.tab';
		$tpl->assign('point', $point);
		
		if(null == (@$tab_selected = $stack[0])) {
			$tab_selected = $visit->get($point, '');
		}
		$tpl->assign('tab_selected', $tab_selected);
	
		// Properties
			
		$properties = array();
			
		$properties['owner'] = array(
			'label' => mb_ucfirst($translate->_('common.owner')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $classifier->owner_context_id,
			'params' => [
				'context' => $classifier->owner_context,
			]
		);
		
		$properties['created'] = array(
			'label' => mb_ucfirst($translate->_('common.created')),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $classifier->created_at,
		);
		
		$properties['updated'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $classifier->updated_at,
		);
	
		// Custom Fields

		@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_CLASSIFIER, $classifier->id)) or array();
		$tpl->assign('custom_field_values', $values);
		
		$properties_cfields = Page_Profiles::getProfilePropertiesCustomFields(CerberusContexts::CONTEXT_CLASSIFIER, $values);
		
		if(!empty($properties_cfields))
			$properties = array_merge($properties, $properties_cfields);
		
		// Custom Fieldsets

		$properties_custom_fieldsets = Page_Profiles::getProfilePropertiesCustomFieldsets(CerberusContexts::CONTEXT_CLASSIFIER, $classifier->id, $values);
		$tpl->assign('properties_custom_fieldsets', $properties_custom_fieldsets);
		
		// Counts
		
		$owner_counts = array(
			'classes' => DAO_ClassifierClass::count($classifier->id),
			'examples' => DAO_ClassifierExample::countByClassifier($classifier->id),
			//'comments' => DAO_Comment::count($context, $classifier->id),
		);
		$tpl->assign('owner_counts', $owner_counts);
		
		// Link counts
		
		$properties_links = array(
			CerberusContexts::CONTEXT_CLASSIFIER => array(
				$classifier->id => 
					DAO_ContextLink::getContextLinkCounts(
						CerberusContexts::CONTEXT_CLASSIFIER,
						$classifier->id,
						array(CerberusContexts::CONTEXT_CUSTOM_FIELDSET)
					),
			),
		);
		
		$tpl->assign('properties_links', $properties_links);
		
		// Properties
		
		$tpl->assign('properties', $properties);
			
		// Tabs
		$tab_manifests = Extension_ContextProfileTab::getExtensions(false, CerberusContexts::CONTEXT_CLASSIFIER);
		$tpl->assign('tab_manifests', $tab_manifests);
		
		// Template
		$tpl->display('devblocks:cerberusweb.core::profiles/classifier.tpl');
	}
	
	function savePeekJsonAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
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
				@$name = DevblocksPlatform::importGPC($_REQUEST['name'], 'string', '');
				@list($owner_context, $owner_context_id) = explode(':', DevblocksPlatform::importGPC($_REQUEST['owner'], 'string', ''));
				@$do_retrain = DevblocksPlatform::importGPC($_REQUEST['do_retrain'], 'integer', 0);
				
				if(empty($owner_context) || false == Extension_DevblocksContext::get($owner_context))
					throw new Exception_DevblocksAjaxValidationError("The 'Owner' field is required.");
				
				if(!CerberusContexts::isOwnableBy($owner_context, $owner_context_id, $active_worker))
					throw new Exception_DevblocksAjaxValidationError("You don't have permission to use this owner.", 'owner');
				
				if(empty($id)) { // New
					if(!$active_worker->hasPriv(sprintf("contexts.%s.create", CerberusContexts::CONTEXT_CLASSIFIER)))
						throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.create'));
					
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
					
					if(false == ($id = DAO_Classifier::create($fields)))
						throw new Exception_DevblocksAjaxValidationError("Failed to create the record.");
					
					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_CLASSIFIER, $id);
					
				} else { // Edit
					if(!$active_worker->hasPriv(sprintf("contexts.%s.update", CerberusContexts::CONTEXT_CLASSIFIER)))
						throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.edit'));
					
					$fields = array(
						DAO_Classifier::UPDATED_AT => time(),
						DAO_Classifier::NAME => $name,
						DAO_Classifier::OWNER_CONTEXT => $owner_context,
						DAO_Classifier::OWNER_CONTEXT_ID => $owner_context_id,
					);
					
					if(!DAO_Classifier::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					DAO_Classifier::update($id, $fields);
				}
	
				// Custom fields
				@$field_ids = DevblocksPlatform::importGPC($_REQUEST['field_ids'], 'array', array());
				DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_CLASSIFIER, $id, $field_ids);
				
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
		
		if(!$classifier_id || false == ($classifier = DAO_Classifier::get($classifier_id))) {
			echo "Invalid classifier.";
			return;
		}
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('classifier_id', $classifier_id);
		
		$tpl->display('devblocks:cerberusweb.core::internal/classifier/import_popup.tpl');
	}
	
	function saveImportPopupJsonAction() {
		@$classifier_id = DevblocksPlatform::importGPC($_REQUEST['classifier_id'], 'integer', 0);
		@$examples_csv = DevblocksPlatform::importGPC($_REQUEST['examples_csv'], 'string', null);
		
		$bayes = DevblocksPlatform::services()->bayesClassifier();
		
		header('Content-Type: application/json');
		
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
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$url_writer = DevblocksPlatform::services()->url();
		
		// Generate hash
		$hash = md5($view_id.$active_worker->id.time());
		
		// Loop through view and get IDs
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);

		// Page start
		@$explore_from = DevblocksPlatform::importGPC($_REQUEST['explore_from'],'integer',0);
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
					'toolbar_extension_id' => 'cerberusweb.contexts.classifier.explore.toolbar',
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
		@$classifier_id = DevblocksPlatform::importGPC($_REQUEST['classifier_id'], 'integer', 0);
		@$text = DevblocksPlatform::importGPC($_REQUEST['text'], 'string', '');
		
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
		
		$tpl->display('devblocks:cerberusweb.core::internal/classifier/prediction.tpl');
	}
};
