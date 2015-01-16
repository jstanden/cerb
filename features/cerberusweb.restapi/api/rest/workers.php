<?php
class ChRest_Workers extends Extension_RestController implements IExtensionRestController {
	function getAction($stack) {
		@$action = array_shift($stack);
		
		// Looking up a single ID?
		if(is_numeric($action)) {
			$this->getId(intval($action));
			
		} else { // actions
			switch($action) {
				case 'me':
					$worker = CerberusApplication::getActiveWorker();
					$this->getId($worker->id);
					break;
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
		
//		$worker = CerberusApplication::getActiveWorker();
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
	
	function getContext($model) {
		$labels = array();
		$values = array();
		$context = CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $model, $labels, $values, null, true);

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
				'password' => 'password',
				'title' => DAO_Worker::TITLE,
				'updated' => DAO_Worker::UPDATED,
			);
			
		} elseif ('subtotal'==$type) {
			$tokens = array(
				'fieldsets' => SearchFields_Worker::VIRTUAL_HAS_FIELDSET,
				'links' => SearchFields_Worker::VIRTUAL_CONTEXT_LINK,
					
				'first_name' => SearchFields_Worker::FIRST_NAME,
				'is_disabled' => SearchFields_Worker::IS_DISABLED,
				'is_superuser' => SearchFields_Worker::IS_SUPERUSER,
				'last_name' => SearchFields_Worker::LAST_NAME,
				'title' => SearchFields_Worker::TITLE,
			);
			
			$tokens_cfields = $this->_handleSearchTokensCustomFields(CerberusContexts::CONTEXT_WORKER);
			
			if(is_array($tokens_cfields))
				$tokens = array_merge($tokens, $tokens_cfields);
			
		} else {
			$tokens = array(
				'id' => SearchFields_Worker::ID,
				'email' => SearchFields_Worker::EMAIL,
				'first_name' => SearchFields_Worker::FIRST_NAME,
				'is_disabled' => SearchFields_Worker::IS_DISABLED,
				'is_superuser' => SearchFields_Worker::IS_SUPERUSER,
				'last_name' => SearchFields_Worker::LAST_NAME,
				'title' => SearchFields_Worker::TITLE,
				'updated' => SearchFields_Worker::UPDATED,
			);
		}
		
		if(isset($tokens[$token]))
			return $tokens[$token];
		
		return NULL;
	}
	
	function getId($id) {
		$worker = CerberusApplication::getActiveWorker();
		
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
	
	function search($filters=array(), $sortToken='first_name', $sortAsc=1, $page=1, $limit=10, $options=array()) {
		@$show_results = DevblocksPlatform::importVar($options['show_results'], 'boolean', true);
		@$subtotals = DevblocksPlatform::importVar($options['subtotals'], 'array', array());
		
		$worker = CerberusApplication::getActiveWorker();
		
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
		
		$view = $this->_getSearchView(
			CerberusContexts::CONTEXT_WORKER,
			$params,
			$limit,
			$page,
			$sortBy,
			$sortAsc
		);
		
		if($show_results)
			list($results, $total) = $view->getData();
		
		// Get subtotal data, if provided
		if(!empty($subtotals))
			$subtotal_data = $this->_handleSearchSubtotals($view, $subtotals);
		
		if($show_results) {
			$objects = array();
			
			$models = CerberusContexts::getModels(CerberusContexts::CONTEXT_WORKER, array_keys($results));
			
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
	
	function putId($id) {
		$worker = CerberusApplication::getActiveWorker();
		
		// ACL
		if(!$worker->is_superuser)
			$this->error(self::ERRNO_ACL);
		
		// Validate the ID
		if(null == DAO_Worker::get($id))
			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid worker ID '%d'", $id));
			
		$putfields = array(
			'first_name' => 'string',
			'is_disabled' => 'bit',
			'is_superuser' => 'bit',
			'last_name' => 'string',
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
		
		// Handle custom fields
		$customfields = $this->_handleCustomFields($_POST);
		if(is_array($customfields))
			DAO_CustomFieldValue::formatAndSetFieldValues(CerberusContexts::CONTEXT_WORKER, $id, $customfields, true, true, true);
		
		// Update
		DAO_Worker::update($id, $fields);
		
		// Password change?
		if(isset($putfields['password']) && !empty($putfields['password']))
			DAO_Worker::setAuth($id, $putfields['password']);
		
		$this->getId($id);
	}
	
	function postCreate() {
		$worker = CerberusApplication::getActiveWorker();
		
		// ACL
		if(!$worker->is_superuser)
			$this->error(self::ERRNO_ACL);
		
		$postfields = array(
			'email' => 'string',
			'first_name' => 'string',
			'is_disabled' => 'bit',
			'is_superuser' => 'bit',
			'last_name' => 'string',
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
		
		// Check required fields
		$reqfields = array(
			DAO_Worker::EMAIL,
		);
		$this->_handleRequiredFields($reqfields, $fields);
		
		$fields[DAO_Worker::AUTH_EXTENSION_ID] = 'login.password';
		
		// Create
		if(false != ($id = DAO_Worker::create($fields))) {
			$email = $fields[DAO_Worker::EMAIL];
			
			// Add the worker e-mail to the addresses table
			DAO_Address::lookupAddress($email, true);
			
			// Addresses
			if(null == DAO_AddressToWorker::getByAddress($email)) {
				DAO_AddressToWorker::assign($email, $id, true);
			}
			
			// Password (optional)
			if(isset($postfields['password']) && !empty($postfields['password']))
				DAO_Worker::setAuth($id, $postfields['password']);
			
			// Handle custom fields
			$customfields = $this->_handleCustomFields($_POST);
			if(is_array($customfields))
				DAO_CustomFieldValue::formatAndSetFieldValues(CerberusContexts::CONTEXT_WORKER, $id, $customfields, true, true, true);
			
			$this->getId($id);
		}
	}
};