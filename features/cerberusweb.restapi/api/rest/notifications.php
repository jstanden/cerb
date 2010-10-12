<?php
class ChRest_Notifications extends Extension_RestController implements IExtensionRestController {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	function getAction($stack) {
		@$action = array_shift($stack);
		
		// Looking up a single notification ID?
		if(is_numeric($action)) {
			$this->getId(intval($action));
			
		} else { // actions
			switch($action) {
				case 'list':
					$this->getList();
					break;
				default:
					break;
			}
		}
		
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	function putAction($stack) {
		@$action = array_shift($stack);
		
		// Looking up a single notification ID?
		if(is_numeric($action)) {
			$this->putId(intval($action));
			
		} else { // actions
			switch($action) {
				default:
					break;
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
				'assignee_id' => DAO_WorkerEvent::WORKER_ID,
				'message' => DAO_WorkerEvent::MESSAGE,
				'created' => DAO_WorkerEvent::CREATED_DATE,
				'is_read' => DAO_WorkerEvent::IS_READ,
				'url' => DAO_WorkerEvent::URL,
			);
		} else {
			$tokens = array(
				'id' => SearchFields_WorkerEvent::ID,
				'is_read' => SearchFields_WorkerEvent::IS_READ,
				'url' => SearchFields_WorkerEvent::URL,
				'worker_id' => SearchFields_WorkerEvent::WORKER_ID,
			);
		}
		
		if(isset($tokens[$token]))
			return $tokens[$token];
		
		return NULL;
	}	
	
	function getContext($id) {
		$labels = array();
		$values = array();
		$context = CerberusContexts::getContext(CerberusContexts::CONTEXT_NOTIFICATION, $id, $labels, $values, null, true);

//		unset($values['latest_message_content']);

		return $values;
	} 
	
	private function getId($id) {
		$worker = $this->getActiveWorker();
		
		$container = $this->search(array(
			array('id', '=', $id),
		));
		
		if(is_array($container) && isset($container['results']) && isset($container['results'][$id]))
			$this->success($container['results'][$id]);

		// Error
		$this->error(self::ERRNO_CUSTOM, sprintf("Invalid notification id '%d'", $id));
	}
	
	private function getList() {
		$worker = $this->getActiveWorker();

		@$page = DevblocksPlatform::importGPC($_REQUEST['page'],'integer',1);
		@$unread = DevblocksPlatform::importGPC($_REQUEST['unread'],'string','');
		
		$filters = array(
			array('worker_id', '=', $worker->id),
		);
		
		if(0 != strlen($unread)) {
			$filters[] = array('is_read', '=', ($unread ? 0 : 1));
		}
		
		$container = $this->search(
			$filters,
			null,
			null,
			$page,
			10
		);
		
		$this->success($container);
	}

	function search($filters=array(), $sortToken='id', $sortAsc=1, $page=1, $limit=10) {
		$worker = $this->getActiveWorker();

		$params = $this->_handleSearchBuildParams($filters);
		
		// (ACL) Add worker group privs
		if(!$worker->is_superuser) {
			$params['tmp_worker_id'] = new DevblocksSearchCriteria(
				SearchFields_WorkerEvent::WORKER_ID,
				'=',
				$worker->id
			);
		}
		
		// Sort
		$sortBy = $this->translateToken($sortToken, 'search');
		$sortAsc = !empty($sortAsc) ? true : false;
		
		// Search
		list($results, $total) = DAO_WorkerEvent::search(
//			array(),
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
		if(null == ($event = DAO_WorkerEvent::get($id)))
			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid notification ID '%d'", $id));
			
		// ACL
		if(!($worker->is_superuser || $worker->id==$event->worker_id))
			$this->error(self::ERRNO_ACL);
			
		$putfields = array(
			'assignee_id' => 'integer',
			'content' => 'string',
			'created' => 'timestamp',
			'is_read' => 'bit',
			'title' => 'string',
			'url' => 'string',
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
		
		// Handle custom fields
//		$customfields = $this->_handleCustomFields($_POST);
//		if(is_array($customfields))
//			DAO_CustomFieldValue::formatAndSetFieldValues(CerberusContexts::CONTEXT_WORKER, $id, $customfields, true, true, true);
		
		// Check required fields
//		$reqfields = array(DAO_Address::EMAIL);
//		$this->_handleRequiredFields($reqfields, $fields);

		// Update
		DAO_WorkerEvent::update($id, $fields);
		$this->getId($id);
	}
	
	function postCreate() {
		$worker = $this->getActiveWorker();
		
		// ACL
//		if(!$worker->is_superuser)
//			$this->error(self::ERRNO_ACL);
		
		$postfields = array(
			'assignee_id' => 'integer',
			'content' => 'string',
			'created' => 'timestamp',
			'is_read' => 'bit',
			'title' => 'string',
			'url' => 'string',
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
		
		if(!isset($fields[DAO_WorkerEvent::CREATED_DATE]))
			$fields[DAO_WorkerEvent::CREATED_DATE] = time();
		
		// Check required fields
		$reqfields = array(
			DAO_WorkerEvent::MESSAGE, 
			DAO_WorkerEvent::URL, 
			DAO_WorkerEvent::WORKER_ID,
		);
		$this->_handleRequiredFields($reqfields, $fields);
		
		// Create
		if(false != ($id = DAO_WorkerEvent::create($fields))) {
			// Handle custom fields
//			$customfields = $this->_handleCustomFields($_POST);
//			if(is_array($customfields))
//				DAO_CustomFieldValue::formatAndSetFieldValues(CerberusContexts::CONTEXT_WORKER, $id, $customfields, true, true, true);
			
			$this->getId($id);
		}
	}	
};
