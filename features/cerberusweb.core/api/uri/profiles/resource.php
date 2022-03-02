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

class PageSection_ProfilesResource extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // resource 
		@$context_id = intval(array_shift($stack)); // 123
		
		$context = CerberusContexts::CONTEXT_RESOURCE;
		
		Page_Profiles::renderProfile($context, $context_id, $stack);
	}
	
	function handleActionForPage(string $action, string $scope=null) {
		if('profileAction' == $scope) {
			switch($action) {
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
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_RESOURCE)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				if(false == ($model = DAO_Resource::get($id)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.record.not_found'));
				
				if(!Context_Resource::isDeletableByActor($model, $active_worker))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				// The `cerb.` namespace is reserved
				if(
					DevblocksPlatform::strStartsWith(DevblocksPlatform::strLower($model->name), 'cerb.')
					&& !DEVELOPMENT_MODE
				) {
					$error = 'The `cerb.` namespace is managed automatically. This resource may not be deleted.';
					throw new Exception_DevblocksAjaxValidationError($error);
				}
				
				CerberusContexts::logActivityRecordDelete(Context_Resource::ID, $model->id, $model->name);
				
				DAO_Resource::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				$name = DevblocksPlatform::importGPC($_POST['name'] ?? null, 'string', '');
				$description = DevblocksPlatform::importGPC($_POST['description'] ?? null, 'string', '');
				$extension_id = DevblocksPlatform::importGPC($_POST['extension_id'] ?? null, 'string', '');
				$is_dynamic = DevblocksPlatform::importGPC($_POST['is_dynamic'] ?? null, 'integer', 0);
				$automation_kata = DevblocksPlatform::importGPC($_POST['automation_kata'] ?? null, 'string', '');
				$file = DevblocksPlatform::importGPC($_FILES['file'] ?? null, 'array', []);
				
				$error = null;
				$fp = null;
				
				$fields = [
					DAO_Resource::AUTOMATION_KATA => $automation_kata,
					DAO_Resource::DESCRIPTION => $description,
					DAO_Resource::EXTENSION_ID => $extension_id,
					DAO_Resource::IS_DYNAMIC => $is_dynamic ? 1 : 0,
					DAO_Resource::NAME => $name,
					DAO_Resource::UPDATED_AT => time(),
				];
				
				/** @var $resource_ext Extension_ResourceType */
				if(false == ($resource_ext = Extension_ResourceType::get($extension_id, true)))
						throw new Exception_DevblocksAjaxValidationError('Invalid resource extension.');
				
				if(is_array($file) && array_key_exists('tmp_name', $file) && $file['tmp_name']) {
					$extension_params = [];
					
					if(false == ($fp = fopen($file['tmp_name'], 'r+b'))) {
						throw new Exception_DevblocksAjaxValidationError('Failed to upload file.');
					}
					
					if(false == ($resource_ext->validateContentData($fp, $extension_params, $error))) {
						throw new Exception_DevblocksAjaxValidationError($error ?? 'Uploaded file is not a valid image.');
					}
					
					$fields[DAO_Resource::EXTENSION_KATA] = DevblocksPlatform::services()->kata()->emit($extension_params);
				}
				
				if(empty($id)) { // New
					// The `cerb.` namespace is reserved
					if(
						DevblocksPlatform::strStartsWith(DevblocksPlatform::strLower($name), 'cerb.')
						&& !DEVELOPMENT_MODE
					) {
						$error = 'The `cerb.` namespace is reserved. Use your own prefix for `Name:`';
						throw new Exception_DevblocksAjaxValidationError($error);
					}

					if(!DAO_Resource::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_Resource::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					$id = DAO_Resource::create($fields);
					DAO_Resource::onUpdateByActor($active_worker, $fields, $id);
					
					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_RESOURCE, $id);
					
				} else { // Edit
					// The `cerb.` namespace is reserved
					if(
						DevblocksPlatform::strStartsWith(DevblocksPlatform::strLower($name), 'cerb.')
						&& !DEVELOPMENT_MODE
					) {
						$error = 'The `cerb.` namespace is managed automatically. Clone this resource to modify it.';
						throw new Exception_DevblocksAjaxValidationError($error);
					}

					if(!DAO_Resource::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_Resource::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					DAO_Resource::update($id, $fields);
					DAO_Resource::onUpdateByActor($active_worker, $fields, $id);
				}
				
				if($id) {
					// File upload
					if(is_resource($fp)) {
						Storage_Resource::put($id, $fp);
						fclose($fp);
					}
					
					// Custom field saves
					$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'] ?? null, 'array', []);
					if(!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_RESOURCE, $id, $field_ids, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
				}
				
				echo json_encode(array(
					'status' => true,
					'context' => CerberusContexts::CONTEXT_RESOURCE,
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
					'total' => $total,
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&type=resource', true),
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
				foreach($results as $opp_id => $row) {
					if($opp_id==$explore_from)
						$orig_pos = $pos;
					
					$url = $url_writer->writeNoProxy(sprintf("c=profiles&type=resource&id=%d-%s", $row[SearchFields_Resource::ID], DevblocksPlatform::strToPermalink($row[SearchFields_Resource::NAME])), true);
					
					$model = new Model_ExplorerSet();
					$model->hash = $hash;
					$model->pos = $pos++;
					$model->params = array(
						'id' => $row[SearchFields_Resource::ID],
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
