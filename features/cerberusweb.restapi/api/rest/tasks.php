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
		$active_worker = CerberusApplication::getActiveWorker();
		$id = array_shift($stack);

		if(null == ($task = DAO_Task::get($id)))
			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid task ID %d", $id));
		
		if(!Context_Task::isDeletableByActor($task, $active_worker))
			$this->error(self::ERRNO_ACL, sprintf("You do not have permission to delete task ID %d", $id));
		
		CerberusContexts::logActivityRecordDelete(CerberusContexts::CONTEXT_TASK, $task->id, $task->title);
		
		DAO_Task::delete($id);

		$result = array('id' => $id);
		$this->success($result);
	}

	function translateToken($token, $type='dao') {
		if('custom_' == substr($token, 0, 7) && in_array($type, array('search', 'subtotal'))) {
			return 'cf_' . intval(substr($token, 7));
		}
		
		$tokens = array();
		
		if('dao'==$type) {
			$tokens = array(
				'completed' => DAO_Task::COMPLETED_DATE,
				'due' => DAO_Task::DUE_DATE,
				'reopen_at' => DAO_Task::REOPEN_AT,
				'status_id' => DAO_Task::STATUS_ID,
				'title' => DAO_Task::TITLE,
				'updated' => DAO_Task::UPDATED_DATE,
			);
			
		} elseif ('subtotal'==$type) {
			$tokens = array(
				'fieldsets' => SearchFields_Task::VIRTUAL_HAS_FIELDSET,
				'links' => SearchFields_Task::VIRTUAL_CONTEXT_LINK,
				'watchers' => SearchFields_Task::VIRTUAL_WATCHERS,
					
				'status_id' => SearchFields_Task::STATUS_ID,
			);
			
			$tokens_cfields = $this->_handleSearchTokensCustomFields(CerberusContexts::CONTEXT_TASK);
			
			if(is_array($tokens_cfields))
				$tokens = array_merge($tokens, $tokens_cfields);
			
		} else {
			$tokens = array(
				'completed' => SearchFields_Task::COMPLETED_DATE,
				'due' => SearchFields_Task::DUE_DATE,
				'id' => SearchFields_Task::ID,
				'reopen_at' => SearchFields_Task::REOPEN_AT,
				'status_id' => SearchFields_Task::STATUS_ID,
				'title' => SearchFields_Task::TITLE,
				'watchers' => SearchFields_Task::VIRTUAL_WATCHERS,
			);
		}
		
		if(isset($tokens[$token]))
			return $tokens[$token];
		
		return NULL;
	}

	function getContext($model) {
		$labels = array();
		$values = array();
		$context = CerberusContexts::getContext(CerberusContexts::CONTEXT_TASK, $model, $labels, $values, null, true);

		return $values;
	}
	
	function getId($id) {
		$worker = CerberusApplication::getActiveWorker();
		
		// ACL
//		if(!$worker->hasPriv('...'))
//			$this->error(self::ERRNO_ACL);

		$container = $this->search(array(
			array('id', '=', $id),
		));
		
		if(is_array($container) && isset($container['results']) && isset($container['results'][$id]))
			$this->success($container['results'][$id]);

		// Error
		$this->error(self::ERRNO_CUSTOM, sprintf("Invalid task id '%d'", $id));
	}
	
	function search($filters=array(), $sortToken='id', $sortAsc=1, $page=1, $limit=10, $options=array()) {
		@$query = DevblocksPlatform::importVar($options['query'], 'string', null);
		@$show_results = DevblocksPlatform::importVar($options['show_results'], 'boolean', true);
		@$subtotals = DevblocksPlatform::importVar($options['subtotals'], 'array', array());
		
		$params = array();
		
		// Sort
		$sortBy = $this->translateToken($sortToken, 'search');
		$sortAsc = !empty($sortAsc) ? true : false;

		// Search
		
		$view = $this->_getSearchView(
			CerberusContexts::CONTEXT_TASK,
			$params,
			$limit,
			$page,
			$sortBy,
			$sortAsc
		);
		
		if(!empty($query) && $view instanceof IAbstractView_QuickSearch)
			$view->addParamsWithQuickSearch($query, true);

		// If we're given explicit filters, merge them in to our quick search
		if(!empty($filters)) {
			if(!empty($query))
				$params = $view->getParams(false);
			
			$custom_field_params = $this->_handleSearchBuildParamsCustomFields($filters, CerberusContexts::CONTEXT_TASK);
			$new_params = $this->_handleSearchBuildParams($filters);
			$params = array_merge($params, $new_params, $custom_field_params);
			
			$view->addParams($params, true);
		}
		
		if($show_results)
			list($results, $total) = $view->getData();
		
		// Get subtotal data, if provided
		if(!empty($subtotals))
			$subtotal_data = $this->_handleSearchSubtotals($view, $subtotals);
		
		if($show_results) {
			$objects = array();
			
			$models = CerberusContexts::getModels(CerberusContexts::CONTEXT_TASK, array_keys($results));
			
			unset($results);
			
			foreach($models as $id => $model) {
				$values = $this->getContext($model);
				$objects[$id] = $values;
			}
		}
		
		$container = array();
		
		if($show_results) {
			$container['results'] = $objects;
			$container['total'] = $total;
			$container['count'] = count($objects);
			$container['page'] = $page;
		}
		
		if(!empty($subtotals)) {
			$container['subtotals'] = $subtotal_data;
		}
		
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
		if(!($worker->hasPriv('contexts.cerberusweb.contexts.task.update') || $task->worker_id == $worker->id))
			$this->error(self::ERRNO_ACL);
			
		$putfields = array(
			'assignee_id' => 'integer',
			'completed' => 'timestamp',
			'due' => 'timestamp',
			'reopen_at' => 'timestamp',
			'status_id' => 'integer',
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
			
			$fields[$field] = $value;
		}
		
		if(!isset($fields[DAO_Task::UPDATED_DATE]))
			$fields[DAO_Task::UPDATED_DATE] = time();
		
		// Handle custom fields
		$customfields = $this->_handleCustomFields($_POST);
		if(is_array($customfields))
			DAO_CustomFieldValue::formatAndSetFieldValues(CerberusContexts::CONTEXT_TASK, $id, $customfields, true, true, true);
		
		// Update
		DAO_Task::update($id, $fields);
		$this->getId($id);
	}
	
	function postCreate() {
		$worker = CerberusApplication::getActiveWorker();
		
		// ACL
		if(!$worker->hasPriv('contexts.cerberusweb.contexts.task.create'))
			$this->error(self::ERRNO_ACL);
		
		$postfields = array(
			'assignee_id' => 'integer',
			'completed' => 'timestamp',
			'due' => 'timestamp',
			'reopen_at' => 'timestamp',
			'status_id' => 'integer',
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

		$note = DevblocksPlatform::importGPC($_POST['note'] ?? null, 'string','');
		
		if(null == ($task = DAO_Task::get($id)))
			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid task ID %d", $id));

		// ACL
		if(!($worker->hasPriv('contexts.cerberusweb.contexts.task.update') || $task->worker_id==$worker->id))
			$this->error(self::ERRNO_ACL);
		
		// Required fields
		if(empty($note))
			$this->error(self::ERRNO_CUSTOM, "The 'note' field is required.");
			
		// Post
		$fields = [
			DAO_Comment::CONTEXT => CerberusContexts::CONTEXT_TASK,
			DAO_Comment::CONTEXT_ID => $task->id,
			DAO_Comment::OWNER_CONTEXT => CerberusContexts::CONTEXT_WORKER,
			DAO_Comment::OWNER_CONTEXT_ID => $worker->id,
			DAO_Comment::CREATED => time(),
			DAO_Comment::COMMENT => $note,
		];
		$note_id = DAO_Comment::create($fields);
		DAO_Comment::onUpdateByActor($worker, $fields, $note_id);
			
		$this->success(array(
			'task_id' => $task->id,
			'note_id' => $note_id,
		));
	}
};