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

class PageSection_ProfilesWorkspaceTab extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::services()->template();
		$visit = CerberusApplication::getVisit();
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // workspace_tab 
		$id = array_shift($stack); // 123
		
		@$id = intval($id);
		
		if(null == ($workspace_tab = DAO_WorkspaceTab::get($id))) {
			return;
		}
		$tpl->assign('workspace_tab', $workspace_tab);
		
		// Tab persistence
		
		$point = 'profiles.workspace_tab.tab';
		$tpl->assign('point', $point);
		
		if(null == (@$tab_selected = $stack[0])) {
			$tab_selected = $visit->get($point, '');
		}
		$tpl->assign('tab_selected', $tab_selected);
		
		// Properties
		
		$properties = [];
		
		$properties['workspace_page_id'] = array(
			'label' => mb_ucfirst($translate->_('common.page')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $workspace_tab->workspace_page_id,
			'params' => [
				'context' => CerberusContexts::CONTEXT_WORKSPACE_PAGE,
			]
		);
		
		$properties['extension_id'] = array(
			'label' => mb_ucfirst($translate->_('common.type')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $workspace_tab->getExtensionName(),
		);
		
		$properties['updated'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $workspace_tab->updated_at,
		);
		
		// Custom Fields
		
		@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_WORKSPACE_TAB, $workspace_tab->id)) or [];
		$tpl->assign('custom_field_values', $values);
		
		$properties_cfields = Page_Profiles::getProfilePropertiesCustomFields(CerberusContexts::CONTEXT_WORKSPACE_TAB, $values);
		
		if(!empty($properties_cfields))
			$properties = array_merge($properties, $properties_cfields);
		
		// Custom Fieldsets
		
		$properties_custom_fieldsets = Page_Profiles::getProfilePropertiesCustomFieldsets(CerberusContexts::CONTEXT_WORKSPACE_TAB, $workspace_tab->id, $values);
		$tpl->assign('properties_custom_fieldsets', $properties_custom_fieldsets);
		
		// Link counts
		
		$properties_links = array(
			CerberusContexts::CONTEXT_WORKSPACE_TAB => array(
				$workspace_tab->id => 
					DAO_ContextLink::getContextLinkCounts(
						CerberusContexts::CONTEXT_WORKSPACE_TAB,
						$workspace_tab->id,
						array(CerberusContexts::CONTEXT_CUSTOM_FIELDSET)
					),
			),
		);
		
		$tpl->assign('properties_links', $properties_links);
		
		// Properties
		
		$tpl->assign('properties', $properties);
		
		// Tabs
		$tab_manifests = Extension_ContextProfileTab::getExtensions(false, CerberusContexts::CONTEXT_WORKSPACE_TAB);
		$tpl->assign('tab_manifests', $tab_manifests);
		
		// Template
		$tpl->display('devblocks:cerberusweb.core::profiles/workspace_tab.tpl');
	}
	
	function getTabParamsAction() {
		@$page_id = DevblocksPlatform::importGPC($_REQUEST['page_id'],'integer',0);
		@$tab_id = DevblocksPlatform::importGPC($_REQUEST['tab_id'],'integer',0);
		@$extension_id = DevblocksPlatform::importGPC($_REQUEST['extension'],'string','');
		
		if(false == ($tab_extension = Extension_WorkspaceTab::get($extension_id)))
			return;
		
		if(false == ($page = DAO_WorkspacePage::get($page_id)))
			return;
		
		if(false == ($tab = DAO_WorkspaceTab::get($tab_id))) {
			$tab = new Model_WorkspaceTab();
			$tab->workspace_page_id = $page_id;
			$tab->extension_id = $tab_extension->id;
		}
		
		$tab_extension->renderTabConfig($page, $tab);
	}
	
	function savePeekJsonAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'], 'string', '');
		
		$url_writer = DevblocksPlatform::services()->url();
		$active_worker = CerberusApplication::getActiveWorker();
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_WORKSPACE_TAB)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				DAO_WorkspaceTab::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				@$mode = DevblocksPlatform::importGPC($_REQUEST['mode'], 'string', '');
				@$workspace_page_id = DevblocksPlatform::importGPC($_REQUEST['workspace_page_id'], 'integer', 0);

				$index = 99;
				
				if(!$workspace_page_id || null == ($workspace_page = DAO_WorkspacePage::get($workspace_page_id)))
					throw new Exception_DevblocksAjaxValidationError("A valid workspace page is required.");
				
				if($workspace_page->extension_id != 'core.workspace.page.workspace')
					throw new Exception_DevblocksAjaxValidationError("A tab can only be added to a workspace page.");
				
				if(!Context_WorkspacePage::isWriteableByActor($workspace_page, $active_worker))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.edit'));
				
				if($id)
					$mode = 'build';
				
				switch($mode) {
					case 'import':
						@$import_json = DevblocksPlatform::importGPC($_REQUEST['import_json'], 'string', '');
						
						@$json = json_decode($import_json, true);
						
						if(
							empty($import_json)
							|| false == (@$json = json_decode($import_json, true))
							|| !isset($json['tab']['extension_id'])
							)
							throw new Exception_DevblocksAjaxValidationError("Invalid JSON.");
						
						@$name = $json['tab']['name'];
						@$extension_id = $json['tab']['extension_id'];
						
						if(null == ($tab_extension = Extension_WorkspaceTab::get($extension_id)))
							throw new Exception_DevblocksAjaxValidationError("Invalid tab extension.");
	
						if(
							!isset($json['tab']['extension_id'])
							|| !isset($json['tab']['name'])
							|| !isset($json['tab']['params'])
						)
							throw new Exception_DevblocksAjaxValidationError("Invalid tab manifest.");
						
						$fields = [
							DAO_WorkspaceTab::NAME => $name,
							DAO_WorkspaceTab::POS => $index,
							DAO_WorkspaceTab::EXTENSION_ID => $json['tab']['extension_id'],
							DAO_WorkspaceTab::PARAMS_JSON => json_encode($json['tab']['params']),
							DAO_WorkspaceTab::WORKSPACE_PAGE_ID => $workspace_page_id,
						];
						
						if(!DAO_WorkspaceTab::validate($fields, $error))
							throw new Exception_DevblocksAjaxValidationError($error);
							
						if(!DAO_WorkspaceTab::onBeforeUpdateByActor($active_worker, $fields, null, $error))
							throw new Exception_DevblocksAjaxValidationError($error);
						
						$id = DAO_WorkspaceTab::create($fields);
						DAO_WorkspaceTab::onUpdateByActor($active_worker, $id, $fields);
						
						if(false == ($tab = DAO_WorkspaceTab::get($id)))
							throw new Exception_DevblocksAjaxValidationError("Failed to load tab.");
						
						if(false == $tab_extension->importTabConfigJson($json, $tab))
							throw new Exception_DevblocksAjaxValidationError("Failed to import tab configuration.");
						
						if(!empty($view_id) && !empty($id))
							C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_WORKSPACE_TAB, $id);
						
						$tab_url = $url_writer->write(sprintf('ajax.php?c=pages&a=showWorkspaceTab&id=%d&_csrf_token=%s', $id, $_SESSION['csrf_token']));
						
						echo json_encode(array(
							'status' => true,
							'id' => $id,
							'page_id' => $workspace_page_id,
							'label' => $name,
							'tab_url' => $tab_url,
							'view_id' => $view_id,
						));
						break;
						
					case 'build':
						@$name = DevblocksPlatform::importGPC($_REQUEST['name'], 'string', '');
						@$extension_id = DevblocksPlatform::importGPC($_REQUEST['extension_id'], 'string', '');
						
						if(empty($id)) { // New
							$fields = array(
								DAO_WorkspaceTab::EXTENSION_ID => $extension_id,
								DAO_WorkspaceTab::NAME => $name,
								DAO_WorkspaceTab::POS => 99,
								DAO_WorkspaceTab::UPDATED_AT => time(),
								DAO_WorkspaceTab::WORKSPACE_PAGE_ID => $workspace_page_id,
							);
							
							if(!DAO_WorkspaceTab::validate($fields, $error))
								throw new Exception_DevblocksAjaxValidationError($error);
								
							if(!DAO_WorkspaceTab::onBeforeUpdateByActor($active_worker, $fields, null, $error))
								throw new Exception_DevblocksAjaxValidationError($error);
							
							$id = DAO_WorkspaceTab::create($fields);
							DAO_WorkspaceTab::onUpdateByActor($active_worker, $id, $fields);
							
							if(!empty($view_id) && !empty($id))
								C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_WORKSPACE_TAB, $id);
							
						} else { // Edit
							$fields = array(
								DAO_WorkspaceTab::NAME => $name,
								DAO_WorkspaceTab::UPDATED_AT => time(),
								DAO_WorkspaceTab::WORKSPACE_PAGE_ID => $workspace_page_id,
							);
							
							if(!DAO_WorkspaceTab::validate($fields, $error, $id))
								throw new Exception_DevblocksAjaxValidationError($error);
								
							if(!DAO_WorkspaceTab::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
								throw new Exception_DevblocksAjaxValidationError($error);
							
							DAO_WorkspaceTab::update($id, $fields);
							DAO_WorkspaceTab::onUpdateByActor($active_worker, $id, $fields);
						}
						
						$tab_url = $url_writer->write(sprintf('ajax.php?c=pages&a=showWorkspaceTab&id=%d&_csrf_token=%s', $id, $_SESSION['csrf_token']));
						
						// Custom fields
						@$field_ids = DevblocksPlatform::importGPC($_REQUEST['field_ids'], 'array', []);
						DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_WORKSPACE_TAB, $id, $field_ids);
						
						// Tab extensions
						if(false == ($tab = DAO_WorkspaceTab::get($id)))
							throw new Exception_DevblocksAjaxValidationError("Failed to load tab.");
						
						if(null == ($tab_extension = $tab->getExtension()))
							throw new Exception_DevblocksAjaxValidationError("Invalid tab extension.");
							
						if(method_exists($tab_extension, 'saveTabConfig'))
							$tab_extension->saveTabConfig($workspace_page, $tab);
						
						echo json_encode(array(
							'status' => true,
							'id' => $id,
							'page_id' => $workspace_page_id,
							'label' => $name,
							'tab_url' => $tab_url,
							'view_id' => $view_id,
						));
						break;
				}
				

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
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&type=workspace_tab', true),
					'toolbar_extension_id' => 'cerberusweb.contexts.workspace.tab.explore.toolbar',
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $opp_id => $row) {
				if($opp_id==$explore_from)
					$orig_pos = $pos;
				
				$url = $url_writer->writeNoProxy(sprintf("c=profiles&type=workspace_tab&id=%d-%s", $row[SearchFields_WorkspaceTab::ID], DevblocksPlatform::strToPermalink($row[SearchFields_WorkspaceTab::NAME])), true);
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_WorkspaceTab::ID],
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
