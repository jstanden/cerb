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
		$worker = CerberusApplication::getActiveWorker();
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
		$worker = CerberusApplication::getActiveWorker();
		
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
				'updated' => DAO_ContactPerson::UPDATED,
			);
			
		} elseif ('subtotal'==$type) {
			$tokens = array(
				'fieldsets' => SearchFields_ContactPerson::VIRTUAL_HAS_FIELDSET,
				'links' => SearchFields_ContactPerson::VIRTUAL_CONTEXT_LINK,
				'watchers' => SearchFields_ContactPerson::VIRTUAL_WATCHERS,
					
				'email_address' => SearchFields_ContactPerson::ADDRESS_EMAIL,
				'email_first_name' => SearchFields_ContactPerson::ADDRESS_FIRST_NAME,
				'email_last_name' => SearchFields_ContactPerson::ADDRESS_LAST_NAME,
			);
			
			$tokens_cfields = $this->_handleSearchTokensCustomFields(CerberusContexts::CONTEXT_CONTACT_PERSON);
			
			if(is_array($tokens_cfields))
				$tokens = array_merge($tokens, $tokens_cfields);
			
		} else {
			$tokens = array(
				'created' => SearchFields_ContactPerson::CREATED,
				'email_id' => SearchFields_ContactPerson::EMAIL_ID,
				'email_address' => SearchFields_ContactPerson::ADDRESS_EMAIL,
				'email_first_name' => SearchFields_ContactPerson::ADDRESS_FIRST_NAME,
				'email_last_name' => SearchFields_ContactPerson::ADDRESS_LAST_NAME,
				'id' => SearchFields_ContactPerson::ID,
				'last_login' => SearchFields_ContactPerson::LAST_LOGIN,
				'updated' => SearchFields_ContactPerson::UPDATED,
			);
		}
		
		if(isset($tokens[$token]))
			return $tokens[$token];
		
		return NULL;
	}
	
	function getContext($model) {
		$labels = array();
		$values = array();
		$context = CerberusContexts::getContext(CerberusContexts::CONTEXT_CONTACT_PERSON, $model, $labels, $values, null, true);

		return $values;
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
			CerberusContexts::CONTEXT_CONTACT_PERSON,
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
			
			$custom_field_params = $this->_handleSearchBuildParamsCustomFields($filters, CerberusContexts::CONTEXT_CONTACT_PERSON);
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
			
			$models = CerberusContexts::getModels(CerberusContexts::CONTEXT_CONTACT_PERSON, array_keys($results));
			
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
		if(!$worker->hasPriv('core.addybook.addy.actions.update'))
			$this->error(self::ERRNO_ACL);
		
		// Validate the ID
		if(null == DAO_ContactPerson::get($id))
			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid contact person ID '%d'", $id));
			
		$putfields = array(
			'password' => 'string',
			'updated' => 'timestamp',
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
		$worker = CerberusApplication::getActiveWorker();
		
		// ACL
		if(!$worker->hasPriv('core.addybook.addy.actions.update'))
			$this->error(self::ERRNO_ACL);
		
		$postfields = array(
			'created' => 'timestamp',
			'email' => 'string',
			'password' => 'string',
			'updated' => 'timestamp',
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
		$worker = CerberusApplication::getActiveWorker();
		
		// ACL
		if(!$worker->hasPriv('core.addybook'))
			$this->error(self::ERRNO_ACL);

		$container = $this->_handlePostSearch();
		
		$this->success($container);
	}
};