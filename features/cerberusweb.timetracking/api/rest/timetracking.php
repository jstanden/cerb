<?php
class ChRest_TimeTracking extends Extension_RestController implements IExtensionRestController {
	function getAction($stack) {
		@$action = array_shift($stack);
		
		// Looking up a single ID?
		if(is_numeric($action)) {
			$this->getId(intval($action));
			
		} else { // actions
			switch($action) {
				case 'activities':
					$this->getActivities();
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
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	private function getId($id) {
		$container = $this->search(array(
			array('id', '=', $id),
		));
		
		if(is_array($container) && isset($container['results']) && isset($container['results'][$id]))
			$this->success($container['results'][$id]);

		// Error
		$this->error(self::ERRNO_CUSTOM, sprintf("Invalid time entry id '%d'", $id));
	}

	private function getActivities() {
		$none = new Model_TimeTrackingActivity();
		$none->id = 0;
		$none->name = '(none)';
		
		$activities = DAO_TimeTrackingActivity::getWhere();
		$activities = array_merge(array(0 => $none), $activities);

		$results = array();
		
		foreach($activities as $id => $activity) {
			$results[$id] = array(
				'id' => $id,
				'name' => $activity->name,
			);
		}
		
		$this->success(array('results' => $results));
	}
	
	function translateToken($token, $type='dao') {
		if('custom_' == substr($token, 0, 7) && in_array($type, array('search', 'subtotal'))) {
			return 'cf_' . intval(substr($token, 7));
		}
		
		$tokens = array();
		
		if('dao'==$type) {
			$tokens = array(
				'activity_id' => DAO_TimeTrackingEntry::ACTIVITY_ID,
				'created' => DAO_TimeTrackingEntry::LOG_DATE,
				'id' => DAO_TimeTrackingEntry::ID,
				'is_closed' => DAO_TimeTrackingEntry::IS_CLOSED,
				'mins' => DAO_TimeTrackingEntry::TIME_ACTUAL_MINS,
				'worker_id' => DAO_TimeTrackingEntry::WORKER_ID,
			);
			
		} elseif ('subtotal'==$type) {
			$tokens = array(
				'fieldsets' => SearchFields_TimeTrackingEntry::VIRTUAL_HAS_FIELDSET,
				'links' => SearchFields_TimeTrackingEntry::VIRTUAL_CONTEXT_LINK,
				'watchers' => SearchFields_TimeTrackingEntry::VIRTUAL_WATCHERS,
					
				'activity' => SearchFields_TimeTrackingEntry::ACTIVITY_ID,
				'is_closed' => SearchFields_TimeTrackingEntry::IS_CLOSED,
				'worker_id' => SearchFields_TimeTrackingEntry::WORKER_ID,
			);
			
			$tokens_cfields = $this->_handleSearchTokensCustomFields(CerberusContexts::CONTEXT_TIMETRACKING);
			
			if(is_array($tokens_cfields))
				$tokens = array_merge($tokens, $tokens_cfields);
			
		} else {
			$tokens = array(
				'activity_id' => SearchFields_TimeTrackingEntry::ACTIVITY_ID,
				'created' => SearchFields_TimeTrackingEntry::LOG_DATE,
				'id' => SearchFields_TimeTrackingEntry::ID,
				'is_closed' => SearchFields_TimeTrackingEntry::IS_CLOSED,
				'mins' => SearchFields_TimeTrackingEntry::TIME_ACTUAL_MINS,
				'worker_id' => SearchFields_TimeTrackingEntry::WORKER_ID,
					
				'links' => SearchFields_TimeTrackingEntry::VIRTUAL_CONTEXT_LINK,
			);
		}
		
		if(isset($tokens[$token]))
			return $tokens[$token];
		
		return NULL;
	}
	
	function getContext($model) {
		$labels = $values = [];
		CerberusContexts::getContext(CerberusContexts::CONTEXT_TIMETRACKING, $model, $labels, $values, null, true);

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
			CerberusContexts::CONTEXT_TIMETRACKING,
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
			
			$custom_field_params = $this->_handleSearchBuildParamsCustomFields($filters, CerberusContexts::CONTEXT_TIMETRACKING);
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
			
			$models = CerberusContexts::getModels(CerberusContexts::CONTEXT_TIMETRACKING, array_keys($results));
			
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
		if(null == ($time_entry = DAO_TimeTrackingEntry::get($id)))
			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid time tracking ID '%d'", $id));
			
		// ACL
		if(!($worker->hasPriv('contexts.cerberusweb.contexts.timetracking.update') || $time_entry->worker_id == $worker->id))
			$this->error(self::ERRNO_ACL);
			
		$putfields = array(
			'activity_id' => 'integer',
			'created' => 'timestamp',
			'is_closed' => 'bit',
			'mins' => 'integer',
			'worker_id' => 'integer',
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

			switch($putfield) {
				// Verify that activity_id exists
				case 'activity_id':
					if(!empty($value))
						if(false == (DAO_TimeTrackingActivity::get($value)))
							$this->error(self::ERRNO_CUSTOM, sprintf("'%d' is not a valid %s.", $value, $putfield));
					break;
					
				// Verify that worker_id exists
				case 'worker_id':
					if(!empty($value))
						if(false == (DAO_Worker::get($value)))
							$this->error(self::ERRNO_CUSTOM, sprintf("'%d' is not a valid %s.", $value, $putfield));
					break;
			}
			
			$fields[$field] = $value;
		}
		
		// Handle custom fields
		$customfields = $this->_handleCustomFields($_POST);
		if(is_array($customfields))
			DAO_CustomFieldValue::formatAndSetFieldValues(CerberusContexts::CONTEXT_TIMETRACKING, $id, $customfields, true, true, true);
		
		// Update
		DAO_TimeTrackingEntry::update($id, $fields);
		$this->getId($id);
	}
	
	function postCreate() {
		$worker = CerberusApplication::getActiveWorker();
		
		// ACL
		if(!$worker->hasPriv('contexts.cerberusweb.contexts.timetracking.create'))
			$this->error(self::ERRNO_ACL);
		
		$postfields = array(
			'activity_id' => 'integer',
			'created' => 'timestamp',
			'is_closed' => 'bit',
			'mins' => 'integer',
			'worker_id' => 'integer',
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
			
			switch($postfield) {
				// Verify that activity_id exists
				case 'activity_id':
					if(!empty($value))
						if(false == (DAO_TimeTrackingActivity::get($value)))
							$this->error(self::ERRNO_CUSTOM, sprintf("'%d' is not a valid %s.", $value, $postfield));
					break;
					
				// Verify that worker_id exists
				case 'worker_id':
					if(!empty($value))
						if(false == (DAO_Worker::get($value)))
							$this->error(self::ERRNO_CUSTOM, sprintf("'%s' is not a valid worker id.", $postfield));
					break;
			}
			
			$fields[$field] = $value;
		}

		// Defaults
		if(!isset($fields[DAO_TimeTrackingEntry::LOG_DATE]))
			$fields[DAO_TimeTrackingEntry::LOG_DATE] = time();
		
		// Check required fields
		$reqfields = array(
			DAO_TimeTrackingEntry::TIME_ACTUAL_MINS,
			DAO_TimeTrackingEntry::WORKER_ID,
		);
		$this->_handleRequiredFields($reqfields, $fields);
		
		// Create
		if(false != ($id = DAO_TimeTrackingEntry::create($fields))) {
			// Custom fields
			
			$custom_fields = $this->_handleCustomFields($_POST);
			
			if(is_array($custom_fields))
				DAO_CustomFieldValue::formatAndSetFieldValues(CerberusContexts::CONTEXT_TIMETRACKING, $id, $custom_fields, true, true, true);
			
			// Result
			
			$this->getId($id);
		}
	}
};
