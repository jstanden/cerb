<?php
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
		
		if(null == ($task = DAO_Task::get($id)))
			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid calendar ID %d", $id));

		DAO_Task::delete($id);

		$result = array('id' => $id);
		$this->success($result);
		*/
	}

	function translateToken($token, $type='dao') {
		$tokens = array();
		
		if('dao'==$type) {
			$tokens = array(
				'name' => DAO_Calendar::NAME,
				'updated' => DAO_Calendar::UPDATED_AT,
			);
		} else {
			$tokens = array(
				'id' => SearchFields_Calendar::ID,
				'name' => SearchFields_Calendar::NAME,
				'updated' => SearchFields_Calendar::UPDATED_AT,
			);
		}
		
		if(isset($tokens[$token]))
			return $tokens[$token];
		
		return NULL;
	}

	function getContext($id) {
		$labels = array();
		$values = array();
		
		$context = CerberusContexts::getContext(CerberusContexts::CONTEXT_CALENDAR, $id, $labels, $values, null, true);

		@$month = DevblocksPlatform::importGPC($_REQUEST['month'], 'integer', date('m'));
		@$year = DevblocksPlatform::importGPC($_REQUEST['year'], 'integer', date('Y'));
		
		// Sanitize
		$month = DevblocksPlatform::intClamp($month, 1, 12);
		$year = DevblocksPlatform::intClamp($year, 1970, 3000);
		
		$calendar_scope = DevblocksCalendarHelper::getCalendar($month, $year);
		
		unset($calendar_scope['calendar_weeks']);
		
		$values['calendar_scope'] = $calendar_scope;
		
		return $values;
	}
	
	function getId($id) {
		$worker = CerberusApplication::getActiveWorker();
		
		// ACL
//		if(!$worker->hasPriv('...'))
//			$this->error("Access denied.");

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

		$calendars = DAO_Calendar::getReadableByWorker($worker);
		
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
	
	function search($filters=array(), $sortToken='id', $sortAsc=1, $page=1, $limit=10) {
		$worker = CerberusApplication::getActiveWorker();

		$custom_field_params = $this->_handleSearchBuildParamsCustomFields($filters, CerberusContexts::CONTEXT_CALENDAR);
		$params = $this->_handleSearchBuildParams($filters);
		$params = array_merge($params, $custom_field_params);
		
		// Sort
		$sortBy = $this->translateToken($sortToken, 'search');
		$sortAsc = !empty($sortAsc) ? true : false;
		
		// Search
		list($results, $total) = DAO_Calendar::search(
			!empty($sortBy) ? array($sortBy) : array(),
			$params,
			$limit,
			max(0,$page-1),
			$sortBy,
			$sortAsc,
			true
		);
		
		$objects = array();
		
		foreach($results as $id => $result) {
			$values = $this->getContext($id);
			$objects[$id] = $values;
		}
		
		$container = array(
			'total' => $total,
			'count' => count($objects),
			'page' => $page,
			'results' => $objects,
		);
		
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