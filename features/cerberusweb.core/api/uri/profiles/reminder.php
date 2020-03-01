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

class PageSection_ProfilesReminder extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // reminder 
		@$context_id = intval(array_shift($stack)); // 123
		
		$context = CerberusContexts::CONTEXT_REMINDER;
		
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
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'], 'string', '');
		
		@$id = DevblocksPlatform::importGPC($_POST['id'], 'integer', 0);
		@$do_delete = DevblocksPlatform::importGPC($_POST['do_delete'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_REMINDER)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				if(false == ($model = DAO_Reminder::get($id)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.record.not_found'));
				
				if(!Context_Reminder::isDeletableByActor($model, $active_worker))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				CerberusContexts::logActivityRecordDelete(CerberusContexts::CONTEXT_REMINDER, $model->id, $model->name);
				
				DAO_Reminder::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				@$name = DevblocksPlatform::importGPC($_POST['name'], 'string', '');
				@$remind_at = DevblocksPlatform::importGPC($_POST['remind_at'], 'string', '');
				@$worker_id = DevblocksPlatform::importGPC($_POST['worker_id'], 'integer', 0);
				@$behavior_ids = DevblocksPlatform::sanitizeArray(DevblocksPlatform::importGPC($_POST['behavior_ids'], 'array', []), 'int');
				@$behaviors_params = DevblocksPlatform::importGPC($_POST['behavior_params'], 'array', []);
				
				$remind_at = !empty($remind_at) ? @strtotime($remind_at) : '';
				$is_closed = ($remind_at && $remind_at <= time()) ? 1 : 0;
				
				// Behaviors
				
				$params = [
					'behaviors' => [],
				];

				$behaviors = DAO_TriggerEvent::getIds($behavior_ids);
				
				foreach($behaviors as $behavior_id => $behavior) {
					$behavior_params = @$behaviors_params[$behavior_id] ?: [];
					$params['behaviors'][$behavior_id] = $behavior_params;
				}
				
				if(empty($id)) { // New
					$fields = array(
						DAO_Reminder::IS_CLOSED => $is_closed,
						DAO_Reminder::NAME => $name,
						DAO_Reminder::PARAMS_JSON => json_encode($params),
						DAO_Reminder::REMIND_AT => $remind_at,
						DAO_Reminder::UPDATED_AT => time(),
						DAO_Reminder::WORKER_ID => $worker_id,
					);
					
					if(!DAO_Reminder::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_Reminder::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					$id = DAO_Reminder::create($fields);
					DAO_Reminder::onUpdateByActor($active_worker, $fields, $id);
					
					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_REMINDER, $id);
					
				} else { // Edit
					if(false == ($reminder = DAO_Reminder::get($id)))
						throw new Exception_DevblocksAjaxValidationError("Invalid record.");
					
					if(!Context_Reminder::isWriteableByActor($reminder, $active_worker))
						throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.edit'));
						
					$fields = array(
						DAO_Reminder::IS_CLOSED => $is_closed,
						DAO_Reminder::NAME => $name,
						DAO_Reminder::PARAMS_JSON => json_encode($params),
						DAO_Reminder::REMIND_AT => $remind_at,
						DAO_Reminder::UPDATED_AT => time(),
						DAO_Reminder::WORKER_ID => $worker_id,
					);
					
					if(!DAO_Reminder::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_Reminder::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					DAO_Reminder::update($id, $fields);
					DAO_Reminder::onUpdateByActor($active_worker, $fields, $id);
				}
				
				// Custom field saves
				@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', []);
				if(!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_REMINDER, $id, $field_ids, $error))
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
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&type=reminder', true),
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $opp_id => $row) {
				if($opp_id==$explore_from)
					$orig_pos = $pos;
				
				$url = $url_writer->writeNoProxy(sprintf("c=profiles&type=reminder&id=%d-%s", $row[SearchFields_Reminder::ID], DevblocksPlatform::strToPermalink($row[SearchFields_Reminder::NAME])), true);
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_Reminder::ID],
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
