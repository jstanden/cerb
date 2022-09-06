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

class PageSection_ProfilesConnectedService extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // connected_service 
		@$context_id = intval(array_shift($stack)); // 123
		
		$context = CerberusContexts::CONTEXT_CONNECTED_SERVICE;
		
		Page_Profiles::renderProfile($context, $context_id, $stack);
	}
	
	function handleActionForPage(string $action, string $scope=null) {
		if('profileAction' == $scope) {
			switch ($action) {
				case 'invoke':
					return $this->_profileAction_invoke();
				case 'getExtensionParams':
					return $this->_profileAction_getExtensionParams();
				case 'savePeekJson':
					return $this->_profileAction_savePeekJson();
				case 'viewExplore':
					return $this->_profileAction_viewExplore();
			}
		}
		return false;
	}
	
	private function _profileAction_savePeekJson() {
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string', '');
		
		$id = DevblocksPlatform::importGPC($_POST['id'] ?? null, 'integer', 0);
		$do_delete = DevblocksPlatform::importGPC($_POST['do_delete'] ?? null, 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$error = null;
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_CONNECTED_SERVICE)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				if(false == ($model = DAO_ConnectedService::get($id)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.record.not_found'));
				
				if(!Context_ConnectedService::isDeletableByActor($model, $active_worker))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				CerberusContexts::logActivityRecordDelete(CerberusContexts::CONTEXT_CONNECTED_SERVICE, $model->id, $model->name);
				
				DAO_ConnectedService::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				$package_uri = DevblocksPlatform::importGPC($_POST['package'] ?? null, 'string', '');
				
				$mode = 'build';
				
				if(!$id && $package_uri)
					$mode = 'library';
				
				switch($mode) {
					case 'library':
						$prompts = DevblocksPlatform::importGPC($_POST['prompts'] ?? null, 'array', []);
						
						if(empty($package_uri))
							throw new Exception_DevblocksAjaxValidationError("You must select a package from the library.");
						
						if(false == ($package = DAO_PackageLibrary::getByUri($package_uri)))
							throw new Exception_DevblocksAjaxValidationError("You selected an invalid package.");
						
						if($package->point != 'connected_service')
							throw new Exception_DevblocksAjaxValidationError("The selected package is not for this extension point.");
						
						$package_json = $package->getPackageJson();
						$records_created = [];
						
						try {
							CerberusApplication::packages()->import($package_json, $prompts, $records_created);
							
						} catch(Exception_DevblocksValidationError $e) {
							throw new Exception_DevblocksAjaxValidationError($e->getMessage());
							
						} catch (Exception $e) {
							throw new Exception_DevblocksAjaxValidationError("An unexpected error occurred.");
						}
						
						if(!array_key_exists(Context_ConnectedService::ID, $records_created))
							throw new Exception_DevblocksAjaxValidationError("There was an issue creating the record.");
						
						$new_service = reset($records_created[Context_ConnectedService::ID]);
						
						if($view_id)
							C4_AbstractView::setMarqueeContextCreated($view_id, Context_ConnectedService::ID, $new_service['id']);
						
						echo json_encode([
							'status' => true,
							'id' => $new_service['id'],
							'label' => $new_service['label'],
							'view_id' => $view_id,
						]);
						return;
						
					case 'build':
						$name = DevblocksPlatform::importGPC($_POST['name'] ?? null, 'string', '');
						$extension_id = DevblocksPlatform::importGPC($_POST['extension_id'] ?? null, 'string', '');
						$params = DevblocksPlatform::importGPC($_POST['params'] ?? null, 'array', []);
						$uri = DevblocksPlatform::importGPC($_POST['uri'] ?? null, 'string', '');
						
						$service = new Model_ConnectedService();
						$service->id = 0;
						$service->extension_id = $extension_id;
						
						if(!$id) { // New
							if(false == ($extension = Extension_ConnectedServiceProvider::get($extension_id)))
								throw new Exception_DevblocksAjaxValidationError("Invalid service provider.");
							
							$fields = array(
								DAO_ConnectedService::EXTENSION_ID => $extension_id,
								DAO_ConnectedService::NAME => $name,
								DAO_ConnectedService::UPDATED_AT => time(),
								DAO_ConnectedService::URI => $uri,
							);
							
						} else { // Edit
							if(false == ($service = DAO_ConnectedService::get($id)))
								throw new Exception_DevblocksAjaxValidationError("Invalid record.");
							
							if(false == ($extension = $service->getExtension()))
								throw new Exception_DevblocksAjaxValidationError("Invalid service provider.");
							
							$fields = array(
								DAO_ConnectedService::NAME => $name,
								DAO_ConnectedService::UPDATED_AT => time(),
								DAO_ConnectedService::URI => $uri,
							);
						}
						
						$params = $service->decryptParams($active_worker) ?: [];
						
						if(false === ($extension->saveConfigForm($service, $params, $error)))
							throw new Exception_DevblocksAjaxValidationError($error);
						
						if(!$id) { // New
							if(!DAO_ConnectedService::validate($fields, $error))
								throw new Exception_DevblocksAjaxValidationError($error);
								
							if(!DAO_ConnectedService::onBeforeUpdateByActor($active_worker, $fields, null, $error))
								throw new Exception_DevblocksAjaxValidationError($error);
							
							$id = DAO_ConnectedService::create($fields);
							DAO_ConnectedService::onUpdateByActor($active_worker, $fields, $id);
							
							if(!empty($view_id) && !empty($id))
								C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_CONNECTED_SERVICE, $id);
							
						} else { // Edit
							if(!DAO_ConnectedService::validate($fields, $error, $id))
								throw new Exception_DevblocksAjaxValidationError($error);
								
							if(!DAO_ConnectedService::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
								throw new Exception_DevblocksAjaxValidationError($error);
							
							DAO_ConnectedService::update($id, $fields);
							DAO_ConnectedService::onUpdateByActor($active_worker, $fields, $id);
						}
						
						if($id) {
							DAO_ConnectedService::setAndEncryptParams($id, $params);
							
							// Custom field saves
							$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'] ?? null, 'array', []);
							if(!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_CONNECTED_SERVICE, $id, $field_ids, $error))
								throw new Exception_DevblocksAjaxValidationError($error);
							
							echo json_encode(array(
								'status' => true,
								'id' => $id,
								'label' => $name,
								'view_id' => $view_id,
							));
							return;
						}
						break;
				}
				
				throw new Exception_DevblocksAjaxValidationError("An unexpected error occurred.");
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
	
	private function _profileAction_getExtensionParams() {
		$extension_id = DevblocksPlatform::importGPC($_REQUEST['id'] ?? null, 'string','');
		
		if(!$extension_id || false == ($ext = Extension_ConnectedServiceProvider::get($extension_id)))
			return;
		
		$service = new Model_ConnectedService();
		$ext->renderConfigForm($service);
	}
	
	private function _profileAction_invoke() {
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$extension_id = DevblocksPlatform::importGPC($_POST['id'] ?? null, 'string','');
		$service_action = DevblocksPlatform::importGPC($_POST['service_action'] ?? null, 'string','');
		
		if(!$extension_id || false == ($ext = Extension_ConnectedServiceProvider::get($extension_id)))
			return;
		
		if($ext instanceof Extension_ConnectedServiceProvider) {
			if (false === ($ext->handleActionForService($service_action))) {
				if(!DEVELOPMENT_MODE_SECURITY_SCAN) {
					trigger_error(
						sprintf('Call to undefined connected service action `%s::%s`',
							get_class($ext),
							$service_action
						),
						E_USER_NOTICE
					);
				}
				DevblocksPlatform::dieWithHttpError(null, 404);
			}
		}
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
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&type=connected_service', true),
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $opp_id => $row) {
				if($opp_id==$explore_from)
					$orig_pos = $pos;
				
				$url = $url_writer->writeNoProxy(sprintf("c=profiles&type=connected_service&id=%d-%s", $row[SearchFields_ConnectedService::ID], DevblocksPlatform::strToPermalink($row[SearchFields_ConnectedService::NAME])), true);
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_ConnectedService::ID],
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
