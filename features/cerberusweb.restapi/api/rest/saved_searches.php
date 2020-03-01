<?php
class ChRest_SavedSearches extends Extension_RestController implements IExtensionRestController {
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
		$worker = CerberusApplication::getActiveWorker();

		$id = array_shift($stack);

		if(null == ($search = DAO_ContextSavedSearch::get($id)))
			$this->error(self::ERRNO_NOT_FOUND, sprintf("Invalid saved search ID %d", $id));
		
		if(!Context_ContextSavedSearch::isWriteableByActor($search, $worker))
			$this->error(self::ERRNO_ACL);
		
		CerberusContexts::logActivityRecordDelete(CerberusContexts::CONTEXT_SAVED_SEARCH, $search->id, $search->name);
		
		DAO_ContextSavedSearch::delete($id);

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
		$this->error(self::ERRNO_NOT_FOUND, sprintf("Invalid saved search id '%d'", $id));
	}
	
	function translateToken($token, $type='dao') {
		if('custom_' == substr($token, 0, 7) && in_array($type, array('search', 'subtotal'))) {
			return 'cf_' . intval(substr($token, 7));
		}
		
		$tokens = array();
		
		if('dao'==$type) {
			$tokens = [
				'context' => DAO_ContextSavedSearch::CONTEXT,
				'name' => DAO_ContextSavedSearch::NAME,
				'owner_context' => DAO_ContextSavedSearch::OWNER_CONTEXT,
				'owner_context_id' => DAO_ContextSavedSearch::OWNER_CONTEXT_ID,
				'query' => DAO_ContextSavedSearch::QUERY,
				'tag' => DAO_ContextSavedSearch::TAG,
				'updated_at' => DAO_ContextSavedSearch::UPDATED_AT,
			];
			
		} elseif ('subtotal'==$type) {
			$tokens = array(
				'context' => SearchFields_ContextSavedSearch::CONTEXT,
				'owner_context' => SearchFields_ContextSavedSearch::OWNER_CONTEXT, // [TODO] Virtual?
			);
			
			$tokens_cfields = $this->_handleSearchTokensCustomFields(CerberusContexts::CONTEXT_SAVED_SEARCH);
			
			if(is_array($tokens_cfields))
				$tokens = array_merge($tokens, $tokens_cfields);
			
		} else {
			$tokens = array(
				'context' => SearchFields_ContextSavedSearch::CONTEXT,
				'id' => SearchFields_ContextSavedSearch::ID,
				'name' => SearchFields_ContextSavedSearch::NAME,
				'owner_context' => SearchFields_ContextSavedSearch::OWNER_CONTEXT,
				'owner_context_id' => SearchFields_ContextSavedSearch::OWNER_CONTEXT_ID,
				'query' => SearchFields_ContextSavedSearch::QUERY,
				'tag' => SearchFields_ContextSavedSearch::TAG,
				'updated_at' => SearchFields_ContextSavedSearch::UPDATED_AT,
			);
		}
		
		if(isset($tokens[$token]))
			return $tokens[$token];
		
		return NULL;
	}
	
	function getContext($model) {
		$labels = array();
		$values = array();
		$context = CerberusContexts::getContext(CerberusContexts::CONTEXT_SAVED_SEARCH, $model, $labels, $values, null, true);

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
			CerberusContexts::CONTEXT_SAVED_SEARCH,
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
			
			$custom_field_params = $this->_handleSearchBuildParamsCustomFields($filters, CerberusContexts::CONTEXT_SAVED_SEARCH);
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
			
			$models = CerberusContexts::getModels(CerberusContexts::CONTEXT_SAVED_SEARCH, array_keys($results));
			
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
		
		// Validate the ID
		if(false == ($search = DAO_ContextSavedSearch::get($id)))
			$this->error(self::ERRNO_NOT_FOUND, sprintf("Invalid saved search ID '%d'", $id));
		
		// ACL
		if(!Context_ContextSavedSearch::isWriteableByActor($search, $worker))
			$this->error(self::ERRNO_ACL);
			
		$putfields = array(
			'context' => 'string',
			'name' => 'string',
			'owner_context' => 'string',
			'owner_context_id' => 'integer',
			'query' => 'string',
			'tag' => 'string',
			'updated_at' => 'timestamp',
		);

		$fields = array();
		
		foreach($putfields as $putfield => $type) {
			if(!isset($_POST[$putfield]))
				continue;
			
			@$value = DevblocksPlatform::importGPC($_POST[$putfield], 'string', '');
			
			if(null == ($field = self::translateToken($putfield, 'dao'))) {
				$this->error(self::ERRNO_PARAM_INVALID, sprintf("'%s' is not a valid field.", $putfield));
			}
			
			// Sanitize
			$value = DevblocksPlatform::importVar($value, $type);
			
			$fields[$field] = $value;
		}
		
		// Validate fields from DAO
		if(!DAO_ContextSavedSearch::validate($fields, $error, $id))
			$this->error(self::ERRNO_PARAM_INVALID, $error);
		
		if(!DAO_ContextSavedSearch::onBeforeUpdateByActor($worker, $fields, $id, $error))
			$this->error(self::ERRNO_PARAM_INVALID, $error);
		
		// Update
		DAO_ContextSavedSearch::update($id, $fields);
		DAO_ContextSavedSearch::onUpdateByActor($worker, $fields, $id);
		
		// Handle custom fields
		$customfields = $this->_handleCustomFields($_POST);
		if(is_array($customfields))
			DAO_CustomFieldValue::formatAndSetFieldValues(CerberusContexts::CONTEXT_SAVED_SEARCH, $id, $customfields, true, true, true);
		
		$this->getId($id);
	}
	
	function postCreate() {
		$worker = CerberusApplication::getActiveWorker();
		
		$postfields = array(
			'context' => 'string',
			'name' => 'string',
			'owner_context' => 'string',
			'owner_context_id' => 'integer',
			'query' => 'string',
			'tag' => 'string',
			'updated_at' => 'timestamp',
		);

		$fields = array();
		
		foreach($postfields as $postfield => $type) {
			if(!isset($_POST[$postfield]))
				continue;
			
			@$value = DevblocksPlatform::importGPC($_POST[$postfield], 'string', '');
			
			if(null == ($field = self::translateToken($postfield, 'dao'))) {
				$this->error(self::ERRNO_PARAM_INVALID, sprintf("'%s' is not a valid field.", $postfield));
			}
			
			// Sanitize
			$value = DevblocksPlatform::importVar($value, $type);
			
			$fields[$field] = $value;
		}
		
		// Check required fields
		$reqfields = array(
			DAO_ContextSavedSearch::CONTEXT,
			DAO_ContextSavedSearch::NAME,
			DAO_ContextSavedSearch::OWNER_CONTEXT,
			DAO_ContextSavedSearch::OWNER_CONTEXT_ID,
			DAO_ContextSavedSearch::QUERY,
		);
		$this->_handleRequiredFields($reqfields, $fields);
		
		// Validate fields from DAO
		if(!DAO_ContextSavedSearch::validate($fields, $error))
			$this->error(self::ERRNO_PARAM_INVALID, $error);
		
		if(!DAO_ContextSavedSearch::onBeforeUpdateByActor($worker, $fields, null, $error))
			$this->error(self::ERRNO_PARAM_INVALID, $error);
		
		// Create
		if(false != ($id = DAO_ContextSavedSearch::create($fields))) {
			DAO_ContextSavedSearch::onUpdateByActor($worker, $fields, $id);
			
			// Handle custom fields
			$customfields = $this->_handleCustomFields($_POST);
			if(is_array($customfields))
				DAO_CustomFieldValue::formatAndSetFieldValues(CerberusContexts::CONTEXT_SAVED_SEARCH, $id, $customfields, true, true, true);
			
			$this->getId($id);
		}
	}
};