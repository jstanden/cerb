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

class PageSection_ProfilesTask extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // task
		@$context_id = intval(array_shift($stack));
		
		$context = CerberusContexts::CONTEXT_TASK;
		
		Page_Profiles::renderProfile($context, $context_id, $stack);
	}
	
	function savePeekJsonAction() {
		@$id = DevblocksPlatform::importGPC($_POST['id'],'integer','');
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string','');
		@$do_delete = DevblocksPlatform::importGPC($_POST['do_delete'],'integer',0);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(403);
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(!empty($id) && !empty($do_delete)) { // delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_TASK)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				DAO_Task::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else { // create/edit
				@$package_uri = DevblocksPlatform::importGPC($_POST['package'], 'string', '');
				
				$mode = 'build';
				$error = null;
				
				if(!$id && $package_uri)
					$mode = 'library';
				
				switch($mode) {
					case 'library':
						@$prompts = DevblocksPlatform::importGPC($_POST['prompts'], 'array', []);
						
						if(empty($package_uri))
							throw new Exception_DevblocksAjaxValidationError("You must select a package from the library.");
						
						if(false == ($package = DAO_PackageLibrary::getByUri($package_uri)))
							throw new Exception_DevblocksAjaxValidationError("You selected an invalid package.");
						
						if($package->point != 'task')
							throw new Exception_DevblocksAjaxValidationError("The selected package is not for this extension point.");
						
						$package_json = $package->getPackageJson();
						$records_created = [];
						
						$prompts['current_worker_id'] = $active_worker->id;
						
						try {
							CerberusApplication::packages()->import($package_json, $prompts, $records_created);
							
						} catch(Exception_DevblocksValidationError $e) {
							throw new Exception_DevblocksAjaxValidationError($e->getMessage());
							
						} catch (Exception $e) {
							throw new Exception_DevblocksAjaxValidationError("An unexpected error occurred.");
						}
						
						if(!array_key_exists(CerberusContexts::CONTEXT_TASK, $records_created))
							throw new Exception_DevblocksAjaxValidationError("There was an issue creating the record.");
						
						$new_task = reset($records_created[CerberusContexts::CONTEXT_TASK]);
						
						if($view_id)
							C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_TASK, $new_task['id']);
						
						echo json_encode([
							'status' => true,
							'id' => $new_task['id'],
							'label' => $new_task['label'],
							'view_id' => $view_id,
						]);
						return;
						break;
						
					case 'build':
						// Load the existing model so we can detect changes
						if($id && false == ($task = DAO_Task::get($id)))
							throw new Exception_DevblocksAjaxValidationError("There was an unexpected error when loading this record.");
						
						$fields = [];
			
						// Title
						@$title = DevblocksPlatform::importGPC($_POST['title'],'string','');
						
						$fields[DAO_Task::TITLE] = $title;
						
						// Completed
						@$status_id = DevblocksPlatform::importGPC($_POST['status_id'],'integer',0);
						$status_id = DevblocksPlatform::intClamp($status_id, 0, 2);
						$fields[DAO_Task::STATUS_ID] = $status_id;
						
						if($id && $task->status_id != $status_id) {
							if(1 == $status_id) {
								$fields[DAO_Task::COMPLETED_DATE] = time();
							} else {
								$fields[DAO_Task::COMPLETED_DATE] = 0;
							}
						}
						
						// Updated Date
						$fields[DAO_Task::UPDATED_DATE] = time();
						
						// Reopen Date
						@$reopen_at = DevblocksPlatform::importGPC($_POST['reopen_at'],'string','');
						@$fields[DAO_Task::REOPEN_AT] = empty($reopen_at) ? 0 : intval(strtotime($reopen_at));
						
						// Due Date
						@$due_date = DevblocksPlatform::importGPC($_POST['due_date'],'string','');
						@$fields[DAO_Task::DUE_DATE] = empty($due_date) ? 0 : intval(strtotime($due_date));
				
						// Importance
						@$importance = DevblocksPlatform::importGPC($_POST['importance'],'integer',0);
						$fields[DAO_Task::IMPORTANCE] = $importance;
						
						// Owner
						@$owner_id = DevblocksPlatform::importGPC($_POST['owner_id'],'integer',0);
						$fields[DAO_Task::OWNER_ID] = $owner_id;
				
						// Save
						if(!empty($id)) {
							if(!DAO_Task::validate($fields, $error, $id))
								throw new Exception_DevblocksAjaxValidationError($error);
							
							if(!DAO_Task::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
								throw new Exception_DevblocksAjaxValidationError($error);
							
							DAO_Task::update($id, $fields);
							DAO_Task::onUpdateByActor($active_worker, $fields, $id);
							
						} else {
							if(!DAO_Task::validate($fields, $error))
								throw new Exception_DevblocksAjaxValidationError($error);
							
							if(!DAO_Task::onBeforeUpdateByActor($active_worker, $fields, null, $error))
								throw new Exception_DevblocksAjaxValidationError($error);
							
							if(false == ($id = DAO_Task::create($fields)))
								return false;
							
							DAO_Task::onUpdateByActor($active_worker, $fields, $id);
							
							// Watchers
							@$add_watcher_ids = DevblocksPlatform::sanitizeArray(DevblocksPlatform::importGPC($_POST ['add_watcher_ids'], 'array', []), 'integer', ['unique','nonzero']);
							if(!empty($add_watcher_ids))
								CerberusContexts::addWatchers(CerberusContexts::CONTEXT_TASK, $id, $add_watcher_ids);
			
							// View marquee
							if(!empty($id) && !empty($view_id)) {
								C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_TASK, $id);
							}
						}
			
						if($id) {
							// Comments
							DAO_Comment::handleFormPost(CerberusContexts::CONTEXT_TASK, $id);
						}
						
						// Custom field saves
						@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', []);
						if(!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_TASK, $id, $field_ids, $error))
							throw new Exception_DevblocksAjaxValidationError($error);
						
						echo json_encode(array(
							'status' => true,
							'id' => $id,
							'label' => $title,
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
	
	function showBulkPopupAction() {
		@$ids = DevblocksPlatform::importGPC($_REQUEST['ids']);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);

		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('view_id', $view_id);

		if(!empty($ids)) {
			$id_list = DevblocksPlatform::parseCsvString($ids);
			$tpl->assign('ids', implode(',', $id_list));
		}
		
		$workers = DAO_Worker::getAllActive();
		$tpl->assign('workers', $workers);
		
		// Custom Fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_TASK, false);
		$tpl->assign('custom_fields', $custom_fields);
		
		$tpl->display('devblocks:cerberusweb.core::tasks/rpc/bulk.tpl');
	}
	
	function startBulkUpdateJsonAction() {
		// Filter: whole list or check
		@$filter = DevblocksPlatform::importGPC($_POST['filter'],'string','');
		$ids = [];
		
		// View
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);
		
		// Actions
		@$actions = DevblocksPlatform::importGPC($_POST['actions'],'array',array());
		@$params = DevblocksPlatform::importGPC($_POST['params'],'array',array());
		
		// Scheduled behavior
		@$behavior_id = DevblocksPlatform::importGPC($_POST['behavior_id'],'string','');
		@$behavior_when = DevblocksPlatform::importGPC($_POST['behavior_when'],'string','');
		@$behavior_params = DevblocksPlatform::importGPC($_POST['behavior_params'],'array',array());
		
		$do = array();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(is_array($actions))
		foreach($actions as $action) {
			switch($action) {
				case 'due':
				case 'importance':
				case 'owner':
					if(isset($params[$action]))
						$do[$action] = $params[$action];
					break;
					
				case 'status':
					if(isset($params[$action])) {
						switch($params[$action]) {
							case '2':
								if($active_worker->hasPriv('contexts.cerberusweb.contexts.task.delete'))
									$do['delete'] = true;
									break;
								break;
								
							default:
								$do[$action] = $params[$action];
								break;
						}
					}
					break;
					
				case 'watchers_add':
				case 'watchers_remove':
					if(!isset($params[$action]))
						break;
						
					if(!isset($do['watchers']))
						$do['watchers'] = array();
					
					$do['watchers'][substr($action,9)] = $params[$action];
					break;
			}
		}
		
		// Do: Scheduled Behavior
		if(0 != strlen($behavior_id)) {
			$do['behavior'] = array(
				'id' => $behavior_id,
				'when' => $behavior_when,
				'params' => $behavior_params,
			);
		}
		
		// Do: Custom fields
		$do = DAO_CustomFieldValue::handleBulkPost($do);

		switch($filter) {
			// Checked rows
			case 'checks':
				@$ids_str = DevblocksPlatform::importGPC($_POST['ids'],'string');
				$ids = DevblocksPlatform::parseCsvString($ids_str);
				break;
				
			case 'sample':
				@$sample_size = min(DevblocksPlatform::importGPC($_POST['filter_sample_size'],'integer',0),9999);
				$filter = 'checks';
				$ids = $view->getDataSample($sample_size);
				break;
				
			default:
				break;
		}
		
		// If we have specific IDs, add a filter for those too
		if(!empty($ids)) {
			$view->addParams([
				new DevblocksSearchCriteria(SearchFields_Task::ID, 'in', $ids)
			], true);
		}
		
		// Create batches
		$batch_key = DAO_ContextBulkUpdate::createFromView($view, $do);
		
		header('Content-Type: application/json; charset=utf-8');
		
		echo json_encode(array(
			'cursor' => $batch_key,
		));
		
		return;
	}
	
	function viewMarkCompletedAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		@$row_ids = DevblocksPlatform::importGPC($_REQUEST['row_id'],'array',array());

		try {
			if(is_array($row_ids))
			foreach($row_ids as $row_id) {
				$row_id = intval($row_id);
				
				if(!empty($row_id))
					DAO_Task::update($row_id, array(
						DAO_Task::STATUS_ID => 1,
						DAO_Task::COMPLETED_DATE => time(),
					));
			}
		} catch (Exception $e) {
			//
		}
		
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->render();
		exit;
	}
	
	function viewTasksExploreAction() {
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$url_writer = DevblocksPlatform::services()->url();
		
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
					'total' => $total,
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&type=task', true),
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $task_id => $row) {
				if($task_id==$explore_from)
					$orig_pos = $pos;
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_Task::ID],
					'url' => $url_writer->writeNoProxy(sprintf("c=profiles&type=task&id=%d", $row[SearchFields_Task::ID]), true),
				);
				$models[] = $model;
			}
			
			DAO_ExplorerSet::createFromModels($models);
			
			$view->renderPage++;
			
		} while(!empty($results));
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('explore',$hash,$orig_pos)));
	}
	
};