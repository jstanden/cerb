<?php
class ChRest_Tasks extends Extension_RestController implements IExtensionRestController {
	function getAction($stack) {
		@$action = array_shift($stack);
		
		// Looking up a single ID?
		if(is_numeric($action)) {
			$this->getId(intval($action));
			
		} else { // actions
			switch($action) {
			}
		}
		
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	function putAction($stack) {
		@$action = array_shift($stack);
		
		// Looking up a single ID?
		if(is_numeric($action)) {
			$this->putId(intval($action));
			
		} else { // actions
			switch($action) {
			}
		}
		
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	function postAction($stack) {
		@$action = array_shift($stack);
		
		if(is_numeric($action) && !empty($stack)) {
			$id = intval($action);
			$action = array_shift($stack);
			
			switch($action) {
				case 'note':
					$this->postNote($id);
					break;
			}
			
		} else {
			switch($action) {
				case 'create':
					$this->postCreate();
					break;
				case 'search':
					$this->postSearch();
					break;
			}
		}
		
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	function deleteAction($stack) {
		$id = array_shift($stack);

		if(null == ($task = DAO_Task::get($id)))
			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid task ID %d", $id));

		DAO_Task::delete($id);

		$result = array('id' => $id);
		$this->success($result);
	}

	function translateToken($token, $type='dao') {
		$tokens = array();
		
		if('dao'==$type) {
			$tokens = array(
				'completed' => DAO_Task::COMPLETED_DATE,
				'due' => DAO_Task::DUE_DATE,
				'is_completed' => DAO_Task::IS_COMPLETED,
				'title' => DAO_Task::TITLE,
				'updated' => DAO_Task::UPDATED_DATE,
			);
		} else {
			$tokens = array(
				'completed' => SearchFields_Task::COMPLETED_DATE,
				'due' => SearchFields_Task::DUE_DATE,
				'id' => SearchFields_Task::ID,
				'is_completed' => SearchFields_Task::IS_COMPLETED,
				'title' => SearchFields_Task::TITLE,
				'watchers' => SearchFields_Task::VIRTUAL_WATCHERS,
			);
		}
		
		if(isset($tokens[$token]))
			return $tokens[$token];
		
		return NULL;
	}

	function getContext($id) {
		$labels = array();
		$values = array();
		$context = CerberusContexts::getContext(CerberusContexts::CONTEXT_TASK, $id, $labels, $values, null, true);

		return $values;
	}
	
	function getId($id) {
		$worker = CerberusApplication::getActiveWorker();
		
		// ACL
//		if(!$worker->hasPriv('...'))
//			$this->error("Access denied.");

		$container = $this->search(array(
			array('id', '=', $id),
		));
		
		if(is_array($container) && isset($container['results']) && isset($container['results'][$id]))
			$this->success($container['results'][$id]);

		// Error
		$this->error(self::ERRNO_CUSTOM, sprintf("Invalid task id '%d'", $id));
	}
	
	function search($filters=array(), $sortToken='id', $sortAsc=1, $page=1, $limit=10) {
		$worker = CerberusApplication::getActiveWorker();

		$custom_field_params = $this->_handleSearchBuildParamsCustomFields($filters, CerberusContexts::CONTEXT_TASK);
		$params = $this->_handleSearchBuildParams($filters);
		$params = array_merge($params, $custom_field_params);
		
		// Sort
		$sortBy = $this->translateToken($sortToken, 'search');
		$sortAsc = !empty($sortAsc) ? true : false;
		
		// Search
		list($results, $total) = DAO_Task::search(
			!empty($sortBy) ? array($sortBy) : array(),
			$params,
			$limit,
			max(0,$page-1),
			$sortBy,
			$sortAsc,
			true
		);
		
		$objects = array();
		
		foreach($results as $id => $result) {
			$values = $this->getContext($id);
			$objects[$id] = $values;
		}
		
		$container = array(
			'total' => $total,
			'count' => count($objects),
			'page' => $page,
			'results' => $objects,
		);
		
		return $container;
	}
	
	function postSearch() {
		$worker = CerberusApplication::getActiveWorker();
		
		// ACL
//		if(!$worker->hasPriv('core.addybook'))
//			$this->error(self::ERRNO_ACL);

		$container = $this->_handlePostSearch();
		
		$this->success($container);
	}
	
	function putId($id) {
		$worker = CerberusApplication::getActiveWorker();
		
		// Validate the ID
		if(null == ($task = DAO_Task::get($id)))
			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid task ID '%d'", $id));
			
		// ACL
		if(!($worker->hasPriv('core.tasks.actions.update_all') || $task->worker_id == $worker->id))
			$this->error(self::ERRNO_ACL);
			
		$putfields = array(
			'assignee_id' => 'integer',
			'completed' => 'timestamp',
			'due' => 'timestamp',
			'is_completed' => 'bit',
			'title' => 'string',
			'updated' => 'timestamp',
		);

		$fields = array();

		foreach($putfields as $putfield => $type) {
			if(!isset($_POST[$putfield]))
				continue;
			
			@$value = DevblocksPlatform::importGPC($_POST[$putfield], 'string', '');
			
			if(null == ($field = self::translateToken($putfield, 'dao'))) {
				$this->error(self::ERRNO_CUSTOM, sprintf("'%s' is not a valid field.", $putfield));
			}
			
			// Sanitize
			$value = DevblocksPlatform::importVar($value, $type);
						
//			switch($field) {
//				case DAO_Worker::PASSWORD:
//					$value = md5($value);
//					break;
//			}
			
			$fields[$field] = $value;
		}
		
		if(!isset($fields[DAO_Task::UPDATED_DATE]))
			$fields[DAO_Task::UPDATED_DATE] = time();
		
		// Handle custom fields
		$customfields = $this->_handleCustomFields($_POST);
		if(is_array($customfields))
			DAO_CustomFieldValue::formatAndSetFieldValues(CerberusContexts::CONTEXT_TASK, $id, $customfields, true, true, true);
		
		// Check required fields
//		$reqfields = array(DAO_Address::EMAIL);
//		$this->_handleRequiredFields($reqfields, $fields);

		// Update
		DAO_Task::update($id, $fields);
		$this->getId($id);
	}
	
	function postCreate() {
		$worker = CerberusApplication::getActiveWorker();
		
		// ACL
		if(!$worker->hasPriv('core.tasks.actions.create'))
			$this->error(self::ERRNO_ACL);
		
		$postfields = array(
			'assignee_id' => 'integer',
			'completed' => 'timestamp',
			'due' => 'timestamp',
			'is_completed' => 'bit',
			'title' => 'string',
			'updated' => 'timestamp',
		);

		$fields = array();
		
		foreach($postfields as $postfield => $type) {
			if(!isset($_POST[$postfield]))
				continue;
				
			@$value = DevblocksPlatform::importGPC($_POST[$postfield], 'string', '');
				
			if(null == ($field = self::translateToken($postfield, 'dao'))) {
				$this->error(self::ERRNO_CUSTOM, sprintf("'%s' is not a valid field.", $postfield));
			}

			// Sanitize
			$value = DevblocksPlatform::importVar($value, $type);
			
//			switch($field) {
//				case DAO_Worker::PASSWORD:
//					$value = md5($value);
//					break;
//			}
			
			$fields[$field] = $value;
		}

		// Defaults
		if(!isset($fields[DAO_Task::UPDATED_DATE]))
			$fields[DAO_Task::UPDATED_DATE] = time();
		
		// Check required fields
		$reqfields = array(
			DAO_Task::TITLE,
		);
		$this->_handleRequiredFields($reqfields, $fields);
		
		// Custom fields
		$custom_fields = $this->_handleCustomFields($_POST);
		
		// Create
		if(false != ($id = DAO_Task::create($fields, $custom_fields))) {
			$this->getId($id);
		}
	}

	private function postNote($id) {
		$worker = CerberusApplication::getActiveWorker();

		@$note = DevblocksPlatform::importGPC($_POST['note'],'string','');
		
		if(null == ($task = DAO_Task::get($id)))
			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid task ID %d", $id));

		// ACL
		if(!($worker->hasPriv('core.tasks.actions.update_all') || $task->worker_id==$worker->id))
			$this->error(self::ERRNO_ACL);
		
		// Required fields
		if(empty($note))
			$this->error(self::ERRNO_CUSTOM, "The 'note' field is required.");
			
		// Post
		$fields = array(
			DAO_Comment::CONTEXT => CerberusContexts::CONTEXT_TASK,
			DAO_Comment::CONTEXT_ID => $task->id,
			DAO_Comment::OWNER_CONTEXT => CerberusContexts::CONTEXT_WORKER,
			DAO_Comment::OWNER_CONTEXT_ID => $worker->id,
			DAO_Comment::CREATED => time(),
			DAO_Comment::COMMENT => $note,
		);
		$note_id = DAO_Comment::create($fields);
			
		$this->success(array(
			'task_id' => $task->id,
			'note_id' => $note_id,
		));
	}
};