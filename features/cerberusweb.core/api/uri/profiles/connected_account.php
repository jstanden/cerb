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

class PageSection_ProfilesConnectedAccount extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // connected_account
		@$context_id = intval(array_shift($stack)); // 123

		$context = CerberusContexts::CONTEXT_CONNECTED_ACCOUNT;
		
		Page_Profiles::renderProfile($context, $context_id, $stack);
	}
	
	function handleActionForPage(string $action, string $scope=null) {
		if('profileAction' == $scope) {
			switch ($action) {
				case 'savePeekJson':
					return $this->_profileAction_savePeekJson();
				case 'auth':
					return $this->_profileAction_auth();
				case 'viewExplore':
					return $this->_profileAction_viewExplore();
			}
		}
		return false;
	}
	
	private function _profileAction_savePeekJson() {
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'], 'string', '');
		
		@$id = DevblocksPlatform::importGPC($_POST['id'], 'integer', 0);
		@$do_delete = DevblocksPlatform::importGPC($_POST['do_delete'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_CONNECTED_ACCOUNT)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				if(false == ($model = DAO_ConnectedAccount::get($id)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.record.not_found'));
				
				if(!Context_ConnectedAccount::isDeletableByActor($model, $active_worker))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				CerberusContexts::logActivityRecordDelete(CerberusContexts::CONTEXT_CONNECTED_ACCOUNT, $model->id, $model->name);
				
				DAO_ConnectedAccount::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				@$name = DevblocksPlatform::importGPC($_POST['name'], 'string', '');
				@$owner = DevblocksPlatform::importGPC($_POST['owner'], 'string', null);
				@$uri = DevblocksPlatform::importGPC($_POST['uri'], 'string', '');
				
				$account = new Model_ConnectedAccount();
				$account->id = 0;
				
				// Edit
				if($id) {
					if(false == ($account = DAO_ConnectedAccount::get($id))
						|| !Context_ConnectedAccount::isWriteableByActor($account, $active_worker)
						)
						throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.edit'));
						
					if(false == ($service_extension = $account->getServiceExtension()))
						throw new Exception_DevblocksAjaxValidationError("Invalid service provider.");
					
					$fields = array(
						DAO_ConnectedAccount::NAME => $name,
						DAO_ConnectedAccount::URI => $uri,
						DAO_ConnectedAccount::UPDATED_AT => time(),
					);
					
					// Owner (only admins)
					if(!empty($owner) && $active_worker->is_superuser) {
						$owner_ctx = '';
						@list($owner_ctx, $owner_ctx_id) = explode(':', $owner, 2);
						
						// Make sure we're given a valid ctx
						
						switch($owner_ctx) {
							case CerberusContexts::CONTEXT_APPLICATION:
							case CerberusContexts::CONTEXT_ROLE:
							case CerberusContexts::CONTEXT_GROUP:
							case CerberusContexts::CONTEXT_WORKER:
								break;
							
							default:
								$owner_ctx = null;
						}
						
						if(empty($owner_ctx))
							throw new Exception_DevblocksAjaxValidationError("A valid 'Owner' is required.");
						
						$fields[DAO_ConnectedAccount::OWNER_CONTEXT] = $owner_ctx;
						$fields[DAO_ConnectedAccount::OWNER_CONTEXT_ID] = $owner_ctx_id;
					}
				
				// Create
				} else {
					@$service_id = DevblocksPlatform::importGPC($_POST['service_id'], 'integer', 0);
					
					$account->service_id = $service_id;
					
					if(false == ($service_extension = $account->getServiceExtension()))
						throw new Exception_DevblocksAjaxValidationError("Invalid service provider.");
					
					$fields = array(
						DAO_ConnectedAccount::NAME => $name,
						DAO_ConnectedAccount::UPDATED_AT => time(),
						DAO_ConnectedAccount::SERVICE_ID => $service_id,
						DAO_ConnectedAccount::URI => $uri,
					);
					
					// Owner (only admins)
					if(!empty($owner) && $active_worker->is_superuser) {
						$owner_ctx = '';
						@list($owner_ctx, $owner_ctx_id) = explode(':', $owner, 2);
						
						// Make sure we're given a valid ctx
						
						switch($owner_ctx) {
							case CerberusContexts::CONTEXT_APPLICATION:
							case CerberusContexts::CONTEXT_ROLE:
							case CerberusContexts::CONTEXT_GROUP:
							case CerberusContexts::CONTEXT_WORKER:
								$fields[DAO_ConnectedAccount::OWNER_CONTEXT] = $owner_ctx;
								$fields[DAO_ConnectedAccount::OWNER_CONTEXT_ID] = $owner_ctx_id;
								break;
						}
					}
					
					// Use the current worker as the owner by default
					if(!isset($fields[DAO_ConnectedAccount::OWNER_CONTEXT])) {
						$fields[DAO_ConnectedAccount::OWNER_CONTEXT] = CerberusContexts::CONTEXT_WORKER;
						$fields[DAO_ConnectedAccount::OWNER_CONTEXT_ID] = $active_worker->id;
					}
				}
				
				// Custom params
				
				$service = $account->getService();
				$params = $account->decryptParams($active_worker) ?: [];
				$error = null;
				
				if(true !== $service_extension->saveAccountConfigForm($service, $account, $params, $error))
					throw new Exception_DevblocksAjaxValidationError($error);
				
				if(empty($id)) {
					if(!DAO_ConnectedAccount::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_ConnectedAccount::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					$id = DAO_ConnectedAccount::create($fields);
					DAO_ConnectedAccount::onUpdateByActor($active_worker, $fields, $id);
					
					if($view_id && $id) {
						C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_CONNECTED_ACCOUNT, $id);
					}
					
				} else {
					if(!DAO_ConnectedAccount::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_ConnectedAccount::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					DAO_ConnectedAccount::update($id, $fields);
					DAO_ConnectedAccount::onUpdateByActor($active_worker, $fields, $id);
				}

				if($id) {
					// Encrypt params
					DAO_ConnectedAccount::setAndEncryptParams($id, $params);
					
					// Custom field saves
					@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', []);
					if(!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_CONNECTED_ACCOUNT, $id, $field_ids, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					echo json_encode(array(
						'status' => true,
						'id' => $id,
						'label' => $name,
						'view_id' => $view_id,
					));
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
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&type=connected_account', true),
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $opp_id => $row) {
				if($opp_id==$explore_from)
					$orig_pos = $pos;
				
				$url = $url_writer->writeNoProxy(sprintf("c=profiles&type=connected_account&id=%d-%s", $row[SearchFields_ConnectedAccount::ID], DevblocksPlatform::strToPermalink($row[SearchFields_ConnectedAccount::NAME])), true);
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_ConnectedAccount::ID],
					'url' => $url,
				);
				$models[] = $model;
			}
			
			DAO_ExplorerSet::createFromModels($models);
			
			$view->renderPage++;
			
		} while(!empty($results));
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('explore',$hash,$orig_pos)));
	}
	
	private function _profileAction_auth() {
		$validation = DevblocksPlatform::services()->validation();
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$account_id = DevblocksPlatform::importGPC(@$_REQUEST['id'], 'integer', 0);
		@$service_id = DevblocksPlatform::importGPC(@$_REQUEST['service_id'], 'integer', 0);
		
		if(false == ($service = DAO_ConnectedService::get($service_id)))
			DevblocksPlatform::dieWithHttpError("Invalid service provider.");
		
		if(!Context_ConnectedService::isReadableByActor($service, $active_worker))
			DevblocksPlatform::dieWithHttpError("Access denied to connected service.");
		
		$account = null;
		
		if($account_id && false == ($account = DAO_ConnectedAccount::get($account_id)))
			DevblocksPlatform::dieWithHttpError("Invalid connected account.");
		
		if($account && !Context_ConnectedAccount::isWriteableByActor($account, $active_worker))
			DevblocksPlatform::dieWithHttpError("Access denied to connected service.");
		
		$edit_params = [
			'id' => $account_id,
			'service_id' => $service_id,
		];
		
		$error = null;
		
		$validation
			->addField('id', 'Account ID')
			->id()
			->setRequired(true)
			->setNotEmpty(false)
			;
		$validation
			->addField('service_id', 'Service ID')
			->id()
			->setRequired(true)
			;
		
		if(false === $validation->validateAll($edit_params, $error))
			DevblocksPlatform::dieWithHttpError($error);
		
		if(false == ($ext = $service->getExtension()))
			DevblocksPlatform::dieWithHttpError("Invalid service provider extension.");
		
		$ext->oauthRender();
	}
};
