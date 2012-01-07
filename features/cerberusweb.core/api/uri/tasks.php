<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2011, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerberusweb.com/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
***********************************************************************/

if (class_exists('Extension_ActivityTab')):
class ChTasksActivityTab extends Extension_ActivityTab {
	const VIEW_ACTIVITY_TASKS = 'activity_tasks';
	
	function showTab() {
		$tpl = DevblocksPlatform::getTemplateService();
		$translate = DevblocksPlatform::getTranslationService();
		
		$defaults = new C4_AbstractViewModel();
		$defaults->class_name = 'View_Task';
		$defaults->id = self::VIEW_ACTIVITY_TASKS;
		$defaults->name = $translate->_('activity.tab.tasks');
		$defaults->renderSortBy = SearchFields_Task::DUE_DATE;
		$defaults->renderSortAsc = true;
		
		$view = C4_AbstractViewLoader::getView(self::VIEW_ACTIVITY_TASKS, $defaults);
		$tpl->assign('view', $view);

		$tpl->display('devblocks:cerberusweb.core::tasks/activity_tab/index.tpl');		
	}
}
endif;

class ChTasksPage extends CerberusPageExtension {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();

		$visit = CerberusApplication::getVisit();
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		
		array_shift($stack); // tasks
		
		$module = array_shift($stack); // display
		
