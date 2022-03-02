<?php
class ChRest_Snippets extends Extension_RestController implements IExtensionRestController {
	function getAction($stack) {
		@$action = array_shift($stack);
		
		// Looking up a single ID?
		if(is_numeric($action)) {
			$this->getId(intval($action));
			
		} else { // actions
			switch($action) {
				case 'paste':
					@$id = array_shift($stack);
					
					if(!is_numeric($id))
						$this->error(self::ERRNO_NOT_IMPLEMENTED);
					
					$this->getPasteId(intval($id));
					
					break;
				
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

		if(null == ($snippet = DAO_Snippet::get($id)))
			$this->error(self::ERRNO_NOT_FOUND, sprintf("Invalid snippet ID %d", $id));
		
		if(!Context_Snippet::isDeletableByActor($snippet, $worker))
			$this->error(self::ERRNO_ACL);
		
		CerberusContexts::logActivityRecordDelete(CerberusContexts::CONTEXT_SNIPPET, $snippet->id, $snippet->title);
		
		DAO_Snippet::delete($id);

		$result = array('id' => $id);
		$this->success($result);
	}
	
	function getId($id) {
		$context_id = DevblocksPlatform::importVar($_REQUEST['context_id'] ?? null, 'integer', 0);
		$prompts = DevblocksPlatform::importVar($_REQUEST['prompts'] ?? null, 'array', []);
		
		$worker = CerberusApplication::getActiveWorker();
		
		$container = $this->search(array(
			array('id', '=', $id),
		));
		
		if(is_array($container) && isset($container['results']) && isset($container['results'][$id])) {
			$values = $container['results'][$id] ?? null;
			$context = $values['context'] ?? null;
			
			if($context_id || $prompts) {
				if(!$context && $context_id)
					$this->error(self::ERRNO_PARAM_INVALID, "Plaintext snippets don't target records.");
				
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				
				$target_values = [];
				
				if($context && $context_id) {
					if(false == ($context_ext = Extension_DevblocksContext::get($context)))
						$this->error(self::ERRNO_PARAM_INVALID, sprintf("This snippet has an invalid context (%s).", $context));
					
					if(!CerberusContexts::isReadableByActor($context, $context_id, $worker))
						$this->error(self::ERRNO_ACL, "You do not have permission to load the target record.");
					
					CerberusContexts::getContext($context, $context_id, $targer_labels, $target_values);
					
					if(empty($target_values) || !is_array($target_values) || !isset($target_values['id']))
						$this->error(self::ERRNO_NOT_FOUND, sprintf("The target record doesn't exist (%s:%d).", $context, $context_id));
				}
				
				// Handle prompted values
				// [TODO] Validate
				if(!empty($prompts))
				foreach($prompts as $k => $v)
					$target_values[$k] = $v;
				
				$paste = $tpl_builder->build($values['content'], $target_values);
				$values['content'] = $paste;
			}
			
			$this->success($values);
		}

		// Error
		$this->error(self::ERRNO_NOT_FOUND, sprintf("Invalid snippet id '%d'", $id));
	}
	
	function translateToken($token, $type='dao') {
		if('custom_' == substr($token, 0, 7) && in_array($type, array('search', 'subtotal'))) {
			return 'cf_' . intval(substr($token, 7));
		}
		
		$tokens = array();
		
		if('dao'==$type) {
			$tokens = [
				'context' => DAO_Snippet::CONTEXT,
				'content' => DAO_Snippet::CONTENT,
				'title' => DAO_Snippet::TITLE,
				'owner_context' => DAO_Snippet::OWNER_CONTEXT,
				'owner_context_id' => DAO_Snippet::OWNER_CONTEXT_ID,
				'updated_at' => DAO_Snippet::UPDATED_AT,
			];
			
		} elseif ('subtotal'==$type) {
			$tokens = array(
				'context' => SearchFields_Snippet::CONTEXT,
				'owner' => SearchFields_Snippet::VIRTUAL_OWNER,
			);
			
			$tokens_cfields = $this->_handleSearchTokensCustomFields(CerberusContexts::CONTEXT_SNIPPET);
			
			if(is_array($tokens_cfields))
				$tokens = array_merge($tokens, $tokens_cfields);
			
		} else {
			$tokens = array(
				'context' => SearchFields_Snippet::CONTEXT,
				'content' => SearchFields_Snippet::FULLTEXT_SNIPPET,
				'id' => SearchFields_Snippet::ID,
				'title' => SearchFields_Snippet::TITLE,
				'owner_context' => SearchFields_Snippet::OWNER_CONTEXT,
				'owner_context_id' => SearchFields_Snippet::OWNER_CONTEXT_ID,
				'total_uses' => SearchFields_Snippet::TOTAL_USES,
				'updated_at' => SearchFields_Snippet::UPDATED_AT,
			);
		}
		
		if(isset($tokens[$token]))
			return $tokens[$token];
		
		return NULL;
	}
	
	function getContext($model) {
		$labels = array();
		$values = array();
		$context = CerberusContexts::getContext(CerberusContexts::CONTEXT_SNIPPET, $model, $labels, $values, null, true);

		return $values;
	}
	
	function search($filters=array(), $sortToken='title', $sortAsc=1, $page=1, $limit=10, $options=array()) {
		@$query = DevblocksPlatform::importVar($options['query'], 'string', null);
		@$show_results = DevblocksPlatform::importVar($options['show_results'], 'boolean', true);
		@$subtotals = DevblocksPlatform::importVar($options['subtotals'], 'array', array());
		
		$params = array();
		
		// Sort
		$sortBy = $this->translateToken($sortToken, 'search');
		$sortAsc = !empty($sortAsc) ? true : false;
		
		// Search
		
		$view = $this->_getSearchView(
			CerberusContexts::CONTEXT_SNIPPET,
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
			
			$custom_field_params = $this->_handleSearchBuildParamsCustomFields($filters, CerberusContexts::CONTEXT_SNIPPET);
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
			
			$models = CerberusContexts::getModels(CerberusContexts::CONTEXT_SNIPPET, array_keys($results));
			
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
		if(false == ($search = DAO_Snippet::get($id)))
			$this->error(self::ERRNO_NOT_FOUND, sprintf("Invalid snippet ID '%d'", $id));
		
		// ACL
		if(!Context_Snippet::isWriteableByActor($search, $worker))
			$this->error(self::ERRNO_ACL);
			
		$putfields = array(
			'context' => 'string',
			'content' => 'string',
			'owner_context' => 'string',
			'owner_context_id' => 'integer',
			'title' => 'string',
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
		if(!DAO_Snippet::validate($fields, $error, $id))
			$this->error(self::ERRNO_PARAM_INVALID, $error);
		
		if(!DAO_Snippet::onBeforeUpdateByActor($worker, $fields, $id, $error))
			$this->error(self::ERRNO_PARAM_INVALID, $error);
		
		// Update
		DAO_Snippet::update($id, $fields);
		DAO_Snippet::onUpdateByActor($worker, $fields, $id);
		
		// Handle custom fields
		$customfields = $this->_handleCustomFields($_POST);
		if(is_array($customfields))
			DAO_CustomFieldValue::formatAndSetFieldValues(CerberusContexts::CONTEXT_SNIPPET, $id, $customfields, true, true, true);
		
		$this->getId($id);
	}
	
	function postCreate() {
		$worker = CerberusApplication::getActiveWorker();
		
		$postfields = array(
			'context' => 'string',
			'content' => 'string',
			'owner_context' => 'string',
			'owner_context_id' => 'integer',
			'title' => 'string',
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
			DAO_Snippet::CONTEXT,
			DAO_Snippet::CONTENT,
			DAO_Snippet::OWNER_CONTEXT,
			DAO_Snippet::OWNER_CONTEXT_ID,
			DAO_Snippet::TITLE,
		);
		$this->_handleRequiredFields($reqfields, $fields);
		
		// If blank it's plaintext (we don't want to validate it)
		if(empty($fields[DAO_Snippet::CONTEXT]))
			unset($fields[DAO_Snippet::CONTEXT]);
		
		// Validate fields from DAO
		// [TODO] Why aren't we validating everything in API?
		if(!DAO_Snippet::validate($fields, $error))
			$this->error(self::ERRNO_PARAM_INVALID, $error);
		
		if(!DAO_Snippet::onBeforeUpdateByActor($worker, $fields, null, $error))
			$this->error(self::ERRNO_PARAM_INVALID, $error);
		
		// Create
		if(false != ($id = DAO_Snippet::create($fields))) {
			DAO_Snippet::onUpdateByActor($worker, $fields, $id);
			
			// Handle custom fields
			$customfields = $this->_handleCustomFields($_POST);
			if(is_array($customfields))
				DAO_CustomFieldValue::formatAndSetFieldValues(CerberusContexts::CONTEXT_SNIPPET, $id, $customfields, true, true, true);
			
			$this->getId($id);
		}
	}
};