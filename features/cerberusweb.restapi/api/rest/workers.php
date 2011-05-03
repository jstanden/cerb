<?php
class ChRest_Workers extends Extension_RestController implements IExtensionRestController {
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
		
//		$worker = $this->getActiveWorker();
//		
//		if(!$worker->is_superuser)
//			$this->error(self::ERRNO_ACL);
//		
//		$id = array_shift($stack);
//
//		if($worker->id == $id)
//			$this->error(self::ERRNO_CUSTOM, sprintf("You can't delete yourself!"));
//		
//		if(null == ($worker = DAO_Worker::get($id)))
//			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid worker ID %d", $id));
//
//		DAO_Worker::delete($id);
//
//		$result = array('id' => $id);
//		$this->success($result);
	}
	
	function getContext($id) {
		$labels = array();
		$values = array();
		$context = CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $id, $labels, $values, null, true);

//		unset($values['initial_message_content']);

		return $values;
	}
	
	function translateToken($token, $type='dao') {
		$tokens = array();
		
		if('dao'==$type) {
			$tokens = array(
				'email' => DAO_Worker::EMAIL,
				'first_name' => DAO_Worker::FIRST_NAME,
				'is_disabled' => DAO_Worker::IS_DISABLED,
				'is_superuser' => DAO_Worker::IS_SUPERUSER,
				'last_name' => DAO_Worker::LAST_NAME,
				'password' => DAO_Worker::PASSWORD,
				'title' => DAO_Worker::TITLE,
			);
		} else {
			$tokens = array(
				'id' => SearchFields_Worker::ID,
				'email' => SearchFields_Worker::EMAIL,
				'first_name' => SearchFields_Worker::FIRST_NAME,
				'is_disabled' => SearchFields_Worker::IS_DISABLED,
				'is_superuser' => SearchFields_Worker::IS_SUPERUSER,
				'last_name' => SearchFields_Worker::LAST_NAME,
				'title' => SearchFields_Worker::TITLE,
			);
		}
		
		if(isset($tokens[$token]))
			return $tokens[$token];
		
		return NULL;		
	}
	
	function getId($id) {
		$worker = $this->getActiveWorker();
		
		// ACL
		if($id != $worker->id && !$worker->is_superuser)
			$this->error(self::ERRNO_ACL);

		$container = $this->search(array(
			array('id', '=', $id),
		));
		
		if(is_array($container) && isset($container['results']) && isset($container['results'][$id]))
			$this->success($container['results'][$id]);

		// Error
		$this->error(self::ERRNO_CUSTOM, sprintf("Invalid worker id '%d'", $id));
	}
	
	function postSearch() {
		$container = $this->_handlePostSearch();
		
		$this->success($container);
	}
	
	function search($filters=array(), $sortToken='first_name', $sortAsc=1, $page=1, $limit=10) {
		$worker = $this->getActiveWorker();
		
		$params = $this->_handleSearchBuildParams($filters);
		
		// (ACL) Limit non-superusers to themselves
		if(!$worker->is_superuser) {
			$params['tmp_worker_id'] = new DevblocksSearchCriteria(
				SearchFields_Worker::ID,
				'=',
				$worker->id
			);
		}
		
		// Sort
		$sortBy = $this->translateToken($sortToken, 'search');
		$sortAsc = !empty($sortAsc) ? true : false;
		
		// Search
		list($results, $total) = DAO_Worker::search(
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
	
	function putId($id) {
		$worker = $this->getActiveWorker();
		
		// ACL
		if(!$worker->is_superuser)
			$this->error(self::ERRNO_ACL);
		
		// Validate the ID
		if(null == DAO_Worker::get($id))
			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid worker ID '%d'", $id));
			
		$putfields = array(
//			'email' => 'string',
			'first_name' => 'string',
			'is_disabled' => 'bit',
			'is_superuser' => 'bit',
			'last_name' => 'string',
			'password' => 'string',
			'title' => 'string',
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
						
			switch($field) {
				case DAO_Worker::PASSWORD:
					$value = md5($value);
					break;
			}
			
			$fields[$field] = $value;
		}
		
		// Handle custom fields
		$customfields = $this->_handleCustomFields($_POST);
		if(is_array($customfields))
			DAO_CustomFieldValue::formatAndSetFieldValues(CerberusContexts::CONTEXT_WORKER, $id, $customfields, true, true, true);
		
		// Check required fields
//		$reqfields = array(DAO_Address::EMAIL);
//		$this->_handleRequiredFields($reqfields, $fields);

		// Update
		DAO_Worker::update($id, $fields);
		
		$this->getId($id);
	}
	
	function postCreate() {
		$worker = $this->getActiveWorker();
		
		// ACL
		if(!$worker->is_superuser)
			$this->error(self::ERRNO_ACL);
		
		$postfields = array(
			'email' => 'string',
			'first_name' => 'string',
			'is_disabled' => 'bit',
			'is_superuser' => 'bit',
			'last_name' => 'string',
			'password' => 'string',
			'title' => 'string',
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
			
			switch($field) {
				case DAO_Worker::PASSWORD:
					$value = md5($value);
					break;
			}
			
			$fields[$field] = $value;
		}
		
		// Check required fields
		$reqfields = array(
			DAO_Worker::EMAIL, 
			DAO_Worker::PASSWORD,
		);
		$this->_handleRequiredFields($reqfields, $fields);
		
		// Create
		if(false != ($id = DAO_Worker::create($fields))) {
			$email = $fields[DAO_Worker::EMAIL];
			
			// Add the worker e-mail to the addresses table
			DAO_Address::lookupAddress($email, true);
			
			// Addresses
			if(null == DAO_AddressToWorker::getByAddress($email)) {
				DAO_AddressToWorker::assign($email, $id);
				DAO_AddressToWorker::update($email, array(
					DAO_AddressToWorker::IS_CONFIRMED => 1
				));
			}
			
			// Handle custom fields
			$customfields = $this->_handleCustomFields($_POST);
			if(is_array($customfields))
				DAO_CustomFieldValue::formatAndSetFieldValues(CerberusContexts::CONTEXT_WORKER, $id, $customfields, true, true, true);
			
			$this->getId($id);
		}
	}	
};