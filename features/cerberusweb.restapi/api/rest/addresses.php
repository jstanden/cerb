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
			default:
				$this->error(self::ERRNO_NOT_IMPLEMENTED);
				break;
		}
	}
	
	function deleteAction($stack) {
		// Consistency with the Web-UI
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
		
//		$worker = CerberusApplication::getActiveWorker();
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
		$worker = CerberusApplication::getActiveWorker();
		
		$container = $this->search(array(
			array('id', '=', $id),
		));
		
		if(is_array($container) && isset($container['results']) && isset($container['results'][$id]))
			$this->success($container['results'][$id]);

		// Error
		$this->error(self::ERRNO_CUSTOM, sprintf("Invalid address id '%d'", $id));
	}

	function translateToken($token, $type='dao') {
		if('custom_' == substr($token, 0, 7) && in_array($type, array('search', 'subtotal'))) {
			return 'cf_' . intval(substr($token, 7));
		}
		
		$tokens = array();
		
		if('dao'==$type) {
			$tokens = array(
				'email' => DAO_Address::EMAIL,
				'is_banned' => DAO_Address::IS_BANNED,
				'is_defunct' => DAO_Address::IS_DEFUNCT,
				'org_id' => DAO_Address::CONTACT_ORG_ID,
				'updated' => DAO_Address::UPDATED,
			);
			
		} elseif ('subtotal'==$type) {
			$tokens = array(
				'fieldsets' => SearchFields_Address::VIRTUAL_HAS_FIELDSET,
				'is_banned' => SearchFields_Address::IS_BANNED,
				'is_defunct' => SearchFields_Address::IS_DEFUNCT,
				'links' => SearchFields_Address::VIRTUAL_CONTEXT_LINK,
				'org_name' => SearchFields_Address::ORG_NAME,
				'watchers' => SearchFields_Address::VIRTUAL_WATCHERS,
			);
			
			$tokens_cfields = $this->_handleSearchTokensCustomFields(CerberusContexts::CONTEXT_ADDRESS);
			
			if(is_array($tokens_cfields))
				$tokens = array_merge($tokens, $tokens_cfields);
			
		} else {
			$tokens = array(
				'id' => SearchFields_Address::ID,
				'email' => SearchFields_Address::EMAIL,
				'is_banned' => SearchFields_Address::IS_BANNED,
				'is_defunct' => SearchFields_Address::IS_DEFUNCT,
				'num_nonspam' => SearchFields_Address::NUM_NONSPAM,
				'num_spam' => SearchFields_Address::NUM_SPAM,
				'org_id' => SearchFields_Address::CONTACT_ORG_ID,
				'updated' => SearchFields_Address::UPDATED,
			);
		}
		
		if(isset($tokens[$token]))
			return $tokens[$token];
		
		return NULL;
	}
	
	function getContext($model) {
		$labels = array();
		$values = array();
		$context = CerberusContexts::getContext(CerberusContexts::CONTEXT_ADDRESS, $model, $labels, $values, null, true);

		return $values;
	}
	
	function search($filters=array(), $sortToken='email', $sortAsc=1, $page=1, $limit=10, $options=array()) {
		@$query = DevblocksPlatform::importVar($options['query'], 'string', null);
		@$show_results = DevblocksPlatform::importVar($options['show_results'], 'boolean', true);
		@$subtotals = DevblocksPlatform::importVar($options['subtotals'], 'array', array());
		
		$params = array();
		
		// Sort
		$sortBy = $this->translateToken($sortToken, 'search');
		$sortAsc = !empty($sortAsc) ? true : false;

		// Search
		
		$view = $this->_getSearchView(
			CerberusContexts::CONTEXT_ADDRESS,
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

			$custom_field_params = $this->_handleSearchBuildParamsCustomFields($filters, CerberusContexts::CONTEXT_ADDRESS);
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
			
			$models = CerberusContexts::getModels(CerberusContexts::CONTEXT_ADDRESS, array_keys($results));
			
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
		if(null == DAO_Address::get($id))
			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid address ID '%d'", $id));
			
		$putfields = array(
			'is_banned' => 'bit',
			'is_defunct' => 'bit',
			'org_id' => 'integer',
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
		$worker = CerberusApplication::getActiveWorker();
		
		// ACL
		if(!$worker->hasPriv('core.addybook.addy.actions.update'))
			$this->error(self::ERRNO_ACL);
		
		$postfields = array(
			'email' => 'string',
			'is_banned' => 'bit',
			'org_id' => 'integer',
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
		$worker = CerberusApplication::getActiveWorker();
		
		$container = $this->_handlePostSearch();
		
		$this->success($container);
	}
};
