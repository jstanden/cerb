<?php

use Cerb\Extensions\Extension_QueueConsumer;

/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2026, Webgroup Media LLC
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

class PageSection_ProfilesQueue extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // queue 
		@$context_id = intval(array_shift($stack)); // 123
		
		$context = CerberusContexts::CONTEXT_QUEUE;
		
		Page_Profiles::renderProfile($context, $context_id, $stack);
	}
	
	function handleActionForPage(string $action, ?string $scope=null) {
		if('profileAction' == $scope) {
			switch($action) {
				case 'getExtensionConfig':
					return $this->_profileAction_getExtensionConfig();
				case 'savePeekJson':
					return $this->_profileAction_savePeekJson();
				case 'viewExplore':
					return $this->_profileAction_viewExplore();
			}
		}
		return false;
	}

	private function _profileAction_getExtensionConfig() {
		$active_worker = CerberusApplication::getActiveWorker();

		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);

		if(!$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);

		$extension_id = DevblocksPlatform::importGPC($_POST['extension_id'] ?? null, 'string', '');

		if(!$extension_id)
			return;

		if(!($queue_extension = Extension_QueueConsumer::get($extension_id, true)))
			DevblocksPlatform::dieWithHttpError(null, 404);

		$model = new Model_Queue();
		$model->extension_id = $extension_id;

		$queue_extension->renderConfig($model);
	}
	
	private function _profileAction_savePeekJson() {
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string', '');
		
		$id = DevblocksPlatform::importGPC($_POST['id'] ?? null, 'integer', 0);
		$do_delete = DevblocksPlatform::importGPC($_POST['do_delete'] ?? null, 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		
		try {
			if(!$active_worker->is_superuser)
				throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.admin'));
			
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_QUEUE)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				if(!($model = DAO_Queue::get($id)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.record.not_found'));
				
				// The `cerb.` namespace is reserved
				if(
					DevblocksPlatform::strStartsWith(DevblocksPlatform::strLower($model->name), 'cerb.')
					&& !DEVELOPMENT_MODE
				) {
					$error = 'The `cerb.` namespace is managed automatically. This queue may not be deleted.';
					throw new Exception_DevblocksAjaxValidationError($error);
				}
				
				if(!Context_Queue::isDeletableByActor($model, $active_worker))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				CerberusContexts::logActivityRecordDelete(Context_Queue::ID, $model->id, $model->name);
				
				DAO_Queue::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				$extension_id = DevblocksPlatform::importGPC($_POST['extension_id'] ?? null, 'string', '');
				$name = DevblocksPlatform::importGPC($_POST['name'] ?? null, 'string', '');
				$retry_max = DevblocksPlatform::importGPC($_POST['retry_max'] ?? null, 'integer', 0);
				$retry_window_secs = DevblocksPlatform::importGPC($_POST['retry_window_secs'] ?? null, 'integer', 86400);

				$error = null;

				$fields = [
					DAO_Queue::NAME => $name,
					DAO_Queue::RETRY_MAX => $retry_max,
					DAO_Queue::RETRY_WINDOW_SECS => $retry_window_secs,
					DAO_Queue::UPDATED_AT => time(),
				];
				
				if(empty($id)) { // New
					// The `cerb.` namespace is reserved
					if(
						DevblocksPlatform::strStartsWith(DevblocksPlatform::strLower($name), 'cerb.')
						&& !DEVELOPMENT_MODE
					) {
						$error = 'The `cerb.` namespace is reserved. Use your own prefix for `Name:`';
						throw new Exception_DevblocksAjaxValidationError($error);
					}
					
					$fields[DAO_Queue::CREATED_AT] = time();
					$fields[DAO_Queue::EXTENSION_ID] = $extension_id;
					
					if(!DAO_Queue::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_Queue::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					$id = DAO_Queue::create($fields);
					DAO_Queue::onUpdateByActor($active_worker, $fields, $id);
					
					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_QUEUE, $id);
					
				} else { // Edit
					// The `cerb.` namespace is reserved
					if(
						DevblocksPlatform::strStartsWith(DevblocksPlatform::strLower($name), 'cerb.')
						&& !DEVELOPMENT_MODE
					) {
						$error = 'The `cerb.` namespace is managed automatically. This queue is not editable.';
						throw new Exception_DevblocksAjaxValidationError($error);
					}

					if(!DAO_Queue::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_Queue::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					DAO_Queue::update($id, $fields);
					DAO_Queue::onUpdateByActor($active_worker, $fields, $id);
				}
				
				if($id) {
					// Let the queue's consumer extension persist its own params
					DAO_Queue::clearCache();
					if(($queue_model = DAO_Queue::get($id)) && ($queue_extension = $queue_model->getExtension())) {
						if(!$queue_extension->saveConfig($_POST, $id, $error))
							throw new Exception_DevblocksAjaxValidationError($error);
					}

					// Custom field saves
					$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'] ?? null, 'array', []);
					if(!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_QUEUE, $id, $field_ids, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
				}
				
				echo json_encode(array(
					'status' => true,
					'context' => CerberusContexts::CONTEXT_QUEUE,
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
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string', '');
		$explore_from = DevblocksPlatform::importGPC($_POST['explore_from'] ?? null, 'int', 0);
		
		$http_response = Cerb_ORMHelper::generateRecordExploreSet($view_id, $explore_from);
		DevblocksPlatform::redirect($http_response);
	}
};
