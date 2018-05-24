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
|	http://cerb.ai		http://webgroup.media
***********************************************************************/

class PageSection_ProfilesProfileWidget extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // profile_widget 
		@$context_id = intval(array_shift($stack)); // 123
		
		$context = CerberusContexts::CONTEXT_PROFILE_WIDGET;
		
		Page_Profiles::renderProfile($context, $context_id, $stack);
	}
	
	function savePeekJsonAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_PROFILE_WIDGET)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				DAO_ProfileWidget::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				@$name = DevblocksPlatform::importGPC($_REQUEST['name'], 'string', '');
				@$extension_id = DevblocksPlatform::importGPC($_REQUEST['extension_id'], 'string', '');
				@$profile_tab_id = DevblocksPlatform::importGPC($_REQUEST['profile_tab_id'], 'integer', 0);
				@$params = DevblocksPlatform::importGPC($_REQUEST['params'], 'array', []);
				@$width_units = DevblocksPlatform::importGPC($_REQUEST['width_units'], 'integer', 1);
				
				if(empty($id)) { // New
					$fields = array(
						DAO_ProfileWidget::EXTENSION_ID => $extension_id,
						DAO_ProfileWidget::EXTENSION_PARAMS_JSON => json_encode($params),
						DAO_ProfileWidget::NAME => $name,
						DAO_ProfileWidget::PROFILE_TAB_ID => $profile_tab_id,
						DAO_ProfileWidget::UPDATED_AT => time(),
						DAO_ProfileWidget::WIDTH_UNITS => $width_units,
					);
					
					if(!DAO_ProfileWidget::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_ProfileWidget::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					$id = DAO_ProfileWidget::create($fields);
					DAO_ProfileWidget::onUpdateByActor($active_worker, $id, $fields);
					
					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_PROFILE_WIDGET, $id);
					
				} else { // Edit
					$fields = array(
						DAO_ProfileWidget::EXTENSION_PARAMS_JSON => json_encode($params),
						DAO_ProfileWidget::NAME => $name,
						DAO_ProfileWidget::PROFILE_TAB_ID => $profile_tab_id,
						DAO_ProfileWidget::UPDATED_AT => time(),
						DAO_ProfileWidget::WIDTH_UNITS => $width_units,
					);
					
					if(!DAO_ProfileWidget::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
						
					if(!DAO_ProfileWidget::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					DAO_ProfileWidget::update($id, $fields);
					DAO_ProfileWidget::onUpdateByActor($active_worker, $id, $fields);
				}
	
				// Custom field saves
				@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', []);
				if(!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_PROFILE_WIDGET, $id, $field_ids, $error))
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
	
	function getFieldsTabsByContextAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		
		$tpl = DevblocksPlatform::services()->template();
		
		if(false == ($context_ext = Extension_DevblocksContext::get($context)))
			return;
		
		$tpl->assign('context_ext', $context_ext);
		
		// =================================================================
		// Properties
		
		$properties = $context_ext->profileGetFields();
		
		$tpl->assign('custom_field_values', []);
		
		$properties_cfields = Page_Profiles::getProfilePropertiesCustomFields($context, []);
		
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
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/fields/fields_config_tabs.tpl');
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
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&type=profile_widget', true),
					'toolbar_extension_id' => 'cerberusweb.contexts.profile.widget.explore.toolbar',
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $opp_id => $row) {
				if($opp_id==$explore_from)
					$orig_pos = $pos;
				
				$url = $url_writer->writeNoProxy(sprintf("c=profiles&type=profile_widget&id=%d-%s", $row[SearchFields_ProfileWidget::ID], DevblocksPlatform::strToPermalink($row[SearchFields_ProfileWidget::NAME])), true);
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_ProfileWidget::ID],
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
