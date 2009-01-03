<?php
if (class_exists('Extension_ActivityTab')):
class ChTasksActivityTab extends Extension_ActivityTab {
	const VIEW_ACTIVITY_TASKS = 'activity_tasks';
	
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	function showTab() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl_path = realpath(dirname(__FILE__) . '/../../templates') . DIRECTORY_SEPARATOR;
		$tpl->assign('path', $tpl_path);
		
		$translate = DevblocksPlatform::getTranslationService();
		
		if(null == ($view = C4_AbstractViewLoader::getView('', self::VIEW_ACTIVITY_TASKS))) {
			$view = new C4_TaskView();
			$view->id = self::VIEW_ACTIVITY_TASKS;
			$view->renderSortBy = SearchFields_Task::DUE_DATE;
			$view->renderSortAsc = 1;
			
			$view->name = $translate->_('common.search_results');
			
			C4_AbstractViewLoader::setView($view->id, $view);
		}

		$tpl->assign('response_uri', 'activity/tasks');
		
		$tpl->assign('view', $view);
		$tpl->assign('view_fields', C4_TaskView::getFields());
		$tpl->assign('view_searchable_fields', C4_TaskView::getSearchFields());
		
		$tpl->display($tpl_path . 'tasks/activity_tab/index.tpl.php');		
	}
}
endif;

class ChTasksController extends DevblocksControllerExtension {
	function __construct($manifest) {
		parent::__construct($manifest);
		
		$router = DevblocksPlatform::getRoutingService();
		$router->addRoute('tasks','core.controller.tasks');
	}
		
	function isVisible() {
		// check login
		$visit = CerberusApplication::getVisit();
		
		if(empty($visit)) {
			return false;
		} else {
			return true;
		}
	}
	
	function handleRequest(DevblocksHttpRequest $request) {
		$worker = CerberusApplication::getActiveWorker();
		if(empty($worker)) return;
		
		$stack = $request->path;
		array_shift($stack); // internal
		
	    @$action = array_shift($stack) . 'Action';

	    switch($action) {
	        case NULL:
	            // [TODO] Index/page render
	            break;
	            
	        default:
			    // Default action, call arg as a method suffixed with Action
				if(method_exists($this,$action)) {
					call_user_func(array(&$this, $action));
				}
	            break;
	    }
	}
	
	function showTaskPeekAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer','');
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$link_namespace = DevblocksPlatform::importGPC($_REQUEST['link_namespace'],'string',''); // opt
		@$link_object_id = DevblocksPlatform::importGPC($_REQUEST['link_object_id'],'integer',0); // opt
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$path = realpath(dirname(__FILE__) . '/../../templates/') . DIRECTORY_SEPARATOR;
		$tpl->assign('path', $path);
		
		if(!empty($id)) {
			$task = DAO_Task::get($id);
			$tpl->assign('task', $task);
			
			if(!empty($task->source_extension) && !empty($task->source_id)) {
				if(null != ($mft = DevblocksPlatform::getExtension($task->source_extension))) {
					$source_info = $mft->createInstance();
					@$tpl->assign('source_info', $source_info->getSourceInfo($task->source_id));
				}
			}
		}

		// Only used on create
		if(!empty($link_namespace) && !empty($link_object_id)) {
			$tpl->assign('link_namespace', $link_namespace);
			$tpl->assign('link_object_id', $link_object_id);
		}
		
		$workers = DAO_Worker::getAllActive();
		$tpl->assign('workers', $workers);
		
