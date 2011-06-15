<?php
class ChRest_ContactPerson extends Extension_RestController implements IExtensionRestController {
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
		$worker = $this->getActiveWorker();
		if(!$worker->hasPriv('core.addybook.person.actions.delete'))
			$this->error(self::ERRNO_ACL);

		$id = array_shift($stack);

		if(null == ($contact = DAO_ContactPerson::get($id)))
			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid contact id %d", $id));

		DAO_ContactPerson::delete($id);

		$result = array('id' => $id);
		$this->success($result);		
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
		$this->error(self::ERRNO_CUSTOM, sprintf("Invalid contact id '%d'", $id));
	}

	function translateToken($token, $type='dao') {
		$tokens = array();
		
		if('dao'==$type) {
			$tokens = array(
				'auth_password' => DAO_ContactPerson::AUTH_PASSWORD,
				'created' => DAO_ContactPerson::CREATED,
				'email_id' => DAO_ContactPerson::EMAIL_ID,
			);
		} else {
			$tokens = array(
				'created' => SearchFields_ContactPerson::CREATED,
				'email_id' => SearchFields_ContactPerson::EMAIL_ID,
				'email_address' => SearchFields_ContactPerson::ADDRESS_EMAIL,
				'email_first_name' => SearchFields_ContactPerson::ADDRESS_FIRST_NAME,
				'email_last_name' => SearchFields_ContactPerson::ADDRESS_LAST_NAME,
				'id' => SearchFields_ContactPerson::ID,
				'last_login' => SearchFields_ContactPerson::LAST_LOGIN,
			);
		}
		
		if(isset($tokens[$token]))
			return $tokens[$token];
		
		return NULL;
	}	
	
	function getContext($id) {
		$labels = array();
		$values = array();
		$context = CerberusContexts::getContext(CerberusContexts::CONTEXT_CONTACT_PERSON, $id, $labels, $values, null, true);

		return $values;
	}
	
	function search($filters=array(), $sortToken='id', $sortAsc=1, $page=1, $limit=10) {
		$worker = $this->getActiveWorker();

		$custom_field_params = $this->_handleSearchBuildParamsCustomFields($filters, CerberusContexts::CONTEXT_CONTACT_PERSON);
		$params = $this->_handleSearchBuildParams($filters);
		$params = array_merge($params, $custom_field_params);
		
		// Sort
		$sortBy = $this->translateToken($sortToken, 'search');
		$sortAsc = !empty($sortAsc) ? true : false;
		
		// Search
		list($results, $total) = DAO_ContactPerson::search(
			array($sortBy),
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
		if(null == DAO_ContactPerson::get($id))
			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid contact person ID '%d'", $id));
			
		$putfields = array(
			'password' => 'string',
		);

		$fields = array();

		foreach($putfields as $putfield => $type) {
			if(!isset($_POST[$putfield]))
				continue;
			
			@$value = DevblocksPlatform::importGPC($_POST[$putfield], 'string', '');
			switch($putfield) {
				case 'password':
					$putfield = 'auth_password';
					break;
			}
			
			if(null == ($field = self::translateToken($putfield, 'dao'))) {
				$this->error(self::ERRNO_CUSTOM, sprintf("'%s' is not a valid field.", $putfield));
			}
			
			// Sanitize
			$value = DevblocksPlatform::importVar($value, $type);

			// Overrides
			switch($field) {
				case 'auth_password':
					$salt = CerberusApplication::generatePassword(8);
					$value = md5($salt.md5($value));
					$fields[DAO_ContactPerson::AUTH_SALT] = $salt;
					break;
			}
			
			$fields[$field] = $value;
		}
		
		// Check required fields
//		$reqfields = array(DAO_ContactPerson::EMAIL);
//		$this->_handleRequiredFields($reqfields, $fields);

		// Handle custom fields
		$customfields = $this->_handleCustomFields($_POST);
		if(is_array($customfields))
			DAO_CustomFieldValue::formatAndSetFieldValues(CerberusContexts::CONTEXT_CONTACT_PERSON, $id, $customfields, true, true, true);

		// Update
		DAO_ContactPerson::update($id, $fields);
		$this->getId($id);
	}
	
	function postCreate() {
		$worker = $this->getActiveWorker();
		
		// ACL
		if(!$worker->hasPriv('core.addybook.addy.actions.update'))
			$this->error(self::ERRNO_ACL);
		
		$postfields = array(
			'email' => 'string',
			'password' => 'string',
		);

		$fields = array();
		
		foreach($postfields as $postfield => $type) {
			if(!isset($_POST[$postfield]))
				continue;
				
			@$value = DevblocksPlatform::importGPC($_POST[$postfield], 'string', '');
			
			switch($postfield) {
				case 'email':
					if(null != ($lookup = DAO_Address::lookupAddress($value, true))) {
						unset($postfields['email']);
						$postfield = 'email_id';
						$value = $lookup->id;
					}
					break;
				case 'password':
					$postfield = 'auth_password';
					break;
				default:
					$this->error($postfield);
					break;
			}
				
			if(null == ($field = self::translateToken($postfield, 'dao'))) {
				$this->error(self::ERRNO_CUSTOM, sprintf("'%s' is not a valid field.", $postfield));
			}

			// Sanitize
			$value = DevblocksPlatform::importVar($value, $type);
			
			// Overrides
			switch($field) {
				case 'auth_password':
					$salt = CerberusApplication::generatePassword(8);
					$value = md5($salt.md5($value));
					$fields[DAO_ContactPerson::AUTH_SALT] = $salt;
					break;
			}
			
			$fields[$field] = $value;
		}

		// Check required fields
		$reqfields = array(DAO_ContactPerson::EMAIL_ID, DAO_ContactPerson::AUTH_PASSWORD);
		$this->_handleRequiredFields($reqfields, $fields);
		
		// Create
		if(false != ($id = DAO_ContactPerson::create($fields))) {
			// update address
			DAO_Address::update($fields['email_id'], array('contact_person_id' => $id));
			
			// Handle custom fields
			$customfields = $this->_handleCustomFields($_POST);
			if(is_array($customfields))
				DAO_CustomFieldValue::formatAndSetFieldValues(CerberusContexts::CONTEXT_CONTACT_PERSON, $id, $customfields, true, true, true);
				
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