		switch($module) {
			default:
			case 'display':
				@$task_id = intval(array_shift($stack));
				if(null == ($task = DAO_Task::get($task_id))) {
					break; // [TODO] Not found
				}
				$tpl->assign('task', $task);			

				if(null == (@$tab_selected = $stack[0])) {
//					$tab_selected = $visit->get(self::SESSION_OPP_TAB, '');
				}
				$tpl->assign('tab_selected', $tab_selected);

				// Custom fields
				
				$custom_fields = DAO_CustomField::getAll();
				$tpl->assign('custom_fields', $custom_fields);
				
				// Properties
				
				$properties = array();
				
				$properties['is_completed'] = array(
					'label' => ucfirst($translate->_('task.is_completed')),
					'type' => Model_CustomField::TYPE_CHECKBOX,
					'value' => $task->is_completed,
				);
				
				if(!$task->is_completed) {
					$properties['due_date'] = array(
						'label' => ucfirst($translate->_('task.due_date')),
						'type' => Model_CustomField::TYPE_DATE,
						'value' => $task->due_date,
					);
				} else {
					$properties['completed_date'] = array(
						'label' => ucfirst($translate->_('task.completed_date')),
						'type' => Model_CustomField::TYPE_DATE,
						'value' => $task->completed_date,
					);
				}
				
				$properties['updated_date'] = array(
					'label' => ucfirst($translate->_('common.updated')),
					'type' => Model_CustomField::TYPE_DATE,
					'value' => $task->updated_date,
				);
				
				@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_TASK, $task->id)) or array();
		
				foreach($custom_fields as $cf_id => $cfield) {
					if(!isset($values[$cf_id]))
						continue;
						
					$properties['cf_' . $cf_id] = array(
						'label' => $cfield->name,
						'type' => $cfield->type,
						'value' => $values[$cf_id],
					);
				}
				
				$tpl->assign('properties', $properties);				
				
				$workers = DAO_Worker::getAll();
				$tpl->assign('workers', $workers);
				
				// Macros
				$macros = DAO_TriggerEvent::getByOwner(CerberusContexts::CONTEXT_WORKER, $active_worker->id, 'event.macro.task');
				$tpl->assign('macros', $macros);
				
				$tpl->display('devblocks:cerberusweb.core::tasks/display/index.tpl');
				break;
		}
	}

	function isVisible() {
		// The current session must be a logged-in worker to use this page.
		if(null == ($worker = CerberusApplication::getActiveWorker()))
			return false;
		return true;
	}
	
	function showTaskPeekAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer','');
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		
		$tpl = DevblocksPlatform::getTemplateService();

		// Handle context links ([TODO] as an optional array)
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer','');
		$tpl->assign('context', $context);
		$tpl->assign('context_id', $context_id);
		
		if(!empty($id)) {
			$task = DAO_Task::get($id);
			$tpl->assign('task', $task);
		}

		// Custom fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_TASK); 
		$tpl->assign('custom_fields', $custom_fields);

		$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_TASK, $id);
		if(isset($custom_field_values[$id]))
			$tpl->assign('custom_field_values', $custom_field_values[$id]);
		
		$types = Model_CustomField::getTypes();
		$tpl->assign('types', $types);

		// Comments
		$comments = DAO_Comment::getByContext(CerberusContexts::CONTEXT_TASK, $id);
		$last_comment = array_shift($comments);
		unset($comments);
		$tpl->assign('last_comment', $last_comment);

		// View
		$tpl->assign('id', $id);
		$tpl->assign('view_id', $view_id);
		$tpl->display('devblocks:cerberusweb.core::tasks/rpc/peek.tpl');
	}
	
	function saveTaskPeekAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer','');
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'],'integer',0);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!empty($id) && !empty($do_delete)) { // delete
			$task = DAO_Task::get($id);

			// Check privs
			if($active_worker->hasPriv('core.tasks.actions.delete'))
				DAO_Task::delete($id);
			
		} else { // create|update
			$fields = array();
	
			// Title
			@$title = DevblocksPlatform::importGPC($_REQUEST['title'],'string','');
			$fields[DAO_Task::TITLE] = !empty($title) ? $title : 'New Task';
	
			// Completed
			@$completed = DevblocksPlatform::importGPC($_REQUEST['completed'],'integer',0);
			
			$fields[DAO_Task::IS_COMPLETED] = intval($completed);
			
			// [TODO] This shouldn't constantly update the completed date (it should compare)
			if($completed)
				$fields[DAO_Task::COMPLETED_DATE] = time();
			else
				$fields[DAO_Task::COMPLETED_DATE] = 0;
			
			// Updated Date
			$fields[DAO_Task::UPDATED_DATE] = time();
			
			// Due Date
			@$due_date = DevblocksPlatform::importGPC($_REQUEST['due_date'],'string','');
			@$fields[DAO_Task::DUE_DATE] = empty($due_date) ? 0 : intval(strtotime($due_date));		
	
			// Comment
			@$comment = DevblocksPlatform::importGPC($_REQUEST['comment'],'string','');
			
			// Save
			if(!empty($id)) {
				DAO_Task::update($id, $fields);
				
			} else {
				$id = DAO_Task::create($fields);

				@$is_watcher = DevblocksPlatform::importGPC($_REQUEST['is_watcher'],'integer',0);
				if($is_watcher)
					CerberusContexts::addWatchers(CerberusContexts::CONTEXT_TASK, $id, $active_worker->id);
				
				// Context Link (if given)
				@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
				@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer','');
				if(!empty($id) && !empty($context) && !empty($context_id)) {
					DAO_ContextLink::setLink(CerberusContexts::CONTEXT_TASK, $id, $context, $context_id);
				}
			}

			// Custom field saves
			@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', array());
			DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_TASK, $id, $field_ids);
			
			// Comments				
			if(!empty($comment) && !empty($id)) {
				@$also_notify_worker_ids = DevblocksPlatform::importGPC($_REQUEST['notify_worker_ids'],'array',array());
				
				$fields = array(
					DAO_Comment::CONTEXT => CerberusContexts::CONTEXT_TASK,
					DAO_Comment::CONTEXT_ID => $id,
					DAO_Comment::ADDRESS_ID => $active_worker->getAddress()->id,
					DAO_Comment::CREATED => time(),
					DAO_Comment::COMMENT => $comment,
				);
				$comment_id = DAO_Comment::create($fields, $also_notify_worker_ids);
			}
		}
		
		if(!empty($view_id) && null != ($view = C4_AbstractViewLoader::getView($view_id))) {
			$view->render();
		}
		
		exit;
	}
	
	function showTaskBulkPanelAction() {
		@$ids = DevblocksPlatform::importGPC($_REQUEST['ids']);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);

		$active_worker = CerberusApplication::getActiveWorker();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);

	    if(!empty($ids)) {
	        $id_list = DevblocksPlatform::parseCsvString($ids);
	        $tpl->assign('ids', implode(',', $id_list));
	    }
		
	    $workers = DAO_Worker::getAllActive();
	    $tpl->assign('workers', $workers);
	    
		// Custom Fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_TASK);
		$tpl->assign('custom_fields', $custom_fields);
		
		// Macros
		$macros = DAO_TriggerEvent::getByOwner(CerberusContexts::CONTEXT_WORKER, $active_worker->id, 'event.macro.task');
		$tpl->assign('macros', $macros);
		
		$tpl->display('devblocks:cerberusweb.core::tasks/rpc/bulk.tpl');
	}
	
	function doTaskBulkUpdateAction() {
		// Filter: whole list or check
	    @$filter = DevblocksPlatform::importGPC($_REQUEST['filter'],'string','');
		$ids = array();
	    
	    // View
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView($view_id);
		
		// Task fields
		@$due = trim(DevblocksPlatform::importGPC($_POST['due'],'string',''));
		@$status = trim(DevblocksPlatform::importGPC($_POST['status'],'string',''));

		// Scheduled behavior
		@$behavior_id = DevblocksPlatform::importGPC($_POST['behavior_id'],'string','');
		@$behavior_when = DevblocksPlatform::importGPC($_POST['behavior_when'],'string','');
		
		$do = array();
		$active_worker = CerberusApplication::getActiveWorker();
		
		// Do: Due
		if(0 != strlen($due))
			$do['due'] = $due;
			
		// Do: Status
		if(0 != strlen($status)) {
			switch($status) {
				case 2: // deleted
					if($active_worker->hasPriv('core.tasks.actions.delete'))
						$do['delete'] = true;
					break;
				default:
					$do['status'] = $status;
					break;
			}
		}
			
		// Do: Scheduled Behavior
		if(0 != strlen($behavior_id)) {
			$do['behavior'] = array(
				'id' => $behavior_id,
				'when' => $behavior_when,
			);
		}
		
		// Watchers
		$watcher_params = array();
		
		@$watcher_add_ids = DevblocksPlatform::importGPC($_REQUEST['do_watcher_add_ids'],'array',array());
		if(!empty($watcher_add_ids))
			$watcher_params['add'] = $watcher_add_ids;
			
		@$watcher_remove_ids = DevblocksPlatform::importGPC($_REQUEST['do_watcher_remove_ids'],'array',array());
		if(!empty($watcher_remove_ids))
			$watcher_params['remove'] = $watcher_remove_ids;
		
		if(!empty($watcher_params))
			$do['watchers'] = $watcher_params;
			
		// Do: Custom fields
		$do = DAO_CustomFieldValue::handleBulkPost($do);

		switch($filter) {
			// Checked rows
			case 'checks':
			    @$ids_str = DevblocksPlatform::importGPC($_REQUEST['ids'],'string');
				$ids = DevblocksPlatform::parseCsvString($ids_str);
				break;
			case 'sample':
				@$sample_size = min(DevblocksPlatform::importGPC($_REQUEST['filter_sample_size'],'integer',0),9999);
				$filter = 'checks';
				$ids = $view->getDataSample($sample_size);
				break;
			default:
				break;
		}
		
		$view->doBulkUpdate($filter, $do, $ids);
		
		$view->render();
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
						DAO_Task::IS_COMPLETED => 1,
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
	
	function doQuickSearchAction() {
		@$type = DevblocksPlatform::importGPC($_POST['type'],'string');
		@$query = DevblocksPlatform::importGPC($_POST['query'],'string');
	
		$query = trim($query);
	
		$defaults = new C4_AbstractViewModel();
		$defaults->class_name = 'View_Task';
		$defaults->id = ChTasksActivityTab::VIEW_ACTIVITY_TASKS;
		$view = C4_AbstractViewLoader::getView($defaults->id, $defaults);
	
		$params = array();
		if(!is_numeric($query))
			if($query && false===strpos($query,'*'))
				$query = '*' . $query . '*';
	
		switch($type) {
			case "title":
				$params[SearchFields_Task::TITLE] = new DevblocksSearchCriteria(SearchFields_Task::TITLE, DevblocksSearchCriteria::OPER_LIKE, strtolower($query));
				break;
		}
	
		$view->addParams($params, false); // Add, don't replace
		$view->renderPage = 0;
		$view->renderSortBy = null;
	
		C4_AbstractViewLoader::setView($defaults->id,$view);
	
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('activity','tasks')));
	}	
	
	function viewTasksExploreAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$url_writer = DevblocksPlatform::getUrlService();
		
		// Generate hash
		$hash = md5($view_id.$active_worker->id.time()); 
		
		// Loop through view and get IDs
		$view = C4_AbstractViewLoader::getView($view_id);

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
					//'worker_id' => $active_worker->id,
					'total' => $total,
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=activity&tab=tasks', true),
//					'toolbar_extension_id' => 'cerberusweb.explorer.toolbar.',
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
					'url' => $url_writer->writeNoProxy(sprintf("c=tasks&tab=display&id=%d", $row[SearchFields_Task::ID]), true),
				);
				$models[] = $model; 
			}
			
			DAO_ExplorerSet::createFromModels($models);
			
			$view->renderPage++;
			
		} while(!empty($results));
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('explore',$hash,$orig_pos)));
	}
};
