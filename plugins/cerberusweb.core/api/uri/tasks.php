<?php
if (class_exists('Extension_ActivityTab')):
class ChTasksActivityTab extends Extension_ActivityTab {
	const VIEW_ACTIVITY_TASKS = 'activity_tasks';
	
	private $_TPL_PATH = '';
	
	function __construct($manifest) {
		$this->_TPL_PATH = dirname(dirname(dirname(__FILE__))) . '/templates/';
		parent::__construct($manifest);
	}
	
	function showTab() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl_path = $this->_TPL_PATH;
		$tpl->assign('path', $tpl_path);
		
		$translate = DevblocksPlatform::getTranslationService();
		
		if(null == ($view = C4_AbstractViewLoader::getView('', self::VIEW_ACTIVITY_TASKS))) {
			$view = new C4_TaskView();
			$view->id = self::VIEW_ACTIVITY_TASKS;
			$view->renderSortBy = SearchFields_Task::DUE_DATE;
			$view->renderSortAsc = 1;
			
			$view->name = $translate->_('activity.tab.tasks');
			
			C4_AbstractViewLoader::setView($view->id, $view);
		}

		$tpl->assign('response_uri', 'activity/tasks');
		
		$tpl->assign('view', $view);
		$tpl->assign('view_fields', C4_TaskView::getFields());
		$tpl->assign('view_searchable_fields', C4_TaskView::getSearchFields());
		
		$tpl->display($tpl_path . 'tasks/activity_tab/index.tpl');		
	}
}
endif;

class ChTasksController extends DevblocksControllerExtension {
	private $_TPL_PATH = '';
	
	function __construct($manifest) {
		$this->_TPL_PATH = dirname(dirname(dirname(__FILE__))) . '/templates/';
		parent::__construct($manifest);
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
		$path = $this->_TPL_PATH;
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
		
		// Custom fields
		$custom_fields = DAO_CustomField::getBySource(ChCustomFieldSource_Task::ID); 
		$tpl->assign('custom_fields', $custom_fields);

		$custom_field_values = DAO_CustomFieldValue::getValuesBySourceIds(ChCustomFieldSource_Task::ID, $id);
		if(isset($custom_field_values[$id]))
			$tpl->assign('custom_field_values', $custom_field_values[$id]);
		
		$types = Model_CustomField::getTypes();
		$tpl->assign('types', $types);

		// View
		$tpl->assign('id', $id);
		$tpl->assign('view_id', $view_id);
		$tpl->display('file:' . $path . 'tasks/rpc/peek.tpl');
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

			// Check privs
			if(($active_worker->hasPriv('core.tasks.actions.create') && $active_worker->id==$task->worker_id)
				|| ($active_worker->hasPriv('core.tasks.actions.update_nobody') && empty($task->worker_id)) 
				|| $active_worker->hasPriv('core.tasks.actions.update_all'))
					DAO_Task::delete($id);
			
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
			
			// Custom field saves
			@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', array());
			DAO_CustomFieldValue::handleFormPost(ChCustomFieldSource_Task::ID, $id, $field_ids);
		}
		
		if(!empty($view_id) && null != ($view = C4_AbstractViewLoader::getView('', $view_id))) {
			$view->render();
		}
		
		exit;
	}
	
	function showTaskBulkPanelAction() {
		@$ids = DevblocksPlatform::importGPC($_REQUEST['ids']);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);

	    if(!empty($ids)) {
	        $id_list = DevblocksPlatform::parseCsvString($ids);
	        $tpl->assign('ids', implode(',', $id_list));
	    }
		
	    $workers = DAO_Worker::getAllActive();
	    $tpl->assign('workers', $workers);
	    
		// Custom Fields
		$custom_fields = DAO_CustomField::getBySource(ChCustomFieldSource_Task::ID);
		$tpl->assign('custom_fields', $custom_fields);
		
		$tpl->cache_lifetime = "0";
		$tpl->display('file:' . $this->_TPL_PATH . 'tasks/rpc/bulk.tpl');
	}
	
	function doTaskBulkUpdateAction() {
		// Checked rows
	    @$ids_str = DevblocksPlatform::importGPC($_REQUEST['ids'],'string');
		$ids = DevblocksPlatform::parseCsvString($ids_str);

		// Filter: whole list or check
	    @$filter = DevblocksPlatform::importGPC($_REQUEST['filter'],'string','');
	    
	    // View
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView('',$view_id);
		
		// Task fields
		$due = trim(DevblocksPlatform::importGPC($_POST['due'],'string',''));
		$status = trim(DevblocksPlatform::importGPC($_POST['status'],'string',''));
		$worker_id = trim(DevblocksPlatform::importGPC($_POST['worker_id'],'string',''));

		$do = array();
		
		// Do: Due
		if(0 != strlen($due))
			$do['due'] = $due;
			
		// Do: Status
		if(0 != strlen($status))
			$do['status'] = $status;
			
		// Do: Worker
		if(0 != strlen($worker_id))
			$do['worker_id'] = $worker_id;
			
		// Do: Custom fields
		$do = DAO_CustomFieldValue::handleBulkPost($do);
			
		$view->doBulkUpdate($filter, $do, $ids);
		
		$view->render();
		return;
	}
};
