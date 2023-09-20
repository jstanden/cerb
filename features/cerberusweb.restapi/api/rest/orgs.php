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
		$worker = CerberusApplication::getActiveWorker();
		if(!$worker->hasPriv('contexts.cerberusweb.contexts.org.delete'))
			$this->error(self::ERRNO_ACL);

		$id = array_shift($stack);

		if(null == ($org = DAO_ContactOrg::get($id)))
			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid organization ID %d", $id));
		
		CerberusContexts::logActivityRecordDelete(CerberusContexts::CONTEXT_ORG, $org->id, $org->name);
		
		DAO_ContactOrg::delete($id);

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
		$this->error(self::ERRNO_CUSTOM, sprintf("Invalid org id '%d'", $id));
	}
	
	function translateToken($token, $type='dao') {
		if('custom_' == substr($token, 0, 7) && in_array($type, array('search', 'subtotal'))) {
			return 'cf_' . intval(substr($token, 7));
		}
		
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
				'updated' => DAO_ContactOrg::UPDATED,
			);
			
		} elseif ('subtotal'==$type) {
			$tokens = array(
				'fieldsets' => SearchFields_ContactOrg::VIRTUAL_HAS_FIELDSET,
				'links' => SearchFields_ContactOrg::VIRTUAL_CONTEXT_LINK,
				'watchers' => SearchFields_ContactOrg::VIRTUAL_WATCHERS,
					
				'country' => SearchFields_ContactOrg::COUNTRY,
				'province' => SearchFields_ContactOrg::PROVINCE,
				'postal' => SearchFields_ContactOrg::POSTAL,
			);
			
			$tokens_cfields = $this->_handleSearchTokensCustomFields(CerberusContexts::CONTEXT_ORG);
			
			if(is_array($tokens_cfields))
				$tokens = array_merge($tokens, $tokens_cfields);
			
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
				'updated' => SearchFields_ContactOrg::UPDATED,
			);
		}
		
		if(isset($tokens[$token]))
			return $tokens[$token];
		
		return NULL;
	}
	
	function getContext($model) {
		$labels = array();
		$values = array();
		$context = CerberusContexts::getContext(CerberusContexts::CONTEXT_ORG, $model, $labels, $values, null, true);

		return $values;
	}
	
	function search($filters=array(), $sortToken='name', $sortAsc=1, $page=1, $limit=10, $options=array()) {
		@$query = DevblocksPlatform::importVar($options['query'], 'string', null);
		@$show_results = DevblocksPlatform::importVar($options['show_results'], 'boolean', true);
		@$subtotals = DevblocksPlatform::importVar($options['subtotals'], 'array', array());
		
		$params = array();
		
		// Sort
		$sortBy = $this->translateToken($sortToken, 'search');
		$sortAsc = !empty($sortAsc) ? true : false;
		
		// Search
		
		$view = $this->_getSearchView(
			CerberusContexts::CONTEXT_ORG,
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
			
			$custom_field_params = $this->_handleSearchBuildParamsCustomFields($filters, CerberusContexts::CONTEXT_ORG);
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
			
			$models = CerberusContexts::getModels(CerberusContexts::CONTEXT_ORG, array_keys($results));
			
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
		if(!$worker->hasPriv('contexts.cerberusweb.contexts.org.update'))
			$this->error(self::ERRNO_ACL);
		
		// Validate the ID
		if(null == DAO_ContactOrg::get($id))
			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid org ID '%d'", $id));
			
		$putfields = array(
			'name' => 'string',
			'street' => 'string',
			'city' => 'string',
			'created' => 'timestamp',
			'province' => 'string',
			'postal' => 'string',
			'country' => 'string',
			'phone' => 'string',
			'updated' => 'timestamp',
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
			
			$fields[$field] = $value;
		}
		
		// Handle custom fields
		$customfields = $this->_handleCustomFields($_POST);
		if(is_array($customfields))
			DAO_CustomFieldValue::formatAndSetFieldValues(CerberusContexts::CONTEXT_ORG, $id, $customfields, true, true, true);
		
		// Update
		DAO_ContactOrg::update($id, $fields);
		$this->getId($id);
	}
	
	function postCreate() {
		$worker = CerberusApplication::getActiveWorker();
		
		// ACL
		if(!$worker->hasPriv('contexts.cerberusweb.contexts.org.create'))
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
		$worker = CerberusApplication::getActiveWorker();

		$note = DevblocksPlatform::importGPC($_POST['note'] ?? null, 'string','');
		
		if(null == ($org = DAO_ContactOrg::get($id)))
			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid org ID %d", $id));

		// ACL
		if(!$worker->hasPriv('contexts.cerberusweb.contexts.org.update'))
			$this->error(self::ERRNO_ACL);
		
		// Required fields
		if(empty($note))
			$this->error(self::ERRNO_CUSTOM, "The 'note' field is required.");
			
		// Post
		$fields = [
			DAO_Comment::CONTEXT => CerberusContexts::CONTEXT_ORG,
			DAO_Comment::CONTEXT_ID => $org->id,
			DAO_Comment::OWNER_CONTEXT => CerberusContexts::CONTEXT_WORKER,
			DAO_Comment::OWNER_CONTEXT_ID => $worker->id,
			DAO_Comment::CREATED => time(),
			DAO_Comment::COMMENT => $note,
		];
		$note_id = DAO_Comment::create($fields);
		DAO_Comment::onUpdateByActor($worker, $fields, $note_id);
			
		$this->success(array(
			'org_id' => $org->id,
			'note_id' => $note_id,
		));
	}
};