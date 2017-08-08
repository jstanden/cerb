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

class PageSection_ProfilesClassifierExample extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::services()->template();
		$visit = CerberusApplication::getVisit();
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // classifier_example 
		$id = array_shift($stack); // 123

		@$id = intval($id);
		
		if(null == ($model = DAO_ClassifierExample::get($id))) {
			return;
		}
		$tpl->assign('model', $model);
	
		// Tab persistence
		
		$point = 'profiles.classifier_example.tab';
		$tpl->assign('point', $point);
		
		if(null == (@$tab_selected = $stack[0])) {
			$tab_selected = $visit->get($point, '');
		}
		$tpl->assign('tab_selected', $tab_selected);
	
		// Properties
			
		$properties = array();
			
		$properties['classifier_id'] = array(
			'label' => mb_ucfirst($translate->_('common.classifier')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->classifier_id,
			'params' => [
				'context' => CerberusContexts::CONTEXT_CLASSIFIER,
			],
		);
		
		$properties['class_id'] = array(
			'label' => mb_ucfirst($translate->_('common.classifier.classification')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->class_id,
			'params' => [
				'context' => CerberusContexts::CONTEXT_CLASSIFIER_CLASS,
			],
		);
		
		$properties['updated'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->updated_at,
		);
	
		// Custom Fields

		@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_CLASSIFIER_EXAMPLE, $model->id)) or array();
		$tpl->assign('custom_field_values', $values);
		
		$properties_cfields = Page_Profiles::getProfilePropertiesCustomFields(CerberusContexts::CONTEXT_CLASSIFIER_EXAMPLE, $values);
		
		if(!empty($properties_cfields))
			$properties = array_merge($properties, $properties_cfields);
		
		// Custom Fieldsets

		$properties_custom_fieldsets = Page_Profiles::getProfilePropertiesCustomFieldsets(CerberusContexts::CONTEXT_CLASSIFIER_EXAMPLE, $model->id, $values);
		$tpl->assign('properties_custom_fieldsets', $properties_custom_fieldsets);
		
		// Link counts
		
		$properties_links = array(
			CerberusContexts::CONTEXT_CLASSIFIER_EXAMPLE => array(
				$model->id => 
					DAO_ContextLink::getContextLinkCounts(
						CerberusContexts::CONTEXT_CLASSIFIER_EXAMPLE,
						$model->id,
						array(CerberusContexts::CONTEXT_CUSTOM_FIELDSET)
					),
			),
		);
		
		$tpl->assign('properties_links', $properties_links);
		
		// Properties
		
		$tpl->assign('properties', $properties);
			
		// Tabs
		$tab_manifests = Extension_ContextProfileTab::getExtensions(false, CerberusContexts::CONTEXT_CLASSIFIER_EXAMPLE);
		$tpl->assign('tab_manifests', $tab_manifests);
		
		// Template
		$tpl->display('devblocks:cerberusweb.core::profiles/classifier_example.tpl');
	}
	
	function savePeekJsonAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$bayes = DevblocksPlatform::services()->bayesClassifier();
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(false == ($example = DAO_ClassifierExample::get($id)))
					throw new Exception_DevblocksAjaxValidationError("The record does not exist.");
				
				if(!Context_ClassifierExample::isWriteableByActor($example, $active_worker))
					throw new Exception_DevblocksAjaxValidationError("You do not have permission to delete this record.");
				
				DAO_ClassifierExample::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				@$expression = DevblocksPlatform::importGPC($_REQUEST['expression'], 'string', '');
				@$classifier_id = DevblocksPlatform::importGPC($_REQUEST['classifier_id'], 'integer', 0);
				@$class_id = DevblocksPlatform::importGPC($_REQUEST['class_id'], 'integer', 0);
				
				if(empty($classifier_id))
					throw new Exception_DevblocksAjaxValidationError("The 'Classifier' field is required.", 'classifier_id');
				
				if(false == ($classifier = DAO_Classifier::get($classifier_id)))
					throw new Exception_DevblocksAjaxValidationError("The 'Classifier' is invalid.", 'classifier_id');
				
				if($class_id && false == ($class = DAO_ClassifierClass::get($class_id)))
					throw new Exception_DevblocksAjaxValidationError("The 'Classification' is invalid.", 'class_id');
				
				// Verify that the class_id belongs to the classifier_id
				if($class_id && $class->classifier_id != $classifier->id)
					throw new Exception_DevblocksAjaxValidationError("The 'Classification' doesn't belong to the given 'Classifier'.", 'class_id');
				
				if(!Context_Classifier::isWriteableByActor($classifier, $active_worker))
					throw new Exception_DevblocksAjaxValidationError("You do not have permission to modify this record.", 'classifier_id');
				
				if(empty($expression))
					throw new Exception_DevblocksAjaxValidationError("The 'Expression' field is required.", 'expression');
				
				$fields = array(
					DAO_ClassifierExample::EXPRESSION => $expression,
					DAO_ClassifierExample::CLASSIFIER_ID => $classifier_id,
					DAO_ClassifierExample::CLASS_ID => $class_id,
					DAO_ClassifierExample::UPDATED_AT => time(),
				);
				
				if(empty($id)) { // New
					$id = DAO_ClassifierExample::create($fields);
					
					// Train the model (online learning)
					if($class_id)
						$bayes::train($expression, $classifier_id, $class_id);
					
					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_CLASSIFIER_EXAMPLE, $id);
					
				} else { // Edit
					DAO_ClassifierExample::update($id, $fields);
				}
	
				// Custom fields
				@$field_ids = DevblocksPlatform::importGPC($_REQUEST['field_ids'], 'array', array());
				DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_CLASSIFIER_EXAMPLE, $id, $field_ids);
				
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
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&type=classifier_example', true),
					'toolbar_extension_id' => 'cerberusweb.contexts.classifier.example.explore.toolbar',
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $opp_id => $row) {
				if($opp_id==$explore_from)
					$orig_pos = $pos;
				
				$url = $url_writer->writeNoProxy(sprintf("c=profiles&type=classifier_example&id=%d-%s", $row[SearchFields_ClassifierExample::ID], DevblocksPlatform::strToPermalink($row[SearchFields_ClassifierExample::EXPRESSION])), true);
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_ClassifierExample::ID],
					'url' => $url,
				);
				$models[] = $model;
			}
			
			DAO_ExplorerSet::createFromModels($models);
			
			$view->renderPage++;
			
		} while(!empty($results));
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('explore',$hash,$orig_pos)));
	}
};
