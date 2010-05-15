<?php
class ChRest_Tasks extends Extension_RestController implements IExtensionRestController {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
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
		
		switch($action) {
			case 'create':
				$this->postCreate();
				break;
			case 'search':
				$this->postSearch();
				break;
		}
		
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	function deleteAction($stack) {
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}

	function translateToken($token, $type='dao') {
		$tokens = array();
		
		if('dao'==$type) {
			$tokens = array(
				'assignee_id' => DAO_Task::WORKER_ID,
				'completed' => DAO_Task::COMPLETED_DATE,
				'due' => DAO_Task::DUE_DATE,
				'is_completed' => DAO_Task::IS_COMPLETED,
				'title' => DAO_Task::TITLE,
				'updated' => DAO_Task::UPDATED_DATE,
			);
		} else {
			$tokens = array(
				'assignee_id' => SearchFields_Task::WORKER_ID,
				'completed' => SearchFields_Task::COMPLETED_DATE,
				'due' => SearchFields_Task::DUE_DATE,
				'id' => SearchFields_Task::ID,
				'is_completed' => SearchFields_Task::IS_COMPLETED,
				'title' => SearchFields_Task::TITLE,
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

//		unset($values['initial_message_content']);

		return $values;
	}
	
	function getId($id) {
		$worker = $this->getActiveWorker();
		
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
		$worker = $this->getActiveWorker();

		$params = $this->_handleSearchBuildParams($filters);
		
		// (ACL) Add worker group privs
//		if(!$worker->is_superuser) {
//			$memberships = $worker->getMemberships();
//			$params['tmp_worker_memberships'] = new DevblocksSearchCriteria(
//				SearchFields_Ticket::TICKET_TEAM_ID,
//				'in',
//				(!empty($memberships) ? array_keys($memberships) : array(0))
//			);
//		}
		
		// Sort
		$sortBy = $this->translateToken($sortToken, 'search');
		$sortAsc = !empty($sortAsc) ? true : false;
		
		// Search
		list($results, $total) = DAO_Task::search(
			array(),
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
		$worker = $this->getActiveWorker();
		
		// ACL
//		if(!$worker->hasPriv('core.addybook'))
//			$this->error(self::ERRNO_ACL);

		$container = $this->_handlePostSearch();
		
		$this->success($container);
	}
	
	function putId($id) {
		$worker = $this->getActiveWorker();
		
		// Validate the ID
		if(null == ($task = DAO_Task::get($id)))
			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid task ID '%d'", $id));
			
		// ACL
		if(!($worker->hasPriv('core.tasks.actions.update_all') || $task->worker_id == $worker->id))
			$this->error(self::ERRNO_ACL);
			
		$putfields = array(
			'assignee_id' => 'integer',		
			'completed' => 'integer',
			'due' => 'integer',
			'is_completed' => 'integer',
			'title' => 'string',
			'updated' => 'integer',
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
			$value = $this->_handleSanitizeValue($value, $type);
						
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
			DAO_CustomFieldValue::formatAndSetFieldValues(ChCustomFieldSource_Task::ID, $id, $customfields, true, true, true);
		
		// Check required fields
//		$reqfields = array(DAO_Address::EMAIL);
//		$this->_handleRequiredFields($reqfields, $fields);

		// Update
		DAO_Task::update($id, $fields);
		$this->getId($id);
	}
	
	function postCreate() {
		$worker = $this->getActiveWorker();
		
		// ACL
		if(!$worker->hasPriv('core.tasks.actions.create'))
			$this->error(self::ERRNO_ACL);
		
		$postfields = array(
			'assignee_id' => 'integer',
			'completed' => 'integer',
			'due' => 'integer',
			'is_completed' => 'integer',
			'title' => 'string',
			'updated' => 'integer',
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
			$value = $this->_handleSanitizeValue($value, $type);
			
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
		
		// Create
		if(false != ($id = DAO_Task::create($fields))) {
			// Handle custom fields
			$customfields = $this->_handleCustomFields($_POST);
			if(is_array($customfields))
				DAO_CustomFieldValue::formatAndSetFieldValues(ChCustomFieldSource_Task::ID, $id, $customfields, true, true, true);
			
			$this->getId($id);
		}
	}		
};