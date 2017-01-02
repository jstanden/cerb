<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2014, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.io/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.io	    http://webgroup.media
***********************************************************************/

class PageSection_ProfilesClassifierClass extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // classifier_class
		$id = array_shift($stack); // 123

		@$id = intval($id);
		
		$context = CerberusContexts::CONTEXT_CLASSIFIER_CLASS;
		
		if(null == ($classifier_class = DAO_ClassifierClass::get($id))) {
			return;
		}
		$tpl->assign('classifier_class', $classifier_class);
	
		// Tab persistence
		
		$point = 'profiles.classifier_class.tab';
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
			'value' => $classifier_class->classifier_id,
			'params' => array(
				'context' => CerberusContexts::CONTEXT_CLASSIFIER,
			),
		);
			
		$properties['updated'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $classifier_class->updated_at,
		);
		
		$properties['training_count'] = array(
			'label' => mb_ucfirst($translate->_('dao.classifier_class.training_count')),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $classifier_class->training_count,
		);
		
		$properties['dictionary_size'] = array(
			'label' => mb_ucfirst($translate->_('dao.classifier.dictionary_size')),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $classifier_class->dictionary_size,
		);
			
	
		// Custom Fields

		@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds($context, $classifier_class->id)) or array();
		$tpl->assign('custom_field_values', $values);
		
		$properties_cfields = Page_Profiles::getProfilePropertiesCustomFields($context, $values);
		
		if(!empty($properties_cfields))
			$properties = array_merge($properties, $properties_cfields);
		
		// Custom Fieldsets

		$properties_custom_fieldsets = Page_Profiles::getProfilePropertiesCustomFieldsets($context, $classifier_class->id, $values);
		$tpl->assign('properties_custom_fieldsets', $properties_custom_fieldsets);
		
		// Counts
		
		$owner_counts = array(
			'examples' => DAO_ClassifierExample::countByClass($classifier_class->id),
			//'comments' => DAO_Comment::count($context, $classifier_class->id),
		);
		$tpl->assign('owner_counts', $owner_counts);
		
		// Link counts
		
		$properties_links = array(
			$context => array(
				$classifier_class->id => 
					DAO_ContextLink::getContextLinkCounts(
						$context,
						$classifier_class->id,
						array(CerberusContexts::CONTEXT_CUSTOM_FIELDSET)
					),
			),
		);
		
		$tpl->assign('properties_links', $properties_links);
		
		// Properties
		
		$tpl->assign('properties', $properties);
			
		// Macros
		
		$macros = DAO_TriggerEvent::getReadableByActor(
			$active_worker,
			'event.macro.classifier_class'
		);
		$tpl->assign('macros', $macros);

		// Tabs
		$tab_manifests = Extension_ContextProfileTab::getExtensions(false, CerberusContexts::CONTEXT_CLASSIFIER_CLASS);
		$tpl->assign('tab_manifests', $tab_manifests);
		
		// Template
		$tpl->display('devblocks:cerberusweb.core::profiles/classifier_class.tpl');
	}
	
	function savePeekJsonAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(!empty($id) && !empty($do_delete)) { // Delete
				// [TODO] Check ACL
				DAO_ClassifierClass::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				@$classifier_id = DevblocksPlatform::importGPC($_REQUEST['classifier_id'], 'integer', 0);
				@$name = DevblocksPlatform::importGPC($_REQUEST['name'], 'string', '');
				
				if(empty($name))
					throw new Exception_DevblocksAjaxValidationError("The 'Name' field is required.", 'name');
				
				// [TODO] Attribs
				
				if(empty($id)) { // New
					if(empty($classifier_id))
						throw new Exception_DevblocksAjaxValidationError("The 'Classifier' field is required.", 'classifier_id');
					
					$fields = array(
						DAO_ClassifierClass::CLASSIFIER_ID => $classifier_id,
						DAO_ClassifierClass::DICTIONARY_SIZE => 0,
						DAO_ClassifierClass::NAME => $name,
						DAO_ClassifierClass::SLOTS_JSON => json_encode([]),
						DAO_ClassifierClass::TRAINING_COUNT => 0,
						DAO_ClassifierClass::UPDATED_AT => time(),
					);
					$id = DAO_ClassifierClass::create($fields);
					
					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_CLASSIFIER_CLASS, $id);
					
				} else { // Edit
					$fields = array(
						DAO_ClassifierClass::NAME => $name,
						DAO_ClassifierClass::SLOTS_JSON => json_encode([]),
						DAO_ClassifierClass::UPDATED_AT => time(),
					);
					DAO_ClassifierClass::update($id, $fields);
					
				}
	
				// Custom fields
				@$field_ids = DevblocksPlatform::importGPC($_REQUEST['field_ids'], 'array', array());
				DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_CLASSIFIER_CLASS, $id, $field_ids);
				
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
	
	function viewExploreAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$url_writer = DevblocksPlatform::getUrlService();
		
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
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&type=classifier_class', true),
					'toolbar_extension_id' => 'cerberusweb.contexts.classifier.class.explore.toolbar',
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $opp_id => $row) {
				if($opp_id==$explore_from)
					$orig_pos = $pos;
				
				$url = $url_writer->writeNoProxy(sprintf("c=profiles&type=classifier_class&id=%d-%s", $row[SearchFields_ClassifierClass::ID], DevblocksPlatform::strToPermalink($row[SearchFields_ClassifierClass::NAME])), true);
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_ClassifierClass::ID],
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
