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
		$tpl_path = $this->_TPL_PATH;
		$tpl->assign('path', $tpl_path);
		
		$translate = DevblocksPlatform::getTranslationService();
		
		if(null == ($view = C4_AbstractViewLoader::getView(self::VIEW_ACTIVITY_TASKS))) {
			$view = new View_Task();
			$view->id = self::VIEW_ACTIVITY_TASKS;
			$view->renderSortBy = SearchFields_Task::DUE_DATE;
			$view->renderSortAsc = 1;
			
			$view->name = $translate->_('activity.tab.tasks');
			
			C4_AbstractViewLoader::setView($view->id, $view);
		}

		$tpl->assign('response_uri', 'activity/tasks');
		
		$tpl->assign('view', $view);
		$tpl->assign('view_fields', View_Task::getFields());
		$tpl->assign('view_searchable_fields', View_Task::getSearchFields());
		
		$tpl->display($tpl_path . 'tasks/activity_tab/index.tpl');		
	}
}
endif;

class ChTasksPage extends CerberusPageExtension {
	private $_TPL_PATH = '';
	
//	const SESSION_OPP_TAB = '';
	
	function __construct($manifest) {
		parent::__construct($manifest);
		$this->_TPL_PATH = dirname(dirname(dirname(__FILE__))) . '/templates/';
	}
	
