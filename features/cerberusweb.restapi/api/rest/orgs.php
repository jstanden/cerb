<?php
class ChRest_Orgs extends Extension_RestController implements IExtensionRestController {
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
		$worker = $this->getActiveWorker();
		if(!$worker->hasPriv('core.addybook.org.actions.delete'))
			$this->error(self::ERRNO_ACL);

		$id = array_shift($stack);

		if(null == ($task = DAO_ContactOrg::get($id)))
			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid organization ID %d", $id));

		DAO_ContactOrg::delete($id);

		$result = array('id' => $id);
		$this->success($result);		
	}
	
	function getId($id) {
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
		$this->error(self::ERRNO_CUSTOM, sprintf("Invalid org id '%d'", $id));		
	}
	
	function translateToken($token, $type='dao') {
		$tokens = array();
		
		if('dao'==$type) {
			$tokens = array(
				'name' => DAO_ContactOrg::NAME,
				'street' => DAO_ContactOrg::STREET,
				'city' => DAO_ContactOrg::CITY,
				'province' => DAO_ContactOrg::PROVINCE,
				'postal' => DAO_ContactOrg::POSTAL,
				'country' => DAO_ContactOrg::COUNTRY,
				'phone' => DAO_ContactOrg::PHONE,
				'website' => DAO_ContactOrg::WEBSITE,
				'created' => DAO_ContactOrg::CREATED,
			);
		} else {
			$tokens = array(
				'id' => SearchFields_ContactOrg::ID,
				'name' => SearchFields_ContactOrg::NAME,
				'street' => SearchFields_ContactOrg::STREET,
				'city' => SearchFields_ContactOrg::CITY,
				'province' => SearchFields_ContactOrg::PROVINCE,
				'postal' => SearchFields_ContactOrg::POSTAL,
				'country' => SearchFields_ContactOrg::COUNTRY,
				'phone' => SearchFields_ContactOrg::PHONE,
				'website' => SearchFields_ContactOrg::WEBSITE,
				'created' => SearchFields_ContactOrg::CREATED,			
			);
		}
		
		if(isset($tokens[$token]))
			return $tokens[$token];
		
		return NULL;
	}	
	
	function getContext($id) {
		$labels = array();
		$values = array();
		$context = CerberusContexts::getContext(CerberusContexts::CONTEXT_ORG, $id, $labels, $values, null, true);

		return $values;
	}
	
	function search($filters=array(), $sortToken='name', $sortAsc=1, $page=1, $limit=10) {
		$worker = $this->getActiveWorker();

		$params = $this->_handleSearchBuildParams($filters);
		
		// Sort
		$sortBy = $this->translateToken($sortToken, 'search');
		$sortAsc = !empty($sortAsc) ? true : false;
		
		// Search
		list($results, $total) = DAO_ContactOrg::search(
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
		$worker = $this->getActiveWorker();
		
		// ACL
		if(!$worker->hasPriv('core.addybook'))
			$this->error(self::ERRNO_ACL);

		$container = $this->_handlePostSearch();
		
		$this->success($container);
	}
	
	function putId($id) {
		$worker = $this->getActiveWorker();
		
		// ACL
		if(!$worker->hasPriv('core.addybook.org.actions.update'))
			$this->error(self::ERRNO_ACL);
		
		// Validate the ID
		if(null == DAO_ContactOrg::get($id))
			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid org ID '%d'", $id));
			
		$putfields = array(
			'name' => 'string',
			'street' => 'string',
			'city' => 'string',
			'province' => 'string',
			'postal' => 'string',
			'country' => 'string',
			'phone' => 'string',
			'website' => 'string',
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
		$customfields = $this->_handleCustomFields($_POST);
		if(is_array($customfields))
			DAO_CustomFieldValue::formatAndSetFieldValues(CerberusContexts::CONTEXT_ORG, $id, $customfields, true, true, true);
		
		// Check required fields
//		$reqfields = array(DAO_Address::EMAIL);
//		$this->_handleRequiredFields($reqfields, $fields);

		// Update
		DAO_ContactOrg::update($id, $fields);
		$this->getId($id);
	}
	
	function postCreate() {
		$worker = $this->getActiveWorker();
		
		// ACL
		if(!$worker->hasPriv('core.addybook.org.actions.update'))
			$this->error(self::ERRNO_ACL);
		
		$postfields = array(
			'name' => 'string',
			'street' => 'string',
			'city' => 'string',
			'province' => 'string',
			'postal' => 'string',
			'country' => 'string',
			'phone' => 'string',
			'website' => 'string',
			'created' => 'timestamp',
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
		
		if(!isset($fields[DAO_ContactOrg::CREATED]))
			$fields[DAO_ContactOrg::CREATED] = time();
		
		// Check required fields
		$reqfields = array(
			DAO_ContactOrg::NAME, 
		);
		$this->_handleRequiredFields($reqfields, $fields);
		
		// Create
		if(false != ($id = DAO_ContactOrg::create($fields))) {
			// Handle custom fields
			$customfields = $this->_handleCustomFields($_POST);
			if(is_array($customfields))
				DAO_CustomFieldValue::formatAndSetFieldValues(CerberusContexts::CONTEXT_ORG, $id, $customfields, true, true, true);
			
			$this->getId($id);
		}
	}	

	private function postNote($id) {
		$worker = $this->getActiveWorker();

		@$note = DevblocksPlatform::importGPC($_POST['note'],'string','');
		
		if(null == ($org = DAO_ContactOrg::get($id)))
			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid org ID %d", $id));

		// ACL
		if(!$worker->hasPriv('core.addybook.org.actions.update'))
			$this->error(self::ERRNO_ACL);
		
		// Required fields
		if(empty($note))
			$this->error(self::ERRNO_CUSTOM, "The 'note' field is required.");
			
		// Post
		$fields = array(
			DAO_Comment::CONTEXT => CerberusContexts::CONTEXT_ORG,
			DAO_Comment::CONTEXT_ID => $org->id,
			DAO_Comment::ADDRESS_ID => $worker->getAddress()->id,
			DAO_Comment::CREATED => time(),
			DAO_Comment::COMMENT => $note,
		);
		$note_id = DAO_Comment::create($fields);
			
		$this->success(array(
			'org_id' => $org->id,
			'note_id' => $note_id,
		));
	}	
};