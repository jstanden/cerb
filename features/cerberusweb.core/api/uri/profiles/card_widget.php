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

class PageSection_ProfilesCardWidget extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // card_widget
		@$context_id = intval(array_shift($stack)); // 123
		
		$context = CerberusContexts::CONTEXT_CARD_WIDGET;
		
		Page_Profiles::renderProfile($context, $context_id, $stack);
	}
	
	function savePeekJsonAction() {
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'], 'string', '');
		
		@$id = DevblocksPlatform::importGPC($_POST['id'], 'integer', 0);
		@$do_delete = DevblocksPlatform::importGPC($_POST['do_delete'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(403);
		
		if(!$active_worker->is_superuser)
			return;
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_CARD_WIDGET)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				DAO_CardWidget::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				@$import_json = DevblocksPlatform::importGPC($_POST['import_json'], 'string', '');
				@$package_uri = DevblocksPlatform::importGPC($_POST['package'], 'string', '');
				
				$mode = 'build';
				
				if(!$id && $import_json) {
					$mode = 'import';
					
				} elseif (!$id && $package_uri) {
					$mode = 'library';
				}
				
				switch($mode) {
					case 'library':
						@$record_type = DevblocksPlatform::importGPC($_POST['record_type'], 'string', '');
						@$prompts = DevblocksPlatform::importGPC($_POST['prompts'], 'array', []);
						
						if(empty($package_uri))
							throw new Exception_DevblocksAjaxValidationError("You must select a package from the library.");
						
						// Verify worker can edit this card (is admin)
						if(!$active_worker->is_superuser)
							throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.admin'));
						
						if(false == ($package = DAO_PackageLibrary::getByUri($package_uri)))
							throw new Exception_DevblocksAjaxValidationError("You selected an invalid package.");
						
						if($package->point != 'card_widget' && !DevblocksPlatform::strStartsWith($package->point, 'card_widget:'))
							throw new Exception_DevblocksAjaxValidationError("The selected package is not for this extension point.");
						
						$package_json = $package->getPackageJson();
						$records_created = [];
						$prompts['prompt_record_type'] = $record_type;
						
						try {
							CerberusApplication::packages()->import($package_json, $prompts, $records_created);
							
						} catch(Exception_DevblocksValidationError $e) {
							throw new Exception_DevblocksAjaxValidationError($e->getMessage());
							
						} catch (Exception $e) {
							throw new Exception_DevblocksAjaxValidationError("An unexpected error occurred.");
						}
						
						if(!array_key_exists(Context_CardWidget::ID, $records_created))
							throw new Exception_DevblocksAjaxValidationError("There was an issue creating the record.");
						
						$new_widget = reset($records_created[Context_CardWidget::ID]);
						
						if($view_id)
							C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_CARD_WIDGET, $new_widget['id']);
						
						echo json_encode([
							'status' => true,
							'id' => $new_widget['id'],
							'label' => $new_widget['label'],
							'view_id' => $view_id,
						]);
						return;
						break;
					
					case 'import':
						@$import_json = DevblocksPlatform::importGPC($_POST['import_json'], 'string', '');
						
						$error = null;
						
						if(
							empty($import_json)
							|| false == (@$widget_json = json_decode($import_json, true))
						)
							throw new Exception_DevblocksAjaxValidationError("Invalid JSON.");
						
						if(!isset($widget_json['widget']['extension_id']))
							throw new Exception_DevblocksAjaxValidationError("JSON doesn't contain widget extension info");
						
						if(!isset($widget_json['widget']['extension_params']))
							throw new Exception_DevblocksAjaxValidationError("JSON doesn't contain widget params");
						
						@$name = $widget_json['widget']['name'] ?: 'New widget';
						@$record_type = $widget_json['widget']['record_type'];
						@$extension_id = $widget_json['widget']['extension_id'];
						
						if(empty($extension_id) || null == ($extension = Extension_CardWidget::get($extension_id)))
							throw new Exception_DevblocksAjaxValidationError("Invalid widget extension");
						
						$fields = [
							DAO_CardWidget::NAME => $name,
							DAO_CardWidget::RECORD_TYPE => $record_type,
							DAO_CardWidget::EXTENSION_ID => $extension_id,
							DAO_CardWidget::EXTENSION_PARAMS_JSON => json_encode($widget_json['widget']['extension_params']),
							DAO_CardWidget::POS => @$widget_json['widget']['pos'] ?: 0,
							DAO_CardWidget::WIDTH_UNITS => @$widget_json['widget']['width_units'] ?: 4,
							DAO_CardWidget::ZONE => @$widget_json['widget']['zone'] ?: '',
						];
						
						if(!DAO_CardWidget::validate($fields, $error))
							throw new Exception_DevblocksAjaxValidationError($error);
						
						if(!DAO_CardWidget::onBeforeUpdateByActor($active_worker, $fields, null, $error))
							throw new Exception_DevblocksAjaxValidationError($error);
						
						$id = DAO_CardWidget::create($fields);
						DAO_CardWidget::onUpdateByActor($active_worker, $fields, $id);
						
						if(!empty($view_id) && !empty($id))
							C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_CARD_WIDGET, $id);
						
						echo json_encode([
							'status' => true,
							'id' => $id,
							'label' => $name,
							'view_id' => $view_id,
						]);
						return;
						break;
					
					case 'build':
						@$name = DevblocksPlatform::importGPC($_POST['name'], 'string', '');
						@$record_type = DevblocksPlatform::importGPC($_POST['record_type'], 'string', '');
						@$extension_id = DevblocksPlatform::importGPC($_POST['extension_id'], 'string', '');
						@$params = DevblocksPlatform::importGPC($_POST['params'], 'array', []);
						@$width_units = DevblocksPlatform::importGPC($_POST['width_units'], 'integer', 1);
						
						$error = null;
						
						if(empty($id)) { // New
							$fields = array(
								DAO_CardWidget::EXTENSION_ID => $extension_id,
								DAO_CardWidget::EXTENSION_PARAMS_JSON => json_encode($params),
								DAO_CardWidget::NAME => $name,
								DAO_CardWidget::RECORD_TYPE => $record_type,
								DAO_CardWidget::UPDATED_AT => time(),
								DAO_CardWidget::WIDTH_UNITS => $width_units,
							);
							
							if(false == ($extension = Extension_CardWidget::get($extension_id)))
								throw new Exception_DevblocksAjaxValidationError("Invalid card widget type.");
							
							if(!$extension->saveConfig($fields, null, $error))
								throw new Exception_DevblocksAjaxValidationError($error);
							
							if(!DAO_CardWidget::validate($fields, $error))
								throw new Exception_DevblocksAjaxValidationError($error);
							
							if(!DAO_CardWidget::onBeforeUpdateByActor($active_worker, $fields, null, $error))
								throw new Exception_DevblocksAjaxValidationError($error);
							
							$id = DAO_CardWidget::create($fields);
							DAO_CardWidget::onUpdateByActor($active_worker, $fields, $id);
							
							if(!empty($view_id) && !empty($id))
								C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_CARD_WIDGET, $id);
							
						} else { // Edit
							$fields = array(
								DAO_CardWidget::EXTENSION_PARAMS_JSON => json_encode($params),
								DAO_CardWidget::NAME => $name,
								DAO_CardWidget::UPDATED_AT => time(),
								DAO_CardWidget::WIDTH_UNITS => $width_units,
							);
							
							if(false == ($widget = DAO_CardWidget::get($id)))
								throw new Exception_DevblocksAjaxValidationError("This card widget no longer exists.");
							
							if(false == ($extension = $widget->getExtension()))
								throw new Exception_DevblocksAjaxValidationError("Invalid card widget type.");
							
							if(!$extension->saveConfig($fields, $id, $error))
								throw new Exception_DevblocksAjaxValidationError($error);
							
							if(!DAO_CardWidget::validate($fields, $error, $id))
								throw new Exception_DevblocksAjaxValidationError($error);
							
							if(!DAO_CardWidget::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
								throw new Exception_DevblocksAjaxValidationError($error);
							
							DAO_CardWidget::update($id, $fields);
							DAO_CardWidget::onUpdateByActor($active_worker, $fields, $id);
						}
						
						if($id) {
							// Custom field saves
							@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', []);
							if(!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_CARD_WIDGET, $id, $field_ids, $error))
								throw new Exception_DevblocksAjaxValidationError($error);
						}
						
						echo json_encode(array(
							'status' => true,
							'id' => $id,
							'label' => $name,
							'view_id' => $view_id,
						));
						return;
						break;
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
	
	function exportWidgetAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'int', 0);
		
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker->is_superuser)
			return;
		
		if(false == ($widget = DAO_CardWidget::get($id)))
			return;
		
		if(false == ($extension = $widget->getExtension()))
			return;
		
		$json = $extension->export($widget);
		
		$tpl->assign('widget', $widget);
		$tpl->assign('json', DevblocksPlatform::strFormatJson($json));
		
		$tpl->display('devblocks:cerberusweb.core::internal/cards/widgets/export_widget.tpl');
	}
	
	function testWidgetTemplateAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'int', 0);
		@$params = DevblocksPlatform::importGPC($_REQUEST['params'], 'array', []);
		@$template_key = DevblocksPlatform::importGPC($_REQUEST['template_key'], 'string', '');
		@$index = DevblocksPlatform::importGPC($_REQUEST['index'], 'integer', 0);
		@$format = DevblocksPlatform::importGPC($_REQUEST['format'], 'string', '');
		
		@$placeholders_yaml = DevblocksPlatform::importVar($params['placeholder_simulator_yaml'], 'string', '');
		
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$tpl = DevblocksPlatform::services()->template();
		
		$placeholders = DevblocksPlatform::services()->string()->yamlParse($placeholders_yaml, 0);
		
		$template = null;
		
		if(DevblocksPlatform::strStartsWith($template_key, 'params[')) {
			$template_key = trim(substr($template_key, 6),'[]');
			$json_key = str_replace(['[',']'],['.',''],$template_key);
			$json_var = DevblocksPlatform::jsonGetPointerFromPath($params, $json_key);
			
			if(is_string($json_var)) {
				@$template = $json_var;
			} elseif (is_array($json_var)) {
				if(array_key_exists($index, $json_var)) {
					@$template = $json_var[$index];
				}
			}
		}
		
		if(false == $template)
			return;
		
		if($id) {
			if(false == ($card_widget = DAO_CardWidget::get($id)))
				return;
		} else {
			@$record_type = DevblocksPlatform::importGPC($_REQUEST['record_type'], 'string', '');
			
			$card_widget = new Model_CardWidget();
			$card_widget->record_type = $record_type;
			$card_widget->extension_id = 'cerb.card.widget.sheet';
		}
		
		if(false == ($context_ext = $card_widget->getRecordExtension()))
			return;
		
		$dict = DevblocksDictionaryDelegate::instance([
			'record_id' => $context_ext->getRandom(),
			'record__context' => $context_ext->id,
			'widget_id' => $id,
			'widget__context' => CerberusContexts::CONTEXT_CARD_WIDGET,
		]);
		
		if(is_array($placeholders))
		foreach($placeholders as $placeholder_key => $placeholder_value) {
			$dict->set($placeholder_key, $placeholder_value);
		}
		
		$success = false;
		$output = '';
		
		if(!is_string($template) || false === (@$out = $tpl_builder->build($template, $dict))) {
			// If we failed, show the compile errors
			$errors = $tpl_builder->getErrors();
			$success = false;
			$output = @array_shift($errors);
			
		} else {
			$success = true;
			$output = $out;
		}
		
		if('json' == $format) {
			header('Content-Type: application/json; charset=utf-8');
			
			echo json_encode([
				'status' => $success,
				'response' => $output,
			]);
			
		} else {
			$tpl->assign('success', $success);
			$tpl->assign('output', $output);
			$tpl->display('devblocks:cerberusweb.core::internal/renderers/test_results.tpl');
		}
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
			$models = [];
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
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&type=card_widget', true),
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
				foreach($results as $opp_id => $row) {
					if($opp_id==$explore_from)
						$orig_pos = $pos;
					
					$url = $url_writer->writeNoProxy(sprintf("c=profiles&type=card_widget&id=%d-%s", $row[SearchFields_CardWidget::ID], DevblocksPlatform::strToPermalink($row[SearchFields_CardWidget::NAME])), true);
					
					$model = new Model_ExplorerSet();
					$model->hash = $hash;
					$model->pos = $pos++;
					$model->params = array(
						'id' => $row[SearchFields_CardWidget::ID],
						'url' => $url,
					);
					$models[] = $model;
				}
			
			DAO_ExplorerSet::createFromModels($models);
			
			$view->renderPage++;
			
		} while(!empty($results));
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('explore',$hash,$orig_pos)));
	}
	
	function renderWidgetConfigAction() {
		@$extension_id = DevblocksPlatform::importGPC($_REQUEST['extension'], 'string', '');
		
		if(false == ($extension = Extension_CardWidget::get($extension_id)))
			return;
		
		$model = new Model_CardWidget();
		$model->extension_id = $extension_id;
		
		$extension->renderConfig($model);
	}
	
	function renderWidgetAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'], 'string', '');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'], 'integer', 0);
		@$full = DevblocksPlatform::importGPC($_REQUEST['full'], 'bool', false);
		
		if(false == ($widget = DAO_CardWidget::get($id)))
			return;
		
		if(false == ($extension = $widget->getExtension()))
			return;
		
		// If full, we also want to replace the container
		if($full) {
			$tpl = DevblocksPlatform::services()->template();
			$tpl->assign('widget', $widget);
			$tpl->assign('context', $context);
			$tpl->assign('context_id', $context_id);
			$tpl->assign('full', true);
			
			$tpl->display('devblocks:cerberusweb.core::internal/cards/widgets/render.tpl');
			
		} else {
			$extension->render($widget, $context, $context_id);
		}
	}
	
	function reorderWidgetsAction() {
		@$record_type = DevblocksPlatform::importGPC($_REQUEST['record_type'], 'string', '');
		@$zones = DevblocksPlatform::importGPC($_REQUEST['zones'], 'array', []);
		
		$active_worker = CerberusApplication::getActiveWorker();
		$cache = DevblocksPlatform::services()->cache();
		
		if(!$active_worker->is_superuser)
			return;
		
		$widgets = DAO_CardWidget::getByContext($record_type);
		$new_zones = [];
		
		// Sanitize widget IDs
		foreach($zones as $zone_id => $zone) {
			$new_zones[$zone_id] = array_values(array_intersect(explode(',', $zone), array_keys($widgets)));
		}
		
		DAO_CardWidget::reorder($new_zones);
		
		$cache_key = sprintf('card_widgets:' . $record_type);
		$cache->remove($cache_key);
	}
	
	function getFieldsTabsByContextAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		
		$tpl = DevblocksPlatform::services()->template();
		
		if(false == ($context_ext = Extension_DevblocksContext::get($context)))
			return;
		
		if(!($context_ext instanceof IDevblocksContextProfile))
			return;
		
		$tpl->assign('context_ext', $context_ext);
		
		// =================================================================
		// Properties
		
		$properties = $context_ext->profileGetFields();
		
		$tpl->assign('custom_field_values', []);
		
		$properties_cfields = Page_Profiles::getProfilePropertiesCustomFields($context, null);
		
		if(!empty($properties_cfields))
			$properties = array_merge($properties, $properties_cfields);
		
		$tpl->assign('properties', $properties);
		
		$properties_custom_fieldsets = Page_Profiles::getProfilePropertiesCustomFieldsets($context, null, [], true);
		$tpl->assign('properties_custom_fieldsets', $properties_custom_fieldsets);
		
		// =================================================================
		// Search buttons
		
		$search_contexts = Extension_DevblocksContext::getAll(false, ['search']);
		$tpl->assign('search_contexts', $search_contexts);
		
		// =================================================================
		// Template
		$tpl->display('devblocks:cerberusweb.core::internal/cards/widgets/fields/fields_config_tabs.tpl');
	}
	
};