	function browseAction() {
		$visit = CerberusApplication::getVisit(); /* @var $visit CerberusVisit */
		$request = DevblocksPlatform::getHttpRequest();
		$stack = $request->path;
		
		array_shift($stack); // tasks
		array_shift($stack); // browse
		
		@$id = array_shift($stack);
		
		if(null == ($task = DAO_Task::get($id))) {
			echo "<H1>Invalid Organization ID.</H1>";
			return;
		}
	
		// Display series support (inherited paging from Display)
		@$view_id = array_shift($stack);
		if(!empty($view_id)) {
			$view = C4_AbstractViewLoader::getView($view_id);

			$range = 250;
			$block_size = 250;
			$page = floor(($view->renderLimit * $view->renderPage)/$block_size);
			
			list($series, $series_count) = DAO_Task::search(
				$view->view_columns,
				$view->params,
				$block_size,
				$page,
				$view->renderSortBy,
				$view->renderSortAsc,
				false
			);
			
			$series_info = array(
				'title' => $view->name,
				'total' => count($series),
				'series' => array_flip(array_keys($series))
			);
			
			$visit->set('ch_task_series', $series_info);
		}
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('tasks','display',$task->id)));
		exit;
	}	
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);

		$visit = CerberusApplication::getVisit();
		$translate = DevblocksPlatform::getTranslationService();
		
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

				$workers = DAO_Worker::getAll();
				$tpl->assign('workers', $workers);
				
				$visit = CerberusApplication::getVisit();
				
				// Does a series exist?
				if(null != ($series_info = $visit->get('ch_task_series', null))) {
					@$series = $series_info['series'];
					
					// Is this ID part of the series?  If not, invalidate
					if(!isset($series[$task_id])) {
						$visit->set('ch_task_series', null);
					} else {
						$series_stats = array(
							'title' => $series_info['title'],
							'total' => $series_info['total'],
							'count' => count($series)
						);
						reset($series);
						$cur = 1;
						while(null !== current($series)) {
							$pos = key($series);
							if(intval($pos)==intval($task_id)) {
								$series_stats['cur'] = $cur;
								if(false !== prev($series)) {
									@$series_stats['prev'] = key($series);
									next($series); // skip to current
								} else {
									reset($series);
								}
								next($series); // next
								@$series_stats['next'] = key($series);
								break;
							}
							next($series);
							$cur++;
						}
						
						$tpl->assign('series_stats', $series_stats);
					}
				}
				
				$tpl->display($this->_TPL_PATH . 'tasks/display/index.tpl');
				break;
		}
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
	
	function showTaskPeekAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer','');
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$link_namespace = DevblocksPlatform::importGPC($_REQUEST['link_namespace'],'string',''); // opt
		@$link_object_id = DevblocksPlatform::importGPC($_REQUEST['link_object_id'],'integer',0); // opt
		
		$tpl = DevblocksPlatform::getTemplateService();
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

		// Notes
		list($notes, $null) = DAO_Note::search(
			array(
				new DevblocksSearchCriteria(SearchFields_Note::SOURCE_EXT_ID,'=',ChNotesSource_Task::ID),
				new DevblocksSearchCriteria(SearchFields_Note::SOURCE_ID,'=',$id),
			),
			-1,
			0,
			SearchFields_Note::CREATED,
			false,
			false
		);
		$tpl->assign('notes', $notes);

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
	
			// Worker
			@$worker_id = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'integer',0);
			@$fields[DAO_Task::WORKER_ID] = intval($worker_id);
			
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
				
				// Content
				@$content = DevblocksPlatform::importGPC($_REQUEST['content'],'string','');

				// Append a note from the first content block, if provided				
				if(!empty($content) && !empty($id)) {
					$fields = array(
						DAO_Note::SOURCE_EXTENSION_ID => ChNotesSource_Task::ID,
						DAO_Note::SOURCE_ID => $id,
						DAO_Note::WORKER_ID => $active_worker->id,
						DAO_Note::CREATED => time(),
						DAO_Note::CONTENT => $content,
					);
					$note_id = DAO_Note::create($fields);
				}
				
				// Write a notification (if not assigned to ourselves)
				$source_extensions = DevblocksPlatform::getExtensions('cerberusweb.task.source', true);
				if(!empty($worker_id) && $active_worker->id != $worker_id) {
					if(null != (@$source_renderer = $source_extensions[$link_namespace])) { /* @var $source_renderer Extension_TaskSource */
						$source_info = $source_renderer->getSourceInfo($link_object_id);
						$source_name = $source_info['name'];
						$source_url = $source_info['url'];
						
						if(empty($source_name) || empty($source_url))
							break;
						
						$fields = array(
							DAO_WorkerEvent::CREATED_DATE => time(),
							DAO_WorkerEvent::WORKER_ID => $worker_id,
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
		
		if(!empty($view_id) && null != ($view = C4_AbstractViewLoader::getView($view_id))) {
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
		$view = C4_AbstractViewLoader::getView($view_id);
		
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
	
	function showTasksPropertiesTabAction() {
		@$task_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(dirname(dirname(__FILE__))) . '/templates/';
		$tpl->assign('path', $tpl_path);
		
		$task = DAO_Task::get($task_id);
		$tpl->assign('task', $task);

		$active_workers = DAO_Worker::getAllActive();
		$tpl->assign('active_workers', $active_workers);

		$workers = DAO_Worker::getAllWithDisabled();
		$tpl->assign('workers', $workers);
	
		// Custom fields
		$custom_fields = DAO_CustomField::getBySource(ChCustomFieldSource_Task::ID);
		$tpl->assign('custom_fields', $custom_fields);
				
		$custom_field_values = DAO_CustomFieldValue::getValuesBySourceIds(ChCustomFieldSource_Task::ID, $task_id);
		if(isset($custom_field_values[$task_id]))
			$tpl->assign('custom_field_values', $custom_field_values[$task_id]);
		
		$tpl->display('file:' . $tpl_path . 'tasks/display/tabs/properties.tpl');
	}
	
	function saveTasksPropertiesTabAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer','');
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'],'integer',0);

		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!empty($id) && !empty($do_delete)) { // delete
			$task = DAO_Task::get($id);

			// Check privs
			if(($active_worker->hasPriv('core.tasks.actions.create') && $active_worker->id==$task->worker_id)
				|| ($active_worker->hasPriv('core.tasks.actions.update_nobody') && empty($task->worker_id)) 
				|| $active_worker->hasPriv('core.tasks.actions.update_all')) {
					DAO_Task::delete($id);
					DevblocksPlatform::redirect(new DevblocksHttpResponse(array('activity','tasks')));
					exit;
				}
			
		} else { // update
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
	
			// Worker
			@$worker_id = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'integer',0);
			@$fields[DAO_Task::WORKER_ID] = intval($worker_id);
			
			// Save
			if(!empty($id)) {
				DAO_Task::update($id, $fields);
			
				// Custom field saves
				@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', array());
				DAO_CustomFieldValue::handleFormPost(ChCustomFieldSource_Task::ID, $id, $field_ids);
			}
		}
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('tasks','display',$id,'properties')));
	}
	
	function showTaskNotesTabAction() {
		@$task_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(dirname(dirname(__FILE__))) . '/templates/';
		$tpl->assign('path', $tpl_path);
		
		$visit = CerberusApplication::getVisit();
//		$visit->set(self::SESSION_OPP_TAB, 'notes');
		
		$task = DAO_Task::get($task_id);
		$tpl->assign('task', $task);

		list($notes, $null) = DAO_Note::search(
			array(
				new DevblocksSearchCriteria(SearchFields_Note::SOURCE_EXT_ID,'=',ChNotesSource_Task::ID),
				new DevblocksSearchCriteria(SearchFields_Note::SOURCE_ID,'=',$task->id),
			),
			25,
			0,
			SearchFields_Note::CREATED,
			false,
			false
		);
		$tpl->assign('notes', $notes);
		
		$active_workers = DAO_Worker::getAllActive();
		$tpl->assign('active_workers', $active_workers);

		$workers = DAO_Worker::getAllWithDisabled();
		$tpl->assign('workers', $workers);
				
		$tpl->display('file:' . $tpl_path . 'tasks/display/tabs/notes.tpl');
	}
	
	function saveTaskNoteAction() {
		@$task_id = DevblocksPlatform::importGPC($_REQUEST['task_id'],'integer', 0);
		@$content = DevblocksPlatform::importGPC($_REQUEST['content'],'string','');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!empty($task_id) && 0 != strlen(trim($content))) {
			$fields = array(
				DAO_Note::SOURCE_EXTENSION_ID => ChNotesSource_Task::ID,
				DAO_Note::SOURCE_ID => $task_id,
				DAO_Note::WORKER_ID => $active_worker->id,
				DAO_Note::CREATED => time(),
				DAO_Note::CONTENT => $content,
			);
			$note_id = DAO_Note::create($fields);
		}
		
		$task = DAO_Task::get($task_id);
		
		// Worker notifications
		$url_writer = DevblocksPlatform::getUrlService();
		@$notify_worker_ids = DevblocksPlatform::importGPC($_REQUEST['notify_worker_ids'],'array',array());
		if(is_array($notify_worker_ids) && !empty($notify_worker_ids))
		foreach($notify_worker_ids as $notify_worker_id) {
			$fields = array(
				DAO_WorkerEvent::CREATED_DATE => time(),
				DAO_WorkerEvent::WORKER_ID => $notify_worker_id,
				DAO_WorkerEvent::URL => $url_writer->write('c=tasks&a=display&id='.$task_id,true),
				DAO_WorkerEvent::TITLE => 'New Task Note', // [TODO] Translate
				DAO_WorkerEvent::CONTENT => sprintf("%s\n%s notes: %s", $task->title, $active_worker->getName(), $content), // [TODO] Translate
				DAO_WorkerEvent::IS_READ => 0,
			);
			DAO_WorkerEvent::create($fields);
		}
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('tasks','display',$task_id)));
	}
	
	// [TODO] This is redundant and should be handled by ?c=internal by passing a $return_path
	function deleteTaskNoteAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer', 0);
		@$task_id = DevblocksPlatform::importGPC($_REQUEST['task_id'],'integer', 0);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(null != ($note = DAO_Note::get($id))) {
			if($note->worker_id == $active_worker->id || $active_worker->is_superuser) {
				DAO_Note::delete($id);
			}
		}
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('tasks','display',$task_id)));
	}	
};
