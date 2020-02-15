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

class PageSection_ProfilesProjectBoardColumn extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // project_board_column 
		@$context_id = intval(array_shift($stack)); // 123

		$context = Context_ProjectBoardColumn::ID;
		
		Page_Profiles::renderProfile($context, $context_id, $stack);
	}
	
	function savePeekJsonAction() {
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'], 'string', '');
		
		@$id = DevblocksPlatform::importGPC($_POST['id'], 'integer', 0);
		@$do_delete = DevblocksPlatform::importGPC($_POST['do_delete'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(403);
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", Context_ProjectBoardColumn::ID)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				DAO_ProjectBoardColumn::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				@$name = DevblocksPlatform::importGPC($_POST['name'], 'string', '');
				@$board_id = DevblocksPlatform::importGPC($_POST['board_id'], 'integer', 0);
				@$actions = DevblocksPlatform::sanitizeArray(DevblocksPlatform::importGPC($_POST['actions'], 'array', []), 'string');
				@$actions_params = DevblocksPlatform::importGPC($_POST['action_params'], 'array', []);
				@$behavior_ids = DevblocksPlatform::sanitizeArray(DevblocksPlatform::importGPC($_POST['behavior_ids'], 'array', []), 'int');
				@$behaviors_params = DevblocksPlatform::importGPC($_POST['behavior_params'], 'array', []);
				
				$params = [
					'actions' => [],
					'behaviors' => [],
				];
				
				$error = null;
				
				// Actions
				
				foreach($actions as $action) {
					$action_params = @$actions_params[$action] ?: [];
					$params['actions'][$action] = $action_params;
				}
				
				// Behaviors
				
				$behaviors = DAO_TriggerEvent::getIds($behavior_ids);
				
				foreach(array_keys($behaviors) as $behavior_id) {
					$behavior_params = @$behaviors_params[$behavior_id] ?: [];
					$params['behaviors'][$behavior_id] = $behavior_params;
				}
				
				// DAO
				
				if(empty($id)) { // New
					$fields = array(
						DAO_ProjectBoardColumn::UPDATED_AT => time(),
						DAO_ProjectBoardColumn::BOARD_ID => $board_id,
						DAO_ProjectBoardColumn::NAME => $name,
						DAO_ProjectBoardColumn::PARAMS_JSON => json_encode($params),
					);
					
					if(!DAO_ProjectBoardColumn::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_ProjectBoardColumn::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					$id = DAO_ProjectBoardColumn::create($fields);
					DAO_ProjectBoardColumn::onUpdateByActor($active_worker, $fields, $id);
					
					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, Context_ProjectBoardColumn::ID, $id);
					
				} else { // Edit
					$fields = array(
						DAO_ProjectBoardColumn::UPDATED_AT => time(),
						DAO_ProjectBoardColumn::BOARD_ID => $board_id,
						DAO_ProjectBoardColumn::NAME => $name,
						DAO_ProjectBoardColumn::PARAMS_JSON => json_encode($params),
					);
					
					if(!DAO_ProjectBoardColumn::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_ProjectBoardColumn::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					DAO_ProjectBoardColumn::update($id, $fields);
					DAO_ProjectBoardColumn::onUpdateByActor($active_worker, $fields, $id);
				}
				
				// Custom field saves
				@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', []);
				if(!DAO_CustomFieldValue::handleFormPost(Context_ProjectBoardColumn::ID, $id, $field_ids, $error))
					throw new Exception_DevblocksAjaxValidationError($error);
					
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'context' => Context_ProjectBoardColumn::ID,
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
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&type=project_board_column', true),
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $opp_id => $row) {
				if($opp_id==$explore_from)
					$orig_pos = $pos;
				
				$url = $url_writer->writeNoProxy(sprintf("c=profiles&type=project_board_column&id=%d-%s", $row[SearchFields_ProjectBoardColumn::ID], DevblocksPlatform::strToPermalink($row[SearchFields_ProjectBoardColumn::NAME])), true);
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_ProjectBoardColumn::ID],
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
