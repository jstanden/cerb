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
//		CerberusContexts::logActivityRecordDelete(CerberusContexts::CONTEXT_WORKER, $worker->id, $worker->getName());
//
//		DAO_Worker::delete($id);
//
//		$result = array('id' => $id);
//		$this->success($result);
	}
	
	function getContext($model) {
		$labels = $values = [];
		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $model, $labels, $values, null, true);

		return $values;
	}
	
	function translateToken($token, $type='dao') {
		if('custom_' == substr($token, 0, 7) && in_array($type, array('search', 'subtotal'))) {
			return 'cf_' . intval(substr($token, 7));
		}
		
		$tokens = array();
		
		if('dao'==$type) {
			$tokens = array(
				'dob' => DAO_Worker::DOB,
				'email_id' => DAO_Worker::EMAIL_ID,
				'first_name' => DAO_Worker::FIRST_NAME,
				'gender' => DAO_Worker::GENDER,
				'is_disabled' => DAO_Worker::IS_DISABLED,
				'is_password_disabled' => DAO_Worker::IS_PASSWORD_DISABLED,
				'is_mfa_required' => DAO_Worker::IS_MFA_REQUIRED,
				'is_superuser' => DAO_Worker::IS_SUPERUSER,
				'language' => DAO_Worker::LANGUAGE,
				'last_name' => DAO_Worker::LAST_NAME,
				'location' => DAO_Worker::LOCATION,
				'mention' => DAO_Worker::AT_MENTION_NAME,
				'mobile' => DAO_Worker::MOBILE,
				'password' => 'password',
				'phone' => DAO_Worker::PHONE,
				'timezone' => DAO_Worker::TIMEZONE,
				'title' => DAO_Worker::TITLE,
				'updated' => DAO_Worker::UPDATED,
			);
			
		} elseif ('subtotal'==$type) {
			$tokens = array(
				'fieldsets' => SearchFields_Worker::VIRTUAL_HAS_FIELDSET,
				'links' => SearchFields_Worker::VIRTUAL_CONTEXT_LINK,
					
				'first_name' => SearchFields_Worker::FIRST_NAME,
				'gender' => SearchFields_Worker::GENDER,
				'is_disabled' => SearchFields_Worker::IS_DISABLED,
				'is_password_disabled' => SearchFields_Worker::IS_PASSWORD_DISABLED,
				'is_mfa_required' => SearchFields_Worker::IS_MFA_REQUIRED,
				'is_superuser' => SearchFields_Worker::IS_SUPERUSER,
				'language' => SearchFields_Worker::LANGUAGE,
				'last_name' => SearchFields_Worker::LAST_NAME,
				'location' => SearchFields_Worker::LOCATION,
				'timezone' => SearchFields_Worker::TIMEZONE,
				'title' => SearchFields_Worker::TITLE,
			);
			
			$tokens_cfields = $this->_handleSearchTokensCustomFields(CerberusContexts::CONTEXT_WORKER);
			
			if(is_array($tokens_cfields))
				$tokens = array_merge($tokens, $tokens_cfields);
			
		} else {
			$tokens = array(
				'dob' => SearchFields_Worker::DOB,
				'id' => SearchFields_Worker::ID,
				'email_id' => SearchFields_Worker::EMAIL_ID,
				'email' => SearchFields_Worker::EMAIL_ADDRESS,
				'first_name' => SearchFields_Worker::FIRST_NAME,
				'gender' => SearchFields_Worker::GENDER,
				'is_disabled' => SearchFields_Worker::IS_DISABLED,
				'is_password_disabled' => SearchFields_Worker::IS_PASSWORD_DISABLED,
				'is_mfa_required' => SearchFields_Worker::IS_MFA_REQUIRED,
				'is_superuser' => SearchFields_Worker::IS_SUPERUSER,
				'language' => SearchFields_Worker::LANGUAGE,
				'last_name' => SearchFields_Worker::LAST_NAME,
				'location' => SearchFields_Worker::LOCATION,
				'mention' => SearchFields_Worker::AT_MENTION_NAME,
				'mobile' => SearchFields_Worker::MOBILE,
				'phone' => SearchFields_Worker::PHONE,
				'timezone' => SearchFields_Worker::TIMEZONE,
				'title' => SearchFields_Worker::TITLE,
				'updated' => SearchFields_Worker::UPDATED,
			);
		}
		
		if(isset($tokens[$token]))
			return $tokens[$token];
		
		return NULL;
	}
	
	private function _validateFields($fields, $id=0) {
		if(isset($fields[DAO_Worker::AT_MENTION_NAME])) {
			$mentions = DAO_Worker::getMentions();
			$mention = DevblocksPlatform::strLower($fields[DAO_Worker::AT_MENTION_NAME]);
			
			if(isset($mentions[$mention]) && $mentions[$mention] != $id) {
				$this->error(self::ERRNO_CUSTOM, sprintf("The 'mention' of '%s' is already used.", $mention));
			}
		}
		
		if(isset($fields[DAO_Worker::DOB])) {
			$dob = $fields[DAO_Worker::DOB];
			
			if(!is_string($dob) || !preg_match('#\d{4}\-\d{1,2}\-{1,2}#', $dob) || false == @strtotime($dob))
				$this->error(self::ERRNO_CUSTOM, "The 'dob' field is not a valid YYYY-MM-DD date.");
		}
		
		if(isset($fields[DAO_Worker::GENDER])) {
			$fields[DAO_Worker::GENDER] = DevblocksPlatform::strUpper($fields[DAO_Worker::GENDER]);
			
			if(!in_array($fields[DAO_Worker::GENDER], ['','M','F']))
				$this->error(self::ERRNO_CUSTOM, "The 'gender' field must be '', 'M' or 'F'.");
		}
		
		if(isset($fields[DAO_Worker::LANGUAGE])) {
			$lang = $fields[DAO_Worker::LANGUAGE];
			
			$translate = DevblocksPlatform::getTranslationService();
			$languages = $translate->getLocaleCodes();
			
			if(!in_array($lang, $languages)) {
				$this->error(self::ERRNO_CUSTOM, "The 'language' field specifies an invalid langage code.");
			}
		}
		
		if(isset($fields[DAO_Worker::TIMEZONE])) {
			$date = DevblocksPlatform::services()->date();
			$timezones = $date->getTimezones();
			
			if(!in_array($fields[DAO_Worker::TIMEZONE], $timezones)) {
				$this->error(self::ERRNO_CUSTOM, "The 'timezone' field specifies an invalid time zone.");
			}
		}
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
		@$query = DevblocksPlatform::importVar($options['query'], 'string', null);
		@$show_results = DevblocksPlatform::importVar($options['show_results'], 'boolean', true);
		@$subtotals = DevblocksPlatform::importVar($options['subtotals'], 'array', array());
		
		$worker = CerberusApplication::getActiveWorker();
		
		$params = array();
		
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
		
		if(!empty($query) && $view instanceof IAbstractView_QuickSearch)
			$view->addParamsWithQuickSearch($query, true);

		// If we're given explicit filters, merge them in to our quick search
		if(!empty($filters)) {
			if(!empty($query))
				$params = $view->getParams(false);
			
			$custom_field_params = $this->_handleSearchBuildParamsCustomFields($filters, CerberusContexts::CONTEXT_WORKER);
			$new_params = $this->_handleSearchBuildParams($filters);
			$params = array_merge($params, $new_params, $custom_field_params);
			
			$view->addParams($params, true);
		}
		
		// (ACL) Limit non-superusers to themselves
		if(!$worker->is_superuser) {
			$params['tmp_worker_id'] = new DevblocksSearchCriteria(
				SearchFields_Worker::ID,
				'=',
				$worker->id
			);
		}
		
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
			'auth' => 'string',
			'dob' => 'string',
			'email_id' => 'integer',
			'first_name' => 'string',
			'gender' => 'string',
			'is_disabled' => 'bit',
			'is_superuser' => 'bit',
			'language' => 'string',
			'last_name' => 'string',
			'location' => 'string',
			'mention' => 'string',
			'mobile' => 'string',
			'phone' => 'string',
			'timezone' => 'string',
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
		
		// Validate $fields
		$this->_validateFields($fields, $id);
		
		// Handle custom fields
		$customfields = $this->_handleCustomFields($_POST);
		if(is_array($customfields))
			DAO_CustomFieldValue::formatAndSetFieldValues(CerberusContexts::CONTEXT_WORKER, $id, $customfields, true, true, true);
		
		// Update
		DAO_Worker::update($id, $fields);
		
		// Password change?
		@$password = DevblocksPlatform::importGPC($_POST['password'], 'string', '');
		if(!empty($password))
			DAO_Worker::setAuth($id, $password);
	
		$this->getId($id);
	}
	
	function postCreate() {
		$worker = CerberusApplication::getActiveWorker();
		
		// ACL
		if(!$worker->is_superuser)
			$this->error(self::ERRNO_ACL);
		
		$postfields = array(
			'auth' => 'string',
			'dob' => 'string',
			'email_id' => 'integer',
			'first_name' => 'string',
			'gender' => 'string',
			'is_disabled' => 'bit',
			'is_superuser' => 'bit',
			'language' => 'string',
			'last_name' => 'string',
			'location' => 'string',
			'mention' => 'string',
			'mobile' => 'string',
			'phone' => 'string',
			'timezone' => 'string',
			'title' => 'string',
			'updated' => 'timestamp',
		);

		$fields = array();
		
		// If we're given an email address as a string, convert it to email_id
		if(isset($_POST['email'])) {
			@$email = DevblocksPlatform::importGPC($_POST['email'], 'string', '');
			
			if(false == ($addy_model = DAO_Address::lookupAddress($email, true)))
				$this->error(self::ERRNO_CUSTOM, "'email' is an invalid value.");
			
			$_POST['email_id'] = $addy_model->id;
			unset($_POST['email']);
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
			
			$fields[$field] = $value;
		}
		
		if(false == ($addy_model = DAO_Address::get($fields['email_id'])))
			$this->error(self::ERRNO_CUSTOM, "'email_id' is an invalid value.");
		
		// Check required fields
		$reqfields = array(
			'email_id',
			'first_name',
		);
		$this->_handleRequiredFields($reqfields, $fields);
		
		// Validate $fields
		$this->_validateFields($fields);
		
		// Create
		if(false != ($id = DAO_Worker::create($fields))) {
			// Password (optional)
			@$password = DevblocksPlatform::importGPC($_POST['password'], 'string', '');
			if(!empty($password))
				DAO_Worker::setAuth($id, $password);
			
			// Handle custom fields
			$customfields = $this->_handleCustomFields($_POST);
			if(is_array($customfields))
				DAO_CustomFieldValue::formatAndSetFieldValues(CerberusContexts::CONTEXT_WORKER, $id, $customfields, true, true, true);
			
			$this->getId($id);
		}
	}
};