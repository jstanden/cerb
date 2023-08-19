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

class PageSection_ProfilesWorkspacePage extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // workspace_page
		@$context_id = intval(array_shift($stack)); // 123
		
		$context = CerberusContexts::CONTEXT_WORKSPACE_PAGE;
		
		Page_Profiles::renderProfile($context, $context_id, $stack);
	}
	
	function handleActionForPage(string $action, string $scope=null) {
		if('profileAction' == $scope) {
			switch ($action) {
				case 'savePeekJson':
					return $this->_profileAction_savePeekJson();
				case 'viewExplore':
					return $this->_profileAction_viewExplore();
			}
		}
		return false;
	}
	
	private function _profileAction_savePeekJson() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$id = DevblocksPlatform::importGPC($_POST['id'] ?? null, 'integer', 0);
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string', '');
		$do_delete = DevblocksPlatform::importGPC($_POST['do_delete'] ?? null, 'integer', '0');
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if($id) {
				if(null == ($workspace_page = DAO_WorkspacePage::get($id)))
					throw new Exception_DevblocksAjaxValidationError("Invalid workspace page.");
				
				if(!Context_WorkspacePage::isWriteableByActor($workspace_page, $active_worker))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.edit'));
			}
			
			if($id && $do_delete) { // Delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_WORKSPACE_PAGE)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				CerberusContexts::logActivityRecordDelete(CerberusContexts::CONTEXT_WORKSPACE_PAGE, $workspace_page->id, $workspace_page->name);
				
				DAO_WorkspacePage::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				
				return;
				
			} else { // Create/Edit
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
						
						if($package->point != 'workspace_page')
							throw new Exception_DevblocksAjaxValidationError("The selected package is not for this extension point.");
						
						// Owner
						list($owner_context, $owner_context_id) = array_pad(explode(':', DevblocksPlatform::importGPC($_POST['owner'] ?? null,'string','')), 2, null);
						
						switch($owner_context) {
							case CerberusContexts::CONTEXT_APPLICATION:
							case CerberusContexts::CONTEXT_ROLE:
							case CerberusContexts::CONTEXT_GROUP:
							case CerberusContexts::CONTEXT_WORKER:
								break;
							
							default:
								$owner_context = null;
								$owner_context_id = null;
								break;
						}
						
						if(!CerberusContexts::isOwnableBy($owner_context, $owner_context_id, $active_worker))
							throw new Exception_DevblocksAjaxValidationError("You can't create pages with this owner.");
						
						$package_json = $package->getPackageJson();
						$records_created = [];
						
						$prompts['owner_context'] = $owner_context;
						$prompts['owner_context_id'] = $owner_context_id;
						
						try {
							CerberusApplication::packages()->import($package_json, $prompts, $records_created);
							
						} catch(Exception_DevblocksValidationError $e) {
							throw new Exception_DevblocksAjaxValidationError($e->getMessage());
							
						} catch (Exception $e) {
							throw new Exception_DevblocksAjaxValidationError("An unexpected error occurred.");
						}
						
						if(!array_key_exists(Context_WorkspacePage::ID, $records_created))
							throw new Exception_DevblocksAjaxValidationError("There was an issue creating the record.");
						
						$new_page = reset($records_created[Context_WorkspacePage::ID]);
						
						// View marquee
						if($new_page && $view_id)
							C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_WORKSPACE_PAGE, $new_page['id']);
						
						echo json_encode([
							'status' => true,
							'id' => $new_page['id'],
							'label' => $new_page['label'],
							'view_id' => $view_id,
						]);
						return;
						break;
					
					case 'import':
						@$json = json_decode($import_json, true);
						
						if(empty($json) || !isset($json['page']))
							throw new Exception_DevblocksAjaxValidationError("Invalid JSON.");
						
						$name = ($json['page']['name'] ?? null) ?: 'New Page';
						$extension_id = $json['page']['extension_id'] ?? null;
						
						if(empty($extension_id) || null == ($page_extension = Extension_WorkspacePage::get($extension_id)))
							throw new Exception_DevblocksAjaxValidationError("Invalid workspace page extension.");
						
						// Owner
						list($owner_context, $owner_context_id) = array_pad(explode(':', DevblocksPlatform::importGPC($_POST['owner'] ?? null,'string','')), 2, null);
						
						switch($owner_context) {
							case CerberusContexts::CONTEXT_APPLICATION:
							case CerberusContexts::CONTEXT_ROLE:
							case CerberusContexts::CONTEXT_GROUP:
							case CerberusContexts::CONTEXT_WORKER:
								break;
							
							default:
								$owner_context = null;
								$owner_context_id = null;
								break;
						}
						
						// Create page
						
						$fields = [
							DAO_WorkspacePage::NAME => $name,
							DAO_WorkspacePage::EXTENSION_ID => $extension_id,
							DAO_WorkspacePage::OWNER_CONTEXT => $owner_context,
							DAO_WorkspacePage::OWNER_CONTEXT_ID => $owner_context_id,
						];
						
						$error = null;
						
						if(!DAO_WorkspacePage::validate($fields, $error, null))
							throw new Exception_DevblocksAjaxValidationError($error);
						
						if(!DAO_WorkspacePage::onBeforeUpdateByActor($active_worker, $fields, null, $error))
							throw new Exception_DevblocksAjaxValidationError($error);
						
						$id = DAO_WorkspacePage::create($fields);
						DAO_WorkspacePage::onUpdateByActor($active_worker, $fields, $id);
						
						if(null == ($page = DAO_WorkspacePage::get($id)))
							throw new Exception_DevblocksAjaxValidationError("Failed to load workspace page.");
						
						if(false == $page_extension->importPageConfigJson($json, $page))
							throw new Exception_DevblocksAjaxValidationError("Failed to import page content.");
						
						// View marquee
						if(!empty($id) && !empty($view_id)) {
							C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_WORKSPACE_PAGE, $id);
						}
						
						echo json_encode(array(
							'status' => true,
							'id' => $id,
							'label' => $name,
							'view_id' => $view_id,
						));
						return;
						break;
					
					case 'build':
						$name = DevblocksPlatform::importGPC($_POST['name'] ?? null, 'string', '');
						$params = DevblocksPlatform::importGPC($_POST['params'] ?? null, 'array', []);
						
						$fields = [
							DAO_WorkspacePage::NAME => trim($name),
							DAO_WorkspacePage::EXTENSION_PARAMS_JSON => json_encode($params),
						];
						
						// Owner
						list($owner_context, $owner_context_id) = array_pad(explode(':', DevblocksPlatform::importGPC($_POST['owner'] ?? null,'string','')), 2, null);
						
						switch($owner_context) {
							case CerberusContexts::CONTEXT_APPLICATION:
							case CerberusContexts::CONTEXT_ROLE:
							case CerberusContexts::CONTEXT_GROUP:
							case CerberusContexts::CONTEXT_WORKER:
								break;
							
							default:
								$owner_context = null;
						}
						
						if(!CerberusContexts::isOwnableBy($owner_context, $owner_context_id, $active_worker)) {
							$owner_context = null;
							$owner_context_id = null;
						}
						
						if(!empty($owner_context)) {
							$fields[DAO_WorkspacePage::OWNER_CONTEXT] = $owner_context;
							$fields[DAO_WorkspacePage::OWNER_CONTEXT_ID] = $owner_context_id;
						}
						
						if(empty($id)) {
							$extension_id = DevblocksPlatform::importGPC($_POST['extension_id'] ?? null, 'string', '');
							
							// Extension
							$fields[DAO_WorkspacePage::EXTENSION_ID] = $extension_id;
							
							if(!DAO_WorkspacePage::validate($fields, $error, null))
								throw new Exception_DevblocksAjaxValidationError($error);
							
							if(!DAO_WorkspacePage::onBeforeUpdateByActor($active_worker, $fields, null, $error))
								throw new Exception_DevblocksAjaxValidationError($error);
							
							$id = DAO_WorkspacePage::create($fields);
							DAO_WorkspacePage::onUpdateByActor($active_worker, $fields, $id);
							
							// View marquee
							if(!empty($id) && !empty($view_id)) {
								C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_WORKSPACE_PAGE, $id);
							}
							
						} else {
							if(!DAO_WorkspacePage::validate($fields, $error, $id))
								throw new Exception_DevblocksAjaxValidationError($error);
							
							if(!DAO_WorkspacePage::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
								throw new Exception_DevblocksAjaxValidationError($error);
							
							DAO_WorkspacePage::update($id, $fields);
							DAO_WorkspacePage::onUpdateByActor($active_worker, $fields, $id);
						}
						
						if($id) {
							// Custom field saves
							$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'] ?? null, 'array', []);
							if(!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_WORKSPACE_PAGE, $id, $field_ids, $error))
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
	
	private function _profileAction_viewExplore() {
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string', '');
		$explore_from = DevblocksPlatform::importGPC($_POST['explore_from'] ?? null, 'int', 0);
		
		$http_response = Cerb_ORMHelper::generateRecordExploreSet($view_id, $explore_from);
		DevblocksPlatform::redirect($http_response);
	}
};