		$tpl->assign('id', $id);
		$tpl->assign('view_id', $view_id);
		$tpl->display('file:' . $path . 'tasks/rpc/peek.tpl.php');
	}
	
	function saveTaskPeekAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer','');
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$link_namespace = DevblocksPlatform::importGPC($_REQUEST['link_namespace'],'string','');
		@$link_object_id = DevblocksPlatform::importGPC($_REQUEST['link_object_id'],'integer',0);
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'],'integer',0);

		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!empty($id) && !empty($do_delete)) { // delete
			$task = DAO_Task::get($id);
			if($active_worker->is_superuser || $active_worker->id == $task->worker_id) {
				DAO_Task::delete($id);
			}
			
		} else { // create|update
			$fields = array();
	
			// Title
			@$title = DevblocksPlatform::importGPC($_REQUEST['title'],'string','');
			if(!empty($title))
				$fields[DAO_Task::TITLE] = $title;
	
			// Completed
			@$completed = DevblocksPlatform::importGPC($_REQUEST['completed'],'integer',0);
			
			$fields[DAO_Task::IS_COMPLETED] = intval($completed);
			
			// [TODO] This shouldn't constantly update the completed date (it should compare)
			if($completed)
				$fields[DAO_Task::COMPLETED_DATE] = time();
			else
				$fields[DAO_Task::COMPLETED_DATE] = 0;
				
			// Due Date
			@$due_date = DevblocksPlatform::importGPC($_REQUEST['due_date'],'string','');
			@$fields[DAO_Task::DUE_DATE] = empty($due_date) ? 0 : intval(strtotime($due_date));		
	
			// Priority
			@$priority = DevblocksPlatform::importGPC($_REQUEST['priority'],'integer',4);
			@$fields[DAO_Task::PRIORITY] = intval($priority);
			
			// Worker
			@$worker_id = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'integer',0);
			@$fields[DAO_Task::WORKER_ID] = intval($worker_id);
			
			// Content
			@$content = DevblocksPlatform::importGPC($_REQUEST['content'],'string','');
			@$fields[DAO_Task::CONTENT] = $content;
	
			// Link to object (optional)
			if(!empty($link_namespace) && !empty($link_object_id)) {
				@$fields[DAO_Task::SOURCE_EXTENSION] = $link_namespace;
				@$fields[DAO_Task::SOURCE_ID] = $link_object_id;
			}
			
			// Save
			if(!empty($id)) {
				DAO_Task::update($id, $fields);
				
			} else {
				$id = DAO_Task::create($fields);

				// Write a notification (if not assigned to ourselves)
//				$url_writer = DevblocksPlatform::getUrlService();
				$source_extensions = DevblocksPlatform::getExtensions('cerberusweb.task.source', true);
				if(!empty($worker_id)) { // && $active_worker->id != $worker_id (Temporarily allow self notifications)
					if(null != (@$source_renderer = $source_extensions[$link_namespace])) { /* @var $source_renderer Extension_TaskSource */
						$source_info = $source_renderer->getSourceInfo($link_object_id);
						$source_name = $source_info['name'];
						$source_url = $source_info['url'];
						
						if(empty($source_name) || empty($source_url))
							break;
						
						$fields = array(
							DAO_WorkerEvent::CREATED_DATE => time(),
							DAO_WorkerEvent::WORKER_ID => $worker_id,
	//						DAO_WorkerEvent::URL => $url_writer->write('c=home&a=tasks',true),
							DAO_WorkerEvent::URL => $source_url,
							DAO_WorkerEvent::TITLE => 'New Task Assignment', // [TODO] Translate
							DAO_WorkerEvent::CONTENT => sprintf("%s\n%s says: %s",
								$source_name, 
								$active_worker->getName(),
								$title
							),
							DAO_WorkerEvent::IS_READ => 0,
						);
						DAO_WorkerEvent::create($fields);
					}
				}
			}
		}
		
		if(!empty($view_id) && null != ($view = C4_AbstractViewLoader::getView('', $view_id))) {
			$view->render();
		}
		
		exit;
	}
	
	function viewCompleteAction() {
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string','');
		@$ids = DevblocksPlatform::importGPC($_POST['row_id'],'array',array());
		
		if(!empty($ids)) {
			$fields = array(
				DAO_Task::IS_COMPLETED => 1,
				DAO_Task::COMPLETED_DATE => time()
			);
			DAO_Task::update($ids, $fields);
		}

		$view = C4_AbstractViewLoader::getView('',$view_id);
	    $view->render();
	    return;
	}
	
	function viewDeleteAction() {
		@$ids = DevblocksPlatform::importGPC($_POST['row_id'],'array',array());
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string','');
		
		if(!empty($ids))
			DAO_Task::delete($ids);
		
		$view = C4_AbstractViewLoader::getView('',$view_id);
	    $view->render();
	    return;
	}
	
	function viewPostponeAction() {
		@$ids = DevblocksPlatform::importGPC($_POST['row_id'],'array',array());
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string','');
		
		if(!empty($ids)) {
			$tasks = DAO_Task::getWhere(sprintf("%s IN (%s)",
				DAO_Task::ID,
				implode(',', $ids)
			));
			
			foreach($tasks as $task) {
				/*
				 * [JAS]: If an existing due date exists and isn't expired, do a  
				 * relative postpone. Otherwise use today as the starting point.
				 */
				$time = ($task->due_date && $task->due_date > time()) ? $task->due_date : time();
				
				$fields = array(
					DAO_Task::DUE_DATE => strtotime('+24 hours',$time)
				);
				DAO_Task::update($task->id, $fields);
			}
		}
		
		$view = C4_AbstractViewLoader::getView('',$view_id);
	    $view->render();
	    return;
	}
	
	function viewDueTodayAction() {
		@$ids = DevblocksPlatform::importGPC($_POST['row_id'],'array',array());
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string','');

		if(!empty($ids)) {
			$fields = array(
				DAO_Task::DUE_DATE => intval(strtotime("tomorrow"))
			);
			DAO_Task::update($ids, $fields);
		}
		
		$view = C4_AbstractViewLoader::getView('',$view_id);
	    $view->render();
	    return;
	}
	
	function viewTakeAction() {
		@$ids = DevblocksPlatform::importGPC($_POST['row_id'],'array',array());
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string','');

		$active_worker = CerberusApplication::getActiveWorker();

		if(!empty($ids)) {
			// Only unassigned
			$where = sprintf("%s IN (%s) AND %s = %d",
				DAO_Task::ID,
				implode(',', $ids),
				DAO_Task::WORKER_ID,
				0
			);
			
			$fields = array(
				DAO_Task::WORKER_ID => intval($active_worker->id)
			);
			DAO_Task::updateWhere($fields, $where);
		}
		
		$view = C4_AbstractViewLoader::getView('',$view_id);
	    $view->render();
	    return;
	}
	
	function viewSurrenderAction() {
		@$ids = DevblocksPlatform::importGPC($_POST['row_id'],'array',array());
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string','');

		$active_worker = CerberusApplication::getActiveWorker();

		if(!empty($ids)) {
			// Only unassigned
			$where = sprintf("%s IN (%s) AND %s = %d",
				DAO_Task::ID,
				implode(',', $ids),
				DAO_Task::WORKER_ID,
				$active_worker->id
			);
			
			$fields = array(
				DAO_Task::WORKER_ID => 0
			);
			DAO_Task::updateWhere($fields, $where);
		}
		
		$view = C4_AbstractViewLoader::getView('',$view_id);
	    $view->render();
	    return;
	}
	
	function viewPriorityHighAction() {
		@$ids = DevblocksPlatform::importGPC($_POST['row_id'],'array',array());
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string','');

		if(!empty($ids)) {
			$fields = array(
				DAO_Task::PRIORITY => 1 // [TODO] These should be Model_Task constants
			);
			DAO_Task::update($ids, $fields);
		}
		
		$view = C4_AbstractViewLoader::getView('',$view_id);
	    $view->render();
	    return;
	}
	
	function viewPriorityNormalAction() {
		@$ids = DevblocksPlatform::importGPC($_POST['row_id'],'array',array());
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string','');

		if(!empty($ids)) {
			$fields = array(
				DAO_Task::PRIORITY => 2
			);
			DAO_Task::update($ids, $fields);
		}
		
		$view = C4_AbstractViewLoader::getView('',$view_id);
	    $view->render();
	    return;
	}
	
	function viewPriorityLowAction() {
		@$ids = DevblocksPlatform::importGPC($_POST['row_id'],'array',array());
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string','');

		if(!empty($ids)) {
			$fields = array(
				DAO_Task::PRIORITY => 3
			);
			DAO_Task::update($ids, $fields);
		}
		
		$view = C4_AbstractViewLoader::getView('',$view_id);
	    $view->render();
	    return;
	}
	
	function viewPriorityNoneAction() {
		@$ids = DevblocksPlatform::importGPC($_POST['row_id'],'array',array());
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string','');
		
		if(!empty($ids)) {
			$fields = array(
				DAO_Task::PRIORITY => 4
			);
			DAO_Task::update($ids, $fields);
		}

		$view = C4_AbstractViewLoader::getView('',$view_id);
	    $view->render();
	    return;
	}
};
