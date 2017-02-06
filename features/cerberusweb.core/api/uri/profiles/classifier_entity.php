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

class PageSection_ProfilesClassifierEntity extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // classifier_entity 
		$id = array_shift($stack); // 123

		@$id = intval($id);
		
		if(null == ($classifier_entity = DAO_ClassifierEntity::get($id))) {
			return;
		}
		$tpl->assign('classifier_entity', $classifier_entity);
	
		// Tab persistence
		
		$point = 'profiles.classifier_entity.tab';
		$tpl->assign('point', $point);
		
		if(null == (@$tab_selected = $stack[0])) {
			$tab_selected = $visit->get($point, '');
		}
		$tpl->assign('tab_selected', $tab_selected);
	
		// Properties
			
		$properties = array();
			
		$properties['name'] = array(
			'label' => mb_ucfirst($translate->_('common.name')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $classifier_entity->name,
		);
			
		$properties['updated'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $classifier_entity->updated_at,
		);
			
	
		// Custom Fields

		@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_CLASSIFIER_ENTITY, $classifier_entity->id)) or array();
		$tpl->assign('custom_field_values', $values);
		
		$properties_cfields = Page_Profiles::getProfilePropertiesCustomFields(CerberusContexts::CONTEXT_CLASSIFIER_ENTITY, $values);
		
		if(!empty($properties_cfields))
			$properties = array_merge($properties, $properties_cfields);
		
		// Custom Fieldsets

		$properties_custom_fieldsets = Page_Profiles::getProfilePropertiesCustomFieldsets(CerberusContexts::CONTEXT_CLASSIFIER_ENTITY, $classifier_entity->id, $values);
		$tpl->assign('properties_custom_fieldsets', $properties_custom_fieldsets);
		
		// Link counts
		
		$properties_links = array(
			CerberusContexts::CONTEXT_CLASSIFIER_ENTITY => array(
				$classifier_entity->id => 
					DAO_ContextLink::getContextLinkCounts(
						CerberusContexts::CONTEXT_CLASSIFIER_ENTITY,
						$classifier_entity->id,
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
			'event.macro.classifier_entity'
		);
		$tpl->assign('macros', $macros);

		// Tabs
		$tab_manifests = Extension_ContextProfileTab::getExtensions(false, CerberusContexts::CONTEXT_CLASSIFIER_ENTITY);
		$tpl->assign('tab_manifests', $tab_manifests);
		
		// Template
		$tpl->display('devblocks:cerberusweb.core::profiles/classifier_entity.tpl');
	}
	
	function savePeekJsonAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(!empty($id) && !empty($do_delete)) { // Delete
				DAO_ClassifierEntity::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				@$name = DevblocksPlatform::importGPC($_REQUEST['name'], 'string', '');
				@$description = DevblocksPlatform::importGPC($_REQUEST['description'], 'string', '');
				@$type = DevblocksPlatform::importGPC($_REQUEST['type'], 'string', '');
				@$params = DevblocksPlatform::importGPC($_REQUEST['params'], 'array', []);
				
				if(empty($name))
					throw new Exception_DevblocksAjaxValidationError("The 'Name' field is required.", 'name');
				
				// [TODO] Names can only contain A-Z, a-z, 0-9, and period.
				
				if($name != DevblocksPlatform::strAlphaNum($name, '.', ''))
					throw new Exception_DevblocksAjaxValidationError("Names can only contain A-Z, a-z, 0-9, and period.", 'name');
				
				if(empty($type) || !in_array($type, ['list','regexp','text']))
					throw new Exception_DevblocksAjaxValidationError("A valid 'Type' is required.", 'type');
				
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
					$id = DAO_ClassifierEntity::create($fields);
					
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
					DAO_ClassifierEntity::update($id, $fields);
					
				}
	
				// Custom fields
				@$field_ids = DevblocksPlatform::importGPC($_REQUEST['field_ids'], 'array', array());
				DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_CLASSIFIER_ENTITY, $id, $field_ids);
				
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
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&type=classifier_entity', true),
					'toolbar_extension_id' => 'cerberusweb.contexts.classifier.entity.explore.toolbar',
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $opp_id => $row) {
				if($opp_id==$explore_from)
					$orig_pos = $pos;
				
				$url = $url_writer->writeNoProxy(sprintf("c=profiles&type=classifier_entity&id=%d-%s", $row[SearchFields_ClassifierEntity::ID], DevblocksPlatform::strToPermalink($row[SearchFields_ClassifierEntity::NAME])), true);
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_ClassifierEntity::ID],
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
