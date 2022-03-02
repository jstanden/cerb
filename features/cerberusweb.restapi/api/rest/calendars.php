<?php
// [TODO] Calendar events via API
// [TODO] Recurring events via API
class ChRest_Calendars extends Extension_RestController implements IExtensionRestController {
	function getAction($stack) {
		@$action = array_shift($stack);
		
		// Looking up a single ID?
		if(is_numeric($action)) {
			$this->getId(intval($action));
			
		} else { // actions
			switch($action) {
				case 'list':
					$this->_getList();
					break;
			}
		}
		
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	function putAction($stack) {
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	function postAction($stack) {
		@$action = array_shift($stack);
		
		if(is_numeric($action) && !empty($stack)) {
			$id = intval($action);
			$action = array_shift($stack);
			
			switch($action) {
			}
			
		} else {
			switch($action) {
				case 'search':
					$this->postSearch();
					break;
			}
		}
		
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	function deleteAction($stack) {
		$this->error(self::ERRNO_NOT_IMPLEMENTED);

		/*
		$id = array_shift($stack);
		
		if(null == ($model = DAO_Task::get($id)))
			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid calendar ID %d", $id));
		
		//CerberusContexts::logActivityRecordDelete(CerberusContexts::CONTEXT_CALENDAR, $model->id, $model->name);

		DAO_Task::delete($id);

		$result = array('id' => $id);
		$this->success($result);
		*/
	}

	function translateToken($token, $type='dao') {
		if('custom_' == substr($token, 0, 7) && in_array($type, array('search', 'subtotal'))) {
			return 'cf_' . intval(substr($token, 7));
		}
		
		$tokens = array();
		
		if('dao'==$type) {
			$tokens = array(
				'name' => DAO_Calendar::NAME,
				'updated' => DAO_Calendar::UPDATED_AT,
			);
			
		} elseif ('subtotal'==$type) {
			$tokens = array(
				'fieldsets' => SearchFields_Calendar::VIRTUAL_HAS_FIELDSET,
				'links' => SearchFields_Calendar::VIRTUAL_CONTEXT_LINK,
				'owner' => SearchFields_Calendar::VIRTUAL_OWNER,
				'watchers' => SearchFields_Calendar::VIRTUAL_WATCHERS,
			);
			
			$tokens_cfields = $this->_handleSearchTokensCustomFields(CerberusContexts::CONTEXT_CALENDAR);
			
			if(is_array($tokens_cfields))
				$tokens = array_merge($tokens, $tokens_cfields);
			
		} else {
			$tokens = array(
				'id' => SearchFields_Calendar::ID,
				'name' => SearchFields_Calendar::NAME,
				'owner_context' => SearchFields_Calendar::OWNER_CONTEXT,
				'owner_context_id' => SearchFields_Calendar::OWNER_CONTEXT_ID,
				'updated' => SearchFields_Calendar::UPDATED_AT,
			);
		}
		
		if(isset($tokens[$token]))
			return $tokens[$token];
		
		return NULL;
	}

	function getContext($model) {
		$labels = array();
		$values = array();
		
		$context = CerberusContexts::getContext(CerberusContexts::CONTEXT_CALENDAR, $model, $labels, $values, null, true);

		$month = DevblocksPlatform::importGPC($_REQUEST['month'] ?? null, 'integer', date('m'));
		$year = DevblocksPlatform::importGPC($_REQUEST['year'] ?? null, 'integer', date('Y'));
		
		// Sanitize
		$month = DevblocksPlatform::intClamp($month, 1, 12);
		$year = DevblocksPlatform::intClamp($year, 1970, 3000);

		// [TODO] Handle 'Start on Monday' argument for this calendar

		// Set some variables to affect the scope in lazy loading
		$values['__scope_month'] = $month;
		$values['__scope_year'] = $year;
		
		return $values;
	}
	
	function getId($id) {
		$worker = CerberusApplication::getActiveWorker();
		
		// ACL
//		if(!$worker->hasPriv('...'))
//			$this->error(self::ERRNO_ACL);

		$container = $this->search(array(
			array('id', '=', $id),
		));

		if(is_array($container) && isset($container['results']) && isset($container['results'][$id]))
			$this->success($container['results'][$id]);

		// Error
		$this->error(self::ERRNO_CUSTOM, sprintf("Invalid calendar id '%d'", $id));
	}
	
	private function _getList() {
		$worker = CerberusApplication::getActiveWorker();

		$calendars = DAO_Calendar::getReadableByActor($worker);
		
		$results = array();

		foreach($calendars as $calendar) {
			$values = $this->getContext($calendar->id);
			$results[] = $values;
		}
		
		$container = array(
			'total' => count($results), // [TODO] $total
			'count' => count($results),
			'page' => 0,
			'results' => $results,
		);
		
		$this->success($container);
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
			CerberusContexts::CONTEXT_CALENDAR,
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
			
			$custom_field_params = $this->_handleSearchBuildParamsCustomFields($filters, CerberusContexts::CONTEXT_CALENDAR);
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
			
			$models = CerberusContexts::getModels(CerberusContexts::CONTEXT_CALENDAR, array_keys($results));
			
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
		
		// ACL
//		if(!$worker->hasPriv('core.addybook'))
//			$this->error(self::ERRNO_ACL);

		$container = $this->_handlePostSearch();
		
		$this->success($container);
	}
};