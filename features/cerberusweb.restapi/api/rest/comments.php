<?php
class ChRest_Comments extends Extension_RestController implements IExtensionRestController {
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
		$id = array_shift($stack);
		
		$container = $this->search(array(
			array('id', '=', $id),
		));
		
		if(is_array($container) && isset($container['results']) && isset($container['results'][$id])) {
			DAO_Comment::delete($id);
			$this->success(array('message' => sprintf("Comment '%d' was removed", $id)));
		} else {
			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid comment id '%d'", $id));
		}
	}
	
	private function getId($id) {
		$container = $this->search(array(
			array('id', '=', $id),
		));
		
		if(is_array($container) && isset($container['results']) && isset($container['results'][$id]))
			$this->success($container['results'][$id]);

		// Error
		$this->error(self::ERRNO_CUSTOM, sprintf("Invalid comment id '%d'", $id));
	}

	function translateToken($token, $type='dao') {
		if('custom_' == substr($token, 0, 7) && in_array($type, array('search', 'subtotal'))) {
			return 'cf_' . intval(substr($token, 7));
		}
		
		$tokens = array();
		
		if('dao'==$type) {
			$tokens = array(
				'context' => DAO_Comment::CONTEXT,
				'context_id' => DAO_Comment::CONTEXT_ID,
				'owner_context' => DAO_Comment::OWNER_CONTEXT,
				'owner_context_id' => DAO_Comment::OWNER_CONTEXT_ID,
				'comment' => DAO_Comment::COMMENT,
				'created' => DAO_Comment::CREATED,
			);
			
		} elseif ('subtotal'==$type) {
			$tokens = array(
				'fieldsets' => SearchFields_Comment::VIRTUAL_HAS_FIELDSET,
				'owner' => SearchFields_Comment::VIRTUAL_OWNER,
				'target' => SearchFields_Comment::VIRTUAL_TARGET,
			);
			
			$tokens_cfields = $this->_handleSearchTokensCustomFields(CerberusContexts::CONTEXT_COMMENT);
			
			if(is_array($tokens_cfields))
				$tokens = array_merge($tokens, $tokens_cfields);
			
		} else {
			$tokens = array(
				'id' => SearchFields_Comment::ID,
				'context' => SearchFields_Comment::CONTEXT,
				'context_id' => SearchFields_Comment::CONTEXT_ID,
				'owner_context' => SearchFields_Comment::OWNER_CONTEXT,
				'owner_context_id' => SearchFields_Comment::OWNER_CONTEXT_ID,
				'comment' => SearchFields_Comment::COMMENT,
			);
		}
		
		if(isset($tokens[$token]))
			return $tokens[$token];
		
		return NULL;
	}
	
	function getContext($model) {
		$labels = array();
		$values = array();
		$context = CerberusContexts::getContext(CerberusContexts::CONTEXT_COMMENT, $model, $labels, $values, null, true);

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
			CerberusContexts::CONTEXT_COMMENT,
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
			
			$new_params = $this->_handleSearchBuildParams($filters);
			$params = array_merge($params, $new_params);
			
			$view->addParams($params, true);
		}
		
		if($show_results)
			list($results, $total) = $view->getData();
		
		// Get subtotal data, if provided
		if(!empty($subtotals))
			$subtotal_data = $this->_handleSearchSubtotals($view, $subtotals);
		
		if($show_results) {
			$objects = array();
			
			$models = CerberusContexts::getModels(CerberusContexts::CONTEXT_COMMENT, array_keys($results));
			unset($results);
			
			if(is_array($models))
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
	
	function postCreate() {
		$worker = CerberusApplication::getActiveWorker();
		
		$postfields = array(
			'context' => 'string',
			'context_id' => 'integer',
			'owner_context' => 'string',
			'owner_context_id' => 'integer',
			'created' => 'integer',
			'comment' => 'string',
			'file_id' => 'array',
		);

		$context = DevblocksPlatform::importGPC($_POST['context'] ?? null, 'string', '');
		$context_id = DevblocksPlatform::importGPC($_POST['context_id'] ?? null, 'integer', 0);
		$owner_context = DevblocksPlatform::importGPC($_POST['owner_context'] ?? null, 'string', '');
		$owner_context_id = DevblocksPlatform::importGPC($_POST['owner_context_id'] ?? null, 'integer', 0);
		$file_ids = DevblocksPlatform::sanitizeArray(DevblocksPlatform::importGPC($_POST['file_id'] ?? null, 'array', []), 'int');

		$fields = array();
		
		foreach($postfields as $postfield => $type) {
			if(!isset($_POST[$postfield]))
				continue;
			
			if(in_array($postfield, array('file_id')))
				continue;
				
			@$value = DevblocksPlatform::importGPC($_POST[$postfield], 'string', '');
			
			switch($postfield) {
				case 'context':
					if(!CerberusContexts::isReadableByActor($context, $context_id, $worker))
						$this->error(self::ERRNO_ACL);
					break;
				
				case 'owner_context':
					if($worker->is_superuser) {
						// A superuser can do anything
						
					} else {
						// A worker cannot comment as the app, a role, or a group
						switch($owner_context) {
							case CerberusContexts::CONTEXT_APPLICATION:
							case CerberusContexts::CONTEXT_GROUP:
							case CerberusContexts::CONTEXT_ROLE:
								$this->error(self::ERRNO_ACL);
								break;
								
							case CerberusContexts::CONTEXT_WORKER:
								// A worker cannot comment as someone else
								if($owner_context_id != $worker->id)
									$this->error(self::ERRNO_ACL);
								break;
						}
					}
					break;
			}
			
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
		
		// Defaults
		if(!isset($fields[DAO_Comment::CREATED]))
			$fields[DAO_Comment::CREATED] = time();
		
		// Check required fields
		$reqfields = array(DAO_Comment::CONTEXT, DAO_Comment::CONTEXT_ID, DAO_Comment::OWNER_CONTEXT, DAO_Comment::OWNER_CONTEXT_ID, DAO_Comment::COMMENT);
		$this->_handleRequiredFields($reqfields, $fields);
		
		// Create
		if(($id = DAO_Comment::create($fields))) {
			// Attachments
			if(is_array($file_ids) && !empty($file_ids))
				DAO_Attachment::addLinks(CerberusContexts::CONTEXT_COMMENT, $id, $file_ids);
			
			DAO_Comment::onUpdateByActor($worker, $fields, $id);
			
			// Retrieve record
			$this->getId($id);
		}
	}
	
	function postSearch() {
		$worker = CerberusApplication::getActiveWorker();

		$container = $this->_handlePostSearch();
		
		$this->success($container);
	}
};