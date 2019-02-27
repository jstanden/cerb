<?php
class ChRest_Notifications extends Extension_RestController implements IExtensionRestController {
	function getAction($stack) {
		@$action = array_shift($stack);
		
		// Looking up a single notification ID?
		if(is_numeric($action)) {
			$this->getId(intval($action));
			
		} else { // actions
			switch($action) {
				case 'list':
					$this->getList();
					break;
					
				default:
					break;
			}
		}
		
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	function putAction($stack) {
		@$action = array_shift($stack);
		
		// Looking up a single notification ID?
		if(is_numeric($action)) {
			$this->putId(intval($action));
			
		} else { // actions
			switch($action) {
				default:
					break;
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
	}
	
	function translateToken($token, $type='dao') {
		if('custom_' == substr($token, 0, 7) && in_array($type, array('search', 'subtotal'))) {
			return 'cf_' . intval(substr($token, 7));
		}
		
		$tokens = array();
		
		if('dao'==$type) {
			$tokens = array(
				'activity_point' => DAO_Notification::ACTIVITY_POINT,
				'worker_id' => DAO_Notification::WORKER_ID,
				'context' => DAO_Notification::CONTEXT,
				'context_id' => DAO_Notification::CONTEXT_ID,
				'created' => DAO_Notification::CREATED_DATE,
				'is_read' => DAO_Notification::IS_READ,
			);
			
		} elseif ('subtotal'==$type) {
			$tokens = array(
				'activity_point' => SearchFields_Notification::ACTIVITY_POINT,
				'is_read' => SearchFields_Notification::IS_READ,
			);
			
			$tokens_cfields = $this->_handleSearchTokensCustomFields(CerberusContexts::CONTEXT_NOTIFICATION);
			
			if(is_array($tokens_cfields))
				$tokens = array_merge($tokens, $tokens_cfields);
			
		} else {
			$tokens = array(
				'context' => SearchFields_Notification::CONTEXT,
				'context_id' => SearchFields_Notification::CONTEXT_ID,
				'created' => SearchFields_Notification::CREATED_DATE,
				'id' => SearchFields_Notification::ID,
				'is_read' => SearchFields_Notification::IS_READ,
				'activity_point' => SearchFields_Notification::ACTIVITY_POINT,
				'worker_id' => SearchFields_Notification::WORKER_ID,
			);
		}
		
		if(isset($tokens[$token]))
			return $tokens[$token];
		
		return NULL;
	}
	
	function getContext($model) {
		$labels = $values = [];
		CerberusContexts::getContext(CerberusContexts::CONTEXT_NOTIFICATION, $model, $labels, $values, null, true);

		return $values;
	}
	
	private function getId($id) {
		$container = $this->search(array(
			array('id', '=', $id),
		));
		
		if(is_array($container) && isset($container['results']) && isset($container['results'][$id]))
			$this->success($container['results'][$id]);

		// Error
		$this->error(self::ERRNO_CUSTOM, sprintf("Invalid notification id '%d'", $id));
	}
	
	private function getList() {
		$worker = CerberusApplication::getActiveWorker();

		@$page = DevblocksPlatform::importGPC($_REQUEST['page'],'integer',1);
		@$unread = DevblocksPlatform::importGPC($_REQUEST['unread'],'string','');
		@$sortAsc = DevblocksPlatform::importGPC($_REQUEST['sortAsc'],'integer',0);
		@$sortBy = DevblocksPlatform::importGPC($_REQUEST['sortBy'],'string','');
		
		$filters = array(
			array('worker_id', '=', $worker->id),
		);
		
		if(0 != strlen($unread)) {
			$filters[] = array('is_read', '=', ($unread ? 0 : 1));
		}
		
		$container = $this->search(
			$filters,
			$sortBy,
			$sortAsc,
			$page,
			10
		);
		
		$this->success($container);
	}

	function search($filters=array(), $sortToken='id', $sortAsc=1, $page=1, $limit=10, $options=array()) {
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
			CerberusContexts::CONTEXT_NOTIFICATION,
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
			
			$custom_field_params = $this->_handleSearchBuildParamsCustomFields($filters, CerberusContexts::CONTEXT_NOTIFICATION);
			$new_params = $this->_handleSearchBuildParams($filters);
			$params = array_merge($params, $new_params, $custom_field_params);
			
			$view->addParams($params, true);
		}
		
		// (ACL) Add worker group privs
		if(!$worker->is_superuser) {
			$params['tmp_worker_id'] = new DevblocksSearchCriteria(
				SearchFields_Notification::WORKER_ID,
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
			
			$models = CerberusContexts::getModels(CerberusContexts::CONTEXT_NOTIFICATION, array_keys($results));
			
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
		$container = $this->_handlePostSearch();
		
		$this->success($container);
	}
	
	function putId($id) {
		$worker = CerberusApplication::getActiveWorker();
		
		// Validate the ID
		if(null == ($event = DAO_Notification::get($id)))
			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid notification ID '%d'", $id));
			
		// ACL
		if(!($worker->is_superuser || $worker->id==$event->worker_id))
			$this->error(self::ERRNO_ACL);
			
		$putfields = array(
			'worker_id' => 'integer',
			'created' => 'timestamp',
			'is_read' => 'bit',
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
		
		// Update
		DAO_Notification::update($id, $fields);
		$this->getId($id);
	}
	
	function postCreate() {
		// ACL
//		if(!$worker->is_superuser)
//			$this->error(self::ERRNO_ACL);
		
		$postfields = array(
			'worker_id' => 'integer',
			'context' => 'string',
			'context_id' => 'integer',
			'created' => 'timestamp',
			'is_read' => 'bit',
			'message' => 'string',
		);
		
		$fields = array(
			DAO_Notification::ACTIVITY_POINT => 'custom.other',
		);
		
		foreach($postfields as $postfield => $type) {
			if(!isset($_POST[$postfield]))
				continue;
				
			@$value = DevblocksPlatform::importGPC($_POST[$postfield], 'string', '');
			
			switch($postfield) {
				case 'message':
					$value = DevblocksPlatform::importVar($value, $type);
					$fields['_message'] = $value;
					break;
					
				default:
					if(null == ($field = self::translateToken($postfield, 'dao')))
						$this->error(self::ERRNO_CUSTOM, sprintf("'%s' is not a valid field.", $postfield));
						
					// Sanitize
					$value = DevblocksPlatform::importVar($value, $type);
						
					$fields[$field] = $value;
					break;
			}
		}
		
		// Check required fields
		$reqfields = array(
			DAO_Notification::ACTIVITY_POINT,
			DAO_Notification::CONTEXT,
			DAO_Notification::CONTEXT_ID,
			DAO_Notification::WORKER_ID,
			'_message',
		);
		$this->_handleRequiredFields($reqfields, $fields);
		
		// [TODO] Verify context+id
		
		$entry = array(
			//{{message}}
			'message' => 'activities.custom.other',
			'variables' => array(
				'message' => $fields['_message'],
				),
			'urls' => array(
				'message' => sprintf("ctx://%s:%d", $fields['context'], $fields['context_id']),
				)
		);
		$fields[DAO_Notification::ENTRY_JSON] = json_encode($entry);
		
		unset($fields['_message']);
		
		if(!isset($fields[DAO_Notification::CREATED_DATE]))
			$fields[DAO_Notification::CREATED_DATE] = time();
		
		// Create
		if(false != ($id = DAO_Notification::create($fields))) {
			$this->getId($id);
		}
	}
};
