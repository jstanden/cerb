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

class PageSection_ProfilesWorkspaceWidget extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // workspace_widget 
		@$context_id = intval(array_shift($stack)); // 123

		$context = CerberusContexts::CONTEXT_WORKSPACE_WIDGET;
		
		Page_Profiles::renderProfile($context, $context_id, $stack);
	}
	
	function handleActionForPage(string $action, string $scope=null) {
		if('profileAction' == $scope) {
			switch ($action) {
				case 'invokeConfig':
					return $this->_profileAction_invokeConfig();
				case 'renderWidget':
					return $this->_profileAction_renderWidget();
				case 'savePeekJson':
					return $this->_profileAction_savePeekJson();
				case 'exportWidget':
					return $this->_profileAction_exportWidget();
				case 'exportWidgetData':
					return $this->_profileAction_exportWidgetData();
				case 'getFieldsTabsByContext':
					return $this->_profileAction_getFieldsTabsByContext();
				case 'getWidgetDatasourceConfig':
					return $this->_profileAction_getWidgetDatasourceConfig();
				case 'getWidgetParams':
					return $this->_profileAction_getWidgetParams();
				case 'reorderWidgets':
					return $this->_profileAction_reorderWidgets();
				case 'testWidgetTemplate':
					return $this->_profileAction_testWidgetTemplate();
				case 'viewExplore':
					return $this->_profileAction_viewExplore();
			}
		}
		return false;
	}
	
	private function _profileAction_getWidgetParams() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		$widget_id = DevblocksPlatform::importGPC($_REQUEST['id'] ?? null, 'integer',0);
		$extension_id = DevblocksPlatform::importGPC($_REQUEST['extension'] ?? null, 'string','');
		
		if(false == ($widget_extension = Extension_WorkspaceWidget::get($extension_id)))
			return;
		
		if(false == ($widget = DAO_WorkspaceWidget::get($widget_id))) {
			$widget = new Model_WorkspaceWidget();
			$widget->extension_id = $widget_extension->id;
		}
		
		if($widget && $widget->id) {
			if(!Context_WorkspaceWidget::isWriteableByActor($widget, $active_worker))
				DevblocksPlatform::dieWithHttpError(null, 403);
		}
		
		$widget_extension->renderConfig($widget);
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
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_WORKSPACE_WIDGET)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				if(false == ($model = DAO_WorkspaceWidget::get($id)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.record.not_found'));
				
				if(!Context_WorkspaceWidget::isDeletableByActor($model, $active_worker))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				CerberusContexts::logActivityRecordDelete(CerberusContexts::CONTEXT_WORKSPACE_WIDGET, $model->id, $model->label);
				
				DAO_WorkspaceWidget::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				$workspace_tab_id = DevblocksPlatform::importGPC($_POST['workspace_tab_id'] ?? null, 'integer', 0);
				$package_uri = DevblocksPlatform::importGPC($_POST['package'] ?? null, 'string', '');
				$import_json = DevblocksPlatform::importGPC($_POST['import_json'] ?? null, 'string', '');
				
				$mode = 'build';
				
				if(!$id && $package_uri) {
					$mode = 'library';
				} elseif (!$id && $import_json) {
					$mode = 'import';
				}
				
				switch($mode) {
					case 'library':
						$prompts = DevblocksPlatform::importGPC($_POST['prompts'] ?? null, 'array', []);
						
						if(empty($package_uri))
							throw new Exception_DevblocksAjaxValidationError("You must select a package from the library.");
						
						if(false == ($package = DAO_PackageLibrary::getByUri($package_uri)))
							throw new Exception_DevblocksAjaxValidationError("You selected an invalid package.");
						
						if($package->point != 'workspace_widget')
							throw new Exception_DevblocksAjaxValidationError("The selected package is not for this extension point.");
						
						$package_json = $package->getPackageJson();
						$records_created = [];
						
						$prompts['workspace_tab_id'] = $workspace_tab_id;
						
						try {
							CerberusApplication::packages()->import($package_json, $prompts, $records_created);
							
						} catch(Exception_DevblocksValidationError $e) {
							throw new Exception_DevblocksAjaxValidationError($e->getMessage());
							
						} catch (Exception $e) {
							throw new Exception_DevblocksAjaxValidationError("An unexpected error occurred.");
						}
						
						if(!array_key_exists(Context_WorkspaceWidget::ID, $records_created))
							throw new Exception_DevblocksAjaxValidationError("There was an issue creating the record.");
						
						$new_widget = reset($records_created[Context_WorkspaceWidget::ID]);
						
						if($view_id)
							C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_WORKSPACE_WIDGET, $new_widget['id']);
						
						echo json_encode([
							'status' => true,
							'id' => $new_widget['id'],
							'label' => $new_widget['label'],
							'view_id' => $view_id,
						]);
						return;
						
					case 'import':
						if(
							empty($import_json)
							|| false == (@$widget_json = json_decode($import_json, true))
							)
							throw new Exception_DevblocksAjaxValidationError("Invalid JSON.");
						
						if(empty($workspace_tab_id))
							throw new Exception_DevblocksAjaxValidationError("Invalid workspace tab target");
						
						if(!isset($widget_json['widget']['extension_id']))
							throw new Exception_DevblocksAjaxValidationError("JSON doesn't contain widget extension info");
						
						if(!isset($widget_json['widget']['params']))
							throw new Exception_DevblocksAjaxValidationError("JSON doesn't contain widget params");
						
						$name = ($widget_json['widget']['label'] ?? null) ?: 'New widget';
						$extension_id = $widget_json['widget']['extension_id'] ?? null;
						
						if(empty($extension_id) || null == (Extension_WorkspaceWidget::get($extension_id)))
							throw new Exception_DevblocksAjaxValidationError("Invalid widget extension");
						
						$fields = [
							DAO_WorkspaceWidget::LABEL => $name,
							DAO_WorkspaceWidget::EXTENSION_ID => $extension_id,
							DAO_WorkspaceWidget::WORKSPACE_TAB_ID => $workspace_tab_id,
							DAO_WorkspaceWidget::POS => @$widget_json['widget']['pos'] ?: 0,
							DAO_WorkspaceWidget::WIDTH_UNITS => @$widget_json['widget']['width_units'] ?: 4,
							DAO_WorkspaceWidget::ZONE => @$widget_json['widget']['zone'] ?: '',
							DAO_WorkspaceWidget::PARAMS_JSON => json_encode($widget_json['widget']['params'])
						];
						
						$error = null;
						
						if(!DAO_WorkspaceWidget::validate($fields, $error))
							throw new Exception_DevblocksAjaxValidationError($error);
						
						if(!DAO_WorkspaceWidget::onBeforeUpdateByActor($active_worker, $fields, null, $error))
							throw new Exception_DevblocksAjaxValidationError($error);
						
						$id = DAO_WorkspaceWidget::create($fields);
						DAO_WorkspaceWidget::onUpdateByActor($active_worker, $fields, $id);
						
						if(!empty($view_id) && !empty($id))
							C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_WORKSPACE_WIDGET, $id);
						
						echo json_encode([
							'status' => true,
							'id' => $id,
							'label' => $name,
							'view_id' => $view_id,
						]);
						return;
					
					case 'build':
						$name = DevblocksPlatform::importGPC($_POST['name'] ?? null, 'string', '');
						$extension_id = DevblocksPlatform::importGPC($_POST['extension_id'] ?? null, 'string', '');
						$width_units = DevblocksPlatform::importGPC($_POST['width_units'] ?? null, 'integer', 1);
						
						$width_units = DevblocksPlatform::intClamp($width_units, 1, 4);
						$error = null;
						
						if(empty($id)) { // New
							$fields = array(
								DAO_WorkspaceWidget::EXTENSION_ID => $extension_id,
								DAO_WorkspaceWidget::LABEL => $name,
								DAO_WorkspaceWidget::UPDATED_AT => time(),
								DAO_WorkspaceWidget::WIDTH_UNITS => $width_units,
								DAO_WorkspaceWidget::WORKSPACE_TAB_ID => $workspace_tab_id,
							);
							
							if(!DAO_WorkspaceWidget::validate($fields, $error))
								throw new Exception_DevblocksAjaxValidationError($error);
								
							if(!DAO_WorkspaceWidget::onBeforeUpdateByActor($active_worker, $fields, null, $error))
								throw new Exception_DevblocksAjaxValidationError($error);
							
							$id = DAO_WorkspaceWidget::create($fields);
							DAO_WorkspaceWidget::onUpdateByActor($active_worker, $fields, $id);
							
							if(!empty($view_id) && !empty($id))
								C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_WORKSPACE_WIDGET, $id);
							
						} else { // Edit
							$fields = array(
								DAO_WorkspaceWidget::LABEL => $name,
								DAO_WorkspaceWidget::UPDATED_AT => time(),
								DAO_WorkspaceWidget::WIDTH_UNITS => $width_units,
								DAO_WorkspaceWidget::WORKSPACE_TAB_ID => $workspace_tab_id,
							);
							
							if(!DAO_WorkspaceWidget::validate($fields, $error, $id))
								throw new Exception_DevblocksAjaxValidationError($error);
								
							if(!DAO_WorkspaceWidget::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
								throw new Exception_DevblocksAjaxValidationError($error);
							
							DAO_WorkspaceWidget::update($id, $fields);
							DAO_WorkspaceWidget::onUpdateByActor($active_worker, $fields, $id);
						}
						
						// Widget extensions
						if(false == ($widget = DAO_WorkspaceWidget::get($id)))
							throw new Exception_DevblocksAjaxValidationError("Failed to load widget.");
						
						if(null == ($widget_extension = $widget->getExtension()))
							throw new Exception_DevblocksAjaxValidationError("Invalid widget extension.");
						
						if(!$widget_extension->saveConfig($widget, $error))
							throw new Exception_DevblocksAjaxValidationError($error);
						
						if($id) {
							// Custom field saves
							$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'] ?? null, 'array', []);
							if(!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_WORKSPACE_WIDGET, $id, $field_ids, $error))
								throw new Exception_DevblocksAjaxValidationError($error);
						}
						
						echo json_encode([
							'status' => true,
							'id' => $id,
							'label' => $name,
							'view_id' => $view_id,
						]);
						return;
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
	
	private function _profileAction_getWidgetDatasourceConfig() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		$widget_id = DevblocksPlatform::importGPC($_REQUEST['widget_id'] ?? null, 'string', '');
		$params_prefix = DevblocksPlatform::importGPC($_REQUEST['params_prefix'] ?? null, 'string', null);
		$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'] ?? null, 'string', '');

		if(null == ($widget = DAO_WorkspaceWidget::get($widget_id))) {
			$widget = new Model_WorkspaceWidget();
		}
		
		if($widget && $widget->id) {
			if(!Context_WorkspaceWidget::isWriteableByActor($widget, $active_worker))
				DevblocksPlatform::dieWithHttpError(null, 403);
		}
		
		if(null == ($datasource_ext = Extension_WorkspaceWidgetDatasource::get($ext_id)))
			return;
		
		$datasource_ext->renderConfig($widget, $widget->params, $params_prefix);
	}
	
	private function _profileAction_invokeConfig() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$id = DevblocksPlatform::importGPC($_POST['id'] ?? null, 'string', '');
		$config_action = DevblocksPlatform::importGPC($_POST['config_action'] ?? null, 'string', '');
		
		if(is_numeric($id)) {
			if(false == ($model = DAO_WorkspaceWidget::get($id)))
				DevblocksPlatform::dieWithHttpError(null, 404);
			
			$extension = $model->getExtension();
			
		} else {
			if(false == ($extension = Extension_WorkspaceWidget::get($id)))
				DevblocksPlatform::dieWithHttpError(null, 404);
			
			$model = new Model_WorkspaceWidget();
			$model->id = 0;
			$model->extension_id = $id;
		}
		
		if(!Context_ProfileWidget::isWriteableByActor($model, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$extension->invokeConfig($config_action, $model);
	}
	
	private function _profileAction_renderWidget() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$id = DevblocksPlatform::importGPC($_POST['id'] ?? null, 'integer', 0);
		$full = DevblocksPlatform::importGPC($_POST['full'] ?? null, 'bool', false);
		
		if(false == ($widget = DAO_WorkspaceWidget::get($id)))
			return;
		
		if(!Context_WorkspaceWidget::isReadableByActor($widget, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		if(false == ($extension = $widget->getExtension()))
			return;
		
		// If full, we also want to replace the container
		if($full) {
			$tpl = DevblocksPlatform::services()->template();
			$tpl->assign('widget', $widget);
			
			if(false == ($widget->getWorkspaceTab()))
				return;
			
			$tpl->assign('full', true);
			$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/render.tpl');
			
		} else {
			$extension->render($widget);
		}
	}
	
	private function _profileAction_reorderWidgets() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(403);
		
		$tab_id = DevblocksPlatform::importGPC($_POST['tab_id'] ?? null, 'integer', 0);
		$zones = DevblocksPlatform::importGPC($_POST['zones'] ?? null, 'array', []);
		
		if(false == ($tab = DAO_WorkspaceTab::get($tab_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!Context_WorkspaceTab::isWriteableByActor($tab, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$widgets = DAO_WorkspaceWidget::getByTab($tab_id);
		$new_zones = [];
		
		// Sanitize widget IDs
		foreach($zones as $zone_id => $zone) {
			$new_zones[$zone_id] = array_values(array_intersect(explode(',', $zone), array_keys($widgets)));
		}
		
		DAO_WorkspaceWidget::reorder($new_zones);
	}
	
	private function _profileAction_getFieldsTabsByContext() {
		$tpl = DevblocksPlatform::services()->template();
		
		$context = DevblocksPlatform::importGPC($_REQUEST['context'] ?? null,'string','');
		
		if(false == ($context_ext = Extension_DevblocksContext::get($context)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!($context_ext instanceof IDevblocksContextProfile))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
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
		
		$search_contexts = Extension_DevblocksContext::getAll(false, ['workspace']);
		$tpl->assign('search_contexts', $search_contexts);
		
		// =================================================================
		// Template
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/record_fields/fields_config_tabs.tpl');
	}
	
	private function _profileAction_testWidgetTemplate() {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$id = DevblocksPlatform::importGPC($_POST['id'] ?? null, 'int', 0);
		$params = DevblocksPlatform::importGPC($_POST['params'] ?? null, 'array', []);
		$template_key = DevblocksPlatform::importGPC($_POST['template_key'] ?? null, 'string', '');
		$index = DevblocksPlatform::importGPC($_POST['index'] ?? null, 'integer', 0);
		$format = DevblocksPlatform::importGPC($_POST['format'] ?? null, 'string', '');
		
		$placeholders_kata = DevblocksPlatform::importVar($params['placeholder_simulator_kata'] ?? null, 'string', '');
		
		$error = null;
		$template = null;
		
		$placeholders = DevblocksPlatform::services()->kata()->parse($placeholders_kata, $error);

		if(DevblocksPlatform::strStartsWith($template_key, 'params[')) {
			$template_key = trim(substr($template_key, 6),'[]');
			$json_key = str_replace([']['],['.'],$template_key);
			$json_var = DevblocksPlatform::jsonGetPointerFromPath($params, $json_key);
			
			if(is_string($json_var)) {
				@$template = $json_var;
			} elseif (is_array($json_var)) {
				if(array_key_exists($index, $json_var)) {
					$template = $json_var[$index] ?? null;
				}
			}
		}
		
		$dict = DevblocksDictionaryDelegate::instance([
			'current_worker_id' => $active_worker->id,
			'current_worker__context' => CerberusContexts::CONTEXT_WORKER,
			'widget_id' => $id,
			'widget__context' => CerberusContexts::CONTEXT_WORKSPACE_WIDGET,
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
	
	private function _profileAction_exportWidget() {
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl = DevblocksPlatform::services()->template();
		
		$id = DevblocksPlatform::importGPC($_REQUEST['id'] ?? null, 'int', 0);
		
		if(false == ($widget = DAO_WorkspaceWidget::get($id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(false == ($extension = $widget->getExtension()))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(false == ($page = $widget->getWorkspacePage()))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!Context_WorkspacePage::isWriteableByActor($page, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$json = $extension->export($widget);
		
		$tpl->assign('widget', $widget);
		$tpl->assign('json', DevblocksPlatform::strFormatJson($json));
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/export_widget.tpl');
	}
	
	private function _profileAction_exportWidgetData() {
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl = DevblocksPlatform::services()->template();
		
		$id = DevblocksPlatform::importGPC($_REQUEST['id'] ?? null, 'int', 0);
		
		if(false == ($widget = DAO_WorkspaceWidget::get($id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(false == ($extension = $widget->getExtension()))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!($extension instanceof ICerbWorkspaceWidget_ExportData))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!Context_WorkspaceWidget::isReadableByActor($widget, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$tpl->assign('widget', $widget);
		$tpl->assign('widget_extension', $extension);
		
		$tpl->assign('export_data', array(
			'csv' => $extension->exportData($widget, 'csv'),
			'json' => $extension->exportData($widget, 'json'),
		));
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/export_widget_data.tpl');
	}
	
	private function _profileAction_viewExplore() {
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$url_writer = DevblocksPlatform::services()->url();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		// Generate hash
		$hash = md5($view_id.$active_worker->id.time());
		
		// Loop through view and get IDs
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);

		// Page start
		$explore_from = DevblocksPlatform::importGPC($_POST['explore_from'] ?? null, 'integer',0);
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
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&type=workspace_widget', true),
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $opp_id => $row) {
				if($opp_id==$explore_from)
					$orig_pos = $pos;
				
				$url = $url_writer->writeNoProxy(sprintf("c=profiles&type=workspace_widget&id=%d-%s", $row[SearchFields_WorkspaceWidget::ID], DevblocksPlatform::strToPermalink($row[SearchFields_WorkspaceWidget::LABEL])), true);
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_WorkspaceWidget::ID],
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
