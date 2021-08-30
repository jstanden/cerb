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

class PageSection_ProfilesWorkspaceTab extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // workspace_tab 
		@$context_id = intval(array_shift($stack)); // 123
		
		$context = CerberusContexts::CONTEXT_WORKSPACE_TAB;
		
		Page_Profiles::renderProfile($context, $context_id, $stack);
	}
	
	function handleActionForPage(string $action, string $scope=null) {
		if('profileAction' == $scope) {
			switch ($action) {
				case 'savePeekJson':
					return $this->_profileAction_savePeekJson();
				case 'saveDashboardTabPrefs':
					return $this->_profileAction_saveDashboardTabPrefs();
				case 'getTabParams':
					return $this->_profileAction_getTabParams();
				case 'previewDashboardPrompts':
					return $this->_profileAction_previewDashboardPrompts();
				case 'viewExplore':
					return $this->_profileAction_viewExplore();
			}
		}
		return false;
	}
	
	private function _profileAction_getTabParams() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$page_id = DevblocksPlatform::importGPC($_REQUEST['page_id'],'integer',0);
		@$tab_id = DevblocksPlatform::importGPC($_REQUEST['tab_id'],'integer',0);
		@$extension_id = DevblocksPlatform::importGPC($_REQUEST['extension'],'string','');
		
		if(false == ($tab_extension = Extension_WorkspaceTab::get($extension_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(false == ($page = DAO_WorkspacePage::get($page_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!Context_WorkspacePage::isWriteableByActor($page, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		if(false == ($tab = DAO_WorkspaceTab::get($tab_id))) {
			$tab = new Model_WorkspaceTab();
			$tab->workspace_page_id = $page_id;
			$tab->extension_id = $tab_extension->id;
		}
		
		$tab_extension->renderTabConfig($page, $tab);
	}
	
	private function _profileAction_previewDashboardPrompts() {
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$tpl = DevblocksPlatform::services()->template();
		
		@$kata_string = DevblocksPlatform::importGPC($_REQUEST['kata'],'string','');
		
		$workspace_tab = new Model_WorkspaceTab();
		$workspace_tab->params = [
			'prompts_kata' => $kata_string,
		];
		
		$prompts = $workspace_tab->getPlaceholderPrompts();
		
		$tpl->assign('prompts', $prompts);
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/tabs/dashboard/config_preview_placeholders.tpl');
	}
	
	private function _profileAction_savePeekJson() {
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'], 'string', '');
		
		@$id = DevblocksPlatform::importGPC($_POST['id'], 'integer', 0);
		@$do_delete = DevblocksPlatform::importGPC($_POST['do_delete'], 'string', '');
		
		$url_writer = DevblocksPlatform::services()->url();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_WORKSPACE_TAB)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				if(false == ($model = DAO_WorkspaceTab::get($id)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.record.not_found'));
				
				if(!Context_WorkspaceTab::isDeletableByActor($model, $active_worker))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				CerberusContexts::logActivityRecordDelete(CerberusContexts::CONTEXT_WORKSPACE_TAB, $model->id, $model->name);
				
				DAO_WorkspaceTab::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				@$workspace_page_id = DevblocksPlatform::importGPC($_POST['workspace_page_id'], 'integer', 0);
				@$package_uri = DevblocksPlatform::importGPC($_POST['package'], 'string', '');
				@$import_json = DevblocksPlatform::importGPC($_POST['import_json'], 'string', '');
				
				$mode = 'build';
				
				if(!$id && $package_uri) {
					$mode = 'library';
					
				} else if (!$id && $import_json) {
					$mode = 'import';
				}
				
				$index = 99;
				$error = null;
				
				if($id) {
					if(false == ($workspace_tab = DAO_WorkspaceTab::get($id))) {
						throw new Exception_DevblocksAjaxValidationError("Invalid workspace tab.");
					} else {
						$workspace_page_id = $workspace_tab->workspace_page_id;
					}
				}
				
				if(!$workspace_page_id || null == ($workspace_page = DAO_WorkspacePage::get($workspace_page_id)))
					throw new Exception_DevblocksAjaxValidationError("A valid workspace page is required.");
				
				if($workspace_page->extension_id != 'core.workspace.page.workspace')
					throw new Exception_DevblocksAjaxValidationError("A tab can only be added to a workspace page.");
				
				if(!Context_WorkspacePage::isWriteableByActor($workspace_page, $active_worker))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.edit'));
				
				switch($mode) {
					case 'library':
						@$prompts = DevblocksPlatform::importGPC($_POST['prompts'], 'array', []);
						
						if(empty($package_uri))
							throw new Exception_DevblocksAjaxValidationError("You must select a package from the library.");
						
						if(false == ($package = DAO_PackageLibrary::getByUri($package_uri)))
							throw new Exception_DevblocksAjaxValidationError("You selected an invalid package.");
						
						if($package->point != 'workspace_tab')
							throw new Exception_DevblocksAjaxValidationError("The selected package is not for this extension point.");
						
						$package_json = $package->getPackageJson();
						$records_created = [];
						
						$prompts['workspace_page_id'] = $workspace_page_id;
						
						try {
							CerberusApplication::packages()->import($package_json, $prompts, $records_created);
							
						} catch(Exception_DevblocksValidationError $e) {
							throw new Exception_DevblocksAjaxValidationError($e->getMessage());
							
						} catch (Exception $e) {
							throw new Exception_DevblocksAjaxValidationError("An unexpected error occurred.");
						}
						
						if(!array_key_exists(Context_WorkspaceTab::ID, $records_created))
							throw new Exception_DevblocksAjaxValidationError("There was an issue creating the record.");
						
						$new_tab = reset($records_created[Context_WorkspaceTab::ID]);
						
						if($view_id)
							C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_WORKSPACE_TAB, $new_tab['id']);
						
						$tab_url = $url_writer->write(sprintf('ajax.php?c=pages&a=renderTab&id=%d', $new_tab['id']));
						
						echo json_encode([
							'status' => true,
							'id' => $new_tab['id'],
							'page_id' => $workspace_page_id,
							'label' => $new_tab['label'],
							'tab_url' => $tab_url,
							'view_id' => $view_id,
						]);
						return;
						break;
					
					case 'import':
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
						DAO_WorkspaceTab::onUpdateByActor($active_worker, $fields, $id);
						
						if(false == ($tab = DAO_WorkspaceTab::get($id)))
							throw new Exception_DevblocksAjaxValidationError("Failed to load tab.");
						
						if(false == $tab_extension->importTabConfigJson($json, $tab))
							throw new Exception_DevblocksAjaxValidationError("Failed to import tab configuration.");
						
						if(!empty($view_id) && !empty($id))
							C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_WORKSPACE_TAB, $id);
						
						$tab_url = $url_writer->write(sprintf('ajax.php?c=pages&a=renderTab&id=%d', $id));
						
						echo json_encode(array(
							'status' => true,
							'id' => $id,
							'page_id' => $workspace_page_id,
							'label' => $name,
							'tab_url' => $tab_url,
							'view_id' => $view_id,
						));
						return;
						break;
						
					case 'build':
						@$name = DevblocksPlatform::importGPC($_POST['name'], 'string', '');
						@$extension_id = DevblocksPlatform::importGPC($_POST['extension_id'], 'string', '');
						
						if(empty($id)) { // New
							$fields = [
								DAO_WorkspaceTab::EXTENSION_ID => $extension_id,
								DAO_WorkspaceTab::NAME => $name,
								DAO_WorkspaceTab::POS => 99,
								DAO_WorkspaceTab::UPDATED_AT => time(),
								DAO_WorkspaceTab::WORKSPACE_PAGE_ID => $workspace_page_id,
							];
							
							if(!DAO_WorkspaceTab::validate($fields, $error))
								throw new Exception_DevblocksAjaxValidationError($error);
								
							if(!DAO_WorkspaceTab::onBeforeUpdateByActor($active_worker, $fields, null, $error))
								throw new Exception_DevblocksAjaxValidationError($error);
							
							$id = DAO_WorkspaceTab::create($fields);
							DAO_WorkspaceTab::onUpdateByActor($active_worker, $fields, $id);
							
							if(!empty($view_id) && !empty($id))
								C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_WORKSPACE_TAB, $id);
							
						} else { // Edit
							$fields = [
								DAO_WorkspaceTab::NAME => $name,
								DAO_WorkspaceTab::UPDATED_AT => time(),
							];
							
							if(!DAO_WorkspaceTab::validate($fields, $error, $id))
								throw new Exception_DevblocksAjaxValidationError($error);
								
							if(!DAO_WorkspaceTab::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
								throw new Exception_DevblocksAjaxValidationError($error);
							
							DAO_WorkspaceTab::update($id, $fields);
							DAO_WorkspaceTab::onUpdateByActor($active_worker, $fields, $id);
						}
						
						$tab_url = $url_writer->write(sprintf('ajax.php?c=pages&a=renderTab&id=%d', $id));
						
						// Custom field saves
						@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', []);
						if(!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_WORKSPACE_TAB, $id, $field_ids, $error))
							throw new Exception_DevblocksAjaxValidationError($error);
				
						// Tab extensions
						if(false == ($tab = DAO_WorkspaceTab::get($id)))
							throw new Exception_DevblocksAjaxValidationError("Failed to load tab.");
						
						if(null == ($tab_extension = $tab->getExtension()))
							throw new Exception_DevblocksAjaxValidationError("Invalid tab extension.");
							
						if(method_exists($tab_extension, 'saveTabConfig')) {
							if (false === ($tab_extension->saveTabConfig($workspace_page, $tab, $error)))
								throw new Exception_DevblocksAjaxValidationError($error);
						}
						
						echo json_encode(array(
							'status' => true,
							'id' => $id,
							'page_id' => $workspace_page_id,
							'label' => $name,
							'tab_url' => $tab_url,
							'view_id' => $view_id,
						));
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
	
	private function _profileAction_saveDashboardTabPrefs() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		@$tab_id = DevblocksPlatform::importGPC($_POST['tab_id'],'int', 0);
		@$prompts = DevblocksPlatform::importGPC($_POST['prompts'],'array', []);
		
		header('Content-Type: application/json; charset=utf-8');
		
		if(!$tab_id || false == ($tab = DAO_WorkspaceTab::get($tab_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		$tab->setDashboardPrefsAsWorker($prompts, $active_worker);
		
		echo json_encode(true);
	}
	
	private function _profileAction_viewExplore() {
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string');
		
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
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&type=workspace_tab', true),
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
