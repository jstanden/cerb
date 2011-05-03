<?php
class ChRest_Addresses extends Extension_RestController implements IExtensionRestController {
	function getAction($stack) {
		@$action = array_shift($stack);
		
		// Looking up a single ID?
		if(is_numeric($action)) {
			$this->getId(intval($action));
			
		} else { // actions
			switch($action) {
				default:
					$this->error(self::ERRNO_NOT_IMPLEMENTED);
					break;
			}
		}
	}
	
	function putAction($stack) {
		@$action = array_shift($stack);
		
		// Looking up a single ID?
		if(is_numeric($action)) {
			$this->putId(intval($action));
			
		} else { // actions
			switch($action) {
				default:
					$this->error(self::ERRNO_NOT_IMPLEMENTED);
					break;
			}
		}
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
		// Consistency with the Web-UI
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
		
//		$worker = $this->getActiveWorker();
//		if(!$worker->hasPriv('core.addybook.person.actions.delete'))
//			$this->error(self::ERRNO_ACL);
//
//		$id = array_shift($stack);
//
//		if(null == ($task = DAO_Address::get($id)))
//			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid address ID %d", $id));
//
//		DAO_Address::delete($id);
//
//		$result = array('id' => $id);
//		$this->success($result);		
	}
	
	private function getId($id) {
		$worker = $this->getActiveWorker();
		
		// ACL
		if(!$worker->hasPriv('core.addybook'))
			$this->error(self::ERRNO_ACL);

		$container = $this->search(array(
			array('id', '=', $id),
		));
		
		if(is_array($container) && isset($container['results']) && isset($container['results'][$id]))
			$this->success($container['results'][$id]);

		// Error
		$this->error(self::ERRNO_CUSTOM, sprintf("Invalid address id '%d'", $id));
	}

	function translateToken($token, $type='dao') {
		$tokens = array();
		
		if('dao'==$type) {
			$tokens = array(
				'email' => DAO_Address::EMAIL,
				'first_name' => DAO_Address::FIRST_NAME,
				'is_banned' => DAO_Address::IS_BANNED,
				'last_name' => DAO_Address::LAST_NAME,
//				'num_nonspam' => DAO_Address::NUM_NONSPAM,
//				'num_spam' => DAO_Address::NUM_SPAM,
				'org_id' => DAO_Address::CONTACT_ORG_ID,
			);
		} else {
			$tokens = array(
				'id' => SearchFields_Address::ID,
				'email' => SearchFields_Address::EMAIL,
				'first_name' => SearchFields_Address::FIRST_NAME,
				'is_banned' => SearchFields_Address::IS_BANNED,
				'last_name' => SearchFields_Address::LAST_NAME,
				'num_nonspam' => SearchFields_Address::NUM_NONSPAM,
				'num_spam' => SearchFields_Address::NUM_SPAM,
				'org_id' => SearchFields_Address::CONTACT_ORG_ID,
				'org_name' => SearchFields_Address::ORG_NAME,
			);
		}
		
		if(isset($tokens[$token]))
			return $tokens[$token];
		
		return NULL;
	}	
	
	function getContext($id) {
		$labels = array();
		$values = array();
		$context = CerberusContexts::getContext(CerberusContexts::CONTEXT_ADDRESS, $id, $labels, $values, null, true);

		return $values;
	}
	
	function search($filters=array(), $sortToken='email', $sortAsc=1, $page=1, $limit=10) {
		$worker = $this->getActiveWorker();

		$custom_field_params = $this->_handleSearchBuildParamsCustomFields($filters, CerberusContexts::CONTEXT_ADDRESS);
		$params = $this->_handleSearchBuildParams($filters);
		$params = array_merge($params, $custom_field_params);
		
		// (ACL) Add worker group privs
		if(!$worker->is_superuser) {
			$memberships = $worker->getMemberships();
			$params['tmp_worker_memberships'] = new DevblocksSearchCriteria(
				SearchFields_Ticket::TICKET_TEAM_ID,
				'in',
				(!empty($memberships) ? array_keys($memberships) : array(0))
			);
		}
		
		// Sort
		$sortBy = $this->translateToken($sortToken, 'search');
		$sortAsc = !empty($sortAsc) ? true : false;
		
		// Search
		list($results, $total) = DAO_Address::search(
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
		if(!$worker->hasPriv('core.addybook.addy.actions.update'))
			$this->error(self::ERRNO_ACL);
		
		// Validate the ID
		if(null == DAO_Address::get($id))
			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid address ID '%d'", $id));
			
		$putfields = array(
			'first_name' => 'string',
			'is_banned' => 'bit',
			'last_name' => 'string',
			'org_id' => 'integer',
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

			// Overrides
			switch($field) {
			}
			
			$fields[$field] = $value;
		}
		
		// Handle custom fields
		$customfields = $this->_handleCustomFields($_POST);
		if(is_array($customfields))
			DAO_CustomFieldValue::formatAndSetFieldValues(CerberusContexts::CONTEXT_ADDRESS, $id, $customfields, true, true, true);
		
		// Check required fields
//		$reqfields = array(DAO_Address::EMAIL);
//		$this->_handleRequiredFields($reqfields, $fields);

		// Update
		DAO_Address::update($id, $fields);
		$this->getId($id);
	}
	
	function postCreate() {
		$worker = $this->getActiveWorker();
		
		// ACL
		if(!$worker->hasPriv('core.addybook.addy.actions.update'))
			$this->error(self::ERRNO_ACL);
		
		$postfields = array(
			'email' => 'string',
			'first_name' => 'string',
			'is_banned' => 'bit',
			'last_name' => 'string',
			'org_id' => 'integer',
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
			
			// Overrides
			switch($field) {
			}
			
			$fields[$field] = $value;
		}
		
		// Check required fields
		$reqfields = array(DAO_Address::EMAIL);
		$this->_handleRequiredFields($reqfields, $fields);
		
		// Create
		if(false != ($id = DAO_Address::create($fields))) {
			// Handle custom fields
			$customfields = $this->_handleCustomFields($_POST);
			if(is_array($customfields))
				DAO_CustomFieldValue::formatAndSetFieldValues(CerberusContexts::CONTEXT_ADDRESS, $id, $customfields, true, true, true);
			
			$this->getId($id);
		}
	}
	
	function postSearch() {
		$worker = $this->getActiveWorker();
		
		// ACL
		if(!$worker->hasPriv('core.addybook'))
			$this->error(self::ERRNO_ACL);

		$container = $this->_handlePostSearch();
		
		$this->success($container);
	}
};