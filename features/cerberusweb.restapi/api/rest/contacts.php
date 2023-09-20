<?php
class ChRest_Contacts extends Extension_RestController implements IExtensionRestController {
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
		$worker = CerberusApplication::getActiveWorker();
		if(!$worker->hasPriv('contexts.cerberusweb.contexts.contact.delete'))
			$this->error(self::ERRNO_ACL);

		$id = array_shift($stack);

		if(null == ($contact = DAO_Contact::get($id)))
			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid contact ID %d", $id));
		
		CerberusContexts::logActivityRecordDelete(CerberusContexts::CONTEXT_CONTACT, $contact->id, $contact->getNameWithEmail());
		
		DAO_Contact::delete($id);

		$result = array('id' => $id);
		$this->success($result);
	}
	
	function getId($id) {
		$worker = CerberusApplication::getActiveWorker();
		
		$container = $this->search(array(
			array('id', '=', $id),
		));
		
		if(is_array($container) && isset($container['results']) && isset($container['results'][$id]))
			$this->success($container['results'][$id]);

		// Error
		$this->error(self::ERRNO_CUSTOM, sprintf("Invalid contact id '%d'", $id));
	}
	
	function translateToken($token, $type='dao') {
		if('custom_' == substr($token, 0, 7) && in_array($type, array('search', 'subtotal'))) {
			return 'cf_' . intval(substr($token, 7));
		}
		
		$tokens = array();
		
		if('dao'==$type) {
			$tokens = array(
				'created_at' => DAO_Contact::CREATED_AT,
				'dob' => DAO_Contact::DOB,
				'email_id' => DAO_Contact::PRIMARY_EMAIL_ID,
				'first_name' => DAO_Contact::FIRST_NAME,
				'gender' => DAO_Contact::GENDER,
				'language' => DAO_Contact::LANGUAGE,
				'last_name' => DAO_Contact::LAST_NAME,
				'location' => DAO_Contact::LOCATION,
				'mobile' => DAO_Contact::MOBILE,
				'org_id' => DAO_Contact::ORG_ID,
				'password' => DAO_Contact::AUTH_PASSWORD,
				'phone' => DAO_Contact::PHONE,
				'timezone' => DAO_Contact::TIMEZONE,
				'title' => DAO_Contact::TITLE,
				'updated_at' => DAO_Contact::UPDATED_AT,
				'username' => DAO_Contact::USERNAME,
			);
			
		} elseif ('subtotal'==$type) {
			$tokens = array(
				'fieldsets' => SearchFields_Contact::VIRTUAL_HAS_FIELDSET,
				'links' => SearchFields_Contact::VIRTUAL_CONTEXT_LINK,
				'watchers' => SearchFields_Contact::VIRTUAL_WATCHERS,
					
				'first_name' => SearchFields_Contact::FIRST_NAME,
				'gender' => SearchFields_Contact::GENDER,
				'language' => SearchFields_Contact::LANGUAGE,
				'last_name' => SearchFields_Contact::LAST_NAME,
				'location' => SearchFields_Contact::LOCATION,
				'org' => SearchFields_Contact::ORG_NAME,
				'timezone' => SearchFields_Contact::TIMEZONE,
				'title' => SearchFields_Contact::TITLE,
			);
			
			$tokens_cfields = $this->_handleSearchTokensCustomFields(CerberusContexts::CONTEXT_CONTACT);
			
			if(is_array($tokens_cfields))
				$tokens = array_merge($tokens, $tokens_cfields);
			
		} else {
			$tokens = array(
				'created_at' => SearchFields_Contact::CREATED_AT,
				'dob' => SearchFields_Contact::DOB,
				'email_id' => SearchFields_Contact::PRIMARY_EMAIL_ID,
				'first_name' => SearchFields_Contact::FIRST_NAME,
				'gender' => SearchFields_Contact::GENDER,
				'id' => SearchFields_Contact::ID,
				'language' => SearchFields_Contact::LANGUAGE,
				'last_name' => SearchFields_Contact::LAST_NAME,
				'location' => SearchFields_Contact::LOCATION,
				'mobile' => SearchFields_Contact::MOBILE,
				'org_id' => SearchFields_Contact::ORG_ID,
				'phone' => SearchFields_Contact::PHONE,
				'timezone' => SearchFields_Contact::TIMEZONE,
				'title' => SearchFields_Contact::TITLE,
				'updated_at' => SearchFields_Contact::UPDATED_AT,
				'username' => SearchFields_Contact::USERNAME,
			);
		}
		
		if(isset($tokens[$token]))
			return $tokens[$token];
		
		return NULL;
	}
	
	function getContext($model) {
		$labels = array();
		$values = array();
		$context = CerberusContexts::getContext(CerberusContexts::CONTEXT_CONTACT, $model, $labels, $values, null, true);

		return $values;
	}
	
	function search($filters=array(), $sortToken='updated_at', $sortAsc=1, $page=1, $limit=10, $options=array()) {
		@$query = DevblocksPlatform::importVar($options['query'], 'string', null);
		@$show_results = DevblocksPlatform::importVar($options['show_results'], 'boolean', true);
		@$subtotals = DevblocksPlatform::importVar($options['subtotals'], 'array', array());
		
		$params = array();
		
		// Sort
		$sortBy = $this->translateToken($sortToken, 'search');
		$sortAsc = !empty($sortAsc) ? true : false;
		
		// Search
		$view = $this->_getSearchView(
			CerberusContexts::CONTEXT_CONTACT,
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
			
			$custom_field_params = $this->_handleSearchBuildParamsCustomFields($filters, CerberusContexts::CONTEXT_CONTACT);
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
			
			$models = CerberusContexts::getModels(CerberusContexts::CONTEXT_CONTACT, array_keys($results));
			
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
		
		$container = $this->_handlePostSearch();
		
		$this->success($container);
	}
	
	function putId($id) {
		$worker = CerberusApplication::getActiveWorker();
		
		// ACL
		if(!$worker->hasPriv('contexts.cerberusweb.contexts.contact.update'))
			$this->error(self::ERRNO_ACL);
		
		// Validate the ID
		if(null == DAO_Contact::get($id))
			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid contact ID '%d'", $id));
			
		$putfields = array(
			'created_at' => 'timestamp',
			'dob' => 'string',
			'email_id' => 'integer',
			'first_name' => 'string',
			'gender' => 'string',
			'language' => 'string',
			'last_name' => 'string',
			'location' => 'string',
			'mobile' => 'string',
			'org_id' => 'integer',
			'password' => 'string',
			'phone' => 'string',
			'timezone' => 'string',
			'title' => 'string',
			'updated_at' => 'timestamp',
			'username' => 'string',
		);
		
		// [TODO] Validate everything
		// [TODO] 'email' and 'org' shortcut

		$fields = array();
		
		// 'password' set hash w/ salt
		if(isset($_POST['password'])) {
			$password = DevblocksPlatform::importGPC($_POST['password'] ?? null, 'string', '');
			unset($_POST['password']);
			
			if(!empty($password)) {
				$salt = CerberusApplication::generatePassword(8);
				$fields[DAO_Contact::AUTH_SALT] = $salt;
				$fields[DAO_Contact::AUTH_PASSWORD] = md5($salt.md5($password));
			}
		}
		
		foreach($putfields as $putfield => $type) {
			if(!isset($_POST[$putfield]))
				continue;
			
			@$value = DevblocksPlatform::importGPC($_POST[$putfield], 'string', '');
			
			if(null == ($field = self::translateToken($putfield, 'dao'))) {
				$this->error(self::ERRNO_CUSTOM, sprintf("'%s' is not a valid field.", $putfield));
			}
			
			// Sanitize
			$value = DevblocksPlatform::importVar($value, $type);
			
			// Validate
			switch($field) {
				case 'primary_email_id':
					if($value != 0 && false == ($addy = DAO_Address::get($value)))
						$this->error(self::ERRNO_CUSTOM, sprintf("'email_id' (%d) is not a valid email address.", $value));
						
					if($addy->contact_id)
						$this->error(self::ERRNO_CUSTOM, sprintf("'email_id' (%d) is already associated with another contact.", $value));
					break;
					
				case 'org_id':
					if($value != 0 && false == ($org = DAO_ContactOrg::get($value)))
						$this->error(self::ERRNO_CUSTOM, sprintf("'org_id' is not a valid organization."));
					break;
			}
			
			$fields[$field] = $value;
		}
		
		// Handle custom fields
		$customfields = $this->_handleCustomFields($_POST);
		if(is_array($customfields))
			DAO_CustomFieldValue::formatAndSetFieldValues(CerberusContexts::CONTEXT_CONTACT, $id, $customfields, true, true, true);
		
		// Update
		DAO_Contact::update($id, $fields);
		$this->getId($id);
	}
	
	function postCreate() {
		$worker = CerberusApplication::getActiveWorker();
		
		// ACL
		if(!$worker->hasPriv('contexts.cerberusweb.contexts.contact.create'))
			$this->error(self::ERRNO_ACL);
		
		$postfields = array(
			'created_at' => 'timestamp',
			'dob' => 'string',
			'email_id' => 'integer',
			'first_name' => 'string',
			'gender' => 'string',
			'language' => 'string',
			'last_name' => 'string',
			'location' => 'string',
			'mobile' => 'string',
			'org_id' => 'integer',
			'password' => 'string',
			'phone' => 'string',
			'timezone' => 'string',
			'title' => 'string',
			'updated_at' => 'timestamp',
		);

		$fields = array();
		
		// [TODO] Validate everything
		// [TODO] 'email' and 'org' shortcut
		
		// 'password' set hash w/ salt
		if(isset($_POST['password'])) {
			$password = DevblocksPlatform::importGPC($_POST['password'] ?? null, 'string', '');
			unset($_POST['password']);
			
			if(!empty($password)) {
				$salt = CerberusApplication::generatePassword(8);
				$fields[DAO_Contact::AUTH_SALT] = $salt;
				$fields[DAO_Contact::AUTH_PASSWORD] = md5($salt.md5($password));
			}
		}
		
		foreach($postfields as $postfield => $type) {
			if(!isset($_POST[$postfield]))
				continue;
				
			@$value = DevblocksPlatform::importGPC($_POST[$postfield], 'string', '');
				
			if(null == ($field = self::translateToken($postfield, 'dao'))) {
				$this->error(self::ERRNO_CUSTOM, sprintf("'%s' is not a valid field.", $postfield));
			}

			// Sanitize
			$value = DevblocksPlatform::importVar($value, $type);
			
			// Validate
			// [TODO] Share this with putId()
			switch($field) {
				case 'primary_email_id':
					if($value != 0 && false == ($addy = DAO_Address::get($value)))
						$this->error(self::ERRNO_CUSTOM, sprintf("'email_id' (%d) is not a valid email address.", $value));
						
					if($addy->contact_id)
						$this->error(self::ERRNO_CUSTOM, sprintf("'email_id' (%d) is already associated with another contact.", $value));
					break;
					
				case 'org_id':
					if($value != 0 && false == ($org = DAO_ContactOrg::get($value)))
						$this->error(self::ERRNO_CUSTOM, sprintf("'org_id' is not a valid organization."));
					break;
			}
			
			$fields[$field] = $value;
		}
		
		// Check required fields
		$reqfields = array(
			DAO_Contact::FIRST_NAME,
		);
		$this->_handleRequiredFields($reqfields, $fields);
		
		// Create
		if(false != ($id = DAO_Contact::create($fields))) {
			// Handle custom fields
			$customfields = $this->_handleCustomFields($_POST);
			if(is_array($customfields))
				DAO_CustomFieldValue::formatAndSetFieldValues(CerberusContexts::CONTEXT_CONTACT, $id, $customfields, true, true, true);
			
			$this->getId($id);
		}
	}

	private function postNote($id) {
		$worker = CerberusApplication::getActiveWorker();

		$note = DevblocksPlatform::importGPC($_POST['note'] ?? null, 'string','');
		
		if(null == ($contact = DAO_Contact::get($id)))
			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid contact ID %d", $id));

		// ACL
		if(!$worker->hasPriv('contexts.cerberusweb.contexts.contact.comment'))
			$this->error(self::ERRNO_ACL);
		
		// Required fields
		if(empty($note))
			$this->error(self::ERRNO_CUSTOM, "The 'note' field is required.");
			
		// Post
		$fields = [
			DAO_Comment::CONTEXT => CerberusContexts::CONTEXT_CONTACT,
			DAO_Comment::CONTEXT_ID => $contact->id,
			DAO_Comment::OWNER_CONTEXT => CerberusContexts::CONTEXT_WORKER,
			DAO_Comment::OWNER_CONTEXT_ID => $worker->id,
			DAO_Comment::CREATED => time(),
			DAO_Comment::COMMENT => $note,
		];
		$note_id = DAO_Comment::create($fields);
		DAO_Comment::onUpdateByActor($worker, $fields, $note_id);
			
		$this->success(array(
			'contact_id' => $contact->id,
			'note_id' => $note_id,
		));
	}
};